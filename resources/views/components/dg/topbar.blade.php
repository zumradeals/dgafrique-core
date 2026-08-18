{{--
    Navigation desktop/tablette DG Afrique V2.
    Le pétrole structure le réseau ; l’orange solaire signale le passage à l’action.
    Les satellites restent dans « Mes outils », jamais au premier niveau.
--}}
@props(['current' => null, 'identity' => null, 'isAdministrator' => false])
{{-- $satellites est injecté par FederationServiceProvider (CAP-049). --}}
<header class="dg-topbar">
    <a href="{{ route('activity.index') }}" class="dg-brand" aria-label="DG Afrique — Fil">
        <span class="dg-brandmark" aria-hidden="true">
            <span class="dg-brandmark__d">D</span>
            <span class="dg-brandmark__g">G</span>
        </span>
        <span class="dg-brand__word">DG Afrique</span>
    </a>

    <nav aria-label="Navigation principale">
        <a href="{{ route('activity.index') }}" @if($current === 'fil') aria-current="page" @endif>Fil</a>
        <a href="{{ route('member.space') }}" @if($current === 'espace') aria-current="page" @endif>Mon espace</a>
        <a href="{{ route('people.index') }}" @if($current === 'personnes') aria-current="page" @endif>Personnes</a>
        <a href="{{ route('needs.index') }}" @if($current === 'besoins') aria-current="page" @endif>Besoins</a>
        <a href="{{ route('projects.index') }}" @if($current === 'projets') aria-current="page" @endif>Projets</a>
        <a href="{{ route('zumra.index') }}" @if($current === 'zumra') aria-current="page" @endif>ZUMRA</a>
    </nav>

    <div class="ml-auto flex items-center gap-2.5">
        <div class="dg-topbar__search hidden xl:flex" aria-disabled="true"
             title="La recherche publique sera activée avec l’index de données réelles.">
            <span aria-hidden="true">⌕</span>
            <span>Rechercher dans le réseau…</span>
        </div>

        <a href="{{ route('notifications.index') }}" class="dg-topbar__notify" aria-label="Notifications">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M7.5 9.5a4.5 4.5 0 0 1 9 0c0 5 2 5.5 2 6.5h-13c0-1 2-1.5 2-6.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                <path d="M10 19h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
            </svg>
        </a>

        <details class="dg-tools-menu">
            <summary class="dg-topbar__tools">
                <span aria-hidden="true">⌘</span>
                <span class="hidden sm:inline">Mes outils</span>
            </summary>
            <div>
                <span class="dg-label" style="padding:4px 8px 6px;display:block">Outils spécialisés</span>
                @forelse(($satellites ?? collect()) as $satellite)
                    <form method="POST" action="{{ route('federation.continue', $satellite->slug) }}">
                        @csrf
                        <button type="submit" style="width:100%;display:flex;align-items:center;gap:10px;padding:10px;border-radius:12px;background:transparent;border:0;font:inherit;text-align:left;cursor:pointer">
                            <span class="dg-tool__mark">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($satellite->display_name, 0, 2)) }}</span>
                            <span style="font-size:13px;font-weight:600;color:var(--dg-night)">{{ $satellite->display_name }}</span>
                        </button>
                    </form>
                @empty
                    <div style="display:flex;align-items:center;gap:10px;padding:10px;border-radius:12px;border:1px dashed var(--dg-line-dashed);color:var(--dg-faint);font-size:12px;line-height:1.5">
                        D’autres outils apparaîtront ici lorsqu’une action réelle les rendra utiles.
                    </div>
                @endforelse
            </div>
        </details>

        <button type="button" class="dg-topbar__agir" data-dg-agir-open aria-haspopup="dialog" aria-controls="dg-agir-sheet">
            <span aria-hidden="true">＋</span>
            AGIR
        </button>

        <details class="dg-account-menu">
            <summary aria-label="Mon compte">
                <span class="dg-avatar dg-avatar--sm dg-avatar--saffron">{{ $identity ? mb_strtoupper(mb_substr($identity->label, 0, 1)) : '?' }}</span>
            </summary>
            <div>
                @if($identity)
                    <span class="dg-meta" style="display:block;padding:8px 10px 4px">Connecté comme {{ $identity->label }}</span>
                @endif
                <a href="{{ route('member.profile.edit') }}">Mon profil</a>
                <a href="{{ route('messages.index') }}">Messages</a>
                <a href="{{ route('notifications.index') }}">Notifications</a>
                @if($isAdministrator)
                    <a href="{{ route('administration.profile.edit') }}">Administration</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Se déconnecter</button>
                </form>
            </div>
        </details>
    </div>
</header>
