<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Demande;
use App\Services\AccessControlService;
use App\Services\DemandWorkflowService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class DemandWorkflowController extends Controller
{
    public function __construct(
        private readonly AccessControlService $access,
        private readonly DemandWorkflowService $workflow
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $actor = $this->access->requireActor($request);
        $userId = (int) $actor->id_utilisateur;
        $userGate = Gate::forUser($actor);
        $canViewAll = $userGate->allows('demande.view.all');
        $agentOnly = $userGate->allows('demande.reply.send')
            && !$userGate->allows('demande.assign')
            && !$userGate->allows('demande.assign.agent')
            && !$canViewAll;

        $query = DB::table('demandes as d')
            ->join('parametres as st', 'st.id_parametre', '=', 'd.id_statut')
            ->join('parametres as td', 'td.id_parametre', '=', 'd.id_type_demande')
            ->leftJoin('services as s', 's.id_service', '=', 'd.id_service_courant')
            ->leftJoin('usagers as u', 'u.id_usager', '=', 'd.id_usager')
            ->select(
                'd.id_demande',
                'd.numero_suivi',
                'd.objet',
                'd.date_soumission',
                'd.date_affectation_accueil',
                'd.date_affectation_agent',
                'd.alerte_accueil',
                'd.alerte_chef',
                'd.alerte_agent',
                'd.id_agent_traitant',
                'st.code as statut_code',
                'st.libelle as statut',
                'td.libelle as type_demande',
                's.libelle as service',
                DB::raw("COALESCE(u.nom, '') as usager_nom"),
                DB::raw("COALESCE(u.prenom, '') as usager_prenom")
            );

        if ($agentOnly) {
            $query->where('d.id_agent_traitant', $userId);
        } elseif (!$canViewAll) {
            $allowedServiceIds = $this->access->scopedServiceIds($userId);
            $query->whereIn('d.id_service_courant', $allowedServiceIds ?: [-1]);
        }

        if ($request->filled('statut_code')) {
            $query->where('st.code', $request->string('statut_code'));
        }
        if ($request->filled('service_id')) {
            $query->where('d.id_service_courant', (int) $request->integer('service_id'));
        }
        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($search) {
                $q->where('d.numero_suivi', 'like', $search)
                    ->orWhere('d.objet', 'like', $search)
                    ->orWhere('u.nom', 'like', $search)
                    ->orWhere('u.prenom', 'like', $search);
            });
        }

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $rows = $query->orderByDesc('d.date_soumission')->paginate($perPage);

        return response()->json($rows);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $actor = $this->access->requireActor($request);
        $demandModel = Demande::query()->find($id);
        if (!$demandModel) {
            return response()->json(['message' => 'Demande introuvable'], 404);
        }

        Gate::forUser($actor)->authorize('view', $demandModel);

        $demand = DB::table('demandes as d')
            ->join('parametres as st', 'st.id_parametre', '=', 'd.id_statut')
            ->join('parametres as td', 'td.id_parametre', '=', 'd.id_type_demande')
            ->leftJoin('services as s', 's.id_service', '=', 'd.id_service_courant')
            ->leftJoin('usagers as u', 'u.id_usager', '=', 'd.id_usager')
            ->where('d.id_demande', $id)
            ->select(
                'd.*',
                'st.code as statut_code',
                'st.libelle as statut',
                'td.code as type_code',
                'td.libelle as type_demande',
                's.libelle as service',
                'u.nom as usager_nom',
                'u.prenom as usager_prenom',
                'u.email as usager_email',
                DB::raw("'' as usager_telephone")
            )
            ->first();

        if (!$demand) {
            return response()->json(['message' => 'Demande introuvable'], 404);
        }

        $history = DB::table('historique_actions as h')
            ->leftJoin('utilisateurs as u', 'u.id_utilisateur', '=', 'h.id_utilisateur')
            ->leftJoin('parametres as os', 'os.id_parametre', '=', 'h.ancien_statut_id')
            ->leftJoin('parametres as ns', 'ns.id_parametre', '=', 'h.nouveau_statut_id')
            ->where('h.id_demande', $id)
            ->orderByDesc('h.date_action')
            ->select(
                'h.type_action',
                'h.date_action',
                'h.commentaire',
                'os.libelle as ancien_statut',
                'ns.libelle as nouveau_statut',
                'u.nom as acteur_nom',
                'u.prenom as acteur_prenom'
            )
            ->get();

        $responses = DB::table('reponses as r')
            ->leftJoin('utilisateurs as u', 'u.id_utilisateur', '=', 'r.id_redacteur')
            ->where('r.id_demande', $id)
            ->orderByDesc('numero_version')
            ->select('r.*', 'u.nom as redacteur_nom', 'u.prenom as redacteur_prenom')
            ->get();

        return response()->json([
            'demande' => $demand,
            'historique' => $history,
            'reponses' => $responses,
        ]);
    }

    public function affecter(Request $request, int $id): JsonResponse
    {
        try {
            $actor = $this->access->requireActor($request);
            $demand = Demande::query()->find($id);
            if (!$demand) {
                throw new RuntimeException('Demande introuvable.');
            }

            Gate::forUser($actor)->authorize('assignService', $demand);

            $payload = $request->validate([
                'id_service' => ['required', 'integer', 'exists:services,id_service'],
                'commentaire' => ['nullable', 'string', 'max:2000'],
            ]);

            $result = $this->workflow->assignDemand(
                actorId: (int) $actor->id_utilisateur,
                demandId: $id,
                serviceId: (int) $payload['id_service'],
                comment: $payload['commentaire'] ?? null
            );

            return response()->json(['message' => 'Demande affectee', 'result' => $result]);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation invalide', 'errors' => $e->errors()], 422);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function affecterAgent(Request $request, int $id): JsonResponse
    {
        try {
            $actor = $this->access->requireActor($request);
            $demandModel = Demande::query()->find($id);
            if (!$demandModel) {
                throw new RuntimeException('Demande introuvable.');
            }

            Gate::forUser($actor)->authorize('assignAgent', $demandModel);

            $payload = $request->validate([
                'id_agent' => ['required', 'integer', 'exists:utilisateurs,id_utilisateur'],
                'commentaire' => ['nullable', 'string', 'max:2000'],
            ]);

            $allowedServiceIds = $this->access->scopedServiceIds((int) $actor->id_utilisateur);
            $demand = DB::table('demandes')->where('id_demande', $id)->first();
            if (!$demand) {
                throw new RuntimeException('Demande introuvable.');
            }
            if (!in_array((int) $demand->id_service_courant, $allowedServiceIds, true)) {
                throw new AuthorizationException('Acces refuse sur cette demande.');
            }

            $agentService = DB::table('utilisateurs')->where('id_utilisateur', (int) $payload['id_agent'])->value('id_service');
            if (!$agentService || (int) $agentService !== (int) $demand->id_service_courant) {
                throw new RuntimeException('Agent invalide pour ce service.');
            }

            $agentHasRole = DB::table('utilisateur_role as ur')
                ->join('roles as r', 'r.id_role', '=', 'ur.id_role')
                ->where('ur.id_utilisateur', (int) $payload['id_agent'])
                ->where('r.code', 'agent')
                ->exists();
            if (!$agentHasRole) {
                throw new RuntimeException('L utilisateur selectionne n est pas un agent.');
            }

            $result = $this->workflow->assignAgent(
                actorId: (int) $actor->id_utilisateur,
                demandId: $id,
                agentId: (int) $payload['id_agent'],
                comment: $payload['commentaire'] ?? null
            );

            return response()->json(['message' => 'Demande affectee a un agent', 'result' => $result]);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation invalide', 'errors' => $e->errors()], 422);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function annulerAffectationAgent(Request $request, int $id): JsonResponse
    {
        try {
            $actor = $this->access->requireActor($request);
            $demand = Demande::query()->find($id);
            if (!$demand) {
                throw new RuntimeException('Demande introuvable.');
            }

            Gate::forUser($actor)->authorize('assignAgent', $demand);

            $payload = $request->validate([
                'commentaire' => ['nullable', 'string', 'max:2000'],
            ]);

            $result = $this->workflow->cancelAgentAssignment(
                actorId: (int) $actor->id_utilisateur,
                demandId: $id,
                comment: $payload['commentaire'] ?? null
            );

            return response()->json(['message' => 'Affectation agent annulee', 'result' => $result]);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation invalide', 'errors' => $e->errors()], 422);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function redigerReponse(Request $request, int $id): JsonResponse
    {
        try {
            $actor = $this->access->requireActor($request);
            $userId = (int) $actor->id_utilisateur;
            $demand = Demande::query()->find($id);
            if (!$demand) {
                throw new RuntimeException('Demande introuvable.');
            }

            Gate::forUser($actor)->authorize('draftReply', $demand);
            $this->assertChefDirectReplyAllowed($userId, $id);

            $payload = $request->validate([
                'contenu_reponse' => ['required', 'string', 'min:5'],
                'type_reponse_code' => ['nullable', 'string', 'max:80'],
            ]);

            $result = $this->workflow->draftResponse(
                actorId: (int) $actor->id_utilisateur,
                demandId: $id,
                content: $payload['contenu_reponse'],
                typeCode: $payload['type_reponse_code'] ?? null
            );

            return response()->json(['message' => 'Reponse enregistree', 'result' => $result]);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation invalide', 'errors' => $e->errors()], 422);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function envoyerReponse(Request $request, int $id): JsonResponse
    {
        try {
            $actor = $this->access->requireActor($request);
            $userId = (int) $actor->id_utilisateur;
            $demand = Demande::query()->find($id);
            if (!$demand) {
                throw new RuntimeException('Demande introuvable.');
            }

            Gate::forUser($actor)->authorize('reply', $demand);
            $this->assertChefDirectReplyAllowed($userId, $id);

            $result = $this->workflow->sendFinalResponse(
                actorId: (int) $actor->id_utilisateur,
                demandId: $id
            );

            return response()->json([
                'message' => 'Reponse mise en file. La demande sera cloturee apres confirmation d envoi.',
                'result' => $result,
            ]);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    private function assertChefDirectReplyAllowed(int $userId, int $demandId): void
    {
        if (!in_array('chef_service', $this->access->roleCodes($userId), true)) {
            return;
        }

        $demand = DB::table('demandes')
            ->where('id_demande', $demandId)
            ->select('id_agent_traitant')
            ->first();

        if (!$demand) {
            throw new RuntimeException('Demande introuvable.');
        }

        if ((int) ($demand->id_agent_traitant ?? 0) > 0) {
            throw new AuthorizationException('Le chef de service ne peut plus repondre directement apres affectation a un agent.');
        }
    }
}
