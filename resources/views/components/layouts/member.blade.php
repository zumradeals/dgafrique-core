@props([
    'title' => null,
    'description' => null,
    'active' => null,
    'actions' => [],
    'wide' => false,
])

@php
    $memberActions = $actions ?: [
        ['href' => route('needs.create'), 'label' => 'Exprimer un besoin', 'description' => 'Dire ce qui vous aiderait à avancer.', 'icon' => 'need'],
        ['href' => route('projects.create'), 'label' => 'Lancer un projet', 'description' => 'Préparer votre idée, étape par étape.', 'icon' => 'project'],
    ];
@endphp

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar'], true) ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="color-scheme" content="light">
        <meta name="robots" content="noindex, nofollow">
        <meta name="theme-color" content="#F6F5F0">
        @if ($description)<meta name="description" content="{{ $description }}">@endif
        <title>{{ $title ? $title.' — ' : '' }}GAMAD</title>
        @vite(['resources/css/app.css', 'resources/css/member.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body @class(['dg-member-wide' => $wide])>
        <a class="dg-skip-link" href="#contenu-principal">Aller au contenu</a>

        <div
            class="dg-network-status"
            role="status"
            x-data
            x-cloak
            x-show="!$store.network.online"
        >
            Connexion interrompue. Vos informations déjà affichées restent visibles.
        </div>

        <div class="dg-app-shell">
            <x-dg.navigation :active="$active" :actions="$memberActions" />

            @if (session('status'))
                <div class="dg-member-notice-wrap" role="status">
                    <x-dg.notice type="success" title="Action confirmée">{{ session('status') }}</x-dg.notice>
                </div>
            @endif

            @if (session('success'))
                <div class="dg-member-notice-wrap" aria-live="polite">
                    <x-dg.notice type="success" title="Action confirmée">{{ session('success') }}</x-dg.notice>
                </div>
            @endif

            @if (session('error'))
                <div class="dg-member-notice-wrap">
                    <x-dg.notice type="danger" title="Nous n’avons pas pu terminer">{{ session('error') }}</x-dg.notice>
                </div>
            @endif

            @if ($errors->any())
                <div class="dg-member-notice-wrap" role="alert">
                    <x-dg.notice type="danger" title="Quelques informations sont à vérifier">
                        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </x-dg.notice>
                </div>
            @endif

            <main class="dg-app-main" id="contenu-principal" tabindex="-1">
                {{ $slot }}
            </main>
        </div>

        @livewireScriptConfig
    </body>
</html>
