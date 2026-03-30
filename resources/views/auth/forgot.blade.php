<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mot de passe oublié - ANBG</title>
    <style>
        body { margin:0; font-family: "Segoe UI", sans-serif; background:#eef4f9; color:#102230; }
        .wrap { min-height:100vh; display:grid; place-items:center; padding:20px; }
        .card { width:min(450px, 100%); background:#fff; border:1px solid #d7e2ea; border-radius:12px; padding:20px; }
        h1 { margin:0 0 6px; }
        p { color:#4c6272; margin:0 0 14px; }
        input, button { width:100%; padding:10px; border:1px solid #cfdbe4; border-radius:8px; margin:6px 0; font-size:14px; }
        button { background:#0d628e; color:#fff; cursor:pointer; }
        .error { color:#b91c1c; font-size:13px; }
        .muted { color:#4c6272; font-size:13px; }
        .msg { padding:10px 12px; border-radius:8px; margin:8px 0; font-size:14px; }
        .msg-ok { background:#eaf7ef; color:#146638; border:1px solid #9bd5af; }
        .msg-err { background:#fdecec; color:#9b1c1c; border:1px solid #edb1b1; }
        a { color:#0d628e; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Mot de passe oublié</h1>
        <p>Saisissez votre email professionnel pour recevoir le lien de réinitialisation.</p>

        @if(session('success'))
            <div class="msg msg-ok">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="msg msg-err">{{ session('error') }}</div>
        @endif
        @if(session('reset_link'))
            <div class="msg msg-ok">
                Lien de réinitialisation :
                <a href="{{ session('reset_link') }}">{{ session('reset_link') }}</a>
            </div>
        @endif

        <form method="post" action="/mot-de-passe/oubli">
            @csrf
            <input type="email" name="email" placeholder="Email professionnel" required value="{{ old('email') }}">
            @error('email') <div class="error">{{ $message }}</div> @enderror
            <button type="submit">Envoyer le lien</button>
        </form>

        <p class="muted" style="margin-top:10px;">
            <a href="/login">Retour à la connexion</a>
        </p>
    </div>
</div>
</body>
</html>
