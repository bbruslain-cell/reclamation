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

    $pageTitle = 'Pilotage';
    $pageSubtitle = 'Lecture globale, supervision et reporting.';
    $canViewScopedCiqTables = in_array('ciq', $roleCodes, true)
        || in_array('chef_direction', $roleCodes, true)
        || in_array('chef_service', $roleCodes, true)
        || in_array('lecture_seule', $roleCodes, true);
    $canExportPilotage = isset($actor) && $actor
        ? \Illuminate\Support\Facades\Gate::forUser($actor)->allows('dashboard.export')
        : false;

    if (in_array('ciq', $roleCodes, true)) {
        $pageTitle = 'Contrôle interne et qualité';
        $pageSubtitle = 'Statistiques de volume et d activité sur la période sélectionnée.';
    } elseif (in_array('chef_direction', $roleCodes, true)) {
        $pageTitle = 'Pilotage de direction';
        $pageSubtitle = 'Tableaux de supervision limités aux services de votre direction.';
    } elseif (in_array('chef_service', $roleCodes, true)) {
        $pageTitle = 'Pilotage de service';
        $pageSubtitle = 'Tableaux de supervision limités à votre service.';
    } elseif (in_array('dg', $roleCodes, true)) {
        $pageTitle = 'Direction générale';
        $pageSubtitle = 'Statistiques de volume et d activité sur la période sélectionnée.';
    } elseif (in_array('lecture_seule', $roleCodes, true)) {
        $pageTitle = 'Consultation en lecture seule';
        $pageSubtitle = 'Statistiques de volume et d activité sur la période sélectionnée.';
    }

    $formatPercent = function ($value) {
        return number_format((float) ($value ?? 0), 1, ',', ' ').' %';
    };

    $periodLabels = [
        'all' => 'Toute période',
        'today' => 'Aujourd hui',
        'week' => 'Cette semaine',
        'month' => 'Ce mois',
        'quarter' => 'Ce trimestre',
        'year' => 'Cette annee',
        'custom' => 'Personnalisee',
    ];

    $selectedPeriod = (string) ($filters['periode'] ?? 'month');
    $selectedDirectionId = (string) ($filters['direction_id'] ?? '');
    $selectedServiceId = (string) ($filters['service_id'] ?? '');
    $selectedStatusCode = (string) ($filters['statut_code'] ?? '');
    $selectedApplicationState = (string) ($filters['application_state'] ?? '');
    $dateFromValue = isset($filters['date_from']) && $filters['date_from'] ? substr((string) $filters['date_from'], 0, 10) : '';
    $dateToValue = isset($filters['date_to']) && $filters['date_to'] ? substr((string) $filters['date_to'], 0, 10) : '';

    $totalReclamations = (int) ($typeTotals['reclamation']['total'] ?? 0);
    $typeGrandTotal = max(0, $totalReclamations);

    $annexeRepartition = $overviewData['annexe_repartition'] ?? [];
    $annexeReclamationsRows = collect($annexeRepartition['reclamations'] ?? []);
    $annexeReclamationsTotal = (int) ($annexeRepartition['total_reclamations'] ?? 0);
    $functionDistribution = $overviewData['annexes_fonctions']['reclamations'] ?? [];
    $functionDistributionRows = collect($functionDistribution['rows'] ?? []);
    $functionDistributionTotals = $functionDistribution['totaux'] ?? [];
    $reclamationServiceDistribution = $overviewData['annexes_services']['reclamations'] ?? [];
    $reclamationServiceDistributionRows = collect($reclamationServiceDistribution['rows'] ?? []);
    $reclamationServiceDistributionTotals = $reclamationServiceDistribution['totaux'] ?? [];

    $typeChartEntries = collect([
        [
            'label' => 'Réclamations',
            'total' => $totalReclamations,
            'color' => '#3996d3',
        ],
    ])->filter(fn (array $entry) => $entry['total'] > 0)
      ->values()
      ->all();

    $statusChartEntries = [
        [
            'label' => 'Dans les délais',
            'total' => (int) ($kpis['global_dans_les_delais'] ?? 0),
            'color' => '#8fc043',
        ],
        [
            'label' => 'A risque',
            'total' => (int) ($kpis['global_a_risque'] ?? 0),
            'color' => '#f9b13c',
        ],
        [
            'label' => 'En retard',
            'total' => (int) ($kpis['global_en_retard'] ?? 0),
            'color' => '#d92d20',
        ],
    ];

    $temporalEvolution = $overviewData['evolution_temporelle'] ?? [];
    $temporalLabels = $temporalEvolution['labels'] ?? [];
    $temporalGranularity = (string) ($temporalEvolution['granularite'] ?? 'month');
    $temporalSeries = $temporalEvolution['series'] ?? [];
    $temporalGranularityLabel = [
        'day' => 'jour',
        'week' => 'semaine',
        'month' => 'mois',
    ][$temporalGranularity] ?? 'periode';

    $palette = ['#3996d3', '#8fc043', '#f9b13c', '#d92d20', '#6b5b95', '#18a999', '#ef6f6c', '#4361ee', '#7f5539', '#577590', '#bc6c25', '#118ab2'];
    $withAlpha = function (string $hex, string $alpha) {
        $clean = ltrim($hex, '#');
        if (strlen($clean) !== 6) {
            return $hex;
        }

        return sprintf('rgba(%d, %d, %d, %s)', hexdec(substr($clean, 0, 2)), hexdec(substr($clean, 2, 2)), hexdec(substr($clean, 4, 2)), $alpha);
    };

    $directionProcessingEntries = collect($overviewData['performance_directions'] ?? [])
        ->map(function (array $row) {
            $appliquees = (int) ($row['total_cloturees'] ?? 0);
            $dansDelai = (int) ($row['total_cloturees_delai'] ?? 0);

            return [
                'label' => (string) ($row['direction'] ?? '-'),
                'appliquees' => $appliquees,
                'dans_delais' => $dansDelai,
                'hors_delais' => max(0, (int) ($row['total_cloturees_hors_delai'] ?? ($appliquees - $dansDelai))),
                'taux_dans_delais' => (float) ($row['taux_reponse_dans_delais'] ?? 0),
            ];
        })
        ->filter(fn (array $row) => $row['appliquees'] > 0)
        ->sortByDesc('appliquees')
        ->values();

    $buildDirectionVolumeChart = function ($entries) use ($palette) {
        $entries = collect($entries)->values();

        return [
            'labels' => $entries->pluck('label')->all(),
            'dataset' => [
                'label' => 'Demandes traitees',
                'data' => $entries->pluck('appliquees')->map(fn ($value) => (int) $value)->all(),
                'backgroundColor' => $entries->values()->map(fn ($row, $index) => $palette[$index % count($palette)])->all(),
            ],
        ];
    };

    $directionVolumeChart = $buildDirectionVolumeChart($directionProcessingEntries);
    $ciqTrackingRows = collect($overviewData['tableau_suivi_annexe'] ?? []);
    $ciqTrackingRowsJson = $ciqTrackingRows->values()->all();
    $formatShortDate = function ($value) {
        if (!$value) {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>{{ $pageTitle }} - ANBG</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
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
        .ciq-surface {
            background:
                linear-gradient(145deg, rgba(255,255,255,0.97), rgba(246,249,253,0.94));
            border: 1px solid rgba(57, 150, 211, 0.10);
            box-shadow: 0 16px 40px rgba(28, 32, 61, 0.08);
            backdrop-filter: blur(10px);
        }
        .hero-shell {
            position: relative;
            overflow: hidden;
            background: #1c203d;
        }
        .hero-shell::before {
            content: '';
            position: absolute;
            inset: -20% auto auto -8%;
            width: 280px;
            height: 280px;
            border-radius: 9999px;
            background: radial-gradient(circle, rgba(248, 233, 50, 0.20) 0%, transparent 68%);
            pointer-events: none;
        }
        .hero-shell::after {
            content: '';
            position: absolute;
            right: -70px;
            bottom: -90px;
            width: 260px;
            height: 260px;
            border-radius: 9999px;
            background: radial-gradient(circle, rgba(143, 192, 67, 0.26) 0%, transparent 72%);
            pointer-events: none;
        }
        .hero-chip {
            border: 1px solid rgba(255, 255, 255, 0.16);
            background: rgba(255, 255, 255, 0.10);
            backdrop-filter: blur(8px);
        }
        .ciq-section-title {
            position: relative;
            padding-left: 1rem;
        }
        .ciq-section-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0.2rem;
            bottom: 0.2rem;
            width: 4px;
            border-radius: 999px;
            background: linear-gradient(180deg, #3996d3 0%, #8fc043 100%);
        }
        .ciq-kpi-card {
            position: relative;
            overflow: hidden;
            border-radius: 1rem;
            padding: 0.95rem 0.9rem;
            aspect-ratio: 1 / 1;
            min-height: 0;
            width: 10.75rem;
            min-width: 10.75rem;
            flex: 0 0 auto;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            gap: 0.2rem;
            box-shadow: 0 10px 22px rgba(28, 32, 61, 0.09);
        }
        .ciq-kpi-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at top right, rgba(255,255,255,0.24), transparent 36%),
                linear-gradient(145deg, rgba(255,255,255,0.05), rgba(255,255,255,0));
            pointer-events: none;
        }
        .ciq-kpi-card > * {
            position: relative;
            z-index: 1;
        }
        .ciq-kpi-card--navy {
            background: linear-gradient(145deg, #1c203d 0%, #25335c 100%);
            color: #ffffff;
        }
        .ciq-kpi-card--sky {
            background: linear-gradient(145deg, #3996d3 0%, #66b8ee 100%);
            color: #ffffff;
        }
        .ciq-kpi-card--gradient {
            background: linear-gradient(135deg, #8fc043 0%, #f8e932 100%);
            color: #1c203d;
        }
        .ciq-kpi-card--mist {
            background: linear-gradient(145deg, #ffffff 0%, #eef6ff 100%);
            border: 1px solid rgba(57, 150, 211, 0.18);
            color: #1c203d;
        }
        .ciq-kpi-card--warm {
            background: linear-gradient(145deg, #fff8d7 0%, #fff0a1 100%);
            border: 1px solid rgba(248, 233, 50, 0.35);
            color: #1c203d;
        }
        .ciq-kpi-card--alert {
            background: linear-gradient(145deg, #fff1ef 0%, #ffd8d2 100%);
            border: 1px solid rgba(217, 45, 32, 0.18);
            color: #6e1b14;
        }
        .ciq-kpi-label {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            opacity: 0.78;
            max-width: 9rem;
        }
        .ciq-kpi-value {
            margin-top: 0.15rem;
            font-size: clamp(2rem, 2vw, 2.5rem);
            font-weight: 700;
            line-height: 1;
        }
        .ciq-kpi-meta {
            margin-top: 0.15rem;
            font-size: 0.75rem;
            line-height: 1.35;
            opacity: 0.76;
            max-width: 9.25rem;
        }
        .ciq-inner-card {
            background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(245,248,252,0.92));
            border: 1px solid rgba(57, 150, 211, 0.12);
            box-shadow: 0 8px 20px rgba(28, 32, 61, 0.06);
        }
        .ciq-export-button {
            background: linear-gradient(180deg, rgba(255,255,255,0.94), rgba(241,246,251,0.96));
            border: 1px solid rgba(57, 150, 211, 0.16);
        }
        .ciq-export-button:hover {
            background: linear-gradient(180deg, rgba(57,150,211,0.10), rgba(143,192,67,0.12));
            border-color: rgba(57, 150, 211, 0.36);
        }
        .ciq-export-button--pdf {
            background: linear-gradient(180deg, rgba(255, 241, 239, 0.96), rgba(255, 232, 228, 0.98));
            border-color: rgba(180, 35, 24, 0.18);
            color: #9f1f16 !important;
        }
        .ciq-export-button--pdf:hover {
            background: linear-gradient(180deg, rgba(254, 220, 215, 0.98), rgba(255, 232, 228, 1));
            border-color: rgba(180, 35, 24, 0.34);
        }
        .ciq-export-button--excel {
            background: linear-gradient(180deg, rgba(243, 249, 234, 0.98), rgba(232, 245, 214, 0.98));
            border-color: rgba(79, 121, 28, 0.2);
            color: #3f6f16 !important;
        }
        .ciq-export-button--excel:hover {
            background: linear-gradient(180deg, rgba(227, 241, 200, 0.98), rgba(243, 249, 234, 1));
            border-color: rgba(79, 121, 28, 0.34);
        }
        .ciq-export-button--png {
            background: linear-gradient(180deg, rgba(234, 244, 251, 0.98), rgba(214, 235, 248, 0.98));
            border-color: rgba(57, 150, 211, 0.22);
            color: #1f78b4 !important;
        }
        .ciq-export-button--png:hover {
            background: linear-gradient(180deg, rgba(213, 233, 248, 1), rgba(234, 244, 251, 1));
            border-color: rgba(57, 150, 211, 0.36);
        }
        .field {
            transition: border-color 160ms ease, box-shadow 160ms ease, background-color 160ms ease;
        }
        .field:focus {
            outline: none;
            border-color: rgba(57, 150, 211, 0.7);
            box-shadow: 0 0 0 4px rgba(57, 150, 211, 0.12);
            background-color: #ffffff;
        }
        .trow:hover td { background: #f8fafd; }
        .icon-svg {
            display: inline-block;
            width: 1em;
            height: 1em;
            vertical-align: middle;
            flex-shrink: 0;
        }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #f1f3f5; }
        ::-webkit-scrollbar-thumb { background: #c5c7d9; border-radius: 99px; }
        input[type="date"]::-webkit-calendar-picker-indicator {
            opacity: 0;
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        input[type="date"]::-webkit-inner-spin-button,
        input[type="date"]::-webkit-clear-button {
            display: none;
        }
        #ciq-tracking-table thead tr {
            background: #1f4e79;
            border-bottom: 1px solid #173b5b;
        }
        #ciq-tracking-table th {
            padding: 12px 10px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #ffffff;
        }
        #ciq-tracking-table td {
            padding: 10px;
            font-size: 13px;
            vertical-align: top;
        }
        #ciq-tracking-table td:nth-child(4) {
            line-height: 1.35rem;
            word-break: break-word;
        }
        .ciq-surface .text-neutral-400 {
            color: #667085 !important;
        }
        .ciq-surface .text-neutral-500 {
            color: #475467 !important;
        }
        .ciq-surface .text-neutral-600 {
            color: #344054 !important;
        }
        .ciq-surface .text-neutral-700 {
            color: #1f2937 !important;
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
                <span class="hidden sm:block text-white font-medium tracking-wider uppercase">Agence Nationale des Bourses du Gabon</span>
            </div>
            <div class="flex items-center gap-3">
                <a href="/espace" class="hidden sm:inline-flex items-center gap-1.5 bg-white/10 hover:bg-white/20 border border-white/15 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition-all duration-150">
                    <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 4h7v7H4V4Zm9 0h7v7h-7V4ZM4 13h7v7H4v-7Zm9 0h7v7h-7v-7Z" fill="currentColor"/>
                    </svg>
                    <span>Mon espace</span>
                </a>
                @if (in_array('admin', $roleCodes, true))
                <a href="/admin" class="hidden sm:inline-flex items-center gap-1.5 bg-white/10 hover:bg-white/20 border border-white/15 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition-all duration-150">
                    <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="m10.6 2 2.8 0 .5 2.1a7.9 7.9 0 0 1 1.8.8l1.9-1 2 2-1 1.9c.3.6.6 1.2.8 1.8L22 10.6v2.8l-2.1.5a7.9 7.9 0 0 1-.8 1.8l1 1.9-2 2-1.9-1a7.9 7.9 0 0 1-1.8.8l-.5 2.1h-2.8l-.5-2.1a7.9 7.9 0 0 1-1.8-.8l-1.9 1-2-2 1-1.9a7.9 7.9 0 0 1-.8-1.8L2 13.4v-2.8l2.1-.5c.2-.6.5-1.2.8-1.8l-1-1.9 2-2 1.9 1c.6-.3 1.2-.6 1.8-.8L10.6 2Zm1.4 6a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z" fill="currentColor"/>
                    </svg>
                    <span>Administration</span>
                </a>
                @endif
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
                        <span class="hidden sm:inline">Deconnexion</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <section class="hero-shell border-b border-white/10 pb-8 pt-6">
        <div class="max-w-screen-xl mx-auto px-4 sm:px-6">
            <div class="relative z-10 flex items-start gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0 mt-1 shadow-lg" style="background: linear-gradient(135deg, rgba(143,192,67,0.9) 0%, rgba(248,233,50,0.95) 100%);">
                    <svg class="icon-svg text-navy text-sm" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 19h16v2H2V4h2v15Zm2.7-4.3 3.5-3.5 2.6 2.6 4.5-5.3 1.5 1.3-5.9 7-2.7-2.7-2.1 2.1-1.4-1.5Z" fill="currentColor"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-white text-xl font-medium leading-snug mb-1">{{ $pageTitle }}</h1>
                    <p class="text-sky-100 text-sm font-light leading-relaxed max-w-3xl">{{ $pageSubtitle }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="hero-chip inline-flex items-center gap-2 rounded-full px-3 py-2 text-xs text-white/90">
                            <svg class="icon-svg text-[#f8e932]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M11 2v10h10A10 10 0 0 0 11 2Zm-1 1.1A10 10 0 1 0 20.9 14H10V3.1Z" fill="currentColor"/>
                            </svg>
                            Lecture de supervision
                        </span>
                        <span class="hero-chip inline-flex items-center gap-2 rounded-full px-3 py-2 text-xs text-white/90">
                            <svg class="icon-svg text-[#8fc043]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M3 4h18v16H3V4Zm2 2v3h14V6H5Zm0 5v3h4v-3H5Zm6 0v3h8v-3h-8Zm-6 5v2h4v-2H5Zm6 0v2h8v-2h-8Z" fill="currentColor"/>
                            </svg>
                            Reporting de direction
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <main class="max-w-screen-xl mx-auto px-4 sm:px-6 py-6 space-y-6">
        <section class="ciq-surface rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-sky/10">
                <h2 class="ciq-section-title text-sm font-medium text-navy">Filtres de supervision</h2>
                <p class="text-xs text-neutral-400 mt-1">Choisissez une période prédéfinie ou définissez une plage via le calendrier.</p>
            </div>
            <form method="get" class="p-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 items-end">
                <div>
                    <label for="periode" class="block text-xs text-neutral-500 mb-1">Période</label>
                    <select id="periode" name="periode" class="field w-full px-3 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy">
                        @foreach($periodLabels as $code => $label)
                        <option value="{{ $code }}" @selected($selectedPeriod === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="date_from" class="block text-xs text-neutral-500 mb-1">Date debut</label>
                    <div class="relative">
                        <input id="date_from" type="date" name="date_from" value="{{ $dateFromValue }}" class="field w-full px-3 py-2.5 pr-11 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy">
                        <button type="button" class="date-picker-trigger absolute inset-y-0 right-0 px-3 text-neutral-400 hover:text-navy transition-colors" data-target="date_from" aria-label="Choisir la date de debut">
                            <svg class="icon-svg text-sm" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M7 2h2v2h6V2h2v2h3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h3V2Zm13 8H4v10h16V10ZM4 8h16V6H4v2Zm2 4h4v4H6v-4Z" fill="currentColor"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div>
                    <label for="date_to" class="block text-xs text-neutral-500 mb-1">Date fin</label>
                    <div class="relative">
                        <input id="date_to" type="date" name="date_to" value="{{ $dateToValue }}" class="field w-full px-3 py-2.5 pr-11 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy">
                        <button type="button" class="date-picker-trigger absolute inset-y-0 right-0 px-3 text-neutral-400 hover:text-navy transition-colors" data-target="date_to" aria-label="Choisir la date de fin">
                            <svg class="icon-svg text-sm" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M7 2h2v2h6V2h2v2h3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h3V2Zm13 8H4v10h16V10ZM4 8h16V6H4v2Zm2 4h4v4H6v-4Z" fill="currentColor"/>
                            </svg>
                        </button>
                    </div>
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
                        <option value="{{ $service['id_service'] }}" @selected($selectedServiceId === (string) $service['id_service'])>{{ $service['direction_code'] }} - {{ $service['code'] }} - {{ $service['libelle'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="statut_code" class="block text-xs text-neutral-500 mb-1">Statut</label>
                    <select id="statut_code" name="statut_code" class="field w-full px-3 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy">
                        <option value="">Tous les statuts</option>
                        @foreach(($catalogues['statuts'] ?? []) as $status)
                        <option value="{{ $status['code'] }}" @selected($selectedStatusCode === (string) $status['code'])>{{ $status['libelle'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="application_state" class="block text-xs text-neutral-500 mb-1">Application</label>
                    <select id="application_state" name="application_state" class="field w-full px-3 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy">
                        <option value="">Tous</option>
                        @foreach(($catalogues['application_states'] ?? []) as $state)
                        <option value="{{ $state['code'] }}" @selected($selectedApplicationState === (string) $state['code'])>{{ $state['libelle'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2 xl:col-span-4 flex flex-wrap gap-3">
                    <button type="submit" class="inline-flex items-center gap-2 bg-navy hover:bg-navy-600 text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-colors duration-150">
                        <svg class="icon-svg text-xs" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M3 5h18l-7 8v5l-4 2v-7L3 5Z" fill="currentColor"/>
                        </svg>
                        <span>Appliquer</span>
                    </button>
                    <a href="/pilotage" class="inline-flex items-center gap-2 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 text-sm font-medium px-5 py-2.5 rounded-xl transition-colors duration-150">
                        <svg class="icon-svg text-xs" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 5a7 7 0 1 1-6.6 9.3l1.9-.6A5 5 0 1 0 12 7h-1.6l2.3 2.3-1.4 1.4L6.6 6l4.7-4.7 1.4 1.4L10.4 5H12Z" fill="currentColor"/>
                        </svg>
                        <span>Reinitialiser</span>
                    </a>
                </div>
                <div class="md:col-span-2 xl:col-span-4">
                    <p class="text-xs text-neutral-400">Si vous choisissez une date de début ou de fin, la période passe automatiquement en mode personnalisée. Si vous revenez sur `Aujourd hui`, `Semaine`, `Mois` ou `Annee`, la plage manuelle est effacée.</p>
                </div>
            </form>
        </section>

        @if ($canViewScopedCiqTables)
        <section class="space-y-5">
            <div>
                <h2 class="ciq-section-title text-sm font-medium text-navy">Volume & activite</h2>
                <p class="text-xs text-neutral-400 mt-1">Vue d ensemble du volume reçu, du traitement et de l état global sur la période sélectionnée.</p>
            </div>

            <div class="flex gap-4 overflow-x-auto pb-2">
                <div class="ciq-kpi-card ciq-kpi-card--navy">
                    <p class="ciq-kpi-label">Total mails reçus</p>
                    <p class="ciq-kpi-value" data-countup="{{ (int) ($kpis['total_demandes'] ?? 0) }}">{{ number_format((int) ($kpis['total_demandes'] ?? 0), 0, ',', ' ') }}</p>
                    <p class="ciq-kpi-meta">Toutes demandes confondues</p>
                </div>
                <div class="ciq-kpi-card ciq-kpi-card--sky">
                    <p class="ciq-kpi-label">Réclamations reçues</p>
                    <p class="ciq-kpi-value" data-countup="{{ $totalReclamations }}">{{ number_format($totalReclamations, 0, ',', ' ') }}</p>
                    <p class="ciq-kpi-meta">Volume filtre courant</p>
                </div>
                <div class="ciq-kpi-card ciq-kpi-card--mist">
                    <p class="ciq-kpi-label">Clôturées</p>
                    <p class="ciq-kpi-value" data-countup="{{ (int) ($kpis['total_traitees'] ?? 0) }}">{{ number_format((int) ($kpis['total_traitees'] ?? 0), 0, ',', ' ') }}</p>
                    <p class="ciq-kpi-meta">Demandes clôturées</p>
                </div>
                <div class="ciq-kpi-card ciq-kpi-card--mist">
                    <p class="ciq-kpi-label">En attente</p>
                    <p class="ciq-kpi-value" data-countup="{{ (int) ($kpis['total_ouvertes'] ?? 0) }}">{{ number_format((int) ($kpis['total_ouvertes'] ?? 0), 0, ',', ' ') }}</p>
                    <p class="ciq-kpi-meta">Demandes non clôturées</p>
                </div>
                <div class="ciq-kpi-card ciq-kpi-card--alert">
                    <p class="ciq-kpi-label">En retard</p>
                    <p class="ciq-kpi-value" data-countup="{{ (int) ($kpis['global_en_retard'] ?? 0) }}">{{ number_format((int) ($kpis['global_en_retard'] ?? 0), 0, ',', ' ') }}</p>
                    <p class="ciq-kpi-meta">Dépassement du délai global</p>
                </div>
                <div class="ciq-kpi-card ciq-kpi-card--gradient">
                    <p class="ciq-kpi-label">Taux dans les délais</p>
                    <p class="ciq-kpi-value" data-countup="{{ (float) ($kpis['taux_traitement_dans_delais'] ?? 0) }}" data-decimals="1" data-suffix="%">{{ $formatPercent($kpis['taux_traitement_dans_delais'] ?? 0) }}</p>
                    <p class="ciq-kpi-meta">Sur les mails traités</p>
                </div>
                <div class="ciq-kpi-card ciq-kpi-card--warm">
                    <p class="ciq-kpi-label">Délai moyen traitement</p>
                    <p class="ciq-kpi-value">
                        @if(($kpis['delai_moyen_traitement_heures'] ?? null) !== null)
                            <span data-countup="{{ (float) $kpis['delai_moyen_traitement_heures'] }}" data-decimals="2" data-suffix=" h">{{ number_format((float) $kpis['delai_moyen_traitement_heures'], 2, ',', ' ') }} h</span>
                        @else
                            -
                        @endif
                    </p>
                    <p class="ciq-kpi-meta">Moyenne en heures ouvrées sur les mails clôturés</p>
                </div>
            </div>
        </section>

        <section class="space-y-5">
            <div>
                <h2 class="ciq-section-title text-sm font-medium text-navy">Graphiques de performance</h2>
                <p class="text-xs text-neutral-400 mt-1">Choisissez un graphique pour garder une lecture compacte et cibler l information utile.</p>
            </div>

            <section class="ciq-surface rounded-2xl overflow-hidden">
                <div class="px-5 py-4 border-b border-sky/10 space-y-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <h3 class="text-sm font-medium text-navy">Vue graphique</h3>
                        @if ($canExportPilotage)
                        <div class="flex flex-wrap gap-2">
                            <button type="button" id="download-chart-png" class="ciq-export-button ciq-export-button--png inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-medium text-neutral-600 transition-colors">
                                <svg class="icon-svg text-xs" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M4 5h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Zm0 2v10h16V7H4Zm3 2.5A1.5 1.5 0 1 0 7 12a1.5 1.5 0 0 0 0-3Zm12 6.5H5l4-4 2.5 2.5 2-2L19 16Z" fill="currentColor"/>
                                </svg>
                                <span>Telecharger PNG</span>
                            </button>
                            <button type="button" id="download-chart-pdf" class="ciq-export-button ciq-export-button--pdf inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-medium text-neutral-600 transition-colors">
                                <svg class="icon-svg text-xs" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M7 2h7l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm6 1.5V8h4.5L13 3.5ZM8 12h2.2a2.3 2.3 0 1 1 0 4.6H9.5V19H8v-7Zm1.5 1.4v1.8h.7a.9.9 0 1 0 0-1.8h-.7ZM13 12h2.1a2.5 2.5 0 1 1 0 5H14.5V19H13v-7Zm1.5 1.4v2.2h.6a1.1 1.1 0 1 0 0-2.2h-.6ZM18.5 13.4H17V15h1.2v1.4H17V19h-1.5v-7h3v1.4Z" fill="currentColor"/>
                                </svg>
                                <span>Telecharger PDF</span>
                            </button>
                        </div>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-neutral-400">Les boutons ci-dessous affichent un seul graphique à la fois.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" data-chart-target="type" class="performance-tab inline-flex items-center gap-2 rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-2 text-sm font-medium text-neutral-600 transition-colors">
                            <svg class="icon-svg text-xs" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M11 2v10h10A10 10 0 0 0 11 2Zm-1 1.1A10 10 0 1 0 20.9 14H10V3.1Z" fill="currentColor"/>
                            </svg>
                            <span>Types</span>
                        </button>
                        <button type="button" data-chart-target="status" class="performance-tab inline-flex items-center gap-2 rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-2 text-sm font-medium text-neutral-600 transition-colors">
                            <svg class="icon-svg text-xs" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 20h16v2H2V4h2v16Zm2-8h3v6H6v-6Zm5-5h3v11h-3V7Zm5 3h3v8h-3v-8Z" fill="currentColor"/>
                            </svg>
                            <span>Statut global</span>
                        </button>
                        <button type="button" data-chart-target="timeline" class="performance-tab inline-flex items-center gap-2 rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-2 text-sm font-medium text-neutral-600 transition-colors">
                            <svg class="icon-svg text-xs" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 19h16v2H2V4h2v15Zm2.7-4.3 3.5-3.5 2.6 2.6 4.5-5.3 1.5 1.3-5.9 7-2.7-2.7-2.1 2.1-1.4-1.5Z" fill="currentColor"/>
                            </svg>
                            <span>Evolution</span>
                        </button>
                        <button type="button" data-chart-target="direction" class="performance-tab inline-flex items-center gap-2 rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-2 text-sm font-medium text-neutral-600 transition-colors">
                            <svg class="icon-svg text-xs" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 21V5a2 2 0 0 1 2-2h8v18H4Zm4-14H6v2h2V7Zm4 0h-2v2h2V7Zm-4 4H6v2h2v-2Zm4 0h-2v2h2v-2Zm7 10h-4V9l4 2v10Zm-2-6h-1v2h1v-2Z" fill="currentColor"/>
                            </svg>
                            <span>Directions</span>
                        </button>
                    </div>
                </div>

                <div class="p-5">
                    <section data-chart-panel="type" class="performance-panel hidden space-y-4">
                        <div>
                            <h4 class="text-sm font-medium text-navy">Réclamations</h4>
                            <p class="text-xs text-neutral-400 mt-1">Répartition du volume par type avec pourcentage.</p>
                        </div>
                        <div class="grid grid-cols-1 xl:grid-cols-[16rem_1fr] gap-5 items-start">
                            <div class="mx-auto w-full max-w-[16rem]">
                                <div class="relative aspect-square">
                                    <canvas id="type-volume-chart"></canvas>
                                </div>
                            </div>
                            <div class="space-y-3">
                                @forelse($typeChartEntries as $entry)
                                @php
                                    $entryPercent = $typeGrandTotal > 0 ? (($entry['total'] / $typeGrandTotal) * 100) : 0;
                                @endphp
                                <div class="ciq-inner-card rounded-xl p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-2">
                                            <span class="w-3 h-3 rounded-full" style="background-color: {{ $entry['color'] }}"></span>
                                            <p class="text-sm font-medium text-navy">{{ $entry['label'] }}</p>
                                        </div>
                                        <p class="text-sm font-semibold text-navy">{{ number_format((int) $entry['total'], 0, ',', ' ') }}</p>
                                    </div>
                                    <p class="mt-2 text-xs text-neutral-400">{{ $formatPercent($entryPercent) }} du volume total filtré</p>
                                </div>
                                @empty
                                <div class="rounded-xl border border-dashed border-sky/20 bg-white/70 px-4 py-8 text-center text-sm text-neutral-400">
                                    Aucune donnée disponible pour cette répartition.
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </section>

                    <section data-chart-panel="status" class="performance-panel hidden space-y-4">
                        <div>
                            <h4 class="text-sm font-medium text-navy">Traitement global des demandes</h4>
                            <p class="text-xs text-neutral-400 mt-1">Comparaison des demandes dans les délais, à risque et en retard.</p>
                        </div>
                        <div class="space-y-4">
                            <div class="h-72">
                                <canvas id="status-volume-chart"></canvas>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                @foreach($statusChartEntries as $entry)
                                <div class="ciq-inner-card rounded-xl p-4">
                                    <div class="flex items-center gap-2">
                                        <span class="w-3 h-3 rounded-full" style="background-color: {{ $entry['color'] }}"></span>
                                        <p class="text-sm font-medium text-navy">{{ $entry['label'] }}</p>
                                    </div>
                                    <p class="mt-3 text-2xl font-semibold text-navy">{{ number_format((int) $entry['total'], 0, ',', ' ') }}</p>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </section>

                    <section data-chart-panel="timeline" class="performance-panel hidden space-y-4">
                        <div>
                            <h4 class="text-sm font-medium text-navy">Evolution temporelle</h4>
                            <p class="text-xs text-neutral-400 mt-1">Suivi comparé des réclamations reçues et des demandes clôturées par {{ $temporalGranularityLabel }}.</p>
                        </div>
                        <div class="space-y-4">
                            <div class="h-80">
                                <canvas id="temporal-evolution-chart"></canvas>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div class="ciq-inner-card rounded-xl p-4">
                                    <div class="flex items-center gap-2">
                                        <span class="w-3 h-3 rounded-full bg-sky"></span>
                                        <p class="text-sm font-medium text-navy">Réclamations reçues</p>
                                    </div>
                                    <p class="mt-3 text-2xl font-semibold text-navy">{{ number_format(array_sum($temporalSeries['reclamations_recues'] ?? []), 0, ',', ' ') }}</p>
                                </div>
                                <div class="ciq-inner-card rounded-xl p-4">
                                    <div class="flex items-center gap-2">
                                        <span class="w-3 h-3 rounded-full bg-gold"></span>
                                        <p class="text-sm font-medium text-navy">Demandes clôturées</p>
                                    </div>
                                    <p class="mt-3 text-2xl font-semibold text-navy">{{ number_format(array_sum($temporalSeries['demandes_cloturees'] ?? []), 0, ',', ' ') }}</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section data-chart-panel="direction" class="performance-panel hidden space-y-4">
                        <div>
                            <h4 class="text-sm font-medium text-navy">Directions qui traitent le plus de demandes</h4>
                            <p class="text-xs text-neutral-400 mt-1">Le donut montre la part de demandes clôturées traitée par chaque direction.</p>
                        </div>
                        <div class="grid grid-cols-1 xl:grid-cols-[16rem_1fr] gap-5 items-start">
                            <div class="mx-auto w-full max-w-[16rem]">
                                <div class="relative aspect-square">
                                    <canvas id="direction-compliance-chart"></canvas>
                                </div>
                            </div>
                            <div class="space-y-3 max-h-80 overflow-y-auto pr-1">
                                @forelse($directionProcessingEntries as $entry)
                                <div class="ciq-inner-card rounded-xl p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <p class="text-sm font-medium text-navy">{{ $entry['label'] }}</p>
                                        <p class="text-sm font-semibold text-navy">{{ number_format((int) $entry['appliquees'], 0, ',', ' ') }}</p>
                                    </div>
                                    <div class="mt-2 flex flex-wrap gap-4 text-xs text-neutral-500">
                                        <span>Traitées : {{ number_format((int) $entry['appliquees'], 0, ',', ' ') }}</span>
                                        <span>Dans les délais : {{ number_format((int) $entry['dans_delais'], 0, ',', ' ') }}</span>
                                        <span>Conformité : {{ $formatPercent($entry['taux_dans_delais']) }}</span>
                                    </div>
                                </div>
                                @empty
                                <div class="rounded-xl border border-dashed border-sky/20 bg-white/70 px-4 py-8 text-center text-sm text-neutral-400">
                                    Aucune demande traitée disponible sur cette période.
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </section>
                </div>
            </section>

            <div id="ciq-tracking-modal" class="fixed inset-0 z-[80] hidden">
                <div id="ciq-tracking-backdrop" class="absolute inset-0 bg-navy/55 backdrop-blur-[2px]"></div>
                <div class="relative z-[81] min-h-full flex items-center justify-center px-4 py-6">
                    <div class="w-full max-w-4xl rounded-3xl bg-white shadow-2xl border border-white/70 overflow-hidden">
                        <div class="flex items-start justify-between gap-4 px-6 py-5 border-b border-neutral-200 bg-gradient-to-r from-neutral-50 to-white">
                            <div>
                                <p class="text-xs uppercase tracking-[0.25em] text-neutral-400">Lecture du dossier</p>
                                <h3 id="ciq-modal-tracking-number" class="mt-1 text-xl font-semibold text-navy">Demande</h3>
                                <p id="ciq-modal-subtitle" class="mt-1 text-sm text-neutral-500">Fiche de soumission usager</p>
                            </div>
                            <button type="button" id="ciq-tracking-close" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-neutral-200 text-neutral-500 hover:text-navy hover:border-sky/40 transition-colors">
                                <svg class="icon-svg h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            </button>
                        </div>

                        <div class="max-h-[80vh] overflow-y-auto px-6 py-6 space-y-6">
                            <section class="rounded-2xl border border-neutral-200 bg-neutral-50/60 p-5">
                                <div class="flex items-center gap-2 mb-4">
                                    <div class="w-6 h-6 rounded-full bg-navy text-white text-xs font-semibold flex items-center justify-center">1</div>
                                    <h4 class="text-sm font-semibold text-navy uppercase tracking-[0.15em]">Formulaire usager</h4>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="rounded-xl bg-white border border-neutral-200 p-4">
                                        <p class="text-xs uppercase tracking-wide text-neutral-400">Nom</p>
                                        <p id="ciq-modal-nom" class="mt-1 text-sm font-medium text-neutral-700">-</p>
                                    </div>
                                    <div class="rounded-xl bg-white border border-neutral-200 p-4">
                                        <p class="text-xs uppercase tracking-wide text-neutral-400">Prenom</p>
                                        <p id="ciq-modal-prenom" class="mt-1 text-sm font-medium text-neutral-700">-</p>
                                    </div>
                                    <div class="rounded-xl bg-white border border-neutral-200 p-4">
                                        <p class="text-xs uppercase tracking-wide text-neutral-400">Adresse email</p>
                                        <p id="ciq-modal-email" class="mt-1 text-sm font-medium text-neutral-700 break-all">-</p>
                                    </div>
                                    <div class="rounded-xl bg-white border border-neutral-200 p-4">
                                        <p class="text-xs uppercase tracking-wide text-neutral-400">Statut usager</p>
                                        <p id="ciq-modal-statut-usager" class="mt-1 text-sm font-medium text-neutral-700">-</p>
                                    </div>
                                    <div class="rounded-xl bg-white border border-neutral-200 p-4">
                                        <p class="text-xs uppercase tracking-wide text-neutral-400">Type de demande</p>
                                        <p id="ciq-modal-type" class="mt-1 text-sm font-medium text-neutral-700">-</p>
                                    </div>
                                    <div class="rounded-xl bg-white border border-neutral-200 p-4">
                                        <p class="text-xs uppercase tracking-wide text-neutral-400">Catégorie</p>
                                        <p id="ciq-modal-categorie" class="mt-1 text-sm font-medium text-neutral-700">-</p>
                                    </div>
                                    <div class="rounded-xl bg-white border border-neutral-200 p-4">
                                        <p class="text-xs uppercase tracking-wide text-neutral-400">Pays</p>
                                        <p id="ciq-modal-pays" class="mt-1 text-sm font-medium text-neutral-700">-</p>
                                    </div>
                                    <div class="rounded-xl bg-white border border-neutral-200 p-4">
                                        <p class="text-xs uppercase tracking-wide text-neutral-400">Établissement</p>
                                        <p id="ciq-modal-etablissement" class="mt-1 text-sm font-medium text-neutral-700">-</p>
                                    </div>
                                </div>
                                <div class="mt-4 rounded-xl bg-white border border-neutral-200 p-4">
                                    <p class="text-xs uppercase tracking-wide text-neutral-400">Objet</p>
                                    <p id="ciq-modal-objet" class="mt-1 text-sm font-medium text-neutral-700">-</p>
                                </div>
                                <div class="mt-4 rounded-xl bg-white border border-neutral-200 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-xs uppercase tracking-wide text-neutral-400">Message</p>
                                        <p id="ciq-modal-reception" class="text-xs text-neutral-400">-</p>
                                    </div>
                                    <p id="ciq-modal-message" class="mt-2 text-sm leading-6 text-neutral-700 whitespace-pre-line">-</p>
                                </div>
                                <div class="mt-4 rounded-xl bg-white border border-neutral-200 p-4">
                                    <p class="text-xs uppercase tracking-wide text-neutral-400">Pièces jointes</p>
                                    <div id="ciq-modal-pieces" class="mt-3 flex flex-wrap gap-2">
                                        <span class="inline-flex items-center rounded-full bg-neutral-100 px-3 py-1 text-xs text-neutral-500">Aucune pièce jointe</span>
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-2xl border border-sky/20 bg-sky/5 p-5">
                                <div class="flex items-center gap-2 mb-4">
                                    <div class="w-6 h-6 rounded-full bg-sky text-white text-xs font-semibold flex items-center justify-center">2</div>
                                    <h4 class="text-sm font-semibold text-navy uppercase tracking-[0.15em]">Suivi dossier</h4>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                    <div class="rounded-xl bg-white border border-neutral-200 p-4">
                                        <p class="text-xs uppercase tracking-wide text-neutral-400">Date de dispatch</p>
                                        <p id="ciq-modal-dispatch" class="mt-1 text-sm font-medium text-neutral-700">-</p>
                                    </div>
                                    <div class="rounded-xl bg-white border border-neutral-200 p-4">
                                        <p class="text-xs uppercase tracking-wide text-neutral-400">Service</p>
                                        <p id="ciq-modal-service" class="mt-1 text-sm font-medium text-neutral-700">-</p>
                                    </div>
                                    <div class="rounded-xl bg-white border border-neutral-200 p-4">
                                        <p class="text-xs uppercase tracking-wide text-neutral-400">Realisation</p>
                                        <p id="ciq-modal-realisation" class="mt-1 text-sm font-medium text-neutral-700">-</p>
                                    </div>
                                    <div class="rounded-xl bg-white border border-neutral-200 p-4">
                                        <p class="text-xs uppercase tracking-wide text-neutral-400">Statut</p>
                                        <p id="ciq-modal-statut" class="mt-1 text-sm font-medium text-neutral-700">-</p>
                                    </div>
                                    <div class="rounded-xl bg-white border border-neutral-200 p-4">
                                        <p class="text-xs uppercase tracking-wide text-neutral-400">Respect délais</p>
                                        <p id="ciq-modal-respect" class="mt-1 text-sm font-medium text-neutral-700">-</p>
                                    </div>
                                    <div class="rounded-xl bg-white border border-neutral-200 p-4">
                                        <p class="text-xs uppercase tracking-wide text-neutral-400">RZ / CS</p>
                                        <p id="ciq-modal-qcs" class="mt-1 text-sm font-medium text-neutral-700">-</p>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @if ($canViewScopedCiqTables)
        <section class="space-y-5">
            <div>
                <h2 class="ciq-section-title text-sm font-medium text-navy">Tableau actuel du suivi des réclamations</h2>
                <p class="text-xs text-neutral-400 mt-1">
                    {{ in_array('chef_direction', $roleCodes, true)
                        ? 'Presentation alignee sur le modele CIQ, limitee a votre perimetre de services.'
                        : (in_array('chef_service', $roleCodes, true)
                            ? 'Présentation alignée sur le modèle CIQ, limitée aux réclamations de votre service.'
                            : 'Présentation alignée sur le modèle bureautique CIQ : réception, dispatch, service, réalisation et respect des délais.') }}
                </p>
            </div>

            <section class="ciq-surface rounded-2xl overflow-hidden">
                <div class="px-5 py-4 border-b border-sky/10 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="icon-svg h-4 w-4 text-sky" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 5.5h18v13H3z" stroke="currentColor" stroke-width="1.7"/><path d="M3 10h18M9 10v8M15 10v8" stroke="currentColor" stroke-width="1.7"/></svg>
                        <h3 class="text-sm font-medium text-navy">Suivi détaillé</h3>
                    </div>
                    @if ($canExportPilotage)
                    <div class="flex items-center gap-2">
                      <p class="text-xs text-neutral-400 hidden sm:block">{{ number_format($ciqTrackingRows->count(), 0, ',', ' ') }} ligne(s) sur le filtre courant</p>
                        <button type="button" id="ciq-tracking-export-xls" class="ciq-export-button ciq-export-button--excel inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-neutral-600 transition-colors">
                        <svg class="icon-svg h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2h7l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14 2v6h6" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="m8.5 12.5 3 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="m11.5 12.5-3 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M14.5 12.5h2.5M14.5 15.5H17M14.5 18.5h2.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                        <span>Excel</span>
                        </button>
                        <button type="button" id="ciq-tracking-export-pdf" class="ciq-export-button ciq-export-button--pdf inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-neutral-600 transition-colors">
                        <svg class="icon-svg h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2h7l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14 2v6h6" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M8.5 18.5v-6h2.2a1.9 1.9 0 1 1 0 3.8H8.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 18.5v-6h1.8a2 2 0 1 1 0 4H14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span>PDF</span>
                        </button>
                    </div>
                    @endif
                </div>

                <div id="ciq-tracking-export-area" class="overflow-x-auto bg-white">
                    <table id="ciq-tracking-table" class="w-full min-w-[1220px] table-fixed">
                        <colgroup>
                            <col class="w-[52px]">
                            <col class="w-[112px]">
                            <col class="w-[170px]">
                            <col class="w-[340px]">
                            <col class="w-[112px]">
                            <col class="w-[108px]">
                            <col class="w-[86px]">
                            <col class="w-[112px]">
                            <col class="w-[100px]">
                            <col class="w-[96px]">
                            <col class="w-[126px]">
                            <col class="w-[148px]">
                        </colgroup>
                        <thead>
                            <tr class="bg-[#2f5f93] border-b border-[#274f79]">
                                <th class="px-3 py-3 text-left text-[11px] font-medium text-white uppercase tracking-wider">N°</th>
                                <th class="px-3 py-3 text-left text-[11px] font-medium text-white uppercase tracking-wider">Date de reception</th>
                                <th class="px-3 py-3 text-left text-[11px] font-medium text-white uppercase tracking-wider">Expediteur</th>
                                <th class="px-3 py-3 text-left text-[11px] font-medium text-white uppercase tracking-wider">Objet</th>
                                <th class="px-3 py-3 text-left text-[11px] font-medium text-white uppercase tracking-wider">Date de dispatch</th>
                                <th class="px-3 py-3 text-left text-[11px] font-medium text-white uppercase tracking-wider">Délais de transmission</th>
                                <th class="px-3 py-3 text-left text-[11px] font-medium text-white uppercase tracking-wider">Service</th>
                                <th class="px-3 py-3 text-left text-[11px] font-medium text-white uppercase tracking-wider">Realisation</th>
                                <th class="px-3 py-3 text-left text-[11px] font-medium text-white uppercase tracking-wider">Statut</th>
                                <th class="px-3 py-3 text-left text-[11px] font-medium text-white uppercase tracking-wider">Respect délais</th>
                                <th class="px-3 py-3 text-left text-[11px] font-medium text-white uppercase tracking-wider">Nombre de jours d attente</th>
                                <th class="px-3 py-3 text-left text-[11px] font-medium text-white uppercase tracking-wider">RZ / CS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200">
                            @forelse($ciqTrackingRows as $row)
                            <tr class="trow transition-colors duration-100 even:bg-[#f5f8fc]">
                                <td class="px-3 py-3 text-sm text-neutral-600">{{ $row['rang'] ?? '-' }}</td>
                                <td class="px-3 py-3 text-sm text-neutral-700 whitespace-nowrap">{{ $formatShortDate($row['date_reception'] ?? null) }}</td>
                                <td class="px-3 py-3 text-sm text-neutral-700">{{ $row['expediteur'] ?? '-' }}</td>
                                <td class="px-3 py-3 text-sm text-neutral-700 min-w-[240px]">{{ $row['objet'] ?? '-' }}</td>
                                <td class="px-3 py-3 text-sm text-neutral-700 whitespace-nowrap">{{ $formatShortDate($row['date_dispatching'] ?? null) }}</td>
                                <td class="px-3 py-3 text-sm font-medium {{ ($row['delai_transmission_oh'] ?? '-') === 'OUI' ? 'text-green-700' : (($row['delai_transmission_oh'] ?? '-') === 'NON' ? 'text-red-700' : 'text-neutral-500') }}">
                                    {{ $row['delai_transmission_oh'] ?? '-' }}
                                </td>
                                <td class="px-3 py-3 text-sm text-neutral-700 whitespace-nowrap">{{ $row['service_direction'] ?? '-' }}</td>
                                <td class="px-3 py-3 text-sm text-neutral-700 whitespace-nowrap">{{ $formatShortDate($row['realisation'] ?? null) }}</td>
                                <td class="px-3 py-3 text-sm font-medium text-neutral-700">{{ $row['statut_traitement'] ?? '-' }}</td>
                                <td class="px-3 py-3 text-sm font-medium {{ ($row['respect_delais'] ?? 'NON') === 'OUI' ? 'text-green-700' : 'text-red-700' }}">
                                    {{ $row['respect_delais'] ?? '-' }}
                                </td>
                                <td class="px-3 py-3 text-sm text-neutral-700 whitespace-nowrap">{{ $row['jours_attente'] ?? '-' }}</td>
                                <td class="px-3 py-3 text-sm text-neutral-700 whitespace-nowrap">{{ $row['qcs'] ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="12" class="px-4 py-10 text-center text-sm text-neutral-400">Aucune donnée disponible pour ce tableau sur la période sélectionnée.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </section>
        @endif

        <section class="space-y-5">
            <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <h2 class="text-sm font-medium text-navy">tableau de la répartition des réclamations les plus récurrentes dans la cellule</h2>
                    <p class="text-xs text-neutral-400 mt-1">Nombre de mails par catégorie avec pourcentage sur le total des réclamations.</p>
                </div>
                @if ($canExportPilotage)
                <div class="flex items-center gap-2">
                    <p class="hidden sm:block text-xs text-neutral-400">{{ number_format($annexeReclamationsRows->count(), 0, ',', ' ') }} ligne(s) au total</p>
                    <button type="button" id="annexe2-export-xls" class="ciq-export-button ciq-export-button--excel inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-neutral-600 transition-colors">
                        <svg class="icon-svg h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2h7l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14 2v6h6" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="m8.5 12.5 3 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="m11.5 12.5-3 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M14.5 12.5h2.5M14.5 15.5H17M14.5 18.5h2.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                        <span>Excel</span>
                    </button>
                    <button type="button" id="annexe2-export-pdf" class="ciq-export-button ciq-export-button--pdf inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-neutral-600 transition-colors">
                        <svg class="icon-svg h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2h7l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14 2v6h6" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M8.5 18.5v-6h2.2a1.9 1.9 0 1 1 0 3.8H8.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 18.5v-6h1.8a2 2 0 1 1 0 4H14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span>PDF</span>
                    </button>
                </div>
                @endif
            </div>

            <section id="annexe2-export-area" class="space-y-5 ciq-surface rounded-2xl p-4 sm:p-5">
                <div class="grid grid-cols-1 gap-5 items-start">
                    <section class="bg-white rounded-2xl border border-neutral-100 overflow-hidden">
                        
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[760px] border-collapse">
                                <thead>
                                    <tr class="bg-[#d0deaa]">
                                        <th class="px-3 py-2 text-left text-sm font-semibold text-navy border border-neutral-300">Réclamation</th>
                                        <th class="px-3 py-2 text-center text-sm font-semibold text-navy border border-neutral-300 w-[150px]">Nombre de mails</th>
                                        <th class="px-3 py-2 text-center text-sm font-semibold text-navy border border-neutral-300 w-[90px]">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($annexeReclamationsRows as $row)
                                    <tr class="odd:bg-white even:bg-neutral-50">
                                        <td class="px-3 py-1.5 text-sm text-neutral-700 border border-neutral-300">{{ $row['categorie'] ?? '-' }}</td>
                                        <td class="px-3 py-1.5 text-sm text-red-600 font-semibold text-center border border-neutral-300">{{ number_format((int) ($row['nombre_mails'] ?? 0), 0, ',', ' ') }}</td>
                                        <td class="px-3 py-1.5 text-sm text-neutral-700 text-center border border-neutral-300">{{ $formatPercent($row['pourcentage'] ?? 0) }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-8 text-center text-sm text-neutral-400 border border-neutral-300">Aucune donnée disponible pour les réclamations.</td>
                                    </tr>
                                    @endforelse
                                    <tr class="bg-[#fff500]">
                                        <td class="px-3 py-2 text-sm font-semibold text-center text-navy border border-neutral-300 uppercase">Total réclamations</td>
                                        <td class="px-3 py-2 text-sm font-semibold text-center text-red-600 border border-neutral-300">{{ number_format($annexeReclamationsTotal, 0, ',', ' ') }}</td>
                                        <td class="px-3 py-2 text-sm font-semibold text-center text-navy border border-neutral-300">&nbsp;</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </section>
        </section>
        <section class="space-y-5">
            <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <h2 class="ciq-section-title text-sm font-medium text-navy">tableau de répartition de l'ensemble des réclamations de la cellule par direction</h2>
                    <p class="text-xs text-neutral-400 mt-1">Toutes les demandes appliquées et non appliquées par fonction, avec exécution, conformité et score moyen.</p>
                </div>
                @if ($canExportPilotage)
                <div class="flex items-center gap-2">
                    <p class="hidden sm:block text-xs text-neutral-400">{{ number_format($functionDistributionRows->count(), 0, ',', ' ') }} ligne(s) au total</p>
                    <button type="button" id="function-distribution-export-xls" class="ciq-export-button ciq-export-button--excel inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-neutral-600 transition-colors">
                        <svg class="icon-svg h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2h7l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14 2v6h6" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="m8.5 12.5 3 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="m11.5 12.5-3 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M14.5 12.5h2.5M14.5 15.5H17M14.5 18.5h2.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                        <span>Excel</span>
                    </button>
                    <button type="button" id="function-distribution-export-pdf" class="ciq-export-button ciq-export-button--pdf inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-neutral-600 transition-colors">
                        <svg class="icon-svg h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2h7l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14 2v6h6" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M8.5 18.5v-6h2.2a1.9 1.9 0 1 1 0 3.8H8.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 18.5v-6h1.8a2 2 0 1 1 0 4H14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span>PDF</span>
                    </button>
                </div>
                @endif
            </div>

            <section id="function-distribution-export-area" class="ciq-surface rounded-2xl p-4 sm:p-5 overflow-hidden">
                <div class="overflow-x-auto">
                    <table id="function-distribution-table" class="w-full min-w-[1180px] border-collapse">
                        <thead>
                            <tr class="bg-neutral-50">
                                <th rowspan="2" class="px-3 py-3 text-center text-sm font-semibold text-navy border border-neutral-300 w-[120px]">Fonctions</th>
                                <th colspan="6" class="px-3 py-3 text-center text-sm font-semibold text-navy border border-neutral-300">Repartition par fonction</th>
                            </tr>
                            <tr class="bg-white">
                                <th class="px-3 py-3 text-center text-sm font-semibold text-navy border border-neutral-300">Nombre de réclamations total</th>
                                <th class="px-3 py-3 text-center text-sm font-semibold text-navy border border-neutral-300">Nombre de réclamations traitées</th>
                                <th class="px-3 py-3 text-center text-sm font-semibold text-emerald-600 border border-neutral-300">Taux d'exécution (%)</th>
                                <th class="px-3 py-3 text-center text-sm font-semibold text-navy border border-neutral-300">Nombre de réclamations traitées dans les délais</th>
                                <th class="px-3 py-3 text-center text-sm font-semibold text-emerald-600 border border-neutral-300">Taux de conformité (72h)(%)</th>
                                <th class="px-3 py-3 text-center text-sm font-semibold text-red-500 border border-neutral-300">(A + B) / 2</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($functionDistributionRows as $row)
                            <tr class="odd:bg-white even:bg-neutral-50">
                                <td class="px-3 py-2 text-sm font-semibold text-center text-navy border border-neutral-300">{{ $row['fonction_code'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-sm text-center text-neutral-700 border border-neutral-300">{{ number_format((int) ($row['total_demandes'] ?? 0), 0, ',', ' ') }}</td>
                                <td class="px-3 py-2 text-sm text-center text-neutral-700 border border-neutral-300">{{ number_format((int) ($row['total_traitees'] ?? 0), 0, ',', ' ') }}</td>
                                <td class="px-3 py-2 text-sm font-semibold text-center text-emerald-600 border border-neutral-300">{{ $formatPercent($row['taux_execution'] ?? 0) }}</td>
                                <td class="px-3 py-2 text-sm text-center text-neutral-700 border border-neutral-300">{{ number_format((int) ($row['total_traitees_delai'] ?? 0), 0, ',', ' ') }}</td>
                                <td class="px-3 py-2 text-sm font-semibold text-center text-emerald-600 border border-neutral-300">{{ $formatPercent($row['taux_conformite'] ?? 0) }}</td>
                                <td class="px-3 py-2 text-sm font-semibold text-center text-red-500 border border-neutral-300">{{ $formatPercent($row['score_moyen'] ?? 0) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-neutral-400 border border-neutral-300">Aucune donnée disponible pour cette répartition sur la période sélectionnée.</td>
                            </tr>
                            @endforelse
                            <tr class="bg-neutral-100">
                                <td class="px-3 py-2 text-sm font-semibold text-center text-navy border border-neutral-300">Total</td>
                                <td class="px-3 py-2 text-sm font-semibold text-center text-neutral-700 border border-neutral-300">{{ number_format((int) ($functionDistributionTotals['total_demandes'] ?? 0), 0, ',', ' ') }}</td>
                                <td class="px-3 py-2 text-sm font-semibold text-center text-neutral-700 border border-neutral-300">{{ number_format((int) ($functionDistributionTotals['total_traitees'] ?? 0), 0, ',', ' ') }}</td>
                                <td class="px-3 py-2 text-sm font-semibold text-center text-emerald-600 border border-neutral-300">{{ $formatPercent($functionDistributionTotals['taux_execution'] ?? 0) }}</td>
                                <td class="px-3 py-2 text-sm font-semibold text-center text-neutral-700 border border-neutral-300">{{ number_format((int) ($functionDistributionTotals['total_traitees_delai'] ?? 0), 0, ',', ' ') }}</td>
                                <td class="px-3 py-2 text-sm font-semibold text-center text-emerald-600 border border-neutral-300">{{ $formatPercent($functionDistributionTotals['taux_conformite'] ?? 0) }}</td>
                                <td class="px-3 py-2 text-sm font-semibold text-center text-red-500 border border-neutral-300">{{ $formatPercent($functionDistributionTotals['score_moyen'] ?? 0) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </section>
        <section class="space-y-5">
            <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <h2 class="ciq-section-title text-sm font-medium text-navy">tableau de répartition des réclamations par service</h2>
                    <p class="text-xs text-neutral-400 mt-1">Vue par service et responsable, avec exécution et conformité sur les réclamations.</p>
                </div>
                @if ($canExportPilotage)
                <div class="flex items-center gap-2">
                    <p class="hidden sm:block text-xs text-neutral-400">{{ number_format($reclamationServiceDistributionRows->count(), 0, ',', ' ') }} ligne(s) au total</p>
                    <button type="button" id="reclamation-service-distribution-export-xls" class="ciq-export-button ciq-export-button--excel inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-neutral-600 transition-colors">
                        <svg class="icon-svg h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2h7l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14 2v6h6" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="m8.5 12.5 3 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="m11.5 12.5-3 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M14.5 12.5h2.5M14.5 15.5H17M14.5 18.5h2.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                        <span>Excel</span>
                    </button>
                    <button type="button" id="reclamation-service-distribution-export-pdf" class="ciq-export-button ciq-export-button--pdf inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-neutral-600 transition-colors">
                        <svg class="icon-svg h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2h7l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14 2v6h6" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M8.5 18.5v-6h2.2a1.9 1.9 0 1 1 0 3.8H8.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 18.5v-6h1.8a2 2 0 1 1 0 4H14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span>PDF</span>
                    </button>
                </div>
                @endif
            </div>

            <section id="reclamation-service-distribution-export-area" class="ciq-surface rounded-2xl p-4 sm:p-5 overflow-hidden">
                <div class="overflow-x-auto">
                    <table id="reclamation-service-distribution-table" class="w-full min-w-[1120px] border-collapse">
                        <thead>
                            <tr class="bg-neutral-50">
                                <th class="px-3 py-3 text-center text-sm font-semibold text-navy border border-neutral-300">Services / Unite</th>
                                <th class="px-3 py-3 text-center text-sm font-semibold text-navy border border-neutral-300">Agents</th>
                                <th class="px-3 py-3 text-center text-sm font-semibold text-navy border border-neutral-300">Nbre de mails reçus</th>
                                <th class="px-3 py-3 text-center text-sm font-semibold text-navy border border-neutral-300">Nbre de mails traités</th>
                                <th class="px-3 py-3 text-center text-sm font-semibold text-emerald-600 border border-neutral-300">Taux d'exécution (%) (A)</th>
                                <th class="px-3 py-3 text-center text-sm font-semibold text-navy border border-neutral-300">Nbre de mails traités dans les délais</th>
                                <th class="px-3 py-3 text-center text-sm font-semibold text-emerald-600 border border-neutral-300">Taux de conformité (72h) (%) (B)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reclamationServiceDistributionRows as $row)
                            <tr class="odd:bg-white even:bg-neutral-50">
                                <td class="px-3 py-2 text-sm font-semibold text-center text-navy border border-neutral-300">{{ $row['service_code'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-sm text-center text-neutral-700 border border-neutral-300">{{ $row['responsable'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-sm text-center text-neutral-700 border border-neutral-300">{{ number_format((int) ($row['total_demandes'] ?? 0), 0, ',', ' ') }}</td>
                                <td class="px-3 py-2 text-sm text-center text-neutral-700 border border-neutral-300">{{ number_format((int) ($row['total_traitees'] ?? 0), 0, ',', ' ') }}</td>
                                <td class="px-3 py-2 text-sm font-semibold text-center text-emerald-600 border border-neutral-300">{{ $formatPercent($row['taux_execution'] ?? 0) }}</td>
                                <td class="px-3 py-2 text-sm text-center text-neutral-700 border border-neutral-300">{{ number_format((int) ($row['total_traitees_delai'] ?? 0), 0, ',', ' ') }}</td>
                                <td class="px-3 py-2 text-sm font-semibold text-center text-emerald-600 border border-neutral-300">{{ $formatPercent($row['taux_conformite'] ?? 0) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-neutral-400 border border-neutral-300">Aucune donnée disponible pour cette répartition par service sur la période sélectionnée.</td>
                            </tr>
                            @endforelse
                            <tr class="bg-neutral-100">
                                <td colspan="2" class="px-3 py-2 text-sm font-semibold text-center text-navy border border-neutral-300">TOTAL</td>
                                <td class="px-3 py-2 text-sm font-semibold text-center text-neutral-700 border border-neutral-300">{{ number_format((int) ($reclamationServiceDistributionTotals['total_demandes'] ?? 0), 0, ',', ' ') }}</td>
                                <td class="px-3 py-2 text-sm font-semibold text-center text-neutral-700 border border-neutral-300">{{ number_format((int) ($reclamationServiceDistributionTotals['total_traitees'] ?? 0), 0, ',', ' ') }}</td>
                                <td class="px-3 py-2 text-sm font-semibold text-center text-emerald-600 border border-neutral-300">{{ $formatPercent($reclamationServiceDistributionTotals['taux_execution'] ?? 0) }}</td>
                                <td class="px-3 py-2 text-sm font-semibold text-center text-neutral-700 border border-neutral-300">{{ number_format((int) ($reclamationServiceDistributionTotals['total_traitees_delai'] ?? 0), 0, ',', ' ') }}</td>
                                <td class="px-3 py-2 text-sm font-semibold text-center text-emerald-600 border border-neutral-300">{{ $formatPercent($reclamationServiceDistributionTotals['taux_conformite'] ?? 0) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </section>

        <section class="space-y-5">
            <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                <div>
                    <h2 class="ciq-section-title text-sm font-medium text-navy">Consultation des dossiers usagers</h2>
                    <p class="text-xs text-neutral-400 mt-1">Registre de consultation des demandes, avec ouverture detaillee via le bouton Consulter.</p>
                </div>
                <p class="text-xs text-neutral-400">{{ number_format($ciqTrackingRows->count(), 0, ',', ' ') }} dossier(s) disponibles sur le filtre courant</p>
            </div>

            <section class="ciq-surface rounded-2xl overflow-hidden">
                <div class="px-5 py-4 border-b border-sky/10 flex items-center gap-2">
                    <svg class="icon-svg h-4 w-4 text-sky" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 7.5A2.5 2.5 0 0 1 5.5 5H10l2 2h6.5A2.5 2.5 0 0 1 21 9.5V11H8.5L6.8 13H3V7.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M3 13h4.6l1.8-2H21l-2 8H5.5A2.5 2.5 0 0 1 3 16.5V13Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                    <h3 class="text-sm font-medium text-navy">Registre des numéros de suivi</h3>
                </div>

                <div class="overflow-x-auto bg-white">
                    <table class="w-full min-w-[980px]">
                        <thead class="bg-neutral-50 border-b border-neutral-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Numéro de suivi</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Expediteur</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Objet</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Reception</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Service</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Statut</th>
                                <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200">
                            @forelse($ciqTrackingRows as $row)
                            <tr class="odd:bg-white even:bg-neutral-50/70 hover:bg-sky/5 transition-colors">
                                <td class="px-4 py-3 align-top">
                                    <div class="inline-flex items-center gap-2 text-sm font-semibold text-navy">
                                        <svg class="icon-svg h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 7.5A2.5 2.5 0 0 1 5.5 5H10l2 2h6.5A2.5 2.5 0 0 1 21 9.5V11H8.5L6.8 13H3V7.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M3 13h4.6l1.8-2H21l-2 8H5.5A2.5 2.5 0 0 1 3 16.5V13Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                                        <span>{{ $row['numero_suivi'] ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-neutral-700 align-top">{{ $row['expediteur'] ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-neutral-700 align-top">{{ $row['objet'] ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-neutral-600 whitespace-nowrap align-top">{{ $formatShortDate($row['date_reception'] ?? null) }}</td>
                                <td class="px-4 py-3 text-sm text-neutral-600 whitespace-nowrap align-top">{{ $row['service_direction'] ?? '-' }}</td>
                                <td class="px-4 py-3 align-top">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-medium {{ ($row['respect_delais'] ?? 'NON') === 'OUI' ? 'bg-leaf/10 text-green-700' : 'bg-red-50 text-red-700' }}">
                                        {{ $row['statut_traitement'] ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right align-top">
                                    <button
                                        type="button"
                                        class="tracking-detail-trigger inline-flex items-center gap-2 rounded-lg border border-neutral-200 bg-white px-3 py-2 text-xs font-medium text-neutral-600 hover:border-sky/35 hover:text-navy transition-colors"
                                        data-tracking-index="{{ $loop->index }}"
                                    >
                                        <svg class="icon-svg h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.7"/></svg>
                                        <span>Consulter</span>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-neutral-400">
                                    Aucun dossier disponible pour la consultation sur la période sélectionnée.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-3 border-t border-neutral-200 bg-neutral-50 text-xs text-neutral-400">
                    Utiliser le bouton <span class="font-medium text-neutral-500">Consulter</span> pour ouvrir la fiche complete de la demande.
                </div>
            </section>
        </section>

    @endif

        <footer class="border-t border-neutral-200 pt-4 pb-2 mt-6 flex items-center justify-between text-xs text-neutral-400">
            <span>&copy; {{ date('Y') }} Agence Nationale des Bourses du Gabon</span>
            <span class="font-medium">Constructeur d avenir</span>
        </footer>
    </main>
    <script>
        (function () {
            const periodSelect = document.getElementById('periode');
            const dateFrom = document.getElementById('date_from');
            const dateTo = document.getElementById('date_to');
            const datePickerTriggers = document.querySelectorAll('.date-picker-trigger');
            const performanceTabs = document.querySelectorAll('.performance-tab');
            const performancePanels = document.querySelectorAll('.performance-panel');
            const initializedCharts = {};
            const chartInstances = {};
            const downloadChartPngButton = document.getElementById('download-chart-png');
            const downloadChartPdfButton = document.getElementById('download-chart-pdf');
            const ciqTrackingExportXlsButton = document.getElementById('ciq-tracking-export-xls');
            const ciqTrackingExportPdfButton = document.getElementById('ciq-tracking-export-pdf');
            const annexe2ExportXlsButton = document.getElementById('annexe2-export-xls');
            const annexe2ExportPdfButton = document.getElementById('annexe2-export-pdf');
            const functionDistributionExportXlsButton = document.getElementById('function-distribution-export-xls');
            const functionDistributionExportPdfButton = document.getElementById('function-distribution-export-pdf');
            const reclamationServiceDistributionExportXlsButton = document.getElementById('reclamation-service-distribution-export-xls');
            const reclamationServiceDistributionExportPdfButton = document.getElementById('reclamation-service-distribution-export-pdf');
            const trackingDetailButtons = document.querySelectorAll('.tracking-detail-trigger');
            const ciqTrackingModal = document.getElementById('ciq-tracking-modal');
            const ciqTrackingBackdrop = document.getElementById('ciq-tracking-backdrop');
            const ciqTrackingClose = document.getElementById('ciq-tracking-close');
            const ciqTrackingDetails = @json($ciqTrackingRowsJson);
            let activePerformanceTarget = 'type';

            function formatCountUpValue(value, decimals, suffix = '') {
                return `${Number(value).toLocaleString('fr-FR', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals,
                })}${suffix}`;
            }

            function animateCountUp(element) {
                if (!element || element.dataset.countupAnimated === 'true') {
                    return;
                }

                const target = Number(element.dataset.countup ?? '0');
                const decimals = Number(element.dataset.decimals ?? '0');
                const suffix = element.dataset.suffix ?? '';
                const duration = 900;
                const start = performance.now();

                element.dataset.countupAnimated = 'true';

                function tick(now) {
                    const progress = Math.min((now - start) / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    const current = target * eased;
                    element.textContent = formatCountUpValue(current, decimals, suffix);

                    if (progress < 1) {
                        requestAnimationFrame(tick);
                        return;
                    }

                    element.textContent = formatCountUpValue(target, decimals, suffix);
                }

                requestAnimationFrame(tick);
            }

            function formatDetailDate(value) {
                if (!value) {
                    return '-';
                }

                const date = new Date(value);
                if (Number.isNaN(date.getTime())) {
                    return value;
                }

                return date.toLocaleString('fr-FR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                });
            }

            function setModalText(id, value) {
                const element = document.getElementById(id);
                if (!element) {
                    return;
                }

                element.textContent = value && `${value}`.trim() !== '' ? value : '-';
            }

            function renderTrackingAttachments(attachments) {
                const container = document.getElementById('ciq-modal-pieces');
                if (!container) {
                    return;
                }

                container.innerHTML = '';

                if (!Array.isArray(attachments) || attachments.length === 0) {
                    const empty = document.createElement('span');
                    empty.className = 'inline-flex items-center rounded-full bg-neutral-100 px-3 py-1 text-xs text-neutral-500';
                    empty.textContent = 'Aucune pièce jointe';
                    container.appendChild(empty);
                    return;
                }

                attachments.forEach((piece) => {
                    const link = document.createElement('a');
                    link.href = `/pieces-jointes/${piece.id_piece_jointe}`;
                    link.target = '_blank';
                    link.rel = 'noopener';
                    link.className = 'inline-flex items-center gap-2 rounded-full border border-sky/20 bg-sky/5 px-3 py-1.5 text-xs font-medium text-sky hover:bg-sky/10 transition-colors';

                    const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                    icon.setAttribute('viewBox', '0 0 24 24');
                    icon.setAttribute('fill', 'none');
                    icon.setAttribute('aria-hidden', 'true');
                    icon.setAttribute('class', 'icon-svg h-3 w-3');
                    const iconPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                    iconPath.setAttribute('d', 'M16.5 6.5a4 4 0 0 1 0 5.7l-6.8 6.8a3 3 0 0 1-4.2-4.2l6.7-6.8 1.4 1.4-6.7 6.8a1 1 0 1 0 1.4 1.4l6.8-6.8a2 2 0 0 0-2.8-2.8L5.8 14.7 4.4 13.3l6.5-6.8a4 4 0 0 1 5.6 0Z');
                    iconPath.setAttribute('stroke', 'currentColor');
                    iconPath.setAttribute('stroke-width', '1.8');
                    iconPath.setAttribute('stroke-linecap', 'round');
                    iconPath.setAttribute('stroke-linejoin', 'round');
                    icon.appendChild(iconPath);
                    const label = document.createElement('span');
                    label.textContent = piece.nom_fichier ?? 'Pièce jointe';

                    link.appendChild(icon);
                    link.appendChild(label);
                    container.appendChild(link);
                });
            }

            function openTrackingModal(index) {
                const row = ciqTrackingDetails[index];
                if (!row || !ciqTrackingModal) {
                    return;
                }

                setModalText('ciq-modal-tracking-number', row.numero_suivi ?? '-');
                setModalText('ciq-modal-subtitle', `${row.expediteur ?? '-'} - ${row.type_demande ?? '-'}`);
                setModalText('ciq-modal-nom', row.usager_nom ?? '-');
                setModalText('ciq-modal-prenom', row.usager_prenom ?? '-');
                setModalText('ciq-modal-email', row.usager_email ?? '-');
                setModalText('ciq-modal-statut-usager', row.usager_statut ?? '-');
                setModalText('ciq-modal-type', row.type_demande ?? '-');
                setModalText('ciq-modal-categorie', row.categorie ?? '-');
                setModalText('ciq-modal-pays', row.usager_pays ?? '-');
                setModalText('ciq-modal-etablissement', row.usager_etablissement ?? '-');
                setModalText('ciq-modal-objet', row.objet_original ?? row.objet ?? '-');
                setModalText('ciq-modal-message', row.message ?? '-');
                setModalText('ciq-modal-reception', `Soumise le ${formatDetailDate(row.date_reception ?? null)}`);
                setModalText('ciq-modal-dispatch', formatDetailDate(row.date_dispatching ?? null));
                setModalText('ciq-modal-service', row.service_direction ?? '-');
                setModalText('ciq-modal-realisation', formatDetailDate(row.realisation ?? null));
                setModalText('ciq-modal-statut', row.statut_traitement ?? '-');
                setModalText('ciq-modal-respect', row.respect_delais ?? '-');
                setModalText('ciq-modal-qcs', row.qcs ?? '-');
                renderTrackingAttachments(row.pieces_jointes ?? []);

                ciqTrackingModal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            function closeTrackingModal() {
                if (!ciqTrackingModal) {
                    return;
                }

                ciqTrackingModal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            function initCountUps() {
                document.querySelectorAll('[data-countup]').forEach((element) => {
                    animateCountUp(element);
                });
            }

            function syncDateInputs() {
                if (!periodSelect || !dateFrom || !dateTo) {
                    return;
                }

                dateFrom.min = '';
                dateFrom.max = dateTo.value || '';
                dateTo.min = dateFrom.value || '';
                dateTo.max = '';
            }

            function forceCustomPeriod() {
                if (!periodSelect || (!dateFrom?.value && !dateTo?.value)) {
                    return;
                }

                periodSelect.value = 'custom';
                syncDateInputs();
            }

            function handlePeriodChange() {
                if (!periodSelect || !dateFrom || !dateTo) {
                    return;
                }

                if (periodSelect.value !== 'custom') {
                    dateFrom.value = '';
                    dateTo.value = '';
                }

                syncDateInputs();
            }

            function openDatePicker(event) {
                const trigger = event.currentTarget;
                const targetId = trigger?.dataset?.target;
                const input = targetId ? document.getElementById(targetId) : null;

                if (!input) {
                    return;
                }

                if (typeof input.showPicker === 'function') {
                    input.showPicker();
                    return;
                }

                input.focus();
            }

            function initTypeVolumeChart() {
                const canvas = document.getElementById('type-volume-chart');
                const entries = @json($typeChartEntries);

                if (!canvas || !entries.length || typeof Chart === 'undefined') {
                    return null;
                }

                if (chartInstances.type) {
                    return chartInstances.type;
                }

                const total = entries.reduce((sum, entry) => sum + Number(entry.total || 0), 0);

                chartInstances.type = new Chart(canvas, {
                    type: 'doughnut',
                    data: {
                        labels: entries.map((entry) => entry.label),
                        datasets: [{
                            data: entries.map((entry) => Number(entry.total || 0)),
                            backgroundColor: entries.map((entry) => entry.color),
                            borderColor: '#ffffff',
                            borderWidth: 4,
                            hoverOffset: 6,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '58%',
                        plugins: {
                            legend: {
                                display: false,
                            },
                            tooltip: {
                                callbacks: {
                                    label(context) {
                                        const value = Number(context.parsed || 0);
                                        const percent = total > 0 ? ((value / total) * 100).toFixed(1).replace('.', ',') : '0,0';
                                        return `${context.label}: ${new Intl.NumberFormat('fr-FR').format(value)} (${percent} %)`;
                                    }
                                }
                            }
                        }
                    }
                });

                return chartInstances.type;
            }

            function initStatusVolumeChart() {
                const canvas = document.getElementById('status-volume-chart');
                const entries = @json($statusChartEntries);

                if (!canvas || typeof Chart === 'undefined') {
                    return null;
                }

                if (chartInstances.status) {
                    return chartInstances.status;
                }

                chartInstances.status = new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: entries.map((entry) => entry.label),
                        datasets: [{
                            label: 'Demandes',
                            data: entries.map((entry) => Number(entry.total || 0)),
                            backgroundColor: entries.map((entry) => entry.color),
                            borderRadius: 12,
                            borderSkipped: false,
                            maxBarThickness: 72,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false,
                            },
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false,
                                },
                                ticks: {
                                    color: '#495057',
                                },
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                    color: '#495057',
                                },
                                grid: {
                                    color: 'rgba(28, 32, 61, 0.08)',
                                },
                            },
                        },
                    }
                });

                return chartInstances.status;
            }

            function initTemporalEvolutionChart() {
                const canvas = document.getElementById('temporal-evolution-chart');
                const labels = @json($temporalLabels);
                const series = @json($temporalSeries);
                const normalizeSeries = (values) => {
                    if (Array.isArray(values)) {
                        return values.map((value) => Number(value || 0));
                    }

                    if (values && typeof values === 'object') {
                        return Object.values(values).map((value) => Number(value || 0));
                    }

                    return [];
                };
                const reclamationsRecues = normalizeSeries(series.reclamations_recues);
                const demandesCloturees = normalizeSeries(series.demandes_cloturees);

                if (!canvas || typeof Chart === 'undefined' || !labels.length) {
                    return null;
                }

                if (chartInstances.timeline) {
                    return chartInstances.timeline;
                }

                chartInstances.timeline = new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [
                            {
                                label: 'Réclamations reçues',
                                data: reclamationsRecues,
                                borderColor: '#3996d3',
                                backgroundColor: 'rgba(57, 150, 211, 0.14)',
                                tension: 0.3,
                                spanGaps: true,
                                fill: false,
                                borderWidth: 3,
                                pointRadius: 3,
                                pointHoverRadius: 5,
                            },
                            {
                                label: 'Demandes clôturées',
                                data: demandesCloturees,
                                borderColor: '#f9b13c',
                                backgroundColor: 'rgba(249, 177, 60, 0.16)',
                                tension: 0.3,
                                spanGaps: true,
                                fill: false,
                                borderWidth: 3,
                                pointRadius: 3,
                                pointHoverRadius: 5,
                            }
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                display: false,
                            },
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false,
                                },
                                ticks: {
                                    color: '#495057',
                                },
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                    color: '#495057',
                                },
                                grid: {
                                    color: 'rgba(28, 32, 61, 0.08)',
                                },
                            },
                        },
                    }
                });

                return chartInstances.timeline;
            }

            function initDirectionVolumeChart(canvasId, chartData) {
                const canvas = document.getElementById(canvasId);

                if (!canvas || typeof Chart === 'undefined' || !chartData?.labels?.length) {
                    return null;
                }

                if (chartInstances.direction) {
                    return chartInstances.direction;
                }

                chartInstances.direction = new Chart(canvas, {
                    type: 'doughnut',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            label: chartData.dataset?.label || 'Demandes traitees',
                            data: chartData.dataset?.data || [],
                            backgroundColor: chartData.dataset?.backgroundColor || [],
                            borderColor: '#ffffff',
                            borderWidth: 3,
                            hoverOffset: 6,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '58%',
                        plugins: {
                            legend: {
                                display: false,
                            },
                            tooltip: {
                                callbacks: {
                                    label(context) {
                                        const seriesLabel = context.dataset?.label || '';
                                        const value = Number(context.parsed || 0);
                                        return `${seriesLabel} - ${context.label}: ${new Intl.NumberFormat('fr-FR').format(value)}`;
                                    }
                                }
                            }
                        }
                    }
                });

                return chartInstances.direction;
            }

            function slugifyChartTitle(value) {
                return (value || 'graphique')
                    .toString()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }

            function getActiveChartExportContext() {
                const panel = document.querySelector(`[data-chart-panel="${activePerformanceTarget}"]`);
                const canvas = panel?.querySelector('canvas') || null;
                const title = panel?.querySelector('h4')?.textContent?.trim() || 'Graphique de performance';

                if (!panel || !canvas) {
                    return null;
                }

                return { panel, canvas, title };
            }

            function downloadActiveChartAsPng() {
                const context = getActiveChartExportContext();

                if (!context) {
                    return;
                }

                const link = document.createElement('a');
                link.href = context.canvas.toDataURL('image/png', 1.0);
                link.download = `${slugifyChartTitle(context.title)}.png`;
                link.click();
            }

            function downloadActiveChartAsPdf() {
                const context = getActiveChartExportContext();

                if (!context || !window.jspdf?.jsPDF) {
                    return;
                }

                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF({
                    orientation: 'landscape',
                    unit: 'mm',
                    format: 'a4',
                });

                const pageWidth = pdf.internal.pageSize.getWidth();
                const pageHeight = pdf.internal.pageSize.getHeight();
                const margin = 12;
                const titleY = 16;
                const imageData = context.canvas.toDataURL('image/png', 1.0);
                const imageWidth = context.canvas.width || 1;
                const imageHeight = context.canvas.height || 1;
                const availableWidth = pageWidth - (margin * 2);
                const availableHeight = pageHeight - 30;
                const ratio = Math.min(availableWidth / imageWidth, availableHeight / imageHeight);
                const renderWidth = imageWidth * ratio;
                const renderHeight = imageHeight * ratio;
                const renderX = (pageWidth - renderWidth) / 2;
                const renderY = 22;

                pdf.setFontSize(13);
                pdf.text(context.title, margin, titleY);
                pdf.addImage(imageData, 'PNG', renderX, renderY, renderWidth, renderHeight);
                pdf.save(`${slugifyChartTitle(context.title)}.pdf`);
            }

            const pilotageExportBaseUrl = @json(url('/pilotage/export/__SECTION__/__FORMAT__'));

            function redirectToPilotageExport(section, format) {
                const url = new URL(
                    pilotageExportBaseUrl
                        .replace('__SECTION__', encodeURIComponent(section))
                        .replace('__FORMAT__', encodeURIComponent(format)),
                    window.location.origin
                );

                const currentParams = new URLSearchParams(window.location.search);
                currentParams.forEach((value, key) => {
                    if (value !== '') {
                        url.searchParams.set(key, value);
                    }
                });

                window.location.href = url.toString();
            }

            function exportCiqTrackingAsExcel() { redirectToPilotageExport('ciq-tracking', 'xlsx'); }
            async function exportCiqTrackingAsPdf() { redirectToPilotageExport('ciq-tracking', 'pdf'); }
            function exportAnnexe2AsExcel() { redirectToPilotageExport('annexe2', 'xlsx'); }
            async function exportAnnexe2AsPdf() { redirectToPilotageExport('annexe2', 'pdf'); }
            function exportFunctionDistributionAsExcel() { redirectToPilotageExport('function-distribution', 'xlsx'); }
            async function exportFunctionDistributionAsPdf() { redirectToPilotageExport('function-distribution', 'pdf'); }
            function exportReclamationServiceDistributionAsExcel() { redirectToPilotageExport('reclamation-service-distribution', 'xlsx'); }
            async function exportReclamationServiceDistributionAsPdf() { redirectToPilotageExport('reclamation-service-distribution', 'pdf'); }
            function setActivePerformanceChart(target) {
                performanceTabs.forEach((tab) => {
                    const active = tab.dataset.chartTarget === target;
                    tab.classList.toggle('bg-navy', active);
                    tab.classList.toggle('text-white', active);
                    tab.classList.toggle('border-navy', active);
                    tab.classList.toggle('bg-neutral-50', !active);
                    tab.classList.toggle('text-neutral-600', !active);
                    tab.classList.toggle('border-neutral-200', !active);
                });

                performancePanels.forEach((panel) => {
                    panel.classList.toggle('hidden', panel.dataset.chartPanel !== target);
                });

                activePerformanceTarget = target;

                const initializeChart = () => {
                    if (!initializedCharts[target]) {
                        if (target === 'type') {
                            initTypeVolumeChart();
                        }

                        if (target === 'status') {
                            initStatusVolumeChart();
                        }

                        if (target === 'timeline') {
                            initTemporalEvolutionChart();
                        }

                        if (target === 'direction') {
                            initDirectionVolumeChart('direction-compliance-chart', @json($directionVolumeChart));
                        }

                        initializedCharts[target] = true;
                    }

                    chartInstances[target]?.resize();
                };

                requestAnimationFrame(() => requestAnimationFrame(initializeChart));
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

            datePickerTriggers.forEach((trigger) => {
                trigger.addEventListener('click', openDatePicker);
            });

            performanceTabs.forEach((tab) => {
                tab.addEventListener('click', () => setActivePerformanceChart(tab.dataset.chartTarget));
            });

            trackingDetailButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const index = Number(button.dataset.trackingIndex ?? '-1');
                    if (index >= 0) {
                        openTrackingModal(index);
                    }
                });
            });

            if (ciqTrackingClose) {
                ciqTrackingClose.addEventListener('click', closeTrackingModal);
            }

            if (ciqTrackingBackdrop) {
                ciqTrackingBackdrop.addEventListener('click', closeTrackingModal);
            }

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && ciqTrackingModal && !ciqTrackingModal.classList.contains('hidden')) {
                    closeTrackingModal();
                }
            });

            if (downloadChartPngButton) {
                downloadChartPngButton.addEventListener('click', downloadActiveChartAsPng);
            }

            if (downloadChartPdfButton) {
                downloadChartPdfButton.addEventListener('click', downloadActiveChartAsPdf);
            }

            if (ciqTrackingExportXlsButton) {
                ciqTrackingExportXlsButton.addEventListener('click', exportCiqTrackingAsExcel);
            }

            if (ciqTrackingExportPdfButton) {
                ciqTrackingExportPdfButton.addEventListener('click', exportCiqTrackingAsPdf);
            }

            if (annexe2ExportXlsButton) {
                annexe2ExportXlsButton.addEventListener('click', exportAnnexe2AsExcel);
            }

            if (annexe2ExportPdfButton) {
                annexe2ExportPdfButton.addEventListener('click', exportAnnexe2AsPdf);
            }

            if (functionDistributionExportXlsButton) {
                functionDistributionExportXlsButton.addEventListener('click', exportFunctionDistributionAsExcel);
            }

            if (functionDistributionExportPdfButton) {
                functionDistributionExportPdfButton.addEventListener('click', exportFunctionDistributionAsPdf);
            }

            if (reclamationServiceDistributionExportXlsButton) {
                reclamationServiceDistributionExportXlsButton.addEventListener('click', exportReclamationServiceDistributionAsExcel);
            }

            if (reclamationServiceDistributionExportPdfButton) {
                reclamationServiceDistributionExportPdfButton.addEventListener('click', exportReclamationServiceDistributionAsPdf);
            }

            syncDateInputs();
            initCountUps();
            setActivePerformanceChart('type');
        })();
    </script>
</body>
</html>
