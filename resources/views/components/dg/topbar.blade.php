{{--
    Navigation desktop/tablette : six entrées qui nomment la matière de l'action, jamais des
    modules. Les satellites (GamaDrive…) vivent dans « Mes outils », jamais au premier niveau.
    « Fil » est l'entrée par défaut d'un membre connecté.
--}}
@props(['current' => null, 'identity' => null, 'isAdministrator' => false])
<header class="dg-topbar" style="border-radius:0">
    <a href="{{ route('activity.index') }}" class="flex items-center gap-2.5" style="color:inherit">
        <span class="dg-topbar__mark">D</span>
        <strong style="font-size:16px;letter-spacing:-.01em">DG Afrique</strong>
    </a>

    <nav aria-label="Navigation principale">
        <a href="{{ route('activity.index') }}" @if($current === 'fil') aria-current="page" @endif>Fil</a>
        <a href="{{ route('member.space') }}" @if($current === 'espace') aria-current="page" @endif>Mon espace</a>
        <a href="{{ route('people.index') }}" @if($current === 'personnes') aria-current="page" @endif>Personnes</a>
        <a href="{{ route('needs.index') }}" @if($current === 'besoins') aria-current="page" @endif>Besoins</a>
        <a href="{{ route('projects.index') }}" @if($current === 'projets') aria-current="page" @endif>Projets</a>
        <a href="{{ route('zumra.index') }}" @if($current === 'zumra') aria-current="page" @endif>ZUMRA</a>
    </nav>

    <div class="ml-auto flex items-center gap-3.5">
        <div class="dg-topbar__search hidden xl:flex" aria-disabled="true"
             title="La recherche publique sera activée avec l’index de données réelles.">
            <span aria-hidden="true">⌕</span>
            <span>Rechercher une capacité, un besoin…</span>
        </div>

        <details class="dg-tools-menu">
            <summary class="dg-topbar__tools">
                <span aria-hidden="true">⚙</span>
                <span class="hidden sm:inline">Mes outils</span>
            </summary>
            <div>
                <span class="dg-label" style="padding:4px 8px 6px;display:block">Outils spécialisés</span>
                <form method="POST" action="{{ route('federation.continue.gamadrive') }}">
                    @csrf
                    <button type="submit" style="width:100%;display:flex;align-items:center;gap:10px;padding:10px;border-radius:12px;background:transparent;border:0;font:inherit;text-align:left;cursor:pointer">
                        <span class="dg-tool__mark">GD</span>
                        <span style="font-size:13px;font-weight:600;color:var(--dg-night)">GamaDrive</span>
                    </button>
                </form>
                <div style="display:flex;align-items:center;gap:10px;padding:10px;border-radius:12px;border:1px dashed var(--dg-line-dashed);color:var(--dg-faint);font-size:12px;line-height:1.5">
                    D’autres outils apparaîtront ici lorsqu’une action réelle les rendra utiles.
                </div>
            </div>
        </details>

        <details class="dg-account-menu">
            <summary aria-label="Mon compte">
                <span class="dg-avatar dg-avatar--sm dg-avatar--saffron" style="border-radius:12px">{{ $identity ? mb_strtoupper(mb_substr($identity->label, 0, 1)) : '?' }}</span>
            </summary>
            <div>
                @if($identity)
                    <span class="dg-meta" style="display:block;padding:8px 10px 4px">Connecté comme {{ $identity->label }}</span>
                @endif
                <a href="{{ route('member.profile.edit') }}">Mon profil</a>
                <a href="{{ route('messages.index') }}">Messages</a>
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
