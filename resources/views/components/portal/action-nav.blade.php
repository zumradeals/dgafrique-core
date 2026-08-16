<nav class="dg-network-nav" aria-label="Navigation principale DG Afrique">
    <div class="dg-container dg-network-nav__inner">
        <a class="dg-brand" href="{{ route('activity.index') }}">
            <span class="dg-brand__mark" aria-hidden="true">DG</span>
            <span>DG Afrique</span>
        </a>

        <div class="dg-network-nav__links">
            <a href="{{ route('activity.index') }}" @if(request()->routeIs('activity.*')) aria-current="page" @endif>Fil</a>
            <a href="{{ route('member.space') }}" @if(request()->routeIs('member.space')) aria-current="page" @endif>Mon espace</a>
            <a href="{{ route('people.index') }}" @if(request()->routeIs('people.*')) aria-current="page" @endif>Personnes</a>
            <a href="{{ route('needs.index') }}" @if(request()->routeIs('needs.*')) aria-current="page" @endif>Besoins</a>
            <a href="{{ route('projects.index') }}" @if(request()->routeIs('projects.*')) aria-current="page" @endif>Projets</a>
            <a href="{{ route('zumra.index') }}" @if(request()->routeIs('zumra.*')) aria-current="page" @endif>ZUMRA</a>

            <details class="dg-network-nav__tools">
                <summary>Plus</summary>
                <div>
                    <a href="{{ route('messages.index') }}">Messages</a>
                    <a href="{{ route('shares.index') }}">Partages reçus</a>
                    <a href="{{ route('federation.continue.gamadrive') }}">Mes outils · GamaDrive</a>
                </div>
            </details>
        </div>
    </div>
</nav>

<nav class="dg-mobile-nav" aria-label="Navigation mobile DG Afrique">
    <a href="{{ route('activity.index') }}" @if(request()->routeIs('activity.*')) aria-current="page" @endif>Fil</a>
    <a href="{{ route('people.index') }}" @if(request()->routeIs('people.*')) aria-current="page" @endif>Personnes</a>
    <a href="{{ route('needs.index') }}" @if(request()->routeIs('needs.*')) aria-current="page" @endif>Agir</a>
    <a href="{{ route('zumra.index') }}" @if(request()->routeIs('zumra.*')) aria-current="page" @endif>ZUMRA</a>
    <a href="{{ route('member.space') }}" @if(request()->routeIs('member.*')) aria-current="page" @endif>Moi</a>
</nav>
