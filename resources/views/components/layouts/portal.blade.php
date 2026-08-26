@props([
    'title' => 'DG Afrique',
    'styles' => [],
])
@php
    $pageStyles = (array) $styles;
    if (request()->routeIs('zumra.index')) {
        $pageStyles[] = 'resources/css/zumra-hub.css';
    }
    if (request()->routeIs('zumra.groups.create')) {
        $pageStyles[] = 'resources/css/zumra-birth.css';
    }
    if (request()->routeIs('zumra.groups.show')) {
        $pageStyles[] = 'resources/css/zumra-space.css';
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
    if (request()->routeIs('projects.draft.*')) {
        $pageStyles[] = 'resources/css/zumra-project-birth.css';
    }
    if (request()->routeIs('needs.index')) {
        $pageStyles[] = 'resources/css/needs-directory.css';
    }
    if (request()->routeIs('missions.index')) {
        $pageStyles[] = 'resources/css/missions-directory.css';
    }
    if (request()->routeIs('missions.show')
        || request()->routeIs('projects.missions.create')
        || request()->routeIs('zumra.groups.missions.create')
        || request()->routeIs('needs.missions.create')
        || request()->routeIs('missions.children.create')
        || request()->routeIs('missions.matching')
        || request()->routeIs('missions.blockers.express-need.create')) {
        $pageStyles[] = 'resources/css/mission-detail.css';
    }
    if (request()->routeIs('transmissions.index')
        || request()->routeIs('transmissions.create')
        || request()->routeIs('transmissions.show')
        || request()->routeIs('transmissions.matching')) {
        $pageStyles[] = 'resources/css/transmission-detail.css';
    }
    if (request()->routeIs('proofs.index')
        || request()->routeIs('proofs.create')
        || request()->routeIs('proofs.show')
        || request()->routeIs('proofs.memory.self')
        || request()->routeIs('proofs.memory')) {
        $pageStyles[] = 'resources/css/proof-detail.css';
    }
    if (request()->routeIs('people.index')) {
        $pageStyles[] = 'resources/css/people-directory.css';
    }
    if (request()->routeIs('projects.show')) {
        $pageStyles[] = 'resources/css/project-space.css';
    }
    if (request()->routeIs('projects.brain.show')) {
        $pageStyles[] = 'resources/css/project-brain.css';
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
</head>
<body class="portal-body">
    {{ $slot }}
</body>
</html>
