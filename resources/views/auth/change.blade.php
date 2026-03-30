<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Changer le mot de passe - ANBG</title>
    <style>
        body { margin:0; font-family: "Segoe UI", sans-serif; background:#eef4f9; color:#102230; }
        .wrap { min-height:100vh; display:grid; place-items:center; padding:20px; }
        .card { width:min(480px, 100%); background:#fff; border:1px solid #d7e2ea; border-radius:12px; padding:20px; }
        h1 { margin:0 0 6px; }
        p { color:#4c6272; margin:0 0 14px; }
        input, button { width:100%; padding:10px; border:1px solid #cfdbe4; border-radius:8px; margin:6px 0; font-size:14px; }
        button { background:#0d628e; color:#fff; cursor:pointer; }
        .error { color:#b91c1c; font-size:13px; }
        .msg { padding:10px 12px; border-radius:8px; margin:8px 0; font-size:14px; }
        .msg-ok { background:#eaf7ef; color:#146638; border:1px solid #9bd5af; }
        .msg-err { background:#fdecec; color:#9b1c1c; border:1px solid #edb1b1; }
        a { color:#0d628e; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Nouveau mot de passe requis</h1>
        <p>Bonjour {{ $actor->prenom }} {{ $actor->nom }}. Pour continuer, définissez un nouveau mot de passe.</p>

        @if(session('error'))
            <div class="msg msg-err">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="msg msg-ok">{{ session('success') }}</div>
        @endif

        <form method="post" action="/mot-de-passe/nouveau">
            @csrf
            <input type="password" name="ancien_mdp" placeholder="Ancien mot de passe" required>
            <input type="password" name="password" placeholder="Nouveau mot de passe (min 8 caractères)" required>
            <input type="password" name="password_confirmation" placeholder="Confirmer le nouveau mot de passe" required>
            @error('ancien_mdp') <div class="error">{{ $message }}</div> @enderror
            @error('password') <div class="error">{{ $message }}</div> @enderror
            <button type="submit">Enregistrer et continuer</button>
        </form>

        <form method="post" action="/logout" style="margin-top:10px;">
            @csrf
            <button type="submit" style="background:none;border:0;color:#0d628e;cursor:pointer;padding:0;text-align:left;">
                Déconnexion
            </button>
        </form>
    </div>
</div>
</body>
</html>
