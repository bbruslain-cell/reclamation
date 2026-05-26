@php
    $variant = $variant ?? 'badge';
    $deliveryState = $deliveryState ?? ($demande->delivery_state ?? 'idle');
    $sentAt = !empty($demande->date_envoi_usager)
        ? \Illuminate\Support\Carbon::parse($demande->date_envoi_usager)->format('d/m/Y H:i')
        : null;
    $failedAt = !empty($demande->date_echec_envoi_usager)
        ? \Illuminate\Support\Carbon::parse($demande->date_echec_envoi_usager)->format('d/m/Y H:i')
        : null;
@endphp

@if($variant === 'panel')
    @if($deliveryState === 'pending')
        <div class="mb-3 flex items-start gap-3 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 font-sans text-sky shadow-sm">
            <span class="mt-0.5 inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-2xl bg-sky text-white">
                <svg class="icon-svg text-sm" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4 8.5A2.5 2.5 0 0 1 6.5 6h11A2.5 2.5 0 0 1 20 8.5v7A2.5 2.5 0 0 1 17.5 18h-11A2.5 2.5 0 0 1 4 15.5v-7Z" stroke="currentColor" stroke-width="1.8"/>
                    <path d="m6 9 6 4 6-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M12 10.5v2.8l1.8 1.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <div>
                <p class="text-sm font-semibold text-navy">Envoi en cours vers l'usager</p>
                <p class="mt-1 text-xs leading-5 text-neutral-500">La demande restera ouverte jusqu'à confirmation technique de l'envoi.</p>
            </div>
        </div>
    @elseif($deliveryState === 'failed')
        <div class="mb-3 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 font-sans text-red-700 shadow-sm">
            <span class="mt-0.5 inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-2xl bg-red-500 text-white">
                <svg class="icon-svg text-sm" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M12 8v5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <path d="M12 16.8h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </span>
            <div>
                <p class="text-sm font-semibold text-navy">Échec d'envoi</p>
                <p class="mt-1 text-xs leading-5 text-neutral-500">Le dernier essai a échoué{{ $failedAt ? ' le '.$failedAt : '' }}. La demande reste ouverte et l'envoi peut être relancé.</p>
            </div>
        </div>
    @elseif($deliveryState === 'sent')
        <div class="mb-3 flex items-start gap-3 rounded-xl border border-green-200 bg-leaf-50 px-4 py-3 font-sans text-green-700 shadow-sm">
            <span class="mt-0.5 inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-2xl bg-leaf text-white">
                <svg class="icon-svg text-sm" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4 8.5A2.5 2.5 0 0 1 6.5 6h11A2.5 2.5 0 0 1 20 8.5v7A2.5 2.5 0 0 1 17.5 18h-11A2.5 2.5 0 0 1 4 15.5v-7Z" stroke="currentColor" stroke-width="1.8"/>
                    <path d="m6 9 6 4 6-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="m10.4 13.1 1.7 1.7 3.4-3.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <div>
                <p class="text-sm font-semibold text-navy">Réponse envoyée à l'usager</p>
                <p class="mt-1 text-xs leading-5 text-neutral-500">{{ $sentAt ? 'Confirmation enregistrée le '.$sentAt.'.' : "La confirmation d'envoi a bien été enregistrée." }}</p>
            </div>
        </div>
    @elseif($deliveryState === 'ready')
        <div class="mb-3 flex items-start gap-3 rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 font-sans text-neutral-600 shadow-sm">
            <span class="mt-0.5 inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-2xl bg-neutral-200 text-navy">
                <svg class="icon-svg text-sm" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4 8.5A2.5 2.5 0 0 1 6.5 6h11A2.5 2.5 0 0 1 20 8.5v7A2.5 2.5 0 0 1 17.5 18h-11A2.5 2.5 0 0 1 4 15.5v-7Z" stroke="currentColor" stroke-width="1.8"/>
                    <path d="m6 9 6 4 6-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <div>
                <p class="text-sm font-semibold text-navy">Réponse prête pour envoi</p>
                <p class="mt-1 text-xs leading-5 text-neutral-500">L'usager n'a pas encore reçu la notification finale.</p>
            </div>
        </div>
    @endif
@else
    @if($deliveryState === 'pending')
        <span class="mt-1 inline-flex items-center gap-1.5 rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 font-sans text-[11px] font-semibold text-sky">
            <svg class="icon-svg text-[11px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M4 8.5A2.5 2.5 0 0 1 6.5 6h11A2.5 2.5 0 0 1 20 8.5v7A2.5 2.5 0 0 1 17.5 18h-11A2.5 2.5 0 0 1 4 15.5v-7Z" stroke="currentColor" stroke-width="1.8"/>
                <path d="m6 9 6 4 6-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 10.5v2.8l1.8 1.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Envoi en cours
        </span>
    @elseif($deliveryState === 'failed')
        <span class="mt-1 inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-red-50 px-2.5 py-1 font-sans text-[11px] font-semibold text-red-700">
            <svg class="icon-svg text-[11px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8"/>
                <path d="M12 8v5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M12 16.8h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Échec d'envoi
        </span>
    @elseif($deliveryState === 'sent')
        <span class="mt-1 inline-flex items-center gap-1.5 rounded-full border border-green-200 bg-leaf-50 px-2.5 py-1 font-sans text-[11px] font-semibold text-green-700">
            <svg class="icon-svg text-[11px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M4 8.5A2.5 2.5 0 0 1 6.5 6h11A2.5 2.5 0 0 1 20 8.5v7A2.5 2.5 0 0 1 17.5 18h-11A2.5 2.5 0 0 1 4 15.5v-7Z" stroke="currentColor" stroke-width="1.8"/>
                <path d="m6 9 6 4 6-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="m10.4 13.1 1.7 1.7 3.4-3.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Envoy&eacute;e
        </span>
    @elseif($deliveryState === 'ready')
        <span class="mt-1 inline-flex items-center gap-1.5 rounded-full border border-neutral-200 bg-neutral-100 px-2.5 py-1 font-sans text-[11px] font-semibold text-neutral-600">
            <svg class="icon-svg text-[11px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M4 8.5A2.5 2.5 0 0 1 6.5 6h11A2.5 2.5 0 0 1 20 8.5v7A2.5 2.5 0 0 1 17.5 18h-11A2.5 2.5 0 0 1 4 15.5v-7Z" stroke="currentColor" stroke-width="1.8"/>
                <path d="m6 9 6 4 6-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Réponse prête
        </span>
    @endif
@endif
