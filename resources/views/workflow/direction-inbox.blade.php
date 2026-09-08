<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chef de direction | ANBG</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
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
        .fa-grid-2::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M4 4h7v7H4V4Zm9 0h7v7h-7V4ZM4 13h7v7H4v-7Zm9 0h7v7h-7v-7Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M4 4h7v7H4V4Zm9 0h7v7h-7V4ZM4 13h7v7H4v-7Zm9 0h7v7h-7v-7Z'/%3E%3C/svg%3E\"); }
        .fa-chart-line::before, .fa-chart-simple::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M4 19h16v2H2V4h2v15Zm2.7-4.3 3.5-3.5 2.6 2.6 4.5-5.3 1.5 1.3-5.9 7-2.7-2.7-2.1 2.1-1.4-1.5Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M4 19h16v2H2V4h2v15Zm2.7-4.3 3.5-3.5 2.6 2.6 4.5-5.3 1.5 1.3-5.9 7-2.7-2.7-2.1 2.1-1.4-1.5Z'/%3E%3C/svg%3E\"); }
        .fa-user::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-4.4 0-8 2.7-8 6v1h16v-1c0-3.3-3.6-6-8-6Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-4.4 0-8 2.7-8 6v1h16v-1c0-3.3-3.6-6-8-6Z'/%3E%3C/svg%3E\"); }
        .fa-right-from-bracket::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h5v-2H5V5h5V3Zm9.7 9-4.2-4.2-1.4 1.4 1.8 1.8H9v2h6.9l-1.8 1.8 1.4 1.4 4.2-4.2Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h5v-2H5V5h5V3Zm9.7 9-4.2-4.2-1.4 1.4 1.8 1.8H9v2h6.9l-1.8 1.8 1.4 1.4 4.2-4.2Z'/%3E%3C/svg%3E\"); }
        .fa-building-user::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M4 20V5l7-2v17H4Zm9 0v-8h7v8h-7ZM6 7h2v2H6V7Zm0 4h2v2H6v-2Zm0 4h2v2H6v-2Zm8-8h5v3h-5V7Zm2.5 4.5a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-2.5 8.5v-1c0-1.7 1.6-3 3.5-3s3.5 1.3 3.5 3v1h-7Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M4 20V5l7-2v17H4Zm9 0v-8h7v8h-7ZM6 7h2v2H6V7Zm0 4h2v2H6v-2Zm0 4h2v2H6v-2Zm8-8h5v3h-5V7Zm2.5 4.5a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-2.5 8.5v-1c0-1.7 1.6-3 3.5-3s3.5 1.3 3.5 3v1h-7Z'/%3E%3C/svg%3E\"); }
        .fa-eye::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 5C5.6 5 2 12 2 12s3.6 7 10 7 10-7 10-7-3.6-7-10-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 5C5.6 5 2 12 2 12s3.6 7 10 7 10-7 10-7-3.6-7-10-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z'/%3E%3C/svg%3E\"); }
        .fa-eye-slash::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m3.3 2 18.7 18.7-1.4 1.4-4.1-4.1A11.7 11.7 0 0 1 12 19C5.6 19 2 12 2 12a19 19 0 0 1 4.4-5.3L1.9 3.4 3.3 2Zm6.1 6.1A4 4 0 0 0 12 16c.7 0 1.4-.2 2-.5L9.4 8.1ZM12 5c6.4 0 10 7 10 7a18.9 18.9 0 0 1-4.1 5.1l-2.2-2.2A4 4 0 0 0 9.1 8.3L7.5 6.7A10.8 10.8 0 0 1 12 5Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m3.3 2 18.7 18.7-1.4 1.4-4.1-4.1A11.7 11.7 0 0 1 12 19C5.6 19 2 12 2 12a19 19 0 0 1 4.4-5.3L1.9 3.4 3.3 2Zm6.1 6.1A4 4 0 0 0 12 16c.7 0 1.4-.2 2-.5L9.4 8.1ZM12 5c6.4 0 10 7 10 7a18.9 18.9 0 0 1-4.1 5.1l-2.2-2.2A4 4 0 0 0 9.1 8.3L7.5 6.7A10.8 10.8 0 0 1 12 5Z'/%3E%3C/svg%3E\"); }
        .fa-clipboard-list::before, .fa-table-list::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 3h6l1 2h3v16H5V5h3l1-2Zm-1 7h8V8H8v2Zm0 4h8v-2H8v2Zm0 4h8v-2H8v2Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 3h6l1 2h3v16H5V5h3l1-2Zm-1 7h8V8H8v2Zm0 4h8v-2H8v2Zm0 4h8v-2H8v2Z'/%3E%3C/svg%3E\"); }
        .fa-clock::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 5h-2v6l5 3 1-1.7-4-2.3V7Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 5h-2v6l5 3 1-1.7-4-2.3V7Z'/%3E%3C/svg%3E\"); }
        .fa-circle-check::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm-1.2 14.2-4-4 1.4-1.4 2.6 2.6 5-5 1.4 1.4Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm-1.2 14.2-4-4 1.4-1.4 2.6 2.6 5-5 1.4 1.4Z'/%3E%3C/svg%3E\"); }
        .fa-circle-exclamation::before, .fa-circle-info::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm1 13h-2v-5h2Zm0-7h-2V6h2Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm1 13h-2v-5h2Zm0-7h-2V6h2Z'/%3E%3C/svg%3E\"); }
        .fa-magnifying-glass::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10 2a8 8 0 1 0 4.9 14.3l4.4 4.4 1.4-1.4-4.4-4.4A8 8 0 0 0 10 2Zm0 2a6 6 0 1 1 0 12 6 6 0 0 1 0-12Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10 2a8 8 0 1 0 4.9 14.3l4.4 4.4 1.4-1.4-4.4-4.4A8 8 0 0 0 10 2Zm0 2a6 6 0 1 1 0 12 6 6 0 0 1 0-12Z'/%3E%3C/svg%3E\"); }
        .fa-filter::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 5h18l-7 8v5l-4 2v-7L3 5Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 5h18l-7 8v5l-4 2v-7L3 5Z'/%3E%3C/svg%3E\"); }
        .fa-file-lines::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M6 2h8l4 4v16H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm7 1.5V7h3.5L13 3.5ZM8 11h8v2H8v-2Zm0 4h8v2H8v-2Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M6 2h8l4 4v16H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm7 1.5V7h3.5L13 3.5ZM8 11h8v2H8v-2Zm0 4h8v2H8v-2Z'/%3E%3C/svg%3E\"); }
        .fa-paperclip::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M8.5 12.5 13 8a3 3 0 1 1 4.2 4.2l-6 6a5 5 0 1 1-7.1-7.1l6.3-6.3 1.4 1.4-6.3 6.3a3 3 0 1 0 4.2 4.2l6-6a1 1 0 1 0-1.4-1.4l-4.5 4.5-1.4-1.4Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M8.5 12.5 13 8a3 3 0 1 1 4.2 4.2l-6 6a5 5 0 1 1-7.1-7.1l6.3-6.3 1.4 1.4-6.3 6.3a3 3 0 1 0 4.2 4.2l6-6a1 1 0 1 0-1.4-1.4l-4.5 4.5-1.4-1.4Z'/%3E%3C/svg%3E\"); }
        .fa-binoculars::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M8 4h3l1 5v2H8l-1 3a4 4 0 1 1-3 0l2-7h2Zm8 0h-3l-1 5v2h4l1 3a4 4 0 1 0 3 0l-2-7h-2ZM7 16a2 2 0 1 0-4 0 2 2 0 0 0 4 0Zm14 0a2 2 0 1 0-4 0 2 2 0 0 0 4 0Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M8 4h3l1 5v2H8l-1 3a4 4 0 1 1-3 0l2-7h2Zm8 0h-3l-1 5v2h4l1 3a4 4 0 1 0 3 0l-2-7h-2ZM7 16a2 2 0 1 0-4 0 2 2 0 0 0 4 0Zm14 0a2 2 0 1 0-4 0 2 2 0 0 0 4 0Z'/%3E%3C/svg%3E\"); }

        body {
            background:
                radial-gradient(circle at top left, rgba(57, 150, 211, 0.14), transparent 12%),
                radial-gradient(circle at top right, rgba(143, 192, 67, 0.14), transparent 26%),
                linear-gradient(180deg, #fff 0%, #fff 100%);
        }
        .surface-card {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(197, 203, 217, 0.55);
            box-shadow: 0 18px 40px rgba(28, 32, 61, 0.08);
            backdrop-filter: blur(10px);
        }
        .hero-shell {
            background: linear-gradient(135deg, rgba(28, 32, 61, 0.98) 0%, rgba(28, 32, 61, 0.92) 48%, rgba(57, 150, 211, 0.94) 100%);
            position: relative;
            overflow: hidden;
        }
        .hero-shell::before {
            content: '';
            position: absolute;
            inset: -20% auto auto -10%;
            width: 280px;
            height: 280px;
            background: radial-gradient(circle, rgba(248, 233, 50, 0.22) 0%, transparent 68%);
            pointer-events: none;
        }
        .hero-shell::after {
            content: '';
            position: absolute;
            right: -60px;
            bottom: -80px;
            width: 260px;
            height: 260px;
            background: radial-gradient(circle, rgba(143, 192, 67, 0.28) 0%, transparent 70%);
            pointer-events: none;
        }
        .hero-chip {
            border: 1px solid rgba(255, 255, 255, 0.16);
            background: rgba(255, 255, 255, 0.10);
            backdrop-filter: blur(8px);
        }
        .kpi-card {
            position: relative;
            overflow: hidden;
        }
        .kpi-card::after {
            content: '';
            position: absolute;
            inset: auto -35% -45% auto;
            width: 120px;
            height: 120px;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.16);
        }
        .kpi-card > * {
            position: relative;
            z-index: 1;
        }
        .section-title-bar {
            background: linear-gradient(90deg, rgba(28, 32, 61, 0.06), rgba(57, 150, 211, 0.04) 50%, rgba(143, 192, 67, 0.06));
        }
        .table-head {
            background: linear-gradient(90deg, rgba(28, 32, 61, 0.96), rgba(57, 150, 211, 0.92));
        }
        .table-head th {
            color: rgba(255, 255, 255, 0.92);
        }
        .icon-svg {
            display: inline-block;
            width: 1em;
            height: 1em;
            vertical-align: middle;
            flex-shrink: 0;
        }
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
        $filtersActifs = collect([
            $search !== '' ? 'Recherche active' : null,
            request('statut_code') ? 'Statut filtré' : null,
        ])->filter()->values();
    @endphp
    <header class="bg-navy sticky top-0 z-50 shadow-md">
        <div class="app-page-frame h-14 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="bg-white rounded-lg px-2.5 py-1.5 flex-shrink-0">
                    <img src="/Logo_anbg.png" alt="ANBG" class="h-7 w-auto object-contain block">
                </div>
                <div class="hidden sm:block">
                    <span class="text-white text-xs font-medium tracking-wider uppercase">Interface chef de direction</span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="/espace"
                    class="hidden sm:inline-flex items-center gap-1.5 bg-white/10 hover:bg-white/20 border border-white/15 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition-all duration-150">
                    <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 4h7v7H4V4Zm9 0h7v7h-7V4ZM4 13h7v7H4v-7Zm9 0h7v7h-7v-7Z" fill="currentColor"/>
                    </svg>
                    <span>Mon espace</span>
                </a>
                @if($canPilotage)
                <a href="/pilotage"
                    class="hidden sm:inline-flex items-center gap-1.5 bg-white/10 hover:bg-white/20 border border-white/15 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition-all duration-150">
                    <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 19h16v2H2V4h2v15Zm2.7-4.3 3.5-3.5 2.6 2.6 4.5-5.3 1.5 1.3-5.9 7-2.7-2.7-2.1 2.1-1.4-1.5Z" fill="currentColor"/>
                    </svg>
                    <span>Pilotage</span>
                </a>
                @endif
                <div class="hidden sm:flex items-center gap-2 bg-white/10 border border-white/15 rounded-lg px-3 py-1.5">
                    <div class="w-6 h-6 rounded-full bg-sky/30 flex items-center justify-center flex-shrink-0">
                        <svg class="icon-svg text-sky-100 text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="8" r="3.2" stroke="currentColor" stroke-width="2"/>
                            <path d="M6 18c1.4-2.8 4-4.2 6-4.2s4.6 1.4 6 4.2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <span class="text-white text-xs font-medium">{{ $actor->prenom }} {{ $actor->nom }}</span>
                </div>
                <form method="post" action="/logout">
                    @csrf
                    <button type="submit"
                        class="flex items-center gap-1.5 bg-white/10 hover:bg-red-500/20 border border-white/15 hover:border-red-400/40 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition-all duration-150">
                        <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M10 6H7.5A2.5 2.5 0 0 0 5 8.5v7A2.5 2.5 0 0 0 7.5 18H10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M13 8l4 4-4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M9 12h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span class="hidden sm:inline">Déconnexion</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <section class="hero-shell rounded-[28px] app-page-frame py-6 sm:py-7 text-white shadow-card mt-6">
        <div class="relative z-10 grid gap-6 lg:grid-cols-[1.45fr_0.85fr] lg:items-end">
            <div class="space-y-4">
                <div class="space-y-6">
                    <h1 class="text-2xl font-bold leading-tight sm:text-[2rem]">Supervision transverse de votre direction</h1>
                    <p class="max-w-3xl text-sm leading-6 text-white/78 sm:text-[15px]">
                        Cette vue donne un suivi global des services rattachés à votre direction : volumes, conformité, retards et lecture détaillée des dossiers sans modifier les réponses usager.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="hero-chip inline-flex items-center gap-2 rounded-full px-3 py-2 text-xs">
                        <svg class="icon-svg text-[#f8e932]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 20V5l7-2v17H4Zm9 0v-8h7v8h-7ZM6 7h2v2H6V7Zm0 4h2v2H6v-2Zm0 4h2v2H6v-2Z" fill="currentColor"/>
                            <path d="M14 7h5v3h-5z" fill="currentColor"/>
                        </svg>
                        {{ number_format((int) ($serviceSummary['services_actifs'] ?? 0), 0, ',', ' ') }} services actifs
                    </span>
                    <span class="hero-chip inline-flex items-center gap-2 rounded-full px-3 py-2 text-xs">
                        <svg class="icon-svg text-[#8fc043]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M9 3h6l1 2h3v16H5V5h3l1-2Zm-1 7h8V8H8v2Zm0 4h8v-2H8v2Zm0 4h8v-2H8v2Z" fill="currentColor"/>
                        </svg>
                        {{ number_format((int) $demandes->total(), 0, ',', ' ') }} transactions suivies
                    </span>
                    <span class="hero-chip inline-flex items-center gap-2 rounded-full px-3 py-2 text-xs">
                        <svg class="icon-svg text-[#3996d3]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 5h-2v6l5 3 1-1.7-4-2.3V7Z" fill="currentColor"/>
                        </svg>
                        {{ $filtersActifs->isNotEmpty() ? $filtersActifs->implode('-') : 'Vue générale sans filtre' }}
                    </span>
                </div>
            </div>
            
        </div>
    </section>

    <main class="app-page-frame py-6 space-y-6">
        @if(session('success'))
        <div class="flex items-start gap-3 bg-leaf-50 border border-leaf/30 text-green-800 px-4 py-3 rounded-xl text-sm shadow-card">
            <svg class="icon-svg text-leaf mt-0.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/>
                <path d="m8.5 12 2.3 2.3L15.5 9.7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
        @endif
        @if(session('error'))
        <div class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm shadow-card">
            <svg class="icon-svg text-red-500 mt-0.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/>
                <path d="M12 8v5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <circle cx="12" cy="16.8" r="1" fill="currentColor"/>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
        @endif

        <section class="surface-card rounded-[26px] px-5 py-5 sm:px-6">
            <form method="get" class="grid grid-cols-1 md:grid-cols-[1fr_220px_auto] gap-3 items-end">
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400 pointer-events-none">
                        <svg class="icon-svg text-sm" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M10 2a8 8 0 1 0 4.9 14.3l4.4 4.4 1.4-1.4-4.4-4.4A8 8 0 0 0 10 2Zm0 2a6 6 0 1 1 0 12 6 6 0 0 1 0-12Z" fill="currentColor"/>
                        </svg>
                    </span>
                    <input name="search" value="{{ $search }}"
                        placeholder="Recherche : numéro suivi, objet, usager..."
                        class="field w-full rounded-2xl border border-neutral-200 bg-white/80 py-3 pl-10 pr-4 text-sm text-navy placeholder-neutral-400 transition-all duration-150">
                </div>
                <div>
                    <label for="statut_code" class="mb-1 block text-xs text-neutral-500">Statut</label>
                    <select id="statut_code" name="statut_code"
                        class="field w-full rounded-2xl border border-neutral-200 bg-white/80 px-3 py-3 text-sm text-navy">
                        <option value="">Tous statuts</option>
                        @foreach($statuts as $st)
                        <option value="{{ $st->code }}" @selected(request('statut_code') === $st->code)>{{ $st->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-navy px-5 py-3 text-sm font-medium text-white transition-colors duration-150 hover:bg-navy-600">
                    <svg class="icon-svg text-xs" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M3 5h18l-7 8v5l-4 2v-7L3 5Z" fill="currentColor"/>
                    </svg>
                    <span>Filtrer</span>
                </button>
            </form>
        </section>

        <section id="direction-kpi-content" class="flex flex-wrap items-stretch justify-center gap-3">
            <article class="kpi-card aspect-square w-[146px] rounded-[22px] bg-[linear-gradient(135deg,#1c203d_0%,#2a3163_100%)] px-4 py-4 text-white shadow-card">
                <div class="flex h-full flex-col items-center justify-center text-center gap-1.5">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-white/12">
                        <svg class="icon-svg text-base" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 20V5l7-2v17H4Zm9 0v-8h7v8h-7Z" fill="currentColor"/>
                        </svg>
                    </span>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/68">Services actifs</p>
                    <p class="text-[1.75rem] font-bold leading-none" data-countup="{{ (int) ($serviceSummary['services_actifs'] ?? 0) }}">{{ number_format((int) ($serviceSummary['services_actifs'] ?? 0), 0, ',', ' ') }}</p>
                    <p class="text-xs text-white/68">Sur le filtre courant</p>
                </div>
            </article>
            <article class="kpi-card aspect-square w-[146px] rounded-[22px] bg-[linear-gradient(135deg,#3996d3_0%,#1c203d_120%)] px-4 py-4 text-white shadow-card">
                <div class="flex h-full flex-col items-center justify-center text-center gap-1.5">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-white/12">
                        <svg class="icon-svg text-base" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M9 3h6l1 2h3v16H5V5h3l1-2Zm-1 7h8V8H8v2Zm0 4h8v-2H8v2Zm0 4h8v-2H8v2Z" fill="currentColor"/>
                        </svg>
                    </span>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/70">Service chargé</p>
                    <p class="text-sm font-bold leading-tight">{{ $serviceSummary['service_plus_charge'] ?? '-' }}</p>
                    <p class="text-xs text-white/68">{{ number_format((int) ($serviceSummary['demandes_plus_charge'] ?? 0), 0, ',', ' ') }} demandes</p>
                </div>
            </article>
            <article class="kpi-card aspect-square w-[146px] rounded-[22px] bg-[linear-gradient(135deg,#8fc043_0%,#45661c_115%)] px-4 py-4 text-white shadow-card">
                <div class="flex h-full flex-col items-center justify-center text-center gap-1.5">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-white/12">
                        <svg class="icon-svg text-base" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M12 7.8v5.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <circle cx="12" cy="16.8" r="1" fill="currentColor"/>
                        </svg>
                    </span>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/72">Plus en retard</p>
                    <p class="text-sm font-bold leading-tight">{{ $serviceSummary['service_plus_retard'] ?? '-' }}</p>
                    <p class="text-xs text-white/68">{{ number_format((int) ($serviceSummary['retards_max'] ?? 0), 0, ',', ' ') }} en retard</p>
                </div>
            </article>
            <article class="kpi-card aspect-square w-[146px] rounded-[22px] bg-[linear-gradient(135deg,#f8e932_0%,#d9a90a_110%)] px-4 py-4 text-[#1c203d] shadow-card">
                <div class="flex h-full flex-col items-center justify-center text-center gap-1.5">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-white/35">
                        <svg class="icon-svg text-base" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 19h16v2H2V4h2v15Zm2.7-4.3 3.5-3.5 2.6 2.6 4.5-5.3 1.5 1.3-5.9 7-2.7-2.7-2.1 2.1-1.4-1.5Z" fill="currentColor"/>
                        </svg>
                    </span>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#1c203d]/72">Conformité max</p>
                    <p class="text-sm font-bold leading-tight">{{ $serviceSummary['meilleur_service'] ?? '-' }}</p>
                    <p class="text-xs text-[#1c203d]/72">{{ number_format((float) ($serviceSummary['meilleur_taux'] ?? 0), 1, ',', ' ') }} %</p>
                </div>
            </article>
        </section>

        <div id="direction-live-content"
            class="space-y-6"
            data-refresh-url="{{ request()->fullUrl() }}"
            data-refresh-interval="20000">
        <section class="space-y-5">
            <div>
                <h2 class="text-sm font-medium text-navy">Performance des services</h2>
                <p class="mt-1 text-xs text-neutral-400">Lecture comparative des services de votre direction sur le volume, la clôture et le respect des délais.</p>
            </div>

            <section class="surface-card rounded-[26px] overflow-hidden">
                <div class="section-title-bar px-5 py-4 border-b border-neutral-200 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-white text-sky shadow-sm"><svg class="icon-svg text-sm" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 19h16v2H2V4h2v15Zm2.7-4.3 3.5-3.5 2.6 2.6 4.5-5.3 1.5 1.3-5.9 7-2.7-2.7-2.1 2.1-1.4-1.5Z" fill="currentColor"/></svg></span>
                        <h3 class="text-sm font-medium text-navy">Comparatif des services</h3>
                    </div>
                    <p class="text-xs text-neutral-400 hidden sm:block">Une ligne par service</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="table-head">
                            <tr>
                                <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">Service</th>
                                <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">Reçues</th>
                                <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">Clôturées</th>
                                <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">En cours</th>
                                <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">Dans les délais</th>
                                <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">À risque</th>
                                <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">En retard</th>
                                <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">Conformité</th>
                                <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">Délai moyen</th>
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

        <section class="surface-card rounded-[26px] overflow-hidden">
            <div class="section-title-bar px-5 py-4 border-b border-neutral-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-white text-sky shadow-sm"><svg class="icon-svg text-sm" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 3h6l1 2h3v16H5V5h3l1-2Zm-1 7h8V8H8v2Zm0 4h8v-2H8v2Zm0 4h8v-2H8v2Z" fill="currentColor"/></svg></span>
                    <h2 class="text-sm font-medium text-navy">Transactions de la direction</h2>
                    <span class="bg-sky-50 text-sky text-xs font-medium px-2 py-0.5 rounded-full border border-sky-100">
                        {{ $demandes->total() }}
                    </span>
                </div>
                <p class="text-xs text-neutral-400 hidden sm:block">Suivi transversal des demandes de votre périmètre</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="table-head">
                        <tr>
                            <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">No Suivi</th>
                            <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">Usager</th>
                            <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">Service</th>
                            <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">Statut</th>
                            <th class="px-4 py-3 text-left text-[11px] font-medium text-neutral-500 uppercase tracking-wider">Alerte globale 24h</th>
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
                                    <span class="w-1.5 h-1.5 rounded-full bg-gold flex-shrink-0"></span> À risque
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1.5 bg-leaf-50 text-green-700 border border-green-200 text-xs font-medium px-2.5 py-1 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-leaf flex-shrink-0"></span> Dans les délais
                                </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <button type="button"
                                    class="view-toggle inline-flex items-center gap-1.5 bg-sky-50 hover:bg-sky text-sky hover:text-white border border-sky-200 hover:border-sky text-xs font-medium px-3 py-1.5 rounded-lg transition-all duration-150"
                                    data-target="detail-d-{{ $demande->id_demande }}" aria-expanded="false">
                                    <span class="icon-slot"><svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5C5.6 5 2 12 2 12s3.6 7 10 7 10-7 10-7-3.6-7-10-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z" fill="currentColor"/></svg></span>
                                    <span class="label-slot">Voir</span>
                                </button>
                            </td>
                        </tr>

                        <tr id="detail-d-{{ $demande->id_demande }}" class="detail-row" style="display:none;">
                            <td colspan="6" class="px-4 py-4 bg-neutral-50 border-b border-neutral-100">
                                @php $pieces = $piecesByDemand->get($demande->id_demande, collect()); @endphp
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    <div class="bg-white border border-neutral-200 rounded-xl p-4 space-y-3">
                                        <h4 class="text-xs font-medium text-neutral-500 uppercase tracking-wider border-b border-neutral-100 pb-2">
                                            <span class="inline-flex items-center gap-1.5"><svg class="icon-svg text-sky" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 2h8l4 4v16H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm7 1.5V7h3.5L13 3.5ZM8 11h8v2H8v-2Zm0 4h8v2H8v-2Z" fill="currentColor"/></svg><span>Détail de la demande</span></span>
                                        </h4>

                                        <div>
                                            <p class="text-xs text-neutral-400 mb-0.5">Objet</p>
                                            <p class="text-sm font-medium text-navy">{{ $demande->objet }}</p>
                                        </div>

                                        <div>
                                            <p class="text-xs text-neutral-400 mb-1">Message complet</p>
                                            <div class="bg-neutral-50 border border-neutral-200 rounded-lg p-3 text-sm text-neutral-700 leading-relaxed whitespace-pre-wrap max-h-44 overflow-y-auto">{{ $demande->message }}</div>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                            <div>
                                                <p class="text-xs text-neutral-400">Usager</p>
                                                <p class="text-sm text-navy">{{ trim(($demande->usager_prenom ?? '').' '.($demande->usager_nom ?? '')) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-neutral-400">Type</p>
                                                <p class="text-sm text-navy">{{ $demande->type_demande }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-neutral-400">Email</p>
                                                <p class="text-sm text-navy">{{ $demande->usager_email ?? '-' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-neutral-400">Statut usager</p>
                                                <p class="text-sm text-navy">{{ $demande->usager_statut ?? '-' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-neutral-400">Pays</p>
                                                <p class="text-sm text-navy">{{ $demande->usager_pays ?? '-' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-neutral-400">Etablissement</p>
                                                <p class="text-sm text-navy">{{ $demande->usager_etablissement ?? '-' }}</p>
                                            </div>
                                        </div>

                                        @if($pieces->isNotEmpty())
                                        <div>
                                            <p class="text-xs text-neutral-400 mb-1.5">Pièces jointes usager</p>
                                            <div class="space-y-1.5">
                                                @foreach($pieces as $piece)
                                                @include('workflow.partials.attachment-actions', ['piece' => $piece])
                                                @endforeach
                                            </div>
                                        </div>
                                        @else
                                        <p class="text-xs text-neutral-400 italic">Aucune pièce jointe usager.</p>
                                        @endif
                                    </div>

                                    <div class="bg-white border border-neutral-200 rounded-xl p-4 space-y-4">
                                        <h4 class="text-xs font-medium text-neutral-500 uppercase tracking-wider border-b border-neutral-100 pb-2">
                                            <span class="inline-flex items-center gap-1.5"><svg class="icon-svg text-sky" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 4h3l1 5v2H8l-1 3a4 4 0 1 1-3 0l2-7h2Zm8 0h-3l-1 5v2h4l1 3a4 4 0 1 0 3 0l-2-7h-2ZM7 16a2 2 0 1 0-4 0 2 2 0 0 0 4 0Zm14 0a2 2 0 1 0-4 0 2 2 0 0 0 4 0Z" fill="currentColor"/></svg><span>Suivi de supervision</span></span>
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
                                                <p class="text-[11px] text-neutral-400 uppercase tracking-wide">Clôture</p>
                                                <p class="mt-1 text-sm text-navy">{{ $demande->date_cloture ?? '-' }}</p>
                                            </div>
                                            <div class="bg-neutral-50 border border-neutral-200 rounded-lg p-3">
                                                <p class="text-[11px] text-neutral-400 uppercase tracking-wide">Alerte chef / agent</p>
                                                <p class="mt-1 text-sm text-navy">{{ $demande->alerte_chef ?? '-' }} / {{ $demande->alerte_agent ?? '-' }}</p>
                                            </div>
                                        </div>

                                        <div class="flex items-start gap-2 bg-sky-50 border border-sky-100 rounded-lg px-3 py-2.5">
                                            <svg class="icon-svg text-sky text-xs mt-0.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/><path d="M12 10v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="7.8" r="1" fill="currentColor"/></svg>
                                            <p class="text-xs text-sky-700 leading-relaxed">
                                                Consultation uniquement : le chef de direction supervise les services de son périmètre et les indicateurs de délai, sans modifier la réponse usager.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-neutral-400">Aucune demande sur votre périmètre.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-4 border-t border-neutral-100">
                {{ $demandes->links() }}
            </div>
        </section>
        </div>

    </main>

    <script nonce="{{ $cspNonce ?? '' }}">
    (function () {
        const eyeSvg = '<svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5C5.6 5 2 12 2 12s3.6 7 10 7 10-7 10-7-3.6-7-10-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z" fill="currentColor"/></svg>';
        const eyeSlashSvg = '<svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m3.3 2 18.7 18.7-1.4 1.4-4.1-4.1A11.7 11.7 0 0 1 12 19C5.6 19 2 12 2 12a19 19 0 0 1 4.4-5.3L1.9 3.4 3.3 2Zm6.1 6.1A4 4 0 0 0 12 16c.7 0 1.4-.2 2-.5L9.4 8.1ZM12 5c6.4 0 10 7 10 7a18.9 18.9 0 0 1-4.1 5.1l-2.2-2.2A4 4 0 0 0 9.1 8.3L7.5 6.7A10.8 10.8 0 0 1 12 5Z" fill="currentColor"/></svg>';
        const liveContent = document.getElementById('direction-live-content');
        const kpiContent = document.getElementById('direction-kpi-content');

        const animateCounters = (root = document) => {
            root.querySelectorAll('[data-countup]:not([data-countup-ready])').forEach((node) => {
                node.dataset.countupReady = '1';
                const target = Number(node.getAttribute('data-countup') || 0);
                const duration = 700;
                const startTime = performance.now();

                const tick = (now) => {
                    const progress = Math.min((now - startTime) / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    node.textContent = Math.round(target * eased).toLocaleString('fr-FR');
                    if (progress < 1) {
                        requestAnimationFrame(tick);
                    }
                };

                requestAnimationFrame(tick);
            });
        };

        const initViewToggles = (root = document) => {
            root.querySelectorAll('.view-toggle:not([data-toggle-ready])').forEach((btn) => {
                btn.dataset.toggleReady = '1';
                btn.addEventListener('click', () => {
                    const row = document.getElementById(btn.dataset.target);
                    if (!row) {
                        return;
                    }

                    const isOpen = row.style.display !== 'none';
                    row.style.display = isOpen ? 'none' : '';
                    btn.setAttribute('aria-expanded', String(!isOpen));

                    const icon = btn.querySelector('.icon-slot');
                    const label = btn.querySelector('.label-slot');
                    if (isOpen) {
                        if (icon) icon.innerHTML = eyeSvg;
                        if (label) label.textContent = 'Voir';
                    } else {
                        if (icon) icon.innerHTML = eyeSlashSvg;
                        if (label) label.textContent = 'Masquer';
                    }
                });
            });
        };

        const initDirectionInteractions = (root = document) => {
            animateCounters(root);
            initViewToggles(root);
        };

        const openDetailIds = () => {
            if (!liveContent) return [];

            return Array.from(liveContent.querySelectorAll('.detail-row'))
                .filter((row) => row.style.display !== 'none')
                .map((row) => row.id);
        };

        const restoreOpenDetails = (ids) => {
            ids.forEach((id) => {
                const row = document.getElementById(id);
                if (!row) return;

                row.style.display = '';

                const btn = Array.from(document.querySelectorAll('.view-toggle'))
                    .find((candidate) => candidate.dataset.target === id);
                if (!btn) return;

                btn.setAttribute('aria-expanded', 'true');
                const icon = btn.querySelector('.icon-slot');
                const label = btn.querySelector('.label-slot');
                if (icon) icon.innerHTML = eyeSlashSvg;
                if (label) label.textContent = 'Masquer';
            });
        };

        const shouldSkipRefresh = (isRefreshing) => {
            return !liveContent || isRefreshing || document.hidden;
        };

        const initDirectionAutoRefresh = () => {
            if (!liveContent || liveContent.dataset.autoRefreshReady === '1') return;
            liveContent.dataset.autoRefreshReady = '1';

            let isRefreshing = false;
            const interval = Math.max(Number(liveContent.dataset.refreshInterval || 20000), 10000);

            const refreshContent = async () => {
                if (shouldSkipRefresh(isRefreshing)) return;
                isRefreshing = true;

                try {
                    const url = new URL(liveContent.dataset.refreshUrl || window.location.href, window.location.origin);
                    url.searchParams.set('_direction_refresh', Date.now().toString());

                    const response = await fetch(url.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-Direction-Refresh': 'tables',
                        },
                        cache: 'no-store',
                    });

                    if (!response.ok) return;

                    const html = await response.text();
                    const nextDocument = new DOMParser().parseFromString(html, 'text/html');
                    const nextKpiContent = nextDocument.getElementById('direction-kpi-content');
                    const nextContent = nextDocument.getElementById('direction-live-content');

                    if (nextKpiContent && kpiContent) {
                        kpiContent.innerHTML = nextKpiContent.innerHTML;
                        animateCounters(kpiContent);
                    }

                    if (!nextContent) return;

                    const openedDetails = openDetailIds();

                    liveContent.innerHTML = nextContent.innerHTML;
                    initDirectionInteractions(liveContent);
                    restoreOpenDetails(openedDetails);
                } catch (error) {
                    console.warn('Rafraîchissement direction interrompu.', error);
                } finally {
                    isRefreshing = false;
                }
            };

            window.setInterval(refreshContent, interval);
            window.addEventListener('focus', refreshContent);
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) refreshContent();
            });
        };

        initDirectionInteractions(document);
        initDirectionAutoRefresh();
    })();
    </script>
</body>
</html>
