@props(['discovery' => false])
<header class="dg-entry-header">
    <a href="{{ route('gateway') }}" class="dg-entry-brand" aria-label="DG Afrique — accueil"><span>DG</span> Afrique</a>
    <nav class="dg-entry-header__links" aria-label="Navigation publique">
        @if (! $discovery)
            <a class="dg-entry-header__discover" href="{{ route('landing') }}">Découvrir</a>
        @endif
        <a class="dg-entry-login" href="{{ route('login') }}">Se connecter</a>
        @if ($discovery)
            <a class="dg-button dg-button--primary dg-entry-header__join" href="{{ route('register') }}">Créer un compte</a>
        @endif
    </nav>
</header>
