@props([
    'title' => 'DG Afrique',
    'styles' => [],
])
@php
    $pageStyles = (array) $styles;
    if (request()->routeIs('zumra.index')) {
        $pageStyles[] = 'resources/css/zumra-hub.css';
    }
    if (request()->routeIs('member.space')) {
        $pageStyles[] = 'resources/css/member-space-v2.css';
    }
    if (request()->routeIs('landing')) {
        $pageStyles[] = 'resources/css/landing-v2.css';
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
