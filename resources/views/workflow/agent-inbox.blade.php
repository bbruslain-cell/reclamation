<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agent — Fenêtre partagée 48h | ANBG</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
        .field:focus {
            outline: none;
            border-color: #3996d3;
            box-shadow: 0 0 0 3px rgba(57,150,211,0.18);
            background: #fff;
        }
        .trow:hover td { background: #f8fafd; }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #f1f3f5; }
        ::-webkit-scrollbar-thumb { background: #c5c7d9; border-radius: 99px; }
    </style>
</head>

<body class="bg-neutral-100 font-sans text-navy min-h-screen">

    {{-- ═══════ TOPBAR ═══════ --}}
    <header class="bg-navy sticky top-0 z-50 shadow-md">
        <div class="max-w-screen-xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="bg-white rounded-lg px-2.5 py-1.5 flex-shrink-0">
                    <img src="/Logo_anbg.png" alt="ANBG" class="h-9 w-auto object-contain block">
                </div>
                <span class="hidden sm:block text-white text-xs font-medium tracking-wider uppercase">AGENCE NATIONALE DES BOURSES DU GABON</span>
            </div>
            <div class="flex items-center gap-3">
                @if(($canPilotage ?? false))
                <a href="/pilotage"
                    class="hidden sm:inline-flex items-center gap-1.5 bg-white/10 hover:bg-white/20 border border-white/15 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition-all duration-150">
                    <i class="fas fa-chart-line text-[10px]"></i>
                    <span>Pilotage</span>
                </a>
                @endif
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

    {{-- ═══════ HERO ═══════ --}}
    <section class="bg-navy border-b border-white/10 pb-8 pt-6">
        <div class="max-w-screen-xl mx-auto px-4 sm:px-6">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-full bg-sky/20 flex items-center justify-center flex-shrink-0 mt-1">
                    <i class="fas fa-headset text-sky-300 text-sm"></i>
                </div>
                <div>
                    <h1 class="text-white text-xl font-medium leading-snug mb-1">Traitement dans la fenêtre 48h</h1>
                    <p class="text-sky-200 text-sm font-light leading-relaxed max-w-2xl">
                        Votre réponse s'inscrit dans la même fenêtre de
                        <strong class="text-white font-medium">48h</strong>
                        ouverte après l'affectation par l'accueil. Le chef de service garde le pilotage du délai global.
                    </p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-1.5 bg-sky/15 border border-sky/25 text-sky-100 text-xs font-medium px-3 py-1 rounded-full">
                            <i class="fas fa-clock text-sky text-[10px]"></i> Fenêtre partagée 48h
                        </span>
                        <span class="inline-flex items-center gap-1.5 bg-leaf/15 border border-leaf/25 text-green-100 text-xs font-medium px-3 py-1 rounded-full">
                            <i class="fas fa-pen text-leaf text-[10px]"></i> Réponse finale agent
                        </span>
                        <span class="inline-flex items-center gap-1.5 bg-gold/15 border border-gold/25 text-yellow-100 text-xs font-medium px-3 py-1 rounded-full">
                            <i class="fas fa-circle-check text-gold text-[10px]"></i> Clôture après envoi
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════ MAIN ═══════ --}}
    <main class="max-w-screen-xl mx-auto px-4 sm:px-6 py-6 space-y-5">

        {{-- Toasts --}}
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

        {{-- Barre de recherche --}}
        <div class="bg-white rounded-xl shadow-card border border-neutral-200 px-5 py-4">
            <form method="get" class="flex gap-3 items-end">
                <div class="flex-1 relative">
                    <i class="fas fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400 text-sm pointer-events-none"></i>
                    <input name="search" value="{{ $search }}"
                        placeholder="Recherche : numéro suivi, objet, usager…"
                        class="field w-full pl-9 pr-4 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy placeholder-neutral-400 transition-all duration-150">
                </div>
                <button type="submit"
                    class="inline-flex items-center gap-2 bg-navy hover:bg-navy-600 text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-colors duration-150">
                    <i class="fas fa-filter text-xs"></i> Filtrer
                </button>
            </form>
        </div>

        {{-- ══════════════════════════════════════
             TABLE DEMANDES
        ══════════════════════════════════════ --}}
        <div class="bg-white rounded-2xl shadow-card border border-neutral-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-neutral-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fas fa-list-check text-sky text-sm"></i>
                    <h2 class="text-sm font-medium text-navy">Mes demandes affectées</h2>
                    <span class="bg-sky-50 text-sky text-xs font-medium px-2 py-0.5 rounded-full border border-sky-100">
                        {{ $demandes->total() }}
                    </span>
                </div>
                <p class="text-xs text-neutral-400 hidden sm:block">Répondre pour clôturer la demande</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-neutral-50 border-b border-neutral-100">
                            <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">N° Suivi</th>
                            <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">Usager</th>
                            <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">Service</th>
                            <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">Statut</th>
                            <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">Alerte 48h</th>
                            <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">Temps restant 48h</th>
                            <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                    @forelse($demandes as $demande)
                        {{-- Ligne principale --}}
                        <tr class="trow transition-colors duration-100">
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs font-medium text-navy bg-navy-50 px-2 py-1 rounded">
                                    {{ $demande->numero_suivi }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-navy">
                                {{ trim(($demande->usager_prenom ?? '').' '.($demande->usager_nom ?? '')) }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs bg-neutral-100 text-neutral-600 px-2 py-1 rounded-full">
                                    {{ $demande->service_code }} — {{ $demande->service }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs text-neutral-500">{{ $demande->statut }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if($demande->alerte_agent === 'rouge')
                                    <span class="inline-flex items-center gap-1.5 bg-red-50 text-red-700 border border-red-200 text-xs font-medium px-2.5 py-1 rounded-full">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 flex-shrink-0"></span> En retard
                                    </span>
                                @elseif($demande->alerte_agent === 'orange')
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
                                @php
                                    $windowTone = match ($demande->service_window_tone ?? 'neutral') {
                                        'red' => 'text-red-600',
                                        'amber' => 'text-amber-600',
                                        'green' => 'text-green-700',
                                        default => 'text-neutral-500',
                                    };
                                @endphp
                                <div class="text-xs">
                                    <p class="font-medium {{ $windowTone }}">{{ $demande->service_window_label ?? '—' }}</p>
                                    <p class="text-neutral-400 mt-1">{{ $demande->service_window_hint ?? '' }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <button type="button"
                                    class="view-toggle inline-flex items-center gap-1.5 bg-sky-50 hover:bg-sky text-sky hover:text-white border border-sky-200 hover:border-sky text-xs font-medium px-3 py-1.5 rounded-lg transition-all duration-150"
                                    data-target="detail-d-{{ $demande->id_demande }}" aria-expanded="false">
                                    <i class="fas fa-eye text-[10px]"></i>
                                    <span>Voir</span>
                                </button>
                            </td>
                        </tr>

                        {{-- Ligne détail --}}
                        <tr id="detail-d-{{ $demande->id_demande }}" style="display:none;">
                            <td colspan="7" class="px-4 py-4 bg-neutral-50 border-b border-neutral-100">
                                @php $pieces = $piecesByDemand->get($demande->id_demande, collect()); @endphp
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                                    {{-- Détail demande --}}
                                    <div class="bg-white border border-neutral-200 rounded-xl p-4 space-y-3">
                                        <h4 class="text-xs font-medium text-neutral-500 uppercase tracking-wider border-b border-neutral-100 pb-2">
                                            <i class="fas fa-file-lines text-sky mr-1.5"></i>Détail de la demande
                                        </h4>

                                        <div>
                                            <p class="text-xs text-neutral-400 mb-0.5">Objet</p>
                                            <p class="text-sm font-medium text-navy">{{ $demande->objet }}</p>
                                        </div>

                                        <div>
                                            <p class="text-xs text-neutral-400 mb-1">Message complet</p>
                                            <div class="bg-neutral-50 border border-neutral-200 rounded-lg p-3 text-sm text-neutral-700 leading-relaxed whitespace-pre-wrap max-h-44 overflow-y-auto">{{ $demande->message }}</div>
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
                                            <p class="text-xs text-neutral-400 mb-1.5">Pièces jointes usager</p>
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
                                        @else
                                        <p class="text-xs text-neutral-400 italic">Aucune pièce jointe usager.</p>
                                        @endif
                                    </div>

                                    {{-- Formulaire réponse --}}
                                    <div class="bg-white border border-neutral-200 rounded-xl p-4">
                                        <h4 class="text-xs font-medium text-neutral-500 uppercase tracking-wider border-b border-neutral-100 pb-2 mb-3">
                                            <i class="fas fa-pen-to-square text-sky mr-1.5"></i>Rédiger la réponse
                                        </h4>

                                        <div class="flex items-start gap-2 bg-neutral-50 border border-neutral-200 rounded-lg px-3 py-2.5 mb-3">
                                            <i class="fas fa-hourglass-half text-sky text-xs mt-0.5 flex-shrink-0"></i>
                                            <div>
                                                <p class="text-xs font-medium text-navy">{{ $demande->service_window_label ?? '—' }}</p>
                                                <p class="text-[11px] text-neutral-400 mt-0.5">{{ $demande->service_window_hint ?? '' }}</p>
                                            </div>
                                        </div>

                                        <div class="flex items-start gap-2 bg-sky-50 border border-sky-100 rounded-lg px-3 py-2.5 mb-3">
                                            <i class="fas fa-circle-info text-sky text-xs mt-0.5 flex-shrink-0"></i>
                                            <p class="text-xs text-sky-700 leading-relaxed">
                                                Votre réponse finalise le traitement de la demande pour l'usager et clôture automatiquement le dossier.
                                            </p>
                                        </div>

                                        <form method="post" action="/agent/demandes/{{ $demande->id_demande }}/envoyer" enctype="multipart/form-data" class="space-y-3">
                                            @csrf @method('put')

                                            <div>
                                                <label class="block text-xs font-medium text-navy mb-1.5">
                                                    Réponse à l'usager <span class="text-red-500">*</span>
                                                </label>
                                                <textarea name="contenu_reponse" required rows="6"
                                                    placeholder="Rédigez votre réponse finale à l'usager…"
                                                    class="field w-full px-3 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy placeholder-neutral-400 resize-y transition-all duration-150"></textarea>
                                            </div>

                                            <div>
                                                <label class="flex flex-col items-center justify-center gap-1.5 border-2 border-dashed border-neutral-200 hover:border-sky rounded-xl p-4 cursor-pointer transition-colors duration-150 bg-neutral-50 hover:bg-sky-50">
                                                    <i class="fas fa-cloud-arrow-up text-neutral-400 text-lg"></i>
                                                    <span class="text-xs font-medium text-neutral-500">Ajouter des pièces jointes</span>
                                                    <span class="text-[11px] text-neutral-400">PDF, JPG, PNG — max 2 Mo chacun</span>
                                                    <input type="file" name="pieces_jointes[]" multiple class="hidden">
                                                </label>
                                            </div>

                                            <button type="submit"
                                                class="w-full flex items-center justify-center gap-2 bg-navy hover:bg-navy-600 text-white text-sm font-medium py-3 rounded-xl transition-colors duration-150">
                                                <i class="fas fa-envelope-circle-check text-xs"></i>
                                                Envoyer et clôturer
                                            </button>
                                        </form>
                                    </div>

                                </div>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-14 text-center">
                                <div class="flex flex-col items-center gap-3 text-neutral-400">
                                    <div class="w-14 h-14 rounded-full bg-neutral-100 flex items-center justify-center">
                                        <i class="fas fa-inbox text-2xl"></i>
                                    </div>
                                    <p class="text-sm font-medium">Aucune demande affectée</p>
                                    <p class="text-xs text-neutral-400">Vous n'avez pas de demandes en attente de traitement.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($demandes->hasPages())
            <div class="px-5 py-4 border-t border-neutral-100 bg-neutral-50">
                {{ $demandes->links() }}
            </div>
            @endif
        </div>

        {{-- Footer --}}
        <footer class="border-t border-neutral-200 pt-4 pb-2 flex items-center justify-between text-xs text-neutral-400">
            <span>© {{ date('Y') }} Agence Nationale des Bourses du Gabon</span>
            <span class="font-medium">Constructeur d'avenir</span>
        </footer>

    </main>

<script>
(function () {
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
                icon.className    = 'fas fa-eye text-[10px]';
                label.textContent = 'Voir';
            } else {
                icon.className    = 'fas fa-eye-slash text-[10px]';
                label.textContent = 'Masquer';
            }
        });
    });
})();
</script>
</body>
</html>
