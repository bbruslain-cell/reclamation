@extends('layouts.app')

@section('title', "Choix de l'espace | ANBG")
@section('body_class', 'min-h-screen bg-neutral-100 font-sans text-navy')

@push('preconnect')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
@endpush

@push('styles')
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
        .fa-right-from-bracket::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h5v-2H5V5h5V3Zm9.7 9-4.2-4.2-1.4 1.4 1.8 1.8H9v2h6.9l-1.8 1.8 1.4 1.4 4.2-4.2Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h5v-2H5V5h5V3Zm9.7 9-4.2-4.2-1.4 1.4 1.8 1.8H9v2h6.9l-1.8 1.8 1.4 1.4 4.2-4.2Z'/%3E%3C/svg%3E"); }
        .fa-key::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M14 3a7 7 0 0 0-6.7 9l-5.3 5.3V21h3.7l1.5-1.5v-1.8H9v-1.8h1.8l1.5-1.5A7 7 0 1 0 14 3Zm0 2a5 5 0 1 1 0 10 5 5 0 0 1 0-10Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M14 3a7 7 0 0 0-6.7 9l-5.3 5.3V21h3.7l1.5-1.5v-1.8H9v-1.8h1.8l1.5-1.5A7 7 0 1 0 14 3Zm0 2a5 5 0 1 1 0 10 5 5 0 0 1 0-10Z'/%3E%3C/svg%3E"); }
        .fa-sliders::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M5 6h14v2H5V6Zm3 5h11v2H8v-2Zm-3 5h8v2H5v-2Z'/%3E%3Ccircle fill='black' cx='8' cy='7' r='2'/%3E%3Ccircle fill='black' cx='15' cy='12' r='2'/%3E%3Ccircle fill='black' cx='13' cy='17' r='2'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M5 6h14v2H5V6Zm3 5h11v2H8v-2Zm-3 5h8v2H5v-2Z'/%3E%3Ccircle fill='black' cx='8' cy='7' r='2'/%3E%3Ccircle fill='black' cx='15' cy='12' r='2'/%3E%3Ccircle fill='black' cx='13' cy='17' r='2'/%3E%3C/svg%3E"); }
        .fa-inbox::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M5 3h14l3 9v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7l3-9Zm1.4 2L4.1 12H9l1 2h4l1-2h4.9L17.6 5H6.4Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M5 3h14l3 9v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7l3-9Zm1.4 2L4.1 12H9l1 2h4l1-2h4.9L17.6 5H6.4Z'/%3E%3C/svg%3E"); }
        .fa-briefcase::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 4h6a2 2 0 0 1 2 2v1h3a2 2 0 0 1 2 2v8a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V9a2 2 0 0 1 2-2h3V6a2 2 0 0 1 2-2Zm0 3h6V6H9v1Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 4h6a2 2 0 0 1 2 2v1h3a2 2 0 0 1 2 2v8a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V9a2 2 0 0 1 2-2h3V6a2 2 0 0 1 2-2Zm0 3h6V6H9v1Z'/%3E%3C/svg%3E"); }
        .fa-building-user::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M4 20V5l7-2v17H4Zm9 0v-8h7v8h-7ZM6 7h2v2H6V7Zm0 4h2v2H6v-2Zm0 4h2v2H6v-2Zm8-8h5v3h-5V7Zm2.5 4.5a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-2.5 8.5v-1c0-1.7 1.6-3 3.5-3s3.5 1.3 3.5 3v1h-7Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M4 20V5l7-2v17H4Zm9 0v-8h7v8h-7ZM6 7h2v2H6V7Zm0 4h2v2H6v-2Zm0 4h2v2H6v-2Zm8-8h5v3h-5V7Zm2.5 4.5a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-2.5 8.5v-1c0-1.7 1.6-3 3.5-3s3.5 1.3 3.5 3v1h-7Z'/%3E%3C/svg%3E"); }
        .fa-headset::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 3a8 8 0 0 0-8 8v5a3 3 0 0 0 3 3h2v-7H6v-1a6 6 0 1 1 12 0v1h-3v7h2a3 3 0 0 0 3-3v-5a8 8 0 0 0-8-8Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 3a8 8 0 0 0-8 8v5a3 3 0 0 0 3 3h2v-7H6v-1a6 6 0 1 1 12 0v1h-3v7h2a3 3 0 0 0 3-3v-5a8 8 0 0 0-8-8Z'/%3E%3C/svg%3E"); }
        .fa-chart-line::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M4 19h16v2H2V4h2v15Zm2.7-4.3 3.5-3.5 2.6 2.6 4.5-5.3 1.5 1.3-5.9 7-2.7-2.7-2.1 2.1-1.4-1.5Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M4 19h16v2H2V4h2v15Zm2.7-4.3 3.5-3.5 2.6 2.6 4.5-5.3 1.5 1.3-5.9 7-2.7-2.7-2.1 2.1-1.4-1.5Z'/%3E%3C/svg%3E"); }
        .fa-star::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m12 2 2.9 6 6.6 1-4.8 4.7 1.1 6.6L12 17.3 6.2 20.3l1.1-6.6L2.5 9l6.6-1L12 2Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m12 2 2.9 6 6.6 1-4.8 4.7 1.1 6.6L12 17.3 6.2 20.3l1.1-6.6L2.5 9l6.6-1L12 2Z'/%3E%3C/svg%3E"); }
        .fa-arrow-right::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m13.3 5.3-1.4 1.4 4.3 4.3H4v2h12.2l-4.3 4.3 1.4 1.4 6.7-6.7-6.7-6.7Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m13.3 5.3-1.4 1.4 4.3 4.3H4v2h12.2l-4.3 4.3 1.4 1.4 6.7-6.7-6.7-6.7Z'/%3E%3C/svg%3E"); }
    </style>
