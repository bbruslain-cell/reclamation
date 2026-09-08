<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ANBG - Administration - @yield('title', 'Accueil')</title>
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

        .fa-chart-line::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M4 19h16v2H2V4h2v15Zm2.7-4.3 3.5-3.5 2.6 2.6 4.5-5.3 1.5 1.3-5.9 7-2.7-2.7-2.1 2.1-1.4-1.5Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M4 19h16v2H2V4h2v15Zm2.7-4.3 3.5-3.5 2.6 2.6 4.5-5.3 1.5 1.3-5.9 7-2.7-2.7-2.1 2.1-1.4-1.5Z'/%3E%3C/svg%3E"); }
        .fa-users::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm6 1a3 3 0 1 0-2.7-4.3A6 6 0 0 1 15 12Zm-6 2c-3.3 0-6 2.1-6 4.7V21h12v-2.3C15 16.1 12.3 14 9 14Zm6 0c-.5 0-1 .1-1.5.2 1.5 1.1 2.5 2.7 2.5 4.5V21h6v-2.3c0-2.6-2.7-4.7-6-4.7Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm6 1a3 3 0 1 0-2.7-4.3A6 6 0 0 1 15 12Zm-6 2c-3.3 0-6 2.1-6 4.7V21h12v-2.3C15 16.1 12.3 14 9 14Zm6 0c-.5 0-1 .1-1.5.2 1.5 1.1 2.5 2.7 2.5 4.5V21h6v-2.3c0-2.6-2.7-4.7-6-4.7Z'/%3E%3C/svg%3E"); }
        .fa-key::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M14 3a7 7 0 0 0-6.7 9l-5.3 5.3V21h3.7l1.5-1.5v-1.8H9v-1.8h1.8l1.5-1.5A7 7 0 1 0 14 3Zm0 2a5 5 0 1 1 0 10 5 5 0 0 1 0-10Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M14 3a7 7 0 0 0-6.7 9l-5.3 5.3V21h3.7l1.5-1.5v-1.8H9v-1.8h1.8l1.5-1.5A7 7 0 1 0 14 3Zm0 2a5 5 0 1 1 0 10 5 5 0 0 1 0-10Z'/%3E%3C/svg%3E"); }
        .fa-building::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M4 21V5a2 2 0 0 1 2-2h8v18H4Zm4-14H6v2h2V7Zm4 0h-2v2h2V7Zm-4 4H6v2h2v-2Zm4 0h-2v2h2v-2Zm7 10h-4V9l4 2v10Zm-2-6h-1v2h1v-2Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M4 21V5a2 2 0 0 1 2-2h8v18H4Zm4-14H6v2h2V7Zm4 0h-2v2h2V7Zm-4 4H6v2h2v-2Zm4 0h-2v2h2v-2Zm7 10h-4V9l4 2v10Zm-2-6h-1v2h1v-2Z'/%3E%3C/svg%3E"); }
        .fa-cubes::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m11 2 7 4v8l-7 4-7-4V6l7-4Zm0 2.3L6 7l5 2.8L16 7l-5-2.7ZM5 8.7v4.6l5 2.9v-4.6L5 8.7Zm7 7.5 5-2.9V8.7l-5 2.9v4.6ZM19 11l3 1.7v6.6L16.5 22v-2.3l3.5-2V11ZM2 12.7 5 11v6.7l3.5 2V22L2 19.3v-6.6Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m11 2 7 4v8l-7 4-7-4V6l7-4Zm0 2.3L6 7l5 2.8L16 7l-5-2.7ZM5 8.7v4.6l5 2.9v-4.6L5 8.7Zm7 7.5 5-2.9V8.7l-5 2.9v4.6ZM19 11l3 1.7v6.6L16.5 22v-2.3l3.5-2V11ZM2 12.7 5 11v6.7l3.5 2V22L2 19.3v-6.6Z'/%3E%3C/svg%3E"); }
        .fa-sliders-h::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 7h8v2H3V7Zm10 0h8v2h-8V7ZM9 6a2 2 0 1 1 0 4 2 2 0 0 1 0-4Zm6 9H3v2h12v-2Zm2-1a2 2 0 1 1 0 4 2 2 0 0 1 0-4Zm2 1h2v2h-2v-2Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 7h8v2H3V7Zm10 0h8v2h-8V7ZM9 6a2 2 0 1 1 0 4 2 2 0 0 1 0-4Zm6 9H3v2h12v-2Zm2-1a2 2 0 1 1 0 4 2 2 0 0 1 0-4Zm2 1h2v2h-2v-2Z'/%3E%3C/svg%3E"); }
        .fa-right-from-bracket::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h5v-2H5V5h5V3Zm9.7 9-4.2-4.2-1.4 1.4 1.8 1.8H9v2h6.9l-1.8 1.8 1.4 1.4 4.2-4.2Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h5v-2H5V5h5V3Zm9.7 9-4.2-4.2-1.4 1.4 1.8 1.8H9v2h6.9l-1.8 1.8 1.4 1.4 4.2-4.2Z'/%3E%3C/svg%3E"); }
        .fa-bars::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 6h18v2H3V6Zm0 5h18v2H3v-2Zm0 5h18v2H3v-2Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 6h18v2H3V6Zm0 5h18v2H3v-2Zm0 5h18v2H3v-2Z'/%3E%3C/svg%3E"); }
        .fa-user::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-4.4 0-8 2.4-8 5.3V22h16v-2.7c0-2.9-3.6-5.3-8-5.3Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-4.4 0-8 2.4-8 5.3V22h16v-2.7c0-2.9-3.6-5.3-8-5.3Z'/%3E%3C/svg%3E"); }
        .fa-trash-can::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 3h6l1 2h4v2H4V5h4l1-2Zm-2 6h2v9H7V9Zm4 0h2v9h-2V9Zm4 0h2v9h-2V9ZM6 7h12l-1 13H7L6 7Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 3h6l1 2h4v2H4V5h4l1-2Zm-2 6h2v9H7V9Zm4 0h2v9h-2V9Zm4 0h2v9h-2V9ZM6 7h12l-1 13H7L6 7Z'/%3E%3C/svg%3E"); }
        .fa-plus::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M11 4h2v7h7v2h-7v7h-2v-7H4v-2h7V4Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M11 4h2v7h7v2h-7v7h-2v-7H4v-2h7V4Z'/%3E%3C/svg%3E"); }
        .fa-magnifying-glass::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10.5 3a7.5 7.5 0 1 1 0 15 7.5 7.5 0 0 1 0-15Zm0 2a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11Zm6.9 10.5 3.6 3.6-1.4 1.4-3.6-3.6 1.4-1.4Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10.5 3a7.5 7.5 0 1 1 0 15 7.5 7.5 0 0 1 0-15Zm0 2a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11Zm6.9 10.5 3.6 3.6-1.4 1.4-3.6-3.6 1.4-1.4Z'/%3E%3C/svg%3E"); }
        .fa-times::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m6.4 5 5.6 5.6L17.6 5 19 6.4 13.4 12 19 17.6 17.6 19 12 13.4 6.4 19 5 17.6 10.6 12 5 6.4 6.4 5Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m6.4 5 5.6 5.6L17.6 5 19 6.4 13.4 12 19 17.6 17.6 19 12 13.4 6.4 19 5 17.6 10.6 12 5 6.4 6.4 5Z'/%3E%3C/svg%3E"); }
        .fa-save::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M5 3h11l3 3v15H5V3Zm2 2v4h8V5H7Zm0 14h10v-8H7v8Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M5 3h11l3 3v15H5V3Zm2 2v4h8V5H7Zm0 14h10v-8H7v8Z'/%3E%3C/svg%3E"); }
        .fa-clock::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 5h-2v6l5 3 1-1.7-4-2.3V7Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 5h-2v6l5 3 1-1.7-4-2.3V7Z'/%3E%3C/svg%3E"); }
        .fa-calendar-day::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M7 2h2v2h6V2h2v2h3v17H4V4h3V2Zm11 7H6v10h12V9Zm-5 2h-2v3.8l3.2 1.9 1-1.7-2.2-1.3V11Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M7 2h2v2h6V2h2v2h3v17H4V4h3V2Zm11 7H6v10h12V9Zm-5 2h-2v3.8l3.2 1.9 1-1.7-2.2-1.3V11Z'/%3E%3C/svg%3E"); }
        .fa-trash-alt::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 3h6l1 2h4v2H4V5h4l1-2Zm-2 6h2v9H7V9Zm4 0h2v9h-2V9Zm4 0h2v9h-2V9ZM6 7h12l-1 13H7L6 7Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 3h6l1 2h4v2H4V5h4l1-2Zm-2 6h2v9H7V9Zm4 0h2v9h-2V9Zm4 0h2v9h-2V9ZM6 7h12l-1 13H7L6 7Z'/%3E%3C/svg%3E"); }
        .fa-pen-to-square::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 17.2V21h3.8l10.9-10.9-3.8-3.8L3 17.2Zm11.9-11.9 1.8-1.8a1.8 1.8 0 0 1 2.6 0l1.2 1.2a1.8 1.8 0 0 1 0 2.6l-1.8 1.8-3.8-3.8Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 17.2V21h3.8l10.9-10.9-3.8-3.8L3 17.2Zm11.9-11.9 1.8-1.8a1.8 1.8 0 0 1 2.6 0l1.2 1.2a1.8 1.8 0 0 1 0 2.6l-1.8 1.8-3.8-3.8Z'/%3E%3C/svg%3E"); }
        .fa-rotate-left::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 5V2L7 6.5 12 11V7a5 5 0 1 1-4.9 6.1H5a7 7 0 1 0 7-8Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 5V2L7 6.5 12 11V7a5 5 0 1 1-4.9 6.1H5a7 7 0 1 0 7-8Z'/%3E%3C/svg%3E"); }
        .fa-power-off::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M11 3h2v7h-2V3Zm1 18a8 8 0 0 1-5.7-13.7l1.4 1.4A6 6 0 1 0 16.3 8.7l1.4-1.4A8 8 0 0 1 12 21Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M11 3h2v7h-2V3Zm1 18a8 8 0 0 1-5.7-13.7l1.4 1.4A6 6 0 1 0 16.3 8.7l1.4-1.4A8 8 0 0 1 12 21Z'/%3E%3C/svg%3E"); }

        .nav-link {
            transition: background-color .15s ease, color .15s ease, border-color .15s ease;
            border: 1px solid transparent;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            border-color: rgba(255, 255, 255, 0.12);
        }

        .nav-link.active {
            background: rgba(57, 150, 211, 0.18);
            color: #fff;
            border-color: rgba(57, 150, 211, 0.34);
            font-weight: 500;
        }

        .admin-inline-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
        }

        .field:focus {
            outline: none;
            border-color: #2c7fb8;
            box-shadow: 0 0 0 3px rgba(44, 127, 184, 0.12);
            background: #fff;
        }

        .trow:hover td {
            background: #f4f8fc;
        }

        #sidebar {
            transition: transform .25s ease;
        }

        #sidebar-overlay {
            transition: opacity .25s ease;
        }

        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
        }
    </style>
