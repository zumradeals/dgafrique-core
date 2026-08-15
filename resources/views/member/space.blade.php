<x-layouts.portal title="Mon espace — DG Afrique">
    <div class="space-shell doctrine-dashboard">
        <header class="space-header">
            <div class="space-identity"><a class="brand" href="/"><span class="brand-mark">G</span><span>DG Afrique</span></a></div>
            <div class="space-search" aria-label="Recherche bientôt disponible">⌕ <span>Rechercher une capacité, un besoin, une opportunité…</span></div>
            <div class="space-identity"><span class="space-notification" aria-label="Aucune nouvelle notification"><i></i></span><details class="account-menu"><summary class="member-summary"><span class="avatar">{{ mb_strtoupper(mb_substr($identity->label, 0, 1)) }}</span><span class="member-name">{{ $identity->label }}</span></summary><div><a href="{{ route('member.profile.edit') }}">Mon profil</a>@if($isAdministrator)<a href="{{ route('administration.profile.edit') }}">Administration</a><a href="{{ route('administration.zumra.edit') }}">Administration ZUMRA</a>@endif<form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Se déconnecter</button></form></div></details></div>
        </header>

        <div class="space-layout">
            <aside class="space-sidebar"><nav aria-label="Navigation Mon espace"><a class="active nav-blue" href="{{ route('member.space') }}">Vue d’ensemble</a><a class="nav-cyan" href="{{ route('member.profile.edit') }}">Mes capacités</a><a class="nav-blue" href="{{ route('people.index') }}">Découvrir des personnes</a><a class="nav-cyan" href="{{ route('recommendations.index') }}">Mes recommandations</a><span class="nav-amber">Mes besoins <small>à venir</small></span><span class="nav-blue">Opportunités <small>à venir</small></span><a class="nav-green" href="{{ route('zumra.index') }}">Mes ZUMRA <small>aperçu</small></a><a class="nav-green" href="{{ route('zumra.membership.show') }}">Adhésion ZUMRA</a><a class="nav-green" href="{{ route('zumra.card.show') }}">Ma Carte ZUMRA</a><span class="nav-amber">Mes projets <small>à venir</small></span><span class="nav-gray">Messages <small>à venir</small></span><span class="nav-gray">Paramètres <small>à venir</small></span>@if($isAdministrator)<a class="nav-cyan" href="{{ route('administration.profile.edit') }}">Administration</a>@endif</nav></aside>

            <main class="space-content doctrine-space-content">
                <section class="dashboard-welcome"><div><p class="eyebrow dark">MON ESPACE D’ACTION</p><h1>Bonjour, {{ $greetingName }}</h1><p>Faites connaître vos capacités, exprimez vos besoins et avancez vers les bonnes opportunités.</p></div><div class="dashboard-identity"><span>Identité reconnue par DG Afrique</span><strong>Compte vérifié</strong></div></section>

                <section class="dashboard-next-action">
                    <div class="next-action-progress"><span>{{ $profileCompletion }}%</span><div><i style="width: {{ $profileCompletion }}%"></i></div></div>
                    <div><span class="card-kicker">VOTRE PROCHAINE ACTION</span><h2>{{ $profile ? 'Enrichir votre profil de capacités' : 'Commencer votre profil de capacités' }}</h2><p>Plus votre profil est précis, plus DG Afrique pourra vous orienter vers des besoins, apprentissages et collaborations pertinents.</p></div>
                    <a href="{{ route('member.profile.edit') }}">{{ $profile ? 'Continuer mon profil' : 'Créer mon profil' }} →</a>
                </section>

                <div class="dashboard-workspace">
                    <section class="dashboard-main-column">
                        <div class="dashboard-section-title"><div><span class="section-label">MES PARCOURS</span><h2>Que voulez-vous faire ?</h2></div><small>Les fonctions s’ouvrent progressivement</small></div>
                        <div class="action-hub">
                            <a class="action-card capacity" href="{{ route('member.profile.edit') }}"><i>C</i><div><h3>Présenter mes capacités</h3><p>Ce que je sais faire, transmettre ou souhaite apprendre.</p><span>Ouvrir mon profil →</span></div></a>
                            <article class="action-card need"><i>B</i><div><h3>Exprimer un besoin</h3><p>Demander une compétence, une ressource ou un accompagnement.</p><span>Parcours à venir</span></div></article>
                            <article class="action-card opportunity"><i>O</i><div><h3>Explorer les opportunités</h3><p>Découvrir des missions, apprentissages et collaborations.</p><span>Index à venir</span></div></article>
                            <a class="action-card zumra" href="{{ route('zumra.index') }}"><i>Z</i><div><h3>Agir avec ZUMRA</h3><p>Rejoindre une équipe, transmettre et construire un projet collectif.</p><span>Ouvrir l’aperçu →</span></div></a>
                        </div>

                        <section class="activity-empty"><div><span class="section-label">ACTIVITÉ</span><h2>Votre mouvement commencera ici</h2><p>Demandes, invitations, projets et opportunités apparaîtront lorsqu’ils seront réellement raccordés à votre identité.</p></div><div class="activity-lines"><i></i><i></i><i></i></div></section>
                    </section>

                    <aside class="dashboard-side-column">
                        <section class="member-foundation"><span class="card-kicker">VOTRE FONDATION</span><div><strong>{{ $profileCompletion }}%</strong><span>Profil de capacités</span></div><div><strong>Actif</strong><span>Compte DG Afrique</span></div><div><strong>{{ $zumraMembership ? ($zumraMembership->status === 'PENDING_PAYMENT' ? 'Dossier prêt' : ucfirst(strtolower($zumraMembership->status))) : 'À commencer' }}</strong><span>Programme ZUMRA</span></div><small>{{ $zumraMembership ? 'État réel de votre dossier d’adhésion.' : 'Aucune adhésion n’est déduite automatiquement.' }}</small><a href="{{ route('zumra.membership.show') }}">Voir mon adhésion →</a></section>
                        <section class="dashboard-principle"><i></i><span class="section-label">PRINCIPE ZUMRA</span><blockquote>« Apprendre, transmettre et construire ensemble. »</blockquote><p>Formation · Travail · Adoration</p><a href="{{ route('zumra.index') }}">Découvrir le moteur social →</a></section>
                    </aside>
                </div>
            </main>
        </div>
    </div>
</x-layouts.portal>
