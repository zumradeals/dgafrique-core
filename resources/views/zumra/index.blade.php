<x-layouts.portal title="ZUMRA — DG Afrique">
    @php
        $initial = mb_strtoupper(mb_substr($identity->label, 0, 1));
        $location = collect([$profile?->city, $profile?->country_code])->filter()->join(' · ');
    @endphp
    <div class="zumra-shell">
        <header class="zumra-header">
            <div class="zumra-branding">
                <a href="{{ route('member.space') }}">← Mon espace</a>
                <strong><i>Z</i> ZUMRA</strong>
                <span>{{ $membership?->status === 'ACTIVE' ? 'Membre actif' : 'Réseau en préparation' }}</span>
            </div>
            <div class="zumra-search">⌕ <span>Rechercher dans ZUMRA…</span></div>
            <div class="zumra-member"><span>{{ $initial }}</span><strong>{{ $identity->label }}</strong></div>
        </header>

        <div class="zumra-layout">
            <aside class="zumra-sidebar">
                <nav aria-label="Navigation ZUMRA">
                    <a class="active" href="{{ route('zumra.index') }}">Fil</a>
                    <a href="{{ route('zumra.membership.show') }}">Mon adhésion <small>{{ $membership ? ($membership->status === 'PENDING_PAYMENT' ? 'à finaliser' : strtolower($membership->status)) : 'à commencer' }}</small></a>
                    <a href="{{ route('zumra.card.show') }}">Ma Carte ZUMRA <small>{{ $membership?->status === 'ACTIVE' ? 'disponible' : 'conditionnée' }}</small></a>
                    <span>Ma contribution <small>à raccorder</small></span>
                    <span>Projets soutenus <small>à raccorder</small></span>
                    <span>Messages <small>à raccorder</small></span>
                    <span>Mon profil ZUMRA <small>à raccorder</small></span>
                </nav>
            </aside>

            <main class="zumra-feed">
                <section class="zumra-composer" aria-disabled="true">
                    <span class="zumra-avatar">{{ $initial }}</span>
                    <div><p>Partagez une actualité, un besoin, un projet…</p><small>La publication sera activée après raccordement du contrat social ZUMRA.</small></div>
                    <button type="button" disabled>Publier</button>
                </section>

                <section class="zumra-empty-feed">
                    <div class="empty-feed-mark"><i></i><i></i><i></i></div>
                    <p class="eyebrow dark">Fil ZUMRA</p>
                    <h1>Le réseau se construit sur des données réelles.</h1>
                    <p>Aucune publication fictive n’est affichée. Le fil s’ouvrira lorsque les contrats de membre, publication et modération seront raccordés.</p>
                    <div class="zumra-gates"><span>{{ $membership ? ($membership->status === 'PENDING_PAYMENT' ? 'Dossier préparé' : 'Adhésion '.strtolower($membership->status)) : 'Adhésion à commencer' }}</span><span>Publication à raccorder</span></div>
                    <a class="zumra-membership-cta" href="{{ route('zumra.membership.show') }}">{{ $membership ? 'Voir mon adhésion' : 'Découvrir le Programme ZUMRA' }} →</a>
                </section>
            </main>

            <aside class="zumra-aside">
                <section class="zumra-profile-card">
                    <span class="zumra-avatar large">{{ $initial }}</span>
                    <h2>{{ $identity->label }}</h2>
                    <p>{{ $location ?: 'Localisation non renseignée' }}</p>
                    <small>{{ $membership?->status === 'ACTIVE' ? 'Membre du Programme ZUMRA' : 'Compte DG Afrique reconnu' }}</small>
                    <div><span><strong>—</strong> contributions</span><span><strong>—</strong> projets suivis</span></div>
                </section>
                <section class="zumra-side-empty"><h2>Profils à suivre</h2><p>Ils apparaîtront à partir de profils ZUMRA attestés.</p><i></i><i></i><i></i></section>
                <section class="zumra-side-empty projects"><h2>Projets tendances</h2><p>Aucun financement n’est présenté sans données gouvernées.</p><i></i><i></i></section>
            </aside>
        </div>
    </div>
</x-layouts.portal>
