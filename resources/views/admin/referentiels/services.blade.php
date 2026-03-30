@extends('admin.layout')

@section('title', 'Services')

@section('content')
@php
    $activeServices = collect($services)->where('actif', true)->count();
    $inactiveServices = collect($services)->where('actif', false)->count();
    $attachedDirections = collect($services)->pluck('id_direction')->unique()->count();
@endphp

<div class="space-y-4" id="services-admin-page">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-neutral-100 bg-white p-5 shadow-card">
            <p class="text-xs uppercase tracking-widest text-neutral-400">Services</p>
            <p class="mt-2 text-3xl font-bold text-navy">{{ collect($services)->count() }}</p>
        </div>
        <div class="rounded-2xl border border-neutral-100 bg-white p-5 shadow-card">
            <p class="text-xs uppercase tracking-widest text-neutral-400">Actifs</p>
            <p class="mt-2 text-3xl font-bold text-green-700">{{ $activeServices }}</p>
        </div>
        <div class="rounded-2xl border border-neutral-100 bg-white p-5 shadow-card">
            <p class="text-xs uppercase tracking-widest text-neutral-400">Directions couvertes</p>
            <p class="mt-2 text-3xl font-bold text-sky">{{ $attachedDirections }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-neutral-100 bg-white shadow-card">
        <div class="border-b border-neutral-100 px-5 py-4">
            <div class="mb-4 flex items-center gap-2">
                <i class="fas fa-cubes text-sm text-sky"></i>
                <h2 class="text-sm font-medium text-navy">Services</h2>
            </div>

            <form method="post" action="/admin/services" class="grid grid-cols-1 gap-3 xl:grid-cols-[240px_160px_minmax(0,1fr)_240px_auto]">
                @csrf
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-navy">Direction parente</label>
                    <select name="id_direction" required class="field w-full appearance-none rounded-xl border border-neutral-200 bg-white px-3 py-2.5 text-sm text-navy transition-all duration-150">
                        <option value="">— Choisir —</option>
                        @foreach($directions as $direction)
                            <option value="{{ $direction->id_direction }}">{{ $direction->code }} - {{ $direction->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-navy">Code</label>
                    <input name="code" placeholder="ex: SRH" required class="field w-full rounded-xl border border-neutral-200 bg-white px-3 py-2.5 text-sm text-navy placeholder-neutral-400 transition-all duration-150">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-navy">Libellé</label>
                    <input name="libelle" placeholder="Service des ressources humaines" required class="field w-full rounded-xl border border-neutral-200 bg-white px-3 py-2.5 text-sm text-navy placeholder-neutral-400 transition-all duration-150">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-navy">Email</label>
                    <input name="email_service" type="email" placeholder="service@anbg.ga" class="field w-full rounded-xl border border-neutral-200 bg-white px-3 py-2.5 text-sm text-navy placeholder-neutral-400 transition-all duration-150">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-navy px-5 py-2.5 text-sm font-medium text-white transition-colors duration-150 hover:bg-navy-600">
                        <i class="fas fa-plus text-xs"></i> Ajouter
                    </button>
                </div>
            </form>
        </div>

        <div class="grid gap-3 border-b border-neutral-100 bg-neutral-50 px-5 py-3 md:grid-cols-[minmax(0,1fr)_220px]">
            <div class="relative">
                <i class="fas fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-neutral-400"></i>
                <input id="service-filter" type="search" placeholder="Rechercher par code, libellé ou email" class="field w-full rounded-lg border border-neutral-200 bg-white py-2 pl-8 pr-3 text-xs text-navy placeholder-neutral-400">
            </div>
            <select id="service-direction-filter" class="field w-full appearance-none rounded-lg border border-neutral-200 bg-white px-3 py-2 text-xs text-navy">
                <option value="">Toutes les directions</option>
                @foreach($directions as $direction)
                    <option value="{{ strtolower($direction->code) }}">{{ $direction->code }}</option>
                @endforeach
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-neutral-100 bg-neutral-50">
                        <th class="px-4 py-3 text-left text-[11px] font-medium uppercase tracking-wider text-neutral-500">Code</th>
                        <th class="px-4 py-3 text-left text-[11px] font-medium uppercase tracking-wider text-neutral-500">Direction</th>
                        <th class="px-4 py-3 text-left text-[11px] font-medium uppercase tracking-wider text-neutral-500">Libellé</th>
                        <th class="px-4 py-3 text-left text-[11px] font-medium uppercase tracking-wider text-neutral-500">Email</th>
                        <th class="px-4 py-3 text-left text-[11px] font-medium uppercase tracking-wider text-neutral-500">Statut</th>
                        <th class="px-4 py-3 text-left text-[11px] font-medium uppercase tracking-wider text-neutral-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                @foreach($services as $service)
                    <tr
                        class="trow service-row transition-colors duration-100"
                        data-filter="{{ strtolower($service->code.' '.$service->libelle.' '.$service->email_service.' '.$service->direction_code.' '.$service->direction_libelle) }}"
                        data-direction="{{ strtolower($service->direction_code) }}"
                    >
                        <td class="px-4 py-3">
                            <span class="rounded bg-neutral-100 px-2 py-1 font-mono text-xs font-medium text-navy">{{ $service->code }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-neutral-500">
                            <span class="rounded-full bg-sky-50 px-2 py-0.5 text-sky">{{ $service->direction_code }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <input form="service-form-{{ $service->id_service }}" name="libelle" value="{{ $service->libelle }}" required class="field w-full rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2 text-sm text-navy transition-all duration-150">
                        </td>
                        <td class="px-4 py-3">
                            <input form="service-form-{{ $service->id_service }}" name="email_service" value="{{ $service->email_service }}" placeholder="service@anbg.ga" class="field w-full rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2 text-sm text-navy transition-all duration-150">
                        </td>
                        <td class="px-4 py-3">
                            <select form="service-form-{{ $service->id_service }}" name="actif" class="field rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2 text-xs text-navy transition-all duration-150">
                                <option value="1" {{ $service->actif ? 'selected' : '' }}>Actif</option>
                                <option value="0" {{ !$service->actif ? 'selected' : '' }}>Inactif</option>
                            </select>
                        </td>
                        <td class="px-4 py-3">
                            <form id="service-form-{{ $service->id_service }}" method="post" action="/admin/services/{{ $service->id_service }}" class="inline">
                                @csrf
                                @method('put')
                                <input type="hidden" name="id_direction" value="{{ $service->id_direction }}">
                            </form>
                            <button form="service-form-{{ $service->id_service }}" type="submit" class="rounded-lg border border-sky-200 px-3 py-1.5 text-xs text-sky transition-colors duration-150 hover:bg-sky-50">
                                Mettre à jour
                            </button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between border-t border-neutral-100 bg-neutral-50 px-5 py-3 text-xs text-neutral-500">
            <span>{{ collect($services)->count() }} service(s) au total</span>
            <span>{{ $inactiveServices }} inactif(s)</span>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const searchInput = document.getElementById('service-filter');
        const directionSelect = document.getElementById('service-direction-filter');
        if (!searchInput || !directionSelect) return;

        const rows = Array.from(document.querySelectorAll('.service-row'));
        const applyFilter = () => {
            const query = searchInput.value.trim().toLowerCase();
            const direction = directionSelect.value.trim().toLowerCase();

            rows.forEach((row) => {
                const haystack = row.dataset.filter || '';
                const rowDirection = row.dataset.direction || '';
                const matchesQuery = query === '' || haystack.includes(query);
                const matchesDirection = direction === '' || rowDirection === direction;
                row.style.display = matchesQuery && matchesDirection ? '' : 'none';
            });
        };

        searchInput.addEventListener('input', applyFilter);
        directionSelect.addEventListener('change', applyFilter);
    })();
</script>
@endpush
