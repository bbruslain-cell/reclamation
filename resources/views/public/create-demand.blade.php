<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>Plateforme réclamations - ANBG</title>
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

        /* Spinner animation */
        @keyframes spin {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }
        .animate-spin { animation: spin 0.8s linear infinite; }

    </style>
</head>
<body class="bg-white font-sans text-navy-500 antialiased">

<div id="app" v-cloak>
    <!-- NAVBAR -->
    <header class="bg-navy-500 sticky top-0 z-50 shadow-md">
        <div class="max-w-screen-xl 2xl:max-w-screen-2xl mx-auto px-4 sm:px-6 2xl:px-10 h-16 flex items-center justify-between">
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
                            Plateforme Reclamation
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
        </div>
    </header>

    <!-- HERO BANNER -->
    <section class="bg-navy-500 border-b border-navy-600 pb-10 pt-8">
        <div class="max-w-screen-xl 2xl:max-w-screen-2xl mx-auto px-4 sm:px-6 2xl:px-10">
            <div class="flex items-start gap-4">
                <div class="mt-1 w-10 h-10 rounded-full bg-sky-400/20 flex items-center justify-center flex-shrink-0 opacity-0 animate-[fade-in-up_0.6s_ease-out_forwards]">
                    <svg class="icon-svg text-sky-300 text-base" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v9A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5v-9Z" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M4 13h4l1.5 2h5L16 13h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div>
                    <h1 class="font-medium text-white text-xl sm:text-2xl leading-snug mb-1 tracking-wide opacity-0 animate-fade-in-up">
                        Bienvenue sur la Plateforme Réclamation de l'ANBG.
                    </h1>
                    <p class="text-sky-200 text-sm sm:text-base leading-relaxed max-w-2xl 2xl:max-w-4xl font-light opacity-0 animate-fade-in-up-1">
                        Soumettez votre réclamation relative à la gestion des bourses. Chaque demande est enregistrée et traitée par nos équipes. Une réponse vous sera apportée dans un délai maximum de<strong class="text-white font-medium"> 72 heures ouvrées.</strong>
                    </p>
                    <!-- Badges délai -->
                </div>
            </div>
        </div>
    </section>

    <!-- MAIN CONTENT -->
    <main class="max-w-screen-xl 2xl:max-w-screen-2xl mx-auto px-4 sm:px-6 2xl:px-10 py-8">

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

        <!-- FORM CARD -->
        <div class="bg-white rounded-2xl shadow-card overflow-hidden border border-neutral-100">

            <!-- Card header -->
            <div class="px-6 py-5 border-b border-navy-600 flex flex-col items-center justify-center text-center bg-navy-500">
           <div class="w-full">
               <h2 class="font-semibold text-white text-2xl tracking-wide">
                   Remplissez le Formulaire Relative à Votre Réclamation.
               </h2>
                      <p class="text-sky-50 text-sm mt-1">
                   Les champs marqués d'un <span class="text-gold-300 font-bold">*</span> sont obligatoires.
               </p>
           </div>
        </div>

            <!-- ✅ MODIF 1 : @submit="handleSubmit" ajouté sur le formulaire -->
            <form method="post" action="/reclamations" enctype="multipart/form-data"
                  @submit="handleSubmit" class="divide-y divide-neutral-100">

                @csrf

                <!-- SECTION 1 : Identité -->
                <div class="px-6 py-6">
                    <div class="flex items-center gap-2 mb-5">
                        <div class="w-6 h-6 rounded-full bg-navy-500 flex items-center justify-center text-white text-xs flex-shrink-0">1</div>
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

                        <!-- Statut usager -->
                        <div class="field-group">
                            <label for="statut_usager" class="block text-sm font-medium text-navy-500 mb-1.5">
                                Statut <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <select
                                    id="statut_usager" name="statut_usager"
                                    v-model="form.statut_usager" required
                                    @change="handleStatutUsagerChange"
                                    class="w-full px-3.5 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy-500 appearance-none
                                           focus:outline-none focus:border-sky-400 focus:bg-white focus:shadow-input-focus transition-all duration-150 cursor-pointer"
                                >
                                    <option value="">- Sélectionner -</option>
                                    <option value="Élève">Élève</option>
                                    <option value="Étudiant">Étudiant</option>
                                    <option value="Parent / Tuteur">Parent / Tuteur</option>
                                    <option value="Enseignant">Enseignant</option>
                                    <option value="Professionnel">Professionnel</option>
                                    <option value="Autre">Autre</option>
                                </select>
                                <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-neutral-400 pointer-events-none text-xs">
                                    <svg class="icon-svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="m7 10 5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            </div>
                            <p v-if="errors.statut_usager" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/><path d="M12 8v5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="16.8" r="1" fill="currentColor"/></svg> @{{ errors.statut_usager }}
                            </p>
                        </div>

                        <!-- Pays -->
                        <div class="field-group">
                            <label for="pays" class="block text-sm font-medium text-navy-500 mb-1.5">
                                Pays <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-neutral-400 text-sm pointer-events-none">
                                    <svg class="icon-svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8"/>
                                        <path d="M4 12h16M12 4a12 12 0 0 1 0 16M12 4a12 12 0 0 0 0 16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    </svg>
                                </span>
                                <input
                                    id="pays" name="pays" type="text"
                                    v-model="form.pays" @input="clearError('pays')" required autocomplete="country-name"
                                    placeholder="Ex. : Gabon"
                                    class="w-full pl-9 pr-3.5 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy-500 placeholder-neutral-400
                                           focus:outline-none focus:border-sky-400 focus:bg-white focus:shadow-input-focus transition-all duration-150"
                                >
                            </div>
                            <p v-if="errors.pays" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/><path d="M12 8v5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="16.8" r="1" fill="currentColor"/></svg> @{{ errors.pays }}
                            </p>
                        </div>

                        <!-- Établissement -->
                        <div class="field-group">
                            <div class="flex items-center justify-between gap-3 mb-1.5">
                                <label for="etablissement" class="block text-sm font-medium text-navy-500">
                                    Établissement <span v-if="requiresEtablissement" class="text-red-500">*</span>
                                </label>
                                <span
                                    class="text-[11px] font-medium"
                                    :class="requiresEtablissement ? 'text-amber-600' : 'text-neutral-400'"
                                >
                                    @{{ requiresEtablissement ? 'Obligatoire pour les élèves et étudiants' : 'Optionnel pour les autres statuts' }}
                                </span>
                            </div>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-neutral-400 text-sm pointer-events-none">
                                    <svg class="icon-svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M4.5 9 12 5l7.5 4v8.5a1 1 0 0 1-1 1h-13a1 1 0 0 1-1-1V9Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                        <path d="M9 18.5v-5h6v5M8 10.5h.01M12 10.5h.01M16 10.5h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <input
                                    id="etablissement" name="etablissement" type="text"
                                    v-model="form.etablissement" @input="clearError('etablissement')"
                                    :required="requiresEtablissement" autocomplete="organization"
                                    placeholder="Nom de votre établissement"
                                    class="w-full pl-9 pr-3.5 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy-500 placeholder-neutral-400
                                           focus:outline-none focus:border-sky-400 focus:bg-white focus:shadow-input-focus transition-all duration-150"
                                >
                            </div>
                            <p v-if="errors.etablissement" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/><path d="M12 8v5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="16.8" r="1" fill="currentColor"/></svg> @{{ errors.etablissement }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2 : Nature de la demande -->
                <div class="px-6 py-6">
                    <div class="flex items-center gap-2 mb-5">
                        <div class="w-6 h-6 rounded-full bg-navy-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">2</div>
                        <h3 class="font-medium text-neutral-700 text-xs uppercase tracking-widest">Nature de la demande</h3>
                    </div>

                    <input type="hidden" name="type_demande_code" value="reclamation">

                    <div class="grid grid-cols-1 gap-4 mb-4">
                        <div class="field-group">
                            <label class="block text-sm font-medium text-navy-500 mb-1.5">
                                Type de demande
                            </label>
                            <div class="w-full px-3.5 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-100 text-navy-500 font-medium">
                                Réclamation
                            </div>
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
                                    <option value="">- Sélectionner -</option>
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

                <!-- SECTION 3 : Message -->
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

                <!-- SECTION 4 : Pièce jointe -->
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
                            <span class="text-[11px] bg-neutral-200 text-neutral-500 px-2 py-0.5 rounded-md font-medium">Max 3,5 Mo</span>
                        </div>
                    </div>

                    <input id="piece_jointe" type="file" name="piece_jointe" accept=".pdf,.jpg,.jpeg,.png" class="hidden">

                    <!-- File preview -->
                    <div id="file-list" class="mt-3 space-y-2"></div>
                    <p v-if="errors.piece_jointe" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                        <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/><path d="M12 8v5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="16.8" r="1" fill="currentColor"/></svg> @{{ errors.piece_jointe }}
                    </p>
                </div>

                <!-- SECTION 5 : Consentement + Soumettre -->
                <div class="px-6 py-6 bg-neutral-50/60">

                    <!-- Consentement RGPD -->
                    <div class="flex items-start gap-3 mb-6 p-4 bg-white border border-neutral-100 rounded-xl shadow-sm">
                        <input
                            type="checkbox" id="consentement" name="consentement"
                            v-model="form.consentement" @change="clearError('consentement')" required
                        >
                        <label for="consentement" class="text-sm text-navy-500 leading-relaxed cursor-pointer">
                            <span class="font-medium">J'accepte le traitement de mes données personnelles</span>, pour la gestion de ma demande par l'ANBG.
                            <span class="text-neutral-400 text-xs block mt-0.5">Ces informations ne seront utilisées qu'à des fins de traitement et de réponse à votre sollicitation.</span>
                        </label>
                    </div>
                    <p v-if="errors.consentement" class="-mt-4 mb-4 text-xs text-red-500 flex items-center gap-1">
                        <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/><path d="M12 8v5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="16.8" r="1" fill="currentColor"/></svg> @{{ errors.consentement }}
                    </p>

                    <!-- Submit button -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <p class="text-xs text-neutral-400 flex items-center gap-1.5"></p>

                        <!-- ✅ MODIF 2 : bouton avec état submitting -->
                        <button
                            type="submit"
                            :disabled="submitting"
                            :class="submitting
                                ? 'bg-neutral-300 text-neutral-500 cursor-not-allowed'
                                : 'bg-sky-500 hover:bg-navy-600 text-white shadow-btn hover:shadow-btn-hover'"
                            class="inline-flex items-center justify-center gap-2.5 font-medium text-sm px-8 py-3 rounded-xl transition-all duration-200 w-full sm:w-auto"
                        >
                            <!-- Spinner pendant l'envoi -->
                            <svg v-if="submitting" class="icon-svg animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" stroke-dasharray="28" stroke-dashoffset="10"/>
                            </svg>
                            <!-- Icône normale -->
                            <svg v-else class="icon-svg text-xs" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 11.5 19 5l-4.8 14-3.1-5.1L4 11.5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                <path d="M10.8 13.8 19 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            @{{ submitting ? 'Envoi en cours…' : 'Soumettre ma demande' }}
                        </button>
                    </div>
                </div>

            </form>
        </div>

    </main>

    <!-- FOOTER -->
    <footer class="mt-8 border-t border-neutral-100 bg-white">
        <div class="max-w-screen-xl 2xl:max-w-screen-2xl mx-auto px-4 sm:px-6 2xl:px-10 py-5 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-neutral-400">
            <span>© {{ date('Y') }} Agence Nationale des Bourses du Gabon.</span>
            <span>Constructeur d'avenir</span>
        </div>
    </footer>

