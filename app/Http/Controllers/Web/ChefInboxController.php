<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Concerns\InteractsWithServiceWindow;
use App\Services\AccessControlService;
use App\Services\DemandWorkflowService;
use App\Services\StepAlertService;
use App\Services\WorkingHoursSlaService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class ChefInboxController extends Controller
{
    use InteractsWithServiceWindow;

    public function __construct(
        private readonly AccessControlService $access,
        private readonly DemandWorkflowService $workflow,
        private readonly StepAlertService $alerts,
        private readonly WorkingHoursSlaService $slaService
    ) {
    }

    public function index(Request $request): View
    {
        $actor = $this->access->requireActor($request);
        $this->access->assertPermission((int) $actor->id_utilisateur, 'demande.assign.agent');

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));
        $allowedServiceIds = $this->access->scopedServiceIds((int) $actor->id_utilisateur);

        $this->alerts->refreshOpenDemandAlerts($allowedServiceIds);

        $baseQuery = DB::table('demandes as d')
            ->join('parametres as st', 'st.id_parametre', '=', 'd.id_statut')
            ->join('parametres as td', 'td.id_parametre', '=', 'd.id_type_demande')
            ->leftJoin('services as s', 's.id_service', '=', 'd.id_service_courant')
            ->leftJoin('usagers as u', 'u.id_usager', '=', 'd.id_usager')
            ->leftJoin('utilisateurs as ag', 'ag.id_utilisateur', '=', 'd.id_agent_traitant')
            ->whereIn('d.id_service_courant', !empty($allowedServiceIds) ? $allowedServiceIds : [-1])
            ->select(
                'd.id_demande',
                'd.numero_suivi',
                'd.objet',
                'd.message',
                'd.id_config_sla',
                'd.date_soumission',
                'd.date_affectation_accueil',
                'd.date_affectation_agent',
                'd.alerte_chef',
                'd.alerte_agent',
                'd.id_service_courant',
                'st.code as statut_code',
                'st.libelle as statut',
                'td.libelle as type_demande',
                's.code as service_code',
                's.libelle as service',
                'u.nom as usager_nom',
                'u.prenom as usager_prenom',
                'ag.nom as agent_nom',
                'ag.prenom as agent_prenom'
            );

        if ($search !== '') {
            $pattern = '%'.$search.'%';
            $baseQuery->where(function ($q) use ($pattern) {
                $q->where('d.numero_suivi', 'like', $pattern)
                    ->orWhere('d.objet', 'like', $pattern)
                    ->orWhere('u.nom', 'like', $pattern)
                    ->orWhere('u.prenom', 'like', $pattern);
            });
        }

        $pending = (clone $baseQuery)
            ->where('st.code', 'affectee_service')
            ->orderByDesc('d.date_affectation_accueil')
            ->paginate(15, ['*'], 'pending_page')
            ->withQueryString();
        $pending->setCollection(
            $pending->getCollection()->map(
                fn ($demande) => $this->withServiceWindowMeta($demande, $this->slaService)
            )
        );

        $assigned = (clone $baseQuery)
            ->whereIn('st.code', ['affectee_agent', 'reponse_prete'])
            ->orderByDesc('d.date_affectation_agent')
            ->paginate(15, ['*'], 'assigned_page')
            ->withQueryString();
        $assigned->setCollection(
            $assigned->getCollection()->map(
                fn ($demande) => $this->withServiceWindowMeta($demande, $this->slaService)
            )
        );

        $demandIds = collect($pending->items())
            ->concat($assigned->items())
            ->pluck('id_demande')
            ->unique()
            ->values()
            ->all();

        $piecesByDemand = collect();
        if (!empty($demandIds)) {
            $piecesByDemand = DB::table('demande_piece_jointe as dpj')
                ->join('pieces_jointes as pj', 'pj.id_piece_jointe', '=', 'dpj.id_piece_jointe')
                ->whereIn('dpj.id_demande', $demandIds)
                ->orderByDesc('pj.id_piece_jointe')
                ->get([
                    'dpj.id_demande',
                    'pj.id_piece_jointe',
                    'pj.nom_fichier',
                    'pj.chemin_fichier',
                ])
                ->groupBy('id_demande');
        }

        $agents = DB::table('utilisateurs as u')
            ->join('utilisateur_role as ur', 'ur.id_utilisateur', '=', 'u.id_utilisateur')
            ->join('roles as r', 'r.id_role', '=', 'ur.id_role')
            ->where('r.code', 'agent')
            ->whereIn('u.id_service', !empty($allowedServiceIds) ? $allowedServiceIds : [-1])
            ->orderBy('u.nom')
            ->get(['u.id_utilisateur', 'u.nom', 'u.prenom', 'u.id_service'])
            ->groupBy('id_service');

        return view('workflow.chef-inbox', [
            'actor' => $actor,
            'search' => $search,
            'pending' => $pending,
            'assigned' => $assigned,
            'piecesByDemand' => $piecesByDemand,
            'agentsByService' => $agents,
            'canPilotage' => $this->access->hasPermission((int) $actor->id_utilisateur, 'dashboard.view'),
        ]);
    }

    public function affecterAgent(Request $request, int $id): RedirectResponse
    {
        try {
            $actor = $this->access->requireActor($request);
            $this->access->assertPermission((int) $actor->id_utilisateur, 'demande.assign.agent');

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

            $agent = DB::table('utilisateurs as u')
                ->join('utilisateur_role as ur', 'ur.id_utilisateur', '=', 'u.id_utilisateur')
                ->join('roles as r', 'r.id_role', '=', 'ur.id_role')
                ->where('u.id_utilisateur', (int) $payload['id_agent'])
                ->where('r.code', 'agent')
                ->select('u.id_service')
                ->first();

            if (!$agent || (int) $agent->id_service !== (int) $demand->id_service_courant) {
                throw new RuntimeException('Agent invalide pour ce service.');
            }

            $this->workflow->assignAgent(
                actorId: (int) $actor->id_utilisateur,
                demandId: $id,
                agentId: (int) $payload['id_agent'],
                comment: $payload['commentaire'] ?? null
            );

            return redirect()->back()->with('success', 'Demande affectee a un agent.');
        } catch (AuthorizationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (ValidationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function reponseDirecte(Request $request, int $id): RedirectResponse
    {
        try {
            $actor = $this->access->requireActor($request);
            $this->access->assertPermission((int) $actor->id_utilisateur, 'demande.reply.send');
            $this->access->assertDemandAccess((int) $actor->id_utilisateur, $id);

            $payload = $request->validate([
                'contenu_reponse' => ['required', 'string', 'min:5'],
                'pieces_jointes' => ['nullable', 'array', 'max:5'],
                'pieces_jointes.*' => ['nullable', 'file', 'max:4096'],
            ]);

            $demandMeta = DB::table('demandes as d')
                ->join('parametres as st', 'st.id_parametre', '=', 'd.id_statut')
                ->where('d.id_demande', $id)
                ->select('st.code as statut_code', 'd.id_agent_traitant')
                ->first();

            if (!$demandMeta) {
                throw new RuntimeException('Demande introuvable.');
            }

            if ((int) ($demandMeta->id_agent_traitant ?? 0) > 0) {
                throw new RuntimeException('Le chef de service ne peut plus repondre directement apres affectation a un agent.');
            }

            if (!in_array((string) $demandMeta->statut_code, ['affectee_service', 'reponse_prete'], true)) {
                throw new RuntimeException('Reponse directe chef impossible pour ce statut.');
            }

            $draft = $this->workflow->draftResponse(
                actorId: (int) $actor->id_utilisateur,
                demandId: $id,
                content: trim($payload['contenu_reponse']),
                typeCode: 'finale'
            );

            $files = $request->file('pieces_jointes', []);
            $this->workflow->attachFilesToResponse(
                actorId: (int) $actor->id_utilisateur,
                responseId: (int) $draft['id_reponse'],
                files: is_array($files) ? $files : []
            );

            $this->workflow->sendFinalResponse(
                actorId: (int) $actor->id_utilisateur,
                demandId: $id
            );

            DB::table('historique_actions')->insert([
                'id_demande' => $id,
                'id_utilisateur' => (int) $actor->id_utilisateur,
                'type_action' => 'reponse_directe_chef',
                'ancien_statut_id' => null,
                'nouveau_statut_id' => null,
                'id_service_associe' => null,
                'date_action' => now(),
                'commentaire' => 'Reponse directe chef de service',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect()->back()->with('success', 'Reponse directe du chef envoyee et demande cloturee.');
        } catch (AuthorizationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (ValidationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function annulerAffectationAgent(Request $request, int $id): RedirectResponse
    {
        try {
            $actor = $this->access->requireActor($request);
            $this->access->assertPermission((int) $actor->id_utilisateur, 'demande.assign.agent');
            $this->access->assertDemandAccess((int) $actor->id_utilisateur, $id);

            $payload = $request->validate([
                'commentaire' => ['nullable', 'string', 'max:2000'],
            ]);

            $this->workflow->cancelAgentAssignment(
                actorId: (int) $actor->id_utilisateur,
                demandId: $id,
                comment: $payload['commentaire'] ?? null
            );

            return redirect()->back()->with('success', 'Affectation agent annulee.');
        } catch (AuthorizationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (ValidationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
