<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chef de direction - ANBG</title>
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
        .detail-row { transition: opacity 0.15s ease; }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #f1f3f5; }
        ::-webkit-scrollbar-thumb { background: #c5c7d9; border-radius: 99px; }
    </style>
</head>
<body class="bg-neutral-100 font-sans text-navy min-h-screen">
    @php
        $servicePerformance = collect($servicePerformance ?? []);
        $serviceSummary = $serviceSummary ?? [];
        $formatHours = function ($value) {
            if ($value === null) {
                return '-';
            }

            return number_format((float) $value, 1, ',', ' ').' h';
        };
    @endphp
    <header class="bg-navy sticky top-0 z-50 shadow-md">
        <div class="max-w-screen-xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="bg-white rounded-lg px-2.5 py-1.5 flex-shrink-0">
                    <img src="/Logo_anbg.png" alt="ANBG" class="h-9 w-auto object-contain block">
                </div>
                <span class="hidden sm:block text-white font-medium tracking-wider uppercase">Agence Nationale des Bourses du Gabon</span>
            </div>
            <div class="flex items-center gap-3">
                <a href="/espace"
                    class="hidden sm:inline-flex items-center gap-1.5 bg-white/10 hover:bg-white/20 border border-white/15 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition-all duration-150">
                    <i class="fas fa-grid-2 text-[10px]"></i>
                    <span>Mon espace</span>
                </a>
                @if($canPilotage)
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
                        <span class="hidden sm:inline">Deconnexion</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <section class="bg-navy border-b border-white/10 pb-8 pt-6">
        <div class="max-w-screen-xl mx-auto px-4 sm:px-6">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-full bg-sky/20 flex items-center justify-center flex-shrink-0 mt-1">
                    <i class="fas fa-building-user text-sky-300 text-sm"></i>
                </div>
                <div>
                    <h1 class="text-white text-xl font-medium leading-snug mb-1">Supervision chef de direction</h1>
                    <p class="text-sky-200 text-sm font-light leading-relaxed max-w-3xl">
                        Cet espace donne une vue globale des demandes de votre direction. Le chef de direction suit les
                        transactions, les statuts et les delais sans intervenir directement dans la reponse usager.
                    </p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-1.5 bg-sky/15 border border-sky/25 text-sky-100 text-xs font-medium px-3 py-1 rounded-full">
                            <i class="fas fa-eye text-sky text-[10px]"></i> Consultation uniquement
                        </span>
                        <span class="inline-flex items-center gap-1.5 bg-leaf/15 border border-leaf/25 text-green-100 text-xs font-medium px-3 py-1 rounded-full">
                            <i class="fas fa-clipboard-list text-leaf text-[10px]"></i> Suivi des transactions
                        </span>
                        <span class="inline-flex items-center gap-1.5 bg-gold/15 border border-gold/25 text-yellow-100 text-xs font-medium px-3 py-1 rounded-full">
                            <i class="fas fa-clock text-gold text-[10px]"></i> Delai global 72h
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <main class="max-w-screen-xl mx-auto px-4 sm:px-6 py-6 space-y-6">
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

        <section class="bg-white rounded-xl shadow-card border border-neutral-200 px-5 py-4">
            <form method="get" class="grid grid-cols-1 md:grid-cols-[1fr_220px_auto] gap-3 items-end">
                <div class="relative">
                    <i class="fas fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400 text-sm pointer-events-none"></i>
                    <input name="search" value="{{ $search }}"
                        placeholder="Recherche : numero suivi, objet, usager..."
                        class="field w-full pl-9 pr-4 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy placeholder-neutral-400 transition-all duration-150">
                </div>
                <div>
                    <label for="statut_code" class="block text-xs text-neutral-500 mb-1">Statut</label>
                    <select id="statut_code" name="statut_code"
                        class="field w-full px-3 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy">
                        <option value="">Tous statuts</option>
                        @foreach($statuts as $st)
                        <option value="{{ $st->code }}" @selected(request('statut_code') === $st->code)>{{ $st->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 bg-navy hover:bg-navy-600 text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-colors duration-150">
                    <i class="fas fa-filter text-xs"></i> Filtrer
                </button>
            </form>
        </section>

        <section class="space-y-5">
            <div>
                <h2 class="text-sm font-medium text-navy">Performance des services</h2>
                <p class="text-xs text-neutral-400 mt-1">Lecture comparative des services de votre direction sur le volume, la cloture et le respect des delais.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl shadow-card border border-neutral-100 p-5">
                    <p class="text-xs uppercase tracking-wider text-neutral-400">Services actifs</p>
                    <p class="mt-2 text-3xl font-semibold text-navy">{{ number_format((int) ($serviceSummary['services_actifs'] ?? 0), 0, ',', ' ') }}</p>
                    <p class="mt-2 text-xs text-neutral-400">Services avec demandes sur le filtre courant</p>
                </div>
                <div class="bg-white rounded-2xl shadow-card border border-neutral-100 p-5">
                    <p class="text-xs uppercase tracking-wider text-neutral-400">Service le plus charge</p>
                    <p class="mt-2 text-sm font-semibold text-navy">{{ $serviceSummary['service_plus_charge'] ?? '-' }}</p>
                    <p class="mt-2 text-xs text-neutral-400">{{ number_format((int) ($serviceSummary['demandes_plus_charge'] ?? 0), 0, ',', ' ') }} demande(s)</p>
                </div>
                <div class="bg-white rounded-2xl shadow-card border border-neutral-100 p-5">
                    <p class="text-xs uppercase tracking-wider text-neutral-400">Service le plus en retard</p>
                    <p class="mt-2 text-sm font-semibold text-navy">{{ $serviceSummary['service_plus_retard'] ?? '-' }}</p>
                    <p class="mt-2 text-xs text-neutral-400">{{ number_format((int) ($serviceSummary['retards_max'] ?? 0), 0, ',', ' ') }} demande(s) en retard</p>
                </div>
                <div class="bg-white rounded-2xl shadow-card border border-neutral-100 p-5">
                    <p class="text-xs uppercase tracking-wider text-neutral-400">Meilleur taux de conformite</p>
                    <p class="mt-2 text-sm font-semibold text-navy">{{ $serviceSummary['meilleur_service'] ?? '-' }}</p>
                    <p class="mt-2 text-xs text-neutral-400">{{ number_format((float) ($serviceSummary['meilleur_taux'] ?? 0), 1, ',', ' ') }} %</p>
                </div>
            </div>

            <section class="bg-white rounded-2xl shadow-card border border-neutral-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-neutral-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-chart-simple text-sky text-sm"></i>
                        <h3 class="text-sm font-medium text-navy">Comparatif des services</h3>
                    </div>
                    <p class="text-xs text-neutral-400 hidden sm:block">Une ligne par service</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-neutral-50 border-b border-neutral-100">
                                <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">Service</th>
                                <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">Recues</th>
                                <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">Cloturees</th>
                                <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">En cours</th>
                                <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">Dans les delais</th>
                                <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">A risque</th>
                                <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">En retard</th>
                                <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">Conformite</th>
                                <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">Delai moyen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                        @forelse($servicePerformance as $serviceRow)
                            <tr class="trow transition-colors duration-100">
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-navy">
                                        {{ ($serviceRow['service_code'] !== '' ? $serviceRow['service_code'].' - ' : '').$serviceRow['service'] }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-neutral-600">{{ number_format((int) $serviceRow['total_demandes'], 0, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-sm text-neutral-600">{{ number_format((int) $serviceRow['total_cloturees'], 0, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-sm text-neutral-600">{{ number_format((int) $serviceRow['total_en_cours'], 0, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-sm text-green-700">{{ number_format((int) $serviceRow['total_dans_les_delais'], 0, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-sm text-amber-700">{{ number_format((int) $serviceRow['total_a_risque'], 0, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-sm text-red-700">{{ number_format((int) $serviceRow['total_en_retard'], 0, ',', ' ') }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 bg-sky-50 border border-sky-100 text-sky text-xs font-medium px-2.5 py-1 rounded-full">
                                        {{ number_format((float) $serviceRow['taux_conformite'], 1, ',', ' ') }} %
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-neutral-600">{{ $formatHours($serviceRow['delai_moyen_heures']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-sm text-neutral-400">Aucune performance service disponible sur ce filtre.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </section>

        <section class="bg-white rounded-2xl shadow-card border border-neutral-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-neutral-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fas fa-table-list text-sky text-sm"></i>
                    <h2 class="text-sm font-medium text-navy">Transactions de la direction</h2>
                    <span class="bg-sky-50 text-sky text-xs font-medium px-2 py-0.5 rounded-full border border-sky-100">
                        {{ $demandes->total() }}
                    </span>
                </div>
                <p class="text-xs text-neutral-400 hidden sm:block">Suivi transversal des demandes de votre perimetre</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-neutral-50 border-b border-neutral-100">
                            <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">N° Suivi</th>
                            <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">Usager</th>
                            <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">Service</th>
                            <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">Statut</th>
                            <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">Alerte globale 72h</th>
                            <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                    @forelse($demandes as $demande)
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
                                    {{ $demande->service_code }} - {{ $demande->service }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-neutral-500">{{ $demande->statut }}</td>
                            <td class="px-4 py-3">
                                @if($demande->delai_alerte === 'en_retard')
                                <span class="inline-flex items-center gap-1.5 bg-red-50 text-red-700 border border-red-200 text-xs font-medium px-2.5 py-1 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 flex-shrink-0"></span> En retard
                                </span>
                                @elseif($demande->delai_alerte === 'a_risque')
                                <span class="inline-flex items-center gap-1.5 bg-gold-50 text-amber-700 border border-amber-200 text-xs font-medium px-2.5 py-1 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gold flex-shrink-0"></span> A risque
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1.5 bg-leaf-50 text-green-700 border border-green-200 text-xs font-medium px-2.5 py-1 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-leaf flex-shrink-0"></span> Dans les delais
                                </span>
                                @endif
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

                        <tr id="detail-d-{{ $demande->id_demande }}" class="detail-row" style="display:none;">
                            <td colspan="6" class="px-4 py-4 bg-neutral-50 border-b border-neutral-100">
                                @php $pieces = $piecesByDemand->get($demande->id_demande, collect()); @endphp
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    <div class="bg-white border border-neutral-200 rounded-xl p-4 space-y-3">
                                        <h4 class="text-xs font-medium text-neutral-500 uppercase tracking-wider border-b border-neutral-100 pb-2">
                                            <i class="fas fa-file-lines text-sky mr-1.5"></i>Detail de la demande
                                        </h4>

                                        <div>
                                            <p class="text-xs text-neutral-400 mb-0.5">Objet</p>
                                            <p class="text-sm font-medium text-navy">{{ $demande->objet }}</p>
                                        </div>

                                        <div>
                                            <p class="text-xs text-neutral-400 mb-1">Message complet</p>
                                            <div class="bg-neutral-50 border border-neutral-200 rounded-lg p-3 text-sm text-neutral-700 leading-relaxed whitespace-pre-wrap max-h-44 overflow-y-auto">{{ $demande->message }}</div>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <p class="text-xs text-neutral-400">Usager</p>
                                                <p class="text-sm text-navy">{{ trim(($demande->usager_prenom ?? '').' '.($demande->usager_nom ?? '')) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-neutral-400">Type</p>
                                                <p class="text-sm text-navy">{{ $demande->type_demande }}</p>
                                            </div>
                                        </div>

                                        @if($pieces->isNotEmpty())
                                        <div>
                                            <p class="text-xs text-neutral-400 mb-1.5">Pieces jointes usager</p>
                                            <div class="space-y-1.5">
                                                @foreach($pieces as $piece)
                                                <a href="/pieces-jointes/{{ $piece->id_piece_jointe }}" target="_blank" rel="noopener"
                                                    class="flex items-center gap-2 bg-sky-50 border border-sky-100 text-sky text-xs font-medium px-3 py-2 rounded-lg hover:bg-sky-100 transition-colors duration-150">
                                                    <i class="fas fa-paperclip text-[10px]"></i>
                                                    <span>{{ $piece->nom_fichier }}</span>
                                                </a>
                                                @endforeach
                                            </div>
                                        </div>
                                        @else
                                        <p class="text-xs text-neutral-400 italic">Aucune piece jointe usager.</p>
                                        @endif
                                    </div>

                                    <div class="bg-white border border-neutral-200 rounded-xl p-4 space-y-4">
                                        <h4 class="text-xs font-medium text-neutral-500 uppercase tracking-wider border-b border-neutral-100 pb-2">
                                            <i class="fas fa-binoculars text-sky mr-1.5"></i>Suivi de supervision
                                        </h4>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div class="bg-neutral-50 border border-neutral-200 rounded-lg p-3">
                                                <p class="text-[11px] text-neutral-400 uppercase tracking-wide">Soumission</p>
                                                <p class="mt-1 text-sm text-navy">{{ $demande->date_soumission ?? '-' }}</p>
                                            </div>
                                            <div class="bg-neutral-50 border border-neutral-200 rounded-lg p-3">
                                                <p class="text-[11px] text-neutral-400 uppercase tracking-wide">Affectation accueil</p>
                                                <p class="mt-1 text-sm text-navy">{{ $demande->date_affectation_accueil ?? '-' }}</p>
                                            </div>
                                            <div class="bg-neutral-50 border border-neutral-200 rounded-lg p-3">
                                                <p class="text-[11px] text-neutral-400 uppercase tracking-wide">Affectation agent</p>
                                                <p class="mt-1 text-sm text-navy">{{ $demande->date_affectation_agent ?? '-' }}</p>
                                            </div>
                                            <div class="bg-neutral-50 border border-neutral-200 rounded-lg p-3">
                                                <p class="text-[11px] text-neutral-400 uppercase tracking-wide">Envoi usager</p>
                                                <p class="mt-1 text-sm text-navy">{{ $demande->date_envoi_usager ?? '-' }}</p>
                                            </div>
                                            <div class="bg-neutral-50 border border-neutral-200 rounded-lg p-3">
                                                <p class="text-[11px] text-neutral-400 uppercase tracking-wide">Cloture</p>
                                                <p class="mt-1 text-sm text-navy">{{ $demande->date_cloture ?? '-' }}</p>
                                            </div>
                                            <div class="bg-neutral-50 border border-neutral-200 rounded-lg p-3">
                                                <p class="text-[11px] text-neutral-400 uppercase tracking-wide">Alerte chef / agent</p>
                                                <p class="mt-1 text-sm text-navy">{{ $demande->alerte_chef ?? '-' }} / {{ $demande->alerte_agent ?? '-' }}</p>
                                            </div>
                                        </div>

                                        <div class="flex items-start gap-2 bg-sky-50 border border-sky-100 rounded-lg px-3 py-2.5">
                                            <i class="fas fa-circle-info text-sky text-xs mt-0.5 flex-shrink-0"></i>
                                            <p class="text-xs text-sky-700 leading-relaxed">
                                                Consultation uniquement : le chef de direction supervise les services de son perimetre et les indicateurs de delai, sans modifier la reponse usager.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-neutral-400">Aucune demande sur votre perimetre.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-4 border-t border-neutral-100">
                {{ $demandes->links() }}
            </div>
        </section>

    </main>

    <script>
    (function () {
        document.querySelectorAll('.view-toggle').forEach((btn) => {
            btn.addEventListener('click', () => {
                const row = document.getElementById(btn.dataset.target);
                if (!row) {
                    return;
                }

                const isOpen = row.style.display !== 'none';
                row.style.display = isOpen ? 'none' : '';
                btn.setAttribute('aria-expanded', String(!isOpen));

                const icon = btn.querySelector('i');
                const label = btn.querySelector('span');
                if (isOpen) {
                    icon.className = 'fas fa-eye text-[10px]';
                    label.textContent = 'Voir';
                } else {
                    icon.className = 'fas fa-eye-slash text-[10px]';
                    label.textContent = 'Masquer';
                }
            });
        });
    })();
    </script>
</body>
</html>
