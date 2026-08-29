@props([
    'title' => null,
    'description' => null,
    'active' => null,
    'actions' => [],
])

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar'], true) ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="color-scheme" content="light">
        <meta name="theme-color" content="#F6F5F0">
        @if ($description)<meta name="description" content="{{ $description }}">@endif
        <title>{{ $title ? $title.' — ' : '' }}DG Afrique</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body>
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
            <x-dg.navigation :active="$active" :actions="$actions" />

            @if (session('success'))
                <div class="mx-auto w-full max-w-[76rem] px-4 pt-4" aria-live="polite">
                    <x-dg.notice type="success" title="Action confirmée">{{ session('success') }}</x-dg.notice>
                </div>
            @endif

            @if (session('error'))
                <div class="mx-auto w-full max-w-[76rem] px-4 pt-4">
                    <x-dg.notice type="danger" title="Nous n’avons pas pu terminer">{{ session('error') }}</x-dg.notice>
                </div>
            @endif

            <main class="dg-app-main" id="contenu-principal" tabindex="-1">
                {{ $slot }}
            </main>
        </div>

        @livewireScriptConfig
    </body>
</html>
