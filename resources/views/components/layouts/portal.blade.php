@props([
    'title' => 'DG Afrique',
    'styles' => [],
])
@php
    $pageStyles = (array) $styles;
    // Le Cerveau (projects.brain.show) a rejoint x-dg.shell / la navigation globale réelle
    // (PVB-I05 V1) : il ne doit plus recevoir le bandeau de contournement .dg-global-nav ni
    // l'ancienne feuille de style project-workspace-v2.css, réservés au flux de naissance du
    // projet (projects.brain.start.*), hors périmètre de cette refonte.
    $isProjectWorkspace = request()->routeIs('projects.brain.*') && ! request()->routeIs('projects.brain.show');
    if (request()->routeIs('zumra.index')) {
        $pageStyles[] = 'resources/css/zumra-hub.css';
    }
    if (request()->routeIs('member.space')) {
        $pageStyles[] = 'resources/css/member-space-v2.css';
    }
    if (request()->routeIs('login') || request()->routeIs('register') || request()->routeIs('register.verify')) {
        $pageStyles[] = 'resources/css/auth-v2.css';
    }
    if (request()->routeIs('activity.index')) {
        $pageStyles[] = 'resources/css/fil-v2.css';
    }
    if (request()->routeIs('projects.index')) {
        $pageStyles[] = 'resources/css/projects-directory.css';
    }
    if (request()->routeIs('needs.index')) {
        $pageStyles[] = 'resources/css/needs-directory.css';
    }
    if (request()->routeIs('projects.show')) {
        $pageStyles[] = 'resources/css/project-detail.css';
    }
    if (request()->routeIs('projects.brain.show')) {
        $pageStyles[] = 'resources/css/project-brain.css';
    }
    if ($isProjectWorkspace) {
        $pageStyles[] = 'resources/css/project-workspace-v2.css';
    }
    $pageStyles = array_values(array_unique($pageStyles));
@endphp
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(array_merge(['resources/css/app.css', 'resources/css/identity-v2.css'], $pageStyles, ['resources/js/app.js']))
    @endif
    @if($isProjectWorkspace)
    <style>
        .dg-global-nav{height:46px;background:#fff;border-bottom:1px solid #e7e1d8;display:flex;align-items:center;padding:0 22px;gap:24px;position:sticky;top:0;z-index:60;font-family:Inter,ui-sans-serif,system-ui,-apple-system,sans-serif;box-shadow:0 1px 5px rgba(6,26,54,.04)}
        .dg-global-brand{display:flex;align-items:center;gap:9px;color:#073746;text-decoration:none;font-weight:800;font-size:13px;white-space:nowrap}
        .dg-global-mark{width:30px;height:30px;border-radius:9px;background:#073746;color:#fff;display:grid;place-items:center;font-weight:900;letter-spacing:-1px}.dg-global-mark b{color:#f4772b}
        .dg-global-links{display:flex;align-items:stretch;height:100%;gap:2px}.dg-global-links a{display:flex;align-items:center;padding:0 12px;color:#43545b;text-decoration:none;font-size:12px;font-weight:600;border-bottom:2px solid transparent}.dg-global-links a:hover{color:#073746;background:#faf8f4}.dg-global-links a.active{color:#073746;border-bottom-color:#f4772b;font-weight:800}
        .dg-global-actions{margin-left:auto;display:flex;align-items:center;gap:8px}.dg-global-tools,.dg-global-act{height:32px;border-radius:9px;display:flex;align-items:center;padding:0 12px;text-decoration:none;font-size:12px;font-weight:800}.dg-global-tools{border:1px solid #d9e0e2;color:#073746;background:#fff}.dg-global-act{background:#f4772b;color:#fff}
        .dg-global-exit{color:#65767d;text-decoration:none;font-size:11px;font-weight:700;padding:7px 9px;border-radius:8px}.dg-global-exit:hover{background:#f5f2ed;color:#073746}
        @media(max-width:980px){.dg-global-nav{gap:10px;padding:0 12px}.dg-global-brand span:last-child{display:none}.dg-global-links a{padding:0 8px}.dg-global-actions .dg-global-tools{display:none}}
        @media(max-width:760px){.dg-global-nav{overflow-x:auto;height:44px}.dg-global-links{min-width:max-content}.dg-global-links a{font-size:11px}.dg-global-actions{display:none}}
    </style>
    @endif
</head>
<body class="portal-body">
    @if($isProjectWorkspace)
        <nav class="dg-global-nav" aria-label="Navigation principale DG Afrique">
            <a class="dg-global-brand" href="{{ route('activity.index') }}" title="Retour au Fil DG Afrique">
                <span class="dg-global-mark">D<b>G</b></span><span>DG Afrique</span>
            </a>
            <div class="dg-global-links">
                <a href="{{ route('activity.index') }}">Fil</a>
                <a href="{{ route('member.space') }}">Mon espace</a>
                <a href="{{ route('people.index') }}">Personnes</a>
                <a href="{{ route('needs.index') }}">Besoins</a>
                <a class="active" href="{{ route('projects.index') }}" aria-current="page">Projets</a>
                <a href="{{ route('zumra.index') }}">ZUMRA</a>
            </div>
            <div class="dg-global-actions">
                <a class="dg-global-exit" href="{{ route('projects.index') }}">← Tous les projets</a>
                <a class="dg-global-tools" href="{{ route('member.space') }}">⌘&nbsp; Mes outils</a>
                <a class="dg-global-act" href="{{ route('projects.create') }}">＋ AGIR</a>
            </div>
        </nav>
    @endif
    {{ $slot }}
</body>
</html>
