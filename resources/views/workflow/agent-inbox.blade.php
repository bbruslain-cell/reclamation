<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>Réponses directe des agents | ANBG</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .table-readability thead th {
            color: #111827 !important;
            font-weight: 600 !important;
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif !important;
        }
        .table-readability td,
        .table-readability td span,
        .table-readability td p {
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif !important;
        }
        .table-readability .text-neutral-400,
        .table-readability .text-neutral-500,
        .table-readability .text-neutral-600 {
            color: #1f2937 !important;
        }
        i[class*='fa-'] {
            display: inline-block;
            width: 1.14em;
            height: 1.14em;
            vertical-align: middle;
            color: currentColor;
            flex-shrink: 0;
            font-size: inherit;
            line-height: 1;
        }
        i[class*='fa-']::before {
            content: '';
            display: block;
            width: 100%;
            height: 100%;
            background-color: currentColor;
            -webkit-mask-repeat: no-repeat;
            mask-repeat: no-repeat;
            -webkit-mask-position: center;
            mask-position: center;
            -webkit-mask-size: contain;
            mask-size: contain;
        }
        .fa-chart-line::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M4 19h16v2H2V4h2v15Zm2.7-4.3 3.5-3.5 2.6 2.6 4.5-5.3 1.5 1.3-5.9 7-2.7-2.7-2.1 2.1-1.4-1.5Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M4 19h16v2H2V4h2v15Zm2.7-4.3 3.5-3.5 2.6 2.6 4.5-5.3 1.5 1.3-5.9 7-2.7-2.7-2.1 2.1-1.4-1.5Z'/%3E%3C/svg%3E"); }
        .fa-user::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-4.4 0-8 2.7-8 6v1h16v-1c0-3.3-3.6-6-8-6Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-4.4 0-8 2.7-8 6v1h16v-1c0-3.3-3.6-6-8-6Z'/%3E%3C/svg%3E\"); }
        .fa-right-from-bracket::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h5v-2H5V5h5V3Zm9.7 9-4.2-4.2-1.4 1.4 1.8 1.8H9v2h6.9l-1.8 1.8 1.4 1.4 4.2-4.2Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h5v-2H5V5h5V3Zm9.7 9-4.2-4.2-1.4 1.4 1.8 1.8H9v2h6.9l-1.8 1.8 1.4 1.4 4.2-4.2Z'/%3E%3C/svg%3E\"); }
        .fa-headset::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 3a8 8 0 0 0-8 8v5a3 3 0 0 0 3 3h2v-7H6v-1a6 6 0 1 1 12 0v1h-3v7h2a3 3 0 0 0 3-3v-5a8 8 0 0 0-8-8Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 3a8 8 0 0 0-8 8v5a3 3 0 0 0 3 3h2v-7H6v-1a6 6 0 1 1 12 0v1h-3v7h2a3 3 0 0 0 3-3v-5a8 8 0 0 0-8-8Z'/%3E%3C/svg%3E\"); }
        .fa-clock::before, .fa-hourglass-half::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 5h-2v6l5 3 1-1.7-4-2.3V7Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 5h-2v6l5 3 1-1.7-4-2.3V7Z'/%3E%3C/svg%3E\"); }
        .fa-pen::before, .fa-pen-to-square::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m16.6 3 4.4 4.4-10 10L6 19l1.6-5 9-11ZM5 21h14v-2H5v2Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m16.6 3 4.4 4.4-10 10L6 19l1.6-5 9-11ZM5 21h14v-2H5v2Z'/%3E%3C/svg%3E\"); }
        .fa-circle-check::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm-1.2 14.2-4-4 1.4-1.4 2.6 2.6 5-5 1.4 1.4Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm-1.2 14.2-4-4 1.4-1.4 2.6 2.6 5-5 1.4 1.4Z'/%3E%3C/svg%3E\"); }
        .fa-circle-exclamation::before, .fa-circle-info::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm1 13h-2v-5h2Zm0-7h-2V6h2Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm1 13h-2v-5h2Zm0-7h-2V6h2Z'/%3E%3C/svg%3E\"); }
        .fa-magnifying-glass::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10 2a8 8 0 1 0 4.9 14.3l4.4 4.4 1.4-1.4-4.4-4.4A8 8 0 0 0 10 2Zm0 2a6 6 0 1 1 0 12 6 6 0 0 1 0-12Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10 2a8 8 0 1 0 4.9 14.3l4.4 4.4 1.4-1.4-4.4-4.4A8 8 0 0 0 10 2Zm0 2a6 6 0 1 1 0 12 6 6 0 0 1 0-12Z'/%3E%3C/svg%3E\"); }
        .fa-filter::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 5h18l-7 8v5l-4 2v-7L3 5Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 5h18l-7 8v5l-4 2v-7L3 5Z'/%3E%3C/svg%3E\"); }
        .fa-inbox::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M5 3h14l3 9v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7l3-9Zm1.4 2L4.1 12H9l1 2h4l1-2h4.9L17.6 5H6.4Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M5 3h14l3 9v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7l3-9Zm1.4 2L4.1 12H9l1 2h4l1-2h4.9L17.6 5H6.4Z'/%3E%3C/svg%3E\"); }
        .fa-eye::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 5C5.6 5 2 12 2 12s3.6 7 10 7 10-7 10-7-3.6-7-10-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 5C5.6 5 2 12 2 12s3.6 7 10 7 10-7 10-7-3.6-7-10-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z'/%3E%3C/svg%3E\"); }
        .fa-eye-slash::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m3.3 2 18.7 18.7-1.4 1.4-4.1-4.1A11.7 11.7 0 0 1 12 19C5.6 19 2 12 2 12a19 19 0 0 1 4.4-5.3L1.9 3.4 3.3 2Zm6.1 6.1A4 4 0 0 0 12 16c.7 0 1.4-.2 2-.5L9.4 8.1ZM12 5c6.4 0 10 7 10 7a18.9 18.9 0 0 1-4.1 5.1l-2.2-2.2A4 4 0 0 0 9.1 8.3L7.5 6.7A10.8 10.8 0 0 1 12 5Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m3.3 2 18.7 18.7-1.4 1.4-4.1-4.1A11.7 11.7 0 0 1 12 19C5.6 19 2 12 2 12a19 19 0 0 1 4.4-5.3L1.9 3.4 3.3 2Zm6.1 6.1A4 4 0 0 0 12 16c.7 0 1.4-.2 2-.5L9.4 8.1ZM12 5c6.4 0 10 7 10 7a18.9 18.9 0 0 1-4.1 5.1l-2.2-2.2A4 4 0 0 0 9.1 8.3L7.5 6.7A10.8 10.8 0 0 1 12 5Z'/%3E%3C/svg%3E\"); }
        .fa-file-lines::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M6 2h8l4 4v16H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm7 1.5V7h3.5L13 3.5ZM8 11h8v2H8v-2Zm0 4h8v2H8v-2Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M6 2h8l4 4v16H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm7 1.5V7h3.5L13 3.5ZM8 11h8v2H8v-2Zm0 4h8v2H8v-2Z'/%3E%3C/svg%3E\"); }
        .fa-paperclip::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M8.5 12.5 13 8a3 3 0 1 1 4.2 4.2l-6 6a5 5 0 1 1-7.1-7.1l6.3-6.3 1.4 1.4-6.3 6.3a3 3 0 1 0 4.2 4.2l6-6a1 1 0 1 0-1.4-1.4l-4.5 4.5-1.4-1.4Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M8.5 12.5 13 8a3 3 0 1 1 4.2 4.2l-6 6a5 5 0 1 1-7.1-7.1l6.3-6.3 1.4 1.4-6.3 6.3a3 3 0 1 0 4.2 4.2l6-6a1 1 0 1 0-1.4-1.4l-4.5 4.5-1.4-1.4Z'/%3E%3C/svg%3E\"); }
        .fa-cloud-arrow-up::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M7 18h10a4 4 0 0 0 .4-8A6 6 0 0 0 6 11a4 4 0 0 0 1 7Zm6-6V8h-2v4H8l4 4 4-4h-3Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M7 18h10a4 4 0 0 0 .4-8A6 6 0 0 0 6 11a4 4 0 0 0 1 7Zm6-6V8h-2v4H8l4 4 4-4h-3Z'/%3E%3C/svg%3E\"); }
        .fa-envelope-circle-check::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 6h18v12H3V6Zm2 2v1l7 4 7-4V8l-7 4-7-4Zm11.2 5.8-1.4 1.4-1.3-1.3-1.4 1.4 2.7 2.7 4.8-4.8-1.4-1.4-3.4 3.4Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 6h18v12H3V6Zm2 2v1l7 4 7-4V8l-7 4-7-4Zm11.2 5.8-1.4 1.4-1.3-1.3-1.4 1.4 2.7 2.7 4.8-4.8-1.4-1.4-3.4 3.4Z'/%3E%3C/svg%3E\"); }
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
                    <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 19h16v2H2V4h2v15Zm2.7-4.3 3.5-3.5 2.6 2.6 4.5-5.3 1.5 1.3-5.9 7-2.7-2.7-2.1 2.1-1.4-1.5Z"/></svg>
                    <span>Pilotage</span>
                </a>
                @endif
                <div class="hidden sm:flex items-center gap-2 bg-white/10 border border-white/15 rounded-lg px-3 py-1.5">
                    <div class="w-6 h-6 rounded-full bg-sky/30 flex items-center justify-center flex-shrink-0">
                        <svg class="icon-svg text-sky-100 text-[10px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-4.4 0-8 2.7-8 6v1h16v-1c0-3.3-3.6-6-8-6Z"/></svg>
                    </div>
                    <span class="text-white text-xs font-medium">{{ $actor->prenom }} {{ $actor->nom }}</span>
                </div>
                <form method="post" action="/logout">
                    @csrf
                    <button type="submit"
                        class="flex items-center gap-1.5 bg-white/10 hover:bg-red-500/20 border border-white/15 hover:border-red-400/40 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition-all duration-150">
                        <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h5v-2H5V5h5V3Zm9.7 9-4.2-4.2-1.4 1.4 1.8 1.8H9v2h6.9l-1.8 1.8 1.4 1.4 4.2-4.2Z"/></svg>
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
                    <svg class="icon-svg text-sky-300 text-sm" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3a8 8 0 0 0-8 8v5a3 3 0 0 0 3 3h2v-7H6v-1a6 6 0 1 1 12 0v1h-3v7h2a3 3 0 0 0 3-3v-5a8 8 0 0 0-8-8Z"/></svg>
                </div>
                <div>
                    <h1 class="text-white text-xl font-medium leading-snug mb-1">Traitement des agents du service</h1>
                   
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-1.5 bg-sky/15 border border-sky/25 text-sky-100 text-xs font-medium px-3 py-1 rounded-full">
                            <svg class="icon-svg text-sky text-[10px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 5h-2v6l5 3 1-1.7-4-2.3V7Z"/></svg> Fenêtre partagée 48h
                        </span>
                        <span class="inline-flex items-center gap-1.5 bg-leaf/15 border border-leaf/25 text-green-100 text-xs font-medium px-3 py-1 rounded-full">
                            <svg class="icon-svg text-leaf text-[10px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m16.6 3 4.4 4.4-10 10L6 19l1.6-5 9-11ZM5 21h14v-2H5v2Z"/></svg> Réponse finale agent
                        </span>
                        <span class="inline-flex items-center gap-1.5 bg-gold/15 border border-gold/25 text-yellow-100 text-xs font-medium px-3 py-1 rounded-full">
                            <svg class="icon-svg text-gold text-[10px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm-1.2 14.2-4-4 1.4-1.4 2.6 2.6 5-5 1.4 1.4Z"/></svg> Clôture après envoi
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
            <svg class="icon-svg text-leaf mt-0.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm-1.2 14.2-4-4 1.4-1.4 2.6 2.6 5-5 1.4 1.4Z"/></svg>
            <span>{{ session('success') }}</span>
        </div>
        @endif
        @if(session('error'))
        <div class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm shadow-card">
            <svg class="icon-svg text-red-500 mt-0.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm1 13h-2v-5h2Zm0-7h-2V6h2Z"/></svg>
            <span>{{ session('error') }}</span>
        </div>
        @endif

        {{-- Barre de recherche --}}
        <div class="bg-white rounded-xl shadow-card border border-neutral-200 px-5 py-4">
            <form method="get" class="flex gap-3 items-end">
                <div class="flex-1 relative">
                    <svg class="icon-svg absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400 text-sm pointer-events-none" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M10 2a8 8 0 1 0 4.9 14.3l4.4 4.4 1.4-1.4-4.4-4.4A8 8 0 0 0 10 2Zm0 2a6 6 0 1 1 0 12 6 6 0 0 1 0-12Z"/></svg>
                    <input name="search" value="{{ $search }}"
                        placeholder="Recherche : numéro suivi, objet, usager…"
                        class="field w-full pl-9 pr-4 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy placeholder-neutral-400 transition-all duration-150">
                </div>
                <button type="submit"
                    class="inline-flex items-center gap-2 bg-navy hover:bg-navy-600 text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-colors duration-150">
                    <svg class="icon-svg text-xs" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 5h18l-7 8v5l-4 2v-7L3 5Z"/></svg> Filtrer
                </button>
            </form>
        </div>

        {{-- ══════════════════════════════════════
             TABLE DEMANDES
        ══════════════════════════════════════ --}}
        <div class="bg-white rounded-2xl shadow-card border border-neutral-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-neutral-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="icon-svg text-sky text-sm" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9 6h11v2H9V6Zm0 5h11v2H9v-2Zm0 5h11v2H9v-2ZM4.5 7.5A1.5 1.5 0 1 0 4.5 4.5a1.5 1.5 0 0 0 0 3Zm0 5A1.5 1.5 0 1 0 4.5 9.5a1.5 1.5 0 0 0 0 3Zm0 5A1.5 1.5 0 1 0 4.5 14.5a1.5 1.5 0 0 0 0 3Z"/></svg>
                    <h2 class="text-sm font-medium text-navy">Mes demandes affectées</h2>
                    <span class="bg-sky-50 text-sky text-xs font-medium px-2 py-0.5 rounded-full border border-sky-100">
                        {{ $demandes->total() }}
                    </span>
                </div>
                <p class="text-xs text-neutral-400 hidden sm:block">Répondre pour clôturer la demande</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full table-readability">
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
                                    <span class="toggle-icon"><svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 5C5.6 5 2 12 2 12s3.6 7 10 7 10-7 10-7-3.6-7-10-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z"/></svg></span>
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
                                            <svg class="icon-svg text-sky mr-1.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6 2h8l4 4v16H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm7 1.5V7h3.5L13 3.5ZM8 11h8v2H8v-2Zm0 4h8v2H8v-2Z"/></svg>Détail de la demande
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
                                                <p class="text-xs text-neutral-400">Email</p>
                                                <p class="text-sm text-navy">{{ $demande->usager_email ?? '—' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-neutral-400">Statut usager</p>
                                                <p class="text-sm text-navy">{{ $demande->usager_statut ?? '—' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-neutral-400">Pays</p>
                                                <p class="text-sm text-navy">{{ $demande->usager_pays ?? '—' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-neutral-400">Établissement</p>
                                                <p class="text-sm text-navy">{{ $demande->usager_etablissement ?? '—' }}</p>
                                            </div>
                                        </div>

                                        @if($pieces->isNotEmpty())
                                        <div>
                                            <p class="text-xs text-neutral-400 mb-1.5">Pièces jointes usager</p>
                                            <div class="space-y-1.5">
                                                @foreach($pieces as $piece)
                                                <a href="/pieces-jointes/{{ $piece->id_piece_jointe }}" target="_blank" rel="noopener"
                                                    class="flex items-center gap-2 bg-sky-50 border border-sky-100 text-sky text-xs font-medium px-3 py-2 rounded-lg hover:bg-sky-100 transition-colors duration-150">
                                                    <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8.5 12.5 13 8a3 3 0 1 1 4.2 4.2l-6 6a5 5 0 1 1-7.1-7.1l6.3-6.3 1.4 1.4-6.3 6.3a3 3 0 1 0 4.2 4.2l6-6a1 1 0 1 0-1.4-1.4l-4.5 4.5-1.4-1.4Z"/></svg>
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
                                            <svg class="icon-svg text-sky mr-1.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m16.6 3 4.4 4.4-10 10L6 19l1.6-5 9-11ZM5 21h14v-2H5v2Z"/></svg>Rédiger la réponse
                                        </h4>

                                       
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
                                                <label for="agent-files-{{ $demande->id_demande }}" class="flex flex-col items-center justify-center gap-1.5 border-2 border-dashed border-neutral-200 hover:border-sky rounded-xl p-4 cursor-pointer transition-colors duration-150 bg-neutral-50 hover:bg-sky-50">
                                                    <svg class="icon-svg text-neutral-400 text-lg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 18h10a4 4 0 0 0 .4-8A6 6 0 0 0 6 11a4 4 0 0 0 1 7Zm6-6V8h-2v4H8l4 4 4-4h-3Z"/></svg>
                                                    <span class="text-xs font-medium text-neutral-500">Ajouter des pièces jointes</span>
                                                    <span class="text-[11px] text-neutral-400">PDF, JPG, PNG — max 2 Mo chacun</span>
                                                    <input id="agent-files-{{ $demande->id_demande }}" type="file" name="pieces_jointes[]" multiple class="hidden" data-file-feedback>
                                                </label>
                                                <span class="file-selection-feedback mt-2 block text-[11px] text-neutral-400">Aucun fichier sélectionné</span>
                                            </div>

                                            <button type="submit"
                                                class="w-full flex items-center justify-center gap-2 bg-navy hover:bg-navy-600 text-white text-sm font-medium py-3 rounded-xl transition-colors duration-150">
                                                <svg class="icon-svg text-xs" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 6h18v12H3V6Zm2 2v1l7 4 7-4V8l-7 4-7-4Zm11.2 5.8-1.4 1.4-1.3-1.3-1.4 1.4 2.7 2.7 4.8-4.8-1.4-1.4-3.4 3.4Z"/></svg>
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
                                        <svg class="icon-svg text-2xl" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M5 3h14l3 9v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7l3-9Zm1.4 2L4.1 12H9l1 2h4l1-2h4.9L17.6 5H6.4Z"/></svg>
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
            const icon  = btn.querySelector('.toggle-icon');
            const label = btn.querySelector('span');
            if (isOpen) {
                icon.innerHTML    = '<svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 5C5.6 5 2 12 2 12s3.6 7 10 7 10-7 10-7-3.6-7-10-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z"/></svg>';
                label.textContent = 'Voir';
            } else {
                icon.innerHTML    = '<svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m3.3 2 18.7 18.7-1.4 1.4-4.1-4.1A11.7 11.7 0 0 1 12 19C5.6 19 2 12 2 12a19 19 0 0 1 4.4-5.3L1.9 3.4 3.3 2Zm6.1 6.1A4 4 0 0 0 12 16c.7 0 1.4-.2 2-.5L9.4 8.1ZM12 5c6.4 0 10 7 10 7a18.9 18.9 0 0 1-4.1 5.1l-2.2-2.2A4 4 0 0 0 9.1 8.3L7.5 6.7A10.8 10.8 0 0 1 12 5Z"/></svg>';
                label.textContent = 'Masquer';
            }
        });
    });

    document.querySelectorAll('input[data-file-feedback]').forEach((input) => {
        input.addEventListener('change', () => {
            const feedback = input.closest('div')?.querySelector('.file-selection-feedback');
            if (!feedback) return;
            const count = input.files?.length ?? 0;
            if (count === 0) {
                feedback.textContent = 'Aucun fichier sélectionné';
            } else if (count === 1) {
                feedback.textContent = input.files[0].name;
            } else {
                feedback.textContent = `${count} fichiers sélectionnés`;
            }
        });
    });
})();
</script>
</body>
</html>