</div><!-- #app -->

<script>
    const categoriesByType = {
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
                    statut_usager:     @json(old('statut_usager', '')),
                    pays:              @json(old('pays', '')),
                    etablissement:     @json(old('etablissement', '')),
                    type_demande_code: 'reclamation',
                    categorie:         @json(old('categorie', '')),
                    objet:             @json(old('objet', '')),
                    message:           @json(old('message', '')),
                    consentement:      @json((bool) old('consentement', false)),
                },
                errors: {
                    nom:               @json($errors->first('nom')),
                    prenom:            @json($errors->first('prenom')),
                    email:             @json($errors->first('email')),
                    statut_usager:     @json($errors->first('statut_usager')),
                    pays:              @json($errors->first('pays')),
                    etablissement:     @json($errors->first('etablissement')),
                    objet:             @json($errors->first('objet')),
                    message:           @json($errors->first('message')),
                    piece_jointe:      @json($errors->first('piece_jointe')),
                    consentement:      @json($errors->first('consentement')),
                },
                categories:  [],
                // ✅ MODIF 2 : état de soumission
                submitting: false,
            };
        },
        mounted() {
            this.form.type_demande_code = 'reclamation';
            this.fillCategories();
            this.setupDropzone();
        },
        computed: {
            requiresEtablissement() {
                return ['Élève', 'Étudiant'].includes(this.form.statut_usager);
            }
        },
        methods: {
            fillCategories() {
                this.categories = categoriesByType.reclamation;
                if (!this.categories.includes(this.form.categorie)) {
                    this.form.categorie = '';
                }
            },
            handleStatutUsagerChange() {
                this.clearError('statut_usager');
                if (!this.requiresEtablissement) {
                    this.clearError('etablissement');
                }
            },
            applyCategory() {
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

            // ✅ MODIF 2 : protection double soumission
            handleSubmit(e) {
                if (this.submitting) {
                    e.preventDefault();
                    return;
                }
                this.submitting = true;
                // Réactiver après 8s en cas d'erreur serveur ou timeout
                setTimeout(() => { this.submitting = false; }, 8000);
            },

            setupDropzone() {
                document.addEventListener('dragover', e => e.preventDefault());
                document.addEventListener('drop', e => e.preventDefault());

                const dz    = document.getElementById('dropzone');
                const input = document.getElementById('piece_jointe');
                const list  = document.getElementById('file-list');
                if (!dz || !input) return;

                // ✅ MODIF 1 : constantes de validation fichier
                const MAX_SIZE = 3.5 * 1024 * 1024; // 3,5 Mo en octets
                const ALLOWED_TYPES = ['application/pdf', 'image/jpeg', 'image/png'];

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

                // ✅ MODIF 1 : validation taille et type avant acceptation du fichier
                const applyFiles = files => {
                    if (!files || files.length === 0) return;

                    const file = files[0];

                    // Vérification de la taille
                    if (file.size > MAX_SIZE) {
                        this.errors.piece_jointe = `Fichier trop lourd (${(file.size / 1024 / 1024).toFixed(1)} Mo). Maximum autorisé : 3,5 Mo.`;
                        return;
                    }

                    // Vérification du type MIME
                    if (!ALLOWED_TYPES.includes(file.type)) {
                        this.errors.piece_jointe = 'Format non accepté. Utilisez PDF, JPG ou PNG.';
                        return;
                    }

                    // Fichier valide : effacer l'erreur et affecter
                    this.errors.piece_jointe = null;
                    const dt = new DataTransfer();
                    dt.items.add(file);
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

                // ✅ MODIF 1 : input change passe aussi par applyFiles (couvre le clic)
                input.addEventListener('change', () => applyFiles(input.files));
            }
        }
    });
    app.mount('#app');
</script>
</body>
</html>
