@extends('admin.layout')

@section('title', 'Paramètres globaux')

@section('content')
@php
    $dayLabels = [
        1 => 'Lundi',
        2 => 'Mardi',
        3 => 'Mercredi',
        4 => 'Jeudi',
        5 => 'Vendredi',
        6 => 'Samedi',
        7 => 'Dimanche',
    ];

    $activeParams = collect($parametres)->where('actif', true)->count();
    $showParameterEditor = $parametres->isNotEmpty();
    $workingDaysLabel = collect($joursOuvres)->map(function ($row) use ($dayLabels) {
        return ($dayLabels[$row->jour_semaine_iso] ?? 'Jour').' '.substr($row->heure_debut, 0, 5).' - '.substr($row->heure_fin, 0, 5);
    });
@endphp

<div class="grid grid-cols-1 gap-6 {{ $showParameterEditor ? 'xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)]' : '' }}">
    @if($showParameterEditor)
    <div class="space-y-6">
        <div class="overflow-hidden rounded-2xl border border-neutral-100 bg-white shadow-card">
            <div class="flex items-center gap-2 border-b border-neutral-100 px-5 py-4">
                <i class="fas fa-sliders-h text-sm text-sky"></i>
                <h2 class="text-sm font-medium text-navy">Paramètres d'application</h2>
            </div>

            <div class="border-b border-neutral-100 bg-neutral-50 px-5 py-4">
                <form method="post" action="/admin/parametres" class="grid grid-cols-1 gap-3 lg:grid-cols-[180px_160px_minmax(0,1fr)_140px_auto]">
                    @csrf
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-navy">Famille</label>
                        <input name="famille" placeholder="ex: priorite" required class="field w-full rounded-xl border border-neutral-200 bg-white px-3 py-2.5 text-sm text-navy placeholder-neutral-400">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-navy">Code</label>
                        <input name="code" placeholder="urgente" required class="field w-full rounded-xl border border-neutral-200 bg-white px-3 py-2.5 text-sm text-navy placeholder-neutral-400">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-navy">Libellé</label>
                        <input name="libelle" placeholder="Urgente" required class="field w-full rounded-xl border border-neutral-200 bg-white px-3 py-2.5 text-sm text-navy placeholder-neutral-400">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-navy">Ordre</label>
                        <input name="ordre_affichage" type="number" min="1" max="999" value="1" class="field w-full rounded-xl border border-neutral-200 bg-white px-3 py-2.5 text-sm text-navy">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-navy px-4 py-2.5 text-sm font-medium text-white transition-colors duration-150 hover:bg-navy-600">
                            <i class="fas fa-plus"></i> Ajouter
                        </button>
                    </div>
                </form>
            </div>

            <div class="max-h-[720px] space-y-4 overflow-y-auto px-5 py-5">
                @foreach($parametres->groupBy('famille') as $famille => $items)
                <div class="overflow-hidden rounded-xl border border-neutral-200">
                    <div class="flex items-center gap-2 border-b border-neutral-200 bg-neutral-50 px-4 py-2.5">
                        <span class="rounded border border-navy-100 bg-navy-50 px-2 py-0.5 font-mono text-xs font-medium text-navy">{{ $famille }}</span>
                        <span class="text-[10px] text-neutral-400">{{ $items->count() }} valeur(s)</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <tbody class="divide-y divide-neutral-100">
                                @foreach($items as $param)
                                <tr class="trow transition-colors duration-100">
                                    <td class="px-3 py-2 font-mono text-xs text-neutral-600">{{ $param->code }}</td>
                                    <td class="px-2 py-2">
                                        <form method="post" action="/admin/parametres/{{ $param->id_parametre }}" class="grid gap-2 md:grid-cols-[minmax(0,1fr)_90px_90px_auto] md:items-center">
                                            @csrf
                                            @method('put')
                                            <input name="libelle" value="{{ $param->libelle }}" required class="field w-full rounded border border-neutral-200 bg-white px-2 py-1 text-xs text-navy">
                                    </td>
                                    <td class="px-2 py-2">
                                            <input name="ordre_affichage" type="number" min="1" max="999" value="{{ $param->ordre_affichage ?: 1 }}" class="field w-full rounded border border-neutral-200 bg-white px-2 py-1 text-xs text-navy">
                                    </td>
                                    <td class="px-2 py-2">
                                            <select name="actif" class="field w-full appearance-none rounded border border-neutral-200 bg-white px-2 py-1 text-[10px] text-navy">
                                                <option value="1" {{ $param->actif ? 'selected' : '' }}>ON</option>
                                                <option value="0" {{ !$param->actif ? 'selected' : '' }}>OFF</option>
                                            </select>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                            <button type="submit" class="admin-inline-action rounded border border-sky-200 px-2.5 py-1 text-[10px] text-sky transition-colors hover:bg-sky-50">
                                                <i class="fas fa-save text-[10px]"></i>
                                                Sauvegarder
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <div class="space-y-6">
        <div class="grid grid-cols-2 gap-4">
            @if($showParameterEditor)
            <div class="rounded-2xl border border-neutral-100 bg-white p-5 shadow-card">
                <p class="text-xs uppercase tracking-widest text-neutral-400">Paramètres actifs</p>
                <p class="mt-2 text-3xl font-bold text-navy">{{ $activeParams }}</p>
            </div>
            @else
            <div class="rounded-2xl border border-neutral-100 bg-white p-5 shadow-card">
                <p class="text-xs uppercase tracking-widest text-neutral-400">Configurations des délais</p>
                <p class="mt-2 text-3xl font-bold text-navy">{{ collect($configurationsSla)->count() }}</p>
            </div>
            @endif
            <div class="rounded-2xl border border-neutral-100 bg-white p-5 shadow-card">
                <p class="text-xs uppercase tracking-widest text-neutral-400">Jours fériés</p>
                <p class="mt-2 text-3xl font-bold text-navy">{{ $joursFeries->count() }}</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-neutral-100 bg-white shadow-card">
            <div class="flex items-center gap-2 border-b border-neutral-100 px-5 py-4">
                <i class="fas fa-clock text-sm text-sky"></i>
                <h2 class="text-sm font-medium text-navy">Configuration active des délais</h2>
            </div>
            <div class="space-y-4 p-5">
                @if($slaConfig)
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                            <p class="text-[11px] uppercase tracking-widest text-neutral-400">Nom</p>
                            <p class="mt-2 text-sm font-medium text-navy">{{ $slaConfig->nom }}</p>
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                            <p class="text-[11px] uppercase tracking-widest text-neutral-400">Fuseau</p>
                            <p class="mt-2 text-sm font-medium text-navy">{{ $slaConfig->fuseau_horaire }}</p>
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                            <p class="text-[11px] uppercase tracking-widest text-neutral-400">Délai max global</p>
                            <p class="mt-2 text-sm font-medium text-navy">{{ $slaConfig->delai_max_heures }} h métier</p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-sky-100 bg-sky-50 p-4">
                        <p class="text-[11px] uppercase tracking-widest text-sky">Plages ouvrées</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @forelse($workingDaysLabel as $label)
                                <span class="rounded-full border border-sky-200 bg-white px-2.5 py-1 text-xs text-navy">{{ $label }}</span>
                            @empty
                                <span class="text-xs text-neutral-500">Aucune plage ouvrée active.</span>
                            @endforelse
                        </div>
                    </div>
                @else
                    <p class="text-sm text-neutral-400">Aucune configuration de délai active.</p>
                @endif
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-neutral-100 bg-white shadow-card">
            <div class="flex items-center justify-between border-b border-neutral-100 px-5 py-4">
                <div class="flex items-center gap-2">
                    <i class="fas fa-calendar-day text-sm text-sky"></i>
                    <h2 class="text-sm font-medium text-navy">Jours fériés exclus du délai</h2>
                </div>
            </div>

            <div class="border-b border-neutral-100 bg-neutral-50 px-5 py-4">
                <form method="post" action="/admin/jours-feries" class="grid grid-cols-1 gap-3 lg:grid-cols-[170px_170px_minmax(0,1fr)_auto]">
                    @csrf
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-navy">Date début <span class="text-red-500">*</span></label>
                        <input type="date" name="date_ferie" required class="field w-full rounded-xl border border-neutral-200 bg-white px-3 py-2.5 text-sm text-navy">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-navy">Date fin</label>
                        <input type="date" name="date_fin" class="field w-full rounded-xl border border-neutral-200 bg-white px-3 py-2.5 text-sm text-navy">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-navy">Description</label>
                        <input type="text" name="libelle" required placeholder="ex: Fêtes de fin d'année" class="field w-full rounded-xl border border-neutral-200 bg-white px-3 py-2.5 text-sm text-navy placeholder-neutral-400">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-navy px-4 py-2.5 text-sm font-medium text-white transition-colors duration-150 hover:bg-navy-600">
                            <i class="fas fa-plus"></i> Ajouter
                        </button>
                    </div>
                </form>
            </div>

            <div class="px-5 py-3">
                @if($joursFeries->count() > 0)
                    <div class="space-y-3">
                        @foreach($joursFeries as $jf)
                        <div class="rounded-lg border border-neutral-100 bg-neutral-50 px-4 py-3">
                            <div class="mb-2 text-xs uppercase tracking-widest text-neutral-400">Jour férié #{{ $jf->id_sla_jour_ferie }}</div>
                            <div class="grid gap-2 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                                <form method="post" action="/admin/jours-feries/{{ $jf->id_sla_jour_ferie }}" class="grid gap-3 lg:grid-cols-[170px_170px_minmax(0,1fr)]">
                                    @csrf
                                    @method('put')
                                    <div>
                                        <label class="mb-1 block text-[11px] font-medium uppercase tracking-widest text-neutral-400">Date début</label>
                                        <input type="date" name="date_ferie" value="{{ $jf->date_ferie }}" required class="field w-full rounded-xl border border-neutral-200 bg-white px-3 py-2.5 text-sm text-navy">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[11px] font-medium uppercase tracking-widest text-neutral-400">Date fin</label>
                                        <input type="date" name="date_fin" value="{{ $jf->date_fin }}" class="field w-full rounded-xl border border-neutral-200 bg-white px-3 py-2.5 text-sm text-navy">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[11px] font-medium uppercase tracking-widest text-neutral-400">Description</label>
                                        <input type="text" name="libelle" value="{{ $jf->libelle }}" required class="field w-full rounded-xl border border-neutral-200 bg-white px-3 py-2.5 text-sm text-navy">
                                    </div>
                                    <div class="lg:col-span-3 flex justify-end">
                                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-sky-200 bg-white px-4 py-2.5 text-sm font-medium text-sky transition-colors hover:bg-sky-50">
                                            <i class="fas fa-save text-xs"></i> Mettre à jour
                                        </button>
                                    </div>
                                </form>

                                <form method="post" action="/admin/jours-feries/{{ $jf->id_sla_jour_ferie }}" onsubmit="return confirm('Supprimer ce jour férié ?')" class="lg:justify-self-end">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-medium text-red-500 transition-colors hover:bg-red-50">
                                        <i class="fas fa-trash-alt text-xs"></i> Supprimer
                                    </button>
                                </form>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="py-4 text-center text-sm text-neutral-400">Aucun jour férié configuré pour l'année en cours.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
