<x-layouts.portal title="Mon espace — DG Afrique">
    <div class="space-shell">
        <header class="space-header">
            <a class="brand" href="/">
                <span class="brand-mark">DG</span><span>DG Afrique</span>
            </a>
            <div class="space-search" aria-label="Recherche bientôt disponible">⌕ <span>Rechercher dans DG Afrique…</span></div>
            <div class="member-summary">
                <span class="avatar">{{ mb_strtoupper(mb_substr($identity->label, 0, 1)) }}</span>
                <span class="member-name">{{ $identity->label }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="logout-button" type="submit">Se déconnecter</button>
                </form>
            </div>
        </header>

        <div class="space-layout">
            <aside class="space-sidebar">
                <nav aria-label="Navigation Mon espace">
                    <a class="active" href="{{ route('member.space') }}">Tableau de bord</a>
                    <a href="{{ route('member.profile.edit') }}">Mon profil <small>CAP‑003</small></a>
                    <span>ZUMRA <small>à venir</small></span>
                    <span>Mes demandes <small>à venir</small></span>
                    <span>Satellites <small>à venir</small></span>
                    <span>Paramètres <small>à venir</small></span>
                    @if($isAdministrator)<a href="{{ route('administration.profile.edit') }}">Administration <small>Configurer</small></a>@endif
                </nav>
            </aside>

            <main class="space-content">
                <p class="eyebrow dark">Mon espace</p>
                <h1>Bienvenue, {{ $identity->label }}</h1>
                <p class="space-lead">Votre identité GAMAD est reconnue. Votre espace personnel DG Afrique peut maintenant se construire autour d’elle.</p>

                <section class="status-grid" aria-label="État du compte">
                    <article class="status-card featured">
                        <span class="card-kicker">Compte DG Afrique</span>
                        <strong>Accès actif</strong>
                        <p>Session vérifiée directement auprès de GAMAD Core.</p>
                    </article>
                    <article class="status-card">
                        <span class="card-kicker">Identité canonique</span>
                        <strong>{{ $identity->reference }}</strong>
                        <p>{{ $identity->regime }}</p>
                    </article>
                    <article class="status-card">
                        <span class="card-kicker">Programme ZUMRA</span>
                        <strong>Non déterminé</strong>
                        <p>Le compte gratuit n’entraîne aucune adhésion automatique.</p>
                    </article>
                </section>

                <section class="next-section">
                    <div>
                        <p class="eyebrow dark">Prochaine étape</p>
                        <h2>{{ $profile ? 'Enrichir votre profil de capacités' : 'Construire votre profil de capacités' }}</h2>
                        <p>{{ $profile ? 'Votre profil est enregistré. Vous pouvez le compléter à votre rythme.' : 'Les informations métier resteront dans DG Afrique et seront rattachées à votre référence Core, sans modifier votre identité canonique.' }}</p>
                    </div>
                    <a class="next-badge profile-action" href="{{ route('member.profile.edit') }}">{{ $profile ? 'Mettre à jour mon profil' : 'Commencer mon profil' }}</a>
                </section>
            </main>
        </div>
    </div>
</x-layouts.portal>
