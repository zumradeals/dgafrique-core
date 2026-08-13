<x-layouts.portal title="Mon espace — DG Afrique">
    <div class="space-shell">
        <header class="space-header">
            <div class="space-identity">
                <a class="brand" href="/">
                    <span class="brand-mark">G</span><span>DG Afrique</span>
                </a>
                <span class="space-notification" aria-label="Aucune nouvelle notification"><i></i></span>
                <details class="account-menu">
                    <summary class="member-summary">
                        <span class="avatar">{{ mb_strtoupper(mb_substr($identity->label, 0, 1)) }}</span>
                        <span class="member-name">{{ $identity->label }}</span>
                    </summary>
                    <div>
                        <a href="{{ route('member.profile.edit') }}">Mon profil</a>
                        @if($isAdministrator)<a href="{{ route('administration.profile.edit') }}">Administration</a>@endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit">Se déconnecter</button>
                        </form>
                    </div>
                </details>
            </div>
            <div class="space-search" aria-label="Recherche bientôt disponible">⌕ <span>Rechercher…</span></div>
        </header>

        <div class="space-layout">
            <aside class="space-sidebar">
                <nav aria-label="Navigation Mon espace">
                    <a class="active nav-blue" href="{{ route('member.space') }}">Tableau de bord</a>
                    <a class="nav-cyan" href="{{ route('member.profile.edit') }}">Mon profil</a>
                    <a class="nav-green" href="{{ route('zumra.index') }}">ZUMRA <small>aperçu</small></a>
                    <span class="nav-amber">Mes demandes <small>à venir</small></span>
                    <span class="nav-blue">Satellites <small>à venir</small></span>
                    <span class="nav-gray">Paramètres <small>à venir</small></span>
                    @if($isAdministrator)<a class="nav-cyan" href="{{ route('administration.profile.edit') }}">Administration</a>@endif
                </nav>
            </aside>

            <main class="space-content">
                <div class="dashboard-heading">
                    <h1>Bonjour, {{ $greetingName }}</h1>
                    <p>Voici l’état de votre espace DG Afrique.</p>
                </div>

                <section class="status-grid" aria-label="État du compte">
                    <article class="status-card">
                        <span class="card-kicker">Profil complété</span>
                        <strong>{{ $profileCompletion }}%</strong>
                        <div class="profile-progress" aria-label="Profil complété à {{ $profileCompletion }} %"><i style="width: {{ $profileCompletion }}%"></i></div>
                    </article>
                    <article class="status-card">
                        <span class="card-kicker">Demandes en cours</span>
                        <strong>—</strong>
                        <p>La capacité Demandes n’est pas encore ouverte.</p>
                    </article>
                    <article class="status-card">
                        <span class="card-kicker">Contribution ZUMRA</span>
                        <strong class="pending-state">Non attestée</strong>
                        <p>Aucun statut n’est inventé sans contrat ZUMRA.</p>
                    </article>
                </section>

                <section class="dashboard-actions" aria-label="Actions rapides">
                    <article>
                        <h2>{{ $profile ? 'Compléter mon profil' : 'Créer mon profil' }}</h2>
                        <p>Ajoutez vos compétences et informations.</p>
                        <a href="{{ route('member.profile.edit') }}">Continuer →</a>
                    </article>
                    <article class="zumra-action">
                        <h2>Ouvrir ZUMRA</h2>
                        <p>Fil, contribution et projets soutenus.</p>
                        <a href="{{ route('zumra.index') }}">Ouvrir l’aperçu →</a>
                    </article>
                    <article>
                        <h2>Faire une demande</h2>
                        <p>Décrivez votre projet et suivez son traitement.</p>
                        <span>Capacité à venir →</span>
                    </article>
                </section>

                <section class="dashboard-trust">
                    <div><span>Identité GAMAD</span><strong>{{ $identity->reference }}</strong></div>
                    <p>Votre session est vérifiée directement auprès de GAMAD Core.</p>
                </section>
            </main>
        </div>
    </div>
</x-layouts.portal>
