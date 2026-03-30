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

    if (in_array('ciq', $roleCodes, true)) {
        $pageTitle = 'Controle interne et qualite';
        $pageSubtitle = 'Statistiques de volume et d activite sur la periode selectionnee.';
    } elseif (in_array('dg', $roleCodes, true)) {
        $pageTitle = 'Direction generale';
        $pageSubtitle = 'Statistiques de volume et d activite sur la periode selectionnee.';
    } elseif (in_array('lecture_seule', $roleCodes, true)) {
        $pageTitle = 'Consultation en lecture seule';
        $pageSubtitle = 'Statistiques de volume et d activite sur la periode selectionnee.';
    }

    $formatPercent = function ($value) {
        return number_format((float) ($value ?? 0), 1, ',', ' ').' %';
    };

    $periodLabels = [
        'all' => 'Toute periode',
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
    $selectedTypeCode = (string) ($filters['type_code'] ?? '');
    $selectedStatusCode = (string) ($filters['statut_code'] ?? '');
    $selectedApplicationState = (string) ($filters['application_state'] ?? '');
    $dateFromValue = isset($filters['date_from']) && $filters['date_from'] ? substr((string) $filters['date_from'], 0, 10) : '';
    $dateToValue = isset($filters['date_to']) && $filters['date_to'] ? substr((string) $filters['date_to'], 0, 10) : '';

    $totalReclamations = (int) ($typeTotals['reclamation']['total'] ?? 0);
    $totalInformations = (int) ($typeTotals['demande_information']['total'] ?? 0);
    $typeGrandTotal = max(0, $totalReclamations + $totalInformations);

    $annexeRepartition = $overviewData['annexe_repartition'] ?? [];
    $annexeInformationsRows = collect($annexeRepartition['informations'] ?? []);
    $annexeReclamationsRows = collect($annexeRepartition['reclamations'] ?? []);
    $annexeInformationsTotal = (int) ($annexeRepartition['total_informations'] ?? 0);
    $annexeReclamationsTotal = (int) ($annexeRepartition['total_reclamations'] ?? 0);
    $functionDistribution = $overviewData['annexes_fonctions']['global'] ?? [];
    $functionDistributionRows = collect($functionDistribution['rows'] ?? []);
    $functionDistributionTotals = $functionDistribution['totaux'] ?? [];

    $typeChartEntries = collect([
        [
            'label' => 'Reclamations',
            'total' => $totalReclamations,
            'color' => '#3996d3',
        ],
        [
            'label' => 'Demandes d information',
            'total' => $totalInformations,
            'color' => '#8fc043',
        ],
    ])->filter(fn (array $entry) => $entry['total'] > 0)
      ->values()
      ->all();

    $statusChartEntries = [
        [
            'label' => 'Dans les delais',
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
    <title>{{ $pageTitle }} - ANBG</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy:    { DEFAULT: '#1c203d', 50: '#ecedf3', 100: '#c5c7d9', 600: '#181b35', 700: '#14162c' },
                        sky:     { DEFAULT: '#3996d3', 50: '#eaf4fb', 100: '#cae4f5', 600: '#2e7fb8' },
                        leaf:    { DEFAULT: '#8fc043', 50: '#f3f9ea' },
                        gold:    { DEFAULT: '#f9b13c', 50: '#fff8ee' },
                        neutral: { 50:'#f8f9fa',100:'#f1f3f5',200:'#e9ecef',300:'#dee2e6',400:'#adb5bd',500:'#6c757d',600:'#495057',700:'#343a40' },
                    },
                    fontFamily: { sans: ['"Roboto"', 'system-ui', 'sans-serif'] },
                    boxShadow: {
                        'card': '0 1px 3px rgba(28,32,61,0.05), 0 4px 16px rgba(28,32,61,0.07)',
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    <i class="fas fa-grid-2 text-[10px]"></i>
                    <span>Mon espace</span>
                </a>
                @if (in_array('admin', $roleCodes, true))
                <a href="/admin" class="hidden sm:inline-flex items-center gap-1.5 bg-white/10 hover:bg-white/20 border border-white/15 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition-all duration-150">
                    <i class="fas fa-gear text-[10px]"></i>
                    <span>Administration</span>
                </a>
                @endif
                <div class="hidden sm:flex items-center gap-2 bg-white/10 border border-white/15 rounded-lg px-3 py-1.5">
                    <div class="w-6 h-6 rounded-full bg-sky/30 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-user text-sky-100 text-[10px]"></i>
                    </div>
                    <span class="text-white text-xs font-medium">{{ $actorName !== '' ? $actorName : 'Utilisateur ANBG' }}</span>
                </div>
                <form method="post" action="/logout">
                    @csrf
                    <button type="submit" class="flex items-center gap-1.5 bg-white/10 hover:bg-red-500/20 border border-white/15 hover:border-red-400/40 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition-all duration-150">
                        <i class="fas fa-right-from-bracket text-[10px]"></i>
                        <span class="hidden sm:inline">Deconnexion</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <section class="border-b border-white/10 pb-8 pt-6" style="background: linear-gradient(135deg, #1c203d 0%, #22386b 54%, #3996d3 100%);">
        <div class="max-w-screen-xl mx-auto px-4 sm:px-6">
            <div class="flex items-start gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0 mt-1 shadow-lg" style="background: linear-gradient(135deg, rgba(143,192,67,0.9) 0%, rgba(248,233,50,0.95) 100%);">
                    <i class="fas fa-chart-line text-navy text-sm"></i>
                </div>
                <div>
                    <h1 class="text-white text-xl font-medium leading-snug mb-1">{{ $pageTitle }}</h1>
                    <p class="text-sky-100 text-sm font-light leading-relaxed max-w-3xl">{{ $pageSubtitle }}</p>
                </div>
            </div>
        </div>
    </section>

    <main class="max-w-screen-xl mx-auto px-4 sm:px-6 py-6 space-y-6">
        <section class="ciq-surface rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-sky/10">
                <h2 class="ciq-section-title text-sm font-medium text-navy">Filtres de supervision</h2>
                <p class="text-xs text-neutral-400 mt-1">Choisissez une periode predefinie ou definissez une plage via le calendrier.</p>
            </div>
            <form method="get" class="p-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 items-end">
                <div>
                    <label for="periode" class="block text-xs text-neutral-500 mb-1">Periode</label>
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
                            <i class="fas fa-calendar-days text-sm"></i>
                        </button>
                    </div>
                </div>
                <div>
                    <label for="date_to" class="block text-xs text-neutral-500 mb-1">Date fin</label>
                    <div class="relative">
                        <input id="date_to" type="date" name="date_to" value="{{ $dateToValue }}" class="field w-full px-3 py-2.5 pr-11 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy">
                        <button type="button" class="date-picker-trigger absolute inset-y-0 right-0 px-3 text-neutral-400 hover:text-navy transition-colors" data-target="date_to" aria-label="Choisir la date de fin">
                            <i class="fas fa-calendar-days text-sm"></i>
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
                    <label for="type_code" class="block text-xs text-neutral-500 mb-1">Type</label>
                    <select id="type_code" name="type_code" class="field w-full px-3 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy">
                        <option value="">Tous les types</option>
                        @foreach(($catalogues['types'] ?? []) as $type)
                        <option value="{{ $type['code'] }}" @selected($selectedTypeCode === (string) $type['code'])>{{ $type['libelle'] }}</option>
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
                        <i class="fas fa-filter text-xs"></i> Appliquer
                    </button>
                    <a href="/pilotage" class="inline-flex items-center gap-2 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 text-sm font-medium px-5 py-2.5 rounded-xl transition-colors duration-150">
                        <i class="fas fa-rotate-left text-xs"></i> Reinitialiser
                    </a>
                </div>
                <div class="md:col-span-2 xl:col-span-4">
                    <p class="text-xs text-neutral-400">Si vous choisissez une date de debut ou de fin, la periode passe automatiquement en mode personnalise. Si vous revenez sur `Aujourd hui`, `Semaine`, `Mois` ou `Annee`, la plage manuelle est effacee.</p>
                </div>
            </form>
        </section>

        <section class="space-y-5">
            <div>
                <h2 class="ciq-section-title text-sm font-medium text-navy">Volume & activite</h2>
                <p class="text-xs text-neutral-400 mt-1">Vue d ensemble du volume recu, du traitement et de l etat global sur la periode selectionnee.</p>
            </div>

            <div class="flex gap-4 overflow-x-auto pb-2">
                <div class="ciq-kpi-card ciq-kpi-card--navy">
                    <p class="ciq-kpi-label">Total mails recus</p>
                    <p class="ciq-kpi-value" data-countup="{{ (int) ($kpis['total_demandes'] ?? 0) }}">{{ number_format((int) ($kpis['total_demandes'] ?? 0), 0, ',', ' ') }}</p>
                    <p class="ciq-kpi-meta">Toutes demandes confondues</p>
                </div>
                <div class="ciq-kpi-card ciq-kpi-card--sky">
                    <p class="ciq-kpi-label">Reclamations recues</p>
                    <p class="ciq-kpi-value" data-countup="{{ $totalReclamations }}">{{ number_format($totalReclamations, 0, ',', ' ') }}</p>
                    <p class="ciq-kpi-meta">Volume filtre courant</p>
                </div>
                <div class="ciq-kpi-card ciq-kpi-card--gradient">
                    <p class="ciq-kpi-label">Demandes d information</p>
                    <p class="ciq-kpi-value" data-countup="{{ $totalInformations }}">{{ number_format($totalInformations, 0, ',', ' ') }}</p>
                    <p class="ciq-kpi-meta">Volume filtre courant</p>
                </div>
                <div class="ciq-kpi-card ciq-kpi-card--mist">
                    <p class="ciq-kpi-label">Cloturees</p>
                    <p class="ciq-kpi-value" data-countup="{{ (int) ($kpis['total_traitees'] ?? 0) }}">{{ number_format((int) ($kpis['total_traitees'] ?? 0), 0, ',', ' ') }}</p>
                    <p class="ciq-kpi-meta">Demandes cloturees</p>
                </div>
                <div class="ciq-kpi-card ciq-kpi-card--mist">
                    <p class="ciq-kpi-label">En attente</p>
                    <p class="ciq-kpi-value" data-countup="{{ (int) ($kpis['total_ouvertes'] ?? 0) }}">{{ number_format((int) ($kpis['total_ouvertes'] ?? 0), 0, ',', ' ') }}</p>
                    <p class="ciq-kpi-meta">Demandes non cloturees</p>
                </div>
                <div class="ciq-kpi-card ciq-kpi-card--alert">
                    <p class="ciq-kpi-label">En retard</p>
                    <p class="ciq-kpi-value" data-countup="{{ (int) ($kpis['global_en_retard'] ?? 0) }}">{{ number_format((int) ($kpis['global_en_retard'] ?? 0), 0, ',', ' ') }}</p>
                    <p class="ciq-kpi-meta">Depassement du delai global</p>
                </div>
                <div class="ciq-kpi-card ciq-kpi-card--gradient">
                    <p class="ciq-kpi-label">Taux dans les delais</p>
                    <p class="ciq-kpi-value" data-countup="{{ (float) ($kpis['taux_traitement_dans_delais'] ?? 0) }}" data-decimals="1" data-suffix="%">{{ $formatPercent($kpis['taux_traitement_dans_delais'] ?? 0) }}</p>
                    <p class="ciq-kpi-meta">Sur les mails traites</p>
                </div>
                <div class="ciq-kpi-card ciq-kpi-card--warm">
                    <p class="ciq-kpi-label">Delai moyen traitement</p>
                    <p class="ciq-kpi-value">
                        @if(($kpis['delai_moyen_traitement_heures'] ?? null) !== null)
                            <span data-countup="{{ (float) $kpis['delai_moyen_traitement_heures'] }}" data-decimals="2" data-suffix=" h">{{ number_format((float) $kpis['delai_moyen_traitement_heures'], 2, ',', ' ') }} h</span>
                        @else
                            -
                        @endif
                    </p>
                    <p class="ciq-kpi-meta">Moyenne en heures ouvrees sur les mails clotures</p>
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
                        <div class="flex flex-wrap gap-2">
                            <button type="button" id="download-chart-png" class="ciq-export-button inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-medium text-neutral-600 transition-colors">
                                <i class="fas fa-image text-xs"></i>
                                <span>Telecharger PNG</span>
                            </button>
                            <button type="button" id="download-chart-pdf" class="ciq-export-button inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-medium text-neutral-600 transition-colors">
                                <i class="fas fa-file-pdf text-xs"></i>
                                <span>Telecharger PDF</span>
                            </button>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs text-neutral-400">Les boutons ci-dessous affichent un seul graphique a la fois.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" data-chart-target="type" class="performance-tab inline-flex items-center gap-2 rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-2 text-sm font-medium text-neutral-600 transition-colors">
                            <i class="fas fa-chart-pie text-xs"></i>
                            <span>Types</span>
                        </button>
                        <button type="button" data-chart-target="status" class="performance-tab inline-flex items-center gap-2 rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-2 text-sm font-medium text-neutral-600 transition-colors">
                            <i class="fas fa-chart-column text-xs"></i>
                            <span>Statut global</span>
                        </button>
                        <button type="button" data-chart-target="timeline" class="performance-tab inline-flex items-center gap-2 rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-2 text-sm font-medium text-neutral-600 transition-colors">
                            <i class="fas fa-chart-line text-xs"></i>
                            <span>Evolution</span>
                        </button>
                        <button type="button" data-chart-target="direction" class="performance-tab inline-flex items-center gap-2 rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-2 text-sm font-medium text-neutral-600 transition-colors">
                            <i class="fas fa-building text-xs"></i>
                            <span>Directions</span>
                        </button>
                    </div>
                </div>

                <div class="p-5">
                    <section data-chart-panel="type" class="performance-panel hidden space-y-4">
                        <div>
                            <h4 class="text-sm font-medium text-navy">Reclamations / demandes d information</h4>
                            <p class="text-xs text-neutral-400 mt-1">Repartition du volume par type avec pourcentage.</p>
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
                                    <p class="mt-2 text-xs text-neutral-400">{{ $formatPercent($entryPercent) }} du volume total filtre</p>
                                </div>
                                @empty
                                <div class="rounded-xl border border-dashed border-sky/20 bg-white/70 px-4 py-8 text-center text-sm text-neutral-400">
                                    Aucune donnee disponible pour cette repartition.
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </section>

                    <section data-chart-panel="status" class="performance-panel hidden space-y-4">
                        <div>
                            <h4 class="text-sm font-medium text-navy">Traitement global des demandes</h4>
                            <p class="text-xs text-neutral-400 mt-1">Comparaison des demandes dans les delais, a risque et en retard.</p>
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
                            <p class="text-xs text-neutral-400 mt-1">Suivi compare des reclamations recues, des demandes d information recues et des demandes cloturees par {{ $temporalGranularityLabel }}.</p>
                        </div>
                        <div class="space-y-4">
                            <div class="h-80">
                                <canvas id="temporal-evolution-chart"></canvas>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div class="ciq-inner-card rounded-xl p-4">
                                    <div class="flex items-center gap-2">
                                        <span class="w-3 h-3 rounded-full bg-sky"></span>
                                        <p class="text-sm font-medium text-navy">Reclamations recues</p>
                                    </div>
                                    <p class="mt-3 text-2xl font-semibold text-navy">{{ number_format(array_sum($temporalSeries['reclamations_recues'] ?? []), 0, ',', ' ') }}</p>
                                </div>
                                <div class="ciq-inner-card rounded-xl p-4">
                                    <div class="flex items-center gap-2">
                                        <span class="w-3 h-3 rounded-full bg-leaf"></span>
                                        <p class="text-sm font-medium text-navy">Demandes d information</p>
                                    </div>
                                    <p class="mt-3 text-2xl font-semibold text-navy">{{ number_format(array_sum($temporalSeries['informations_recues'] ?? []), 0, ',', ' ') }}</p>
                                </div>
                                <div class="ciq-inner-card rounded-xl p-4">
                                    <div class="flex items-center gap-2">
                                        <span class="w-3 h-3 rounded-full bg-gold"></span>
                                        <p class="text-sm font-medium text-navy">Demandes cloturees</p>
                                    </div>
                                    <p class="mt-3 text-2xl font-semibold text-navy">{{ number_format(array_sum($temporalSeries['demandes_cloturees'] ?? []), 0, ',', ' ') }}</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section data-chart-panel="direction" class="performance-panel hidden space-y-4">
                        <div>
                            <h4 class="text-sm font-medium text-navy">Directions qui traitent le plus de demandes</h4>
                            <p class="text-xs text-neutral-400 mt-1">Le donut montre la part de demandes cloturees traitee par chaque direction.</p>
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
                                        <span>Traitees : {{ number_format((int) $entry['appliquees'], 0, ',', ' ') }}</span>
                                        <span>Dans les delais : {{ number_format((int) $entry['dans_delais'], 0, ',', ' ') }}</span>
                                        <span>Conformite : {{ $formatPercent($entry['taux_dans_delais']) }}</span>
                                    </div>
                                </div>
                                @empty
                                <div class="rounded-xl border border-dashed border-sky/20 bg-white/70 px-4 py-8 text-center text-sm text-neutral-400">
                                    Aucune demande traitee disponible sur cette periode.
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </section>
                </div>
            </section>
        </section>

        @if (in_array('ciq', $roleCodes, true))
        <section class="space-y-5">
            <div>
                <h2 class="ciq-section-title text-sm font-medium text-navy">Tableau actuel du suivi des reclamations</h2>
                <p class="text-xs text-neutral-400 mt-1">Presentation alignee sur le modele bureautique CIQ : reception, dispatch, service, realisation et respect des delais.</p>
            </div>

            <section class="ciq-surface rounded-2xl overflow-hidden">
                <div class="px-5 py-4 border-b border-sky/10 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-table text-sky text-sm"></i>
                        <h3 class="text-sm font-medium text-navy">Suivi detaille</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <p class="text-xs text-neutral-400 hidden sm:block">{{ number_format($ciqTrackingRows->count(), 0, ',', ' ') }} ligne(s) sur le filtre courant</p>
                        <button type="button" id="ciq-tracking-export-xls" class="ciq-export-button inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-neutral-600 transition-colors">
                            <i class="fas fa-file-excel text-[11px]"></i>
                            <span>Excel</span>
                        </button>
                        <button type="button" id="ciq-tracking-export-pdf" class="ciq-export-button inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-neutral-600 transition-colors">
                            <i class="fas fa-file-pdf text-[11px]"></i>
                            <span>PDF</span>
                        </button>
                    </div>
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
                                <th class="px-3 py-3 text-left text-[11px] font-medium text-white uppercase tracking-wider">Delais de transmission</th>
                                <th class="px-3 py-3 text-left text-[11px] font-medium text-white uppercase tracking-wider">Service</th>
                                <th class="px-3 py-3 text-left text-[11px] font-medium text-white uppercase tracking-wider">Realisation</th>
                                <th class="px-3 py-3 text-left text-[11px] font-medium text-white uppercase tracking-wider">Statut</th>
                                <th class="px-3 py-3 text-left text-[11px] font-medium text-white uppercase tracking-wider">Respect delais</th>
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
                                <td colspan="12" class="px-4 py-10 text-center text-sm text-neutral-400">Aucune donnee disponible pour ce tableau sur la periode selectionnee.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </section>

        <section class="space-y-5">
            <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <h2 class="text-sm font-medium text-navy"> tableau de la répartition des demandes d’informations et réclamations les plus récurrentes dans la cellule. </h2>
                    <p class="text-xs text-neutral-400 mt-1">Nombre de mails par categorie avec pourcentage sur le total du type.</p>
                </div>
                <div class="flex items-center gap-2">
                    <p class="hidden sm:block text-xs text-neutral-400">{{ number_format(($annexeInformationsRows->count() + $annexeReclamationsRows->count()), 0, ',', ' ') }} ligne(s) au total</p>
                    <button type="button" id="annexe2-export-xls" class="ciq-export-button inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-neutral-600 transition-colors">
                        <i class="fas fa-file-excel text-[11px]"></i>
                        <span>Excel</span>
                    </button>
                    <button type="button" id="annexe2-export-pdf" class="ciq-export-button inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-neutral-600 transition-colors">
                        <i class="fas fa-file-pdf text-[11px]"></i>
                        <span>PDF</span>
                    </button>
                </div>
            </div>

            <section id="annexe2-export-area" class="space-y-5 ciq-surface rounded-2xl p-4 sm:p-5">
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 items-start">
                    <section class="bg-white rounded-2xl border border-neutral-100 overflow-hidden">
                        
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[760px] border-collapse">
                                <thead>
                                    <tr class="bg-[#e8d1cb]">
                                        <th class="px-3 py-2 text-left text-sm font-semibold text-navy border border-neutral-300">Demande d'informations sur eBourse, les bourses &amp; accessoires de bourse</th>
                                        <th class="px-3 py-2 text-center text-sm font-semibold text-navy border border-neutral-300 w-[150px]">Nombre de mails</th>
                                        <th class="px-3 py-2 text-center text-sm font-semibold text-navy border border-neutral-300 w-[90px]">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($annexeInformationsRows as $row)
                                    <tr class="odd:bg-white even:bg-neutral-50">
                                        <td class="px-3 py-1.5 text-sm text-neutral-700 border border-neutral-300">{{ $row['categorie'] ?? '-' }}</td>
                                        <td class="px-3 py-1.5 text-sm text-red-600 font-semibold text-center border border-neutral-300">{{ number_format((int) ($row['nombre_mails'] ?? 0), 0, ',', ' ') }}</td>
                                        <td class="px-3 py-1.5 text-sm text-neutral-700 text-center border border-neutral-300">{{ $formatPercent($row['pourcentage'] ?? 0) }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-8 text-center text-sm text-neutral-400 border border-neutral-300">Aucune donnee disponible pour les demandes d informations.</td>
                                    </tr>
                                    @endforelse
                                    <tr class="bg-[#f8e7d8]">
                                        <td class="px-3 py-2 text-sm font-semibold text-center text-navy border border-neutral-300 uppercase">Total demandes d'informations</td>
                                        <td class="px-3 py-2 text-sm font-semibold text-center text-red-600 border border-neutral-300">{{ number_format($annexeInformationsTotal, 0, ',', ' ') }}</td>
                                        <td class="px-3 py-2 text-sm font-semibold text-center text-navy border border-neutral-300">&nbsp;</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="bg-white rounded-2xl border border-neutral-100 overflow-hidden">
                        
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[760px] border-collapse">
                                <thead>
                                    <tr class="bg-[#d0deaa]">
                                        <th class="px-3 py-2 text-left text-sm font-semibold text-navy border border-neutral-300">Reclamation</th>
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
                                        <td colspan="3" class="px-4 py-8 text-center text-sm text-neutral-400 border border-neutral-300">Aucune donnee disponible pour les reclamations.</td>
                                    </tr>
                                    @endforelse
                                    <tr class="bg-[#fff500]">
                                        <td class="px-3 py-2 text-sm font-semibold text-center text-navy border border-neutral-300 uppercase">Total reclamations</td>
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
                    <h2 class="ciq-section-title text-sm font-medium text-navy">tableau de repartition de l ensemble des reclamations de la cellule par direction</h2>
                    <p class="text-xs text-neutral-400 mt-1">Toutes les demandes appliquees et non appliquees par fonction, avec execution, conformite et score moyen.</p>
                </div>
                <div class="flex items-center gap-2">
                    <p class="hidden sm:block text-xs text-neutral-400">{{ number_format($functionDistributionRows->count(), 0, ',', ' ') }} ligne(s) au total</p>
                    <button type="button" id="function-distribution-export-xls" class="ciq-export-button inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-neutral-600 transition-colors">
                        <i class="fas fa-file-excel text-[11px]"></i>
                        <span>Excel</span>
                    </button>
                    <button type="button" id="function-distribution-export-pdf" class="ciq-export-button inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-neutral-600 transition-colors">
                        <i class="fas fa-file-pdf text-[11px]"></i>
                        <span>PDF</span>
                    </button>
                </div>
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
                                <th class="px-3 py-3 text-center text-sm font-semibold text-navy border border-neutral-300">Nombre de reclamations et demandes d'informations total</th>
                                <th class="px-3 py-3 text-center text-sm font-semibold text-navy border border-neutral-300">Nombre de reclamations et demandes d'informations traite</th>
                                <th class="px-3 py-3 text-center text-sm font-semibold text-emerald-600 border border-neutral-300">Taux d'execution (%)</th>
                                <th class="px-3 py-3 text-center text-sm font-semibold text-navy border border-neutral-300">Nombre de reclamations et demandes d'informations traitees dans les delais</th>
                                <th class="px-3 py-3 text-center text-sm font-semibold text-emerald-600 border border-neutral-300">Taux de conformite (72h)(%)</th>
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
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-neutral-400 border border-neutral-300">Aucune donnee disponible pour cette repartition sur la periode selectionnee.</td>
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
                                label: 'Reclamations recues',
                                data: series.reclamations_recues || [],
                                borderColor: '#3996d3',
                                backgroundColor: 'rgba(57, 150, 211, 0.14)',
                                tension: 0.3,
                                fill: false,
                                borderWidth: 3,
                                pointRadius: 3,
                                pointHoverRadius: 5,
                            },
                            {
                                label: 'Demandes d information',
                                data: series.informations_recues || [],
                                borderColor: '#8fc043',
                                backgroundColor: 'rgba(143, 192, 67, 0.14)',
                                tension: 0.3,
                                fill: false,
                                borderWidth: 3,
                                pointRadius: 3,
                                pointHoverRadius: 5,
                            },
                            {
                                label: 'Demandes cloturees',
                                data: series.demandes_cloturees || [],
                                borderColor: '#f9b13c',
                                backgroundColor: 'rgba(249, 177, 60, 0.16)',
                                tension: 0.3,
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
                                position: 'bottom',
                                labels: {
                                    usePointStyle: true,
                                    boxWidth: 10,
                                    color: '#495057',
                                },
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

            function exportCiqTrackingAsExcel() {
                const table = document.getElementById('ciq-tracking-table');

                if (!table) {
                    return;
                }

                const workbookHtml = `
                    <html xmlns:o="urn:schemas-microsoft-com:office:office"
                          xmlns:x="urn:schemas-microsoft-com:office:excel"
                          xmlns="http://www.w3.org/TR/REC-html40">
                    <head>
                        <meta charset="utf-8">
                        <style>
                            table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }
                            th, td { border: 1px solid #b7c3d0; padding: 6px; font-size: 12px; vertical-align: top; }
                            th { background: #1f4e79; color: #ffffff; font-weight: bold; text-transform: uppercase; }
                        </style>
                    </head>
                    <body>${table.outerHTML}</body>
                    </html>
                `;

                const blob = new Blob([workbookHtml], { type: 'application/vnd.ms-excel' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = 'tableau-suivi-ciq.xls';
                link.click();
                URL.revokeObjectURL(url);
            }

            async function exportCiqTrackingAsPdf() {
                const area = document.getElementById('ciq-tracking-export-area');
                const table = document.getElementById('ciq-tracking-table');

                if (!area || !table || typeof html2canvas === 'undefined' || !window.jspdf?.jsPDF) {
                    return;
                }

                const cloneWrapper = document.createElement('div');
                cloneWrapper.style.position = 'fixed';
                cloneWrapper.style.left = '-100000px';
                cloneWrapper.style.top = '0';
                cloneWrapper.style.width = `${table.scrollWidth}px`;
                cloneWrapper.style.background = '#ffffff';
                cloneWrapper.style.padding = '0';
                cloneWrapper.style.overflow = 'visible';
                cloneWrapper.style.zIndex = '-1';

                const cloneTable = table.cloneNode(true);
                cloneTable.style.width = `${table.scrollWidth}px`;
                cloneTable.style.minWidth = `${table.scrollWidth}px`;
                cloneTable.style.maxWidth = 'none';
                cloneTable.style.tableLayout = 'fixed';
                cloneTable.style.overflow = 'visible';

                cloneWrapper.appendChild(cloneTable);
                document.body.appendChild(cloneWrapper);

                let canvas;
                try {
                    canvas = await html2canvas(cloneTable, {
                        backgroundColor: '#ffffff',
                        scale: 2,
                        useCORS: true,
                        windowWidth: table.scrollWidth,
                        width: table.scrollWidth,
                        scrollX: 0,
                        scrollY: -window.scrollY,
                    });
                } finally {
                    document.body.removeChild(cloneWrapper);
                }

                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF({
                    orientation: 'landscape',
                    unit: 'mm',
                    format: 'a4',
                });

                const pageWidth = pdf.internal.pageSize.getWidth();
                const pageHeight = pdf.internal.pageSize.getHeight();
                const margin = 6;
                const imageData = canvas.toDataURL('image/png', 1.0);
                const ratio = Math.min(
                    (pageWidth - (margin * 2)) / canvas.width,
                    (pageHeight - 18) / canvas.height
                );
                const renderWidth = canvas.width * ratio;
                const renderHeight = canvas.height * ratio;
                const renderX = (pageWidth - renderWidth) / 2;

                pdf.setFontSize(12);
                pdf.text('Tableau actuel du suivi des reclamations', margin, 10);
                pdf.addImage(imageData, 'PNG', renderX, 14, renderWidth, renderHeight);
                pdf.save('tableau-suivi-ciq.pdf');
            }

            function exportAnnexe2AsExcel() {
                const area = document.getElementById('annexe2-export-area');

                if (!area) {
                    return;
                }

                const exportTitle = "Tableau de la repartition des demandes d'informations et reclamations les plus recurrentes dans la cellule.";
                const clone = area.cloneNode(true);
                const heading = clone.querySelector('h2, h3');
                if (heading) {
                    heading.textContent = exportTitle;
                }

                const workbookHtml = `
                    <html xmlns:o="urn:schemas-microsoft-com:office:office"
                          xmlns:x="urn:schemas-microsoft-com:office:excel"
                          xmlns="http://www.w3.org/TR/REC-html40">
                    <head>
                        <meta charset="utf-8">
                        <style>
                            body { font-family: Arial, sans-serif; }
                            h2, h3 { margin: 0 0 8px 0; }
                            .sheet { margin-bottom: 18px; }
                            table { border-collapse: collapse; width: 100%; }
                            th, td { border: 1px solid #aab7c4; padding: 6px; font-size: 12px; vertical-align: top; }
                            thead th { background: #d9e2f3; color: #1f2937; font-weight: bold; }
                        </style>
                    </head>
                    <body>
                        <div style="font-family: Arial, sans-serif; font-size: 14px; font-weight: bold; margin: 0 0 12px 0;">
                            ${exportTitle}
                        </div>
                        ${clone.outerHTML}
                    </body>
                    </html>
                `;

                const blob = new Blob([workbookHtml], { type: 'application/vnd.ms-excel' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = 'annexe-2-repartition-ciq.xls';
                link.click();
                URL.revokeObjectURL(url);
            }

            async function exportAnnexe2AsPdf() {
                const area = document.getElementById('annexe2-export-area');

                if (!area || typeof html2canvas === 'undefined' || !window.jspdf?.jsPDF) {
                    return;
                }

                const exportTitle = "Tableau de la repartition des demandes d'informations et reclamations les plus recurrentes dans la cellule.";

                const cloneWrapper = document.createElement('div');
                cloneWrapper.style.position = 'fixed';
                cloneWrapper.style.left = '-100000px';
                cloneWrapper.style.top = '0';
                cloneWrapper.style.width = '1600px';
                cloneWrapper.style.background = '#ffffff';
                cloneWrapper.style.padding = '0';
                cloneWrapper.style.overflow = 'visible';
                cloneWrapper.style.zIndex = '-1';

                const cloneArea = area.cloneNode(true);
                cloneArea.style.width = '1600px';
                cloneArea.style.maxWidth = 'none';

                const heading = cloneArea.querySelector('h2, h3');
                if (heading) {
                    heading.textContent = exportTitle;
                }

                const layout = cloneArea.querySelector('.grid');
                if (layout) {
                    layout.style.display = 'block';
                }

                cloneArea.querySelectorAll('section').forEach((section) => {
                    section.style.width = '100%';
                    section.style.maxWidth = 'none';
                    section.style.marginBottom = '18px';
                });

                cloneArea.querySelectorAll('table').forEach((table) => {
                    table.style.width = '100%';
                    table.style.minWidth = '0';
                    table.style.tableLayout = 'fixed';
                });

                cloneWrapper.appendChild(cloneArea);
                document.body.appendChild(cloneWrapper);

                let canvas;
                try {
                    canvas = await html2canvas(cloneArea, {
                        backgroundColor: '#ffffff',
                        scale: 2,
                        useCORS: true,
                        windowWidth: 1600,
                        width: 1600,
                        scrollX: 0,
                        scrollY: -window.scrollY,
                    });
                } finally {
                    document.body.removeChild(cloneWrapper);
                }

                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF({
                    orientation: 'landscape',
                    unit: 'mm',
                    format: 'a4',
                });

                const pageWidth = pdf.internal.pageSize.getWidth();
                const pageHeight = pdf.internal.pageSize.getHeight();
                const margin = 6;
                const availableWidth = pageWidth - (margin * 2);
                const titleHeight = 12;
                const availableHeight = pageHeight - (margin * 2) - titleHeight - 4;
                const ratio = availableWidth / canvas.width;
                const sliceHeight = Math.max(1, Math.floor(availableHeight / ratio));

                let offsetY = 0;
                let pageIndex = 0;

                while (offsetY < canvas.height) {
                    const sliceCanvas = document.createElement('canvas');
                    const sliceHeightCurrent = Math.min(sliceHeight, canvas.height - offsetY);
                    sliceCanvas.width = canvas.width;
                    sliceCanvas.height = sliceHeightCurrent;

                    const sliceContext = sliceCanvas.getContext('2d');
                    sliceContext.drawImage(
                        canvas,
                        0,
                        offsetY,
                        canvas.width,
                        sliceHeightCurrent,
                        0,
                        0,
                        canvas.width,
                        sliceHeightCurrent
                    );

                    if (pageIndex > 0) {
                        pdf.addPage();
                    }

                    const imageData = sliceCanvas.toDataURL('image/png', 1.0);
                    const renderHeight = sliceHeightCurrent * ratio;

                    pdf.setFontSize(12);
                    pdf.text(exportTitle, margin, 10);
                    pdf.addImage(imageData, 'PNG', margin, 14, availableWidth, renderHeight);

                    offsetY += sliceHeightCurrent;
                    pageIndex += 1;
                }

                pdf.save(`${exportTitle}.pdf`);
            }

            function exportFunctionDistributionAsExcel() {
                const area = document.getElementById('function-distribution-export-area');

                if (!area) {
                    return;
                }

                const exportTitle = "Tableau de repartition de l'ensemble des reclamations de la cellule par direction.";
                const clone = area.cloneNode(true);

                const workbookHtml = `
                    <html xmlns:o="urn:schemas-microsoft-com:office:office"
                          xmlns:x="urn:schemas-microsoft-com:office:excel"
                          xmlns="http://www.w3.org/TR/REC-html40">
                    <head>
                        <meta charset="utf-8">
                        <style>
                            body { font-family: Arial, sans-serif; }
                            table { border-collapse: collapse; width: 100%; }
                            th, td { border: 1px solid #aab7c4; padding: 6px; font-size: 12px; vertical-align: middle; text-align: center; }
                            thead th { background: #f3f4f6; color: #1f2937; font-weight: bold; }
                        </style>
                    </head>
                    <body>
                        <div style="font-family: Arial, sans-serif; font-size: 14px; font-weight: bold; margin: 0 0 12px 0;">
                            ${exportTitle}
                        </div>
                        ${clone.outerHTML}
                    </body>
                    </html>
                `;

                const blob = new Blob([workbookHtml], { type: 'application/vnd.ms-excel' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = 'tableau-repartition-reclamations-par-direction.xls';
                link.click();
                URL.revokeObjectURL(url);
            }

            async function exportFunctionDistributionAsPdf() {
                const area = document.getElementById('function-distribution-export-area');

                if (!area || typeof html2canvas === 'undefined' || !window.jspdf?.jsPDF) {
                    return;
                }

                const exportTitle = "Tableau de repartition de l'ensemble des reclamations de la cellule par direction.";
                const cloneWrapper = document.createElement('div');
                cloneWrapper.style.position = 'fixed';
                cloneWrapper.style.left = '-100000px';
                cloneWrapper.style.top = '0';
                cloneWrapper.style.width = '1600px';
                cloneWrapper.style.background = '#ffffff';
                cloneWrapper.style.padding = '0';
                cloneWrapper.style.overflow = 'visible';
                cloneWrapper.style.zIndex = '-1';

                const cloneArea = area.cloneNode(true);
                cloneArea.style.width = '1600px';
                cloneArea.style.maxWidth = 'none';

                cloneArea.querySelectorAll('table').forEach((table) => {
                    table.style.width = '100%';
                    table.style.minWidth = '0';
                    table.style.tableLayout = 'fixed';
                });

                cloneWrapper.appendChild(cloneArea);
                document.body.appendChild(cloneWrapper);

                let canvas;
                try {
                    canvas = await html2canvas(cloneArea, {
                        backgroundColor: '#ffffff',
                        scale: 2,
                        useCORS: true,
                        windowWidth: 1600,
                        width: 1600,
                        scrollX: 0,
                        scrollY: -window.scrollY,
                    });
                } finally {
                    document.body.removeChild(cloneWrapper);
                }

                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF({
                    orientation: 'landscape',
                    unit: 'mm',
                    format: 'a4',
                });

                const pageWidth = pdf.internal.pageSize.getWidth();
                const pageHeight = pdf.internal.pageSize.getHeight();
                const margin = 6;
                const availableWidth = pageWidth - (margin * 2);
                const titleHeight = 12;
                const availableHeight = pageHeight - (margin * 2) - titleHeight - 4;
                const ratio = availableWidth / canvas.width;
                const sliceHeight = Math.max(1, Math.floor(availableHeight / ratio));

                let offsetY = 0;
                let pageIndex = 0;

                while (offsetY < canvas.height) {
                    const sliceCanvas = document.createElement('canvas');
                    const sliceHeightCurrent = Math.min(sliceHeight, canvas.height - offsetY);
                    sliceCanvas.width = canvas.width;
                    sliceCanvas.height = sliceHeightCurrent;

                    const sliceContext = sliceCanvas.getContext('2d');
                    sliceContext.drawImage(
                        canvas,
                        0,
                        offsetY,
                        canvas.width,
                        sliceHeightCurrent,
                        0,
                        0,
                        canvas.width,
                        sliceHeightCurrent
                    );

                    if (pageIndex > 0) {
                        pdf.addPage();
                    }

                    const imageData = sliceCanvas.toDataURL('image/png', 1.0);
                    const renderHeight = sliceHeightCurrent * ratio;

                    pdf.setFontSize(12);
                    pdf.text(exportTitle, margin, 10);
                    pdf.addImage(imageData, 'PNG', margin, 14, availableWidth, renderHeight);

                    offsetY += sliceHeightCurrent;
                    pageIndex += 1;
                }

                pdf.save(`${exportTitle}.pdf`);
            }

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

                if (initializedCharts[target]) {
                    return;
                }

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

            syncDateInputs();
            initCountUps();
            setActivePerformanceChart('type');
        })();
    </script>
</body>
</html>
