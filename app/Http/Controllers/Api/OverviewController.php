<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AccessControlService;
use App\Services\StepAlertService;
use App\Services\WorkingHoursSlaService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class OverviewController extends Controller
{
    private const ALLOWED_ALERT_COLUMNS = [
        'd.alerte_accueil',
        'd.alerte_chef',
        'd.alerte_agent',
    ];

    public function __construct(
        private readonly WorkingHoursSlaService $slaService,
        private readonly StepAlertService $stepAlerts
    )
    {
    }

    public function index(Request $request, AccessControlService $access): JsonResponse
    {
        $serviceScopeIds = null;

        try {
            $actor = $access->requireActor($request);
            $userId = (int) $actor->id_utilisateur;
            if (Gate::forUser($actor)->denies('dashboard.view')) {
                throw new AuthorizationException('Accès aux tableaux de bord refusé.');
            }

            $roleCodes = $access->roleCodes($userId);
            $canViewAll = Gate::forUser($actor)->allows('demande.view.all');
            $serviceScopeIds = $canViewAll ? null : $access->scopedServiceIds($userId);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        $this->stepAlerts->refreshOpenDemandAlerts($serviceScopeIds);

        $filters = $request->validate([
            'periode' => ['nullable', 'in:all,today,week,month,quarter,year,custom'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'direction_id' => ['nullable', 'integer', 'exists:directions,id_direction'],
            'service_id' => ['nullable', 'integer', 'exists:services,id_service'],
            'statut_code' => ['nullable', 'string', 'max:80'],
            'application_state' => ['nullable', 'in:appliquee,non_appliquee'],
        ]);

        $defaultPeriod = in_array('chef_direction', $roleCodes ?? [], true) ? 'all' : 'month';
        $periodCode = (string) ($filters['periode'] ?? $defaultPeriod);
        $directionId = isset($filters['direction_id']) ? (int) $filters['direction_id'] : null;
        $serviceId = isset($filters['service_id']) ? (int) $filters['service_id'] : null;
        $statusCode = isset($filters['statut_code']) && $filters['statut_code'] !== ''
            ? (string) $filters['statut_code']
            : null;
        $typeCode = null;
        $applicationState = isset($filters['application_state']) && $filters['application_state'] !== ''
            ? (string) $filters['application_state']
            : null;

        [$resolvedPeriod, $startAt, $endAt] = $this->resolvePeriodBounds(
            $periodCode,
            isset($filters['date_from']) ? (string) $filters['date_from'] : null,
            isset($filters['date_to']) ? (string) $filters['date_to'] : null,
        );

        $baseDemands = DB::table('demandes as d')
            ->join('parametres as st', 'st.id_parametre', '=', 'd.id_statut')
            ->join('parametres as td', 'td.id_parametre', '=', 'd.id_type_demande')
            ->leftJoin('services as s', 's.id_service', '=', 'd.id_service_courant')
            ->leftJoin('directions as dir', 'dir.id_direction', '=', 's.id_direction')
            ->leftJoin('usagers as u', 'u.id_usager', '=', 'd.id_usager');

        $this->applyScope($baseDemands, $serviceScopeIds);
        $this->applyDemandFilters(
            $baseDemands,
            $startAt,
            $endAt,
            $directionId,
            $serviceId,
            $statusCode,
            $typeCode,
            $applicationState,
            'd.date_soumission'
        );

        $activeSla = DB::table('config_sla')
            ->where('actif', true)
            ->orderByDesc('id_config_sla')
            ->first();

        $accueilAlerts = $this->alertCounts($baseDemands, 'd.alerte_accueil');
        $chefAlerts = $this->alertCounts($baseDemands, 'd.alerte_chef');
        $agentAlerts = $this->alertCounts($baseDemands, 'd.alerte_agent');

        $totalDemandes = (clone $baseDemands)->count('d.id_demande');
        $totalCloturees = (clone $baseDemands)
            ->where('st.code', 'cloturee')
            ->count('d.id_demande');
        $totalOuvertes = (clone $baseDemands)
            ->where('st.code', '!=', 'cloturee')
            ->count('d.id_demande');
        $directAccueilMetricsForKpis = $this->buildDirectAccueilMetrics(clone $baseDemands);
        $directAccueilDansDelais = (int) $directAccueilMetricsForKpis->total_traitees_delai;
        $directAccueilEnRetard = max(0, (int) $directAccueilMetricsForKpis->total_traitees - $directAccueilDansDelais);

        $totalGlobalDansDelais = (clone $baseDemands)
            ->where('d.delai_alerte', 'dans_les_delais')
            ->tap(fn (Builder $query) => $this->applyNonDirectAccueilFilter($query))
            ->count('d.id_demande') + $directAccueilDansDelais;
        $totalGlobalARisque = (clone $baseDemands)
            ->where('d.delai_alerte', 'a_risque')
            ->tap(fn (Builder $query) => $this->applyNonDirectAccueilFilter($query))
            ->count('d.id_demande');
        $totalRetard = (clone $baseDemands)
            ->where('d.delai_alerte', 'en_retard')
            ->tap(fn (Builder $query) => $this->applyNonDirectAccueilFilter($query))
            ->count('d.id_demande') + $directAccueilEnRetard;
        $totalClotureesDansDelai = (clone $baseDemands)
            ->where('st.code', 'cloturee')
            ->where('d.delai_alerte', 'dans_les_delais')
            ->tap(fn (Builder $query) => $this->applyNonDirectAccueilFilter($query))
            ->count('d.id_demande') + $directAccueilDansDelais;
        $delaiMoyenTraitement = (clone $baseDemands)
            ->where('st.code', 'cloturee')
            ->avg('d.heures_ouvrees_cloture');

        $tauxTraitementDelai = $totalCloturees > 0
            ? round(($totalClotureesDansDelai / $totalCloturees) * 100, 2)
            : 0.0;

        $statusRows = (clone $baseDemands)
            ->select('st.code', 'st.libelle', DB::raw('COUNT(*) as total'))
            ->groupBy('st.code', 'st.libelle')
            ->orderBy('st.libelle')
            ->get();

        $byDirection = (clone $baseDemands)
            ->select(
                DB::raw("COALESCE(dir.code, 'NON_AFFECTE') as direction_code"),
                DB::raw("COALESCE(dir.libelle, 'Non affecté') as direction"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy(DB::raw("COALESCE(dir.code, 'NON_AFFECTE')"), DB::raw("COALESCE(dir.libelle, 'Non affecté')"))
            ->orderByDesc('total')
            ->get();

        $directionPerfRaw = (clone $baseDemands)
            ->whereNotNull('dir.id_direction')
            ->select(
                'dir.id_direction',
                'dir.code as direction_code',
                'dir.libelle as direction',
                DB::raw('COUNT(*) as total_demandes'),
                DB::raw("SUM(CASE WHEN st.code = 'cloturee' THEN 1 ELSE 0 END) as total_cloturees"),
                DB::raw("SUM(CASE WHEN st.code = 'cloturee' AND d.delai_alerte = 'dans_les_delais' THEN 1 ELSE 0 END) as total_cloturees_delai"),
                DB::raw("AVG(CASE WHEN st.code = 'cloturee' THEN d.heures_ouvrees_cloture END) as delai_moyen_heures"),
                DB::raw("SUM(CASE WHEN d.alerte_chef = 'rouge' THEN 1 ELSE 0 END) as total_retards_chef"),
                DB::raw("SUM(CASE WHEN d.alerte_agent = 'rouge' THEN 1 ELSE 0 END) as total_retards_agent"),
                DB::raw("SUM(CASE WHEN d.alerte_chef = 'rouge' OR d.alerte_agent = 'rouge' THEN 1 ELSE 0 END) as total_retards")
            )
            ->groupBy('dir.id_direction', 'dir.code', 'dir.libelle')
            ->get()
            ->map(function ($row) {
                $cloturees = (int) $row->total_cloturees;
                $dansDelai = (int) $row->total_cloturees_delai;

                return [
                    'direction_code' => (string) $row->direction_code,
                    'direction' => (string) $row->direction,
                    'total_demandes' => (int) $row->total_demandes,
                    'total_cloturees' => $cloturees,
                    'total_cloturees_delai' => $dansDelai,
                    'total_cloturees_hors_delai' => max(0, $cloturees - $dansDelai),
                    'taux_reponse_dans_delais' => $cloturees > 0
                        ? round(($dansDelai / $cloturees) * 100, 2)
                        : 0.0,
                    'delai_moyen_heures' => $row->delai_moyen_heures !== null
                        ? round((float) $row->delai_moyen_heures, 2)
                        : null,
                    'retards_chef' => (int) $row->total_retards_chef,
                    'retards_agent' => (int) $row->total_retards_agent,
                    'retards_total' => (int) $row->total_retards,
                ];
            })
            ->sortByDesc('taux_reponse_dans_delais')
            ->values();

        $directionPerformance = $directionPerfRaw
            ->values()
            ->map(function (array $row, int $index) {
                $row['rang'] = $index + 1;
                return $row;
            });

        $serviceKpis = (clone $baseDemands)
            ->whereNotNull('s.id_service')
            ->select(
                's.id_service',
                's.code as service_code',
                's.libelle as service',
                'dir.libelle as direction',
                DB::raw('COUNT(*) as total_demandes'),
                DB::raw("SUM(CASE WHEN st.code = 'cloturee' THEN 1 ELSE 0 END) as total_cloturees"),
                DB::raw("SUM(CASE WHEN st.code = 'cloturee' AND d.delai_alerte = 'dans_les_delais' THEN 1 ELSE 0 END) as total_cloturees_delai"),
                DB::raw("AVG(CASE WHEN st.code = 'cloturee' THEN d.heures_ouvrees_cloture END) as delai_moyen_heures"),
                DB::raw("SUM(CASE WHEN d.alerte_chef = 'rouge' THEN 1 ELSE 0 END) as retards_chef"),
                DB::raw("SUM(CASE WHEN d.alerte_agent = 'rouge' THEN 1 ELSE 0 END) as retards_agent"),
                DB::raw("SUM(CASE WHEN d.alerte_chef = 'rouge' OR d.alerte_agent = 'rouge' THEN 1 ELSE 0 END) as retards_total")
            )
            ->groupBy('s.id_service', 's.code', 's.libelle', 'dir.libelle')
            ->orderByDesc('total_demandes')
            ->get()
            ->map(function ($row) {
                $cloturees = (int) $row->total_cloturees;
                $dansDelai = (int) $row->total_cloturees_delai;

                return [
                    'service_code' => (string) ($row->service_code ?? ''),
                    'service' => (string) $row->service,
                    'direction' => (string) $row->direction,
                    'total_demandes' => (int) $row->total_demandes,
                    'total_cloturees' => $cloturees,
                    'total_cloturees_delai' => $dansDelai,
                    'total_cloturees_hors_delai' => max(0, $cloturees - $dansDelai),
                    'taux_reponse_dans_delais' => $cloturees > 0
                        ? round(($dansDelai / $cloturees) * 100, 2)
                        : 0.0,
                    'delai_moyen_heures' => $row->delai_moyen_heures !== null
                        ? round((float) $row->delai_moyen_heures, 2)
                        : null,
                    'retards_chef' => (int) $row->retards_chef,
                    'retards_agent' => (int) $row->retards_agent,
                    'retards_total' => (int) $row->retards_total,
                ];
            });

        $agentKpis = (clone $baseDemands)
            ->whereNotNull('d.id_agent_traitant')
            ->leftJoin('utilisateurs as ag', 'ag.id_utilisateur', '=', 'd.id_agent_traitant')
            ->select(
                'ag.id_utilisateur',
                'ag.nom',
                'ag.prenom',
                's.libelle as service',
                DB::raw('COUNT(*) as total_demandes'),
                DB::raw("SUM(CASE WHEN d.alerte_agent = 'vert' THEN 1 ELSE 0 END) as verts"),
                DB::raw("SUM(CASE WHEN d.alerte_agent = 'orange' THEN 1 ELSE 0 END) as oranges"),
                DB::raw("SUM(CASE WHEN d.alerte_agent = 'rouge' THEN 1 ELSE 0 END) as rouges")
            )
            ->groupBy('ag.id_utilisateur', 'ag.nom', 'ag.prenom', 's.libelle')
            ->orderByDesc('total_demandes')
            ->limit(200)
            ->get()
            ->map(fn ($row) => [
                'agent' => trim(((string) ($row->prenom ?? '')).' '.((string) ($row->nom ?? ''))),
                'service' => (string) ($row->service ?? '-'),
                'total_demandes' => (int) $row->total_demandes,
                'verts' => (int) $row->verts,
                'oranges' => (int) $row->oranges,
                'rouges' => (int) $row->rouges,
            ]);

        $byType = (clone $baseDemands)
            ->select('td.code', 'td.libelle', DB::raw('COUNT(*) as total'))
            ->groupBy('td.code', 'td.libelle')
            ->orderBy('td.libelle')
            ->get()
            ->map(fn ($row) => [
                'code' => (string) $row->code,
                'libelle' => (string) $row->libelle,
                'total' => (int) $row->total,
            ]);

        $demandsByCountry = (clone $baseDemands)
            ->whereNotNull('u.pays')
            ->whereRaw("TRIM(u.pays) <> ''")
            ->selectRaw('TRIM(u.pays) as pays, COUNT(*) as total_demandes')
            ->groupByRaw('TRIM(u.pays)')
            ->orderByDesc('total_demandes')
            ->limit(12)
            ->get()
            ->values()
            ->map(fn ($row, int $index) => [
                'rang' => $index + 1,
                'pays' => (string) $row->pays,
                'total_demandes' => (int) $row->total_demandes,
            ]);

        $demandsByEstablishment = (clone $baseDemands)
            ->whereNotNull('u.etablissement')
            ->whereRaw("TRIM(u.etablissement) <> ''")
            ->selectRaw('TRIM(u.etablissement) as etablissement, COUNT(*) as total_demandes')
            ->groupByRaw('TRIM(u.etablissement)')
            ->orderByDesc('total_demandes')
            ->limit(12)
            ->get()
            ->values()
            ->map(fn ($row, int $index) => [
                'rang' => $index + 1,
                'etablissement' => (string) $row->etablissement,
                'total_demandes' => (int) $row->total_demandes,
            ]);

        $typePerformance = (clone $baseDemands)
            ->select(
                'td.code',
                'td.libelle',
                DB::raw('COUNT(*) as total_demandes'),
                DB::raw("SUM(CASE WHEN st.code = 'cloturee' THEN 1 ELSE 0 END) as total_cloturees"),
                DB::raw("SUM(CASE WHEN st.code = 'cloturee' AND d.delai_alerte = 'dans_les_delais' THEN 1 ELSE 0 END) as total_cloturees_delai"),
                DB::raw("SUM(CASE WHEN d.delai_alerte = 'en_retard' THEN 1 ELSE 0 END) as total_retards")
            )
            ->groupBy('td.code', 'td.libelle')
            ->orderByDesc('total_demandes')
            ->get()
            ->map(function ($row) {
                $cloturees = (int) $row->total_cloturees;
                $dansDelai = (int) $row->total_cloturees_delai;

                return [
                    'code' => (string) $row->code,
                    'libelle' => (string) $row->libelle,
                    'total_demandes' => (int) $row->total_demandes,
                    'taux_reponse_dans_delais' => $cloturees > 0
                        ? round(($dansDelai / $cloturees) * 100, 2)
                        : 0.0,
                    'total_retards' => (int) $row->total_retards,
                ];
            });

        $receivedEvolutionRows = (clone $baseDemands)
            ->select('d.date_soumission', 'td.code', 'td.libelle')
            ->orderBy('d.date_soumission')
            ->get();

        $closureEvolutionBase = DB::table('demandes as d')
            ->join('parametres as st', 'st.id_parametre', '=', 'd.id_statut')
            ->join('parametres as td', 'td.id_parametre', '=', 'd.id_type_demande')
            ->leftJoin('services as s', 's.id_service', '=', 'd.id_service_courant')
            ->leftJoin('directions as dir', 'dir.id_direction', '=', 's.id_direction')
            ->leftJoin('usagers as u', 'u.id_usager', '=', 'd.id_usager');

        $this->applyScope($closureEvolutionBase, $serviceScopeIds);
        $this->applyDemandFilters(
            $closureEvolutionBase,
            $startAt,
            $endAt,
            $directionId,
            $serviceId,
            $statusCode,
            $typeCode,
            $applicationState,
            'd.date_envoi_usager'
        );

        $closureEvolutionRows = (clone $closureEvolutionBase)
            ->where('st.code', 'cloturee')
            ->whereNotNull('d.date_envoi_usager')
            ->select('d.date_envoi_usager')
            ->orderBy('d.date_envoi_usager')
            ->get();

        $evolutionGranularity = $this->resolveEvolutionGranularity($resolvedPeriod, $startAt, $endAt);
        $bucketMetadata = $this->buildEvolutionBuckets($startAt, $endAt, $evolutionGranularity, $receivedEvolutionRows, $closureEvolutionRows);

        $typeLabels = [];
        $receivedByType = [];
        $receivedReclamations = [];
        $cloturees = [];

        foreach ($bucketMetadata as $bucketKey => $label) {
            $receivedByType[$bucketKey] = [];
            $receivedReclamations[$bucketKey] = 0;
            $cloturees[$bucketKey] = 0;
        }

        foreach ($receivedEvolutionRows as $row) {
            if (!$row->date_soumission) {
                continue;
            }

            $typeCodeKey = (string) $row->code;
            $typeLabels[$typeCodeKey] = (string) $row->libelle;
            $bucketKey = $this->evolutionBucketKey(Carbon::parse($row->date_soumission), $evolutionGranularity);

            if (!array_key_exists($bucketKey, $bucketMetadata)) {
                $bucketMetadata[$bucketKey] = $this->evolutionBucketLabel(Carbon::parse($row->date_soumission), $evolutionGranularity);
                $receivedByType[$bucketKey] = [];
                $receivedReclamations[$bucketKey] = 0;
                $cloturees[$bucketKey] = 0;
            }

            $receivedByType[$bucketKey][$typeCodeKey] = ($receivedByType[$bucketKey][$typeCodeKey] ?? 0) + 1;

            if ($typeCodeKey === 'reclamation') {
                $receivedReclamations[$bucketKey]++;
            }

        }

        foreach ($closureEvolutionRows as $row) {
            if (!$row->date_envoi_usager) {
                continue;
            }

            $bucketKey = $this->evolutionBucketKey(Carbon::parse($row->date_envoi_usager), $evolutionGranularity);

            if (!array_key_exists($bucketKey, $bucketMetadata)) {
                $bucketMetadata[$bucketKey] = $this->evolutionBucketLabel(Carbon::parse($row->date_envoi_usager), $evolutionGranularity);
                $receivedByType[$bucketKey] = [];
                $receivedReclamations[$bucketKey] = 0;
                $cloturees[$bucketKey] = 0;
            }

            $cloturees[$bucketKey]++;
        }

        ksort($bucketMetadata);
        ksort($receivedByType);
        ksort($receivedReclamations);
        ksort($cloturees);

        ksort($typeLabels);
        $evolutionByType = collect(array_keys($bucketMetadata))->map(function (string $bucketKey) use ($bucketMetadata, $receivedByType, $typeLabels) {
            $types = [];
            foreach ($typeLabels as $code => $label) {
                $types[] = [
                    'code' => $code,
                    'libelle' => $label,
                    'total' => (int) ($receivedByType[$bucketKey][$code] ?? 0),
                ];
            }

            return [
                'periode' => $bucketMetadata[$bucketKey],
                'types' => $types,
            ];
        })->values();

        $evolutionTemporelle = [
            'granularite' => $evolutionGranularity,
            'labels' => array_values($bucketMetadata),
            'series' => [
                'reclamations_recues' => collect(array_keys($bucketMetadata))->map(fn (string $key) => (int) ($receivedReclamations[$key] ?? 0))->values(),
                'demandes_cloturees' => collect(array_keys($bucketMetadata))->map(fn (string $key) => (int) ($cloturees[$key] ?? 0))->values(),
            ],
        ];

        $usagersPlusActifs = (clone $baseDemands)
            ->whereNotNull('u.id_usager')
            ->select(
                'u.id_usager',
                'u.nom',
                'u.prenom',
                'u.email',
                DB::raw('COUNT(*) as total_demandes'),
                DB::raw("SUM(CASE WHEN td.code = 'reclamation' THEN 1 ELSE 0 END) as total_reclamations")
            )
            ->groupBy('u.id_usager', 'u.nom', 'u.prenom', 'u.email')
            ->orderByDesc('total_demandes')
            ->limit(20)
            ->get()
            ->map(function ($row) {
                $total = (int) $row->total_demandes;
                $reclamations = (int) $row->total_reclamations;

                return [
                    'usager' => trim(((string) $row->prenom).' '.((string) $row->nom)),
                    'email' => $row->email,
                    'total_demandes' => $total,
                    'total_reclamations' => $reclamations,
                    'taux_reclamation' => $total > 0 ? round(($reclamations / $total) * 100, 2) : 0.0,
                ];
            });

        $historiqueUsagers = (clone $baseDemands)
            ->whereNotNull('u.id_usager')
            ->select(
                'd.numero_suivi',
                'd.date_soumission',
                'd.objet',
                'td.libelle as type_demande',
                'st.libelle as statut_demande',
                'u.nom',
                'u.prenom',
                'u.email'
            )
            ->orderByDesc('d.date_soumission')
            ->limit(200)
            ->get()
            ->map(fn ($row) => [
                'date_soumission' => $row->date_soumission,
                'numero_suivi' => $row->numero_suivi,
                'usager' => trim(((string) $row->prenom).' '.((string) $row->nom)),
                'email' => $row->email,
                'type_demande' => $row->type_demande,
                'statut_demande' => $row->statut_demande,
                'objet' => $row->objet,
            ]);

        $registreMails = (clone $baseDemands)
            ->whereNotNull('u.id_usager')
            ->select(
                'd.numero_suivi',
                'd.date_soumission',
                'd.objet',
                'd.message',
                'td.libelle as type_demande',
                'st.code as statut_code',
                'st.libelle as statut_demande',
                'dir.libelle as direction',
                's.libelle as service',
                'u.nom',
                'u.prenom',
                'u.email'
            )
            ->orderByDesc('d.date_soumission')
            ->limit(250)
            ->get()
            ->map(fn ($row) => [
                'numero_suivi' => $row->numero_suivi,
                'date_reception' => $row->date_soumission,
                'usager' => trim(((string) $row->prenom).' '.((string) $row->nom)),
                'email' => $row->email,
                'objet' => $row->objet,
                'message' => (string) ($row->message ?? ''),
                'type_demande' => $row->type_demande,
                'statut_demande' => $row->statut_demande,
                'statut_application' => $row->statut_code === 'cloturee' ? 'Appliquée' : 'Non appliquée',
                'direction' => $row->direction ?: '-',
                'service' => $row->service ?: '-',
            ]);

        $trackingPage = max(1, (int) $request->input('tracking_page', 1));
        $trackingPerPage = min(100, max(10, (int) $request->input('tracking_per_page', 25)));
        $trackingAll = $request->boolean('tracking_all', false);
        $trackingDemandId = max(0, (int) $request->input('tracking_demand_id', 0));

        $annexeBaseQuery = (clone $baseDemands)
            ->leftJoin('utilisateurs as ua', 'ua.id_utilisateur', '=', 'd.id_agent_accueil')
            ->leftJoin('utilisateurs as ut', 'ut.id_utilisateur', '=', 'd.id_agent_traitant')
            ->leftJoin('utilisateurs as ud', 'ud.id_utilisateur', '=', 'd.id_agent_direction');

        if ($trackingDemandId > 0) {
            $annexeBaseQuery->where('d.id_demande', $trackingDemandId);
        }

        $annexeTotalRows = (int) (clone $annexeBaseQuery)->count('d.id_demande');
        $annexeLastPage = max(1, (int) ceil($annexeTotalRows / max(1, $trackingPerPage)));

        if (!$trackingAll && $trackingDemandId <= 0) {
            $trackingPage = min($trackingPage, $annexeLastPage);
        }

        $annexeOffset = $trackingAll || $trackingDemandId > 0 ? 0 : ($trackingPage - 1) * $trackingPerPage;
        $annexeLimit = $trackingDemandId > 0
            ? 1
            : ($trackingAll ? min(5000, max(500, (int) $request->input('tracking_limit', 5000))) : $trackingPerPage);

        $annexeBaseRows = $annexeBaseQuery
            ->select(
                'd.id_demande',
                'd.numero_suivi',
                'd.id_config_sla',
                'd.date_soumission',
                'd.date_affectation_accueil',
                'd.date_affectation_agent',
                'd.id_agent_direction',
                'd.id_agent_traitant',
                'd.date_envoi_usager',
                'd.date_cloture',
                'd.objet',
                'd.categorie',
                'd.message',
                'd.delai_alerte',
                'st.code as statut_code',
                'st.libelle as statut_traitement',
                'td.code as type_demande_code',
                'td.libelle as type_demande',
                's.id_service as service_id',
                's.code as service_code',
                's.libelle as service_libelle',
                'dir.code as direction_code',
                'dir.libelle as direction_libelle',
                'u.nom as usager_nom',
                'u.prenom as usager_prenom',
                'u.email as usager_email',
                DB::raw("'' as usager_telephone"),
                'u.statut_usager as usager_statut',
                'u.pays as usager_pays',
                'u.etablissement as usager_etablissement',
                'ua.nom as accueil_nom',
                'ua.prenom as accueil_prenom',
                'ua.id_service as accueil_service_id',
                'ut.nom as agent_traitant_nom',
                'ut.prenom as agent_traitant_prenom',
                'ud.nom as direction_agent_nom',
                'ud.prenom as direction_agent_prenom'
            )
            ->orderByDesc('d.date_soumission')
            ->offset($annexeOffset)
            ->limit($annexeLimit)
            ->get();

        $annexeShownRows = $annexeBaseRows->count();
        $annexeFrom = $annexeTotalRows > 0 ? $annexeOffset + 1 : 0;
        $annexeTo = $annexeTotalRows > 0 ? min($annexeOffset + $annexeShownRows, $annexeTotalRows) : 0;

        $annexeChefByDemand = collect();
        $annexeDirectAccueilByDemand = collect();
        $annexeResponseServiceByDemand = collect();
        $annexeTreatmentActionsByDemand = collect();
        $annexeResponseDetailByDemand = collect();
        $annexePiecesByDemand = collect();
        $annexeDemandIds = $annexeBaseRows->pluck('id_demande')->filter()->values()->all();
        if (!empty($annexeDemandIds)) {
            $annexeChefByDemand = DB::table('historique_actions as ha')
                ->leftJoin('utilisateurs as uc', 'uc.id_utilisateur', '=', 'ha.id_utilisateur')
                ->whereIn('ha.id_demande', $annexeDemandIds)
                ->whereIn('ha.type_action', ['affectation_agent', 'reponse_directe_chef'])
                ->orderByDesc('ha.date_action')
                ->get([
                    'ha.id_demande',
                    'uc.nom as chef_nom',
                    'uc.prenom as chef_prenom',
                ])
                ->groupBy('id_demande')
                ->map(function ($rows) {
                    $first = $rows->first();

                    return trim(((string) ($first->chef_prenom ?? '')).' '.((string) ($first->chef_nom ?? '')));
                });

            $annexeDirectAccueilByDemand = DB::table('historique_actions as ha')
                ->leftJoin('utilisateurs as ua', 'ua.id_utilisateur', '=', 'ha.id_utilisateur')
                ->whereIn('ha.id_demande', $annexeDemandIds)
                ->where('ha.type_action', 'reponse_directe_accueil')
                ->orderByDesc('ha.date_action')
                ->get([
                    'ha.id_demande',
                    'ua.nom as accueil_nom',
                    'ua.prenom as accueil_prenom',
                ])
                ->groupBy('id_demande')
                ->map(function ($rows) {
                    $first = $rows->first();

                    return trim(((string) ($first->accueil_prenom ?? '')).' '.((string) ($first->accueil_nom ?? '')));
                });

            $annexeResponseServiceByDemand = DB::table('reponses as r')
                ->leftJoin('utilisateurs as ue', 'ue.id_utilisateur', '=', 'r.id_envoyeur')
                ->leftJoin('utilisateurs as ur', 'ur.id_utilisateur', '=', 'r.id_redacteur')
                ->whereIn('r.id_demande', $annexeDemandIds)
                ->orderByDesc('r.numero_version')
                ->orderByDesc('r.date_envoi_usager')
                ->get([
                    'r.id_demande',
                    'ue.id_service as envoyeur_service_id',
                    'ur.id_service as redacteur_service_id',
                ])
                ->groupBy('id_demande')
                ->map(function ($rows) {
                    $first = $rows->first();
                    $serviceId = (int) (($first->envoyeur_service_id ?? $first->redacteur_service_id ?? 0) ?: 0);

                    return $serviceId > 0 ? $serviceId : null;
                });

            $annexeTreatmentActionsByDemand = DB::table('historique_actions as ha')
                ->leftJoin('utilisateurs as actor', 'actor.id_utilisateur', '=', 'ha.id_utilisateur')
                ->leftJoin('utilisateurs as assigned_agent', 'assigned_agent.id_utilisateur', '=', 'ha.id_agent_associe')
                ->leftJoin('services as svc', 'svc.id_service', '=', 'ha.id_service_associe')
                ->leftJoin('directions as dir', 'dir.id_direction', '=', 'svc.id_direction')
                ->whereIn('ha.id_demande', $annexeDemandIds)
                ->whereIn('ha.type_action', [
                    'soumission_usager',
                    'affectation_service',
                    'annulation_affectation_service',
                    'affectation_agent',
                    'annulation_affectation_agent',
                    'reponse_redigee',
                    'envoi_reponse',
                    'reponse_directe_accueil',
                    'reponse_directe_chef',
                ])
                ->orderBy('ha.date_action')
                ->get([
                    'ha.id_demande',
                    'ha.type_action',
                    'ha.date_action',
                    'ha.commentaire',
                    'actor.nom as acteur_nom',
                    'actor.prenom as acteur_prenom',
                    'assigned_agent.nom as agent_nom',
                    'assigned_agent.prenom as agent_prenom',
                    'svc.code as service_code',
                    'svc.libelle as service_libelle',
                    'dir.code as direction_code',
                    'dir.libelle as direction_libelle',
                ])
                ->groupBy('id_demande')
                ->map(function ($rows) {
                    return $rows->map(function ($action) {
                        $acteur = trim(((string) ($action->acteur_prenom ?? '')).' '.((string) ($action->acteur_nom ?? '')));
                        $agent = trim(((string) ($action->agent_prenom ?? '')).' '.((string) ($action->agent_nom ?? '')));

                        return [
                            'type_action' => (string) $action->type_action,
                            'libelle' => $this->humanActionLabel((string) $action->type_action),
                            'date_action' => $action->date_action,
                            'acteur' => $acteur !== ''
                                ? $acteur
                                : ((string) $action->type_action === 'soumission_usager' ? 'Usager' : 'Système'),
                            'agent' => $agent !== '' ? $agent : null,
                            'service' => trim((string) ($action->service_code ?? '')) ?: null,
                            'service_libelle' => trim((string) ($action->service_libelle ?? '')) ?: null,
                            'direction' => trim((string) ($action->direction_code ?? '')) ?: null,
                            'direction_libelle' => trim((string) ($action->direction_libelle ?? '')) ?: null,
                            'commentaire' => $this->humanActionComment((string) $action->type_action, $action->commentaire),
                        ];
                    })->values()->all();
                });

            $annexeResponseDetailByDemand = DB::table('reponses as r')
                ->leftJoin('parametres as tr', 'tr.id_parametre', '=', 'r.id_type_reponse')
                ->leftJoin('utilisateurs as redacteur', 'redacteur.id_utilisateur', '=', 'r.id_redacteur')
                ->leftJoin('utilisateurs as envoyeur', 'envoyeur.id_utilisateur', '=', 'r.id_envoyeur')
                ->whereIn('r.id_demande', $annexeDemandIds)
                ->orderByDesc('r.numero_version')
                ->orderByDesc('r.date_envoi_usager')
                ->orderByDesc('r.date_redaction')
                ->get([
                    'r.id_demande',
                    'r.numero_version',
                    'r.contenu_reponse',
                    'r.date_redaction',
                    'r.date_envoi_usager',
                    'tr.libelle as type_reponse',
                    'redacteur.nom as redacteur_nom',
                    'redacteur.prenom as redacteur_prenom',
                    'envoyeur.nom as envoyeur_nom',
                    'envoyeur.prenom as envoyeur_prenom',
                ])
                ->groupBy('id_demande')
                ->map(function ($rows) {
                    $response = $rows->first();
                    $redacteur = trim(((string) ($response->redacteur_prenom ?? '')).' '.((string) ($response->redacteur_nom ?? '')));
                    $envoyeur = trim(((string) ($response->envoyeur_prenom ?? '')).' '.((string) ($response->envoyeur_nom ?? '')));

                    return [
                        'numero_version' => (int) ($response->numero_version ?? 1),
                        'type_reponse' => (string) ($response->type_reponse ?? ''),
                        'contenu' => (string) ($response->contenu_reponse ?? ''),
                        'date_redaction' => $response->date_redaction,
                        'date_envoi_usager' => $response->date_envoi_usager,
                        'redacteur' => $redacteur !== '' ? $redacteur : '-',
                        'envoyeur' => $envoyeur !== '' ? $envoyeur : null,
                    ];
                });

            $annexePiecesByDemand = DB::table('demande_piece_jointe as dpj')
                ->join('pieces_jointes as pj', 'pj.id_piece_jointe', '=', 'dpj.id_piece_jointe')
                ->whereIn('dpj.id_demande', $annexeDemandIds)
                ->orderByDesc('pj.id_piece_jointe')
                ->get([
                    'dpj.id_demande',
                    'pj.id_piece_jointe',
                    'pj.nom_fichier',
                ])
                ->groupBy('id_demande')
                ->map(fn ($rows) => $rows
                    ->map(fn ($piece) => [
                        'id_piece_jointe' => (int) $piece->id_piece_jointe,
                        'nom_fichier' => (string) $piece->nom_fichier,
                    ])
                    ->values()
                    ->all());
        }

        $serviceMetaById = DB::table('services')
            ->get(['id_service', 'code', 'libelle'])
            ->keyBy('id_service');

        $serviceChefByServiceId = DB::table('utilisateurs as u')
            ->join('utilisateur_role as ur', 'ur.id_utilisateur', '=', 'u.id_utilisateur')
            ->join('roles as r', 'r.id_role', '=', 'ur.id_role')
            ->where('r.code', 'chef_service')
            ->whereNotNull('u.id_service')
            ->orderBy('u.prenom')
            ->orderBy('u.nom')
            ->get([
                'u.id_service',
                'u.nom',
                'u.prenom',
            ])
            ->groupBy('id_service')
            ->map(function ($rows) {
                $first = $rows->first();

                return trim(((string) ($first->prenom ?? '')).' '.((string) ($first->nom ?? '')));
            });

        $accueilThresholds = $this->stepAlerts->thresholdsForStep('accueil');
        $serviceThresholds = $this->stepAlerts->thresholdsForStep('chef');

        $annexeRows = $annexeBaseRows
            ->values()
            ->map(function ($row, int $index) use ($annexeChefByDemand, $annexeDirectAccueilByDemand, $annexeResponseServiceByDemand, $annexeTreatmentActionsByDemand, $annexeResponseDetailByDemand, $annexePiecesByDemand, $serviceChefByServiceId, $serviceMetaById, $accueilThresholds, $serviceThresholds) {
                $dispatching = $row->date_affectation_accueil ? Carbon::parse($row->date_affectation_accueil) : null;
                $reception = $row->date_soumission ? Carbon::parse($row->date_soumission) : null;

                $transmissionOk = false;
                if ($dispatching && $reception) {
                    $transmissionOk = $this->slaService->calculateElapsedHours(
                        $reception,
                        $dispatching,
                        (int) $row->id_config_sla
                    ) <= (float) ($accueilThresholds['deadline'] ?? 8);
                }

                $realisationDate = $row->date_envoi_usager ?: $row->date_cloture;

                $serviceId = $row->service_id ? (int) $row->service_id : null;
                if ($serviceId === null) {
                    $serviceId = (int) ($annexeResponseServiceByDemand->get($row->id_demande, 0) ?: 0) ?: null;
                }
                if ($serviceId === null && !empty($row->accueil_service_id)) {
                    $serviceId = (int) $row->accueil_service_id;
                }
                $globalAlertCode = $reception
                    ? $this->resolveGlobalDelayAlert(
                        $row->date_soumission,
                        $realisationDate,
                        (int) $row->id_config_sla
                    )
                    : ($row->delai_alerte ?? null);
                $isDirectAccueil = $annexeDirectAccueilByDemand->has($row->id_demande);
                $respectDelais = $isDirectAccueil
                    ? ($this->isWithinAccueilDirectDeadline(
                        $row->date_soumission,
                        $realisationDate,
                        (int) $row->id_config_sla
                    ) ? 'OUI' : 'NON')
                    : ($globalAlertCode === 'en_retard' ? 'NON' : 'OUI');
                $responsableRetard = $this->resolveGlobalDelayOwner(
                    $row,
                    $realisationDate,
                    (float) ($accueilThresholds['deadline'] ?? 8),
                    (float) ($serviceThresholds['deadline'] ?? 16),
                    $globalAlertCode
                );
                $serviceMeta = $serviceId !== null ? $serviceMetaById->get($serviceId) : null;
                $serviceLabel = trim((string) ($serviceMeta->code ?? $row->service_code ?? $row->service_libelle ?? '-'));

                if ($isDirectAccueil) {
                    $serviceLabel = trim((string) ($serviceMeta->code ?? 'UCAS'));
                }

                $responsableReponseFallback = $isDirectAccueil
                    ? (string) ($annexeDirectAccueilByDemand->get($row->id_demande, '') ?: '')
                    : (string) ($annexeChefByDemand->get($row->id_demande, '') ?: '');
                if ($responsableReponseFallback === '' && $serviceId !== null) {
                    $responsableReponseFallback = (string) $serviceChefByServiceId->get($serviceId, '');
                }

                $expediteur = trim(((string) ($row->usager_prenom ?? '')).' '.((string) ($row->usager_nom ?? '')));
                $piecesJointes = $annexePiecesByDemand->get($row->id_demande, []);
                $traitementActions = collect($annexeTreatmentActionsByDemand->get($row->id_demande, []));
                $affectationAccueil = $traitementActions->where('type_action', 'affectation_service')->last();
                $affectationAgent = $traitementActions->where('type_action', 'affectation_agent')->last();
                $reponseDetail = $annexeResponseDetailByDemand->get($row->id_demande, null);
                $reponseRedacteur = is_array($reponseDetail)
                    ? trim((string) ($reponseDetail['redacteur'] ?? ''))
                    : '';
                $reponseEnvoyeur = is_array($reponseDetail)
                    ? trim((string) ($reponseDetail['envoyeur'] ?? ''))
                    : '';
                $qcs = $reponseRedacteur !== '' && $reponseRedacteur !== '-'
                    ? $reponseRedacteur
                    : ($reponseEnvoyeur !== '' && $reponseEnvoyeur !== '-'
                        ? $reponseEnvoyeur
                        : $responsableReponseFallback);

                $agentAccueil = trim(((string) ($row->accueil_prenom ?? '')).' '.((string) ($row->accueil_nom ?? '')));
                $agentTraitant = trim(((string) ($row->agent_traitant_prenom ?? '')).' '.((string) ($row->agent_traitant_nom ?? '')));
                $agentDirection = trim(((string) ($row->direction_agent_prenom ?? '')).' '.((string) ($row->direction_agent_nom ?? '')));
                $chefAffectation = is_array($affectationAgent ?? null) ? (string) ($affectationAgent['acteur'] ?? '') : '';
                $agentAffecte = is_array($affectationAgent ?? null) ? (string) ($affectationAgent['agent'] ?? '') : '';
                $serviceAffecte = is_array($affectationAccueil ?? null)
                    ? (string) (($affectationAccueil['service'] ?? '') ?: ($affectationAccueil['service_libelle'] ?? ''))
                    : $serviceLabel;
                $serviceAffecteLibelle = is_array($affectationAccueil ?? null)
                    ? (string) (($affectationAccueil['service_libelle'] ?? '') ?: ($affectationAccueil['service'] ?? ''))
                    : (string) ($serviceMeta->libelle ?? $row->service_libelle ?? $serviceLabel);
                $directionAffectee = is_array($affectationAccueil ?? null)
                    ? (string) (($affectationAccueil['direction'] ?? '') ?: ($affectationAccueil['direction_libelle'] ?? ''))
                    : (string) ($row->direction_code ?? $row->direction_libelle ?? '');
                $affectationAccueilDate = is_array($affectationAccueil ?? null)
                    ? ($affectationAccueil['date_action'] ?? $row->date_affectation_accueil)
                    : $row->date_affectation_accueil;
                $affectationAgentDate = is_array($affectationAgent ?? null)
                    ? ($affectationAgent['date_action'] ?? $row->date_affectation_agent)
                    : $row->date_affectation_agent;

                return [
                    'id_demande' => (int) $row->id_demande,
                    'rang' => $index + 1,
                    'numero_suivi' => $row->numero_suivi,
                    'date_reception' => $row->date_soumission,
                    'expediteur' => $expediteur,
                    'objet' => (string) ($row->categorie ?: ($row->objet ?: $row->type_demande ?: '-')),
                    'date_dispatching' => $row->date_affectation_accueil,
                    'delai_transmission_oh' => $row->date_affectation_accueil ? ($transmissionOk ? 'OUI' : 'NON') : '-',
                    'service_direction' => $serviceLabel !== '' ? $serviceLabel : '-',
                    'realisation' => $realisationDate,
                    'statut_code' => (string) ($row->statut_code ?? ''),
                    'statut_traitement' => $this->humanDemandStatusLabel(
                        (string) ($row->statut_code ?? ''),
                        (string) ($row->statut_traitement ?? '')
                    ),
                    'respect_delais' => $respectDelais,
                    'jours_attente' => $this->formatBusinessDuration(
                        $row->date_soumission,
                        $realisationDate,
                        (int) $row->id_config_sla
                    ),
                    'responsable_retard' => $responsableRetard,
                    'qcs' => $qcs !== '' ? $qcs : '-',
                    'usager_nom' => (string) ($row->usager_nom ?? ''),
                    'usager_prenom' => (string) ($row->usager_prenom ?? ''),
                    'usager_email' => (string) ($row->usager_email ?? ''),
                    'usager_telephone' => (string) ($row->usager_telephone ?? ''),
                    'usager_statut' => (string) ($row->usager_statut ?? ''),
                    'usager_pays' => (string) ($row->usager_pays ?? ''),
                    'usager_etablissement' => (string) ($row->usager_etablissement ?? ''),
                    'type_demande' => (string) ($row->type_demande ?? '-'),
                    'type_demande_code' => (string) ($row->type_demande_code ?? ''),
                    'categorie' => (string) ($row->categorie ?? ''),
                    'objet_original' => (string) ($row->objet ?? ''),
                    'message' => (string) ($row->message ?? ''),
                    'pieces_jointes' => $piecesJointes,
                    'traitement' => [
                        'affectation_accueil' => [
                            'agent' => is_array($affectationAccueil ?? null)
                                ? (string) ($affectationAccueil['acteur'] ?? ($agentAccueil ?: '-'))
                                : ($agentAccueil !== '' ? $agentAccueil : '-'),
                            'service' => trim($serviceAffecte) !== '' ? $serviceAffecte : '-',
                            'service_libelle' => trim($serviceAffecteLibelle) !== '' ? $serviceAffecteLibelle : '-',
                            'direction' => trim($directionAffectee) !== '' ? $directionAffectee : '-',
                            'date' => $affectationAccueilDate,
                            'commentaire' => is_array($affectationAccueil ?? null) ? ($affectationAccueil['commentaire'] ?? null) : null,
                        ],
                        'affectation_agent' => [
                            'chef' => $chefAffectation !== '' ? $chefAffectation : ($responsableReponseFallback !== '' ? $responsableReponseFallback : '-'),
                            'agent' => $agentAffecte !== '' ? $agentAffecte : ($agentTraitant !== '' ? $agentTraitant : '-'),
                            'date' => $affectationAgentDate,
                            'commentaire' => is_array($affectationAgent ?? null) ? ($affectationAgent['commentaire'] ?? null) : null,
                        ],
                        'reponse' => $reponseDetail ?: [
                            'numero_version' => null,
                            'type_reponse' => null,
                            'contenu' => '',
                            'date_redaction' => null,
                            'date_envoi_usager' => $realisationDate,
                            'redacteur' => $agentDirection !== '' ? $agentDirection : '-',
                            'envoyeur' => null,
                        ],
                        'historique' => $traitementActions->values()->all(),
                    ],
                ];
            });

        $registreControleInterne = (clone $baseDemands)
            ->leftJoin('utilisateurs as ua', 'ua.id_utilisateur', '=', 'd.id_agent_accueil')
            ->leftJoin('utilisateurs as ut', 'ut.id_utilisateur', '=', 'd.id_agent_traitant')
            ->leftJoin('utilisateurs as ud', 'ud.id_utilisateur', '=', 'd.id_agent_direction')
            ->select(
                'd.numero_suivi',
                'd.objet',
                'd.date_soumission',
                'd.date_affectation_accueil',
                'd.date_affectation_agent',
                'd.date_reponse_direction',
                'd.date_envoi_usager',
                'd.date_cloture',
                'd.heures_ouvrees_cloture',
                'd.delai_alerte',
                'st.code as statut_code',
                'st.libelle as statut_traitement',
                'td.libelle as type_demande',
                'dir.libelle as direction',
                's.libelle as service',
                'u.nom as usager_nom',
                'u.prenom as usager_prenom',
                'ua.nom as accueil_nom',
                'ua.prenom as accueil_prenom',
                'ut.nom as agent_nom',
                'ut.prenom as agent_prenom',
                'ud.nom as chef_nom',
                'ud.prenom as chef_prenom'
            )
            ->orderByDesc('d.date_soumission')
            ->limit(500)
            ->get()
            ->map(function ($row) {
                $acteurAffectation = trim(((string) ($row->accueil_prenom ?? '')).' '.((string) ($row->accueil_nom ?? '')));
                $acteurReponse = trim(((string) ($row->chef_prenom ?? '')).' '.((string) ($row->chef_nom ?? '')));
                if ($acteurReponse === '') {
                    $acteurReponse = trim(((string) ($row->agent_prenom ?? '')).' '.((string) ($row->agent_nom ?? '')));
                }
                if ($acteurReponse === '' && $row->date_envoi_usager) {
                    $acteurReponse = $acteurAffectation;
                }

                return [
                    'numero_suivi' => $row->numero_suivi,
                    'objet' => $row->objet,
                    'type_demande' => $row->type_demande,
                    'usager' => trim(((string) ($row->usager_prenom ?? '')).' '.((string) ($row->usager_nom ?? ''))),
                    'date_reception' => $row->date_soumission,
                    'date_affectation_direction' => $row->date_affectation_accueil,
                    'acteur_affectation' => $acteurAffectation !== '' ? $acteurAffectation : '-',
                    'direction_affectee' => $row->direction ?: '-',
                    'service_affecte' => $row->service ?: '-',
                    'date_affectation_agent' => $row->date_affectation_agent,
                    'date_reponse' => $row->date_envoi_usager ?: ($row->date_reponse_direction ?: $row->date_cloture),
                    'acteur_reponse' => $acteurReponse !== '' ? $acteurReponse : '-',
                    'statut_traitement' => $row->statut_traitement,
                    'statut_application' => $row->statut_code === 'cloturee' ? 'Appliquée' : 'Non appliquée',
                    'delai_global' => $this->humanAlertLabel((string) ($row->delai_alerte ?? '')),
                    'delai_moyen_heures' => $row->heures_ouvrees_cloture !== null
                        ? round((float) $row->heures_ouvrees_cloture, 2)
                        : null,
                ];
            });

        $categoryExpression = "COALESCE(NULLIF(TRIM(d.categorie), ''), d.objet)";
        $annexeReclamationsRaw = (clone $baseDemands)
            ->where('td.code', 'reclamation')
            ->select(DB::raw($categoryExpression.' as categorie'), DB::raw('COUNT(*) as total'))
            ->groupBy(DB::raw($categoryExpression))
            ->orderByDesc('total')
            ->orderBy(DB::raw($categoryExpression))
            ->get();

        $totalAnnexeReclamations = (int) $annexeReclamationsRaw->sum('total');
        $annexeReclamations = $annexeReclamationsRaw->map(function ($row) use ($totalAnnexeReclamations) {
            $total = (int) $row->total;
            return [
                'categorie' => (string) ($row->categorie ?? '-'),
                'objet' => (string) ($row->categorie ?? '-'),
                'nombre_mails' => $total,
                'pourcentage' => $totalAnnexeReclamations > 0 ? round(($total / $totalAnnexeReclamations) * 100, 2) : 0.0,
            ];
        })->values();

        $includeEmptyFunctionDefinitions = $serviceScopeIds === null;
        $annexeFonctionsGlobal = $this->buildFunctionPerformanceAnnexe(clone $baseDemands, null, $includeEmptyFunctionDefinitions);
        $annexeFonctionsReclamations = $this->buildFunctionPerformanceAnnexe(clone $baseDemands, 'reclamation', $includeEmptyFunctionDefinitions);
        $annexeServicesReclamations = $this->buildServicePerformanceAnnexe(clone $baseDemands, 'reclamation');

        $openDemands = (clone $baseDemands)
            ->where('st.code', '!=', 'cloturee')
            ->select(
                'd.numero_suivi',
                'd.objet',
                'd.date_soumission',
                'd.id_config_sla',
                'd.delai_alerte',
                'd.alerte_accueil',
                'd.alerte_chef',
                'd.alerte_agent',
                'st.libelle as statut',
                's.libelle as service',
                DB::raw("COALESCE(u.nom, '') as usager_nom"),
                DB::raw("COALESCE(u.prenom, '') as usager_prenom")
            )
            ->orderByDesc('d.date_soumission')
            ->limit(30)
            ->get();

        $alertStats = [
            'vert' => 0,
            'orange' => 0,
            'rouge' => 0,
        ];

        $demandesOverview = $openDemands->map(function ($row) use (&$alertStats) {
            $elapsedHours = $this->slaService->calculateElapsedHours(
                Carbon::parse($row->date_soumission),
                now(),
                (int) $row->id_config_sla
            );
            $globalAlert = (string) ($row->delai_alerte ?? 'dans_les_delais');

            if ($globalAlert === 'dans_les_delais') {
                $alertStats['vert']++;
            } elseif ($globalAlert === 'a_risque') {
                $alertStats['orange']++;
            } elseif ($globalAlert === 'en_retard') {
                $alertStats['rouge']++;
            }

            return [
                'numero_suivi' => $row->numero_suivi,
                'objet' => $row->objet,
                'usager' => trim($row->usager_nom.' '.$row->usager_prenom),
                'service' => $row->service,
                'statut' => $row->statut,
                'heures_ouvrees' => $elapsedHours,
                'alerte' => $this->humanAlertLabel($globalAlert),
                'alerte_accueil' => $row->alerte_accueil,
                'alerte_chef' => $row->alerte_chef,
                'alerte_agent' => $row->alerte_agent,
            ];
        });

        $actionsQuery = DB::table('historique_actions as ha')
            ->join('demandes as d', 'd.id_demande', '=', 'ha.id_demande')
            ->join('parametres as st', 'st.id_parametre', '=', 'd.id_statut')
            ->join('parametres as td', 'td.id_parametre', '=', 'd.id_type_demande')
            ->leftJoin('services as s', 's.id_service', '=', 'd.id_service_courant')
            ->leftJoin('directions as dir', 'dir.id_direction', '=', 's.id_direction')
            ->leftJoin('utilisateurs as u', 'u.id_utilisateur', '=', 'ha.id_utilisateur');

        $this->applyScope($actionsQuery, $serviceScopeIds);
        $this->applyDemandFilters(
            $actionsQuery,
            $startAt,
            $endAt,
            $directionId,
            $serviceId,
            $statusCode,
            $typeCode,
            $applicationState,
            'ha.date_action'
        );

        $actionsRecentes = $actionsQuery
            ->select(
                'ha.date_action',
                'ha.type_action',
                'ha.commentaire',
                'd.numero_suivi',
                'dir.libelle as direction',
                's.libelle as service',
                'u.nom as acteur_nom',
                'u.prenom as acteur_prenom'
            )
            ->orderByDesc('ha.date_action')
            ->limit(100)
            ->get()
            ->map(fn ($row) => [
                'date_action' => $row->date_action,
                'type_action' => $row->type_action,
                'numero_suivi' => $row->numero_suivi,
                'acteur' => trim(((string) ($row->acteur_prenom ?? '')).' '.((string) ($row->acteur_nom ?? ''))),
                'direction' => $row->direction,
                'service' => $row->service,
                'commentaire' => $row->commentaire,
            ]);

        $traceQuery = DB::table('historique_actions as ha')
            ->join('demandes as d', 'd.id_demande', '=', 'ha.id_demande')
            ->join('parametres as st', 'st.id_parametre', '=', 'd.id_statut')
            ->join('parametres as td', 'td.id_parametre', '=', 'd.id_type_demande')
            ->leftJoin('usagers as us', 'us.id_usager', '=', 'd.id_usager')
            ->leftJoin('utilisateurs as ua', 'ua.id_utilisateur', '=', 'ha.id_utilisateur')
            ->leftJoin('parametres as old_st', 'old_st.id_parametre', '=', 'ha.ancien_statut_id')
            ->leftJoin('parametres as new_st', 'new_st.id_parametre', '=', 'ha.nouveau_statut_id')
            ->leftJoin('services as s', 's.id_service', '=', 'd.id_service_courant')
            ->leftJoin('directions as dir', 'dir.id_direction', '=', 's.id_direction');

        $this->applyScope($traceQuery, $serviceScopeIds);
        $this->applyDemandFilters(
            $traceQuery,
            $startAt,
            $endAt,
            $directionId,
            $serviceId,
            $statusCode,
            $typeCode,
            $applicationState,
            'ha.date_action'
        );

        $traceRows = $traceQuery
            ->select(
                'd.id_demande',
                'd.numero_suivi',
                'd.objet',
                'st.libelle as statut_courant',
                'td.libelle as type_demande',
                'us.nom as usager_nom',
                'us.prenom as usager_prenom',
                'ha.date_action',
                'ha.type_action',
                'ha.commentaire',
                'old_st.libelle as ancien_statut',
                'new_st.libelle as nouveau_statut',
                'ua.nom as acteur_nom',
                'ua.prenom as acteur_prenom',
                's.libelle as service',
                'dir.libelle as direction'
            )
            ->orderByDesc('ha.date_action')
            ->limit(3000)
            ->get()
            ->groupBy('id_demande')
            ->map(function ($rows) {
                $sortedRows = $rows->sortBy('date_action')->values();
                $first = $sortedRows->first();

                $actions = $sortedRows->map(function ($row) use ($first) {
                    $acteur = trim(((string) ($row->acteur_prenom ?? '')).' '.((string) ($row->acteur_nom ?? '')));
                    if ($acteur === '' && $row->type_action === 'soumission_usager') {
                        $acteur = trim(((string) ($first->usager_prenom ?? '')).' '.((string) ($first->usager_nom ?? '')));
                        $acteur = $acteur !== '' ? "{$acteur} (usager)" : 'Usager';
                    }

                    return [
                        'date_action' => $row->date_action,
                        'action' => $this->humanActionLabel((string) $row->type_action),
                        'acteur' => $acteur !== '' ? $acteur : 'Systeme',
                        'transition' => $row->ancien_statut || $row->nouveau_statut
                            ? trim(((string) ($row->ancien_statut ?? '-')).' -> '.((string) ($row->nouveau_statut ?? '-')))
                            : null,
                        'commentaire' => $row->commentaire,
                    ];
                })->values();

                return [
                    'numero_suivi' => $first->numero_suivi,
                    'usager' => trim(((string) ($first->usager_prenom ?? '')).' '.((string) ($first->usager_nom ?? ''))),
                    'type_demande' => $first->type_demande,
                    'statut_courant' => $first->statut_courant,
                    'objet' => $first->objet,
                    'direction' => $first->direction,
                    'service' => $first->service,
                    'actions' => $actions,
                    'date_dernier_evenement' => $actions->last()['date_action'] ?? null,
                ];
            })
            ->sortByDesc('date_dernier_evenement')
            ->values();

        $organisation = DB::table('directions')
            ->where('actif', true)
            ->when(
                $serviceScopeIds !== null,
                fn ($q) => $q->whereIn('id_direction', function ($sq) use ($serviceScopeIds) {
                    $sq->from('services')
                        ->select('id_direction')
                        ->whereIn('id_service', !empty($serviceScopeIds) ? $serviceScopeIds : [-1]);
                })
            )
            ->orderBy('code')
            ->get()
            ->map(function ($direction) use ($serviceScopeIds) {
                $services = DB::table('services')
                    ->where('id_direction', $direction->id_direction)
                    ->where('actif', true)
                    ->when(
                        $serviceScopeIds !== null,
                        fn ($q) => $q->whereIn('id_service', !empty($serviceScopeIds) ? $serviceScopeIds : [-1])
                    )
                    ->orderBy('code')
                    ->get(['code', 'libelle']);

                return [
                    'code' => $direction->code,
                    'libelle' => $direction->libelle,
                    'services' => $services,
                ];
            });

        $catalogDirections = DB::table('directions as d')
            ->where('d.actif', true)
            ->when(
                $serviceScopeIds !== null,
                fn ($q) => $q->whereIn('d.id_direction', function ($sq) use ($serviceScopeIds) {
                    $sq->from('services')
                        ->select('id_direction')
                        ->whereIn('id_service', !empty($serviceScopeIds) ? $serviceScopeIds : [-1]);
                })
            )
            ->orderBy('d.code')
            ->get(['d.id_direction', 'd.code', 'd.libelle']);

        $catalogServices = DB::table('services as s')
            ->join('directions as d', 'd.id_direction', '=', 's.id_direction')
            ->where('s.actif', true)
            ->when(
                $serviceScopeIds !== null,
                fn ($q) => $q->whereIn('s.id_service', !empty($serviceScopeIds) ? $serviceScopeIds : [-1])
            )
            ->orderBy('d.code')
            ->orderBy('s.code')
            ->get([
                's.id_service',
                's.id_direction',
                's.code',
                's.libelle',
                'd.code as direction_code',
                'd.libelle as direction_libelle',
            ]);

        $catalogStatus = DB::table('parametres')
            ->where('famille', 'statut_demande')
            ->where('actif', true)
            ->orderBy('ordre_affichage')
            ->get(['code', 'libelle']);
        $catalogTypes = DB::table('parametres')
            ->where('famille', 'type_demande')
            ->where('actif', true)
            ->orderBy('ordre_affichage')
            ->get(['code', 'libelle']);

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'filters_appliques' => [
                'periode' => $resolvedPeriod,
                'date_from' => $startAt,
                'date_to' => $endAt,
                'direction_id' => $directionId,
                'service_id' => $serviceId,
                'statut_code' => $statusCode,
                'application_state' => $applicationState,
            ],
            'kpis' => [
                'total_demandes' => $totalDemandes,
                'total_ouvertes' => $totalOuvertes,
                'total_traitees' => $totalCloturees,
                'total_appliquees' => $totalCloturees,
                'total_non_appliquees' => $totalOuvertes,
                'global_dans_les_delais' => $totalGlobalDansDelais,
                'global_a_risque' => $totalGlobalARisque,
                'global_en_retard' => $totalRetard,
                'total_en_retard' => $totalRetard,
                'taux_traitement_dans_delais' => $tauxTraitementDelai,
                'delai_moyen_traitement_heures' => $delaiMoyenTraitement !== null
                    ? round((float) $delaiMoyenTraitement, 2)
                    : null,
                'accueil_verts' => $accueilAlerts['vert'],
                'accueil_oranges' => $accueilAlerts['orange'],
                'accueil_rouges' => $accueilAlerts['rouge'],
                'chef_rouges' => $chefAlerts['rouge'],
                'agent_rouges' => $agentAlerts['rouge'],
                'global_verts' => $alertStats['vert'],
                'global_oranges' => $alertStats['orange'],
                'global_rouges' => $alertStats['rouge'],
            ],
            'sla_active' => $activeSla,
            'statuts' => $statusRows,
            'par_direction' => $byDirection,
            'performance_directions' => $directionPerformance,
            'kpi_services' => $serviceKpis,
            'kpi_agents' => $agentKpis,
            'par_type' => $byType,
            'demandes_par_pays' => $demandsByCountry,
            'demandes_par_etablissement' => $demandsByEstablishment,
            'performance_types' => $typePerformance,
            'evolution_par_type' => $evolutionByType,
            'evolution_temporelle' => $evolutionTemporelle,
            'usagers_plus_actifs' => $usagersPlusActifs,
            'historique_usagers' => $historiqueUsagers,
            'registre_mails' => $registreMails,
            'registre_controle_interne' => $registreControleInterne,
            'tableau_suivi_annexe' => $annexeRows,
            'tableau_suivi_pagination' => [
                'current_page' => $trackingPage,
                'per_page' => $trackingPerPage,
                'total' => $annexeTotalRows,
                'from' => $annexeFrom,
                'to' => $annexeTo,
                'last_page' => $annexeLastPage,
                'is_all' => $trackingAll,
            ],
            'annexe_repartition' => [
                'reclamations' => $annexeReclamations,
                'total_reclamations' => $totalAnnexeReclamations,
            ],
            'annexes_fonctions' => [
                'global' => $annexeFonctionsGlobal,
                'reclamations' => $annexeFonctionsReclamations,
            ],
            'annexes_services' => [
                'reclamations' => $annexeServicesReclamations,
            ],
            'actions_recentes' => $actionsRecentes,
            'tracabilite_globale' => $traceRows,
            'demandes_en_cours' => $demandesOverview,
            'organisation' => $organisation,
            'catalogues' => [
                'directions' => $catalogDirections,
                'services' => $catalogServices,
                'statuts' => $catalogStatus,
                'types' => $catalogTypes,
                'application_states' => [
                    ['code' => 'appliquee', 'libelle' => 'Appliquée'],
                    ['code' => 'non_appliquee', 'libelle' => 'Non appliquée'],
                ],
            ],

        ]);
    }

    public function trackingDetail(Request $request, AccessControlService $access, int $id): JsonResponse
    {
        try {
            $actor = $access->requireActor($request);
            $userId = (int) $actor->id_utilisateur;

            if (Gate::forUser($actor)->denies('dashboard.view')) {
                throw new AuthorizationException('Accès aux tableaux de bord refusé.');
            }

            $access->assertDemandAccess($userId, $id);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        $request->merge([
            'tracking_demand_id' => $id,
            'tracking_page' => 1,
            'tracking_per_page' => 10,
        ]);

        $overview = $this->index($request, $access)->getData(true);
        $detail = collect($overview['tableau_suivi_annexe'] ?? [])
            ->first(fn (array $row): bool => (int) ($row['id_demande'] ?? 0) === $id);

        if (!$detail) {
            return response()->json(['message' => 'Dossier introuvable dans le filtre courant.'], 404);
        }

        return response()->json(['data' => $detail]);
    }

    private function applyScope(Builder $query, ?array $serviceScopeIds): void
    {
        if ($serviceScopeIds !== null) {
            $query->whereIn('d.id_service_courant', !empty($serviceScopeIds) ? $serviceScopeIds : [-1]);
        }
    }

    private function applyDemandFilters(
        Builder $query,
        ?string $startAt,
        ?string $endAt,
        ?int $directionId,
        ?int $serviceId,
        ?string $statusCode,
        ?string $typeCode,
        ?string $applicationState,
        string $dateField
    ): void {
        if ($startAt) {
            $query->where($dateField, '>=', $startAt);
        }
        if ($endAt) {
            $query->where($dateField, '<=', $endAt);
        }
        if ($directionId) {
            $query->where('s.id_direction', $directionId);
        }
        if ($serviceId) {
            $serviceCode = DB::table('services')
                ->where('id_service', $serviceId)
                ->value('code');

            if ($serviceCode === 'UCAS') {
                $query->where(function (Builder $serviceQuery) use ($serviceId) {
                    $serviceQuery->where('s.id_service', $serviceId);
                    $serviceQuery->orWhere(function (Builder $directAccueilQuery) {
                        $this->applyDirectAccueilFilter($directAccueilQuery);
                    });
                });
            } else {
                $query->where('s.id_service', $serviceId);
            }
        }
        if ($statusCode) {
            $query->where('st.code', $statusCode);
        }
        if ($typeCode) {
            $query->where('td.code', $typeCode);
        }
        if ($applicationState === 'appliquee') {
            $query->where('st.code', 'cloturee');
        }
        if ($applicationState === 'non_appliquee') {
            $query->where('st.code', '!=', 'cloturee');
        }
    }

    private function alertCounts(Builder $baseQuery, string $column): array
    {
        if (!in_array($column, self::ALLOWED_ALERT_COLUMNS, true)) {
            throw new InvalidArgumentException("Colonne d'alerte non autorisée: {$column}");
        }

        $row = (clone $baseQuery)
            ->selectRaw("SUM(CASE WHEN {$column} = 'vert' THEN 1 ELSE 0 END) as vert")
            ->selectRaw("SUM(CASE WHEN {$column} = 'orange' THEN 1 ELSE 0 END) as orange")
            ->selectRaw("SUM(CASE WHEN {$column} = 'rouge' THEN 1 ELSE 0 END) as rouge")
            ->first();

        return [
            'vert' => (int) ($row->vert ?? 0),
            'orange' => (int) ($row->orange ?? 0),
            'rouge' => (int) ($row->rouge ?? 0),
        ];
    }

    private function resolvePeriodBounds(string $period, ?string $dateFrom, ?string $dateTo): array
    {
        $now = now();
        $start = null;
        $end = null;
        $resolved = $period;

        if ($dateFrom || $dateTo) {
            $resolved = 'custom';
            $start = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null;
            $end = $dateTo ? Carbon::parse($dateTo)->endOfDay() : null;

            if ($start && !$end) {
                $end = $now->copy()->endOfDay();
            }

            if (!$start && $end) {
                $start = $end->copy()->startOfDay();
            }

            if ($start && $end && $start->greaterThan($end)) {
                [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            }

            return [
                $resolved,
                $start ? $start->toDateTimeString() : null,
                $end ? $end->toDateTimeString() : null,
            ];
        }

        switch ($period) {
            case 'today':
                $start = $now->copy()->startOfDay();
                $end = $now->copy()->endOfDay();
                break;
            case 'week':
                $start = $now->copy()->startOfWeek();
                $end = $now->copy()->endOfWeek();
                break;
            case 'month':
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                break;
            case 'quarter':
                $start = $now->copy()->startOfQuarter();
                $end = $now->copy()->endOfQuarter();
                break;
            case 'year':
                $start = $now->copy()->startOfYear();
                $end = $now->copy()->endOfYear();
                break;
            case 'custom':
                $resolved = 'all';
                break;
            case 'all':
                break;
            default:
                $resolved = 'month';
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                break;
        }

        return [
            $resolved,
            $start ? $start->toDateTimeString() : null,
            $end ? $end->toDateTimeString() : null,
        ];
    }

    private function resolveEvolutionGranularity(string $resolvedPeriod, ?string $startAt, ?string $endAt): string
    {
        if ($resolvedPeriod === 'today' || $resolvedPeriod === 'week') {
            return 'day';
        }

        if ($resolvedPeriod === 'month') {
            return 'week';
        }

        if ($resolvedPeriod === 'quarter' || $resolvedPeriod === 'year') {
            return 'month';
        }

        if ($startAt && $endAt) {
            $days = Carbon::parse($startAt)->diffInDays(Carbon::parse($endAt)) + 1;

            if ($days <= 14) {
                return 'day';
            }

            if ($days <= 120) {
                return 'week';
            }

            return 'month';
        }

        return 'month';
    }

    private function buildEvolutionBuckets(
        ?string $startAt,
        ?string $endAt,
        string $granularity,
        iterable $receivedRows,
        iterable $closureRows
    ): array {
        $buckets = [];

        if ($startAt && $endAt) {
            $current = $this->normalizeEvolutionBucket(Carbon::parse($startAt), $granularity);
            $end = $this->normalizeEvolutionBucket(Carbon::parse($endAt), $granularity);
            $boundedStart = Carbon::parse($startAt);
            $boundedEnd = Carbon::parse($endAt);

            while ($current->lessThanOrEqualTo($end)) {
                $key = $this->evolutionBucketKey($current, $granularity);
                $buckets[$key] = $this->evolutionBucketLabel($current, $granularity, $boundedStart, $boundedEnd);
                $current = $this->incrementEvolutionBucket($current, $granularity);
            }

            return $buckets;
        }

        foreach ($receivedRows as $row) {
            if (!$row->date_soumission) {
                continue;
            }

            $date = Carbon::parse($row->date_soumission);
            $key = $this->evolutionBucketKey($date, $granularity);
            $buckets[$key] = $this->evolutionBucketLabel($date, $granularity);
        }

        foreach ($closureRows as $row) {
            if (!$row->date_envoi_usager) {
                continue;
            }

            $date = Carbon::parse($row->date_envoi_usager);
            $key = $this->evolutionBucketKey($date, $granularity);
            $buckets[$key] = $this->evolutionBucketLabel($date, $granularity);
        }

        return $buckets;
    }

    private function normalizeEvolutionBucket(Carbon $date, string $granularity): Carbon
    {
        return match ($granularity) {
            'day' => $date->copy()->startOfDay(),
            'week' => $date->copy()->startOfWeek(),
            default => $date->copy()->startOfMonth(),
        };
    }

    private function incrementEvolutionBucket(Carbon $date, string $granularity): Carbon
    {
        return match ($granularity) {
            'day' => $date->copy()->addDay(),
            'week' => $date->copy()->addWeek(),
            default => $date->copy()->addMonth(),
        };
    }

    private function evolutionBucketKey(Carbon $date, string $granularity): string
    {
        $normalized = $this->normalizeEvolutionBucket($date, $granularity);

        return match ($granularity) {
            'day' => $normalized->format('Y-m-d'),
            'week' => $normalized->format('Y-m-d'),
            default => $normalized->format('Y-m'),
        };
    }

    private function evolutionBucketLabel(
        Carbon $date,
        string $granularity,
        ?Carbon $boundedStart = null,
        ?Carbon $boundedEnd = null
    ): string
    {
        $normalized = $this->normalizeEvolutionBucket($date, $granularity);

        return match ($granularity) {
            'day' => $normalized->translatedFormat('d M'),
            'week' => $this->formatWeeklyEvolutionLabel($normalized, $boundedStart, $boundedEnd),
            default => ucfirst($normalized->translatedFormat('M Y')),
        };
    }

    private function formatWeeklyEvolutionLabel(
        Carbon $weekStart,
        ?Carbon $boundedStart = null,
        ?Carbon $boundedEnd = null
    ): string {
        $displayStart = $weekStart->copy()->startOfWeek();
        $displayEnd = $weekStart->copy()->endOfWeek();

        if ($boundedStart && $displayStart->lessThan($boundedStart)) {
            $displayStart = $boundedStart->copy();
        }

        if ($boundedEnd && $displayEnd->greaterThan($boundedEnd)) {
            $displayEnd = $boundedEnd->copy();
        }

        if ($displayStart->isSameDay($displayEnd)) {
            return 'Jour du '.$displayStart->translatedFormat('d M');
        }

        $sameMonth = $displayStart->format('mY') === $displayEnd->format('mY');

        if ($sameMonth) {
            return $displayStart->translatedFormat('d').' - '.$displayEnd->translatedFormat('d M');
        }

        return $displayStart->translatedFormat('d M').' - '.$displayEnd->translatedFormat('d M');
    }

    private function calculateWaitingDays(?string $startDate, ?string $endDate): int
    {
        if (!$startDate) {
            return 0;
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = $endDate ? Carbon::parse($endDate)->startOfDay() : now()->startOfDay();

        if ($end->lessThan($start)) {
            return 0;
        }

        return $start->diffInDays($end);
    }

    private function formatBusinessDuration(?string $startDate, ?string $endDate, int $configId): string
    {
        if (!$startDate || !$endDate || $configId <= 0) {
            return '-';
        }

        $elapsedHours = $this->slaService->calculateElapsedHours(
            Carbon::parse($startDate),
            Carbon::parse($endDate),
            $configId
        );
        $dayHours = max(1.0, $this->slaService->businessDayWorkedHours($configId));
        $days = (int) floor($elapsedHours / $dayHours);
        $remainingHours = round(max(0, $elapsedHours - ($days * $dayHours)), 2);

        if ($days <= 0) {
            return $this->formatWorkedHoursLabel($elapsedHours);
        }

        if ($remainingHours <= 0) {
            return "{$days} j";
        }

        return "{$days} j ".$this->formatWorkedHoursLabel($remainingHours);
    }

    private function humanActionLabel(string $action): string
    {
        return match ($action) {
            'soumission_usager' => "Soumission à l'usager",
            'soumission' => 'Soumission',
            'affectation_service' => 'Affectation au service',
            'affectation_agent' => 'Affectation à un agent',
            'annulation_affectation_agent' => "Annulation d'affectation à l'agent",
            'reponse_redigee' => 'Réponse rédigée',
            'envoi_reponse' => 'Réponse finale envoyée',
            'reponse_directe_accueil' => 'Réponse directe de l\'accueil',
            'reponse_directe_chef' => 'Réponse directe du chef',
            default => ucfirst(str_replace('_', ' ', $action)),
        };
    }

    private function humanActionComment(string $action, ?string $comment): ?string
    {
        $normalized = trim((string) $comment);

        return match ($action) {
            'reponse_redigee' => $normalized === '' || $normalized === 'Réponse unique'
                ? 'Réponse rédigée'
                : $normalized,
            'envoi_reponse' => $normalized === '' || $normalized === 'Envoi final à l\'usager'
                ? "Envoi final à l'usager"
                : $normalized,
            default => $comment,
        };
    }

    private function buildFunctionPerformanceAnnexe(Builder $baseDemands, ?string $typeCode = null, bool $includeEmptyDefinitions = true): array
    {
        $functionDefinitions = [
            'DS' => 'Direction de la Scolarité',
            'DSIC' => 'Direction des Systèmes d\'Informations et de la Communication',
            'DAF' => 'Direction Administrative et Financière',
            'UCAS' => 'Unité Courrier, Accueil et Sécurité',
        ];

        $directAccueilExistsExpression = $this->directAccueilExpression();
        $functionCodeExpression = "CASE WHEN {$directAccueilExistsExpression} THEN 'UCAS' WHEN dir.code IN ('DS', 'DSIC', 'DAF') THEN dir.code ELSE NULL END";
        $functionLabelExpression = "CASE WHEN {$directAccueilExistsExpression} THEN 'Unité Courrier, Accueil et Sécurité' WHEN dir.code IN ('DS', 'DSIC', 'DAF') THEN dir.libelle ELSE NULL END";

        $filteredDemands = (clone $baseDemands)
            ->when($typeCode !== null, fn (Builder $query) => $query->where('td.code', $typeCode));

        $ucasMetrics = $this->buildDirectAccueilMetrics(clone $filteredDemands);

        $metrics = (clone $filteredDemands)
            ->whereRaw($functionCodeExpression.' IS NOT NULL')
            ->select(
                DB::raw($functionCodeExpression.' as fonction_code'),
                DB::raw($functionLabelExpression.' as fonction_label'),
                DB::raw('COUNT(*) as total_demandes'),
                DB::raw("SUM(CASE WHEN st.code = 'cloturee' THEN 1 ELSE 0 END) as total_traitees"),
                DB::raw("SUM(CASE WHEN st.code = 'cloturee' AND d.delai_alerte = 'dans_les_delais' THEN 1 ELSE 0 END) as total_traitees_delai")
            )
            ->groupBy(DB::raw($functionCodeExpression), DB::raw($functionLabelExpression))
            ->get()
            ->keyBy('fonction_code');

        $rows = collect($functionDefinitions)
            ->map(function (string $defaultLabel, string $code) use ($metrics, $ucasMetrics) {
                $metric = $code === 'UCAS'
                    ? $ucasMetrics
                    : $metrics->get($code);
                $totalDemandes = (int) ($metric->total_demandes ?? 0);
                $totalTraitees = (int) ($metric->total_traitees ?? 0);
                $totalTraiteesDelai = (int) ($metric->total_traitees_delai ?? 0);
                $tauxExecution = $totalDemandes > 0 ? round(($totalTraitees / $totalDemandes) * 100, 1) : 0.0;
                $tauxConformite = $totalTraitees > 0 ? round(($totalTraiteesDelai / $totalTraitees) * 100, 1) : 0.0;

                return [
                    'fonction_code' => $code,
                    'fonction_label' => $code === 'UCAS'
                        ? $defaultLabel
                        : (string) ($metric->fonction_label ?? $defaultLabel),
                    'total_demandes' => $totalDemandes,
                    'total_traitees' => $totalTraitees,
                    'taux_execution' => $tauxExecution,
                    'total_traitees_delai' => $totalTraiteesDelai,
                    'taux_conformite' => $tauxConformite,
                    'score_moyen' => round(($tauxExecution + $tauxConformite) / 2, 1),
                ];
            });

        if (!$includeEmptyDefinitions) {
            $rows = $rows->filter(fn (array $row) => (int) ($row['total_demandes'] ?? 0) > 0);
        }

        $rows = $rows->values();

        $sumDemandes = (int) $rows->sum('total_demandes');
        $sumTraitees = (int) $rows->sum('total_traitees');
        $sumTraiteesDelai = (int) $rows->sum('total_traitees_delai');
        $totalTauxExecution = $sumDemandes > 0 ? round(($sumTraitees / $sumDemandes) * 100, 1) : 0.0;
        $totalTauxConformite = $sumTraitees > 0 ? round(($sumTraiteesDelai / $sumTraitees) * 100, 1) : 0.0;

        return [
            'rows' => $rows,
            'totaux' => [
                'total_demandes' => $sumDemandes,
                'total_traitees' => $sumTraitees,
                'taux_execution' => $totalTauxExecution,
                'total_traitees_delai' => $sumTraiteesDelai,
                'taux_conformite' => $totalTauxConformite,
                'score_moyen' => round(($totalTauxExecution + $totalTauxConformite) / 2, 1),
            ],
        ];
    }

    private function buildServicePerformanceAnnexe(Builder $baseDemands, ?string $typeCode = null): array
    {
        $directAccueilExistsExpression = $this->directAccueilExpression();
        $serviceCodeExpression = "CASE WHEN {$directAccueilExistsExpression} THEN 'UCAS' ELSE s.code END";
        $serviceLabelExpression = "CASE WHEN {$directAccueilExistsExpression} THEN 'Unite Courrier, Accueil et Securite' ELSE s.libelle END";

        $filteredDemands = (clone $baseDemands)
            ->when($typeCode !== null, fn (Builder $query) => $query->where('td.code', $typeCode));
        $ucasMetrics = $this->buildDirectAccueilMetrics(clone $filteredDemands);

        $responsableByServiceCode = DB::table('utilisateurs as u')
            ->join('utilisateur_role as ur', 'ur.id_utilisateur', '=', 'u.id_utilisateur')
            ->join('roles as r', 'r.id_role', '=', 'ur.id_role')
            ->join('services as s', 's.id_service', '=', 'u.id_service')
            ->where('r.code', 'chef_service')
            ->whereNotNull('s.code')
            ->orderBy('u.prenom')
            ->orderBy('u.nom')
            ->get([
                's.code as service_code',
                'u.nom',
                'u.prenom',
            ])
            ->groupBy('service_code')
            ->map(function ($rows) {
                $first = $rows->first();

                return trim(((string) ($first->prenom ?? '')).' '.((string) ($first->nom ?? '')));
            });

        $metrics = (clone $filteredDemands)
            ->where(function (Builder $query) use ($directAccueilExistsExpression) {
                $query
                    ->where(function (Builder $directAccueilQuery) {
                        $this->applyDirectAccueilFilter($directAccueilQuery);
                    })
                    ->orWhere(function (Builder $serviceQuery) {
                        $serviceQuery
                            ->whereNotNull('s.id_service')
                            ->where('s.code', '!=', 'UCAS');
                    });
            })
            ->select(
                DB::raw($serviceCodeExpression.' as service_code'),
                DB::raw($serviceLabelExpression.' as service_label'),
                DB::raw('COUNT(*) as total_demandes'),
                DB::raw("SUM(CASE WHEN st.code = 'cloturee' THEN 1 ELSE 0 END) as total_traitees"),
                DB::raw("SUM(CASE WHEN st.code = 'cloturee' AND d.delai_alerte = 'dans_les_delais' THEN 1 ELSE 0 END) as total_traitees_delai")
            )
            ->groupBy(DB::raw($serviceCodeExpression), DB::raw($serviceLabelExpression))
            ->get()
            ->map(function ($row) use ($responsableByServiceCode, $ucasMetrics) {
                $serviceCode = (string) ($row->service_code ?? '');
                $totalDemandes = $serviceCode === 'UCAS'
                    ? (int) ($ucasMetrics->total_demandes ?? 0)
                    : (int) ($row->total_demandes ?? 0);
                $totalTraitees = $serviceCode === 'UCAS'
                    ? (int) ($ucasMetrics->total_traitees ?? 0)
                    : (int) ($row->total_traitees ?? 0);
                $totalTraiteesDelai = $serviceCode === 'UCAS'
                    ? (int) ($ucasMetrics->total_traitees_delai ?? 0)
                    : (int) ($row->total_traitees_delai ?? 0);
                $tauxExecution = $totalDemandes > 0 ? round(($totalTraitees / $totalDemandes) * 100, 1) : 0.0;
                $tauxConformite = $totalTraitees > 0 ? round(($totalTraiteesDelai / $totalTraitees) * 100, 1) : 0.0;

                return [
                    'service_code' => $serviceCode,
                    'service_label' => (string) ($row->service_label ?? $serviceCode ?: '-'),
                    'responsable' => (string) ($responsableByServiceCode->get($serviceCode, '-') ?: '-'),
                    'total_demandes' => $totalDemandes,
                    'total_traitees' => $totalTraitees,
                    'taux_execution' => $tauxExecution,
                    'total_traitees_delai' => $totalTraiteesDelai,
                    'taux_conformite' => $tauxConformite,
                ];
            })
            ->filter(fn (array $row) => $row['service_code'] !== '' && $row['total_demandes'] > 0)
            ->sort(function (array $a, array $b) {
                if ($a['service_code'] === 'UCAS' && $b['service_code'] !== 'UCAS') {
                    return -1;
                }

                if ($b['service_code'] === 'UCAS' && $a['service_code'] !== 'UCAS') {
                    return 1;
                }

                return strcmp($a['service_code'], $b['service_code']);
            })
            ->values();

        $sumDemandes = (int) $metrics->sum('total_demandes');
        $sumTraitees = (int) $metrics->sum('total_traitees');
        $sumTraiteesDelai = (int) $metrics->sum('total_traitees_delai');
        $totalTauxExecution = $sumDemandes > 0 ? round(($sumTraitees / $sumDemandes) * 100, 1) : 0.0;
        $totalTauxConformite = $sumTraitees > 0 ? round(($sumTraiteesDelai / $sumTraitees) * 100, 1) : 0.0;

        return [
            'rows' => $metrics,
            'totaux' => [
                'total_demandes' => $sumDemandes,
                'total_traitees' => $sumTraitees,
                'taux_execution' => $totalTauxExecution,
                'total_traitees_delai' => $sumTraiteesDelai,
                'taux_conformite' => $totalTauxConformite,
            ],
        ];
    }

    private function resolveGlobalDelayOwner(
        object $row,
        ?string $realisationDate,
        float $accueilDeadline,
        float $serviceDeadline,
        ?string $globalAlertCode = null
    ): string
    {
        if (($globalAlertCode ?? $row->delai_alerte ?? null) !== 'en_retard') {
            return '-';
        }

        if (!$row->date_soumission || !$realisationDate || (int) ($row->id_config_sla ?? 0) <= 0) {
            return '-';
        }

        $configId = (int) $row->id_config_sla;
        $reception = Carbon::parse($row->date_soumission);
        $serviceStartAt = $row->date_affectation_accueil ? Carbon::parse($row->date_affectation_accueil) : null;
        $agentAssignedAt = $row->date_affectation_agent ? Carbon::parse($row->date_affectation_agent) : null;
        $end = Carbon::parse($realisationDate);

        if (!$serviceStartAt) {
            return 'Accueil';
        }

        $accueilElapsed = $this->slaService->calculateElapsedHours($reception, $serviceStartAt, $configId);
        if ($accueilElapsed > $accueilDeadline) {
            return 'Accueil';
        }

        if (!$agentAssignedAt) {
            return 'Chef de service';
        }

        $serviceElapsedBeforeAgent = $this->slaService->calculateElapsedHours($serviceStartAt, $agentAssignedAt, $configId);
        if ($serviceElapsedBeforeAgent > $serviceDeadline) {
            return 'Chef de service';
        }

        $serviceElapsedTotal = $this->slaService->calculateElapsedHours($serviceStartAt, $end, $configId);
        if ($serviceElapsedTotal > $serviceDeadline) {
            return 'Agent';
        }

        return 'Chef de service';
    }

    private function resolveGlobalDelayAlert(?string $startAt, ?string $endAt, int $configId): ?string
    {
        if (!$startAt || $configId <= 0) {
            return null;
        }

        $thresholds = $this->stepAlerts->globalThresholds($configId);
        $elapsed = $this->slaService->calculateElapsedHours(
            Carbon::parse($startAt),
            $endAt ? Carbon::parse($endAt) : null,
            $configId
        );

        return $this->slaService->classifyAlert(
            $elapsed,
            (int) $thresholds['deadline'],
            (float) $thresholds['warning']
        );
    }

    private function formatWorkedHoursLabel(float $hours): string
    {
        $formatted = number_format($hours, 1, ',', ' ');
        $formatted = rtrim(rtrim($formatted, '0'), ',');

        return $formatted.' h';
    }

    private function humanDemandStatusLabel(string $statusCode, string $fallback = ''): string
    {
        return match ($statusCode) {
            'nouvelle' => 'Reçu',
            'affectee_service' => 'Affectée au service',
            'affectee_agent' => 'Affectée à un agent',
            'reponse_prete' => 'Réponse rédigée',
            'cloturee' => 'Clôturée',
            default => trim($fallback) !== '' ? trim($fallback) : '-',
        };
    }

    private function humanAlertLabel(string $alertCode): string
    {
        return match ($alertCode) {
            'dans_les_delais' => 'Dans les délais',
            'a_risque' => 'À risque',
            'en_retard' => 'Hors délai',
            default => '-',
        };
    }

    private function directAccueilExpression(): string
    {
        return "EXISTS (
            SELECT 1
            FROM historique_actions ha_direct_accueil
            WHERE ha_direct_accueil.id_demande = d.id_demande
              AND ha_direct_accueil.type_action = 'reponse_directe_accueil'
        )";
    }

    private function applyDirectAccueilFilter(Builder $query): void
    {
        $query->whereExists(function (Builder $subQuery) {
            $subQuery
                ->selectRaw('1')
                ->from('historique_actions as ha_direct_accueil')
                ->whereColumn('ha_direct_accueil.id_demande', 'd.id_demande')
                ->where('ha_direct_accueil.type_action', 'reponse_directe_accueil');
        });
    }

    private function applyNonDirectAccueilFilter(Builder $query): void
    {
        $query->whereNotExists(function (Builder $subQuery) {
            $subQuery
                ->selectRaw('1')
                ->from('historique_actions as ha_direct_accueil')
                ->whereColumn('ha_direct_accueil.id_demande', 'd.id_demande')
                ->where('ha_direct_accueil.type_action', 'reponse_directe_accueil');
        });
    }

    private function isWithinAccueilDirectDeadline(?string $startDate, ?string $endDate, int $configId): bool
    {
        if (!$startDate || !$endDate || $configId <= 0) {
            return false;
        }

        $deadline = (float) ($this->stepAlerts->thresholdsForStep('accueil')['deadline'] ?? 8);

        return $this->slaService->calculateElapsedHours(
            Carbon::parse($startDate),
            Carbon::parse($endDate),
            $configId
        ) <= $deadline;
    }

    private function buildDirectAccueilMetrics(Builder $baseDemands): object
    {
        $rows = (clone $baseDemands)
            ->tap(fn (Builder $query) => $this->applyDirectAccueilFilter($query))
            ->select(
                'd.id_config_sla',
                'd.date_soumission',
                'd.date_envoi_usager',
                'd.date_cloture',
                'st.code as statut_code'
            )
            ->get();

        $totalDemandes = (int) $rows->count();
        $totalTraitees = (int) $rows->filter(fn ($row) => (string) ($row->statut_code ?? '') === 'cloturee')->count();
        $totalTraiteesDelai = (int) $rows
            ->filter(fn ($row) => (string) ($row->statut_code ?? '') === 'cloturee')
            ->filter(fn ($row) => $this->isWithinAccueilDirectDeadline(
                $row->date_soumission ?? null,
                $row->date_envoi_usager ?? $row->date_cloture ?? null,
                (int) ($row->id_config_sla ?? 0)
            ))
            ->count();

        return (object) [
            'total_demandes' => $totalDemandes,
            'total_traitees' => $totalTraitees,
            'total_traitees_delai' => $totalTraiteesDelai,
        ];
    }
}


