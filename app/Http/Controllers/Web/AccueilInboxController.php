<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AccessControlService;
use App\Services\DemandWorkflowService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class AccueilInboxController extends Controller
{
    public function __construct(
        private readonly AccessControlService $access,
        private readonly DemandWorkflowService $workflow,
        private readonly \App\Services\StepAlertService $alerts
    ) {
    }

    public function index(Request $request): View
    {
        $actor = $this->access->requireActor($request);
        $this->assertCanAccessAccueil((int) $actor->id_utilisateur);
        $this->alerts->refreshOpenDemandAlerts();

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'sort_by' => ['nullable', 'string', 'max:50'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'type_code' => ['nullable', 'string', 'max:80'],
            'direction_id' => ['nullable', 'integer', 'exists:directions,id_direction'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));
        $sortBy = (string) ($filters['sort_by'] ?? 'date_soumission');
        $sortDir = (string) ($filters['sort_dir'] ?? 'desc');
        $typeCode = (string) ($filters['type_code'] ?? '');
        $directionId = isset($filters['direction_id']) ? (int) $filters['direction_id'] : 0;
        $dateFrom = isset($filters['date_from']) ? (string) $filters['date_from'] : '';
        $dateTo = isset($filters['date_to']) ? (string) $filters['date_to'] : '';

        $baseQuery = DB::table('demandes as d')
            ->join('parametres as st', 'st.id_parametre', '=', 'd.id_statut')
            ->join('parametres as td', 'td.id_parametre', '=', 'd.id_type_demande')
            ->leftJoin('services as s', 's.id_service', '=', 'd.id_service_courant')
            ->leftJoin('directions as dir', 'dir.id_direction', '=', 's.id_direction')
            ->leftJoin('usagers as u', 'u.id_usager', '=', 'd.id_usager')
            ->select(
                'd.id_demande',
                'd.numero_suivi',
                'd.objet',
                'd.message',
                'd.date_soumission',
                'd.date_affectation_accueil',
                'd.alerte_accueil',
                'st.code as statut_code',
                'st.libelle as statut',
                'td.code as type_demande_code',
                'td.libelle as type_demande',
                's.id_direction',
                's.libelle as service',
                'dir.libelle as direction',
                'u.nom as usager_nom',
                'u.prenom as usager_prenom',
                'u.email as usager_email',
                'u.telephone as usager_telephone'
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
        if ($typeCode !== '') {
            $baseQuery->where('td.code', $typeCode);
        }
        if ($directionId > 0) {
            $baseQuery->where('s.id_direction', $directionId);
        }
        if ($dateFrom !== '') {
            $baseQuery->whereDate('d.date_soumission', '>=', $dateFrom);
        }
        if ($dateTo !== '') {
            $baseQuery->whereDate('d.date_soumission', '<=', $dateTo);
        }

        $sortBy = $this->normalizeSortBy($sortBy);

        $nouvelles = $this->applySort(clone $baseQuery, $sortBy, $sortDir)
            ->where('st.code', 'nouvelle')
            ->paginate(10, ['*'], 'nouvelles_page')
            ->withQueryString();

        $demandIds = collect($nouvelles->items())
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
                    'pj.type_mime',
                ])
                ->groupBy('id_demande');
        }

        return view('workflow.accueil-inbox', [
            'actor' => $actor,
            'nouvelles' => $nouvelles,
            'piecesByDemand' => $piecesByDemand,
            'services' => DB::table('services')
                ->where('actif', true)
                ->orderBy('id_direction')
                ->orderBy('code')
                ->get(['id_service', 'id_direction', 'code', 'libelle']),
            'directions' => DB::table('directions')
                ->where('actif', true)
                ->orderBy('code')
                ->get(['id_direction', 'code', 'libelle']),
            'types' => DB::table('parametres')
                ->where('famille', 'type_demande')
                ->where('actif', true)
                ->orderBy('ordre_affichage')
                ->get(['code', 'libelle']),
            'search' => $search,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'typeCode' => $typeCode,
            'directionId' => $directionId > 0 ? $directionId : null,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function affecter(Request $request, int $id): RedirectResponse
    {
        try {
            $actor = $this->access->requireActor($request);
            $this->access->assertPermission((int) $actor->id_utilisateur, 'demande.assign');

            $payload = $request->validate([
                'id_direction' => ['required', 'integer', 'exists:directions,id_direction'],
                'id_service' => ['required', 'integer', 'exists:services,id_service'],
                'commentaire' => ['nullable', 'string', 'max:2000'],
            ]);

            $serviceDirectionId = DB::table('services')
                ->where('id_service', (int) $payload['id_service'])
                ->where('actif', true)
                ->value('id_direction');

            if (!$serviceDirectionId) {
                throw new RuntimeException('Service invalide ou inactif.');
            }
            if ((int) $serviceDirectionId !== (int) $payload['id_direction']) {
                throw new RuntimeException('Le service selectionne n appartient pas a la direction choisie.');
            }

            $this->workflow->assignDemand(
                actorId: (int) $actor->id_utilisateur,
                demandId: $id,
                serviceId: (int) $payload['id_service'],
                comment: $payload['commentaire'] ?? null
            );

            return redirect()->back()->with('success', 'Demande affectee.');
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

            $payload = $request->validate([
                'contenu_reponse' => ['required', 'string', 'min:5'],
                'pieces_jointes' => ['nullable', 'array', 'max:5'],
                'pieces_jointes.*' => ['nullable', 'file', 'max:4096'],
            ]);

            $demandMeta = DB::table('demandes as d')
                ->join('parametres as st', 'st.id_parametre', '=', 'd.id_statut')
                ->join('parametres as td', 'td.id_parametre', '=', 'd.id_type_demande')
                ->where('d.id_demande', $id)
                ->select('st.code as statut_code', 'td.code as type_demande_code', 'd.date_affectation_accueil')
                ->first();

            if (!$demandMeta) {
                throw new RuntimeException('Demande introuvable.');
            }
            if ((string) $demandMeta->statut_code !== 'nouvelle') {
                throw new RuntimeException('Reponse directe impossible pour ce statut.');
            }
            if ((string) $demandMeta->type_demande_code !== 'demande_information') {
                throw new RuntimeException('Reponse directe reservee aux demandes d information simples.');
            }

            if (!$demandMeta->date_affectation_accueil) {
                DB::table('demandes')
                    ->where('id_demande', $id)
                    ->update([
                        'id_agent_accueil' => (int) $actor->id_utilisateur,
                        'id_service_courant' => $actor->id_service ? (int) $actor->id_service : null,
                        'date_affectation' => now(),
                        'date_affectation_accueil' => now(),
                        'updated_at' => now(),
                    ]);
            } elseif ($actor->id_service) {
                DB::table('demandes')
                    ->where('id_demande', $id)
                    ->update([
                        'id_service_courant' => (int) $actor->id_service,
                        'updated_at' => now(),
                    ]);
            }

            $draft = $this->workflow->draftResponse(
                actorId: (int) $actor->id_utilisateur,
                demandId: $id,
                content: trim($payload['contenu_reponse']),
                typeCode: 'directe'
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
                'type_action' => 'reponse_directe_accueil',
                'ancien_statut_id' => null,
                'nouveau_statut_id' => null,
                'id_service_associe' => $actor->id_service ? (int) $actor->id_service : null,
                'date_action' => now(),
                'commentaire' => 'Reponse directe accueil',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect()->back()->with('success', 'Reponse directe envoyee et demande cloturee.');
        } catch (AuthorizationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (ValidationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }


    private function assertCanAccessAccueil(int $userId): void
    {
        if (!$this->access->hasPermission($userId, 'demande.assign')) {
            throw new AuthorizationException('Acces reserve au role accueil.');
        }
    }

    private function normalizeSortBy(string $sortBy): string
    {
        $allowed = [
            'date_soumission',
            'numero_suivi',
            'type_demande',
            'direction',
            'alerte_accueil',
        ];

        if (!in_array($sortBy, $allowed, true)) {
            $sortBy = 'date_soumission';
        }

        return $sortBy;
    }

    private function applySort(Builder $query, string $sortBy, string $sortDir): Builder
    {
        if ($sortBy === 'numero_suivi') {
            $query->orderBy('d.numero_suivi', $sortDir);
        } elseif ($sortBy === 'type_demande') {
            $query->orderBy('td.libelle', $sortDir);
        } elseif ($sortBy === 'direction') {
            $query->orderBy('dir.libelle', $sortDir);
        } elseif ($sortBy === 'alerte_accueil') {
            $order = $sortDir === 'asc' ? 'asc' : 'desc';
            $query->orderByRaw(
                "case d.alerte_accueil when 'rouge' then 3 when 'orange' then 2 when 'vert' then 1 else 0 end {$order}"
            );
        } else {
            $query->orderBy('d.date_soumission', $sortDir);
        }

        $query->orderByDesc('d.id_demande');

        return $query;
    }
}
