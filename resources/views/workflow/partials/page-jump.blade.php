@php
    $queryParameters = request()->except($pageName);
@endphp

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <p class="text-xs text-neutral-500">20 dossiers par page</p>

    <form method="get" action="{{ url()->current() }}" data-page-jump-form class="flex flex-wrap items-center gap-2 text-xs text-neutral-600">
        @foreach($queryParameters as $name => $value)
            @if(is_scalar($value))
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endif
        @endforeach

        <label for="{{ $pageName }}-jump" class="font-medium text-navy">Aller à la page</label>
        <input
            id="{{ $pageName }}-jump"
            name="{{ $pageName }}"
            type="number"
            min="1"
            max="{{ $paginator->lastPage() }}"
            value="{{ $paginator->currentPage() }}"
            inputmode="numeric"
            data-page-jump-input
            class="filter-control h-9 w-16 rounded-lg border border-neutral-200 bg-white px-2 text-center text-sm font-semibold text-navy transition-all duration-150"
        >
        <span>sur {{ $paginator->lastPage() }}</span>
        <button type="submit" title="Charger la page saisie" aria-label="Charger la page saisie" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-sky-200 bg-sky-50 text-sky transition-colors duration-150 hover:border-sky hover:bg-sky hover:text-white">
            <svg class="icon-svg text-xs" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M5 12h13M14 7l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </form>
</div>