</head>

<body class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(57,150,211,0.10),_transparent_28%),linear-gradient(180deg,_#f5f9fc_0%,_#eef3f8_100%)] font-sans text-navy">
<div id="sidebar-overlay" class="fixed inset-0 z-30 hidden bg-slate-900/30 opacity-0 lg:hidden"></div>

<aside id="sidebar" class="fixed left-0 top-0 z-40 flex h-full w-72 -translate-x-full flex-col border-r border-white/10 bg-navy lg:translate-x-0">
    <div class="border-b border-white/10 px-6 py-5">
        <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl border border-white/10 bg-white">
                <img src="/Logo_anbg.png" alt="ANBG" class="h-7 w-auto object-contain">
            </div>
            <div>
                <p class="text-sm font-semibold text-white">Administration</p>
                <p class="text-[11px] uppercase tracking-[0.18em] text-white/45">ANBG</p>
            </div>
        </div>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-5">
        <div class="space-y-1.5">
            <p class="px-3 text-[11px] font-medium uppercase tracking-[0.18em] text-white/35">Pilotage</p>
            <a href="/admin/dashboard" class="nav-link {{ request()->is('admin/dashboard') ? 'active' : '' }} flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-white/75">
                <i class="fas fa-chart-line w-4 text-center text-white/35"></i>
                <span>Tableau de bord</span>
            </a>
            <a href="/admin/utilisateurs" class="nav-link {{ request()->is('admin/utilisateurs') ? 'active' : '' }} flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-white/75">
                <i class="fas fa-users w-4 text-center text-white/35"></i>
                <span>Utilisateurs</span>
            </a>
            <a href="/admin/roles" class="nav-link {{ request()->is('admin/roles') ? 'active' : '' }} flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-white/75">
                <i class="fas fa-key w-4 text-center text-white/35"></i>
                <span>Rôles</span>
            </a>
        </div>

        <div class="space-y-1.5">
            <p class="px-3 text-[11px] font-medium uppercase tracking-[0.18em] text-white/35">Référentiels</p>
            <a href="/admin/directions" class="nav-link {{ request()->is('admin/directions') ? 'active' : '' }} flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-white/75">
                <i class="fas fa-building w-4 text-center text-white/35"></i>
                <span>Directions</span>
            </a>
            <a href="/admin/services" class="nav-link {{ request()->is('admin/services') ? 'active' : '' }} flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-white/75">
                <i class="fas fa-cubes w-4 text-center text-white/35"></i>
                <span>Services</span>
            </a>
            <a href="/admin/parametres" class="nav-link {{ request()->is('admin/parametres') ? 'active' : '' }} flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-white/75">
                <i class="fas fa-sliders-h w-4 text-center text-white/35"></i>
                <span>Paramètres</span>
            </a>
        </div>
    </nav>

    <div class="border-t border-white/10 px-4 py-4">
        <form method="post" action="/logout">
            @csrf
            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/5 px-3 py-2.5 text-sm font-medium text-white/80 transition-colors hover:bg-white/10 hover:text-white">
                <i class="fas fa-right-from-bracket text-xs"></i>
                <span>Déconnexion</span>
            </button>
        </form>
    </div>
</aside>

<div class="min-h-screen lg:ml-72">
    <header class="sticky top-0 z-20 border-b border-sky-100 bg-white/88 backdrop-blur">
        <div class="flex min-h-[68px] items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <button id="sidebar-open" type="button" class="flex h-10 w-10 items-center justify-center rounded-xl border border-sky-100 bg-sky-50 text-sky lg:hidden">
                    <i class="fas fa-bars text-sm"></i>
                </button>
                <div>
                    <p class="text-[11px] uppercase tracking-[0.18em] text-sky">Interface admin</p>
                    <h1 class="text-base font-semibold text-navy">@yield('title', 'Accueil')</h1>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @php
                    $avatarUrl = $actor && $actor->avatar_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($actor->avatar_path) : null;
                    $hasAvatar = !empty($avatarUrl);
                @endphp

                @if(session('generated_password'))
                <div class="hidden rounded-xl border border-gold/40 bg-gold-50 px-3 py-2 text-xs text-amber-800 md:block">
                    Mot de passe généré : <code class="font-mono font-semibold">{{ session('generated_password') }}</code>
                </div>
                @endif

                <form method="post" action="/admin/avatar" enctype="multipart/form-data" id="avatar-form" class="flex items-center gap-3">
                    @csrf
                    <input type="file" name="avatar" id="avatar-input" accept="image/*" class="hidden">
                    <button id="avatar-select" type="button"
                        class="h-10 w-10 overflow-hidden rounded-full border border-sky-100 bg-sky-50 transition-colors hover:border-sky">
                        @if($hasAvatar)
                            <img src="{{ $avatarUrl }}" alt="Avatar" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-neutral-400">
                                <i class="fas fa-user text-xs"></i>
                            </div>
                        @endif
                    </button>
                    <div class="hidden text-left sm:block">
                        <p class="text-sm font-medium text-navy">{{ $actor->prenom ?? 'Admin' }} {{ $actor->nom ?? '' }}</p>
                        <p class="text-xs text-neutral-400">Administrateur</p>
                    </div>
                </form>

                @if($hasAvatar)
                <form method="post" action="/admin/avatar/remove">
                    @csrf
                    <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-xl border border-neutral-200 bg-white text-neutral-400 transition-colors hover:border-red-200 hover:bg-red-50 hover:text-red-500">
                        <i class="fas fa-trash-can text-[11px]"></i>
                    </button>
                </form>
                @endif
            </div>
        </div>
    </header>

    <main class="py-6">
        <div class="app-page-frame space-y-5">
            @if(session('success'))
            <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 shadow-soft">
                {{ session('success') }}
            </div>
            @endif

            @if(session('error'))
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 shadow-soft">
                {{ session('error') }}
            </div>
            @endif

            @if($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 shadow-soft">
                <p class="mb-1 font-medium">Erreurs de validation</p>
                <ul class="list-inside list-disc space-y-0.5 text-xs">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            @yield('content')
        </div>
    </main>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
    function openSidebar() {
        document.getElementById('sidebar').classList.add('translate-x-0');
        document.getElementById('sidebar-overlay').classList.remove('hidden');
        setTimeout(() => document.getElementById('sidebar-overlay').classList.add('opacity-100'), 10);
    }

    function closeSidebar() {
        document.getElementById('sidebar').classList.remove('translate-x-0');
        document.getElementById('sidebar-overlay').classList.remove('opacity-100');
        setTimeout(() => document.getElementById('sidebar-overlay').classList.add('hidden'), 250);
    }

    document.getElementById('sidebar-overlay')?.addEventListener('click', closeSidebar);
    document.getElementById('sidebar-open')?.addEventListener('click', openSidebar);
    document.getElementById('avatar-select')?.addEventListener('click', () => {
        document.getElementById('avatar-input')?.click();
    });
    document.getElementById('avatar-input')?.addEventListener('change', () => {
        document.getElementById('avatar-form')?.requestSubmit();
    });
</script>

@stack('scripts')
</body>
</html>
