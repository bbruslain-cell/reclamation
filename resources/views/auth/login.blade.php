<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>Connexion - Espace agents ANBG</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --anbg-navy:  #1c203d;
            --anbg-sky:   #3996d3;
            --anbg-leaf:  #8fc043;
            --anbg-gold:  #f9b13c;
        }

        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: #eef2f7;
        }

        .login-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(20px, 4vw, 48px);
        }

        .login-card {
            display: flex;
            width: min(100%, 900px);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 28px 70px rgba(28, 32, 61, 0.18);
        }

        /* ══ PANEL GAUCHE — Branding ══ */
        .panel-brand {
            flex: 1;
            background: var(--anbg-navy);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 52px 36px;
            position: relative;
            overflow: hidden;
        }
        .panel-brand::before {
            content: '';
            position: absolute;
            top: -100px; right: -100px;
            width: 300px; height: 300px;
            border-radius: 50%;
            background: rgba(57, 150, 211, 0.08);
            pointer-events: none;
        }
        .panel-brand::after {
            content: '';
            position: absolute;
            bottom: -80px; left: -80px;
            width: 240px; height: 240px;
            border-radius: 50%;
            background: rgba(143, 192, 67, 0.07);
            pointer-events: none;
        }
        .color-bar {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg,
                var(--anbg-sky)  0%,
                var(--anbg-leaf) 50%,
                var(--anbg-gold) 100%);
        }
        .brand-logo-box {
            position: relative; z-index: 1;
            background: #ffffff;
            border-radius: 22px;
            padding: 24px 36px;
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .brand-logo-box img {
            display: block;
            height: 100px;
            width: auto;
            object-fit: contain;
        }
        .brand-divider {
            position: relative; z-index: 1;
            height: 3px;
            width: 60px;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--anbg-sky), var(--anbg-leaf));
            margin-bottom: 20px;
        }
        .brand-title {
            position: relative; z-index: 1;
            font-size: 15px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: 3px;
            text-transform: uppercase;
            text-align: center;
            margin-bottom: 8px;
        }
        .brand-sub {
            position: relative; z-index: 1;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.38);
            text-align: center;
            letter-spacing: 0.5px;
        }

        /* ══ PANEL DROIT — Formulaire ══ */
        .panel-form {
            flex: 1.3;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 52px 48px;
        }
        .form-heading {
            font-size: 24px;
            font-weight: 700;
            color: var(--anbg-navy);
            margin-bottom: 4px;
        }
        .form-sub {
            font-size: 13px;
            color: #94a3b8;
            margin-bottom: 36px;
        }

        .error-banner {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #fff5f5;
            border: 1px solid #fecaca;
            border-radius: 14px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #b91c1c;
        }
        .error-banner svg { flex-shrink: 0; margin-top: 1px; width: 15px; height: 15px; }

        .field-group { margin-bottom: 20px; }
        .field-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--anbg-navy);
            margin-bottom: 8px;
        }
        .field-wrap {
            position: relative;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
        }
        .field-wrap:focus-within {
            border-color: var(--anbg-sky);
            box-shadow: 0 0 0 3px rgba(57, 150, 211, 0.12);
        }
        .field-icon {
            position: absolute;
            left: 15px; top: 50%;
            transform: translateY(-50%);
            color: #b0bac7;
            display: inline-flex;
            pointer-events: none;
        }
        .field-icon svg { width: 18px; height: 18px; }
        .field-input {
            display: block;
            width: 100%;
            height: 52px;
            padding: 0 18px 0 46px;
            border: none;
            outline: none;
            background: transparent;
            font-size: 14px;
            font-weight: 500;
            color: var(--anbg-navy);
            font-family: 'Inter', sans-serif;
        }
        .field-input::placeholder { color: #b0bac7; font-weight: 400; }

        .pwd-toggle {
            position: absolute;
            right: 10px; top: 50%;
            transform: translateY(-50%);
            width: 36px; height: 36px;
            display: inline-flex;
            align-items: center; justify-content: center;
            border-radius: 10px;
            color: #b0bac7;
            cursor: pointer;
            background: transparent;
            border: none;
            transition: color 0.18s, background 0.18s;
        }
        .pwd-toggle:hover { color: var(--anbg-sky); background: rgba(57,150,211,0.08); }
        .pwd-toggle:focus { outline: none; box-shadow: 0 0 0 3px rgba(57,150,211,0.15); }
        .pwd-toggle svg { width: 18px; height: 18px; }

        .field-error {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 6px;
            font-size: 12px;
            color: #dc2626;
        }
        .field-error svg { width: 12px; height: 12px; }

        /* Bouton — état normal et état loading */
        .submit-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            height: 54px;
            margin-top: 8px;
            border: none;
            border-radius: 14px;
            background: var(--anbg-navy);
            color: #ffffff;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.3px;
            cursor: pointer;
            box-shadow: 0 10px 28px rgba(28, 32, 61, 0.22);
            transition: opacity 0.2s ease, transform 0.15s ease;
        }
        .submit-btn:hover:not(:disabled) { opacity: 0.88; transform: translateY(-1px); }
        .submit-btn:active:not(:disabled) { transform: translateY(0); }
        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .submit-btn svg { width: 16px; height: 16px; }

        /* Spinner d'attente */
        .spinner {
            width: 16px; height: 16px;
            border: 2px solid rgba(255,255,255,0.35);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            flex-shrink: 0;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .secure-note {
            margin-top: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            font-size: 12px;
            color: #b0bac7;
        }
        .secure-note svg { width: 12px; height: 12px; }

        @media (max-width: 640px) {
            .login-card { flex-direction: column; }
            .panel-brand { padding: 40px 24px; }
            .panel-form  { padding: 36px 24px; }
            .panel-brand::before,
            .panel-brand::after { display: none; }
            .brand-logo-box img { height: 80px; }
        }
    </style>
</head>
<body>
<main class="login-shell">
    <div class="login-card">

        {{-- ════ PANEL GAUCHE — Branding ════ --}}
        <div class="panel-brand">
            <div class="brand-logo-box">
                <img src="{{ asset('Logo_anbg.png') }}" alt="Logo ANBG">
            </div>
            <div class="brand-divider"></div>
            <p class="brand-title">Espace agents</p>
            <p class="brand-sub">Plateforme interne · Accès réservé</p>
            <div class="color-bar"></div>
        </div>

        {{-- ════ PANEL DROIT — Formulaire ════ --}}
        <div class="panel-form">
            <h1 class="form-heading">Connexion</h1>
            <p class="form-sub">Entrez vos identifiants pour accéder à votre espace</p>

            @if ($errors->any())
                <div class="error-banner">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M12 7.8v5.4M12 16.7h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            {{-- route('login') au lieu de '/login'  --}}
            <form method="POST" action="{{ route('login') }}" autocomplete="on" id="login-form">
                @csrf

                <div class="field-group">
                    <label for="email" class="field-label">Adresse email</label>
                    <div class="field-wrap">
                        <span class="field-icon">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 8.5A2.5 2.5 0 0 1 6.5 6h11A2.5 2.5 0 0 1 20 8.5v7A2.5 2.5 0 0 1 17.5 18h-11A2.5 2.5 0 0 1 4 15.5v-7Z" stroke="currentColor" stroke-width="1.8"/>
                                <path d="m6 9 6 4 6-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="exemple@anbg.ga"
                            autocomplete="username"
                            inputmode="email"
                            autocapitalize="none"
                            autocorrect="off"
                            spellcheck="false"
                            required
                            autofocus
                            class="field-input"
                        >
                    </div>
                    @error('email')
                        <p class="field-error">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8"/>
                                <path d="M12 8v5M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="field-group">
                    <label for="password" class="field-label">Mot de passe</label>
                    <div class="field-wrap">
                        <span class="field-icon">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
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
                            class="field-input"
                            style="padding-right: 52px;"
                        >
                        <button
                            type="button"
                            id="toggle-password"
                            aria-label="Afficher le mot de passe"
                            aria-pressed="false"
                            class="pwd-toggle"
                        >
                           
                        </button>
                    </div>
                    @error('password')
                        <p class="field-error">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8"/>
                                <path d="M12 8v5M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- bouton avec état loading anti double-submit --}}
                <button type="submit" id="submit-btn" class="submit-btn">
                    <span id="btn-icon">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M13 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <path d="M4 12h11M10 7l5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span id="btn-label">Se connecter</span>
                </button>
            </form>

        
        </div>

    </div>
</main>

<script>
    (function () {

        /* ── Toggle mot de passe ─────────────────────── */
        const pwdInput = document.getElementById('password');
        const toggleBtn = document.getElementById('toggle-password');
        const iconSlot  = document.getElementById('pwd-icon');

        if (pwdInput && toggleBtn && iconSlot) {
            const eyeOpen = `<svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M2.8 12s3.2-5.5 9.2-5.5S21.2 12 21.2 12s-3.2 5.5-9.2 5.5S2.8 12 2.8 12Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                <circle cx="12" cy="12" r="2.7" stroke="currentColor" stroke-width="1.8"/>
            </svg>`;

            const eyeShut = `<svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="m3.5 3.5 17 17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                <path d="M9.9 6.8A10.3 10.3 0 0 1 12 6.5c6 0 9.2 5.5 9.2 5.5a16.7 16.7 0 0 1-3.6 4.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                <path d="M6.3 9.2A16.3 16.3 0 0 0 2.8 12s3.2 5.5 9.2 5.5c1.3 0 2.5-.2 3.6-.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>`;

            toggleBtn.addEventListener('click', function () {
                const show = pwdInput.type === 'password';
                pwdInput.type = show ? 'text' : 'password';
                toggleBtn.setAttribute('aria-pressed', String(show));
                toggleBtn.setAttribute('aria-label', show ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
                iconSlot.innerHTML = show ? eyeShut : eyeOpen;
            });
        }

        /* ── Protection double-submit ────────────────── */
        const form      = document.getElementById('login-form');
        const submitBtn = document.getElementById('submit-btn');
        const btnIcon   = document.getElementById('btn-icon');
        const btnLabel  = document.getElementById('btn-label');

        if (form && submitBtn) {
            form.addEventListener('submit', function () {
                // Désactive le bouton immédiatement
                submitBtn.disabled = true;

                // Remplace l'icône par un spinner et le texte par "Connexion…"
                if (btnIcon)  btnIcon.innerHTML  = '<span class="spinner"></span>';
                if (btnLabel) btnLabel.textContent = 'Connexion…';

                // Sécurité : si le serveur ne répond pas dans 15s, on réactive le bouton
                setTimeout(function () {
                    submitBtn.disabled = false;
                    if (btnIcon)  btnIcon.innerHTML  = `<svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M13 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        <path d="M4 12h11M10 7l5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>`;
                    if (btnLabel) btnLabel.textContent = 'Se connecter';
                }, 15000);
            });
        }

    })();
</script>
</body>
</html>