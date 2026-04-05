<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion — Espace Agents ANBG</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy:   { DEFAULT: '#1c203d', light: '#2a2f5a', subtle: '#ecedf3' },
                        sky:    { DEFAULT: '#3996d3', light: '#eaf4fb', ring: 'rgba(57,150,211,0.22)' },
                        leaf:   { DEFAULT: '#8fc043' },
                        gold:   { DEFAULT: '#f9b13c' },
                    },
                    fontFamily: {
                        sans: ['"Inter"', 'system-ui', 'sans-serif'],
                    },
                    boxShadow: {
                        'panel': '0 25px 60px -10px rgba(28,32,61,0.18)',
                        'btn':   '0 8px 22px -6px rgba(57,150,211,0.42)',
                    }
                }
            }
        }
    </script>
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
        .fa-check::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m9.2 16.6-4.3-4.3-1.4 1.4 5.7 5.7L20.5 8.1l-1.4-1.4-9.9 9.9Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m9.2 16.6-4.3-4.3-1.4 1.4 5.7 5.7L20.5 8.1l-1.4-1.4-9.9 9.9Z'/%3E%3C/svg%3E"); }
        .fa-lock::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M7 10V8a5 5 0 1 1 10 0v2h1a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h1Zm2 0h6V8a3 3 0 1 0-6 0v2Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M7 10V8a5 5 0 1 1 10 0v2h1a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h1Zm2 0h6V8a3 3 0 1 0-6 0v2Z'/%3E%3C/svg%3E"); }
        .fa-circle-exclamation::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm-1 5h2v7h-2V7Zm0 9h2v2h-2v-2Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm-1 5h2v7h-2V7Zm0 9h2v2h-2v-2Z'/%3E%3C/svg%3E"); }
        .fa-envelope::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 5h18a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Zm0 2v.5l9 5.5 9-5.5V7H3Zm18 10V9.8l-8.5 5.2a1 1 0 0 1-1 0L3 9.8V17h18Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 5h18a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Zm0 2v.5l9 5.5 9-5.5V7H3Zm18 10V9.8l-8.5 5.2a1 1 0 0 1-1 0L3 9.8V17h18Z'/%3E%3C/svg%3E"); }
        .fa-right-to-bracket::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M13 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6v-2h6V5h-6V3ZM4.3 12l4.2-4.2 1.4 1.4L8.1 11H16v2H8.1l1.8 1.8-1.4 1.4L4.3 12Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M13 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6v-2h6V5h-6V3ZM4.3 12l4.2-4.2 1.4 1.4L8.1 11H16v2H8.1l1.8 1.8-1.4 1.4L4.3 12Z'/%3E%3C/svg%3E"); }
        .fa-arrow-left::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10.7 5.3 4 12l6.7 6.7 1.4-1.4L7.8 13H20v-2H7.8l4.3-4.3-1.4-1.4Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10.7 5.3 4 12l6.7 6.7 1.4-1.4L7.8 13H20v-2H7.8l4.3-4.3-1.4-1.4Z'/%3E%3C/svg%3E"); }
        /* Focus ring personnalisé */
        .input-field:focus {
            outline: none;
            border-color: #3996d3;
            box-shadow: 0 0 0 3px rgba(57,150,211,0.20);
            background: #ffffff;
        }
        /* Décors géométriques fond navy */
        .deco-circle-1 {
            position: absolute; width: 280px; height: 280px;
            border-radius: 50%; top: -80px; right: -80px;
            background: rgba(255,255,255,0.07);
        }
        .deco-circle-2 {
            position: absolute; width: 200px; height: 200px;
            border-radius: 50%; bottom: -60px; left: -60px;
            background: rgba(57,150,211,0.18);
        }
        .deco-circle-3 {
            position: absolute; width: 100px; height: 100px;
            border-radius: 50%; bottom: 120px; right: 40px;
            background: rgba(143,192,67,0.15);
        }
    </style>
