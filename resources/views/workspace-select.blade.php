<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choix de l'espace | ANBG</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: { DEFAULT: '#1c203d', 50: '#ecedf3', 100: '#c5c7d9', 600: '#181b35' },
                        sky: { DEFAULT: '#3996d3', 50: '#eaf4fb', 100: '#cae4f5', 600: '#2e7fb8' },
                        leaf: { DEFAULT: '#8fc043', 50: '#f3f9ea' },
                        gold: { DEFAULT: '#f9b13c', 50: '#fff8ee' },
                        neutral: { 50:'#f8f9fa',100:'#f1f3f5',200:'#e9ecef',300:'#dee2e6',400:'#adb5bd',500:'#6c757d',700:'#343a40' },
                    },
                    fontFamily: { sans: ['"Roboto"', 'system-ui', 'sans-serif'] },
                    boxShadow: {
                        card: '0 12px 35px rgba(28,32,61,0.08)',
                    }
                }
            }
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="min-h-screen bg-neutral-100 font-sans text-navy">
    <div class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(57,150,211,0.18),_transparent_32%),linear-gradient(180deg,_#f8f9fa_0%,_#eef3f7_100%)]">
        <header class="bg-navy text-white shadow-sm">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="bg-white rounded-lg px-2.5 py-1.5">
                        <img src="/Logo_anbg.png" alt="ANBG" class="h-9 w-auto object-contain block">
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.22em] text-white/60">Portail collaborateur</p>
                        <p class="text-sm font-medium">Choisissez votre espace</p>
                    </div>
                </div>
                <form method="post" action="/logout">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-medium hover:bg-white/20 transition-colors duration-150">
                        <i class="fas fa-right-from-bracket text-[10px]"></i>
                        <span>Deconnexion</span>
                    </button>
                </form>
            </div>
        </header>

        <main class="max-w-6xl mx-auto px-4 sm:px-6 py-8">
            <section class="rounded-3xl bg-navy text-white shadow-card overflow-hidden">
                <div class="px-6 py-8 sm:px-8">
                    <p class="text-xs uppercase tracking-[0.22em] text-sky-100/70">Connexion multi-role</p>
                    <h1 class="mt-2 text-2xl sm:text-3xl font-medium">Plusieurs espaces sont disponibles pour votre compte.</h1>
                    <p class="mt-3 max-w-3xl text-sm sm:text-base text-sky-100/90 leading-relaxed">
                        Choisissez l'espace dans lequel vous souhaitez travailler maintenant. Vos autres droits restent actifs pendant la session.
                    </p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach($roles as $role)
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-medium">
                                <i class="fas fa-key text-[10px] text-sky-200"></i>
                                {{ $role }}
                            </span>
                        @endforeach
                    </div>
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
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                @if($isDefault)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-leaf-50 px-2.5 py-1 text-[11px] font-medium text-green-700 border border-green-200">
                                        <i class="fas fa-star text-[10px]"></i> Recommande
                                    </span>
                                @endif
                                <span class="inline-flex items-center gap-1 rounded-full bg-gold-50 px-2.5 py-1 text-[11px] font-medium text-amber-700 border border-amber-200">
                                    {{ $space['badge'] }}
                                </span>
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
</body>
</html>
