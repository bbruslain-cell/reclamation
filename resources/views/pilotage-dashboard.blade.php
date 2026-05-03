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
    $dashboardPageEyebrow = 'Controle interne et qualite';
    $dashboardPageTitle = 'Dashboard graphique des reclamations';
    $dashboardPageSubtitle = 'Vue dediee aux graphiques decisionnels : volumes recus, performance des directions, evolution et services.';

    if (in_array('chef_direction', $roleCodes, true)) {
        $dashboardNavLabel = 'Dashboard direction';
        $dashboardPageEyebrow = 'Pilotage de direction';
        $dashboardPageTitle = $currentDirectionLabel
            ? 'Dashboard graphique - '.$currentDirectionLabel
            : 'Dashboard graphique de direction';
        $dashboardPageSubtitle = $currentDirectionLabel
            ? 'Vue graphique dediee a la direction '.$currentDirectionLabel.', limitee aux services de votre perimetre.'
            : 'Vue graphique dediee a votre direction, limitee aux services de votre perimetre.';
    } elseif (in_array('chef_service', $roleCodes, true)) {
        $dashboardNavLabel = 'Dashboard service';
        $dashboardPageEyebrow = 'Pilotage de service';
        $dashboardPageTitle = $currentServiceLabel
            ? 'Dashboard graphique - '.$currentServiceLabel
            : 'Dashboard graphique de service';
        $dashboardPageSubtitle = $currentServiceLabel
            ? 'Vue graphique dediee au service '.$currentServiceLabel.', limitee a votre perimetre.'
            : 'Vue graphique dediee a votre service, limitee a votre perimetre.';
    } elseif (in_array('dg', $roleCodes, true)) {
        $dashboardNavLabel = 'Dashboard direction generale';
        $dashboardPageEyebrow = 'Direction generale';
        $dashboardPageTitle = 'Dashboard graphique global';
        $dashboardPageSubtitle = 'Vue graphique de synthese pour la direction generale.';
    } elseif (in_array('lecture_seule', $roleCodes, true)) {
        $dashboardNavLabel = 'Dashboard lecture seule';
        $dashboardPageEyebrow = 'Lecture seule';
        $dashboardPageSubtitle = 'Vue graphique consultative, sans action sur les donnees.';
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

    $evolutionTemporelle = $overviewData['evolution_temporelle'] ?? [];
    $reclamationEvolutionLabels = collect(data_get($evolutionTemporelle, 'labels', []))->values();
    $reclamationEvolutionReceived = collect(data_get($evolutionTemporelle, 'series.reclamations_recues', []))
        ->map(fn ($value) => (int) $value)
        ->values();
    $reclamationEvolutionClosed = collect(data_get($evolutionTemporelle, 'series.demandes_cloturees', []))
        ->map(fn ($value) => (int) $value)
        ->values();
    $reclamationEvolutionTotal = (int) $reclamationEvolutionReceived->sum();
    $reclamationEvolutionClosedTotal = (int) $reclamationEvolutionClosed->sum();
    $reclamationEvolutionData = [
        'labels' => $reclamationEvolutionLabels->all(),
        'recues' => $reclamationEvolutionReceived->all(),
        'cloturees' => $reclamationEvolutionClosed->all(),
    ];
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>{{ $dashboardNavLabel }} - ANBG</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
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
        .export-pdf {
            background: linear-gradient(180deg, rgba(255, 241, 239, 0.96), rgba(255, 232, 228, 0.98));
            border: 1px solid rgba(180, 35, 24, 0.18);
            color: #9f1f16;
        }
        .export-pdf:hover {
            background: linear-gradient(180deg, rgba(254, 220, 215, 0.98), rgba(255, 232, 228, 1));
            border-color: rgba(180, 35, 24, 0.34);
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
<body class="font-sans text-navy min-h-screen">
    <header class="bg-navy sticky top-0 z-50 shadow-md">
        <div class="max-w-screen-xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between gap-4">
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
        <div class="max-w-screen-xl mx-auto px-4 sm:px-6">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-sky-100">{{ $dashboardPageEyebrow }}</p>
                    <h1 class="mt-3 text-2xl sm:text-3xl font-semibold text-white">{{ $dashboardPageTitle }}</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-sky-100">{{ $dashboardPageSubtitle }}</p>
                </div>
                <div class="hero-kpi-grid grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="hero-kpi-card rounded-2xl border border-white/15 bg-white/10 p-3 text-white backdrop-blur">
                        <p class="hero-kpi-label text-white/65">Réclamations</p>
                        <p class="hero-kpi-value mt-2 text-[1.95rem] font-bold">{{ number_format($totalReclamations, 0, ',', ' ') }}</p>
                    </div>
                    <div class="hero-kpi-card rounded-2xl border border-white/15 bg-white/10 p-3 text-white backdrop-blur">
                        <p class="hero-kpi-label text-white/65">Clôturées</p>
                        <p class="hero-kpi-value mt-2 text-[1.95rem] font-bold">{{ number_format((int) ($kpis['total_traitees'] ?? 0), 0, ',', ' ') }}</p>
                    </div>
                    <div class="hero-kpi-card rounded-2xl border border-white/15 bg-white/10 p-3 text-white backdrop-blur">
                        <p class="hero-kpi-label text-white/65">En retard</p>
                        <p class="hero-kpi-value mt-2 text-[1.95rem] font-bold">{{ number_format((int) ($kpis['global_en_retard'] ?? 0), 0, ',', ' ') }}</p>
                    </div>
                    <div class="hero-kpi-card rounded-2xl border border-white/15 bg-white/10 p-3 text-white backdrop-blur">
                        <p class="hero-kpi-label text-white/65">Dans délais</p>
                        <p class="hero-kpi-value mt-2 text-[1.95rem] font-bold">{{ $formatPercent($kpis['taux_traitement_dans_delais'] ?? 0) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <main class="max-w-screen-xl mx-auto px-4 sm:px-6 py-6 space-y-6">
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
                        <a href="{{ url('/pilotage/export/direction-demand-donut/pdf') }}{{ request()->getQueryString() ? '?'.request()->getQueryString() : '' }}" class="export-pdf inline-flex w-fit items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-medium transition-colors">
                            <svg class="icon-svg h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2h7l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14 2v6h6" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                            <span>PDF</span>
                        </a>
                    @endif
                </div>
                <div class="relative mx-auto mt-5 donut-wrap">
                    @if($directionDonutTotal > 0)
                        <canvas id="direction-demand-donut-chart"></canvas>
                    @else
                        <div class="flex h-full items-center justify-center rounded-full border border-dashed border-neutral-200 bg-neutral-50 text-center text-sm text-neutral-400">Aucune donnée à afficher.</div>
                    @endif
                </div>
                <div class="mt-6 flex flex-wrap items-center justify-center gap-x-4 gap-y-2">
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
                            <a href="{{ url('/pilotage/export/direction-sla-performance/pdf') }}{{ request()->getQueryString() ? '?'.request()->getQueryString() : '' }}" class="export-pdf inline-flex w-fit items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-medium transition-colors">
                                <svg class="icon-svg h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2h7l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14 2v6h6" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                                <span>PDF</span>
                            </a>
                        @endif
                        <div class="w-fit rounded-2xl bg-white/90 px-4 py-2 text-right shadow-soft">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-neutral-400">Moyenne</p>
                            <p class="mt-1 text-xl font-bold text-navy">{{ number_format($directionSlaAverage, 1, ',', ' ') }} %</p>
                        </div>
                    </div>
                </div>
                <div class="chart-wrap mt-5">
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
                        <h2 class="mt-2 text-base font-semibold text-navy">Réclamations reçues et clôturées</h2>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                        @if ($canExportPilotage)
                            <a href="{{ url('/pilotage/export/reclamation-evolution/pdf') }}{{ request()->getQueryString() ? '?'.request()->getQueryString() : '' }}" class="export-pdf inline-flex w-fit items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-medium transition-colors">
                                <svg class="icon-svg h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2h7l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14 2v6h6" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                                <span>PDF</span>
                            </a>
                        @endif
                        <div class="grid grid-cols-2 gap-2">
                            <div class="rounded-2xl bg-white/90 px-4 py-2 text-center shadow-soft">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-neutral-400">Reçues</p>
                                <p class="mt-1 text-xl font-bold text-sky">{{ number_format($reclamationEvolutionTotal, 0, ',', ' ') }}</p>
                            </div>
                            <div class="rounded-2xl bg-white/90 px-4 py-2 text-center shadow-soft">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-neutral-400">Clôturées</p>
                                <p class="mt-1 text-xl font-bold text-leaf">{{ number_format($reclamationEvolutionClosedTotal, 0, ',', ' ') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="chart-wrap chart-wrap-line mt-5">
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
                            <a href="{{ url('/pilotage/export/service-sla-performance/pdf') }}{{ request()->getQueryString() ? '?'.request()->getQueryString() : '' }}" class="export-pdf inline-flex w-fit items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-medium transition-colors">
                                <svg class="icon-svg h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2h7l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14 2v6h6" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                                <span>PDF</span>
                            </a>
                        @endif
                        <div class="w-fit rounded-2xl bg-white/90 px-4 py-2 text-right shadow-soft">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-neutral-400">Moyenne</p>
                            <p class="mt-1 text-xl font-bold text-navy">{{ number_format($serviceSlaAverage, 1, ',', ' ') }} %</p>
                        </div>
                    </div>
                </div>
                <div class="chart-wrap mt-5">
                    @if($serviceSlaRows->isNotEmpty())
                        <canvas id="service-sla-bar-chart"></canvas>
                    @else
                        <div class="flex h-full items-center justify-center rounded-2xl border border-dashed border-neutral-200 bg-neutral-50 text-center text-sm text-neutral-400">Aucun service clôturé sur la période.</div>
                    @endif
                </div>
            </article>
        </section>
    </main>

    <script>
        (function () {
            const directionDemandRows = @json($directionDonutRows->values()->all());
            const directionSlaRows = @json($directionSlaRows->values()->all());
            const serviceSlaRows = @json($serviceSlaRows->values()->all());
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

            function initReclamationEvolutionLineChart() {
                const canvas = document.getElementById('reclamation-evolution-line-chart');
                const labels = Array.isArray(reclamationEvolutionData.labels) ? reclamationEvolutionData.labels : [];
                const recues = Array.isArray(reclamationEvolutionData.recues) ? reclamationEvolutionData.recues : [];
                const cloturees = Array.isArray(reclamationEvolutionData.cloturees) ? reclamationEvolutionData.cloturees : [];

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
            initReclamationEvolutionLineChart();
        })();
    </script>
</body>
</html>
