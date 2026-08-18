{{--
    Coquille commune des écrans authentifiés : navigation desktop en tête,
    navigation mobile en pied. Chaque écran reste responsable de son contenu.
--}}
@props(['current' => null, 'identity' => null, 'isAdministrator' => false])
<div class="dg min-h-screen" style="padding-bottom:0">
    <div class="hidden lg:block">
        <x-dg.topbar :current="$current" :identity="$identity" :is-administrator="$isAdministrator" />
    </div>

    <div class="lg:hidden">
        <header class="dg-mobilebar">
            <a href="{{ route('activity.index') }}" class="dg-brand" aria-label="DG Afrique — Fil">
                <span class="dg-brandmark" aria-hidden="true">
                    <span class="dg-brandmark__d">D</span>
                    <span class="dg-brandmark__g">G</span>
                </span>
                <span class="dg-brand__word">DG Afrique</span>
            </a>

            <div class="dg-mobilebar__actions">
                <a href="{{ route('notifications.index') }}" class="dg-topbar__notify" aria-label="Notifications">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M7.5 9.5a4.5 4.5 0 0 1 9 0c0 5 2 5.5 2 6.5h-13c0-1 2-1.5 2-6.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                        <path d="M10 19h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                </a>

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
    </div>

    <div style="padding-bottom:88px" class="lg:pb-0">
        {{ $slot }}
    </div>

    <div class="lg:hidden fixed inset-x-0 bottom-0 z-30">
        <x-dg.tabbar :current="$current" />
    </div>

    {{-- Une seule feuille d’action partagée par le bouton AGIR desktop et mobile. --}}
    <x-dg.agir-sheet />
</div>
