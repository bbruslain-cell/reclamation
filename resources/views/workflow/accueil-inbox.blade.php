<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accueil — Traitement des demandes | ANBG</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --anbg-navy: #1c203d;
            --anbg-sky: #3996d3;
            --anbg-leaf: #8fc043;
            --anbg-sun: #f8e932;
        }

        body {
            background:
                radial-gradient(circle at top left, rgba(57, 150, 211, 0.14), transparent 10%),
                radial-gradient(circle at top right, rgba(143, 192, 67, 0.14), transparent 24%),
                linear-gradient(180deg, #fff 0%, #fff 100%);
        }

        .surface-card {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(197, 203, 217, 0.55);
            box-shadow: 0 18px 40px rgba(28, 32, 61, 0.08);
            backdrop-filter: blur(10px);
        }
        .hero-shell {
            background:
                linear-gradient(135deg, rgba(28, 32, 61, 0.98) 0%, rgba(28, 32, 61, 0.92) 48%, rgba(57, 150, 211, 0.94) 100%);
            position: relative;
            overflow: hidden;
        }
        .hero-shell::before {
            content: '';
            position: absolute;
            inset: -20% auto auto -10%;
            width: 280px;
            height: 280px;
            background: radial-gradient(circle, rgba(248, 233, 50, 0.22) 0%, transparent 68%);
            pointer-events: none;
        }
        .hero-shell::after {
            content: '';
            position: absolute;
            right: -60px;
            bottom: -80px;
            width: 260px;
            height: 260px;
            background: radial-gradient(circle, rgba(143, 192, 67, 0.28) 0%, transparent 70%);
            pointer-events: none;
        }
        .hero-chip {
            border: 1px solid rgba(255, 255, 255, 0.16);
            background: rgba(255, 255, 255, 0.10);
            backdrop-filter: blur(8px);
        }
        .kpi-card {
            position: relative;
            overflow: hidden;
        }
        .kpi-card::after {
            content: '';
            position: absolute;
            inset: auto -35% -45% auto;
            width: 120px;
            height: 120px;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.16);
        }
        .kpi-card > * {
            position: relative;
            z-index: 1;
        }
        .section-title-bar {
            background: linear-gradient(90deg, rgba(28, 32, 61, 0.06), rgba(57, 150, 211, 0.04) 50%, rgba(143, 192, 67, 0.06));
        }
        .table-head {
            background: linear-gradient(90deg, rgba(28, 32, 61, 0.96), rgba(57, 150, 211, 0.92));
        }
        .table-head th {
            color: rgba(255, 255, 255, 0.92);
        }
    </style>
</head>
<body class="text-navy font-sans min-h-screen">

<!-- ═══════════════════ TOPBAR ═══════════════════ -->
<header class="bg-navy sticky top-0 z-50 shadow-md">
    <div class="max-w-screen-xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between gap-4">

        <!-- Logo -->
        <div class="flex items-center gap-3">
            <div class="bg-white rounded-lg px-2.5 py-1.5 flex-shrink-0">
                <img src="/Logo_anbg.png" alt="ANBG" class="h-7 w-auto object-contain block">
            </div>
            <div class="hidden sm:block">
                <span class="text-white text-xs font-medium tracking-wider uppercase">Interface Accueil</span>
            </div>
        </div>

        <!-- Agent info + déconnexion -->
        <div class="flex items-center gap-3">
            <div class="hidden sm:flex items-center gap-2 bg-white/10 border border-white/15 rounded-lg px-3 py-1.5">
                <div class="w-6 h-6 rounded-full bg-sky/30 flex items-center justify-center flex-shrink-0">
                    <svg class="icon-svg text-sky-100 text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="8" r="3.2" stroke="currentColor" stroke-width="2"/>
                        <path d="M6 18c1.4-2.8 4-4.2 6-4.2s4.6 1.4 6 4.2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
                <span class="text-white text-xs font-medium">{{ $actor->prenom }} {{ $actor->nom }}</span>
            </div>
            <form method="post" action="/logout">
                @csrf
                <button type="submit"
                    class="flex items-center gap-1.5 bg-white/10 hover:bg-red-500/20 border border-white/15 hover:border-red-400/40 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition-all duration-150">
                    <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M10 6H7.5A2.5 2.5 0 0 0 5 8.5v7A2.5 2.5 0 0 0 7.5 18H10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M13 8l4 4-4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M9 12h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <span class="hidden sm:inline">Déconnexion</span>
                </button>
            </form>
        </div>
    </div>
</header>

