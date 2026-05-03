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
    $pageEyebrow = 'Vue de pilotage';
    $dashboardLabel = 'Dashboard';
    $scopeChipLabel = 'Lecture de supervision';
    $reportingChipLabel = 'Reporting global';
    $sectionPresentation = 'Presentation alignee sur le modele bureautique de pilotage : reception, affectation, traitement et respect des delais.';
    $canViewScopedCiqTables = in_array('ciq', $roleCodes, true)
        || in_array('chef_direction', $roleCodes, true)
        || in_array('chef_service', $roleCodes, true)
        || in_array('lecture_seule', $roleCodes, true);
    $canExportPilotage = (bool) ($canExportPilotage ?? false);

    if (in_array('ciq', $roleCodes, true)) {
        $pageTitle = 'Contrôle interne et qualité';
        $pageSubtitle = "Statistiques de volume et d'activité sur la période sélectionnée.";
    } elseif (in_array('chef_direction', $roleCodes, true)) {
        $pageTitle = 'Pilotage de direction';
        $pageSubtitle = 'Tableaux de supervision limités aux services de votre direction.';
    } elseif (in_array('chef_service', $roleCodes, true)) {
        $pageTitle = 'Pilotage de service';
        $pageSubtitle = 'Tableaux de supervision limités à votre service.';
    } elseif (in_array('dg', $roleCodes, true)) {
        $pageTitle = 'Direction générale';
        $pageSubtitle = "Statistiques de volume et d'activité sur la période sélectionnée.";
    } elseif (in_array('lecture_seule', $roleCodes, true)) {
        $pageTitle = 'Consultation en lecture seule';
        $pageSubtitle = "Statistiques de volume et d'activité sur la période sélectionnée.";
    }

    $formatPercent = function ($value) {
        return number_format((float) ($value ?? 0), 1, ',', ' ').' %';
    };

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
    $selectedApplicationState = (string) ($filters['application_state'] ?? '');
    $dateFromValue = isset($filters['date_from']) && $filters['date_from'] ? substr((string) $filters['date_from'], 0, 10) : '';
    $dateToValue = isset($filters['date_to']) && $filters['date_to'] ? substr((string) $filters['date_to'], 0, 10) : '';
    $directionOptions = collect($catalogues['directions'] ?? [])->values();
    $serviceOptions = collect($catalogues['services'] ?? [])->values();
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

    if (in_array('ciq', $roleCodes, true)) {
        $pageEyebrow = 'Controle interne et qualite';
        $reportingChipLabel = 'Reporting CIQ';
        $sectionPresentation = 'Presentation alignee sur le modele CIQ : reception, affectation, service, realisation et respect des delais.';
    } elseif (in_array('chef_direction', $roleCodes, true)) {
        $pageEyebrow = 'Direction';
        $dashboardLabel = 'Dashboard direction';
        $scopeChipLabel = 'Perimetre direction';
        $pageSubtitle = $currentDirectionLabel
            ? 'Tableaux de supervision limites a la direction '.$currentDirectionLabel.'.'
            : 'Tableaux de supervision limites aux services de votre direction.';
        $reportingChipLabel = $currentDirectionLabel ? 'Direction '.$currentDirectionLabel : 'Reporting direction';
        $sectionPresentation = $currentDirectionLabel
            ? 'Presentation dediee a la direction '.$currentDirectionLabel.', limitee aux services de votre perimetre.'
            : 'Presentation dediee a votre direction, limitee aux services de votre perimetre.';
    } elseif (in_array('chef_service', $roleCodes, true)) {
        $pageEyebrow = 'Service';
        $dashboardLabel = 'Dashboard service';
        $scopeChipLabel = 'Perimetre service';
        $pageSubtitle = $currentServiceLabel
            ? 'Tableaux de supervision limites au service '.$currentServiceLabel.'.'
            : 'Tableaux de supervision limites a votre service.';
        $reportingChipLabel = $currentServiceLabel ? 'Service '.$currentServiceLabel : 'Reporting service';
        $sectionPresentation = $currentServiceLabel
            ? 'Presentation dediee au service '.$currentServiceLabel.', limitee aux reclamations de votre perimetre.'
            : 'Presentation dediee a votre service, limitee aux reclamations de votre perimetre.';
    } elseif (in_array('dg', $roleCodes, true)) {
        $pageEyebrow = 'Direction generale';
        $reportingChipLabel = 'Reporting direction generale';
    } elseif (in_array('lecture_seule', $roleCodes, true)) {
        $pageEyebrow = 'Lecture seule';
        $reportingChipLabel = 'Consultation securisee';
    }

    $totalReclamations = (int) ($typeTotals['reclamation']['total'] ?? 0);

    $annexeRepartition = $overviewData['annexe_repartition'] ?? [];
    $annexeReclamationsRows = collect($annexeRepartition['reclamations'] ?? []);
    $annexeReclamationsTotal = (int) ($annexeRepartition['total_reclamations'] ?? 0);
    $functionDistribution = $overviewData['annexes_fonctions']['reclamations'] ?? [];
    $functionDistributionRows = collect($functionDistribution['rows'] ?? []);
    $functionDistributionTotals = $functionDistribution['totaux'] ?? [];
    $reclamationServiceDistribution = $overviewData['annexes_services']['reclamations'] ?? [];
    $reclamationServiceDistributionRows = collect($reclamationServiceDistribution['rows'] ?? []);
    $reclamationServiceDistributionTotals = $reclamationServiceDistribution['totaux'] ?? [];

    $ciqTrackingRows = collect($overviewData['tableau_suivi_annexe'] ?? []);
    $ciqTrackingPagination = $overviewData['tableau_suivi_pagination'] ?? [];
    $ciqTrackingCurrentPage = max(1, (int) ($ciqTrackingPagination['current_page'] ?? 1));
    $ciqTrackingLastPage = max(1, (int) ($ciqTrackingPagination['last_page'] ?? 1));
    $ciqTrackingTotal = (int) ($ciqTrackingPagination['total'] ?? $ciqTrackingRows->count());
    $ciqTrackingFrom = (int) ($ciqTrackingPagination['from'] ?? ($ciqTrackingRows->isNotEmpty() ? 1 : 0));
    $ciqTrackingTo = (int) ($ciqTrackingPagination['to'] ?? $ciqTrackingRows->count());
    $ciqTrackingPreviousUrl = $ciqTrackingCurrentPage > 1
        ? request()->fullUrlWithQuery(['tracking_page' => $ciqTrackingCurrentPage - 1])
        : null;
    $ciqTrackingNextUrl = $ciqTrackingCurrentPage < $ciqTrackingLastPage
        ? request()->fullUrlWithQuery(['tracking_page' => $ciqTrackingCurrentPage + 1])
        : null;
    $ciqTrackingRowsJson = $ciqTrackingRows
        ->map(fn (array $row) => [
            'id_demande' => (int) ($row['id_demande'] ?? 0),
            'numero_suivi' => (string) ($row['numero_suivi'] ?? ''),
            'expediteur' => (string) ($row['expediteur'] ?? ''),
            'type_demande' => (string) ($row['type_demande'] ?? ''),
        ])
        ->values()
        ->all();
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
                @if ($canViewScopedCiqTables)
                <a href="{{ url('/pilotage/dashboard') }}{{ request()->getQueryString() ? '?'.request()->getQueryString() : '' }}" class="inline-flex items-center gap-1.5 bg-sky/20 hover:bg-sky/30 border border-sky/30 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition-all duration-150" aria-label="Ouvrir le {{ strtolower($dashboardLabel) }}">
                    <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 19h16v2H2V4h2v15Zm2.5-2.5v-5h3v5h-3Zm5 0v-9h3v9h-3Zm5 0v-12h3v12h-3Z" fill="currentColor"/>
                    </svg>
                    <span class="hidden sm:inline">{{ $dashboardLabel }}</span>
                </a>
                @endif
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
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-100 mb-2">{{ $pageEyebrow }}</p>
                    <h1 class="text-white text-xl font-medium leading-snug mb-1">{{ $pageTitle }}</h1>
                    <p class="text-sky-100 text-sm font-light leading-relaxed max-w-3xl">{{ $pageSubtitle }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="hero-chip inline-flex items-center gap-2 rounded-full px-3 py-2 text-xs text-white/90">
                            <svg class="icon-svg text-[#f8e932]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M11 2v10h10A10 10 0 0 0 11 2Zm-1 1.1A10 10 0 1 0 20.9 14H10V3.1Z" fill="currentColor"/>
                            </svg>
                            {{ $scopeChipLabel }}
                        </span>
                        <span class="hero-chip inline-flex items-center gap-2 rounded-full px-3 py-2 text-xs text-white/90">
                            <svg class="icon-svg text-[#8fc043]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M3 4h18v16H3V4Zm2 2v3h14V6H5Zm0 5v3h4v-3H5Zm6 0v3h8v-3h-8Zm-6 5v2h4v-2H5Zm6 0v2h8v-2h-8Z" fill="currentColor"/>
                            </svg>
                            {{ $reportingChipLabel }}
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
                    <p class="text-xs text-neutral-400">Si vous choisissez une date de début ou de fin, la période passe automatiquement en mode personnalisée. Si vous revenez sur `Aujourd'hui`, `Semaine`, `Mois` ou `Année`, la plage manuelle est effacée.</p>
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

        {{-- Graphiques CIQ disponibles sur la page dédiée /pilotage/dashboard. --}}

            <div id="ciq-tracking-modal" class="fixed inset-0 z-[80] hidden">
                <div id="ciq-tracking-backdrop" class="absolute inset-0 bg-navy/55 backdrop-blur-[2px]"></div>
                <div class="relative z-[81] min-h-full flex items-center justify-center px-4 py-6">
                    <div class="w-full max-w-6xl rounded-3xl bg-white shadow-2xl border border-white/70 overflow-hidden">
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
                                        <p class="text-xs uppercase tracking-wide text-neutral-400">Prénom</p>
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
                                    <h4 class="text-sm font-semibold text-navy uppercase tracking-[0.15em]">Traitement et traçabilité</h4>
                                </div>
                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                                    <article class="rounded-2xl bg-white border border-sky/15 p-4 shadow-soft">
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="text-xs uppercase tracking-wide text-neutral-400">Affectation UCAS</p>
                                            <span id="ciq-modal-accueil-date" class="rounded-full bg-sky/10 px-2.5 py-1 text-[11px] font-medium text-sky">-</span>
                                        </div>
                                        <div class="mt-3 space-y-3">
                                            <div>
                                                <p class="text-[11px] uppercase tracking-wide text-neutral-400">Agent UCAS</p>
                                                <p id="ciq-modal-accueil-agent" class="mt-1 text-sm font-semibold text-navy">-</p>
                                            </div>
                                            <div>
                                                <p class="text-[11px] uppercase tracking-wide text-neutral-400">Service affecté</p>
                                                <p id="ciq-modal-accueil-service" class="mt-1 text-sm font-medium text-neutral-700">-</p>
                                                <p id="ciq-modal-accueil-service-libelle" class="mt-0.5 text-xs text-neutral-400">-</p>
                                            </div>
                                            <p id="ciq-modal-accueil-commentaire" class="hidden rounded-xl bg-neutral-50 px-3 py-2 text-xs leading-5 text-neutral-500"></p>
                                        </div>
                                    </article>

                                    <article class="rounded-2xl bg-white border border-leaf/20 p-4 shadow-soft">
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="text-xs uppercase tracking-wide text-neutral-400">Affectation agent</p>
                                            <span id="ciq-modal-agent-date" class="rounded-full bg-leaf/10 px-2.5 py-1 text-[11px] font-medium text-leaf">-</span>
                                        </div>
                                        <div class="mt-3 space-y-3">
                                            <div>
                                                <p class="text-[11px] uppercase tracking-wide text-neutral-400">Chef de service</p>
                                                <p id="ciq-modal-chef-agent" class="mt-1 text-sm font-semibold text-navy">-</p>
                                            </div>
                                            <div>
                                                <p class="text-[11px] uppercase tracking-wide text-neutral-400">Agent assigné</p>
                                                <p id="ciq-modal-agent-assigne" class="mt-1 text-sm font-medium text-neutral-700">-</p>
                                            </div>
                                            <p id="ciq-modal-agent-commentaire" class="hidden rounded-xl bg-neutral-50 px-3 py-2 text-xs leading-5 text-neutral-500"></p>
                                        </div>
                                    </article>

                                    <article class="rounded-2xl bg-white border border-neutral-200 p-4 shadow-soft">
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="text-xs uppercase tracking-wide text-neutral-400">Clôture</p>
                                            <span id="ciq-modal-reponse-date" class="rounded-full bg-navy/10 px-2.5 py-1 text-[11px] font-medium text-navy">-</span>
                                        </div>
                                        <div class="mt-3 space-y-3">
                                            <div>
                                                <p class="text-[11px] uppercase tracking-wide text-neutral-400">Rédacteur de la réponse</p>
                                                <p id="ciq-modal-reponse-redacteur" class="mt-1 text-sm font-semibold text-navy">-</p>
                                            </div>
                                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                                                <div class="rounded-xl bg-neutral-50 p-3">
                                                    <p class="text-[10px] uppercase tracking-wide text-neutral-400">Statut</p>
                                                    <p id="ciq-modal-statut" class="mt-1 text-xs font-semibold text-neutral-700">-</p>
                                                </div>
                                                <div class="rounded-xl bg-neutral-50 p-3">
                                                    <p class="text-[10px] uppercase tracking-wide text-neutral-400">Délais</p>
                                                    <p id="ciq-modal-respect" class="mt-1 text-xs font-semibold text-neutral-700">-</p>
                                                </div>
                                                <div class="rounded-xl bg-neutral-50 p-3">
                                                    <p class="text-[10px] uppercase tracking-wide text-neutral-400">Responsable du retard</p>
                                                    <p id="ciq-modal-responsable-retard" class="mt-1 text-xs font-semibold text-neutral-700">-</p>
                                                </div>
                                            </div>
                                        </div>
                                    </article>
                                </div>

                                <div class="mt-5 rounded-2xl border border-neutral-200 bg-white p-4 shadow-soft">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p class="text-xs uppercase tracking-wide text-neutral-400">Réponse apportée à la demande</p>
                                            <p id="ciq-modal-reponse-meta" class="mt-1 text-xs text-neutral-500">-</p>
                                        </div>
                                        <span id="ciq-modal-realisation" class="inline-flex w-fit items-center rounded-full bg-neutral-100 px-3 py-1 text-xs font-medium text-neutral-500">-</span>
                                    </div>
                                    <p id="ciq-modal-reponse-contenu" class="mt-3 rounded-xl bg-neutral-50 px-4 py-3 text-sm leading-6 text-neutral-700 whitespace-pre-line">Aucune réponse finale enregistrée.</p>
                                </div>

                                <div class="mt-5 rounded-2xl border border-neutral-200 bg-white p-4 shadow-soft">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-xs uppercase tracking-wide text-neutral-400">Historique des actions</p>
                                            <p class="mt-1 text-xs text-neutral-500">Lecture chronologique du traitement de la demande.</p>
                                        </div>
                                    </div>
                                    <div id="ciq-modal-treatment-timeline" class="mt-4 space-y-3">
                                        <div class="rounded-xl border border-dashed border-neutral-200 bg-neutral-50 px-4 py-3 text-sm text-neutral-400">
                                            Aucune action tracée.
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        @if ($canViewScopedCiqTables)
        <section class="space-y-5">
            <div>
                <h2 class="ciq-section-title text-sm font-medium text-navy">Tableau actuel du suivi des réclamations</h2>
                <p class="text-xs text-neutral-400 mt-1">
                    {{ $sectionPresentation }}
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
                      <p class="text-xs text-neutral-400 hidden sm:block">
                          {{ number_format($ciqTrackingFrom, 0, ',', ' ') }}-{{ number_format($ciqTrackingTo, 0, ',', ' ') }}
                          sur {{ number_format($ciqTrackingTotal, 0, ',', ' ') }} ligne(s)
                      </p>
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
                                <th class="px-3 py-3 text-left text-[11px] font-medium text-white uppercase tracking-wider">Expéditeur</th>
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
                <div class="flex flex-col gap-3 border-t border-neutral-200 bg-neutral-50 px-5 py-3 text-xs text-neutral-500 sm:flex-row sm:items-center sm:justify-between">
                    <span>
                        Page {{ number_format($ciqTrackingCurrentPage, 0, ',', ' ') }} / {{ number_format($ciqTrackingLastPage, 0, ',', ' ') }}
                        - lignes {{ number_format($ciqTrackingFrom, 0, ',', ' ') }} à {{ number_format($ciqTrackingTo, 0, ',', ' ') }}
                        sur {{ number_format($ciqTrackingTotal, 0, ',', ' ') }}
                    </span>
                    <div class="flex items-center gap-2">
                        @if ($ciqTrackingPreviousUrl)
                            <a href="{{ $ciqTrackingPreviousUrl }}" class="inline-flex items-center rounded-lg border border-neutral-200 bg-white px-3 py-1.5 font-medium text-neutral-600 hover:border-sky/30 hover:text-navy">Précédent</a>
                        @else
                            <span class="inline-flex items-center rounded-lg border border-neutral-200 bg-neutral-100 px-3 py-1.5 font-medium text-neutral-300">Précédent</span>
                        @endif
                        @if ($ciqTrackingNextUrl)
                            <a href="{{ $ciqTrackingNextUrl }}" class="inline-flex items-center rounded-lg border border-neutral-200 bg-white px-3 py-1.5 font-medium text-neutral-600 hover:border-sky/30 hover:text-navy">Suivant</a>
                        @else
                            <span class="inline-flex items-center rounded-lg border border-neutral-200 bg-neutral-100 px-3 py-1.5 font-medium text-neutral-300">Suivant</span>
                        @endif
                    </div>
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
                                <th colspan="6" class="px-3 py-3 text-center text-sm font-semibold text-navy border border-neutral-300">Répartition par fonction</th>
                            </tr>
                            <tr class="bg-white">
                                <th class="px-3 py-3 text-center text-sm font-semibold text-navy border border-neutral-300">Nombre de réclamations total</th>
                                <th class="px-3 py-3 text-center text-sm font-semibold text-navy border border-neutral-300">Nombre de réclamations traitées</th>
                                <th class="px-3 py-3 text-center text-sm font-semibold text-emerald-600 border border-neutral-300">Taux d'exécution (%)</th>
                                <th class="px-3 py-3 text-center text-sm font-semibold text-navy border border-neutral-300">Nombre de réclamations traitées dans les délais</th>
                                <th class="px-3 py-3 text-center text-sm font-semibold text-emerald-600 border border-neutral-300">Taux de conformité (24h)(%)</th>
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
                                <th class="px-3 py-3 text-center text-sm font-semibold text-emerald-600 border border-neutral-300">Taux de conformité (24h) (%) (B)</th>
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
                <p class="text-xs text-neutral-400">
                    {{ number_format($ciqTrackingFrom, 0, ',', ' ') }}-{{ number_format($ciqTrackingTo, 0, ',', ' ') }}
                    sur {{ number_format($ciqTrackingTotal, 0, ',', ' ') }} dossier(s)
                </p>
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
                                <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Expéditeur</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Objet</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Réception</th>
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
                                        data-tracking-id="{{ $row['id_demande'] ?? '' }}"
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
                <div class="px-5 py-3 border-t border-neutral-200 bg-neutral-50 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between text-xs text-neutral-400">
                    <span>
                        Utiliser le bouton <span class="font-medium text-neutral-500">Consulter</span> pour ouvrir la fiche complète de la demande.
                    </span>
                    @if ($ciqTrackingLastPage > 1)
                    <div class="inline-flex items-center gap-2">
                        <span class="font-medium text-neutral-500">Page {{ number_format($ciqTrackingCurrentPage, 0, ',', ' ') }} / {{ number_format($ciqTrackingLastPage, 0, ',', ' ') }}</span>
                        @if ($ciqTrackingPreviousUrl)
                            <a href="{{ $ciqTrackingPreviousUrl }}" class="rounded-lg border border-neutral-200 bg-white px-3 py-1.5 font-medium text-neutral-600 hover:border-sky/40 hover:text-navy transition-colors">Précédent</a>
                        @else
                            <span class="rounded-lg border border-neutral-200 bg-neutral-100 px-3 py-1.5 font-medium text-neutral-300">Précédent</span>
                        @endif
                        @if ($ciqTrackingNextUrl)
                            <a href="{{ $ciqTrackingNextUrl }}" class="rounded-lg border border-neutral-200 bg-white px-3 py-1.5 font-medium text-neutral-600 hover:border-sky/40 hover:text-navy transition-colors">Suivant</a>
                        @else
                            <span class="rounded-lg border border-neutral-200 bg-neutral-100 px-3 py-1.5 font-medium text-neutral-300">Suivant</span>
                        @endif
                    </div>
                    @endif
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
            const ciqTrackingExportXlsButton = document.getElementById('ciq-tracking-export-xls');
            const ciqTrackingExportPdfButton = document.getElementById('ciq-tracking-export-pdf');
            const annexe2ExportXlsButton = document.getElementById('annexe2-export-xls');
            const annexe2ExportPdfButton = document.getElementById('annexe2-export-pdf');
            const functionDistributionExportXlsButton = document.getElementById('function-distribution-export-xls');
            const functionDistributionExportPdfButton = document.getElementById('function-distribution-export-pdf');
            const reclamationServiceDistributionExportXlsButton = document.getElementById('reclamation-service-distribution-export-xls');
            const reclamationServiceDistributionExportPdfButton = document.getElementById('reclamation-service-distribution-export-pdf');
            const ciqTrackingModal = document.getElementById('ciq-tracking-modal');
            const ciqTrackingBackdrop = document.getElementById('ciq-tracking-backdrop');
            const ciqTrackingClose = document.getElementById('ciq-tracking-close');
            const trackingDetailButtons = document.querySelectorAll('.tracking-detail-trigger');
            const ciqTrackingDetails = @json($ciqTrackingRowsJson);
            const ciqTrackingDetailsById = new Map(
                Array.isArray(ciqTrackingDetails)
                    ? ciqTrackingDetails.map((row) => [String(row.id_demande ?? ''), row])
                    : []
            );
            const ciqTrackingDetailCache = new Map();
            const ciqTrackingDetailBaseUrl = @json(url('/pilotage/demandes/__ID__/detail'));
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

            function detailValue(value, fallback = '-') {
                const normalized = value === null || value === undefined ? '' : `${value}`.trim();
                return normalized !== '' ? normalized : fallback;
            }

            function setOptionalBlock(id, value) {
                const element = document.getElementById(id);
                if (!element) {
                    return;
                }

                const normalized = value === null || value === undefined ? '' : `${value}`.trim();
                element.textContent = normalized;
                element.classList.toggle('hidden', normalized === '');
            }

            function formatShortDetailDate(value) {
                const formatted = formatDetailDate(value);
                return formatted === '-' ? '-' : formatted.replace(',', ' à');
            }

            function renderTreatmentTimeline(actions) {
                const container = document.getElementById('ciq-modal-treatment-timeline');
                if (!container) {
                    return;
                }

                container.innerHTML = '';

                if (!Array.isArray(actions) || actions.length === 0) {
                    const empty = document.createElement('div');
                    empty.className = 'rounded-xl border border-dashed border-neutral-200 bg-neutral-50 px-4 py-3 text-sm text-neutral-400';
                    empty.textContent = 'Aucune action tracée.';
                    container.appendChild(empty);
                    return;
                }

                actions.forEach((action, index) => {
                    const item = document.createElement('article');
                    item.className = 'relative rounded-2xl border border-neutral-200 bg-neutral-50/70 px-4 py-3';

                    const header = document.createElement('div');
                    header.className = 'flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between';

                    const left = document.createElement('div');
                    left.className = 'flex items-start gap-3';

                    const badge = document.createElement('div');
                    badge.className = 'mt-0.5 flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-sky/10 text-xs font-semibold text-sky';
                    badge.textContent = `${index + 1}`;

                    const titleWrap = document.createElement('div');
                    const title = document.createElement('p');
                    title.className = 'text-sm font-semibold text-navy';
                    title.textContent = detailValue(action.libelle, 'Action');
                    const actor = document.createElement('p');
                    actor.className = 'mt-0.5 text-xs text-neutral-500';
                    actor.textContent = `Acteur : ${detailValue(action.acteur, 'Non renseigné')}`;
                    titleWrap.appendChild(title);
                    titleWrap.appendChild(actor);

                    left.appendChild(badge);
                    left.appendChild(titleWrap);

                    const date = document.createElement('span');
                    date.className = 'inline-flex w-fit rounded-full bg-white px-3 py-1 text-xs font-medium text-neutral-500 shadow-soft';
                    date.textContent = formatShortDetailDate(action.date_action ?? null);

                    header.appendChild(left);
                    header.appendChild(date);
                    item.appendChild(header);

                    const details = [];
                    if (detailValue(action.service, '') !== '') {
                        details.push(`Service : ${detailValue(action.service)}${detailValue(action.service_libelle, '') !== '' ? ` - ${action.service_libelle}` : ''}`);
                    }
                    if (detailValue(action.agent, '') !== '') {
                        details.push(`Agent assigné : ${action.agent}`);
                    }
                    if (detailValue(action.commentaire, '') !== '') {
                        details.push(`Commentaire : ${action.commentaire}`);
                    }

                    if (details.length > 0) {
                        const detail = document.createElement('p');
                        detail.className = 'mt-3 rounded-xl bg-white px-3 py-2 text-xs leading-5 text-neutral-600';
                        detail.textContent = details.join(' | ');
                        item.appendChild(detail);
                    }

                    container.appendChild(item);
                });
            }

            function renderTreatmentTrace(row) {
                const traitement = row.traitement ?? {};
                const accueil = traitement.affectation_accueil ?? {};
                const agent = traitement.affectation_agent ?? {};
                const reponse = traitement.reponse ?? {};

                setModalText('ciq-modal-accueil-date', formatShortDetailDate(accueil.date ?? row.date_dispatching ?? null));
                setModalText('ciq-modal-accueil-agent', detailValue(accueil.agent, '-'));
                setModalText('ciq-modal-accueil-service', detailValue(accueil.service, row.service_direction ?? '-'));
                setModalText('ciq-modal-accueil-service-libelle', [
                    detailValue(accueil.service_libelle, ''),
                    detailValue(accueil.direction, ''),
                ].filter(Boolean).join(' - ') || '-');
                setOptionalBlock('ciq-modal-accueil-commentaire', accueil.commentaire ?? '');

                const hasAgentAssignment = detailValue(agent.date ?? row.date_affectation_agent ?? null, '') !== '';
                setModalText('ciq-modal-agent-date', formatShortDetailDate(agent.date ?? row.date_affectation_agent ?? null));
                setModalText('ciq-modal-chef-agent', hasAgentAssignment ? detailValue(agent.chef, row.qcs ?? '-') : 'Aucune affectation agent');
                setModalText('ciq-modal-agent-assigne', hasAgentAssignment ? detailValue(agent.agent, '-') : 'Traitement direct ou en attente');
                setOptionalBlock('ciq-modal-agent-commentaire', agent.commentaire ?? '');

                const responseDate = reponse.date_envoi_usager ?? row.realisation ?? null;
                const redactionDate = reponse.date_redaction ?? null;
                const responseType = detailValue(reponse.type_reponse, 'Réponse finale');
                const responseAuthor = detailValue(reponse.redacteur, row.qcs ?? '-');
                const responseSender = detailValue(reponse.envoyeur, '');

                setModalText('ciq-modal-reponse-date', formatShortDetailDate(responseDate));
                setModalText('ciq-modal-reponse-redacteur', responseAuthor);
                setModalText('ciq-modal-realisation', `Envoi usager : ${formatShortDetailDate(responseDate)}`);
                setModalText(
                    'ciq-modal-reponse-meta',
                    `${responseType} | Rédigée par ${responseAuthor} le ${formatShortDetailDate(redactionDate)}${responseSender !== '' ? ` | Envoyée par ${responseSender}` : ''}`
                );
                setModalText('ciq-modal-reponse-contenu', detailValue(reponse.contenu, 'Aucune réponse finale enregistrée.'));
                renderTreatmentTimeline(traitement.historique ?? []);
            }

            function buildTrackingDetailUrl(demandId) {
                const url = new URL(
                    ciqTrackingDetailBaseUrl.replace('__ID__', encodeURIComponent(demandId)),
                    window.location.origin
                );
                const currentParams = new URLSearchParams(window.location.search);

                currentParams.forEach((value, key) => {
                    if (value !== '') {
                        url.searchParams.set(key, value);
                    }
                });

                return url;
            }

            function setTrackingModalLoading(row) {
                const placeholderIds = [
                    'ciq-modal-nom',
                    'ciq-modal-prenom',
                    'ciq-modal-email',
                    'ciq-modal-statut-usager',
                    'ciq-modal-type',
                    'ciq-modal-categorie',
                    'ciq-modal-pays',
                    'ciq-modal-etablissement',
                    'ciq-modal-objet',
                    'ciq-modal-message',
                    'ciq-modal-reception',
                    'ciq-modal-accueil-date',
                    'ciq-modal-accueil-agent',
                    'ciq-modal-accueil-service',
                    'ciq-modal-accueil-service-libelle',
                    'ciq-modal-agent-date',
                    'ciq-modal-chef-agent',
                    'ciq-modal-agent-assigne',
                    'ciq-modal-reponse-date',
                    'ciq-modal-reponse-redacteur',
                    'ciq-modal-statut',
                    'ciq-modal-respect',
                    'ciq-modal-reponse-meta',
                    'ciq-modal-realisation',
                    'ciq-modal-responsable-retard',
                ];

                setModalText('ciq-modal-tracking-number', row?.numero_suivi ?? 'Demande');
                setModalText('ciq-modal-subtitle', 'Chargement sécurisé du dossier...');
                placeholderIds.forEach((id) => setModalText(id, '-'));
                setModalText('ciq-modal-reponse-contenu', 'Chargement du traitement...');
                setOptionalBlock('ciq-modal-accueil-commentaire', '');
                setOptionalBlock('ciq-modal-agent-commentaire', '');
                renderTrackingAttachments([]);
                renderTreatmentTimeline([]);
            }

            async function loadTrackingDetail(demandId) {
                const normalizedDemandId = String(demandId ?? '');
                if (ciqTrackingDetailCache.has(normalizedDemandId)) {
                    return ciqTrackingDetailCache.get(normalizedDemandId);
                }

                const response = await fetch(buildTrackingDetailUrl(normalizedDemandId), {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    let message = 'Impossible de charger le dossier.';
                    try {
                        const errorPayload = await response.json();
                        message = errorPayload.message || message;
                    } catch (error) {
                        // Keep the generic message if the server did not return JSON.
                    }
                    throw new Error(message);
                }

                const payload = await response.json();
                const detail = payload.data ?? payload;
                ciqTrackingDetailCache.set(normalizedDemandId, detail);

                return detail;
            }

            async function openTrackingModal(demandId) {
                const normalizedDemandId = String(demandId ?? '');
                const row = ciqTrackingDetailsById.get(normalizedDemandId)
                    || (Array.isArray(ciqTrackingDetails)
                        ? ciqTrackingDetails.find((item) => String(item.id_demande ?? '') === normalizedDemandId)
                        : null);

                if (!row || !ciqTrackingModal) {
                    console.warn('Dossier introuvable pour consultation', normalizedDemandId);
                    return;
                }

                setTrackingModalLoading(row);
                ciqTrackingModal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');

                try {
                    const detail = await loadTrackingDetail(normalizedDemandId);
                    setModalText('ciq-modal-tracking-number', detail.numero_suivi ?? '-');
                    setModalText('ciq-modal-subtitle', `${detail.expediteur ?? '-'} - ${detail.type_demande ?? '-'}`);
                    setModalText('ciq-modal-nom', detail.usager_nom ?? '-');
                    setModalText('ciq-modal-prenom', detail.usager_prenom ?? '-');
                    setModalText('ciq-modal-email', detail.usager_email ?? '-');
                    setModalText('ciq-modal-statut-usager', detail.usager_statut ?? '-');
                    setModalText('ciq-modal-type', detail.type_demande ?? '-');
                    setModalText('ciq-modal-categorie', detail.categorie ?? '-');
                    setModalText('ciq-modal-pays', detail.usager_pays ?? '-');
                    setModalText('ciq-modal-etablissement', detail.usager_etablissement ?? '-');
                    setModalText('ciq-modal-objet', detail.objet_original ?? detail.objet ?? '-');
                    setModalText('ciq-modal-message', detail.message ?? '-');
                    setModalText('ciq-modal-reception', `Soumise le ${formatDetailDate(detail.date_reception ?? null)}`);
                    setModalText('ciq-modal-statut', detail.statut_traitement ?? '-');
                    setModalText('ciq-modal-respect', detail.respect_delais ?? '-');
                    setModalText('ciq-modal-responsable-retard', detail.responsable_retard ?? '-');
                    renderTrackingAttachments(detail.pieces_jointes ?? []);
                    renderTreatmentTrace(detail);
                } catch (error) {
                    setModalText('ciq-modal-subtitle', error.message || 'Erreur de chargement');
                    setModalText('ciq-modal-reponse-contenu', error.message || 'Impossible de charger le dossier.');
                    renderTreatmentTimeline([]);
                }
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

            trackingDetailButtons.forEach((button) => {
                button.addEventListener('click', () => openTrackingModal(button.dataset.trackingId));
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
        })();
    </script>
</body>
</html>