@endpush

@section('content')
    <div class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(57,150,211,0.18),_transparent_32%),linear-gradient(180deg,_#f8f9fa_0%,_#eef3f7_100%)]">
        <header class="bg-navy text-white shadow-sm">
            <div class="app-page-frame h-16 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="bg-white rounded-lg px-2.5 py-1.5">
                        <img src="/Logo_anbg.png" alt="ANBG" class="h-9 w-auto object-contain block">
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.22em] text-white/60">Portail Multi-Rôle</p>
                        <p class="text-sm font-medium">Choisissez votre espace</p>
                    </div>
                </div>
                <form method="post" action="/logout">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-medium hover:bg-white/20 transition-colors duration-150">
                        <i class="fas fa-right-from-bracket text-[10px]"></i>
                        <span>Déconnexion</span>
                    </button>
                </form>
            </div>
        </header>

        <main class="app-page-frame py-8">
            <section class="rounded-3xl bg-navy text-white shadow-card overflow-hidden">
                <div class="px-6 py-8 sm:px-8">
                    <h1 class="mt-2 text-2xl sm:text-3xl font-medium">Espaces disponibles pour votre compte.</h1>
                    <p class="mt-3 max-w-3xl text-sm sm:text-base text-sky-100/90 leading-relaxed">
                        Choisissez l'espace dans lequel vous souhaitez travailler maintenant. Vos autres droits restent actifs pendant la séssion.
                    </p>

                </div>
            </section>

            <section class="mt-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                @foreach($spaces as $space)
                    @php $isDefault = ($defaultSpace['code'] ?? null) === $space['code']; @endphp
                    <a href="{{ $space['href'] }}"
                        class="group rounded-3xl border {{ $isDefault ? 'border-sky-200 bg-sky-50/80' : 'border-neutral-200 bg-white/90' }} p-5 shadow-card transition-all duration-150 hover:-translate-y-0.5 hover:border-sky-200 hover:bg-white">
                        <div class="flex items-start justify-between gap-3">
                            <div class="w-11 h-11 rounded-2xl {{ $isDefault ? 'bg-sky text-white' : 'bg-navy-50 text-navy' }} flex items-center justify-center flex-shrink-0">
                                <i class="fas {{ $space['code'] === 'admin' ? 'fa-sliders' : ($space['code'] === 'accueil' ? 'fa-inbox' : ($space['code'] === 'chef' ? 'fa-briefcase' : ($space['code'] === 'chef_direction' ? 'fa-building-user' : ($space['code'] === 'agent' ? 'fa-headset' : 'fa-chart-line')))) }} text-sm"></i>
                            </div>

                        </div>

                        <div class="mt-4">
                            <h2 class="text-lg font-medium text-navy">{{ $space['title'] }}</h2>
                            <p class="mt-2 text-sm text-neutral-500 leading-relaxed">{{ $space['description'] }}</p>
                        </div>

                        <div class="mt-5 inline-flex items-center gap-2 text-sm font-medium text-sky group-hover:text-sky-600">
                            <span>Ouvrir cet espace</span>
                            <i class="fas fa-arrow-right text-[11px] transition-transform duration-150 group-hover:translate-x-0.5"></i>
                        </div>
                    </a>
                @endforeach
            </section>
        </main>
    </div>
@endsection
