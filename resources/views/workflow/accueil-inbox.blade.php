<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accueil — Traitement des demandes | ANBG</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy:    { DEFAULT: '#1c203d', 50: '#ecedf3', 100: '#c5c7d9', 600: '#181b35', 700: '#14162c' },
                        sky:     { DEFAULT: '#3996d3', 50: '#eaf4fb', 100: '#cae4f5', 200: '#9acbeb', 600: '#2e7fb8' },
                        leaf:    { DEFAULT: '#8fc043', 50: '#f3f9ea' },
                        gold:    { DEFAULT: '#f9b13c', 50: '#fff8ee' },
                        neutral: {
                            50: '#f8f9fa', 100: '#f1f3f5', 200: '#e9ecef',
                            300: '#dee2e6', 400: '#adb5bd', 500: '#6c757d',
                            600: '#495057', 700: '#343a40', 800: '#212529',
                        }
                    },
                    fontFamily: {
                        sans: ['"Roboto"', 'system-ui', 'sans-serif'],
                    },
                    boxShadow: {
                        'card':  '0 1px 3px rgba(28,32,61,0.05), 0 4px 16px rgba(28,32,61,0.07)',
                        'badge': '0 2px 6px rgba(28,32,61,0.10)',
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
        /* Input / select focus ring */
        .field:focus {
            outline: none;
            border-color: #3996d3;
            box-shadow: 0 0 0 3px rgba(57,150,211,0.18);
            background: #fff;
        }
        /* Table row hover */
        .demand-row:hover td { background: #f8fafd; }
        /* Detail row animation */
        .detail-row { transition: opacity 0.18s ease; }
        /* Scrollbar fin */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #f1f3f5; }
        ::-webkit-scrollbar-thumb { background: #c5c7d9; border-radius: 99px; }
    </style>
</head>
<body class="bg-neutral-100 text-navy font-sans min-h-screen">

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
                    <i class="fas fa-user text-sky-100 text-[10px]"></i>
                </div>
                <span class="text-white text-xs font-medium">{{ $actor->prenom }} {{ $actor->nom }}</span>
            </div>
            <form method="post" action="/logout">
                @csrf
                <button type="submit"
                    class="flex items-center gap-1.5 bg-white/10 hover:bg-red-500/20 border border-white/15 hover:border-red-400/40 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition-all duration-150">
                    <i class="fas fa-right-from-bracket text-[10px]"></i>
                    <span class="hidden sm:inline">Déconnexion</span>
                </button>
            </form>
        </div>
    </div>
</header>

<!-- ═══════════════════ MAIN ═══════════════════ -->
<main class="max-w-screen-xl mx-auto px-4 sm:px-6 py-6 space-y-5">

    <!-- Toasts -->
    @if(session('success'))
    <div class="flex items-start gap-3 bg-leaf-50 border border-leaf/30 text-green-800 px-4 py-3 rounded-xl text-sm shadow-card">
        <i class="fas fa-circle-check text-leaf mt-0.5 flex-shrink-0"></i>
        <span>{{ session('success') }}</span>
    </div>
    @endif
    @if(session('error'))
    <div class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm shadow-card">
        <i class="fas fa-circle-exclamation text-red-500 mt-0.5 flex-shrink-0"></i>
        <span>{{ session('error') }}</span>
    </div>
    @endif
    @if($errors->any())
    <div class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm shadow-card">
        <i class="fas fa-triangle-exclamation text-red-500 mt-0.5 flex-shrink-0"></i>
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

    <!-- ── STAT RAPIDE ── -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl shadow-card border border-neutral-200 px-5 py-4 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-sky-50 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-inbox text-sky text-base"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-navy leading-none">{{ $nouvelles->total() }}</p>
                <p class="text-xs text-neutral-400 mt-0.5">Demandes à traiter</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-card border border-neutral-200 px-5 py-4 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-gold-50 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-triangle-exclamation text-gold text-base"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-navy leading-none">
                    {{ $nouvelles->getCollection()->where('alerte_accueil','orange')->count() }}
                </p>
                <p class="text-xs text-neutral-400 mt-0.5">À risque</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-card border border-neutral-200 px-5 py-4 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-red-50 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-exclamation text-red-500 text-base"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-navy leading-none">
                    {{ $nouvelles->getCollection()->where('alerte_accueil','rouge')->count() }}
                </p>
                <p class="text-xs text-neutral-400 mt-0.5">En retard</p>
            </div>
        </div>
    </div>

    <!-- ── FILTRES ── -->
    <div class="bg-white rounded-xl shadow-card border border-neutral-200 overflow-hidden">
        <div class="px-5 py-3.5 border-b border-neutral-100 flex items-center gap-2">
            <i class="fas fa-sliders text-sky text-sm"></i>
            <h2 class="text-sm font-medium text-navy">Filtres et tri</h2>
        </div>
        <div class="px-5 py-4">
            <form method="get" class="grid grid-cols-2 sm:grid-cols-4 gap-3">

                <div class="col-span-2 sm:col-span-4">
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400 text-sm pointer-events-none">
                            <i class="fas fa-magnifying-glass"></i>
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
                    <select name="sort_by" class="field w-full px-3 py-2.5 border border-neutral-200 rounded-lg text-sm bg-neutral-50 text-navy appearance-none transition-all duration-150">
                        <option value="date_soumission" {{ $sortBy==='date_soumission'?'selected':'' }}>Date soumission</option>
                        <option value="numero_suivi"    {{ $sortBy==='numero_suivi'?'selected':'' }}>Numéro suivi</option>
                        <option value="type_demande"    {{ $sortBy==='type_demande'?'selected':'' }}>Type demande</option>
                        <option value="direction"       {{ $sortBy==='direction'?'selected':'' }}>Direction</option>
                        <option value="alerte_accueil"  {{ $sortBy==='alerte_accueil'?'selected':'' }}>Alerte accueil</option>
                    </select>
                </div>
                <div>
                    <select name="sort_dir" class="field w-full px-3 py-2.5 border border-neutral-200 rounded-lg text-sm bg-neutral-50 text-navy appearance-none transition-all duration-150">
                        <option value="desc" {{ $sortDir==='desc'?'selected':'' }}>↓ Descendant</option>
                        <option value="asc"  {{ $sortDir==='asc'?'selected':'' }}>↑ Ascendant</option>
                    </select>
                </div>

                <div class="col-span-2 flex justify-end">
                    <button type="submit"
                        class="inline-flex items-center gap-2 bg-navy hover:bg-navy-600 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition-colors duration-150">
                        <i class="fas fa-filter text-xs"></i>
                        Appliquer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ── TABLE DEMANDES ── -->
    <div class="bg-white rounded-xl shadow-card border border-neutral-200 overflow-hidden">
        <div class="px-5 py-3.5 border-b border-neutral-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fas fa-list-check text-sky text-sm"></i>
                <h2 class="text-sm font-medium text-navy">Demandes nouvelles</h2>
                <span class="ml-1 bg-sky-50 text-sky text-xs font-medium px-2 py-0.5 rounded-full border border-sky-100">
                    {{ $nouvelles->total() }}
                </span>
            </div>
            <p class="text-xs text-neutral-400 hidden sm:block">Affecter à une direction/service ou répondre directement.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-neutral-50 border-b border-neutral-100">
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
                    <tr class="demand-row transition-colors duration-100">
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
                                <i class="fas fa-eye text-[10px]"></i>
                                <span>Voir</span>
                            </button>
                        </td>
                    </tr>

                    <!-- Ligne détail (cachée par défaut) -->
                    <tr id="detail-{{ $demande->id_demande }}" class="detail-row" style="display:none;">
                        <td colspan="6" class="px-4 py-4 bg-neutral-50 border-b border-neutral-100">
                            @php $pieces = $piecesByDemand->get($demande->id_demande, collect()); @endphp
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                                <!-- Colonne gauche : détail demande -->
                                <div class="bg-white border border-neutral-200 rounded-xl p-4 space-y-3">
                                    <h4 class="text-xs font-medium text-neutral-500 uppercase tracking-wider border-b border-neutral-100 pb-2">
                                        <i class="fas fa-file-lines text-sky mr-1.5"></i>Détail de la demande
                                    </h4>

                                    <div>
                                        <p class="text-xs text-neutral-400 mb-0.5">Objet</p>
                                        <p class="text-sm font-medium text-navy">{{ $demande->objet }}</p>
                                    </div>

                                    <div>
                                        <p class="text-xs text-neutral-400 mb-1">Message</p>
                                        <div class="bg-neutral-50 border border-neutral-200 rounded-lg p-3 text-sm text-neutral-700 leading-relaxed whitespace-pre-wrap max-h-40 overflow-y-auto">{{ $demande->message }}</div>
                                    </div>

                                    <div class="flex flex-wrap gap-x-6 gap-y-1">
                                        <div>
                                            <p class="text-xs text-neutral-400">Email</p>
                                            <p class="text-sm text-navy">{{ $demande->usager_email ?? '—' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-neutral-400">Téléphone</p>
                                            <p class="text-sm text-navy">{{ $demande->usager_telephone ?? '—' }}</p>
                                        </div>
                                    </div>

                                    @if($pieces->isNotEmpty())
                                    <div>
                                        <p class="text-xs text-neutral-400 mb-1.5">Pièces jointes</p>
                                        <div class="space-y-1.5">
                                            @foreach($pieces as $piece)
                                            <a href="/pieces-jointes/{{ $piece->id_piece_jointe }}" target="_blank" rel="noopener"
                                               class="flex items-center gap-2 bg-sky-50 border border-sky-100 text-sky text-xs font-medium px-3 py-2 rounded-lg hover:bg-sky-100 transition-colors duration-150">
                                                <i class="fas fa-paperclip text-[10px]"></i>
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
                                            <i class="fas fa-arrow-right-to-bracket text-sky mr-1.5"></i>Affectation Direction → Service
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
                                                <i class="fas fa-paper-plane text-xs"></i>
                                                Affecter
                                            </button>
                                        </form>
                                    </div>

                                    <!-- Box réponse directe -->
                                    <div class="bg-white border border-neutral-200 rounded-xl p-4">
                                        <h4 class="text-xs font-medium text-neutral-500 uppercase tracking-wider border-b border-neutral-100 pb-2 mb-3">
                                            <i class="fas fa-reply text-sky mr-1.5"></i>Réponse directe à l'usager
                                        </h4>
                                        @if($demande->type_demande_code === 'demande_information')
                                            <form method="post" action="/accueil/demandes/{{ $demande->id_demande }}/reponse-directe" enctype="multipart/form-data" class="space-y-2.5">
                                                @csrf
                                                @method('put')
                                                <textarea name="contenu_reponse" required rows="4"
                                                    placeholder="Saisir la réponse directe…"
                                                    class="field w-full px-3 py-2.5 border border-neutral-200 rounded-lg text-sm bg-neutral-50 text-navy placeholder-neutral-400 resize-y transition-all duration-150"></textarea>
                                                <div class="relative">
                                                    <label class="flex flex-col items-center justify-center gap-1.5 border-2 border-dashed border-neutral-200 hover:border-sky rounded-lg p-3 cursor-pointer transition-colors duration-150 bg-neutral-50 hover:bg-sky-50">
                                                        <i class="fas fa-cloud-arrow-up text-neutral-400 text-base"></i>
                                                        <span class="text-xs text-neutral-400">Pièces jointes (optionnel)</span>
                                                        <input type="file" name="pieces_jointes[]" multiple class="hidden">
                                                    </label>
                                                </div>
                                                <button type="submit"
                                                    class="w-full flex items-center justify-center gap-2 bg-sky hover:bg-sky-600 text-white text-sm font-medium py-2.5 rounded-lg transition-colors duration-150">
                                                    <i class="fas fa-envelope-circle-check text-xs"></i>
                                                    Envoyer maintenant
                                                </button>
                                            </form>
                                        @else
                                            <div class="flex items-start gap-2.5 bg-neutral-50 border border-neutral-200 rounded-lg px-3 py-2.5">
                                                <i class="fas fa-lock text-neutral-400 text-xs mt-0.5 flex-shrink-0"></i>
                                                <p class="text-xs text-neutral-500 leading-relaxed">
                                                    Réponse directe désactivée — cette demande doit être traitée par une direction/service.
                                                </p>
                                            </div>
                                        @endif
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
                                    <i class="fas fa-inbox text-xl"></i>
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
        <div class="px-5 py-4 border-t border-neutral-100 bg-neutral-50">
            {{ $nouvelles->links() }}
        </div>
        @endif
    </div>

</main>

<!-- ═══════════════════ FOOTER ═══════════════════ -->
<footer class="border-t border-neutral-200 bg-white mt-8">
    <div class="max-w-screen-xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between text-xs text-neutral-400">
        <span>© {{ date('Y') }} Agence Nationale des Bourses du Gabon</span>
        <span class="text-neutral-400 font-medium">Constructeur d'avenir</span>
    </div>
</footer>

<script>
(function () {
    /* ── Filtre services selon direction ── */
    const forms = document.querySelectorAll('.affectation-form');
    forms.forEach((form) => {
        const dirSel = form.querySelector('.direction-select');
        const svcSel = form.querySelector('.service-select');
        if (!dirSel || !svcSel) return;
        const options = Array.from(svcSel.querySelectorAll('option[data-direction-id]'));
        const refresh = () => {
            const val = dirSel.value;
            svcSel.value = '';
            options.forEach(o => { o.hidden = val !== '' && o.dataset.directionId !== val; });
        };
        dirSel.addEventListener('change', refresh);
        refresh();
    });

    /* ── Toggle détail ── */
    document.querySelectorAll('.view-toggle').forEach((btn) => {
        btn.addEventListener('click', () => {
            const row = document.getElementById(btn.dataset.target);
            if (!row) return;
            const isOpen = row.style.display !== 'none';
            row.style.display = isOpen ? 'none' : '';
            btn.setAttribute('aria-expanded', String(!isOpen));
            const icon  = btn.querySelector('i');
            const label = btn.querySelector('span');
            if (isOpen) {
                icon.className  = 'fas fa-eye text-[10px]';
                label.textContent = 'Voir';
            } else {
                icon.className  = 'fas fa-eye-slash text-[10px]';
                label.textContent = 'Masquer';
            }
        });
    });
})();
</script>
</body>
</html>
