@extends('admin.layout')

@section('title', 'Directions')

@section('content')
@php
    $activeDirections = collect($directions)->where('actif', true)->count();
    $inactiveDirections = collect($directions)->where('actif', false)->count();
    $totalServices = collect($services)->count();
@endphp

<div class="space-y-4" id="directions-admin-page">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-neutral-100 bg-white p-5 shadow-card">
            <p class="text-xs uppercase tracking-widest text-neutral-400">Directions</p>
            <p class="mt-2 text-3xl font-bold text-navy">{{ collect($directions)->count() }}</p>
        </div>
        <div class="rounded-2xl border border-neutral-100 bg-white p-5 shadow-card">
            <p class="text-xs uppercase tracking-widest text-neutral-400">Actives</p>
            <p class="mt-2 text-3xl font-bold text-green-700">{{ $activeDirections }}</p>
        </div>
        <div class="rounded-2xl border border-neutral-100 bg-white p-5 shadow-card">
            <p class="text-xs uppercase tracking-widest text-neutral-400">Services rattachés</p>
            <p class="mt-2 text-3xl font-bold text-sky">{{ $totalServices }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-neutral-100 bg-white shadow-card">
        <div class="border-b border-neutral-100 px-5 py-4">
            <div class="mb-4 flex items-center gap-2">
                <i class="fas fa-building text-sm text-sky"></i>
                <h2 class="text-sm font-medium text-navy">Directions</h2>
            </div>

            <form method="post" action="/admin/directions" class="grid grid-cols-1 gap-3 lg:grid-cols-[180px_minmax(0,1fr)_auto]">
                @csrf
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-navy">Code</label>
                    <input name="code" placeholder="ex: DS" required class="field w-full rounded-xl border border-neutral-200 bg-white px-3 py-2.5 text-sm text-navy placeholder-neutral-400 transition-all duration-150">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-navy">Libellé</label>
                    <input name="libelle" placeholder="Direction des systèmes d'information" required class="field w-full rounded-xl border border-neutral-200 bg-white px-3 py-2.5 text-sm text-navy placeholder-neutral-400 transition-all duration-150">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-navy px-5 py-2.5 text-sm font-medium text-white transition-colors duration-150 hover:bg-navy-600">
                        <i class="fas fa-plus text-xs"></i> Ajouter
                    </button>
                </div>
            </form>
        </div>

        <div class="border-b border-neutral-100 bg-neutral-50 px-5 py-3">
            <div class="relative">
                <i class="fas fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-neutral-400"></i>
                <input id="direction-filter" type="search" placeholder="Rechercher une direction par code ou libellé" class="field w-full rounded-lg border border-neutral-200 bg-white py-2 pl-8 pr-3 text-xs text-navy placeholder-neutral-400">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-neutral-100 bg-neutral-50">
                        <th class="px-4 py-3 text-left text-[11px] font-medium uppercase tracking-wider text-neutral-500">Code</th>
                        <th class="px-4 py-3 text-left text-[11px] font-medium uppercase tracking-wider text-neutral-500">Libellé</th>
                        <th class="px-4 py-3 text-left text-[11px] font-medium uppercase tracking-wider text-neutral-500">Statut</th>
                        <th class="px-4 py-3 text-left text-[11px] font-medium uppercase tracking-wider text-neutral-500">Services</th>
                        <th class="px-4 py-3 text-left text-[11px] font-medium uppercase tracking-wider text-neutral-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                @foreach($directions as $direction)
                    @php $srvCount = collect($services)->where('id_direction', $direction->id_direction)->count(); @endphp
                    <tr class="trow direction-row transition-colors duration-100" data-filter="{{ strtolower($direction->code.' '.$direction->libelle) }}">
                        <td class="px-4 py-3">
                            <span class="rounded bg-navy-50 px-2 py-1 font-mono text-xs font-medium text-navy">{{ $direction->code }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <input form="direction-form-{{ $direction->id_direction }}" name="libelle" value="{{ $direction->libelle }}" required class="field w-full rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2 text-sm text-navy transition-all duration-150">
                        </td>
                        <td class="px-4 py-3">
                            <select form="direction-form-{{ $direction->id_direction }}" name="actif" class="field rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2 text-xs text-navy transition-all duration-150">
                                <option value="1" {{ $direction->actif ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ !$direction->actif ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </td>
                        <td class="px-4 py-3 text-xs text-neutral-500">{{ $srvCount }}</td>
                        <td class="px-4 py-3">
                            <form id="direction-form-{{ $direction->id_direction }}" method="post" action="/admin/directions/{{ $direction->id_direction }}" class="inline">
                                @csrf
                                @method('put')
                            </form>
                            <button form="direction-form-{{ $direction->id_direction }}" type="submit" class="admin-inline-action rounded-lg border border-sky-200 px-3 py-1.5 text-xs text-sky transition-colors duration-150 hover:bg-sky-50">
                                <i class="fas fa-save text-[10px]"></i>
                                Mettre à jour
                            </button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between border-t border-neutral-100 bg-neutral-50 px-5 py-3 text-xs text-neutral-500">
            <span>{{ collect($directions)->count() }} direction(s) au total</span>
            <span>{{ $inactiveDirections }} inactive(s)</span>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    (() => {
        const input = document.getElementById('direction-filter');
        if (!input) return;

        const rows = Array.from(document.querySelectorAll('.direction-row'));
        input.addEventListener('input', () => {
            const query = input.value.trim().toLowerCase();
            rows.forEach((row) => {
                const haystack = row.dataset.filter || '';
                row.style.display = query === '' || haystack.includes(query) ? '' : 'none';
            });
        });
    })();
</script>
@endpush
