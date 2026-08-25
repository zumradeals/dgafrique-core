@props(['title' => 'Administration — DG Afrique', 'current' => null])
@php
    // ADMIN-CONTROL-002 — sommaire unique de navigation, partagé par toutes les pages
    // administratives : une seule source de vérité pour la sidebar, jamais une navigation
    // recopiée écran par écran. `current` identifie l'écran actif (clé de $navSections).
    $navSections = [
        ['key' => 'dashboard', 'label' => 'Vue d’ensemble', 'route' => 'administration.dashboard', 'icon' => '◈'],
        ['heading' => 'Communauté'],
        ['key' => 'people', 'label' => 'Personnes & capacités', 'route' => 'administration.community.people', 'icon' => '◇'],
        ['key' => 'zumra', 'label' => 'ZUMRA', 'route' => 'administration.community.zumra', 'icon' => '○'],
        ['key' => 'needs', 'label' => 'Besoins', 'route' => 'administration.community.needs', 'icon' => '◎'],
        ['key' => 'organizations', 'label' => 'Organisations', 'route' => 'administration.community.organizations', 'icon' => '▢'],
        ['heading' => 'Projets'],
        ['key' => 'projects', 'label' => 'Projets', 'route' => 'administration.projects.index', 'icon' => '◆'],
        ['key' => 'missions', 'label' => 'Missions', 'route' => 'administration.projects.missions', 'icon' => '☑'],
        ['key' => 'accompaniment', 'label' => 'Accompagnement', 'route' => 'administration.project-accompaniment.edit', 'icon' => '◑'],
        ['key' => 'fundings', 'label' => 'Financements', 'route' => 'administration.projects.fundings', 'icon' => '✚'],
        ['key' => 'proofs', 'label' => 'Preuves', 'route' => 'administration.projects.proofs', 'icon' => '▣'],
        ['heading' => 'Finance'],
        ['key' => 'finance', 'label' => 'Vue financière', 'route' => 'administration.finance.index', 'icon' => '◈'],
        ['key' => 'wallets', 'label' => 'Wallets ZAHAB', 'route' => 'administration.zahab.wallets.index', 'icon' => '▤'],
        ['key' => 'ledger', 'label' => 'Ledger', 'route' => 'administration.ledger.index', 'icon' => '▥'],
        ['key' => 'acquisitions', 'label' => 'Acquisitions GeniusPay', 'route' => 'administration.finance.acquisitions', 'icon' => '⇩'],
        ['key' => 'contributions', 'label' => 'Contributions & adhésions', 'route' => 'administration.finance.contributions', 'icon' => '⇄'],
        ['heading' => 'Pilotage'],
        ['key' => 'engines', 'label' => 'Moteurs', 'route' => 'administration.engines.index', 'icon' => '⚙'],
        ['key' => 'moderation', 'label' => 'Modération', 'route' => 'administration.moderation.index', 'icon' => '⚑'],
        ['key' => 'configuration', 'label' => 'Configuration', 'route' => 'administration.configuration.index', 'icon' => '☰'],
        ['key' => 'journal', 'label' => 'Journal', 'route' => 'administration.journal.index', 'icon' => '▦'],
    ];
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
        @vite(['resources/css/app.css', 'resources/css/admin-console.css'])
    @endif
</head>
<body class="ac-body">
    <input type="checkbox" id="ac-sidebar-toggle" class="ac-sidebar-toggle-input" aria-hidden="true">
    <div class="ac-shell">
        <label for="ac-sidebar-toggle" class="ac-scrim" aria-hidden="true"></label>
        <aside class="ac-sidebar" aria-label="Navigation d’administration">
            <a href="{{ route('administration.dashboard') }}" class="ac-brand">
                <span class="ac-brand__mark">D<b>G</b></span>
                <span>Administration</span>
            </a>
            <nav class="ac-nav">
                @foreach($navSections as $item)
                    @if(isset($item['heading']))
                        <p class="ac-nav__heading">{{ $item['heading'] }}</p>
                    @else
                        <a href="{{ route($item['route']) }}" class="ac-nav__link @if($current === $item['key']) is-active @endif">
                            <span aria-hidden="true">{{ $item['icon'] }}</span>{{ $item['label'] }}
                        </a>
                    @endif
                @endforeach
            </nav>
            <a href="{{ route('member.space') }}" class="ac-nav__exit">← Retour au portail membre</a>
        </aside>
        <div class="ac-main">
            <header class="ac-topbar">
                <label for="ac-sidebar-toggle" class="ac-topbar__burger" aria-label="Ouvrir la navigation">☰</label>
                <span class="ac-topbar__title">{{ $title }}</span>
            </header>
            <main class="ac-content">
                @if(session('status'))
                    <div class="ac-alert ac-alert--success">{{ session('status') }}</div>
                @endif
                @if($errors->any())
                    <div class="ac-alert ac-alert--danger">{{ $errors->first() }}</div>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
