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

            <main class="space-content dashboard-content">
                <section class="dashboard-intro">
                    <div><p class="eyebrow dark">Mon espace</p><h1>Bienvenue,<br><em>{{ $identity->label }}</em></h1><p class="space-lead">Un seul espace pour présenter vos capacités, suivre ce qui vous concerne et accéder progressivement à l’écosystème.</p></div>
                    <div class="identity-seal"><span>✓</span><div><small>IDENTITÉ GAMAD</small><strong>Reconnue et active</strong><code>{{ $identity->reference }}</code></div></div>
                </section>

                <section class="status-grid" aria-label="État du compte">
                    <article class="status-card featured">
                        <span class="status-symbol">DG</span><span class="card-kicker">Compte DG Afrique</span><strong>Votre porte d’entrée est ouverte.</strong><p>Votre session est vérifiée directement auprès de GAMAD Core.</p><a href="{{ route('member.profile.edit') }}">Continuer mon parcours →</a>
                    </article>
                    <article class="status-card">
                        <span class="status-symbol blue">✦</span><span class="card-kicker">Profil de capacités</span><strong>{{ $profile ? 'Déjà commencé' : 'À construire librement' }}</strong><p>{{ $profile ? 'Vous pouvez enrichir vos informations à votre rythme.' : 'Présentez vos pratiques, vos envies d’apprendre et vos intentions.' }}</p><a href="{{ route('member.profile.edit') }}">{{ $profile ? 'Enrichir mon profil' : 'Commencer mon profil' }} →</a>
                    </article>
                    <article class="status-card">
                        <span class="status-symbol green">Z</span><span class="card-kicker">Programme ZUMRA</span><strong>Adhésion non déterminée</strong><p>Votre compte gratuit n’entraîne aucune adhésion ni contribution automatique.</p><span class="honest-state">État à raccorder</span>
                    </article>
                </section>

                <section class="dashboard-lower"><div class="next-section"><div><p class="eyebrow dark">Prochaine action utile</p><h2>{{ $profile ? 'Faites vivre votre profil.' : 'Dites-nous ce que vous savez faire.' }}</h2><p>{{ $profile ? 'Ajoutez progressivement vos nouvelles pratiques et vos prochains objectifs.' : 'Les expériences informelles comptent autant que les parcours classiques. Aucun score ne résumera votre valeur.' }}</p></div><a class="next-badge profile-action" href="{{ route('member.profile.edit') }}">{{ $profile ? 'Mettre à jour' : 'Commencer maintenant' }} <span>→</span></a></div><aside class="available-services"><p class="eyebrow dark">Disponible maintenant</p><a href="{{ route('member.profile.edit') }}"><span>01</span><div><b>Mon profil</b><small>Capacités et intentions</small></div><i>→</i></a>@if($isAdministrator)<a href="{{ route('administration.profile.edit') }}"><span>02</span><div><b>Administration</b><small>Configuration du portail</small></div><i>→</i></a>@endif<div class="future-service"><span>+</span><div><b>Autres services</b><small>Ouverture progressive</small></div></div></aside></section>
            </main>
        </div>
    </div>
</x-layouts.portal>
