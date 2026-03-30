<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Réinitialiser le mot de passe - ANBG</title>
    <style>
        body { margin:0; font-family: "Segoe UI", sans-serif; background:#eef4f9; color:#102230; }
        .wrap { min-height:100vh; display:grid; place-items:center; padding:20px; }
        .card { width:min(450px, 100%); background:#fff; border:1px solid #d7e2ea; border-radius:12px; padding:20px; }
        h1 { margin:0 0 6px; }
        p { color:#4c6272; margin:0 0 14px; }
        input, button { width:100%; padding:10px; border:1px solid #cfdbe4; border-radius:8px; margin:6px 0; font-size:14px; }
        button { background:#0d628e; color:#fff; cursor:pointer; }
        .error { color:#b91c1c; font-size:13px; }
        .msg { padding:10px 12px; border-radius:8px; margin:8px 0; font-size:14px; }
        .msg-err { background:#fdecec; color:#9b1c1c; border:1px solid #edb1b1; }
        a { color:#0d628e; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Définir un nouveau mot de passe</h1>
        <p>Choisissez un mot de passe d’au moins 8 caractères.</p>

        @if(session('error'))
            <div class="msg msg-err">{{ session('error') }}</div>
        @endif

        <form method="post" action="/mot-de-passe/reinitialiser">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">
            <input type="password" name="password" placeholder="Nouveau mot de passe" required>
            <input type="password" name="password_confirmation" placeholder="Confirmer le mot de passe" required>
            @error('password') <div class="error">{{ $message }}</div> @enderror
            <button type="submit">Mettre à jour</button>
        </form>

        <p style="margin-top:10px;"><a href="/login">Retour à la connexion</a></p>
    </div>
</div>
</body>
</html>
