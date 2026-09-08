<!doctype html>
<html lang="fr">
@php
    $roleCodes = $roleCodes ?? [];
    $overviewData = $overviewData ?? [];
    $filters = $overviewData['filters_appliques'] ?? [];
    $catalogues = $overviewData['catalogues'] ?? [];
    $kpis = $overviewData['kpis'] ?? [];
    $typeTotals = collect($overviewData['par_type'] ?? [])->keyBy('code');
    $actorName = trim(((string) ($actor->prenom ?? '')).' '.((string) ($actor->nom ?? '')));
    $canExportPilotage = (bool) ($canExportPilotage ?? false);
    $directionOptions = collect($catalogues['directions'] ?? [])->values();
    $serviceOptions = collect($catalogues['services'] ?? [])->values();

    $formatPercent = fn ($value) => number_format((float) ($value ?? 0), 1, ',', ' ').' %';

    $periodLabels = [
        'all' => 'Toute période',
        'today' => "Aujourd'hui",
        'week' => 'Cette semaine',
        'month' => 'Ce mois',
        'quarter' => 'Ce trimestre',
        'year' => 'Cette année',
        'custom' => 'Personnalisée',
    ];

    $selectedPeriod = (string) ($filters['periode'] ?? 'month');
    $selectedDirectionId = (string) ($filters['direction_id'] ?? '');
    $selectedServiceId = (string) ($filters['service_id'] ?? '');
    $dateFromValue = isset($filters['date_from']) && $filters['date_from'] ? substr((string) $filters['date_from'], 0, 10) : '';
    $dateToValue = isset($filters['date_to']) && $filters['date_to'] ? substr((string) $filters['date_to'], 0, 10) : '';
    $currentDirection = null;
    $currentService = null;

    if ($selectedDirectionId !== '') {
        $currentDirection = $directionOptions->first(fn ($direction) => (string) ($direction['id_direction'] ?? '') === $selectedDirectionId);
    }

    if ($selectedServiceId !== '') {
        $currentService = $serviceOptions->first(fn ($service) => (string) ($service['id_service'] ?? '') === $selectedServiceId);
    }

    if (!$currentService && !empty($actor->id_service)) {
        $currentService = $serviceOptions->first(fn ($service) => (int) ($service['id_service'] ?? 0) === (int) $actor->id_service);
    }

    if (!$currentDirection && $currentService) {
        $currentDirection = $directionOptions->first(fn ($direction) => (int) ($direction['id_direction'] ?? 0) === (int) ($currentService['id_direction'] ?? 0));
    }

    if (!$currentDirection && in_array('chef_direction', $roleCodes, true) && $directionOptions->count() === 1) {
        $currentDirection = $directionOptions->first();
    }

    if (!$currentService && in_array('chef_service', $roleCodes, true) && $serviceOptions->count() === 1) {
        $currentService = $serviceOptions->first();
    }

    $currentDirectionLabel = $currentDirection
        ? trim(trim(((string) ($currentDirection['code'] ?? '')).' - '.((string) ($currentDirection['libelle'] ?? ''))), ' -')
        : null;
    $currentServiceLabel = $currentService
        ? trim(trim(((string) ($currentService['code'] ?? '')).' - '.((string) ($currentService['libelle'] ?? ''))), ' -')
        : null;
    $dashboardNavLabel = 'Dashboard CIQ';
    $dashboardPageEyebrow = 'Contrôle interne et qualité';
    $dashboardPageTitle = 'Dashboard graphique des réclamations';
    $dashboardPageSubtitle = 'Vue dédiée aux graphiques décisionnels : volumes reçus, performance des directions, évolution et services.';

    if (in_array('chef_direction', $roleCodes, true)) {
        $dashboardNavLabel = 'Dashboard direction';
        $dashboardPageEyebrow = 'Pilotage de direction';
        $dashboardPageTitle = $currentDirectionLabel
            ? 'Dashboard graphique - '.$currentDirectionLabel
            : 'Dashboard graphique de direction';
        $dashboardPageSubtitle = $currentDirectionLabel
            ? 'Vue graphique dédiée à la '.$currentDirectionLabel.', limitée aux services de votre périmètre.'
            : 'Vue graphique dédiée à votre direction, limitée aux services de votre périmètre.';
    } elseif (in_array('chef_service', $roleCodes, true)) {
        $dashboardNavLabel = 'Dashboard service';
        $dashboardPageEyebrow = 'Pilotage de service';
        $dashboardPageTitle = $currentServiceLabel
            ? 'Dashboard graphique - '.$currentServiceLabel
            : 'Dashboard graphique de service';
        $dashboardPageSubtitle = $currentServiceLabel
            ? 'Vue graphique dédiée au '.$currentServiceLabel.', limitée à votre périmètre.'
            : 'Vue graphique dédiée à votre service, limitée à votre périmètre.';
    } elseif (in_array('dg', $roleCodes, true)) {
        $dashboardNavLabel = 'Dashboard direction générale';
        $dashboardPageEyebrow = 'Direction générale';
        $dashboardPageTitle = 'Dashboard graphique global';
        $dashboardPageSubtitle = 'Vue graphique de synthèse pour la direction générale.';
    } elseif (in_array('lecture_seule', $roleCodes, true)) {
        $dashboardNavLabel = 'Dashboard lecture seule';
        $dashboardPageEyebrow = 'Lecture seule';
        $dashboardPageSubtitle = 'Vue graphique consultative, sans action sur les données.';
    }

    $totalReclamations = (int) ($typeTotals['reclamation']['total'] ?? 0);

    $directionDonutPalette = ['#60a5fa', '#f472b6', '#fbbf24', '#2dd4bf', '#a78bfa', '#fb7185', '#34d399', '#38bdf8', '#f59e0b', '#818cf8'];
    $directionDonutRows = collect($overviewData['par_direction'] ?? [])
        ->map(function ($row) {
            $direction = trim((string) data_get($row, 'direction_code', data_get($row, 'direction', '-')));

            return [
                'direction' => $direction !== '' ? $direction : '-',
                'direction_libelle' => trim((string) data_get($row, 'direction', '-')) ?: '-',
                'total' => (int) data_get($row, 'total', 0),
            ];
        })
        ->filter(function (array $row) {
            $label = mb_strtolower($row['direction']);

            return $row['total'] > 0
                && $row['direction'] !== '-'
                && !str_contains($label, 'non affect');
        })
        ->values();
    $directionDonutTotal = (int) $directionDonutRows->sum('total');
    $directionDonutRows = $directionDonutRows
        ->map(function (array $row, int $index) use ($directionDonutPalette, $directionDonutTotal) {
            $row['color'] = $directionDonutPalette[$index % count($directionDonutPalette)];
            $row['percent'] = $directionDonutTotal > 0 ? round(($row['total'] / $directionDonutTotal) * 100, 1) : 0.0;

            return $row;
        })
        ->values();

    $directionSlaRows = collect($overviewData['performance_directions'] ?? [])
        ->map(function ($row) {
            $taux = (float) data_get($row, 'taux_reponse_dans_delais', 0);

            return [
                'direction' => trim((string) data_get($row, 'direction_code', data_get($row, 'direction', '-'))) ?: '-',
                'direction_libelle' => trim((string) data_get($row, 'direction', '-')) ?: '-',
                'total_cloturees' => (int) data_get($row, 'total_cloturees', 0),
                'total_cloturees_delai' => (int) data_get($row, 'total_cloturees_delai', 0),
                'taux' => round($taux, 1),
                'color' => $taux >= 80 ? '#34d399' : ($taux >= 60 ? '#fbbf24' : '#fb7185'),
            ];
        })
        ->filter(fn (array $row) => $row['direction'] !== '-' && $row['total_cloturees'] > 0)
        ->sortByDesc('taux')
        ->values();
    $directionSlaAverage = $directionSlaRows->isNotEmpty() ? round((float) $directionSlaRows->avg('taux'), 1) : 0.0;

    $serviceSlaRows = collect($overviewData['kpi_services'] ?? [])
        ->map(function ($row) {
            $taux = (float) data_get($row, 'taux_reponse_dans_delais', 0);

            return [
                'service' => trim((string) data_get($row, 'service_code', data_get($row, 'service', '-'))) ?: '-',
                'service_libelle' => trim((string) data_get($row, 'service', '-')) ?: '-',
                'direction' => trim((string) data_get($row, 'direction', '-')) ?: '-',
                'total_cloturees' => (int) data_get($row, 'total_cloturees', 0),
                'total_cloturees_delai' => (int) data_get($row, 'total_cloturees_delai', 0),
                'taux' => round($taux, 1),
                'color' => $taux >= 80 ? '#34d399' : ($taux >= 60 ? '#fbbf24' : '#fb7185'),
            ];
        })
        ->filter(fn (array $row) => $row['service'] !== '-' && $row['total_cloturees'] > 0)
        ->sortByDesc('taux')
        ->values();
    $serviceSlaAverage = $serviceSlaRows->isNotEmpty() ? round((float) $serviceSlaRows->avg('taux'), 1) : 0.0;

    $rankingPalette = ['#3996d3', '#8fc043', '#f59e0b', '#a78bfa', '#fb7185', '#2dd4bf', '#818cf8', '#f472b6', '#34d399', '#38bdf8', '#fbbf24', '#60a5fa'];
    $countryDemandRows = collect($overviewData['demandes_par_pays'] ?? [])
        ->map(function ($row, int $index) use ($rankingPalette) {
            return [
                'label' => trim((string) data_get($row, 'pays', '-')) ?: '-',
                'total_demandes' => (int) data_get($row, 'total_demandes', 0),
                'color' => $rankingPalette[$index % count($rankingPalette)],
            ];
        })
        ->filter(fn (array $row) => $row['label'] !== '-' && $row['total_demandes'] > 0)
        ->values();
    $countryDemandTotal = (int) $countryDemandRows->sum('total_demandes');

    $establishmentDemandRows = collect($overviewData['demandes_par_etablissement'] ?? [])
        ->map(function ($row, int $index) use ($rankingPalette) {
            return [
                'label' => trim((string) data_get($row, 'etablissement', '-')) ?: '-',
                'total_demandes' => (int) data_get($row, 'total_demandes', 0),
                'color' => $rankingPalette[$index % count($rankingPalette)],
            ];
        })
        ->filter(fn (array $row) => $row['label'] !== '-' && $row['total_demandes'] > 0)
        ->values();
    $establishmentDemandTotal = (int) $establishmentDemandRows->sum('total_demandes');

    $evolutionTemporelle = $overviewData['evolution_temporelle'] ?? [];
    $reclamationEvolutionLabels = collect(data_get($evolutionTemporelle, 'labels', []))->values();
    $reclamationEvolutionReceived = collect(data_get($evolutionTemporelle, 'series.reclamations_recues', []))
        ->map(fn ($value) => (int) $value)
        ->values();
    $reclamationEvolutionClosed = collect(data_get($evolutionTemporelle, 'series.demandes_cloturees', []))
        ->map(fn ($value) => (int) $value)
        ->values();
    $reclamationEvolutionOpen = collect(data_get($evolutionTemporelle, 'series.reclamations_non_cloturees', []))
        ->map(fn ($value) => (int) $value)
        ->values();
    if ($reclamationEvolutionOpen->isEmpty() && $reclamationEvolutionReceived->isNotEmpty()) {
        $reclamationEvolutionOpen = $reclamationEvolutionReceived
            ->map(fn (int $value, int $index) => max(0, $value - (int) ($reclamationEvolutionClosed[$index] ?? 0)))
            ->values();
    }
    $reclamationEvolutionTotal = (int) $reclamationEvolutionReceived->sum();
    $reclamationEvolutionClosedTotal = (int) $reclamationEvolutionClosed->sum();
    $reclamationEvolutionOpenTotal = (int) $reclamationEvolutionOpen->sum();
    $reclamationEvolutionData = [
        'labels' => $reclamationEvolutionLabels->all(),
        'recues' => $reclamationEvolutionReceived->all(),
        'cloturees' => $reclamationEvolutionClosed->all(),
        'nonCloturees' => $reclamationEvolutionOpen->all(),
    ];
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>{{ $dashboardNavLabel }} - ANBG</title>
    <script nonce="{{ $cspNonce ?? '' }}" src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background:
                radial-gradient(circle at top left, rgba(57, 150, 211, 0.16), transparent 32%),
                radial-gradient(circle at top right, rgba(143, 192, 67, 0.16), transparent 24%),
                linear-gradient(180deg, #f3f7fb 0%, #eef2f8 48%, #f7f8fb 100%);
        }
        .dashboard-hero {
            background:
                radial-gradient(circle at 12% 10%, rgba(57, 150, 211, 0.28), transparent 30%),
                radial-gradient(circle at 92% 88%, rgba(143, 192, 67, 0.24), transparent 28%),
                #1c203d;
        }
        .chart-card {
            background:
                radial-gradient(circle at 14% 10%, rgba(57, 150, 211, 0.10), transparent 34%),
                radial-gradient(circle at 88% 86%, rgba(143, 192, 67, 0.12), transparent 30%),
                #ffffff;
            border: 1px solid rgba(57, 150, 211, 0.10);
            box-shadow: 0 16px 40px rgba(28, 32, 61, 0.08);
        }
        .donut-card {
            background:
                radial-gradient(circle at 15% 12%, rgba(57, 150, 211, 0.10), transparent 34%),
                radial-gradient(circle at 86% 90%, rgba(45, 212, 191, 0.14), transparent 30%),
                #ffffff;
        }
        .chart-wrap {
            position: relative;
            width: 100%;
            height: 320px;
        }
        .chart-wrap-line {
            height: 300px;
        }
        .donut-wrap {
            width: min(300px, 78vw);
            height: min(300px, 78vw);
            max-width: 300px;
            max-height: 300px;
        }
        .hero-kpi-grid {
            width: 100%;
            max-width: 560px;
        }
        .hero-kpi-card {
            min-width: 0;
            min-height: 106px;
        }
        .hero-kpi-label {
            display: block;
            min-height: 2rem;
            font-size: 0.68rem;
            line-height: 1rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            text-wrap: balance;
        }
        .hero-kpi-value {
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
            line-height: 1;
        }
        .legend-dot {
            width: 0.65rem;
            height: 0.65rem;
            border-radius: 9999px;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.9);
        }
        .export-word {
            background: linear-gradient(180deg, rgba(238, 246, 255, 0.98), rgba(222, 238, 252, 0.98));
            border: 1px solid rgba(24, 90, 189, 0.2);
            color: #185abd;
        }
        .export-word:hover {
            background: linear-gradient(180deg, rgba(218, 235, 252, 1), rgba(205, 227, 248, 1));
            border-color: rgba(24, 90, 189, 0.38);
        }
        .field:focus {
            outline: none;
            border-color: rgba(57, 150, 211, 0.7);
            box-shadow: 0 0 0 4px rgba(57, 150, 211, 0.12);
            background-color: #ffffff;
        }
        .icon-svg {
            display: inline-block;
            width: 1em;
            height: 1em;
            vertical-align: middle;
            flex-shrink: 0;
        }
    </style>
