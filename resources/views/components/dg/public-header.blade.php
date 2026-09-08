@props(['discovery' => false])
<header class="dg-entry-header">
    <a href="{{ route('gateway') }}" class="dg-entry-brand" aria-label="GAMAD — accueil"><span>GAMAD</span></a>
    <nav class="dg-entry-header__links" aria-label="Navigation publique">
        <a class="dg-entry-header__discover" href="{{ route('gateway') }}#comment-agir">Comment ça marche</a>
        <a class="dg-entry-header__discover" href="{{ route('gateway') }}#zumra">La ZUMRA</a>
        <a class="dg-entry-login" href="{{ route('login') }}">Se connecter</a>
        <a class="dg-button dg-button--primary dg-entry-header__join" href="{{ route('register') }}">Créer mon compte</a>
    </nav>
</header>
