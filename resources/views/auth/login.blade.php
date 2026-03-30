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
                        sans: ['"Roboto"', 'system-ui', 'sans-serif'],
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
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
                            class="input-field w-full pl-10 pr-4 py-3 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy placeholder-neutral-400 transition-all duration-150"
                        >
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

</body>
</html>
