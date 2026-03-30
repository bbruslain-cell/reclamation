<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ANBG - Administration - @yield('title', 'Accueil')</title>

    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: { DEFAULT: '#1c203d', 700: '#14162c' },
                        sky: { DEFAULT: '#3996d3', 50: '#eef6fb', 100: '#d7ebf8', 600: '#2e7fb8' },
                        leaf: { DEFAULT: '#8fc043', 50: '#f3f9ea' },
                        gold: { DEFAULT: '#f9b13c', 50: '#fff8ee' },
                        neutral: {
                            25: '#fcfcfd',
                            50: '#f8fafc',
                            100: '#eef2f6',
                            200: '#dde5ee',
                            300: '#c4d0dd',
                            400: '#8fa0b3',
                            500: '#627487',
                            600: '#435467',
                            700: '#2d3a4a',
                            800: '#1e293b'
                        }
                    },
                    fontFamily: {
                        sans: ['"Roboto"', 'system-ui', 'sans-serif']
                    },
                    boxShadow: {
                        shell: '0 10px 35px rgba(28, 32, 61, 0.08)',
                        soft: '0 1px 2px rgba(28, 32, 61, 0.06)'
                    }
                }
            }
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
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
<div id="sidebar-overlay" class="fixed inset-0 z-30 hidden bg-slate-900/30 opacity-0 lg:hidden" onclick="closeSidebar()"></div>

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
                <button onclick="openSidebar()" class="flex h-10 w-10 items-center justify-center rounded-xl border border-sky-100 bg-sky-50 text-sky lg:hidden">
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
                    <input type="file" name="avatar" id="avatar-input" accept="image/*" class="hidden" onchange="document.getElementById('avatar-form').submit()">
                    <button type="button" onclick="document.getElementById('avatar-input').click()"
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

    <main class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-[1480px] space-y-5">
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

<script>
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
</script>

@stack('scripts')
</body>
</html>