</head>
<body
    class="font-sans text-navy min-h-screen"
    data-dashboard-data-url="{{ url('/pilotage/data') }}"
    data-dashboard-refresh-interval="20000"
>
    <header class="bg-navy sticky top-0 z-50 shadow-md">
        <div class="app-page-frame h-14 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="bg-white rounded-lg px-2.5 py-1.5 flex-shrink-0">
                    <img src="/Logo_anbg.png" alt="ANBG" class="h-9 w-auto object-contain block">
                </div>
                <span class="hidden sm:block text-white font-medium tracking-wider uppercase">{{ $dashboardNavLabel }}</span>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ url('/pilotage') }}{{ request()->getQueryString() ? '?'.request()->getQueryString() : '' }}" class="inline-flex items-center gap-1.5 bg-white/10 hover:bg-white/20 border border-white/15 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition-all duration-150">
                    <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 19h16v2H2V4h2v15Zm2.7-4.3 3.5-3.5 2.6 2.6 4.5-5.3 1.5 1.3-5.9 7-2.7-2.7-2.1 2.1-1.4-1.5Z" fill="currentColor"/>
                    </svg>
                    <span>Pilotage</span>
                </a>
                <a href="/espace" class="hidden sm:inline-flex items-center gap-1.5 bg-white/10 hover:bg-white/20 border border-white/15 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition-all duration-150">
                    <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 4h7v7H4V4Zm9 0h7v7h-7V4ZM4 13h7v7H4v-7Zm9 0h7v7h-7v-7Z" fill="currentColor"/>
                    </svg>
                    <span>Mon espace</span>
                </a>
                <div class="hidden sm:flex items-center gap-2 bg-white/10 border border-white/15 rounded-lg px-3 py-1.5">
                    <div class="w-6 h-6 rounded-full bg-sky/30 flex items-center justify-center flex-shrink-0">
                        <svg class="icon-svg text-sky-100 text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="8" r="3.2" stroke="currentColor" stroke-width="2"/>
                            <path d="M6 18c1.4-2.8 4-4.2 6-4.2s4.6 1.4 6 4.2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <span class="text-white text-xs font-medium">{{ $actorName !== '' ? $actorName : 'Utilisateur ANBG' }}</span>
                </div>
                <form method="post" action="/logout">
                    @csrf
                    <button type="submit" class="flex items-center gap-1.5 bg-white/10 hover:bg-red-500/20 border border-white/15 hover:border-red-400/40 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition-all duration-150">
                        <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h5v-2H5V5h5V3Zm9.7 9-4.2-4.2-1.4 1.4 1.8 1.8H9v2h6.9l-1.8 1.8 1.4 1.4 4.2-4.2Z" fill="currentColor"/>
                        </svg>
                        <span class="hidden sm:inline">Déconnexion</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <section class="dashboard-hero border-b border-white/10 py-8">
        <div class="app-page-frame">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-sky-100">{{ $dashboardPageEyebrow }}</p>
                    <h1 class="mt-3 text-2xl sm:text-3xl font-semibold text-white">{{ $dashboardPageTitle }}</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-sky-100">{{ $dashboardPageSubtitle }}</p>
                </div>
                <div class="hero-kpi-grid grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="hero-kpi-card rounded-2xl border border-white/15 bg-white/10 p-3 text-white backdrop-blur">
                        <p class="hero-kpi-label text-white/65">Réclamations</p>
                        <p class="hero-kpi-value mt-2 text-[1.95rem] font-bold" data-dashboard-value="total-reclamations">{{ number_format($totalReclamations, 0, ',', ' ') }}</p>
                    </div>
                    <div class="hero-kpi-card rounded-2xl border border-white/15 bg-white/10 p-3 text-white backdrop-blur">
                        <p class="hero-kpi-label text-white/65">Clôturées</p>
                        <p class="hero-kpi-value mt-2 text-[1.95rem] font-bold" data-dashboard-value="total-traitees">{{ number_format((int) ($kpis['total_traitees'] ?? 0), 0, ',', ' ') }}</p>
                    </div>
                    <div class="hero-kpi-card rounded-2xl border border-white/15 bg-white/10 p-3 text-white backdrop-blur">
                        <p class="hero-kpi-label text-white/65">En retard</p>
                        <p class="hero-kpi-value mt-2 text-[1.95rem] font-bold" data-dashboard-value="global-en-retard">{{ number_format((int) ($kpis['global_en_retard'] ?? 0), 0, ',', ' ') }}</p>
                    </div>
                    <div class="hero-kpi-card rounded-2xl border border-white/15 bg-white/10 p-3 text-white backdrop-blur">
                        <p class="hero-kpi-label text-white/65">Dans délais</p>
                        <p class="hero-kpi-value mt-2 text-[1.95rem] font-bold" data-dashboard-value="taux-traitement-delai">{{ $formatPercent($kpis['taux_traitement_dans_delais'] ?? 0) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <main class="app-page-frame py-6 space-y-6">
        <section class="chart-card rounded-3xl p-5">
            <form method="get" class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-6 xl:items-end">
                <div>
                    <label for="periode" class="block text-xs text-neutral-500 mb-1">Période</label>
                    <select id="periode" name="periode" class="field w-full px-3 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy">
                        @foreach($periodLabels as $code => $label)
                            <option value="{{ $code }}" @selected($selectedPeriod === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="date_from" class="block text-xs text-neutral-500 mb-1">Date début</label>
                    <input id="date_from" type="date" name="date_from" value="{{ $dateFromValue }}" class="field w-full px-3 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy">
                </div>
                <div>
                    <label for="date_to" class="block text-xs text-neutral-500 mb-1">Date fin</label>
                    <input id="date_to" type="date" name="date_to" value="{{ $dateToValue }}" class="field w-full px-3 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy">
                </div>
                <div>
                    <label for="direction_id" class="block text-xs text-neutral-500 mb-1">Direction</label>
                    <select id="direction_id" name="direction_id" class="field w-full px-3 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy">
                        <option value="">Toutes les directions</option>
                        @foreach(($catalogues['directions'] ?? []) as $direction)
                            <option value="{{ $direction['id_direction'] }}" @selected($selectedDirectionId === (string) $direction['id_direction'])>{{ $direction['code'] }} - {{ $direction['libelle'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="service_id" class="block text-xs text-neutral-500 mb-1">Service</label>
                    <select id="service_id" name="service_id" class="field w-full px-3 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy">
                        <option value="">Tous les services</option>
                        @foreach(($catalogues['services'] ?? []) as $service)
                            <option value="{{ $service['id_service'] }}" @selected($selectedServiceId === (string) $service['id_service'])>{{ $service['direction_code'] }} - {{ $service['code'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-navy px-4 py-2.5 text-sm font-medium text-white hover:bg-navy-600 transition-colors">
                        <svg class="icon-svg text-xs" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 5h18l-7 8v5l-4 2v-7L3 5Z" fill="currentColor"/></svg>
                        <span>Appliquer</span>
                    </button>
                </div>
            </form>
        </section>

        <section class="grid grid-cols-1 gap-5 xl:grid-cols-2">
            <article class="chart-card donut-card rounded-3xl p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-neutral-400">Donut</p>
                        <h2 class="mt-2 text-base font-semibold text-navy">Demandes par Direction</h2>
                    </div>
                    @if ($canExportPilotage)
                        <a href="{{ url('/pilotage/export/direction-demand-donut/docx') }}{{ request()->getQueryString() ? '?'.request()->getQueryString() : '' }}" class="export-word inline-flex w-fit items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-medium transition-colors">
                            <svg class="icon-svg h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2h7l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14 2v6h6" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                            <span>Word</span>
                        </a>
                    @endif
                </div>
                <div id="direction-demand-donut-wrap" class="relative mx-auto mt-5 donut-wrap">
                    @if($directionDonutTotal > 0)
                        <canvas id="direction-demand-donut-chart"></canvas>
                    @else
                        <div class="flex h-full items-center justify-center rounded-full border border-dashed border-neutral-200 bg-neutral-50 text-center text-sm text-neutral-400">Aucune donnée à afficher.</div>
                    @endif
                </div>
                <div id="direction-demand-donut-legend" class="mt-6 flex flex-wrap items-center justify-center gap-x-4 gap-y-2">
                    @forelse($directionDonutRows as $row)
                        <div class="inline-flex max-w-full items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-neutral-600 shadow-soft">
                            <span class="legend-dot flex-shrink-0" style="background-color: {{ $row['color'] }}"></span>
                            <span class="max-w-[10rem] truncate">{{ $row['direction'] }}</span>
                        </div>
                    @empty
                        <span class="text-xs text-neutral-400">Aucune direction disponible</span>
                    @endforelse
                </div>
            </article>

            <article class="chart-card rounded-3xl p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-neutral-400">Performance</p>
                        <h2 class="mt-2 text-base font-semibold text-navy">Taux dans les délais par Direction</h2>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                        @if ($canExportPilotage)
                            <a href="{{ url('/pilotage/export/direction-sla-performance/docx') }}{{ request()->getQueryString() ? '?'.request()->getQueryString() : '' }}" class="export-word inline-flex w-fit items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-medium transition-colors">
                                <svg class="icon-svg h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2h7l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14 2v6h6" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                                <span>Word</span>
                            </a>
                        @endif
                        <div class="w-fit rounded-2xl bg-white/90 px-4 py-2 text-right shadow-soft">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-neutral-400">Moyenne</p>
                            <p class="mt-1 text-xl font-bold text-navy" data-dashboard-value="direction-sla-average">{{ number_format($directionSlaAverage, 1, ',', ' ') }} %</p>
                        </div>
                    </div>
                </div>
                <div id="direction-sla-bar-wrap" class="chart-wrap mt-5">
                    @if($directionSlaRows->isNotEmpty())
                        <canvas id="direction-sla-bar-chart"></canvas>
                    @else
                        <div class="flex h-full items-center justify-center rounded-2xl border border-dashed border-neutral-200 bg-neutral-50 text-center text-sm text-neutral-400">Aucune direction clôturée sur la période.</div>
                    @endif
                </div>
            </article>

            <article class="chart-card rounded-3xl p-5 xl:col-span-2">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-neutral-400">Évolution</p>
                        <h2 class="mt-2 text-base font-semibold text-navy">Réclamations reçues, clôturées et non clôturées</h2>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                        @if ($canExportPilotage)
                            <a href="{{ url('/pilotage/export/reclamation-evolution/docx') }}{{ request()->getQueryString() ? '?'.request()->getQueryString() : '' }}" class="export-word inline-flex w-fit items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-medium transition-colors">
                                <svg class="icon-svg h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2h7l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14 2v6h6" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                                <span>Word</span>
                            </a>
                        @endif
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                            <div class="rounded-2xl bg-white/90 px-4 py-2 text-center shadow-soft">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-neutral-400">Reçues</p>
                                <p class="mt-1 text-xl font-bold text-sky" data-dashboard-value="reclamation-evolution-total">{{ number_format($reclamationEvolutionTotal, 0, ',', ' ') }}</p>
                            </div>
                            <div class="rounded-2xl bg-white/90 px-4 py-2 text-center shadow-soft">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-neutral-400">Clôturées</p>
                                <p class="mt-1 text-xl font-bold text-leaf" data-dashboard-value="reclamation-evolution-closed-total">{{ number_format($reclamationEvolutionClosedTotal, 0, ',', ' ') }}</p>
                            </div>
                            <div class="rounded-2xl bg-white/90 px-4 py-2 text-center shadow-soft">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-neutral-400">Non clôturées</p>
                                <p class="mt-1 text-xl font-bold text-amber-500" data-dashboard-value="reclamation-evolution-open-total">{{ number_format($reclamationEvolutionOpenTotal, 0, ',', ' ') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="reclamation-evolution-line-wrap" class="chart-wrap chart-wrap-line mt-5">
                    @if($reclamationEvolutionLabels->isNotEmpty())
                        <canvas id="reclamation-evolution-line-chart"></canvas>
                    @else
                        <div class="flex h-full items-center justify-center rounded-2xl border border-dashed border-neutral-200 bg-neutral-50 text-center text-sm text-neutral-400">Aucune évolution disponible sur la période.</div>
                    @endif
                </div>
                <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-neutral-600 shadow-soft">
                        <span class="legend-dot" style="background-color: #3996d3"></span>
                        Réclamations reçues
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-neutral-600 shadow-soft">
                        <span class="legend-dot" style="background-color: #8fc043"></span>
                        Réclamations clôturées
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-neutral-600 shadow-soft">
                        <span class="legend-dot" style="background-color: #f59e0b"></span>
                        Réclamations non clôturées
                    </span>
                </div>
            </article>

            <article class="chart-card rounded-3xl p-5 xl:col-span-2">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-neutral-400">Services</p>
                        <h2 class="mt-2 text-base font-semibold text-navy">Taux dans les délais par Service</h2>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                        @if ($canExportPilotage)
                            <a href="{{ url('/pilotage/export/service-sla-performance/docx') }}{{ request()->getQueryString() ? '?'.request()->getQueryString() : '' }}" class="export-word inline-flex w-fit items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-medium transition-colors">
                                <svg class="icon-svg h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2h7l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14 2v6h6" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                                <span>Word</span>
                            </a>
                        @endif
                        <div class="w-fit rounded-2xl bg-white/90 px-4 py-2 text-right shadow-soft">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-neutral-400">Moyenne</p>
                            <p class="mt-1 text-xl font-bold text-navy" data-dashboard-value="service-sla-average">{{ number_format($serviceSlaAverage, 1, ',', ' ') }} %</p>
                        </div>
                    </div>
                </div>
                <div id="service-sla-bar-wrap" class="chart-wrap mt-5">
                    @if($serviceSlaRows->isNotEmpty())
                        <canvas id="service-sla-bar-chart"></canvas>
                    @else
                        <div class="flex h-full items-center justify-center rounded-2xl border border-dashed border-neutral-200 bg-neutral-50 text-center text-sm text-neutral-400">Aucun service clôturé sur la période.</div>
                    @endif
                </div>
            </article>

            <article class="chart-card rounded-3xl p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-neutral-400">Origine usagers</p>
                        <h2 class="mt-2 text-base font-semibold text-navy">Pays les plus demandeurs</h2>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                        @if ($canExportPilotage)
                            <a href="{{ url('/pilotage/export/country-demand-ranking/docx') }}{{ request()->getQueryString() ? '?'.request()->getQueryString() : '' }}" class="export-word inline-flex w-fit items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-medium transition-colors">
                                <svg class="icon-svg h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2h7l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14 2v6h6" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                                <span>Word</span>
                            </a>
                        @endif
                        <div class="w-fit rounded-2xl bg-white/90 px-4 py-2 text-right shadow-soft">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-neutral-400">Demandes</p>
                            <p class="mt-1 text-xl font-bold text-navy" data-dashboard-value="country-demand-total">{{ number_format($countryDemandTotal, 0, ',', ' ') }}</p>
                        </div>
                    </div>
                </div>
                <div id="country-demand-bar-wrap" class="chart-wrap mt-5">
                    @if($countryDemandRows->isNotEmpty())
                        <canvas id="country-demand-bar-chart"></canvas>
                    @else
                        <div class="flex h-full items-center justify-center rounded-2xl border border-dashed border-neutral-200 bg-neutral-50 text-center text-sm text-neutral-400">Aucun pays renseigné sur la période.</div>
                    @endif
                </div>
            </article>

            <article class="chart-card rounded-3xl p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-neutral-400">Origine usagers</p>
                        <h2 class="mt-2 text-base font-semibold text-navy">Établissements les plus demandeurs</h2>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                        @if ($canExportPilotage)
                            <a href="{{ url('/pilotage/export/establishment-demand-ranking/docx') }}{{ request()->getQueryString() ? '?'.request()->getQueryString() : '' }}" class="export-word inline-flex w-fit items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-medium transition-colors">
                                <svg class="icon-svg h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2h7l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14 2v6h6" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                                <span>Word</span>
                            </a>
                        @endif
                        <div class="w-fit rounded-2xl bg-white/90 px-4 py-2 text-right shadow-soft">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-neutral-400">Demandes</p>
                            <p class="mt-1 text-xl font-bold text-navy" data-dashboard-value="establishment-demand-total">{{ number_format($establishmentDemandTotal, 0, ',', ' ') }}</p>
                        </div>
                    </div>
                </div>
                <div id="establishment-demand-bar-wrap" class="chart-wrap mt-5">
                    @if($establishmentDemandRows->isNotEmpty())
                        <canvas id="establishment-demand-bar-chart"></canvas>
                    @else
                        <div class="flex h-full items-center justify-center rounded-2xl border border-dashed border-neutral-200 bg-neutral-50 text-center text-sm text-neutral-400">Aucun établissement renseigné sur la période.</div>
                    @endif
                </div>
            </article>
        </section>
    </main>

    <script nonce="{{ $cspNonce ?? '' }}">
        (function () {
            const directionDemandRows = @json($directionDonutRows->values()->all());
            const directionSlaRows = @json($directionSlaRows->values()->all());
            const serviceSlaRows = @json($serviceSlaRows->values()->all());
            const countryDemandRows = @json($countryDemandRows->values()->all());
            const establishmentDemandRows = @json($establishmentDemandRows->values()->all());
            const reclamationEvolutionData = @json($reclamationEvolutionData);
            const periodSelect = document.getElementById('periode');
            const dateFrom = document.getElementById('date_from');
            const dateTo = document.getElementById('date_to');

            function forceCustomPeriod() {
                if (!periodSelect || (!dateFrom?.value && !dateTo?.value)) {
                    return;
                }

                periodSelect.value = 'custom';
            }

            function handlePeriodChange() {
                if (!periodSelect || !dateFrom || !dateTo) {
                    return;
                }

                if (periodSelect.value !== 'custom') {
                    dateFrom.value = '';
                    dateTo.value = '';
                }
            }

            function truncateChartLabel(label, maxLength = 28) {
                const value = `${label ?? ''}`;
                return value.length > maxLength ? `${value.slice(0, maxLength - 1)}…` : value;
            }

            function initDirectionDemandDonutChart() {
                const canvas = document.getElementById('direction-demand-donut-chart');

                if (!canvas || !directionDemandRows.length || typeof Chart === 'undefined') {
                    return;
                }

                const total = directionDemandRows.reduce((sum, row) => sum + Number(row.total || 0), 0);

                new Chart(canvas, {
                    type: 'doughnut',
                    data: {
                        labels: directionDemandRows.map((row) => row.direction),
                        datasets: [{
                            data: directionDemandRows.map((row) => Number(row.total || 0)),
                            backgroundColor: directionDemandRows.map((row) => row.color),
                            borderColor: '#ffffff',
                            borderWidth: 5,
                            borderRadius: 8,
                            spacing: 4,
                            hoverOffset: 10,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '68%',
                        animation: { animateRotate: true, animateScale: true, duration: 950, easing: 'easeOutQuart' },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                displayColors: true,
                                backgroundColor: '#1c203d',
                                titleColor: '#ffffff',
                                bodyColor: '#eaf4fb',
                                padding: 12,
                                cornerRadius: 12,
                                callbacks: {
                                    label(context) {
                                        const value = Number(context.parsed || 0);
                                        const percent = total > 0 ? ((value / total) * 100).toFixed(1).replace('.', ',') : '0,0';
                                        return ` ${new Intl.NumberFormat('fr-FR').format(value)} demande(s) - ${percent} %`;
                                    },
                                },
                            },
                        },
                    },
                });
            }

            function initHorizontalBarChart(canvasId, rows, labelResolver) {
                const canvas = document.getElementById(canvasId);

                if (!canvas || !rows.length || typeof Chart === 'undefined') {
                    return;
                }

                new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: rows.map(labelResolver),
                        datasets: [{
                            label: 'Taux dans les délais',
                            data: rows.map((row) => Number(row.taux || 0)),
                            backgroundColor: rows.map((row) => row.color),
                            borderColor: rows.map((row) => row.color),
                            borderWidth: 1,
                            borderRadius: 12,
                            borderSkipped: false,
                            barThickness: 22,
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 900, easing: 'easeOutQuart' },
                        scales: {
                            x: {
                                min: 0,
                                max: 100,
                                grid: { color: 'rgba(28, 32, 61, 0.07)' },
                                ticks: {
                                    color: '#667085',
                                    callback(value) { return `${value} %`; },
                                },
                            },
                            y: {
                                grid: { display: false },
                                ticks: {
                                    color: '#1c203d',
                                    font: { weight: 600 },
                                    callback(value) { return truncateChartLabel(this.getLabelForValue(value), 24); },
                                },
                            },
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1c203d',
                                titleColor: '#ffffff',
                                bodyColor: '#eaf4fb',
                                padding: 12,
                                cornerRadius: 12,
                                callbacks: {
                                    title(items) {
                                        const row = rows[items[0]?.dataIndex ?? -1] ?? {};
                                        return row.direction_libelle || row.service_libelle || items[0]?.label || '';
                                    },
                                    label(context) {
                                        const row = rows[context.dataIndex] ?? {};
                                        const taux = Number(row.taux || 0).toFixed(1).replace('.', ',');
                                        const cloturees = new Intl.NumberFormat('fr-FR').format(Number(row.total_cloturees || 0));
                                        const delai = new Intl.NumberFormat('fr-FR').format(Number(row.total_cloturees_delai || 0));

                                        return ` ${taux} % dans les délais (${delai}/${cloturees})`;
                                    },
                                },
                            },
                        },
                    },
                });
            }

            function initDemandRankingBarChart(canvasId, rows) {
                const canvas = document.getElementById(canvasId);

                if (!canvas || !rows.length || typeof Chart === 'undefined') {
                    return;
                }

                new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: rows.map((row) => row.label),
                        datasets: [{
                            label: 'Demandes',
                            data: rows.map((row) => Number(row.total_demandes || 0)),
                            backgroundColor: rows.map((row) => row.color),
                            borderColor: rows.map((row) => row.color),
                            borderWidth: 1,
                            borderRadius: 12,
                            borderSkipped: false,
                            barThickness: 22,
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 900, easing: 'easeOutQuart' },
                        scales: {
                            x: {
                                beginAtZero: true,
                                grid: { color: 'rgba(28, 32, 61, 0.07)' },
                                ticks: {
                                    precision: 0,
                                    color: '#667085',
                                },
                            },
                            y: {
                                grid: { display: false },
                                ticks: {
                                    color: '#1c203d',
                                    font: { weight: 600 },
                                    callback(value) { return truncateChartLabel(this.getLabelForValue(value), 24); },
                                },
                            },
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1c203d',
                                titleColor: '#ffffff',
                                bodyColor: '#eaf4fb',
                                padding: 12,
                                cornerRadius: 12,
                                callbacks: {
                                    title(items) {
                                        return rows[items[0]?.dataIndex ?? -1]?.label || items[0]?.label || '';
                                    },
                                    label(context) {
                                        const value = new Intl.NumberFormat('fr-FR').format(Number(context.parsed.x || 0));

                                        return ` ${value} demande(s)`;
                                    },
                                },
                            },
                        },
                    },
                });
            }

            function initReclamationEvolutionLineChart() {
                const canvas = document.getElementById('reclamation-evolution-line-chart');
                const labels = Array.isArray(reclamationEvolutionData.labels) ? reclamationEvolutionData.labels : [];
                const recues = Array.isArray(reclamationEvolutionData.recues) ? reclamationEvolutionData.recues : [];
                const cloturees = Array.isArray(reclamationEvolutionData.cloturees) ? reclamationEvolutionData.cloturees : [];
                const nonCloturees = Array.isArray(reclamationEvolutionData.nonCloturees)
                    ? reclamationEvolutionData.nonCloturees
                    : recues.map((value, index) => Math.max(0, Number(value || 0) - Number(cloturees[index] || 0)));

                if (!canvas || !labels.length || typeof Chart === 'undefined') {
                    return;
                }

                const context = canvas.getContext('2d');
                const skyGradient = context.createLinearGradient(0, 0, 0, 300);
                skyGradient.addColorStop(0, 'rgba(57, 150, 211, 0.24)');
                skyGradient.addColorStop(1, 'rgba(57, 150, 211, 0.02)');

                new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [
                            {
                                label: 'Réclamations reçues',
                                data: recues.map((value) => Number(value || 0)),
                                borderColor: '#3996d3',
                                backgroundColor: skyGradient,
                                fill: true,
                                tension: 0.42,
                                borderWidth: 3,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                pointBackgroundColor: '#ffffff',
                                pointBorderColor: '#3996d3',
                                pointBorderWidth: 2,
                            },
                            {
                                label: 'Réclamations clôturées',
                                data: cloturees.map((value) => Number(value || 0)),
                                borderColor: '#8fc043',
                                backgroundColor: 'rgba(143, 192, 67, 0.12)',
                                fill: false,
                                tension: 0.42,
                                borderWidth: 3,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                pointBackgroundColor: '#ffffff',
                                pointBorderColor: '#8fc043',
                                pointBorderWidth: 2,
                            },
                            {
                                label: 'Réclamations non clôturées',
                                data: nonCloturees.map((value) => Number(value || 0)),
                                borderColor: '#f59e0b',
                                backgroundColor: 'rgba(245, 158, 11, 0.12)',
                                fill: false,
                                tension: 0.42,
                                borderWidth: 3,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                pointBackgroundColor: '#ffffff',
                                pointBorderColor: '#f59e0b',
                                pointBorderWidth: 2,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 950, easing: 'easeOutQuart' },
                        interaction: { mode: 'index', intersect: false },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: '#667085', maxRotation: 0, autoSkip: true, maxTicksLimit: 8 },
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(28, 32, 61, 0.07)' },
                                ticks: { precision: 0, color: '#667085' },
                            },
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1c203d',
                                titleColor: '#ffffff',
                                bodyColor: '#eaf4fb',
                                padding: 12,
                                cornerRadius: 12,
                                callbacks: {
                                    label(context) {
                                        const value = new Intl.NumberFormat('fr-FR').format(Number(context.parsed.y || 0));
                                        return ` ${context.dataset.label}: ${value}`;
                                    },
                                },
                            },
                        },
                    },
                });
            }

            if (periodSelect) {
                periodSelect.addEventListener('change', handlePeriodChange);
            }

            if (dateFrom) {
                dateFrom.addEventListener('change', forceCustomPeriod);
            }

            if (dateTo) {
                dateTo.addEventListener('change', forceCustomPeriod);
            }

            initDirectionDemandDonutChart();
            initHorizontalBarChart('direction-sla-bar-chart', directionSlaRows, (row) => row.direction);
            initHorizontalBarChart('service-sla-bar-chart', serviceSlaRows, (row) => row.service);
            initDemandRankingBarChart('country-demand-bar-chart', countryDemandRows);
            initDemandRankingBarChart('establishment-demand-bar-chart', establishmentDemandRows);
            initReclamationEvolutionLineChart();

            const dashboardDataUrl = document.body?.dataset.dashboardDataUrl || '/pilotage/data';
            const dashboardRefreshInterval = Math.max(Number(document.body?.dataset.dashboardRefreshInterval || 20000), 10000);
            const dashboardNumberFormatter = new Intl.NumberFormat('fr-FR');
            const dashboardPercentFormatter = new Intl.NumberFormat('fr-FR', {
                minimumFractionDigits: 1,
                maximumFractionDigits: 1,
            });
            const directionDemandPalette = @json($directionDonutPalette);
            const demandRankingPalette = @json($rankingPalette);
            const chartEmptyClasses = 'flex h-full items-center justify-center rounded-2xl border border-dashed border-neutral-200 bg-neutral-50 text-center text-sm text-neutral-400';
            const donutEmptyClasses = 'flex h-full items-center justify-center rounded-full border border-dashed border-neutral-200 bg-neutral-50 text-center text-sm text-neutral-400';
            let dashboardRefreshing = false;

            function dashboardNumber(value) {
                return Number.isFinite(Number(value)) ? Number(value) : 0;
            }

            function dashboardRound(value, decimals = 1) {
                const factor = Math.pow(10, decimals);
                return Math.round(dashboardNumber(value) * factor) / factor;
            }

            function dashboardAverage(rows, key) {
                if (!rows.length) {
                    return 0;
                }

                return rows.reduce((sum, row) => sum + dashboardNumber(row[key]), 0) / rows.length;
            }

            function normalizeDashboardText(value, fallback = '-') {
                const text = `${value ?? ''}`.trim();

                return text !== '' ? text : fallback;
            }

            function escapeDashboardHtml(value) {
                const node = document.createElement('span');
                node.textContent = `${value ?? ''}`;

                return node.innerHTML;
            }

            function setDashboardValue(key, value) {
                document.querySelectorAll(`[data-dashboard-value="${key}"]`).forEach((node) => {
                    node.textContent = value;
                });
            }

            function destroyDashboardChart(canvas) {
                if (!canvas || typeof Chart === 'undefined') {
                    return;
                }

                Chart.getChart(canvas)?.destroy();
            }

            function ensureDashboardCanvas(wrapperId, canvasId, hasData, emptyMessage, emptyClasses) {
                const wrapper = document.getElementById(wrapperId);

                if (!wrapper) {
                    return null;
                }

                const currentCanvas = document.getElementById(canvasId);

                if (!hasData) {
                    destroyDashboardChart(currentCanvas);
                    wrapper.innerHTML = `<div class="${emptyClasses}">${escapeDashboardHtml(emptyMessage)}</div>`;

                    return null;
                }

                if (!currentCanvas) {
                    wrapper.innerHTML = `<canvas id="${canvasId}"></canvas>`;
                }

                return document.getElementById(canvasId);
            }

            function normalizeDirectionDemandRows(data) {
                const rows = Array.isArray(data?.par_direction) ? data.par_direction : [];
                const mappedRows = rows
                    .map((row) => {
                        const direction = normalizeDashboardText(row.direction_code ?? row.direction, '-');

                        return {
                            direction,
                            direction_libelle: normalizeDashboardText(row.direction, '-'),
                            total: Math.max(0, Math.round(dashboardNumber(row.total))),
                        };
                    })
                    .filter((row) => {
                        const label = row.direction.toLocaleLowerCase('fr-FR');

                        return row.total > 0
                            && row.direction !== '-'
                            && !label.includes('non affect');
                    });
                const total = mappedRows.reduce((sum, row) => sum + row.total, 0);

                return mappedRows.map((row, index) => ({
                    ...row,
                    color: directionDemandPalette[index % directionDemandPalette.length],
                    percent: total > 0 ? dashboardRound((row.total / total) * 100, 1) : 0,
                }));
            }

            function normalizeSlaRows(rows, type) {
                return (Array.isArray(rows) ? rows : [])
                    .map((row) => {
                        const taux = dashboardRound(row.taux_reponse_dans_delais, 1);
                        const codeKey = type === 'direction' ? 'direction_code' : 'service_code';
                        const labelKey = type === 'direction' ? 'direction' : 'service';
                        const code = normalizeDashboardText(row[codeKey] ?? row[labelKey], '-');
                        const label = normalizeDashboardText(row[labelKey], '-');
                        const normalizedRow = {
                            total_cloturees: Math.max(0, Math.round(dashboardNumber(row.total_cloturees))),
                            total_cloturees_delai: Math.max(0, Math.round(dashboardNumber(row.total_cloturees_delai))),
                            taux,
                            color: taux >= 80 ? '#34d399' : (taux >= 60 ? '#fbbf24' : '#fb7185'),
                        };

                        if (type === 'direction') {
                            return {
                                ...normalizedRow,
                                direction: code,
                                direction_libelle: label,
                            };
                        }

                        return {
                            ...normalizedRow,
                            service: code,
                            service_libelle: label,
                            direction: normalizeDashboardText(row.direction, '-'),
                        };
                    })
                    .filter((row) => row[type] !== '-' && row.total_cloturees > 0)
                    .sort((a, b) => b.taux - a.taux);
            }

            function normalizeDemandRankingRows(rows, labelKey) {
                return (Array.isArray(rows) ? rows : [])
                    .map((row, index) => ({
                        label: normalizeDashboardText(row[labelKey], '-'),
                        total_demandes: Math.max(0, Math.round(dashboardNumber(row.total_demandes))),
                        color: demandRankingPalette[index % demandRankingPalette.length],
                    }))
                    .filter((row) => row.label !== '-' && row.total_demandes > 0);
            }

            function normalizeReclamationEvolution(data) {
                const evolution = data?.evolution_temporelle || {};
                const series = evolution.series || {};

                return {
                    labels: Array.isArray(evolution.labels) ? evolution.labels : [],
                    recues: Array.isArray(series.reclamations_recues)
                        ? series.reclamations_recues.map((value) => Math.max(0, Math.round(dashboardNumber(value))))
                        : [],
                    cloturees: Array.isArray(series.demandes_cloturees)
                        ? series.demandes_cloturees.map((value) => Math.max(0, Math.round(dashboardNumber(value))))
                        : [],
                    nonCloturees: Array.isArray(series.reclamations_non_cloturees)
                        ? series.reclamations_non_cloturees.map((value) => Math.max(0, Math.round(dashboardNumber(value))))
                        : [],
                };
            }

            function renderDirectionLegend(rows) {
                const legend = document.getElementById('direction-demand-donut-legend');

                if (!legend) {
                    return;
                }

                if (!rows.length) {
                    legend.innerHTML = '<span class="text-xs text-neutral-400">Aucune direction disponible</span>';

                    return;
                }

                legend.innerHTML = rows.map((row) => `
                    <div class="inline-flex max-w-full items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-neutral-600 shadow-soft">
                        <span class="legend-dot flex-shrink-0" style="background-color: ${escapeDashboardHtml(row.color)}"></span>
                        <span class="max-w-[10rem] truncate">${escapeDashboardHtml(row.direction)}</span>
                    </div>
                `).join('');
            }

            function renderDirectionDemandChart(rows) {
                renderDirectionLegend(rows);

                const canvas = ensureDashboardCanvas(
                    'direction-demand-donut-wrap',
                    'direction-demand-donut-chart',
                    rows.length > 0,
                    'Aucune donnée à afficher.',
                    donutEmptyClasses
                );

                if (!canvas || typeof Chart === 'undefined') {
                    return;
                }

                destroyDashboardChart(canvas);

                const total = rows.reduce((sum, row) => sum + dashboardNumber(row.total), 0);

                new Chart(canvas, {
                    type: 'doughnut',
                    data: {
                        labels: rows.map((row) => row.direction),
                        datasets: [{
                            data: rows.map((row) => dashboardNumber(row.total)),
                            backgroundColor: rows.map((row) => row.color),
                            borderColor: '#ffffff',
                            borderWidth: 5,
                            borderRadius: 8,
                            spacing: 4,
                            hoverOffset: 10,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '68%',
                        animation: { animateRotate: true, animateScale: true, duration: 650, easing: 'easeOutQuart' },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                displayColors: true,
                                backgroundColor: '#1c203d',
                                titleColor: '#ffffff',
                                bodyColor: '#eaf4fb',
                                padding: 12,
                                cornerRadius: 12,
                                callbacks: {
                                    label(context) {
                                        const value = dashboardNumber(context.parsed);
                                        const percent = total > 0 ? dashboardPercentFormatter.format((value / total) * 100) : '0,0';

                                        return ` ${dashboardNumberFormatter.format(value)} demande(s) - ${percent} %`;
                                    },
                                },
                            },
                        },
                    },
                });
            }

            function renderHorizontalDashboardChart(canvasId, wrapperId, rows, labelResolver, titleResolver, emptyMessage) {
                const canvas = ensureDashboardCanvas(wrapperId, canvasId, rows.length > 0, emptyMessage, chartEmptyClasses);

                if (!canvas || typeof Chart === 'undefined') {
                    return;
                }

                destroyDashboardChart(canvas);

                new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: rows.map(labelResolver),
                        datasets: [{
                            label: 'Taux dans les délais',
                            data: rows.map((row) => dashboardNumber(row.taux)),
                            backgroundColor: rows.map((row) => row.color),
                            borderColor: rows.map((row) => row.color),
                            borderWidth: 1,
                            borderRadius: 12,
                            borderSkipped: false,
                            barThickness: 22,
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 650, easing: 'easeOutQuart' },
                        scales: {
                            x: {
                                min: 0,
                                max: 100,
                                grid: { color: 'rgba(28, 32, 61, 0.07)' },
                                ticks: {
                                    color: '#667085',
                                    callback(value) { return `${value} %`; },
                                },
                            },
                            y: {
                                grid: { display: false },
                                ticks: {
                                    color: '#1c203d',
                                    font: { weight: 600 },
                                    callback(value) { return truncateChartLabel(this.getLabelForValue(value), 24); },
                                },
                            },
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1c203d',
                                titleColor: '#ffffff',
                                bodyColor: '#eaf4fb',
                                padding: 12,
                                cornerRadius: 12,
                                callbacks: {
                                    title(items) {
                                        const row = rows[items[0]?.dataIndex ?? -1] ?? {};

                                        return titleResolver(row, items[0]?.label || '');
                                    },
                                    label(context) {
                                        const row = rows[context.dataIndex] ?? {};
                                        const taux = dashboardPercentFormatter.format(dashboardNumber(row.taux));
                                        const cloturees = dashboardNumberFormatter.format(dashboardNumber(row.total_cloturees));
                                        const delai = dashboardNumberFormatter.format(dashboardNumber(row.total_cloturees_delai));

                                        return ` ${taux} % dans les délais (${delai}/${cloturees})`;
                                    },
                                },
                            },
                        },
                    },
                });
            }

            function renderDemandRankingChart(canvasId, wrapperId, rows, emptyMessage) {
                const canvas = ensureDashboardCanvas(wrapperId, canvasId, rows.length > 0, emptyMessage, chartEmptyClasses);

                if (!canvas || typeof Chart === 'undefined') {
                    return;
                }

                destroyDashboardChart(canvas);

                new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: rows.map((row) => row.label),
                        datasets: [{
                            label: 'Demandes',
                            data: rows.map((row) => dashboardNumber(row.total_demandes)),
                            backgroundColor: rows.map((row) => row.color),
                            borderColor: rows.map((row) => row.color),
                            borderWidth: 1,
                            borderRadius: 12,
                            borderSkipped: false,
                            barThickness: 22,
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 650, easing: 'easeOutQuart' },
                        scales: {
                            x: {
                                beginAtZero: true,
                                grid: { color: 'rgba(28, 32, 61, 0.07)' },
                                ticks: {
                                    precision: 0,
                                    color: '#667085',
                                },
                            },
                            y: {
                                grid: { display: false },
                                ticks: {
                                    color: '#1c203d',
                                    font: { weight: 600 },
                                    callback(value) { return truncateChartLabel(this.getLabelForValue(value), 24); },
                                },
                            },
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1c203d',
                                titleColor: '#ffffff',
                                bodyColor: '#eaf4fb',
                                padding: 12,
                                cornerRadius: 12,
                                callbacks: {
                                    title(items) {
                                        return rows[items[0]?.dataIndex ?? -1]?.label || items[0]?.label || '';
                                    },
                                    label(context) {
                                        const value = dashboardNumberFormatter.format(dashboardNumber(context.parsed.x));

                                        return ` ${value} demande(s)`;
                                    },
                                },
                            },
                        },
                    },
                });
            }

            function renderReclamationEvolutionChart(data) {
                const canvas = ensureDashboardCanvas(
                    'reclamation-evolution-line-wrap',
                    'reclamation-evolution-line-chart',
                    data.labels.length > 0,
                    'Aucune évolution disponible sur la période.',
                    chartEmptyClasses
                );

                if (!canvas || typeof Chart === 'undefined') {
                    return;
                }

                destroyDashboardChart(canvas);

                const context = canvas.getContext('2d');
                const skyGradient = context.createLinearGradient(0, 0, 0, 300);
                skyGradient.addColorStop(0, 'rgba(57, 150, 211, 0.24)');
                skyGradient.addColorStop(1, 'rgba(57, 150, 211, 0.02)');

                new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: 'Réclamations reçues',
                                data: data.recues.map((value) => dashboardNumber(value)),
                                borderColor: '#3996d3',
                                backgroundColor: skyGradient,
                                fill: true,
                                tension: 0.42,
                                borderWidth: 3,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                pointBackgroundColor: '#ffffff',
                                pointBorderColor: '#3996d3',
                                pointBorderWidth: 2,
                            },
                            {
                                label: 'Réclamations clôturées',
                                data: data.cloturees.map((value) => dashboardNumber(value)),
                                borderColor: '#8fc043',
                                backgroundColor: 'rgba(143, 192, 67, 0.12)',
                                fill: false,
                                tension: 0.42,
                                borderWidth: 3,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                pointBackgroundColor: '#ffffff',
                                pointBorderColor: '#8fc043',
                                pointBorderWidth: 2,
                            },
                            {
                                label: 'Réclamations non clôturées',
                                data: (data.nonCloturees.length
                                    ? data.nonCloturees
                                    : data.recues.map((value, index) => Math.max(0, dashboardNumber(value) - dashboardNumber(data.cloturees[index] || 0)))
                                ).map((value) => dashboardNumber(value)),
                                borderColor: '#f59e0b',
                                backgroundColor: 'rgba(245, 158, 11, 0.12)',
                                fill: false,
                                tension: 0.42,
                                borderWidth: 3,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                pointBackgroundColor: '#ffffff',
                                pointBorderColor: '#f59e0b',
                                pointBorderWidth: 2,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 650, easing: 'easeOutQuart' },
                        interaction: { mode: 'index', intersect: false },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: '#667085', maxRotation: 0, autoSkip: true, maxTicksLimit: 8 },
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(28, 32, 61, 0.07)' },
                                ticks: { precision: 0, color: '#667085' },
                            },
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1c203d',
                                titleColor: '#ffffff',
                                bodyColor: '#eaf4fb',
                                padding: 12,
                                cornerRadius: 12,
                                callbacks: {
                                    label(context) {
                                        const value = dashboardNumberFormatter.format(dashboardNumber(context.parsed.y));

                                        return ` ${context.dataset.label}: ${value}`;
                                    },
                                },
                            },
                        },
                    },
                });
            }

            function updateDashboard(data) {
                const kpis = data?.kpis || {};
                const typeRows = Array.isArray(data?.par_type) ? data.par_type : [];
                const totalReclamations = dashboardNumber(typeRows.find((row) => row.code === 'reclamation')?.total);
                const directionDemand = normalizeDirectionDemandRows(data);
                const directionSla = normalizeSlaRows(data?.performance_directions, 'direction');
                const serviceSla = normalizeSlaRows(data?.kpi_services, 'service');
                const countryDemand = normalizeDemandRankingRows(data?.demandes_par_pays, 'pays');
                const establishmentDemand = normalizeDemandRankingRows(data?.demandes_par_etablissement, 'etablissement');
                const evolution = normalizeReclamationEvolution(data);

                setDashboardValue('total-reclamations', dashboardNumberFormatter.format(totalReclamations));
                setDashboardValue('total-traitees', dashboardNumberFormatter.format(dashboardNumber(kpis.total_traitees)));
                setDashboardValue('global-en-retard', dashboardNumberFormatter.format(dashboardNumber(kpis.global_en_retard)));
                setDashboardValue('taux-traitement-delai', `${dashboardPercentFormatter.format(dashboardNumber(kpis.taux_traitement_dans_delais))} %`);
                setDashboardValue('direction-sla-average', `${dashboardPercentFormatter.format(dashboardAverage(directionSla, 'taux'))} %`);
                setDashboardValue('service-sla-average', `${dashboardPercentFormatter.format(dashboardAverage(serviceSla, 'taux'))} %`);
                setDashboardValue('reclamation-evolution-total', dashboardNumberFormatter.format(evolution.recues.reduce((sum, value) => sum + dashboardNumber(value), 0)));
                setDashboardValue('reclamation-evolution-closed-total', dashboardNumberFormatter.format(evolution.cloturees.reduce((sum, value) => sum + dashboardNumber(value), 0)));
                setDashboardValue('reclamation-evolution-open-total', dashboardNumberFormatter.format((evolution.nonCloturees.length
                    ? evolution.nonCloturees
                    : evolution.recues.map((value, index) => Math.max(0, dashboardNumber(value) - dashboardNumber(evolution.cloturees[index] || 0)))
                ).reduce((sum, value) => sum + dashboardNumber(value), 0)));
                setDashboardValue('country-demand-total', dashboardNumberFormatter.format(countryDemand.reduce((sum, row) => sum + dashboardNumber(row.total_demandes), 0)));
                setDashboardValue('establishment-demand-total', dashboardNumberFormatter.format(establishmentDemand.reduce((sum, row) => sum + dashboardNumber(row.total_demandes), 0)));

                renderDirectionDemandChart(directionDemand);
                renderHorizontalDashboardChart(
                    'direction-sla-bar-chart',
                    'direction-sla-bar-wrap',
                    directionSla,
                    (row) => row.direction,
                    (row, fallback) => row.direction_libelle || fallback,
                    'Aucune direction clôturée sur la période.'
                );
                renderReclamationEvolutionChart(evolution);
                renderHorizontalDashboardChart(
                    'service-sla-bar-chart',
                    'service-sla-bar-wrap',
                    serviceSla,
                    (row) => row.service,
                    (row, fallback) => row.service_libelle || fallback,
                    'Aucun service clôturé sur la période.'
                );
                renderDemandRankingChart(
                    'country-demand-bar-chart',
                    'country-demand-bar-wrap',
                    countryDemand,
                    'Aucun pays renseigné sur la période.'
                );
                renderDemandRankingChart(
                    'establishment-demand-bar-chart',
                    'establishment-demand-bar-wrap',
                    establishmentDemand,
                    'Aucun établissement renseigné sur la période.'
                );
            }

            function dashboardRefreshUrl() {
                const url = new URL(dashboardDataUrl, window.location.origin);
                const currentUrl = new URL(window.location.href);

                currentUrl.searchParams.forEach((value, key) => {
                    if (!key.startsWith('_')) {
                        url.searchParams.set(key, value);
                    }
                });
                url.searchParams.set('_dashboard_refresh', Date.now().toString());

                return url.toString();
            }

            async function refreshDashboard() {
                if (dashboardRefreshing || document.hidden) {
                    return;
                }

                dashboardRefreshing = true;

                try {
                    const response = await fetch(dashboardRefreshUrl(), {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    if (response.status === 401) {
                        window.location.href = '/login';
                        return;
                    }

                    if (!response.ok) {
                        throw new Error(`Refresh dashboard failed: ${response.status}`);
                    }

                    updateDashboard(await response.json());
                } catch (error) {
                    console.warn(error);
                } finally {
                    dashboardRefreshing = false;
                }
            }

            window.setInterval(refreshDashboard, dashboardRefreshInterval);
            window.addEventListener('focus', refreshDashboard);
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    refreshDashboard();
                }
            });
        })();
    </script>
</body>
</html>
