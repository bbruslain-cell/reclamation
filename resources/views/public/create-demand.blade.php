<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Plateforme réclamations — ANBG</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        /* ── Charte ANBG ── */
                        navy: {
                            50:  '#ecedf3',
                            100: '#c5c7d9',
                            200: '#9ea1bf',
                            300: '#777ba5',
                            400: '#50558b',
                            500: '#1c203d', /* Principal #1c203d */
                            600: '#181b35',
                            700: '#14162c',
                            800: '#101223',
                            900: '#0c0d1a',
                        },
                        sky: {
                            50:  '#eaf4fb',
                            100: '#cae4f5',
                            200: '#9acbeb',
                            300: '#65b0e0',
                            400: '#3996d3', /* Accent #3996d3 */
                            500: '#2e7fb8',
                            600: '#246898',
                            700: '#1a5178',
                            800: '#103a58',
                            900: '#072338',
                        },
                        leaf: {
                            400: '#8fc043', /* Vert charte */
                            500: '#76a335',
                        },
                        gold: {
                            300: '#f8e932', /* Jaune charte */
                            400: '#f9b13c', /* Orange/flamme charte */
                            500: '#e09428',
                        },
                        neutral: {
                            50:  '#f8f9fa',
                            100: '#f1f3f5',
                            200: '#e9ecef',
                            300: '#dee2e6',
                            400: '#adb5bd',
                            500: '#6c757d',
                            600: '#495057',
                            700: '#343a40',
                            800: '#212529',
                            900: '#111317',
                        }
                    },
                    fontFamily: {
                        sans:    ['"Inter"', 'system-ui', 'sans-serif'],
                        display: ['"Inter"', 'system-ui', 'sans-serif'],
                    },
                    boxShadow: {
                        'card':        '0 1px 3px rgba(28,32,61,0.06), 0 8px 24px rgba(28,32,61,0.09)',
                        'btn':         '0 4px 16px rgba(57,150,211,0.32)',
                        'btn-hover':   '0 6px 24px rgba(57,150,211,0.46)',
                        'input-focus': '0 0 0 3px rgba(57,150,211,0.20)',
                    },
                    backgroundImage: {
                        'hero-pattern': "url(\"data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%233996d3' fill-opacity='0.07'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E\")",
                    },
                    keyframes: {
                        'fade-in-up': {
                            '0%': { opacity: '0', transform: 'translateY(15px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        }
                    },
                    animation: {
                        'fade-in-up': 'fade-in-up 0.6s ease-out forwards',
                        'fade-in-up-1': 'fade-in-up 0.6s ease-out 0.1s forwards',
                        'fade-in-up-2': 'fade-in-up 0.6s ease-out 0.2s forwards',
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/vue@3.4.21/dist/vue.global.prod.js"></script>
    <style>
        [v-cloak] { display: none; }

        /* Transitions */
        .field-group { transition: opacity 0.3s ease, transform 0.3s ease; }

        /* Custom file dropzone */
        .dropzone-active { border-color: #3996d3 !important; background-color: #edf6fc !important; }

        /* Custom checkbox */
        input[type="checkbox"] {
            appearance: none;
            -webkit-appearance: none;
            width: 18px;
            height: 18px;
            border: 2px solid #dee2e6;
            border-radius: 5px;
            background: white;
            cursor: pointer;
            position: relative;
            flex-shrink: 0;
            margin-top: 2px;
            transition: border-color 0.30s, background 0.30s;
        }
        input[type="checkbox"]:checked {
            background: #1c203d;
            border-color: #1c203d;
        }
        input[type="checkbox"]:checked::after {
            content: '';
            position: absolute;
            left: 4px;
            top: 1px;
            width: 6px;
            height: 10px;
            border: 2px solid white;
            border-top: none;
            border-left: none;
            transform: rotate(45deg);
        }
        input[type="checkbox"]:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(57,150,211,0.22);
        }

        /* Progress steps */
        .step-line { flex: 1; height: 2px; background: #e2e8f0; }
        .step-line.done { background: #3996d3; }

        /* Floating label effect on textarea */
        .char-count { font-size: 11px; color: #94a3b8; }
        .icon-svg {
            display: inline-block;
            width: 1.24em;
            height: 1.24em;
            vertical-align: middle;
            flex-shrink: 0;
        }

    </style>
</head>
<body class="bg-neutral-50 font-sans text-navy-500 antialiased">

<div id="app" v-cloak>
    <!-- ═══════════════════════ NAVBAR ═══════════════════════ -->
    <header class="bg-navy-500 sticky top-0 z-50 shadow-md">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <!-- Logo + Nom -->
            <div class="flex items-center gap-3">
                <!-- Cartouche blanc logo  -->
                <a href="https://www.anbg-ga.com/" class="flex items-center gap-2 sm:gap-3 group">
                    <div class="bg-white rounded-md px-1.5 py-1 sm:px-2 flex items-center justify-center transition-transform group-hover:scale-105">
                        <img src="{{ asset('Logo_anbg.png') }}" alt="ANBG" class="h-8 sm:h-10 w-auto object-contain block">
                    </div>
                    <div class="flex flex-col justify-center">
                        <span class="text-white font-medium text-xs sm:text-sm leading-tight tracking-wide group-hover:underline max-w-[150px] sm:max-w-none">
                            Agence Nationale des Bourses du Gabon
                        </span>
                        <div class="text-sky-300 text-[9px] sm:text-xs font-light tracking-wider uppercase mt-0.5 sm:mt-0">
                            Plateforme Réclamations
                        </div>
                    </div> 
                </a>
            </div>
            <!-- Réseaux sociaux -->
            <div class="flex items-center gap-3">
                <a href="https://www.facebook.com/anbggabon" target="_blank" rel="noopener"
                   class="w-8 h-8 rounded-lg bg-white/10 hover:bg-sky-400/30 flex items-center justify-center text-white transition-colors duration-150">
                    <svg class="icon-svg text-xs" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M13.5 21v-7h2.3l.4-2.8h-2.7V9.4c0-.8.2-1.3 1.4-1.3H16V5.6c-.6-.1-1.3-.1-2-.1-2 0-3.4 1.2-3.4 3.5v2.2H8.5V14h2.1v7h2.9Z"/>
                    </svg>
                </a>
        </div>
    </header>

    <!-- ═══════════════════════ HERO BANNER ═══════════════════════ -->
    <section class="bg-navy-500 bg-hero-pattern border-b border-navy-600 pb-10 pt-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="flex items-start gap-4">
                <div class="mt-1 w-10 h-10 rounded-full bg-sky-400/20 flex items-center justify-center flex-shrink-0 opacity-0 animate-[fade-in-up_0.6s_ease-out_forwards]">
                    <svg class="icon-svg text-sky-300 text-base" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v9A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5v-9Z" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M4 13h4l1.5 2h5L16 13h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div>
                    <h1 class="font-medium text-white text-xl sm:text-2xl leading-snug mb-1 tracking-wide opacity-0 animate-fade-in-up">
                        Bienvenue sur la Plateforme Réclamations de l'ANBG
                    </h1>
                    <p class="text-sky-200 text-sm sm:text-base leading-relaxed max-w-2xl font-light opacity-0 animate-fade-in-up-1">
                        Soumettez vos préoccupations, réclamations ou  demandes d'informations relatives à la gestion des bourses. Chaque requête est enregistrée et traitée par nos équipes. Une réponse vous sera apportée dans un delai maximum de<strong class="text-white font-medium"> 72 heures ouvrées.</strong>.
                    </p>
                    <!-- Badges SLA -->
                    <div class="mt-3 flex flex-wrap gap-2 opacity-0 animate-fade-in-up-2">
                        <span class="inline-flex items-center gap-1.5 bg-sky-400/15 border border-sky-400/25 text-sky-100 text-xs font-medium px-3 py-1 rounded-full hover:bg-sky-400/20 transition-colors">
                            <svg class="icon-svg text-sky-300 text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/>
                                <path d="M12 8v4l2.8 1.8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg> Réponse sous 72h
                        </span>
                        <span class="inline-flex items-center gap-1.5 bg-leaf-400/15 border border-leaf-400/25 text-green-100 text-xs font-medium px-3 py-1 rounded-full hover:bg-leaf-400/20 transition-colors">
                            <svg class="icon-svg text-leaf-400 text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 4.5 18 7v4.4c0 3.3-2.1 6.2-6 8.1-3.9-1.9-6-4.8-6-8.1V7l6-2.5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                <path d="m9.5 12 1.7 1.7 3.3-3.3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg> Données protégées (RGPD)
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════ MAIN CONTENT ═══════════════════════ -->
    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-8">

        <!-- Success Message -->
        @if(session('success'))
        <div class="mb-6 flex items-start gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3.5 rounded-xl shadow-sm">
            <svg class="icon-svg text-emerald-500 mt-0.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/>
                <path d="m8.5 12 2.3 2.3L15.5 9.7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
        @endif

        <!-- Global errors -->
        @if($errors->any())
        <div class="mb-6 flex items-start gap-3 bg-red-50 border border-red-200 text-red-800 px-4 py-3.5 rounded-xl shadow-sm">
            <svg class="icon-svg text-red-500 mt-0.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/>
                <path d="M12 8v5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <circle cx="12" cy="16.8" r="1" fill="currentColor"/>
            </svg>
            <div class="text-sm">
                <p class="font-medium mb-1">Veuillez corriger les erreurs suivantes :</p>
                @foreach($errors->all() as $error)
                    <div class="flex items-center gap-1.5"><span class="w-1 h-1 rounded-full bg-red-400 inline-block"></span> {{ $error }}</div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- ── FORM CARD ── -->
        <div class="bg-white rounded-2xl shadow-card overflow-hidden border border-neutral-100">

            <!-- Card header -->
            <div class="px-6 py-5 border-b border-neutral-100 flex items-center justify-between bg-gradient-to-r from-neutral-50 to-white">
                <div>
                    <h2 class="font-medium text-navy-500 text-base tracking-wide">Formulaire usager</h2>
                    <p class="text-neutral-500 text-xs mt-0.5">Les champs marqués d'un <span class="text-red-500 font-bold">*</span> sont obligatoires.</p>
                </div>
                <div class="hidden sm:flex items-center gap-1.5 text-xs text-neutral-400 bg-neutral-50 border border-neutral-200 px-3 py-1.5 rounded-full">
                    <svg class="icon-svg text-neutral-400 text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <rect x="6" y="11" width="12" height="8" rx="2" stroke="currentColor" stroke-width="2"/>
                        <path d="M9 11V8.5A3 3 0 0 1 12 5.5a3 3 0 0 1 3 3V11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Formulaire sécurisé
                </div>
            </div>

            <!-- FORM -->
            <form method="post" action="/reclamations" enctype="multipart/form-data" class="divide-y divide-neutral-100">

                @csrf

                <!-- ── SECTION 1 : Identité ── -->
                <div class="px-6 py-6">
                    <div class="flex items-center gap-2 mb-5">
                        <div class="w-6 h-6 rounded-full bg-navy-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">1</div>
                        <h3 class="font-medium text-neutral-700 text-xs uppercase tracking-widest">Vos informations</h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Nom -->
                        <div class="field-group">
                            <label for="nom" class="block text-sm font-medium text-navy-500 mb-1.5">
                                Nom <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="nom" name="nom" type="text"
                                v-model="form.nom" @input="clearError('nom')" required autocomplete="family-name"
                                placeholder="Votre nom de famille"
                                class="w-full px-3.5 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy-500 placeholder-neutral-400
                                       focus:outline-none focus:border-sky-400 focus:bg-white focus:shadow-input-focus transition-all duration-150"
                            >
                            <p v-if="errors.nom" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/><path d="M12 8v5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="16.8" r="1" fill="currentColor"/></svg> @{{ errors.nom }}
                            </p>
                        </div>

                        <!-- Prénom -->
                        <div class="field-group">
                            <label for="prenom" class="block text-sm font-medium text-navy-500 mb-1.5">
                                Prénom <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="prenom" name="prenom" type="text"
                                v-model="form.prenom" @input="clearError('prenom')" required autocomplete="given-name"
                                placeholder="Votre prénom"
                                class="w-full px-3.5 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy-500 placeholder-neutral-400
                                       focus:outline-none focus:border-sky-400 focus:bg-white focus:shadow-input-focus transition-all duration-150"
                            >
                            <p v-if="errors.prenom" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/><path d="M12 8v5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="16.8" r="1" fill="currentColor"/></svg> @{{ errors.prenom }}
                            </p>
                        </div>

                        <!-- Email -->
                        <div class="field-group">
                            <label for="email" class="block text-sm font-medium text-navy-500 mb-1.5">
                                Adresse email <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-neutral-400 text-sm pointer-events-none">
                                    <svg class="icon-svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M4 8.5A2.5 2.5 0 0 1 6.5 6h11A2.5 2.5 0 0 1 20 8.5v7A2.5 2.5 0 0 1 17.5 18h-11A2.5 2.5 0 0 1 4 15.5v-7Z" stroke="currentColor" stroke-width="1.8"/>
                                        <path d="m6 9 6 4 6-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <input
                                    id="email" name="email" type="email"
                                    v-model="form.email" @input="clearError('email')" required autocomplete="email"
                                    placeholder="prenom.nom@email.com"
                                    class="w-full pl-9 pr-3.5 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy-500 placeholder-neutral-400
                                           focus:outline-none focus:border-sky-400 focus:bg-white focus:shadow-input-focus transition-all duration-150"
                                >
                            </div>
                            <p v-if="errors.email" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/><path d="M12 8v5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="16.8" r="1" fill="currentColor"/></svg> @{{ errors.email }}
                            </p>
                        </div>

                        <!-- Téléphone -->
                        <div class="field-group">
                            <label for="telephone" class="block text-sm font-medium text-navy-500 mb-1.5">
                                Téléphone <span class="text-neutral-400 font-normal text-xs">(optionnel)</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-neutral-400 text-sm pointer-events-none">
                                    <svg class="icon-svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M7.5 4.8h2.1l1.1 3.1-1.6 1.6a13 13 0 0 0 5.4 5.4l1.6-1.6 3.1 1.1v2.1a1.8 1.8 0 0 1-1.8 1.8h-.7C10.2 18.3 5.7 13.8 5.7 8.3v-.7A1.8 1.8 0 0 1 7.5 4.8Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <input
                                    id="telephone" name="telephone" type="tel"
                                    v-model="form.telephone" autocomplete="tel"
                                    placeholder="+241 XX XX XX XX"
                                    class="w-full pl-9 pr-3.5 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy-500 placeholder-neutral-400
                                           focus:outline-none focus:border-sky-400 focus:bg-white focus:shadow-input-focus transition-all duration-150"
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── SECTION 2 : Nature de la demande ── -->
                <div class="px-6 py-6">
                    <div class="flex items-center gap-2 mb-5">
                        <div class="w-6 h-6 rounded-full bg-navy-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">2</div>
                        <h3 class="font-medium text-neutral-700 text-xs uppercase tracking-widest">Nature de la demande</h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <!-- Type de demande -->
                        <div class="field-group">
                            <label for="type_demande_code" class="block text-sm font-medium text-navy-500 mb-1.5">
                                Type de demande <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <select
                                    id="type_demande_code" name="type_demande_code"
                                    v-model="form.type_demande_code" required
                                    @change="fillCategories(); clearError('type_demande_code')"
                                    class="w-full px-3.5 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy-500 appearance-none
                                           focus:outline-none focus:border-sky-400 focus:bg-white focus:shadow-input-focus transition-all duration-150 cursor-pointer"
                                >
                                    <option value="">— Sélectionner —</option>
                                    <option value="demande_information">Demande d'information</option>
                                    <option value="reclamation">Réclamation</option>
                                </select>
                                <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-neutral-400 pointer-events-none text-xs">
                                    <svg class="icon-svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="m7 10 5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            </div>
                            <p class="mt-1.5 text-xs text-neutral-400">Choisissez d'abord le type pour filtrer les catégories.</p>
                            <p v-if="errors.type_demande_code" class="mt-1 text-xs text-red-500 flex items-center gap-1">
                                <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/><path d="M12 8v5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="16.8" r="1" fill="currentColor"/></svg> @{{ errors.type_demande_code }}
                            </p>
                        </div>

                        <!-- Catégorie -->
                        <div class="field-group">
                            <label for="categorie" class="block text-sm font-medium text-navy-500 mb-1.5">
                                Catégorie <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <select
                                    id="categorie" name="categorie"
                                    v-model="form.categorie" required
                                    @change="applyCategory(); clearError('categorie')"
                                    class="w-full px-3.5 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy-500 appearance-none
                                           focus:outline-none focus:border-sky-400 focus:bg-white focus:shadow-input-focus transition-all duration-150 cursor-pointer"
                                >
                                    <option value="">— Sélectionner —</option>
                                    <option v-for="cat in categories" :key="cat" :value="cat">@{{ cat }}</option>
                                </select>
                                <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-neutral-400 pointer-events-none text-xs">
                                    <svg class="icon-svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="m7 10 5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Objet -->
                    <div class="field-group">
                        <label for="objet" class="block text-sm font-medium text-navy-500 mb-1.5">
                            Objet de la demande <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="objet" name="objet" type="text"
                            v-model="form.objet" readonly tabindex="-1" required
                            placeholder="Sélectionnez une catégorie ci-dessus"
                            class="w-full px-3.5 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-100 text-neutral-500 placeholder-neutral-400
                                   cursor-not-allowed transition-all duration-150"
                        >
                        <p v-if="errors.objet" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                            <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/><path d="M12 8v5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="16.8" r="1" fill="currentColor"/></svg> @{{ errors.objet }}
                        </p>
                    </div>
                </div>

                <!-- ── SECTION 3 : Message ── -->
                <div class="px-6 py-6">
                    <div class="flex items-center gap-2 mb-5">
                        <div class="w-6 h-6 rounded-full bg-navy-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">3</div>
                        <h3 class="font-medium text-neutral-700 text-xs uppercase tracking-widest">Détail de votre demande</h3>
                    </div>

                    <div class="field-group">
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="message" class="block text-sm font-medium text-navy-500">
                                Message <span class="text-red-500">*</span>
                            </label>
                            <span class="char-count">@{{ form.message.length }} / 2000 caractères</span>
                        </div>
                        <textarea
                            id="message" name="message"
                            v-model="form.message" @input="clearError('message')" required maxlength="2000"
                            placeholder="Décrivez votre situation avec le plus de précisions possible"
                            rows="6"
                            class="w-full px-3.5 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy-500 placeholder-neutral-400 resize-y
                                   focus:outline-none focus:border-sky-400 focus:bg-white focus:shadow-input-focus transition-all duration-150"
                        ></textarea>
                        <p v-if="errors.message" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                            <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/><path d="M12 8v5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="16.8" r="1" fill="currentColor"/></svg> @{{ errors.message }}
                        </p>
                    </div>
                </div>

                <!-- ── SECTION 4 : Pièce jointe ── -->
                <div class="px-6 py-6">
                    <div class="flex items-center gap-2 mb-5">
                        <div class="w-6 h-6 rounded-full bg-navy-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">4</div>
                        <h3 class="font-medium text-neutral-700 text-xs uppercase tracking-widest">Pièce jointe <span class="text-neutral-400 font-normal text-xs">(optionnelle)</span></h3>
                    </div>

                    <!-- Dropzone -->
                    <div
                        id="dropzone" role="button" tabindex="0"
                        class="border-2 border-dashed border-neutral-200 rounded-xl px-6 py-8 text-center cursor-pointer transition-all duration-150 hover:border-sky-400 hover:bg-sky-50 bg-neutral-50"
                    >
                        <div class="w-12 h-12 rounded-full bg-sky-50 border border-sky-100 flex items-center justify-center mx-auto mb-3">
                            <svg class="icon-svg text-sky-400 text-lg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M8 16.5h8a3.5 3.5 0 0 0 .4-7A5.2 5.2 0 0 0 6.1 10 3.2 3.2 0 0 0 8 16.5Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="m12 8.5 2.5 2.5M12 8.5 9.5 11M12 8.5v7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-navy-500 mb-1">Glissez-déposez votre fichier ici</p>
                        <p class="text-xs text-neutral-400">ou <span class="text-sky-400 font-medium underline underline-offset-2">cliquez pour parcourir</span></p>
                        <div class="mt-3 flex flex-wrap justify-center gap-2">
                            <span class="text-[11px] bg-neutral-100 text-neutral-500 px-2 py-0.5 rounded-md font-medium">PDF</span>
                            <span class="text-[11px] bg-neutral-100 text-neutral-500 px-2 py-0.5 rounded-md font-medium">JPG</span>
                            <span class="text-[11px] bg-neutral-100 text-neutral-500 px-2 py-0.5 rounded-md font-medium">PNG</span>
                            <span class="text-[11px] bg-neutral-200 text-neutral-500 px-2 py-0.5 rounded-md font-medium">Max 2 Mo</span>
                        </div>
                    </div>

                    <input id="piece_jointe" type="file" name="piece_jointe" accept=".pdf,.jpg,.jpeg,.png" class="hidden">

                    <!-- File preview -->
                    <div id="file-list" class="mt-3 space-y-2"></div>
                    <p v-if="errors.piece_jointe" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                        <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/><path d="M12 8v5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="16.8" r="1" fill="currentColor"/></svg> @{{ errors.piece_jointe }}
                    </p>
                </div>

                <!-- ── SECTION 5 : Consentement + Soumettre ── -->
                <div class="px-6 py-6 bg-neutral-50/60">

                    <!-- Consentement RGPD -->
                    <div class="flex items-start gap-3 mb-6 p-4 bg-white border border-neutral-100 rounded-xl shadow-sm">
                        <input
                            type="checkbox" id="consentement" name="consentement"
                            v-model="form.consentement" @change="clearError('consentement')" required
                        >
                        <label for="consentement" class="text-sm text-navy-500 leading-relaxed cursor-pointer">
                            <span class="font-medium">J'accepte le traitement de mes données personnelles</span> conformément au RGPD, pour la gestion de ma demande par l'ANBG.
                            <span class="text-neutral-400 text-xs block mt-0.5">Ces informations ne seront utilisées qu'à des fins de traitement et de réponse à votre sollicitation.</span>
                        </label>
                    </div>
                    <p v-if="errors.consentement" class="-mt-4 mb-4 text-xs text-red-500 flex items-center gap-1">
                        <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/><path d="M12 8v5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="16.8" r="1" fill="currentColor"/></svg> @{{ errors.consentement }}
                    </p>

                    <!-- Submit button -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <p class="text-xs text-neutral-400 flex items-center gap-1.5">
                            <svg class="icon-svg text-neutral-300" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 4.5 18 7v4.4c0 3.3-2.1 6.2-6 8.1-3.9-1.9-6-4.8-6-8.1V7l6-2.5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            </svg>
                            Formulaire protégé — transmission chiffrée HTTPS
                        </p>
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center gap-2.5 bg-sky-500 hover:bg-navy-600 text-white font-medium text-sm px-8 py-3 rounded-xl shadow-btn hover:shadow-btn-hover transition-all duration-200 w-full sm:w-auto"
                        >
                            <svg class="icon-svg text-xs" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 11.5 19 5l-4.8 14-3.1-5.1L4 11.5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                <path d="M10.8 13.8 19 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            Soumettre ma demande
                        </button>
                    </div>
                </div>

            </form>
        </div>

       
        
    </main>

    <!-- ═══════════════════════ FOOTER ═══════════════════════ -->
    <footer class="mt-8 border-t border-neutral-100 bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-5 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-neutral-400">
            <span>© {{ date('Y') }} Agence Nationale des Bourses du Gabon.</span>
            <a href="/login" >
                Constructeur d'avenir </i>
            </a>
        </div>
    </footer>

</div><!-- #app -->

<script>
    const legacyCategoriesByType = {
        demande_information: [
            "Demande de billets (rapatriement / stage) et remboursements",
            "Nombre de mails",
            "Demande de modifications des informations dans les comptes eBourse",
            "Demande de traitement et/ou validation des dossiers de bourses",
            "Demande d'information sur le paiement de la bourse",
            "Demande d'informations sur la réorientation de bourse",
            "Demande d'informations sur le traitement des dossiers par la commission technique",
            "Demande d'infos création compte ou de demande de bourse",
            "Demande d'infos sur les bourses de coopération",
            "Demande d'infos sur les critères d'attributions ou de maintien de bourses",
            "Demande d'infos sur le changement de catégorie",
            "Identifiant et mot de passe oublié",
            "Demande d'informations diverses"
        ],
        reclamation: [
            "Retard de paiement",
            "Montant incorrect",
            "Changement de compte bancaire",
            "Perte d'accès / mot de passe",
            "Erreur d'état civil",
            "Autre réclamation"
        ],
        autre: ["Autre"]
    };

    const categoriesByType = {
        demande_information: [
            "Demande de billets (rapatriement et stage) et remboursements",
            "Demande de modifications des informations dans les comptes eBourse",
            "Demande de traitement et/ou validation des dossiers de bourses",
            "Demande d'information sur le paiement de la bourse",
            "Demande d'informations sur la reorientation de bourse",
            "Demande d'informations sur le traitement des dossiers par la commission technique",
            "Demande d'infos creation compte ou de demande de bourse",
            "Demande d'infos sur les bourses de cooperation",
            "Demande d'infos sur les criteres d'attributions ou de maintien de bourses",
            "Demande d'infos sur le changement de categorie",
            "Identifiant et mot de passe oublie",
            "Demande d'informations diverses"
        ],
        reclamation: [
            "Demande de modification d'attestation d'attribution de bourse ou maintien",
            "Reclamation du paiement des frais de scolarite",
            "Reclamation sur les RIB non valides sur eBourse",
            "Recours apres deliberation de la CT",
            "Reclamation diverses"
        ],
        autre: ["Autre"]
    };

    const app = Vue.createApp({
        data() {
            return {
                form: {
                    nom:               @json(old('nom', '')),
                    prenom:            @json(old('prenom', '')),
                    email:             @json(old('email', '')),
                    telephone:         @json(old('telephone', '')),
                    type_demande_code: @json(old('type_demande_code', '')),
                    categorie:         @json(old('categorie', '')),
                    objet:             @json(old('objet', '')),
                    message:           @json(old('message', '')),
                    consentement:      @json((bool) old('consentement', false)),
                },
                errors: {
                    nom:               @json($errors->first('nom')),
                    prenom:            @json($errors->first('prenom')),
                    email:             @json($errors->first('email')),
                    type_demande_code: @json($errors->first('type_demande_code')),
                    objet:             @json($errors->first('objet')),
                    message:           @json($errors->first('message')),
                    piece_jointe:      @json($errors->first('piece_jointe')),
                    consentement:      @json($errors->first('consentement')),
                },
                categories: []
            };
        },
        mounted() {
            this.fillCategories();
            this.setupDropzone();
        },
        methods: {
            fillCategories() {
                const key = (this.form.type_demande_code || 'autre').toLowerCase();
                this.categories = categoriesByType[key] || categoriesByType.autre;
                if (!this.categories.includes(this.form.categorie)) {
                    this.form.categorie = '';
                }
            },
            applyCategory() {
                // L'objet prend strictement la valeur de la catégorie choisie
                this.form.objet = this.form.categorie;
                if (this.form.objet) {
                    this.clearError('objet');
                }
            },
            clearError(field) {
                if (this.errors[field]) {
                    this.errors[field] = null;
                }
            },
            setupDropzone() {
                document.addEventListener('dragover', e => e.preventDefault());
                document.addEventListener('drop', e => e.preventDefault());

                const dz    = document.getElementById('dropzone');
                const input = document.getElementById('piece_jointe');
                const list  = document.getElementById('file-list');
                if (!dz || !input) return;

                const refreshList = () => {
                    list.innerHTML = '';
                    Array.from(input.files || []).forEach(file => {
                        const ext  = file.name.split('.').pop().toUpperCase();
                        const size = (file.size / 1024).toFixed(0);
                        const div  = document.createElement('div');
                        div.className = 'flex items-center gap-2.5 bg-sky-50 border border-sky-100 text-navy-500 text-xs px-3 py-2 rounded-lg';
                        div.innerHTML = `
                            <span class="w-7 h-7 rounded bg-sky-100 flex items-center justify-center font-medium text-[10px] text-sky-400 flex-shrink-0">${ext}</span>
                            <span class="flex-1 font-medium truncate">${file.name}</span>
                            <span class="text-sky-400">${size} Ko</span>
                        `;
                        list.appendChild(div);
                    });
                };

                const applyFiles = files => {
                    const dt = new DataTransfer();
                    if (files && files.length > 0) dt.items.add(files[0]);
                    input.files = dt.files;
                    refreshList();
                };

                dz.addEventListener('click',    () => input.click());
                dz.addEventListener('keypress', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); } });
                dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('dropzone-active'); });
                dz.addEventListener('dragleave',    () => dz.classList.remove('dropzone-active'));
                dz.addEventListener('drop', e => {
                    e.preventDefault();
                    dz.classList.remove('dropzone-active');
                    if (e.dataTransfer?.files?.length) applyFiles(e.dataTransfer.files);
                });
                input.addEventListener('change', refreshList);
            }
        }
    });
    app.mount('#app');
</script>
</body>
</html>
