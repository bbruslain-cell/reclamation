<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Concerns\InteractsWithDeliveryStatus;
use App\Http\Requests\AssignServiceRequest;
use App\Http\Requests\CancelAssignmentRequest;
use App\Http\Requests\ListAccueilInboxRequest;
use App\Http\Requests\SendWorkflowResponseRequest;
use App\Models\Demande;
use App\Services\AccessControlService;
use App\Services\DemandWorkflowService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class AccueilInboxController extends Controller
{
    use InteractsWithDeliveryStatus;

    public function __construct(
        private readonly AccessControlService $access,
        private readonly DemandWorkflowService $workflow,
        private readonly \App\Services\StepAlertService $alerts
    ) {
    }

    public function index(ListAccueilInboxRequest $request): View
    {
        $actor = $this->access->requireActor($request);
        Gate::forUser($actor)->authorize('demande.assign');
        $this->alerts->refreshOpenDemandAlerts();

        $search = $request->searchTerm();
        $sortBy = $request->sortBy();
        $sortDir = $request->sortDirection();
        $typeCode = $request->typeCode();
        $directionId = $request->directionId();
        $dateFrom = $request->dateFrom();
        $dateTo = $request->dateTo();

        $baseQuery = DB::table('demandes as d')
            ->join('parametres as st', 'st.id_parametre', '=', 'd.id_statut')
            ->join('parametres as td', 'td.id_parametre', '=', 'd.id_type_demande')
            ->leftJoin('services as s', 's.id_service', '=', 'd.id_service_courant')
            ->leftJoin('directions as dir', 'dir.id_direction', '=', 's.id_direction')
            ->leftJoin('usagers as u', 'u.id_usager', '=', 'd.id_usager');

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

        $listQuery = (clone $baseQuery)
            ->leftJoinSub($this->latestActionIdsFor('affectation_service'), 'last_service_assignment', function ($join): void {
                $join->on('last_service_assignment.id_demande', '=', 'd.id_demande');
            })
            ->leftJoin('historique_actions as service_assignment_action', 'service_assignment_action.id_action', '=', 'last_service_assignment.id_action')
            ->select(
                'd.id_demande',
                'd.numero_suivi',
                'd.objet',
                'd.message',
                'd.date_soumission',
                'd.date_affectation_accueil',
                'd.date_demande_envoi_usager',
                'd.date_envoi_usager',
                'd.date_echec_envoi_usager',
                'd.alerte_accueil',
                'st.code as statut_code',
                'st.libelle as statut',
                'td.code as type_demande_code',
                'td.libelle as type_demande',
                's.id_direction',
                's.code as service_code',
                's.libelle as service',
                'dir.libelle as direction',
                'u.nom as usager_nom',
                'u.prenom as usager_prenom',
                'u.email as usager_email',
                DB::raw("'' as usager_telephone"),
                'u.statut_usager as usager_statut',
                'u.pays as usager_pays',
                'u.etablissement as usager_etablissement',
                'service_assignment_action.commentaire as commentaire_affectation_service',
                'service_assignment_action.date_action as date_commentaire_affectation_service'
            );

        $summaryStats = (clone $baseQuery)
            ->selectRaw("
                COUNT(*) as total_demandes,
                SUM(CASE WHEN st.code = 'nouvelle' THEN 1 ELSE 0 END) as total_nouvelles,
                SUM(CASE WHEN td.code = 'reclamation' THEN 1 ELSE 0 END) as total_reclamations,
                SUM(CASE WHEN st.code = 'nouvelle' AND d.alerte_accueil = 'orange' THEN 1 ELSE 0 END) as total_a_risque,
                SUM(CASE WHEN st.code = 'nouvelle' AND d.alerte_accueil = 'rouge' THEN 1 ELSE 0 END) as total_en_retard
            ")
            ->first();

        $sortBy = $this->normalizeSortBy($sortBy);

        $nouvelles = $this->applySort(clone $listQuery, $sortBy, $sortDir)
            ->where('st.code', 'nouvelle')
            ->paginate(10, ['*'], 'nouvelles_page')
            ->withQueryString();
        $nouvelles->setCollection(
            $nouvelles->getCollection()->map(
                fn ($demande) => $this->withDeliveryStatusMeta($demande)
            )
        );
        $affectees = $this->applySort(clone $listQuery, $sortBy, $sortDir)
            ->where(function ($query) use ($actor) {
                $query->where('st.code', 'affectee_service');

                if ($actor->id_service) {
                    $query->orWhere(function ($pendingQuery) use ($actor) {
                        $pendingQuery
                            ->where('st.code', 'reponse_prete')
                            ->where('d.id_service_courant', (int) $actor->id_service);
                    });
                }
            })
            ->paginate(10, ['*'], 'affectees_page')
            ->withQueryString();
        $affectees->setCollection(
            $affectees->getCollection()->map(
                fn ($demande) => $this->withDeliveryStatusMeta($demande)
            )
        );

        $demandIds = collect($nouvelles->items())
            ->merge($affectees->items())
            ->pluck('id_demande')
            ->unique()
            ->values()
            ->all();

        $piecesByDemand = collect();
        $historyByDemand = collect();
        $recentAccueilActions = DB::table('historique_actions as ha')
            ->join('demandes as d', 'd.id_demande', '=', 'ha.id_demande')
            ->leftJoin('usagers as u', 'u.id_usager', '=', 'd.id_usager')
            ->leftJoin('utilisateurs as actor_u', 'actor_u.id_utilisateur', '=', 'ha.id_utilisateur')
            ->leftJoin('services as s', 's.id_service', '=', 'ha.id_service_associe')
            ->whereIn('ha.type_action', [
                'affectation_service',
                'annulation_affectation_service',
                'reponse_directe_accueil',
                'envoi_reponse',
                'echec_envoi_reponse',
            ])
            ->orderByDesc('ha.date_action')
            ->limit(20)
            ->get([
                'ha.type_action',
                'ha.date_action',
                'ha.commentaire',
                'd.id_demande',
                'd.numero_suivi',
                'd.objet',
                'u.nom as usager_nom',
                'u.prenom as usager_prenom',
                'actor_u.nom as acteur_nom',
                'actor_u.prenom as acteur_prenom',
                's.code as service_code',
            ]);
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

            $historyByDemand = DB::table('historique_actions as ha')
                ->leftJoin('utilisateurs as u', 'u.id_utilisateur', '=', 'ha.id_utilisateur')
                ->leftJoin('services as s', 's.id_service', '=', 'ha.id_service_associe')
                ->leftJoin('parametres as old_st', 'old_st.id_parametre', '=', 'ha.ancien_statut_id')
                ->leftJoin('parametres as new_st', 'new_st.id_parametre', '=', 'ha.nouveau_statut_id')
                ->whereIn('ha.id_demande', $demandIds)
                ->orderByDesc('ha.date_action')
                ->get([
                    'ha.id_demande',
                    'ha.type_action',
                    'ha.date_action',
                    'ha.commentaire',
                    's.code as service_code',
                    'old_st.libelle as ancien_statut',
                    'new_st.libelle as nouveau_statut',
                    'u.nom as acteur_nom',
                    'u.prenom as acteur_prenom',
                ])
                ->groupBy('id_demande');
        }

        return view('workflow.accueil-inbox', [
            'actor' => $actor,
            'nouvelles' => $nouvelles,
            'affectees' => $affectees,
            'piecesByDemand' => $piecesByDemand,
            'historyByDemand' => $historyByDemand,
            'recentAccueilActions' => $recentAccueilActions,
            'summaryStats' => $summaryStats,
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

    public function affecter(AssignServiceRequest $request, int $id): RedirectResponse
    {
        try {
            $actor = $this->access->requireActor($request);
            $demand = Demande::query()->find($id);
            if (!$demand) {
                throw new RuntimeException('Demande introuvable.');
            }

            Gate::forUser($actor)->authorize('assignService', $demand);

            $serviceDirectionId = DB::table('services')
                ->where('id_service', $request->serviceId())
                ->where('actif', true)
                ->value('id_direction');

            if (!$serviceDirectionId) {
                throw new RuntimeException('Service invalide ou inactif.');
            }
            if ((int) $serviceDirectionId !== $request->directionId()) {
                throw new RuntimeException("Le service sélectionné n'appartient pas à la direction choisie.");
            }

            $this->workflow->assignDemand(
                actorId: (int) $actor->id_utilisateur,
                demandId: $id,
                serviceId: $request->serviceId(),
                comment: $request->comment()
            );

            return redirect()->back()->with('success', 'Demande affectée.');
        } catch (AuthorizationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (ValidationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function reponseDirecteAccueil(SendWorkflowResponseRequest $request, int $id): RedirectResponse
    {
        try {
            $actor = $this->access->requireActor($request);
            $demand = Demande::query()->find($id);
            if (!$demand) {
                throw new RuntimeException('Demande introuvable.');
            }

            Gate::forUser($actor)->authorize('reply', $demand);

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
                throw new RuntimeException('Réponse directe impossible pour ce statut.');
            }
            if ((string) $demandMeta->type_demande_code !== 'reclamation') {
                throw new RuntimeException("Seules les réclamations peuvent être clôturées directement à l'accueil.");
            }

            if (!$demandMeta->date_affectation_accueil) {
                DB::table('demandes')
                    ->where('id_demande', $id)
                    ->update([
                        'id_agent_accueil' => (int) $actor->id_utilisateur,
                        'id_service_courant' => $actor->id_service ? (int) $actor->id_service : null,
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
                content: $request->responseContent(),
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
                'commentaire' => "Réponse directe accueil envoyée à l'usager",
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect()->back()->with('success', 'Réponse directe mise en file. La réclamation sera clôturée après confirmation d\'envoi.');
        } catch (AuthorizationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (ValidationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /* Legacy direct-response block kept out of execution.
    
    

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
            if ((string) $demandMeta->type_demande_code !== 'legacy_disabled') {
                throw new RuntimeException('Cette action nest plus disponible.');
            }

            if (!$demandMeta->date_affectation_accueil) {
                DB::table('demandes')
                    ->where('id_demande', $id)
                    ->update([
                        'id_agent_accueil' => (int) $actor->id_utilisateur,
                        'id_service_courant' => $actor->id_service ? (int) $actor->id_service : null,
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
                'type_action' => 'action_accueil_desactivee',
                'ancien_statut_id' => null,
                'nouveau_statut_id' => null,
                'id_service_associe' => $actor->id_service ? (int) $actor->id_service : null,
                'date_action' => now(),
                'commentaire' => 'Action accueil desactivee',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect()->back()->with('succes', 'Action desactivÃƒÂ©e.');
        } catch (AuthorizationException $e) {
            return redirect()->back()->with('erreur', $e->getMessage());
        } catch (ValidationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return redirect()->back()->with('erreur', $e->getMessage());
        }
    }

    */
    public function annulerAffectation(CancelAssignmentRequest $request, int $id): RedirectResponse
    {
        try {
            $actor = $this->access->requireActor($request);
            $demand = Demande::query()->find($id);
            if (!$demand) {
                throw new RuntimeException('Demande introuvable.');
            }

            Gate::forUser($actor)->authorize('cancelServiceAssignment', $demand);

            $this->workflow->cancelServiceAssignment(
                actorId: (int) $actor->id_utilisateur,
                demandId: $id,
                comment: $request->comment()
            );

            return redirect()->back()->with('success', "Affectation annulée et demande retournée à l'accueil.");
        } catch (AuthorizationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (ValidationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
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

    private function latestActionIdsFor(string $typeAction): Builder
    {
        return DB::table('historique_actions')
            ->select('id_demande', DB::raw('MAX(id_action) as id_action'))
            ->where('type_action', $typeAction)
            ->groupBy('id_demande');
    }
}
