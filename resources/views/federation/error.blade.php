<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>Accès indisponible</title>
</head>
<body>
<main>
    <h1>Cet outil ne peut pas être ouvert</h1>
    <p>{{ $message }}</p>
    <form method="POST" action="{{ route('federation.continue', $satelliteSlug) }}">@csrf<button type="submit">Réessayer</button></form>
    <p><a href="/espace">Retour à mon espace DG Afrique</a></p>
</main>
</body>
</html>
