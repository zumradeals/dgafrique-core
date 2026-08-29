@props(['title' => null, 'description' => null])

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
        <main class="dg-public-main" id="contenu-principal" tabindex="-1">
            {{ $slot }}
        </main>
        @livewireScriptConfig
    </body>
</html>
