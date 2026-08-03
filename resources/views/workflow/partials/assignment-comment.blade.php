@php
    $assignmentComment = trim((string) ($comment ?? ''));
@endphp

@if($assignmentComment !== '')
<div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5">
    <div class="flex items-start gap-2">
        <svg class="icon-svg mt-0.5 flex-shrink-0 text-amber-600 text-xs" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 9v4M12 17h.01M10.3 4.9 2.9 18a1.5 1.5 0 0 0 1.3 2.2h15.6a1.5 1.5 0 0 0 1.3-2.2L13.7 4.9a1.5 1.5 0 0 0-2.4 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">{{ $label ?? 'Consigne / urgence' }}</p>
            <p class="mt-1 whitespace-pre-wrap text-sm leading-relaxed text-neutral-800">{{ $assignmentComment }}</p>
        </div>
    </div>
</div>
@endif
