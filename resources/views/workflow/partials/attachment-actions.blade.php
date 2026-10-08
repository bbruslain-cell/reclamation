@php
    $attachmentUrl = url('/pieces-jointes/'.$piece->id_piece_jointe);
    $previewUrl = $attachmentUrl.'?preview=1';
@endphp

<div class="flex flex-col gap-2 rounded-lg border border-sky-100 bg-sky-50 px-3 py-2 text-xs text-sky">
    <div class="flex min-w-0 items-center gap-2 font-medium">
        <svg class="icon-svg text-[10px] flex-shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M8.5 12.5 13 8a3 3 0 1 1 4.2 4.2l-6 6a5 5 0 1 1-7.1-7.1l6.3-6.3 1.4 1.4-6.3 6.3a3 3 0 1 0 4.2 4.2l6-6a1 1 0 1 0-1.4-1.4l-4.5 4.5-1.4-1.4Z" fill="currentColor"/>
        </svg>
        <span class="truncate">{{ $piece->nom_fichier }}</span>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ $previewUrl }}"
            data-attachment-preview
            data-attachment-name="{{ $piece->nom_fichier }}"
            class="inline-flex items-center gap-1.5 rounded-md border border-sky-200 bg-white px-2.5 py-1.5 font-medium text-sky transition-colors duration-150 hover:bg-sky-100"
            title="Ouvrir l'aperçu">
            <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 5c6 0 9.5 7 9.5 7s-3.5 7-9.5 7-9.5-7-9.5-7S6 5 12 5Zm0 2C8.5 7 6 10.3 4.8 12c1.2 1.7 3.7 5 7.2 5s6-3.3 7.2-5C18 10.3 15.5 7 12 7Zm0 2.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5Z" fill="currentColor"/>
            </svg>
            Aperçu
        </a>
        <a href="{{ $attachmentUrl }}"
            class="inline-flex items-center gap-1.5 rounded-md border border-neutral-200 bg-white px-2.5 py-1.5 font-medium text-neutral-600 transition-colors duration-150 hover:bg-neutral-100"
            title="Télécharger le fichier">
            <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 3v10m0 0 4-4m-4 4-4-4M5 17v2h14v-2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Télécharger
        </a>
    </div>
</div>
