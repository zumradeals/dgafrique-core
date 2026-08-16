<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>Ouverture de {{ $displayName }}</title>
    <style nonce="{{ $nonce }}">
        :root { color-scheme: light; font-family: system-ui, sans-serif; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f7f8fa; color: #172033; }
        main { width: min(92vw, 30rem); padding: 2rem; text-align: center; background: white; border: 1px solid #e4e7ec; border-radius: 1rem; box-shadow: 0 1rem 2.5rem rgba(23, 32, 51, .08); }
        h1 { margin: 0 0 .75rem; font-size: 1.35rem; }
        p { margin: 0; line-height: 1.55; color: #5d6678; }
        button { margin-top: 1.25rem; border: 0; border-radius: .75rem; padding: .8rem 1rem; font: inherit; cursor: pointer; }
    </style>
</head>
<body>
<main>
    <h1>Ouverture de {{ $displayName }}</h1>
    <p>Votre accès sécurisé est en cours de transmission.</p>

    <form id="federation-handoff" method="post" action="{{ $callbackUrl }}">
        <input type="hidden" name="jeton" value="{{ $token }}">
        <noscript>
            <button type="submit">Continuer vers {{ $displayName }}</button>
        </noscript>
    </form>
</main>
<script nonce="{{ $nonce }}">
    document.getElementById('federation-handoff').submit();
</script>
</body>
</html>
