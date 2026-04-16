<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>Connexion - Espace Agents ANBG</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .input-field:focus {
            outline: none;
            border-color: #3996d3;
            box-shadow: 0 0 0 3px rgba(57, 150, 211, 0.20);
            background: #ffffff;
        }

        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear {
            display: none;
        }

        .deco-circle-1 {
            position: absolute;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            top: -80px;
            right: -80px;
            background: rgba(255, 255, 255, 0.07);
        }

        .deco-circle-2 {
            position: absolute;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            bottom: -60px;
            left: -60px;
            background: rgba(57, 150, 211, 0.18);
        }

        .deco-circle-3 {
            position: absolute;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            bottom: 120px;
            right: 40px;
            background: rgba(143, 192, 67, 0.15);
        }
    </style>
</head>
<body class="min-h-screen bg-neutral-100 flex items-center justify-center p-4 font-sans" style="background: radial-gradient(160% 140% at 10% 10%, #f1f3f5 0%, #e4ecf5 100%);">
    <main class="w-full max-w-4xl bg-white rounded-2xl shadow-panel overflow-hidden flex flex-col md:flex-row min-h-[580px]">
        <section class="relative bg-navy overflow-hidden md:w-6/12 flex-col p-10 gap-7 hidden md:flex">
            <div class="deco-circle-1"></div>
            <div class="deco-circle-2"></div>
            <div class="deco-circle-3"></div>

            <div class="relative z-10">
                <div class="inline-block bg-white rounded-xl px-4 py-2.5">
                    <img src="{{ asset('Logo_anbg.png') }}" alt="ANBG" class="h-11 w-auto object-contain block">
                </div>
            </div>

            <div class="relative z-10 mt-2">
                <h1 class="text-white text-2xl font-bold leading-snug mb-3">
                    Espace<br>Agents ANBG
                </h1>
                <p class="text-blue-200 text-sm leading-relaxed font-light">
                    Accédez à vos tableaux de bord, affectez les demandes et répondez aux usagers en toute sécurité.
                </p>
            </div>

            <ul class="relative z-10 space-y-3 mt-2">
                <li class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-full bg-sky/20 border border-sky/30 flex items-center justify-center flex-shrink-0">
                        <svg class="h-3.5 w-3.5 text-sky" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="m5 12 4.2 4.2L19 6.5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span class="text-blue-100 text-sm">Suivi temps réel des demandes</span>
                </li>
                <li class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-full bg-leaf/20 border border-leaf/30 flex items-center justify-center flex-shrink-0">
                        <svg class="h-3.5 w-3.5 text-leaf" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="m5 12 4.2 4.2L19 6.5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span class="text-blue-100 text-sm">Affectation aux services et agents</span>
                </li>
                <li class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-full bg-gold/20 border border-gold/30 flex items-center justify-center flex-shrink-0">
                        <svg class="h-3.5 w-3.5 text-gold" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="m5 12 4.2 4.2L19 6.5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span class="text-blue-100 text-sm">Historique et notifications internes</span>
                </li>
            </ul>

            <div class="relative z-10 mt-auto">
                <div class="inline-flex items-center gap-2 bg-white/10 border border-white/15 text-blue-200 text-xs px-3 py-1.5 rounded-full">
                    <svg class="h-3.5 w-3.5 text-sky" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M7 10V8a5 5 0 0 1 10 0v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M5 10h14v10H5V10Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                    </svg>
                    Accès réservé au personnel ANBG
                </div>
            </div>
        </section>

        <section class="flex-1 flex flex-col px-8 py-10 md:px-12">
            <div class="md:hidden mb-6">
                <div class="inline-block bg-[#ecedf3] rounded-xl px-4 py-2">
                    <img src="{{ asset('Logo_anbg.png') }}" alt="ANBG" class="h-9 w-auto object-contain block">
                </div>
            </div>

            <div class="mb-8">
                <h2 class="text-navy text-2xl font-bold mb-1 tracking-tight">Connexion</h2>
                <p class="text-neutral-400 text-sm">Renseignez vos identifiants ANBG.</p>
            </div>

            @if ($errors->any())
                <div class="mb-5 flex items-start gap-2.5 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
                    <svg class="mt-0.5 h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 8v5M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z" stroke="currentColor" stroke-width="1.8"/>
                    </svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ url('/login') }}" class="flex flex-col gap-5">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-navy mb-1.5">
                        Adresse email
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-neutral-400 pointer-events-none">
                            <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 6h16v12H4V6Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                <path d="m4.5 7 7.5 6 7.5-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="prenom.nom@anbg.ga"
                            autocomplete="email"
                            required
                            autofocus
                            class="input-field w-full pl-10 pr-4 py-3 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy placeholder-neutral-400 transition-all duration-150"
                        >
                    </div>
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 8v5M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z" stroke="currentColor" stroke-width="1.8"/>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-navy mb-1.5">
                        Mot de passe
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-neutral-400 pointer-events-none">
                            <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M7 10V8a5 5 0 0 1 10 0v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                <path d="M5 10h14v10H5V10Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Mot de passe"
                            autocomplete="current-password"
                            required
                            class="input-field w-full pl-10 pr-11 py-3 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy placeholder-neutral-400 transition-all duration-150"
                        >
                        <button
                            type="button"
                            id="toggle-password"
                            aria-label="Afficher le mot de passe"
                            aria-pressed="false"
                            class="absolute right-3 top-1/2 -translate-y-1/2 inline-flex h-8 w-8 items-center justify-center rounded-lg text-neutral-400 transition-colors duration-150 hover:bg-sky-50 hover:text-sky focus:outline-none focus:ring-2 focus:ring-sky/30"
                        >
                            <span id="toggle-password-icon" class="inline-flex items-center justify-center">
                                <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="1.8"/>
                                </svg>
                            </span>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 8v5M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z" stroke="currentColor" stroke-width="1.8"/>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="mt-2 w-full bg-navy hover:bg-[#2a2f5a] text-white font-medium text-sm py-3.5 rounded-xl shadow-btn transition-all duration-200 flex items-center justify-center gap-2"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M13 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        <path d="M4 12h11M10 7l5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Se connecter
                </button>
            </form>
        </section>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.getElementById('toggle-password');
            const iconSlot = document.getElementById('toggle-password-icon');

            if (!passwordInput || !toggleButton || !iconSlot) {
                return;
            }

            const eyeSvg = `<svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="1.8"/>
            </svg>`;

            const eyeSlashSvg = `<svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="m3 3 18 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                <path d="M10.6 10.8a3 3 0 0 0 3.8 3.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                <path d="M8.1 5.8A10.7 10.7 0 0 1 12 5.1c6 0 9.5 6.9 9.5 6.9a15.6 15.6 0 0 1-3.2 4.1M5.8 7.5A15.6 15.6 0 0 0 2.5 12S6 18.9 12 18.9c1.2 0 2.3-.3 3.3-.7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>`;

            toggleButton.addEventListener('click', function () {
                const isHidden = passwordInput.type === 'password';
                passwordInput.type = isHidden ? 'text' : 'password';
                toggleButton.setAttribute('aria-pressed', String(isHidden));
                toggleButton.setAttribute('aria-label', isHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
                iconSlot.innerHTML = isHidden ? eyeSlashSvg : eyeSvg;
            });
        });
    </script>
</body>
</html>