<!-- ═══════════════════ MAIN ═══════════════════ -->
<main class="max-w-screen-xl mx-auto px-3 sm:px-6 py-4 sm:py-6 space-y-5">

    <!-- Toasts -->
    @if(session('success'))
    <div class="flex items-start gap-3 bg-leaf-50 border border-leaf/30 text-green-800 px-4 py-3 rounded-xl text-sm shadow-card">
        <svg class="icon-svg text-leaf mt-0.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/>
            <path d="m8.5 12 2.3 2.3L15.5 9.7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif
    @if(session('error'))
    <div class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm shadow-card">
        <svg class="icon-svg text-red-500 mt-0.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/>
            <path d="M12 8v5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <circle cx="12" cy="16.8" r="1" fill="currentColor"/>
        </svg>
        <span>{{ session('error') }}</span>
    </div>
    @endif
    @if($errors->any())
    <div class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm shadow-card">
        <svg class="icon-svg text-red-500 mt-0.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 4.5 20 18.5H4L12 4.5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
            <path d="M12 9.5v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <circle cx="12" cy="15.8" r="1" fill="currentColor"/>
        </svg>
        <div>
            <p class="font-medium mb-1">Erreurs de validation :</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    @php
        $summary = (object) [
            'total_demandes' => (int) ($summaryStats->total_demandes ?? 0),
            'total_nouvelles' => (int) ($summaryStats->total_nouvelles ?? 0),
            'total_reclamations' => (int) ($summaryStats->total_reclamations ?? 0),
            'total_a_risque' => (int) ($summaryStats->total_a_risque ?? 0),
            'total_en_retard' => (int) ($summaryStats->total_en_retard ?? 0),
        ];
        $filtersActifs = collect([
            $typeCode !== '' ? 'Type filtré' : null,
            !empty($directionId) ? 'Direction ciblée' : null,
            $dateFrom !== '' || $dateTo !== '' ? 'Période personnalisée' : null,
            $search !== '' ? 'Recherche active' : null,
        ])->filter()->values();
    @endphp

    <section class="hero-shell rounded-[22px] sm:rounded-[28px] px-4 py-5 sm:px-8 sm:py-7 text-white shadow-card">
        <div class="relative z-10 grid gap-6 lg:grid-cols-[1.45fr_0.85fr] lg:items-end">
            <div class="space-y-4">
                
                <div class="space-y-6">
                    <h1 class="text-xl font-bold leading-tight sm:text-[2rem]">Pilotage opérationnel des demandes</h1>
                    <p class="max-w-3xl text-sm leading-6 text-white/78 sm:text-[15px]">
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="hero-chip inline-flex items-center gap-2 rounded-full px-3 py-2 text-xs">
                        <svg class="icon-svg text-[#f8e932]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v9A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5v-9Z" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M4 13h4l1.5 2h5L16 13h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        {{ $summary->total_nouvelles }} demandes nouvelles
                    </span>
                    <span class="hero-chip inline-flex items-center gap-2 rounded-full px-3 py-2 text-xs">
                        <svg class="icon-svg text-[#8fc043]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M3 4v5h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M5.5 9A8 8 0 1 1 8 17.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <path d="M12 8v4l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        {{ $recentAccueilActions->count() }} actions accueil récentes
                    </span>
                    <span class="hero-chip inline-flex items-center gap-2 rounded-full px-3 py-2 text-xs">
                        <svg class="icon-svg text-[#3996d3]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 6h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <path d="M7 12h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <path d="M10 18h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                        {{ $filtersActifs->isNotEmpty() ? $filtersActifs->implode(' · ') : 'Vue générale sans filtre' }}
                    </span>
                </div>
            </div>

            
        </div>
    </section>

    <section id="accueil-kpi-content" data-new-demand-count="{{ $summary->total_nouvelles }}" class="flex flex-wrap items-stretch justify-center gap-3">
        <article class="kpi-card aspect-square w-[calc(50%_-_0.375rem)] min-w-[132px] max-w-[146px] rounded-[18px] sm:rounded-[22px] bg-[linear-gradient(135deg,#1c203d_0%,#2a3163_100%)] px-3 py-3 sm:px-4 sm:py-4 text-white shadow-card">
            <div class="flex h-full flex-col items-center justify-center text-center gap-1.5">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-white/12">
                    <svg class="icon-svg text-base" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 8.5A2.5 2.5 0 0 1 6.5 6h11A2.5 2.5 0 0 1 20 8.5v7A2.5 2.5 0 0 1 17.5 18h-11A2.5 2.5 0 0 1 4 15.5v-7Z" stroke="currentColor" stroke-width="1.8"/>
                        <path d="m6 9 6 4 6-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <p class="text-[1.75rem] font-bold leading-none" data-countup="{{ $summary->total_demandes }}">0</p>
                <p class="text-xs text-white/68">Demandes totales</p>
            </div>
        </article>

        <article class="kpi-card aspect-square w-[calc(50%_-_0.375rem)] min-w-[132px] max-w-[146px] rounded-[18px] sm:rounded-[22px] bg-[linear-gradient(135deg,#3996d3_0%,#1c203d_120%)] px-3 py-3 sm:px-4 sm:py-4 text-white shadow-card">
            <div class="flex h-full flex-col items-center justify-center text-center gap-1.5">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-white/12">
                    <svg class="icon-svg text-base" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v9A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5v-9Z" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M4 13h4l1.5 2h5L16 13h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <p class="text-[1.75rem] font-bold leading-none" data-countup="{{ $summary->total_nouvelles }}">0</p>
                <p class="text-xs text-white/68">Demandes nouvelles</p>
            </div>
        </article>



        <article class="kpi-card aspect-square w-[calc(50%_-_0.375rem)] min-w-[132px] max-w-[146px] rounded-[18px] sm:rounded-[22px] bg-[linear-gradient(135deg,#fff7df_0%,#ffe28b_100%)] px-3 py-3 sm:px-4 sm:py-4 text-[#8a5b00] shadow-card">
            <div class="flex h-full flex-col items-center justify-center text-center gap-1.5">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-white/60">
                    <svg class="icon-svg text-base" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 4.5 20 18.5H4L12 4.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        <path d="M12 9.5v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        <circle cx="12" cy="15.8" r="1" fill="currentColor"/>
                    </svg>
                </span>
                <p class="text-[1.75rem] font-bold leading-none" data-countup="{{ $summary->total_a_risque }}">0</p>
                <p class="text-xs text-[#8a5b00]/72">Demandes à risque</p>
            </div>
        </article>

        <article class="kpi-card aspect-square w-[calc(50%_-_0.375rem)] min-w-[132px] max-w-[146px] rounded-[18px] sm:rounded-[22px] bg-[linear-gradient(135deg,#ffe7e7_0%,#ffc9c9_100%)] px-3 py-3 sm:px-4 sm:py-4 text-[#8b1d1d] shadow-card">
            <div class="flex h-full flex-col items-center justify-center text-center gap-1.5">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-white/60">
                    <svg class="icon-svg text-base" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M12 7.8v5.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        <circle cx="12" cy="16.7" r="1" fill="currentColor"/>
                    </svg>
                </span>
                <p class="text-[1.75rem] font-bold leading-none" data-countup="{{ $summary->total_en_retard }}">0</p>
                <p class="text-xs text-[#8b1d1d]/70">Demandes en retard</p>
            </div>
        </article>
    </section>

    <!-- ── FILTRES ── -->
    <div class="surface-card rounded-[20px] sm:rounded-[26px] overflow-hidden">
        <div class="section-title-bar px-4 py-4 sm:px-5 border-b border-white/70 flex items-center gap-2">
            <svg class="icon-svg text-sky text-sm" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M5 6h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                <path d="M8 12h11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                <path d="M5 18h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                <circle cx="8" cy="6" r="2" fill="currentColor"/>
                <circle cx="15" cy="12" r="2" fill="currentColor"/>
                <circle cx="13" cy="18" r="2" fill="currentColor"/>
            </svg>
            <h2 class="text-sm font-semibold text-navy">Filtres et tri des Demandes</h2>
        </div>
        <div class="px-4 py-4 sm:px-5">
            <form method="get" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">

                <div class="sm:col-span-2 lg:col-span-4">
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400 text-sm pointer-events-none">
                            <svg class="icon-svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle cx="11" cy="11" r="5.5" stroke="currentColor" stroke-width="2"/>
                                <path d="m16 16 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <input
                            name="search" value="{{ $search }}"
                            placeholder="Recherche : numéro, objet, nom usager…"
                            class="field w-full pl-9 pr-4 py-2.5 border border-neutral-200 rounded-lg text-sm bg-neutral-50 text-navy placeholder-neutral-400 transition-all duration-150"
                        >
                    </div>
                </div>

                <div>
                    <select name="type_code" class="field w-full px-3 py-2.5 border border-neutral-200 rounded-lg text-sm bg-neutral-50 text-navy appearance-none transition-all duration-150">
                        <option value="">Tous les types</option>
                        @foreach($types as $type)
                            <option value="{{ $type->code }}" {{ $typeCode === $type->code ? 'selected' : '' }}>{{ $type->libelle }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <select name="direction_id" class="field w-full px-3 py-2.5 border border-neutral-200 rounded-lg text-sm bg-neutral-50 text-navy appearance-none transition-all duration-150">
                        <option value="">Toutes les directions</option>
                        @foreach($directions as $direction)
                            <option value="{{ $direction->id_direction }}" {{ (int)$directionId === (int)$direction->id_direction ? 'selected' : '' }}>
                                {{ $direction->code }} — {{ $direction->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <input type="date" name="date_from" value="{{ $dateFrom }}"
                        class="field w-full px-3 py-2.5 border border-neutral-200 rounded-lg text-sm bg-neutral-50 text-navy transition-all duration-150">
                </div>
                <div>
                    <input type="date" name="date_to" value="{{ $dateTo }}"
                        class="field w-full px-3 py-2.5 border border-neutral-200 rounded-lg text-sm bg-neutral-50 text-navy transition-all duration-150">
                </div>

                
                <div>
                    <select name="sort_dir" class="field w-full px-3 py-2.5 border border-neutral-200 rounded-lg text-sm bg-neutral-50 text-navy appearance-none transition-all duration-150">
                        <option value="desc" {{ $sortDir==='desc'?'selected':'' }}>↓ Descendant</option>
                        <option value="asc"  {{ $sortDir==='asc'?'selected':'' }}>↑ Ascendant</option>
                    </select>
                </div>

                <div class="sm:col-span-2 lg:col-span-4 flex justify-stretch sm:justify-end pt-1">
                    <button type="submit"
                        class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-xl bg-[linear-gradient(135deg,#1c203d_0%,#3996d3_100%)] px-5 py-2.5 text-sm font-medium text-white shadow-badge transition-transform duration-200 hover:-translate-y-0.5">
                        <svg class="icon-svg text-xs" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 6h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M7 12h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M10 18h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Appliquer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="accueil-live-content" data-refresh-url="{{ request()->fullUrl() }}" data-refresh-interval="20000" class="space-y-4">
    <!-- ── TABLE DEMANDES ── -->
    <div class="surface-card rounded-[20px] sm:rounded-[26px] overflow-hidden">
        <div class="section-title-bar px-4 py-4 sm:px-5 border-b border-white/70 flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <svg class="icon-svg text-sky text-sm" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M9 7h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    <path d="M9 12h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    <path d="M9 17h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    <path d="m5 7 1.5 1.5L8.5 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="m5 12 1.5 1.5L8.5 11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="m5 17 1.5 1.5L8.5 16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h2 class="text-sm font-semibold text-navy">Demandes nouvelles</h2>
                <span class="ml-1 rounded-full border border-sky-200 bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky">
                    {{ $nouvelles->total() }}
                </span>
            </div>
            <p class="text-xs text-neutral-400 hidden sm:block">Affecter chaque réclamation à la bonne direction ou au bon service.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[760px] w-full">
                <thead>
                    <tr class="table-head border-b border-white/10">
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Numéro</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Usager</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Alerte</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                @forelse($nouvelles as $demande)
                    <!-- Ligne principale -->
                    <tr class="demand-row transition-colors duration-100" data-new-demand-row data-demand-id="{{ $demande->id_demande }}">
                        <td class="px-4 py-3">
                            <span class="font-mono text-xs font-medium text-navy bg-navy-50 px-2 py-1 rounded">
                                {{ $demande->numero_suivi }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-neutral-500 whitespace-nowrap">
                            {{ \Illuminate\Support\Carbon::parse($demande->date_soumission)->format('d/m/Y') }}
                            <span class="text-neutral-400">{{ \Illuminate\Support\Carbon::parse($demande->date_soumission)->format('H:i') }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-navy font-medium">
                            {{ trim(($demande->usager_prenom ?? '').' '.($demande->usager_nom ?? '')) }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs text-neutral-600 bg-neutral-100 px-2 py-1 rounded-full">
                                {{ $demande->type_demande }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($demande->alerte_accueil === 'rouge')
                                <span class="inline-flex items-center gap-1.5 bg-red-50 text-red-700 border border-red-200 text-xs font-medium px-2.5 py-1 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 flex-shrink-0"></span> En retard
                                </span>
                            @elseif($demande->alerte_accueil === 'orange')
                                <span class="inline-flex items-center gap-1.5 bg-gold-50 text-amber-700 border border-amber-200 text-xs font-medium px-2.5 py-1 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gold flex-shrink-0"></span> À risque
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 bg-leaf-50 text-green-700 border border-green-200 text-xs font-medium px-2.5 py-1 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-leaf flex-shrink-0"></span> Dans les délais
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <button type="button"
                                class="view-toggle inline-flex items-center gap-1.5 bg-sky-50 hover:bg-sky text-sky hover:text-white border border-sky-200 hover:border-sky text-xs font-medium px-3 py-1.5 rounded-lg transition-all duration-150"
                                data-target="detail-{{ $demande->id_demande }}" aria-expanded="false">
                                <span class="toggle-icon text-[10px]">
                                    <svg class="icon-svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M2.8 12s3.2-5.5 9.2-5.5S21.2 12 21.2 12s-3.2 5.5-9.2 5.5S2.8 12 2.8 12Z" stroke="currentColor" stroke-width="2"/>
                                        <circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                </span>
                                <span>Voir</span>
                            </button>
                        </td>
                    </tr>

                    <!-- Ligne détail (cachée par défaut) -->
                    <tr id="detail-{{ $demande->id_demande }}" class="detail-row" style="display:none;">
                        <td colspan="6" class="px-3 py-3 sm:px-4 sm:py-4 bg-[linear-gradient(180deg,#f8fbfe_0%,#f4f7fb_100%)] border-b border-neutral-100">
                            @php
                                $pieces = $piecesByDemand->get($demande->id_demande, collect());
                            @endphp
                            <div class="grid max-w-[calc(100vw_-_2rem)] grid-cols-1 gap-4 sm:max-w-none lg:grid-cols-2">

                                <!-- Colonne gauche : détail demande -->
                                <div class="bg-white border border-neutral-200 rounded-xl p-4 space-y-3">
                                    <h4 class="text-xs font-medium text-neutral-500 uppercase tracking-wider border-b border-neutral-100 pb-2">
                                        <svg class="icon-svg text-sky mr-1.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M8 3.5h5l4 4V18a2 2 0 0 1-2 2H8A2 2 0 0 1 6 18V5.5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                            <path d="M13 3.5V8h4.5" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                            <path d="M9 11.5h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                            <path d="M9 15h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        </svg>Détails de la demande
                                    </h4>

                                    <div>
                                        <p class="text-xs text-neutral-400 mb-0.5">Objet</p>
                                        <p class="text-sm font-medium text-navy">{{ $demande->objet }}</p>
                                    </div>

                                    <div>
                                        <p class="text-xs text-neutral-400 mb-1">Message</p>
                                        <div class="bg-neutral-50 border border-neutral-200 rounded-lg p-3 text-sm text-neutral-700 leading-relaxed whitespace-pre-wrap max-h-40 overflow-y-auto">{{ $demande->message }}</div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <p class="text-xs text-neutral-400">Email</p>
                                            <p class="text-sm text-navy">{{ $demande->usager_email ?? '—' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-neutral-400">Statut usager</p>
                                            <p class="text-sm text-navy">{{ $demande->usager_statut ?? '—' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-neutral-400">Pays</p>
                                            <p class="text-sm text-navy">{{ $demande->usager_pays ?? '—' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-neutral-400">Établissement</p>
                                            <p class="text-sm text-navy">{{ $demande->usager_etablissement ?? '—' }}</p>
                                        </div>
                                    </div>

                                    @if($pieces->isNotEmpty())
                                    <div>
                                        <p class="text-xs text-neutral-400 mb-1.5">Pièces jointes</p>
                                        <div class="space-y-1.5">
                                            @foreach($pieces as $piece)
                                            <a href="/pieces-jointes/{{ $piece->id_piece_jointe }}" target="_blank" rel="noopener"
                                               class="flex items-center gap-2 bg-sky-50 border border-sky-100 text-sky text-xs font-medium px-3 py-2 rounded-lg hover:bg-sky-100 transition-colors duration-150">
                                                <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                    <path d="M8.5 12.5 13 8a3 3 0 1 1 4.2 4.2l-6 6a5 5 0 1 1-7.1-7.1l6.3-6.3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                {{ $piece->nom_fichier }}
                                            </a>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                </div>

                                <!-- Colonne droite : actions -->
                                <div class="space-y-3">

                                    <!-- Box affectation -->
                                    <div class="bg-white border border-neutral-200 rounded-xl p-4">
                                        <h4 class="text-xs font-medium text-neutral-500 uppercase tracking-wider border-b border-neutral-100 pb-2 mb-3">
                                            <svg class="icon-svg text-sky mr-1.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M10 6H7.5A2.5 2.5 0 0 0 5 8.5v7A2.5 2.5 0 0 0 7.5 18H10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                <path d="M13 8l4 4-4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M9 12h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            </svg>Affectation Direction → Service
                                        </h4>
                                        <form method="post" action="/accueil/demandes/{{ $demande->id_demande }}/affecter" class="affectation-form space-y-2.5">
                                            @csrf
                                            @method('put')
                                            <select name="id_direction" class="direction-select field w-full px-3 py-2.5 border border-neutral-200 rounded-lg text-sm bg-neutral-50 text-navy appearance-none transition-all duration-150" required>
                                                <option value="">— Choisir une direction —</option>
                                                @foreach($directions as $direction)
                                                    <option value="{{ $direction->id_direction }}">{{ $direction->code }} — {{ $direction->libelle }}</option>
                                                @endforeach
                                            </select>
                                            <select name="id_service" class="service-select field w-full px-3 py-2.5 border border-neutral-200 rounded-lg text-sm bg-neutral-50 text-navy appearance-none transition-all duration-150" required>
                                                <option value="">— Choisir un service —</option>
                                                @foreach($services as $service)
                                                    <option value="{{ $service->id_service }}" data-direction-id="{{ $service->id_direction }}">{{ $service->code }} — {{ $service->libelle }}</option>
                                                @endforeach
                                            </select>
                                            <input name="commentaire" placeholder="Commentaire optionnel…"
                                                class="field w-full px-3 py-2.5 border border-neutral-200 rounded-lg text-sm bg-neutral-50 text-navy placeholder-neutral-400 transition-all duration-150">
                                            <button type="submit"
                                                class="w-full flex items-center justify-center gap-2 bg-navy hover:bg-navy-600 text-white text-sm font-medium py-2.5 rounded-lg transition-colors duration-150">
                                                <svg class="icon-svg text-xs" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                    <path d="M4 11.5 19 5l-4.8 14-3.1-5.1L4 11.5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                                    <path d="M10.8 13.8 19 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                </svg>
                                                Affecter
                                            </button>
                                        </form>
                                    </div>

                                    <div class="bg-white border border-leaf/25 rounded-xl p-4">
                                        <h4 class="text-xs font-medium text-neutral-500 uppercase tracking-wider border-b border-neutral-100 pb-2 mb-3">
                                            <svg class="icon-svg text-leaf mr-1.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M4 12.5 8.5 17 20 5.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>Réponse directe accueil
                                        </h4>
                                        <p class="text-xs text-neutral-500 mb-3">
                                            Utiliser cette action si la réclamation peut être traitée immédiatement au niveau accueil, sans affectation vers une direction.
                                        </p>
                                        <form method="post" action="/accueil/demandes/{{ $demande->id_demande }}/reponse-directe" enctype="multipart/form-data" class="space-y-3 anbg-upload-form">
                                            @csrf
                                            @method('put')
                                            <textarea
                                                name="contenu_reponse"
                                                rows="4"
                                                required
                                                placeholder="Rédiger la réponse transmise directement à l’usager…"
                                                class="field w-full px-3 py-2.5 border border-neutral-200 rounded-lg text-sm bg-neutral-50 text-navy placeholder-neutral-400 transition-all duration-150"
                                            >{{ old('contenu_reponse') }}</textarea>
                                            <div class="anbg-upload-widget space-y-2">
                                                <input
                                                    type="file"
                                                    name="pieces_jointes[]"
                                                    multiple
                                                    class="hidden"
                                                    data-upload-input
                                                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
                                                >
                                                <div
                                                    class="anbg-dropzone px-5 py-8 cursor-pointer"
                                                    data-upload-dropzone
                                                    role="button"
                                                    tabindex="0"
                                                    aria-label="Joindre des pièces à la réponse directe"
                                                >
                                                    <div class="flex flex-col items-center text-center gap-2.5">
                                                        <span class="anbg-dropzone-icon">
                                                            <svg class="icon-svg text-lg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                                <path d="M8 16.5h8a3.5 3.5 0 0 0 .4-7A5.2 5.2 0 0 0 6.1 10 3.2 3.2 0 0 0 8 16.5Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                                                <path d="m12 8.5 2.5 2.5M12 8.5 9.5 11M12 8.5v7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                                            </svg>
                                                        </span>
                                                        <div class="space-y-1">
                                                            <p class="text-base font-medium text-navy">Glissez-déposez votre fichier ici</p>
                                                            <p class="text-sm text-neutral-400">
                                                                ou
                                                                <span class="text-sky font-medium underline underline-offset-2">cliquez pour parcourir</span>
                                                            </p>
                                                        </div>
                                                        <div class="flex flex-wrap justify-center gap-2 text-[11px]">
                                                            <span class="rounded-lg bg-neutral-100 px-2.5 py-1 text-neutral-500 font-medium">PDF</span>
                                                            <span class="rounded-lg bg-neutral-100 px-2.5 py-1 text-neutral-500 font-medium">JPG</span>
                                                            <span class="rounded-lg bg-neutral-100 px-2.5 py-1 text-neutral-500 font-medium">PNG</span>
                                                            <span class="rounded-lg bg-neutral-100 px-2.5 py-1 text-neutral-500 font-medium">Max 4 Mo</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="hidden flex flex-wrap gap-2" data-upload-list></div>
                                            </div>
                                            <button
                                                type="submit"
                                                class="w-full flex items-center justify-center gap-2 bg-leaf hover:bg-green-600 text-white text-sm font-medium py-2.5 rounded-lg transition-colors duration-150"
                                            >
                                                <svg class="icon-svg text-xs" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                    <path d="M4 12.5 8.5 17 20 5.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                Envoyer la réponse directe
                                            </button>
                                        </form>
                                    </div>

                                </div>
                            </div>
                        </td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center">
                            <div class="flex flex-col items-center gap-3 text-neutral-400">
                                <div class="w-12 h-12 rounded-full bg-neutral-100 flex items-center justify-center">
                                    <svg class="icon-svg text-xl" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v9A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5v-9Z" stroke="currentColor" stroke-width="1.8"/>
                                        <path d="M4 13h4l1.5 2h5L16 13h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <p class="text-sm">Aucune demande nouvelle.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($nouvelles->hasPages())
        <div class="px-4 py-4 sm:px-5 border-t border-neutral-100 bg-white/70">
            {{ $nouvelles->links() }}
        </div>
        @endif
    </div>

    <div class="surface-card rounded-[20px] sm:rounded-[26px] overflow-hidden">
        <div class="section-title-bar px-4 py-4 sm:px-5 border-b border-white/70 flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <svg class="icon-svg text-sky text-sm" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M5 7.5A2.5 2.5 0 0 1 7.5 5H18a2 2 0 0 1 2 2v9.5A2.5 2.5 0 0 1 17.5 19h-10A2.5 2.5 0 0 1 5 16.5v-9Z" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M8.5 10.5h8M8.5 14h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    <path d="m3.5 10.5 1.6 1.6L8 9.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h2 class="text-sm font-semibold text-navy">Demandes affectées</h2>
                <span class="ml-1 rounded-full border border-gold-200 bg-gold-50 px-2 py-0.5 text-xs font-medium text-amber-700">
                    {{ $affectees->total() }}
                </span>
            </div>
            <p class="text-xs text-neutral-400 hidden sm:block">Annuler l’affectation si la demande doit revenir au niveau accueil.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[900px] w-full">
                <thead>
                    <tr class="table-head border-b border-white/10">
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Numéro</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Date de dispatch</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Usager</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Direction / service</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Envoi usager</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                @forelse($affectees as $demande)
                    <tr class="demand-row transition-colors duration-100"
                        data-delivery-id="{{ $demande->id_demande }}"
                        data-delivery-label="{{ $demande->numero_suivi }}"
                        data-delivery-state="{{ $demande->delivery_state ?? 'idle' }}">
                        <td class="px-4 py-3">
                            <span class="font-mono text-xs font-medium text-navy bg-navy-50 px-2 py-1 rounded">
                                {{ $demande->numero_suivi }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-neutral-500 whitespace-nowrap">
                            {{ $demande->date_affectation_accueil ? \Illuminate\Support\Carbon::parse($demande->date_affectation_accueil)->format('d/m/Y H:i') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-navy font-medium">
                            {{ trim(($demande->usager_prenom ?? '').' '.($demande->usager_nom ?? '')) }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs text-neutral-600 bg-neutral-100 px-2 py-1 rounded-full">
                                {{ $demande->type_demande }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-neutral-600">
                            {{ trim(($demande->direction ?? '').($demande->service ? ' — '.$demande->service : '')) ?: '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @include('workflow.partials.delivery-status', ['demande' => $demande, 'variant' => 'badge'])
                        </td>
                        <td class="px-4 py-3">
                            <button type="button"
                                class="view-toggle inline-flex items-center gap-1.5 bg-gold-50 hover:bg-gold text-amber-700 hover:text-navy border border-gold-200 hover:border-gold text-xs font-medium px-3 py-1.5 rounded-lg transition-all duration-150"
                                data-target="assigned-detail-{{ $demande->id_demande }}" aria-expanded="false">
                                <span class="toggle-icon text-[10px]">
                                    <svg class="icon-svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M2.8 12s3.2-5.5 9.2-5.5S21.2 12 21.2 12s-3.2 5.5-9.2 5.5S2.8 12 2.8 12Z" stroke="currentColor" stroke-width="2"/>
                                        <circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                </span>
                                <span>Voir</span>
                            </button>
                        </td>
                    </tr>

                    <tr id="assigned-detail-{{ $demande->id_demande }}" class="detail-row" style="display:none;">
                        <td colspan="7" class="px-3 py-3 sm:px-4 sm:py-4 bg-[linear-gradient(180deg,#fffdf7_0%,#f9fbfd_100%)] border-b border-neutral-100">
                            @php
                                $pieces = $piecesByDemand->get($demande->id_demande, collect());
                                $historyEntries = $historyByDemand->get($demande->id_demande, collect());
                                $historyLabels = [
                                    'soumission_usager' => 'Soumission usager',
                                    'soumission' => 'Soumission',
                                    'categorie_usager' => 'Catégorie choisie',
                                    'affectation_service' => 'Affectation service',
                                    'annulation_affectation_service' => 'Annulation affectation service',
                                    'affectation_agent' => 'Affectation agent',
                                    'annulation_affectation_agent' => 'Annulation affectation agent',
                                    'reponse_redigee' => 'Réponse rédigée',
                                    'reponse_directe_chef' => 'Réponse directe chef',
                                    'envoi_reponse' => 'Envoi réponse',
                                    'echec_envoi_reponse' => 'Echec d\'envoi',
                                ];
                            @endphp
                            <div class="grid max-w-[calc(100vw_-_2rem)] grid-cols-1 gap-4 sm:max-w-none lg:grid-cols-2">
                                <div class="bg-white border border-neutral-200 rounded-xl p-4 space-y-3">
                                    <h4 class="text-xs font-medium text-neutral-500 uppercase tracking-wider border-b border-neutral-100 pb-2">
                                        <svg class="icon-svg text-sky mr-1.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M8 3.5h5l4 4V18a2 2 0 0 1-2 2H8A2 2 0 0 1 6 18V5.5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                            <path d="M13 3.5V8h4.5" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                            <path d="M9 11.5h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                            <path d="M9 15h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        </svg>Détails de la demande
                                    </h4>

                                    <div>
                                        <p class="text-xs text-neutral-400 mb-0.5">Objet</p>
                                        <p class="text-sm font-medium text-navy">{{ $demande->objet }}</p>
                                    </div>

                                    <div>
                                        <p class="text-xs text-neutral-400 mb-1">Message</p>
                                        <div class="bg-neutral-50 border border-neutral-200 rounded-lg p-3 text-sm text-neutral-700 leading-relaxed whitespace-pre-wrap max-h-40 overflow-y-auto">{{ $demande->message }}</div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <p class="text-xs text-neutral-400">Email</p>
                                            <p class="text-sm text-navy">{{ $demande->usager_email ?? '—' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-neutral-400">Statut usager</p>
                                            <p class="text-sm text-navy">{{ $demande->usager_statut ?? '—' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-neutral-400">Pays</p>
                                            <p class="text-sm text-navy">{{ $demande->usager_pays ?? '—' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-neutral-400">Établissement</p>
                                            <p class="text-sm text-navy">{{ $demande->usager_etablissement ?? '—' }}</p>
                                        </div>
                                    </div>

                                    @if($pieces->isNotEmpty())
                                    <div>
                                        <p class="text-xs text-neutral-400 mb-1.5">Pièces jointes</p>
                                        <div class="space-y-1.5">
                                            @foreach($pieces as $piece)
                                            <a href="/pieces-jointes/{{ $piece->id_piece_jointe }}" target="_blank" rel="noopener"
                                               class="flex items-center gap-2 bg-sky-50 border border-sky-100 text-sky text-xs font-medium px-3 py-2 rounded-lg hover:bg-sky-100 transition-colors duration-150">
                                                <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                    <path d="M8.5 12.5 13 8a3 3 0 1 1 4.2 4.2l-6 6a5 5 0 1 1-7.1-7.1l6.3-6.3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                {{ $piece->nom_fichier }}
                                            </a>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                </div>

                                <div class="space-y-3">
                                    @include('workflow.partials.delivery-status', ['demande' => $demande, 'variant' => 'panel'])

                                    <div class="bg-white border border-neutral-200 rounded-xl p-4">
                                        <h4 class="text-xs font-medium text-neutral-500 uppercase tracking-wider border-b border-neutral-100 pb-2 mb-3">
                                            <svg class="icon-svg text-sky mr-1.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M4 6.5A2.5 2.5 0 0 1 6.5 4h7l2 2H19a1 1 0 0 1 1 1v10.5A2.5 2.5 0 0 1 17.5 20h-11A2.5 2.5 0 0 1 4 17.5v-11Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                                <path d="M8 11h8M8 15h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                            </svg>Affectation en cours
                                        </h4>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div class="rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2.5">
                                                <p class="text-xs text-neutral-400">Direction</p>
                                                <p class="mt-1 text-sm font-medium text-navy">{{ $demande->direction ?? '—' }}</p>
                                            </div>
                                            <div class="rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2.5">
                                                <p class="text-xs text-neutral-400">Service</p>
                                                <p class="mt-1 text-sm font-medium text-navy">{{ trim(($demande->service_code ?? '').($demande->service ? ' — '.$demande->service : '')) ?: '—' }}</p>
                                            </div>
                                        </div>
                                        <form method="post" action="/accueil/demandes/{{ $demande->id_demande }}/annuler-affectation" class="mt-3 space-y-2.5">
                                            @csrf
                                            @method('put')
                                            <input name="commentaire" placeholder="Motif d’annulation optionnel…"
                                                class="field w-full px-3 py-2.5 border border-neutral-200 rounded-lg text-sm bg-neutral-50 text-navy placeholder-neutral-400 transition-all duration-150">
                                            <button type="submit"
                                                class="w-full flex items-center justify-center gap-2 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 text-sm font-medium py-2.5 rounded-lg transition-colors duration-150">
                                                <svg class="icon-svg text-xs" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                    <path d="M7 7h10M9 7V5.8A1.8 1.8 0 0 1 10.8 4h2.4A1.8 1.8 0 0 1 15 5.8V7M8 10.5l8 8M16 10.5l-8 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                Annuler l’affectation
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center">
                            <div class="flex flex-col items-center gap-3 text-neutral-400">
                                <div class="w-12 h-12 rounded-full bg-neutral-100 flex items-center justify-center">
                                    <svg class="icon-svg text-xl" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M5 7.5A2.5 2.5 0 0 1 7.5 5H18a2 2 0 0 1 2 2v9.5A2.5 2.5 0 0 1 17.5 19h-10A2.5 2.5 0 0 1 5 16.5v-9Z" stroke="currentColor" stroke-width="1.8"/>
                                        <path d="M8.5 10.5h8M8.5 14h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    </svg>
                                </div>
                                <p class="text-sm">Aucune demande affectée à annuler pour le moment.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($affectees->hasPages())
        <div class="px-4 py-4 sm:px-5 border-t border-neutral-100 bg-white/70">
            {{ $affectees->links() }}
        </div>
        @endif
    </div>

    <div class="surface-card rounded-[20px] sm:rounded-[26px] overflow-hidden">
        <div class="section-title-bar px-4 py-4 sm:px-5 border-b border-white/70 flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <svg class="icon-svg text-sky text-sm" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M3 4v5h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M5.5 9A8 8 0 1 1 8 17.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    <path d="M12 8v4l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h2 class="text-sm font-semibold text-navy">Historique des actions accueil</h2>
            </div>
            <p class="text-xs text-neutral-400 hidden sm:block">Affectations, annulations et réponses directes enregistrées récemment.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[980px] w-full">
                <thead>
                    <tr class="table-head border-b border-white/10">
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Action</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Numéro</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Usager</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Service</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Agent accueil</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Commentaire</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                @php
                    $actionLabels = [
                        'affectation_service' => 'Affectation service',
                        'annulation_affectation_service' => 'Annulation affectation',
                        'reponse_directe_accueil' => 'Réponse directe accueil',
                        'envoi_reponse' => 'Envoi réponse',
                        'echec_envoi_reponse' => 'Echec d\'envoi',
                    ];
                @endphp
                @forelse($recentAccueilActions as $entry)
                    <tr class="demand-row transition-colors duration-100">
                        <td class="px-4 py-3 text-xs text-neutral-500 whitespace-nowrap">
                            {{ \Illuminate\Support\Carbon::parse($entry->date_action)->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full {{ in_array($entry->type_action, ['annulation_affectation_service', 'echec_envoi_reponse'], true) ? 'bg-red-50 text-red-700 border border-red-200' : (in_array($entry->type_action, ['reponse_directe_accueil', 'envoi_reponse'], true) ? 'bg-leaf-50 text-green-700 border border-green-200' : 'bg-navy-50 text-navy border border-navy-100') }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ in_array($entry->type_action, ['annulation_affectation_service', 'echec_envoi_reponse'], true) ? 'bg-red-500' : (in_array($entry->type_action, ['reponse_directe_accueil', 'envoi_reponse'], true) ? 'bg-leaf' : 'bg-navy') }}"></span>
                                {{ $actionLabels[$entry->type_action] ?? ucfirst(str_replace('_', ' ', (string) $entry->type_action)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-mono text-xs font-medium text-navy bg-navy-50 px-2 py-1 rounded">
                                {{ $entry->numero_suivi }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-navy font-medium">
                            {{ trim(($entry->usager_prenom ?? '').' '.($entry->usager_nom ?? '')) ?: '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-neutral-600 whitespace-nowrap">{{ $entry->service_code ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-neutral-600">
                            {{ trim(($entry->acteur_prenom ?? '').' '.($entry->acteur_nom ?? '')) ?: 'Système' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-neutral-600">
                            {{ $entry->commentaire ?: '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center">
                            <div class="flex flex-col items-center gap-3 text-neutral-400">
                                <div class="w-12 h-12 rounded-full bg-neutral-100 flex items-center justify-center">
                                    <svg class="icon-svg text-xl" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8"/>
                                        <path d="M12 8v4l2.8 1.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <p class="text-sm">Aucune action accueil enregistrée pour le moment.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    </div>

</main>

<div
    id="accueil-new-demand-toast"
    class="pointer-events-none fixed right-4 top-24 z-50 w-[min(92vw,360px)] transition-all duration-300 ease-out"
    style="opacity: 0; transform: translateY(8px); visibility: hidden;"
    aria-live="polite"
    aria-atomic="true"
>
    <div class="flex items-start gap-3 rounded-2xl border border-sky-100 bg-white/95 px-4 py-3 text-navy shadow-[0_18px_55px_rgba(28,32,61,0.18)] backdrop-blur">
        <span class="mt-0.5 inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,#3996d3_0%,#1c203d_100%)] text-white">
            <svg class="icon-svg text-base" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M4 8.5A2.5 2.5 0 0 1 6.5 6h11A2.5 2.5 0 0 1 20 8.5v7A2.5 2.5 0 0 1 17.5 18h-11A2.5 2.5 0 0 1 4 15.5v-7Z" stroke="currentColor" stroke-width="1.8"/>
                <path d="m6 9 6 4 6-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>
        <div>
            <p class="text-sm font-semibold">Nouvelle demande reçue</p>
            <p id="accueil-new-demand-toast-message" class="mt-0.5 text-xs leading-5 text-neutral-500">
                Vous avez une nouvelle demande à traiter.
            </p>
        </div>
    </div>
</div>

<div
    id="accueil-delivery-toast"
    class="pointer-events-none fixed right-4 top-44 z-50 w-[min(92vw,360px)] transition-all duration-300 ease-out"
    style="opacity: 0; transform: translateY(8px); visibility: hidden;"
    aria-live="polite"
    aria-atomic="true"
>
    <div id="accueil-delivery-toast-card" class="flex items-start gap-3 rounded-2xl border bg-white/95 px-4 py-3 text-navy shadow-[0_18px_55px_rgba(28,32,61,0.18)] backdrop-blur">
        <span id="accueil-delivery-toast-icon" class="mt-0.5 inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl text-white"></span>
        <div>
            <p id="accueil-delivery-toast-title" class="text-sm font-semibold"></p>
            <p id="accueil-delivery-toast-message" class="mt-0.5 text-xs leading-5 text-neutral-500"></p>
        </div>
    </div>
</div>

<!-- ═══════════════════ FOOTER ═══════════════════ -->
<footer class="border-t border-neutral-200 bg-white mt-8">
    <div class="max-w-screen-xl mx-auto px-4 sm:px-6 py-4 flex flex-col items-center justify-between gap-1 text-center text-xs text-neutral-400 sm:flex-row sm:text-left">
        <span>© {{ date('Y') }} Agence Nationale des Bourses du Gabon</span>
        <span class="text-neutral-400 font-medium">Constructeur d'avenir</span>
    </div>
</footer>

<script>
(function () {
    const eyeSvg = `
        <svg class="icon-svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M2.8 12s3.2-5.5 9.2-5.5S21.2 12 21.2 12s-3.2 5.5-9.2 5.5S2.8 12 2.8 12Z" stroke="currentColor" stroke-width="2"/>
            <circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="2"/>
        </svg>`;
    const eyeOffSvg = `
        <svg class="icon-svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M3.5 3.5 20.5 20.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <path d="M9.9 6.8A10.3 10.3 0 0 1 12 6.5c6 0 9.2 5.5 9.2 5.5a16.7 16.7 0 0 1-3.6 4.2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <path d="M6.3 9.2A16.3 16.3 0 0 0 2.8 12s3.2 5.5 9.2 5.5c1.3 0 2.5-.2 3.6-.6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>`;

    const liveContent = document.getElementById('accueil-live-content');
    const kpiContent = document.getElementById('accueil-kpi-content');
    const newDemandToast = document.getElementById('accueil-new-demand-toast');
    const newDemandToastMessage = document.getElementById('accueil-new-demand-toast-message');
    const deliveryToast = document.getElementById('accueil-delivery-toast');
    const deliveryToastCard = document.getElementById('accueil-delivery-toast-card');
    const deliveryToastIcon = document.getElementById('accueil-delivery-toast-icon');
    const deliveryToastTitle = document.getElementById('accueil-delivery-toast-title');
    const deliveryToastMessage = document.getElementById('accueil-delivery-toast-message');
    let newDemandToastTimeout = null;
    let deliveryToastTimeout = null;
    const deliverySuccessIcon = '<svg class="icon-svg text-base" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 8.5A2.5 2.5 0 0 1 6.5 6h11A2.5 2.5 0 0 1 20 8.5v7A2.5 2.5 0 0 1 17.5 18h-11A2.5 2.5 0 0 1 4 15.5v-7Z" stroke="currentColor" stroke-width="1.8"/><path d="m6 9 6 4 6-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="m10.4 13.1 1.7 1.7 3.4-3.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    const deliveryErrorIcon = '<svg class="icon-svg text-base" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8"/><path d="M12 8v5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M12 16.8h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';

    const getNewDemandCount = (node) => Number(node?.dataset?.newDemandCount || 0);

    const showNewDemandNotification = (difference = 1) => {
        if (!newDemandToast) return;

        if (newDemandToastMessage) {
            newDemandToastMessage.textContent = difference > 1
                ? `Vous avez ${difference} nouvelles demandes à traiter.`
                : 'Vous avez une nouvelle demande à traiter.';
        }

        window.clearTimeout(newDemandToastTimeout);
        newDemandToast.style.visibility = 'visible';
        newDemandToast.style.opacity = '1';
        newDemandToast.style.transform = 'translateY(0)';

        newDemandToastTimeout = window.setTimeout(() => {
            newDemandToast.style.opacity = '0';
            newDemandToast.style.transform = 'translateY(8px)';
            window.setTimeout(() => {
                if (newDemandToast.style.opacity === '0') {
                    newDemandToast.style.visibility = 'hidden';
                }
            }, 320);
        }, 4500);
    };

    const showDeliveryNotification = (kind, title, message) => {
        if (!deliveryToast || !deliveryToastCard || !deliveryToastIcon || !deliveryToastTitle || !deliveryToastMessage) {
            return;
        }

        if (kind === 'success') {
            deliveryToastCard.className = 'flex items-start gap-3 rounded-2xl border border-green-200 bg-white/95 px-4 py-3 text-navy shadow-[0_18px_55px_rgba(28,32,61,0.18)] backdrop-blur';
            deliveryToastIcon.className = 'mt-0.5 inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl bg-leaf text-white';
            deliveryToastIcon.innerHTML = deliverySuccessIcon;
        } else {
            deliveryToastCard.className = 'flex items-start gap-3 rounded-2xl border border-red-200 bg-white/95 px-4 py-3 text-navy shadow-[0_18px_55px_rgba(28,32,61,0.18)] backdrop-blur';
            deliveryToastIcon.className = 'mt-0.5 inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl bg-red-500 text-white';
            deliveryToastIcon.innerHTML = deliveryErrorIcon;
        }

        deliveryToastTitle.textContent = title;
        deliveryToastMessage.textContent = message;

        window.clearTimeout(deliveryToastTimeout);
        deliveryToast.style.visibility = 'visible';
        deliveryToast.style.opacity = '1';
        deliveryToast.style.transform = 'translateY(0)';

        deliveryToastTimeout = window.setTimeout(() => {
            deliveryToast.style.opacity = '0';
            deliveryToast.style.transform = 'translateY(8px)';
            window.setTimeout(() => {
                if (deliveryToast.style.opacity === '0') {
                    deliveryToast.style.visibility = 'hidden';
                }
            }, 320);
        }, 4500);
    };

    const getVisibleNewDemandIds = (root) => Array.from(root?.querySelectorAll('[data-new-demand-row][data-demand-id]') || [])
        .map((row) => String(row.dataset.demandId || '').trim())
        .filter(Boolean);

    const countAddedVisibleNewDemands = (currentRoot, nextRoot) => {
        const currentIds = new Set(getVisibleNewDemandIds(currentRoot));

        return getVisibleNewDemandIds(nextRoot).filter((id) => !currentIds.has(id)).length;
    };

    const getDeliveryStateMap = (root) => {
        const map = new Map();
        (root?.querySelectorAll('[data-delivery-id][data-delivery-state]') || []).forEach((row) => {
            map.set(String(row.dataset.deliveryId || ''), {
                state: String(row.dataset.deliveryState || 'idle'),
                label: String(row.dataset.deliveryLabel || '').trim(),
            });
        });

        return map;
    };

    const summarizeDeliveryTransitions = (currentRoot, nextRoot) => {
        const currentStates = getDeliveryStateMap(currentRoot);
        const nextStates = getDeliveryStateMap(nextRoot);
        const delivered = [];
        const failed = [];

        currentStates.forEach((entry, id) => {
            if (entry.state !== 'pending') {
                return;
            }

            const nextEntry = nextStates.get(id);
            if (!nextEntry || nextEntry.state === 'sent') {
                delivered.push(entry.label || id);
                return;
            }

            if (nextEntry.state === 'failed') {
                failed.push(nextEntry.label || entry.label || id);
            }
        });

        return { delivered, failed };
    };

    const animateCounters = (root = document) => {
        root.querySelectorAll('[data-countup]:not([data-countup-ready])').forEach((node) => {
            node.dataset.countupReady = '1';
            const target = Number(node.getAttribute('data-countup') || 0);
            const duration = 700;
            const startTime = performance.now();

            const tick = (now) => {
                const progress = Math.min((now - startTime) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                node.textContent = Math.round(target * eased).toLocaleString('fr-FR');
                if (progress < 1) {
                    requestAnimationFrame(tick);
                }
            };

            requestAnimationFrame(tick);
        });
    };

    const initAffectationForms = (root = document) => {
        root.querySelectorAll('.affectation-form:not([data-affectation-ready])').forEach((form) => {
            form.dataset.affectationReady = '1';
            const dirSel = form.querySelector('.direction-select');
            const svcSel = form.querySelector('.service-select');
            if (!dirSel || !svcSel) return;
            const options = Array.from(svcSel.querySelectorAll('option[data-direction-id]'));
            const refresh = () => {
                const val = dirSel.value;
                svcSel.value = '';
                options.forEach((option) => {
                    option.hidden = val !== '' && option.dataset.directionId !== val;
                });
            };
            dirSel.addEventListener('change', refresh);
            refresh();
        });
    };

    const initFormRefreshLocks = (root = document) => {
        root.querySelectorAll('form:not([data-refresh-form-ready])').forEach((form) => {
            form.dataset.refreshFormReady = '1';
            form.addEventListener('input', () => {
                form.dataset.refreshDirty = '1';
            });
            form.addEventListener('change', () => {
                form.dataset.refreshDirty = '1';
            });
            form.addEventListener('submit', () => {
                if (liveContent && liveContent.contains(form)) {
                    liveContent.dataset.refreshLocked = '1';
                }
            });
        });
    };

    const initGlobalDropGuard = () => {
        if (window.accueilDropGuardReady) return;
        window.accueilDropGuardReady = true;
        document.addEventListener('dragover', (event) => {
            event.preventDefault();
        });

        document.addEventListener('drop', (event) => {
            event.preventDefault();
        });
    };

    const formatBytes = (bytes) => {
        if (!Number.isFinite(bytes) || bytes <= 0) return '';
        if (bytes < 1024 * 1024) {
            return `${Math.max(1, Math.round(bytes / 1024))} Ko`;
        }

        return `${(bytes / (1024 * 1024)).toFixed(1).replace('.', ',')} Mo`;
    };

    const initUploadWidgets = (root = document) => {
        root.querySelectorAll('.anbg-upload-widget:not([data-upload-ready])').forEach((widget) => {
            widget.dataset.uploadReady = '1';
            const input = widget.querySelector('[data-upload-input]');
            const dropzone = widget.querySelector('[data-upload-dropzone]');
            const list = widget.querySelector('[data-upload-list]');
            if (!input || !dropzone || !list) return;

            const renderFiles = () => {
                const files = Array.from(input.files || []);
                list.innerHTML = '';

                if (!files.length) {
                    list.classList.add('hidden');
                    return;
                }

                list.classList.remove('hidden');
                files.forEach((file) => {
                    const item = document.createElement('div');
                    item.className = 'anbg-file-chip';
                    item.innerHTML = `
                        <svg class="icon-svg text-sky text-[13px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M8 3.5h5l4 4V18a2 2 0 0 1-2 2H8A2 2 0 0 1 6 18V5.5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            <path d="M13 3.5V8h4.5" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        </svg>
                        <strong>${file.name}</strong>
                        <span class="text-neutral-500">${formatBytes(file.size)}</span>
                    `;
                    list.appendChild(item);
                });
            };

            dropzone.addEventListener('click', () => input.click());
            dropzone.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    input.click();
                }
            });
            input.addEventListener('change', renderFiles);
            dropzone.addEventListener('dragover', (event) => {
                event.preventDefault();
                dropzone.classList.add('is-dragover');
            });
            dropzone.addEventListener('dragleave', () => {
                dropzone.classList.remove('is-dragover');
            });
            dropzone.addEventListener('drop', (event) => {
                event.preventDefault();
                dropzone.classList.remove('is-dragover');
                const files = event.dataTransfer?.files;
                if (!files || !files.length) return;
                const transfer = new DataTransfer();
                Array.from(files).forEach((file) => transfer.items.add(file));
                input.files = transfer.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
                renderFiles();
            });
        });
    };

    const initViewToggles = (root = document) => {
        root.querySelectorAll('.view-toggle:not([data-toggle-ready])').forEach((btn) => {
            btn.dataset.toggleReady = '1';
            btn.addEventListener('click', () => {
                const row = document.getElementById(btn.dataset.target);
                if (!row) return;
                const isOpen = row.style.display !== 'none';
                row.style.display = isOpen ? 'none' : '';
                btn.setAttribute('aria-expanded', String(!isOpen));
                const iconWrap = btn.querySelector('.toggle-icon');
                const label = Array.from(btn.querySelectorAll('span')).find((span) => !span.classList.contains('toggle-icon'));
                if (isOpen) {
                    row.querySelectorAll('form').forEach((form) => {
                        delete form.dataset.refreshDirty;
                    });
                    if (iconWrap) iconWrap.innerHTML = eyeSvg;
                    if (label) label.textContent = 'Voir';
                } else {
                    if (iconWrap) iconWrap.innerHTML = eyeOffSvg;
                    if (label) label.textContent = 'Masquer';
                }
            });
        });
    };

    const initAccueilInteractions = (root = document) => {
        animateCounters(root);
        initAffectationForms(root);
        initFormRefreshLocks(root);
        initGlobalDropGuard();
        initUploadWidgets(root);
        initViewToggles(root);
    };

    const hasFocusedControl = () => {
        const active = document.activeElement;
        if (!active || !liveContent || !liveContent.contains(active)) return false;

        return ['INPUT', 'TEXTAREA', 'SELECT', 'BUTTON'].includes(active.tagName) || active.isContentEditable;
    };

    const hasOpenDetail = () => {
        if (!liveContent) return false;

        return Array.from(liveContent.querySelectorAll('.detail-row')).some((row) => row.style.display !== 'none');
    };

    const hasDirtyForm = () => {
        if (!liveContent) return false;

        return Array.from(liveContent.querySelectorAll('form')).some((form) => form.dataset.refreshDirty === '1');
    };

    const shouldSkipRefresh = (isRefreshing) => {
        if (!liveContent || isRefreshing || document.hidden || liveContent.dataset.refreshLocked === '1') {
            return true;
        }

        return hasFocusedControl() || hasOpenDetail() || hasDirtyForm();
    };

    const initAccueilAutoRefresh = () => {
        if (!liveContent || liveContent.dataset.autoRefreshReady === '1') return;
        liveContent.dataset.autoRefreshReady = '1';

        let isRefreshing = false;
        const interval = Math.max(Number(liveContent.dataset.refreshInterval || 20000), 10000);

        const refreshTables = async () => {
            if (shouldSkipRefresh(isRefreshing)) return;
            isRefreshing = true;

            try {
                const url = new URL(liveContent.dataset.refreshUrl || window.location.href, window.location.origin);
                url.searchParams.set('_accueil_refresh', Date.now().toString());

                const response = await fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-Accueil-Refresh': 'tables',
                    },
                    cache: 'no-store',
                });

                if (!response.ok) return;

                const html = await response.text();
                const nextDocument = new DOMParser().parseFromString(html, 'text/html');
                const nextKpiContent = nextDocument.getElementById('accueil-kpi-content');
                const nextContent = nextDocument.getElementById('accueil-live-content');
                let newDemandDifference = nextContent ? countAddedVisibleNewDemands(liveContent, nextContent) : 0;
                const deliveryTransitions = nextContent ? summarizeDeliveryTransitions(liveContent, nextContent) : { delivered: [], failed: [] };

                if (nextKpiContent && kpiContent) {
                    const currentNewDemandCount = getNewDemandCount(kpiContent);
                    const nextNewDemandCount = getNewDemandCount(nextKpiContent);
                    newDemandDifference = Math.max(newDemandDifference, nextNewDemandCount - currentNewDemandCount);
                    kpiContent.innerHTML = nextKpiContent.innerHTML;
                    kpiContent.dataset.newDemandCount = String(nextNewDemandCount);
                    animateCounters(kpiContent);
                }

                if (!nextContent) return;

                liveContent.innerHTML = nextContent.innerHTML;
                initAccueilInteractions(liveContent);

                if (newDemandDifference > 0) {
                    showNewDemandNotification(newDemandDifference);
                }

                if (deliveryTransitions.delivered.length > 0) {
                    const firstLabel = deliveryTransitions.delivered[0];
                    showDeliveryNotification(
                        'success',
                        deliveryTransitions.delivered.length > 1 ? 'Reponses envoyees' : 'Reponse envoyee',
                        deliveryTransitions.delivered.length > 1
                            ? `${deliveryTransitions.delivered.length} reponses ont ete confirmees par le systeme.`
                            : `La demande ${firstLabel} a bien ete envoyee a l'usager.`
                    );
                }

                if (deliveryTransitions.failed.length > 0) {
                    const firstLabel = deliveryTransitions.failed[0];
                    showDeliveryNotification(
                        'error',
                        'Echec d envoi',
                        deliveryTransitions.failed.length > 1
                            ? `${deliveryTransitions.failed.length} envois ont echoue. Une relance est possible.`
                            : `L'envoi pour la demande ${firstLabel} a echoue. Vous pouvez relancer la reponse.`
                    );
                }
            } catch (error) {
                console.warn('Rafraîchissement accueil interrompu.', error);
            } finally {
                isRefreshing = false;
            }
        };

        window.setInterval(refreshTables, interval);
        window.addEventListener('focus', refreshTables);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) refreshTables();
        });
    };

    initAccueilInteractions(document);
    initAccueilAutoRefresh();
})();
</script>
</body>
</html>