</head>
<body class="min-h-screen bg-neutral-100 flex items-center justify-center p-4 font-sans" style="background: radial-gradient(160% 140% at 10% 10%, #f1f3f5 0%, #e4ecf5 100%);">

    <div class="w-full max-w-4xl bg-white rounded-2xl shadow-panel overflow-hidden flex flex-col md:flex-row min-h-[580px]">

        <!-- ══════════════════ PANNEAU GAUCHE (Navy) ══════════════════ -->
        <div class="relative bg-navy overflow-hidden md:w-6/12 flex flex-col p-10 gap-7 hidden md:flex">
            <!-- Décors -->
            <div class="deco-circle-1"></div>
            <div class="deco-circle-2"></div>
            <div class="deco-circle-3"></div>

            <!-- Logo dans cartouche blanc -->
            <div class="relative z-10">
                <div class="inline-block bg-white rounded-xl px-4 py-2.5">
                    <img src="{{ asset('Logo_anbg.png') }}" alt="ANBG" class="h-11 w-auto object-contain block">
                </div>
            </div>

            <!-- Titre -->
            <div class="relative z-10 mt-2">
                <h1 class="text-white text-2xl font-bold leading-snug mb-3">
                    Espace<br>Agents ANBG
                </h1>
                <p class="text-blue-200 text-sm leading-relaxed font-light">
                    Accédez à vos tableaux de bord, affectez les demandes et répondez aux usagers en toute sécurité.
                </p>
            </div>

            <!-- Features list -->
            <ul class="relative z-10 space-y-3 mt-2">
                <li class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-full bg-sky/20 border border-sky/30 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check text-sky text-[10px]"></i>
                    </span>
                    <span class="text-blue-100 text-sm">Suivi temps réel des demandes</span>
                </li>
                <li class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-full bg-leaf/20 border border-leaf/30 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check text-leaf text-[10px]"></i>
                    </span>
                    <span class="text-blue-100 text-sm">Affectation aux services et agents</span>
                </li>
                <li class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-full bg-gold/20 border border-gold/30 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check text-gold text-[10px]"></i>
                    </span>
                    <span class="text-blue-100 text-sm">Historique et notifications internes</span>
                </li>
            </ul>

            <!-- Badge sécurité en bas -->
            <div class="relative z-10 mt-auto">
                <div class="inline-flex items-center gap-2 bg-white/10 border border-white/15 text-blue-200 text-xs px-3 py-1.5 rounded-full">
                    <i class="fas fa-lock text-sky text-[10px]"></i>
                    Accès réservé au personnel ANBG
                </div>
            </div>
        </div>

        <!-- ══════════════════ PANNEAU DROIT (Formulaire) ══════════════════ -->
        <div class="flex-1 flex flex-col px-8 py-10 md:px-12">

            <!-- Logo mobile uniquement -->
            <div class="md:hidden mb-6">
                <div class="inline-block bg-navy-subtle rounded-xl px-4 py-2">
                    <img src="{{ asset('Logo_anbg.png') }}" alt="ANBG" class="h-9 w-auto object-contain block">
                </div>
            </div>

            <!-- En-tête -->
            <div class="mb-8">
                <h2 class="text-navy text-2xl font-bold mb-1 tracking-tight">Connexion</h2>
                <p class="text-neutral-400 text-sm">Renseignez vos identifiants ANBG.</p>
            </div>

            <!-- Erreur globale -->
            @if ($errors->any())
            <div class="mb-5 flex items-start gap-2.5 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
                <i class="fas fa-circle-exclamation mt-0.5 flex-shrink-0"></i>
                <span>{{ $errors->first() }}</span>
            </div>
            @endif

            <!-- Formulaire -->
            <form method="POST" action="{{ url('/login') }}" class="flex flex-col gap-5 flex-1">
                @csrf

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-navy mb-1.5">
                        Adresse email
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-neutral-400 text-sm pointer-events-none">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input
                            type="email" id="email" name="email"
                            value="{{ old('email') }}"
                            placeholder="prenom.nom@anbg.ga"
                            required autofocus
                            class="input-field w-full pl-10 pr-4 py-3 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy placeholder-neutral-400 transition-all duration-150"
                        >
                    </div>
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                            <i class="fas fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Mot de passe -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-sm font-medium text-navy">
                            Mot de passe
                        </label>
                        <a href="{{ url('/mot-de-passe/oubli') }}"
                           class="text-xs text-sky font-medium hover:underline underline-offset-2">
                            Mot de passe oublié ?
                        </a>
                    </div>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-neutral-400 text-sm pointer-events-none">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input
                            type="password" id="password" name="password"
                            placeholder="••••••••"
                            required
                            class="input-field w-full pl-10 pr-11 py-3 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy placeholder-neutral-400 transition-all duration-150"
                        >
                        <button
                            type="button"
                            id="toggle-password"
                            aria-label="Afficher le mot de passe"
                            aria-pressed="false"
                            class="absolute right-3 top-1/2 -translate-y-1/2 inline-flex items-center justify-center text-neutral-400 hover:text-sky transition-colors duration-150"
                        >
                            <span id="toggle-password-icon" class="inline-flex items-center justify-center">
                                <svg class="h-[1.05rem] w-[1.05rem]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 5C5.6 5 2 12 2 12s3.6 7 10 7 10-7 10-7-3.6-7-10-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z" fill="currentColor"/>
                                </svg>
                            </span>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                            <i class="fas fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Bouton connexion -->
                <button
                    type="submit"
                    class="mt-2 w-full bg-navy hover:bg-navy-light text-white font-medium text-sm py-3.5 rounded-xl shadow-btn transition-all duration-200 flex items-center justify-center gap-2"
                >
                    <i class="fas fa-right-to-bracket text-xs"></i>
                    Se connecter
                </button>
            </form>

            <!-- Lien retour formulaire public -->
            <div class="mt-auto pt-6 border-t border-neutral-100 text-center">
                <a href="{{ url('/reclamations/nouvelle') }}"
                   class="text-xs text-neutral-400 font-medium">
                    <i class="fas fa-arrow-left text-[10px] mr-1"></i>
                    Aller au formulaire public
                </a>
            </div>
        </div>

    </div>

    <script>
        (function () {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.getElementById('toggle-password');
            const iconSlot = document.getElementById('toggle-password-icon');

            if (!passwordInput || !toggleButton || !iconSlot) {
                return;
            }

            const eyeSvg = '<svg class="h-[1.05rem] w-[1.05rem]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5C5.6 5 2 12 2 12s3.6 7 10 7 10-7 10-7-3.6-7-10-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z" fill="currentColor"/></svg>';
            const eyeSlashSvg = '<svg class="h-[1.05rem] w-[1.05rem]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m3.3 2 18.7 18.7-1.4 1.4-4.1-4.1A11.7 11.7 0 0 1 12 19C5.6 19 2 12 2 12a19 19 0 0 1 4.4-5.3L1.9 3.4 3.3 2Zm6.1 6.1A4 4 0 0 0 12 16c.7 0 1.4-.2 2-.5L9.4 8.1ZM12 5c6.4 0 10 7 10 7a18.9 18.9 0 0 1-4.1 5.1l-2.2-2.2A4 4 0 0 0 9.1 8.3L7.5 6.7A10.8 10.8 0 0 1 12 5Z" fill="currentColor"/></svg>';

            toggleButton.addEventListener('click', () => {
                const isHidden = passwordInput.type === 'password';
                passwordInput.type = isHidden ? 'text' : 'password';
                toggleButton.setAttribute('aria-pressed', String(isHidden));
                toggleButton.setAttribute('aria-label', isHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
                iconSlot.innerHTML = isHidden ? eyeSlashSvg : eyeSvg;
            });
        })();
    </script>
</body>
</html>
