@if(session('success'))
    @php
        $successMessage = (string) session('success');
        $isWelcomeMessage = str_starts_with($successMessage, 'Bienvenue ');
    @endphp

    @if($isWelcomeMessage)
        <p data-auto-dismiss-flash role="status" class="text-sm font-medium text-green-700 transition-all duration-300 ease-out">
            {{ $successMessage }}
        </p>
    @else
        <div data-auto-dismiss-flash role="status" class="flex items-center gap-3 rounded-xl border border-leaf/30 bg-leaf-50 px-4 py-3 text-sm text-green-800 shadow-sm transition-all duration-300 ease-out">
            <svg class="icon-svg flex-shrink-0 text-leaf" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/>
                <path d="m8.5 12 2.3 2.3L15.5 9.7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>{{ $successMessage }}</span>
        </div>
    @endif
@endif
