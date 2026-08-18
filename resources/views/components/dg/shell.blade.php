{{--
    Coquille commune des écrans authentifiés (Fil, Mon espace) : navigation desktop en tête,
    navigation mobile en pied. Chaque écran reste responsable de son propre contenu.
--}}
@props(['current' => null, 'identity' => null, 'isAdministrator' => false])
<div class="dg min-h-screen" style="padding-bottom:0">
    <div class="hidden lg:block">
        <x-dg.topbar :current="$current" :identity="$identity" :is-administrator="$isAdministrator" />
    </div>

    <header class="lg:hidden flex items-center justify-between" style="height:56px;background:var(--dg-forest);padding:0 18px;color:#EFE6D6">
        <a href="{{ route('activity.index') }}" style="display:flex;align-items:center;gap:8px;color:inherit">
            <span class="dg-topbar__mark" style="width:28px;height:28px;font-size:15px">D</span>
            <strong style="font-size:14px">DG Afrique</strong>
        </a>
        <details class="dg-account-menu">
            <summary aria-label="Mon compte">
                <span class="dg-avatar dg-avatar--sm dg-avatar--saffron" style="border-radius:10px;width:30px;height:30px;font-size:13px">{{ $identity ? mb_strtoupper(mb_substr($identity->label, 0, 1)) : '?' }}</span>
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
    </header>

    <div style="padding-bottom:88px" class="lg:pb-0">
        {{ $slot }}
    </div>

    <div class="lg:hidden fixed inset-x-0 bottom-0 z-30">
        <x-dg.tabbar :current="$current" />
    </div>
</div>
