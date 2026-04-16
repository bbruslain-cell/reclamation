<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Demande;
use App\Services\AccessControlService;
use App\Services\StepAlertService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DirectionInboxController extends Controller
{
    public function __construct(
        private readonly AccessControlService $access,
        private readonly StepAlertService $alerts
    ) {
    }

    public function index(Request $request): View
    {
        $actor = $this->access->requireActor($request);
        $this->assertChefDirectionAccess((int) $actor->id_utilisateur);
        $allowedStatusCodes = ['affectee_service', 'affectee_agent', 'reponse_prete', 'cloturee'];

        $allowedServiceIds = $this->access->scopedServiceIds((int) $actor->id_utilisateur);
        $this->alerts->refreshOpenDemandAlerts($allowedServiceIds);

        $baseQuery = DB::table('demandes as d')
            ->join('parametres as st', 'st.id_parametre', '=', 'd.id_statut')
            ->join('parametres as td', 'td.id_parametre', '=', 'd.id_type_demande')
            ->leftJoin('services as s', 's.id_service', '=', 'd.id_service_courant')
            ->leftJoin('usagers as u', 'u.id_usager', '=', 'd.id_usager')
            ->whereIn('d.id_service_courant', !empty($allowedServiceIds) ? $allowedServiceIds : [-1]);

        if ($request->filled('statut_code')) {
            $requestedStatus = (string) $request->string('statut_code');
            if (in_array($requestedStatus, $allowedStatusCodes, true)) {
                $baseQuery->where('st.code', $requestedStatus);
            }
        }
        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $baseQuery->where(function ($q) use ($search) {
                $q->where('d.numero_suivi', 'like', $search)
                    ->orWhere('d.objet', 'like', $search)
                    ->orWhere('u.nom', 'like', $search)
                    ->orWhere('u.prenom', 'like', $search);
            });
        }

        $servicePerformance = (clone $baseQuery)
            ->whereNotNull('s.id_service')
            ->select(
                's.id_service',
                's.code as service_code',
                's.libelle as service',
                DB::raw('COUNT(*) as total_demandes'),
                DB::raw("SUM(CASE WHEN st.code = 'cloturee' THEN 1 ELSE 0 END) as total_cloturees"),
                DB::raw("SUM(CASE WHEN st.code != 'cloturee' THEN 1 ELSE 0 END) as total_en_cours"),
                DB::raw("SUM(CASE WHEN d.delai_alerte = 'dans_les_delais' THEN 1 ELSE 0 END) as total_dans_les_delais"),
                DB::raw("SUM(CASE WHEN d.delai_alerte = 'a_risque' THEN 1 ELSE 0 END) as total_a_risque"),
                DB::raw("SUM(CASE WHEN d.delai_alerte = 'en_retard' THEN 1 ELSE 0 END) as total_en_retard"),
                DB::raw("AVG(CASE WHEN st.code = 'cloturee' THEN d.heures_ouvrees_cloture END) as delai_moyen_heures")
            )
            ->groupBy('s.id_service', 's.code', 's.libelle')
            ->orderByDesc('total_demandes')
            ->get()
            ->map(function ($row) {
                $totalCloturees = (int) $row->total_cloturees;
                $dansLesDelais = (int) $row->total_dans_les_delais;

                return [
                    'service_code' => (string) ($row->service_code ?? ''),
                    'service' => (string) ($row->service ?? '-'),
                    'total_demandes' => (int) $row->total_demandes,
                    'total_cloturees' => $totalCloturees,
                    'total_en_cours' => (int) $row->total_en_cours,
                    'total_dans_les_delais' => $dansLesDelais,
                    'total_a_risque' => (int) $row->total_a_risque,
                    'total_en_retard' => (int) $row->total_en_retard,
                    'taux_conformite' => $totalCloturees > 0
                        ? round(($dansLesDelais / $totalCloturees) * 100, 1)
                        : 0.0,
                    'delai_moyen_heures' => $row->delai_moyen_heures !== null
                        ? round((float) $row->delai_moyen_heures, 1)
                        : null,
                ];
            })
            ->values();

        $serviceMostLoaded = $servicePerformance->sortByDesc('total_demandes')->first();
        $serviceMostLate = $servicePerformance->sortByDesc('total_en_retard')->first();
        $serviceMostCompliant = $servicePerformance
            ->filter(fn (array $row) => $row['total_cloturees'] > 0)
            ->sortByDesc('taux_conformite')
            ->first();

        $query = (clone $baseQuery)
            ->select(
                'd.id_demande',
                'd.numero_suivi',
                'd.objet',
                'd.message',
                'd.date_soumission',
                'd.date_affectation_accueil',
                'd.date_affectation_agent',
                'd.date_envoi_usager',
                'd.date_cloture',
                'd.delai_alerte',
                'd.alerte_chef',
                'd.alerte_agent',
                'st.code as statut_code',
                'st.libelle as statut',
                'td.libelle as type_demande',
                's.code as service_code',
                's.libelle as service',
                'u.nom as usager_nom',
                'u.prenom as usager_prenom',
                'u.email as usager_email',
                'u.statut_usager as usager_statut',
                'u.pays as usager_pays',
                'u.etablissement as usager_etablissement'
            );

        $demandes = $query->orderByDesc('d.date_soumission')->paginate(20)->withQueryString();
        $demandIds = collect($demandes->items())
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

        return view('workflow.direction-inbox', [
            'actor' => $actor,
            'demandes' => $demandes,
            'search' => (string) $request->string('search', ''),
            'piecesByDemand' => $piecesByDemand,
            'servicePerformance' => $servicePerformance,
            'serviceSummary' => [
                'services_actifs' => $servicePerformance->count(),
                'service_plus_charge' => $serviceMostLoaded
                    ? trim(($serviceMostLoaded['service_code'] !== '' ? $serviceMostLoaded['service_code'].' - ' : '').$serviceMostLoaded['service'])
                    : '-',
                'demandes_plus_charge' => (int) ($serviceMostLoaded['total_demandes'] ?? 0),
                'service_plus_retard' => $serviceMostLate && (int) $serviceMostLate['total_en_retard'] > 0
                    ? trim(($serviceMostLate['service_code'] !== '' ? $serviceMostLate['service_code'].' - ' : '').$serviceMostLate['service'])
                    : '-',
                'retards_max' => (int) ($serviceMostLate['total_en_retard'] ?? 0),
                'meilleur_service' => $serviceMostCompliant
                    ? trim(($serviceMostCompliant['service_code'] !== '' ? $serviceMostCompliant['service_code'].' - ' : '').$serviceMostCompliant['service'])
                    : '-',
                'meilleur_taux' => (float) ($serviceMostCompliant['taux_conformite'] ?? 0),
            ],
            'statuts' => DB::table('parametres')
                ->where('famille', 'statut_demande')
                ->whereIn('code', $allowedStatusCodes)
                ->orderBy('ordre_affichage')
                ->get(['code', 'libelle']),
            'canPilotage' => Gate::forUser($actor)->allows('dashboard.view'),
        ]);
    }

    public function rediger(Request $request, int $id): RedirectResponse
    {
        try {
            $actor = $this->access->requireActor($request);
            $this->assertChefDirectionAccess((int) $actor->id_utilisateur);

            $demand = Demande::query()->find($id);
            if (!$demand) {
                throw new AuthorizationException('Demande introuvable.');
            }

            Gate::forUser($actor)->authorize('view', $demand);

            throw new AuthorizationException('Le chef de direction dispose d un acces en consultation uniquement.');
        } catch (AuthorizationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private function assertChefDirectionAccess(int $userId): void
    {
        $roles = $this->access->roleCodes($userId);

        if (in_array('admin', $roles, true) || in_array('chef_direction', $roles, true)) {
            return;
        }

        throw new AuthorizationException('Acces reserve au chef de direction.');
    }
}


