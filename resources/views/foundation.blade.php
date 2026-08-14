<x-layouts.portal title="DG Afrique — Capacités, besoins et opportunités">
    <div class="claude-portal doctrine-home">
        <header class="claude-header">
            <div class="claude-header-left">
                <a class="claude-brand" href="/" aria-label="Accueil DG Afrique"><span>G</span><strong>DG Afrique</strong></a>
                <nav aria-label="Navigation principale"><a href="#capacites">Capacités</a><a href="#besoins">Besoins</a><a href="#opportunites">Opportunités</a><a href="#zumra">ZUMRA</a><a href="#gamad">Univers GAMAD</a></nav>
            </div>
            <div class="claude-header-actions">
                <span class="visibility-cta" title="Parcours en préparation">Proposer une opportunité</span>
                <details class="apps-menu"><summary aria-label="Ouvrir le lanceur de plateformes"><i></i><i></i><i></i><i></i></summary><div><small>Univers GAMAD</small><a href="/"><b class="sat-blue">D</b><span>DG Afrique <em class="active-pill">Actif</em></span></a><span><b class="sat-green">Z</b><span>ZUMRA <em>En préparation</em></span></span><span><b class="sat-cyan">G</b><span>GamaDrive <em>À raccorder</em></span></span></div></details>
                <span class="notification-placeholder" aria-label="Notifications bientôt disponibles"><i></i></span>
                <a class="claude-login" href="{{ route('login') }}">Connexion</a>
            </div>
        </header>

        <main>
            <section class="claude-hero doctrine-hero">
                <div class="cover-placeholder"><span>visuel de couverture administrable</span></div>
                <div class="claude-hero-inner">
                    <p class="hero-eyebrow">LE PORTAIL DES CAPACITÉS AFRICAINES</p>
                    <h1>Ce que vous savez faire peut répondre à <em>un besoin réel.</em></h1>
                    <p>DG Afrique révèle les capacités, rassemble les besoins et ouvre des opportunités. ZUMRA transforme les connexions utiles en actions collectives.</p>
                    <nav class="search-tabs" aria-label="Catégories de découverte"><a class="active" href="#capacites">Capacités</a><a href="#besoins">Besoins</a><a href="#opportunites">Opportunités</a><a href="#zumra">ZUMRA</a></nav>
                    <div class="claude-search" role="search"><div><small>JE RECHERCHE</small><span>Une capacité, un besoin, une opportunité…</span></div><div><small>OÙ ?</small><span>Pays, ville ou à distance</span></div><a href="#passer-action">Explorer</a></div>
                    <p class="search-honesty">La recherche publique sera activée avec l’index de données réelles. Les parcours accessibles sont présentés ci-dessous.</p>
                </div>
            </section>

            <div class="claude-main-flow">
                <section class="claude-section doctrine-intro" id="passer-action">
                    <div class="claude-heading"><span class="section-label">UN PORTAIL, TROIS MOUVEMENTS</span><h2>De la capacité à <em>l’action</em></h2><p>Chaque parcours part d’une réalité déclarée, puis vérifiée selon son niveau.</p></div>
                    <div class="doctrine-pillars">
                        <article id="capacites"><span>01</span><i class="pillar-capacity"></i><h3>Révéler les capacités</h3><p>Présentez ce que vous savez faire, transmettre ou souhaitez apprendre.</p><a href="{{ route('register') }}">Construire mon profil →</a></article>
                        <article id="besoins"><span>02</span><i class="pillar-need"></i><h3>Exprimer les besoins</h3><p>Décrivez un obstacle, une compétence recherchée ou un projet à renforcer.</p><em>Parcours en préparation</em></article>
                        <article id="opportunites"><span>03</span><i class="pillar-opportunity"></i><h3>Ouvrir des opportunités</h3><p>Missions, apprentissages, collaborations et financements pourront trouver leurs destinataires.</p><em>Index en préparation</em></article>
                    </div>
                </section>

                <section class="claude-zumra doctrine-zumra" id="zumra">
                    <div class="zumra-inner">
                        <div class="zumra-text"><p class="section-label light">LE MOTEUR D’ACTION COMMUNAUTAIRE</p><h2>Une capacité isolée peut aider.<br><em>Une ZUMRA peut bâtir.</em></h2><p>ZUMRA réunit des personnes autour d’un objectif, organise la transmission des connaissances et transforme une intention en équipe, puis en projet réel.</p><div class="zumra-states"><div><strong>Apprendre</strong><small>par la transmission</small></div><div><strong>Former</strong><small>une équipe gouvernée</small></div><div><strong>Construire</strong><small>un projet réel</small></div><div><strong>Contribuer</strong><small>librement à l’écosystème</small></div></div><div class="zumra-join"><div class="empty-avatars"><i></i><i></i><i></i><i></i><i></i></div><p>Le Programme ZUMRA est distinct du compte gratuit DG Afrique.</p><span>Adhésion unique · 1 $ / 500 FCFA</span></div></div>
                        <div class="zumra-journey" aria-label="Parcours d’une ZUMRA"><div><b>1</b><span><strong>Une intention</strong><small>un objectif utile et conforme aux valeurs</small></span></div><i></i><div><b>5</b><span><strong>Une gouvernance</strong><small>cinq responsabilités pour être validée</small></span></div><i></i><div><b>50+</b><span><strong>Une ZUMRA établie</strong><small>sans limite de membres ni de territoire</small></span></div></div>
                    </div>
                </section>

                <section class="claude-section action-paths">
                    <div class="claude-heading"><span class="section-label">CHOISIR SON POINT DE DÉPART</span><h2>Que voulez-vous <em>faire aujourd’hui ?</em></h2><p>DG Afrique vous conduit vers la prochaine action réellement disponible.</p></div>
                    <div class="claude-needs-grid">
                        <article><i></i><h3>Présenter mes capacités</h3><p>Créez votre compte puis renseignez progressivement ce que vous savez faire.</p><a href="{{ route('register') }}">Créer mon espace →</a></article>
                        <article><i></i><h3>Trouver une capacité</h3><p>L’annuaire ouvrira avec des profils consentis et des informations réelles.</p><span class="coming-link">Annuaire · en préparation</span></article>
                        <article class="featured"><b>Moteur social</b><i></i><h3>Découvrir ZUMRA</h3><p>Entrez dans l’espace qui reliera membres, équipes, apprentissages et projets.</p><a href="{{ route('login', ['next' => route('zumra.index', absolute: false)]) }}">Voir ZUMRA →</a></article>
                        <article><i></i><h3>Exprimer un besoin</h3><p>Le parcours permettra de demander une compétence, un accompagnement ou une ressource.</p><span class="coming-link">Demandes · à venir</span></article>
                        <article><i></i><h3>Proposer une opportunité</h3><p>Les organisations pourront publier des occasions vérifiables de travailler, apprendre ou collaborer.</p><span class="coming-link">Opportunités · à venir</span></article>
                        <article><i></i><h3>Lancer un projet collectif</h3><p>Les adhérents pourront proposer une ZUMRA et constituer sa gouvernance.</p><span class="coming-link">Création ZUMRA · prochain gate</span></article>
                    </div>
                </section>

                <section class="claude-section trends-section">
                    <div class="claude-heading"><span class="section-label">PREUVES ET MOUVEMENTS RÉELS</span><h2>Ce qui prendra vie sur <em class="green">DG Afrique</em></h2><p>Aucun chiffre, profil ou projet n’est inventé avant son raccordement.</p></div>
                    <div class="trends-grid"><aside><h3>Univers GAMAD</h3>@foreach([['D','DG Afrique','Actif','blue'],['Z','ZUMRA','En préparation','green'],['G','GamaDrive','À raccorder','cyan'],['W','Wasplex','À raccorder','amber'],['G','G‑POS','À raccorder','green']] as $sat)<div class="sat-row"><b class="sat-{{ $sat[3] }}">{{ $sat[0] }}</b><span>{{ $sat[1] }}</span><em>{{ $sat[2] }}</em></div>@endforeach</aside><div class="feed-empty"><h3><b>Z</b> Actions récentes de ZUMRA</h3><article><div class="empty-author"><i></i><span></span></div><p>Les apprentissages, besoins, activités et projets apparaîtront ici à partir de publications réelles.</p><div class="media-placeholder">preuve ou média réel</div><small>Fil en préparation</small></article></div><aside class="people-empty"><h3>Capacités recherchées</h3>@for($i=0;$i<5;$i++)<div><i></i><span></span></div>@endfor<p>Les tendances seront calculées à partir de besoins réels.</p></aside></div>
                </section>

                <section class="claude-section gamad-manifesto" id="gamad"><div><span class="section-label">LA MARQUE QUI RELIE L’ÉCOSYSTÈME</span><h2>GAMAD rend la confiance possible.<br>DG Afrique la rend <em>visible.</em></h2><p>GAMAD Core protège les identités et les contrats dans le monde invisible. DG Afrique présente les capacités et les opportunités. Les produits et projets approuvés portent leur relation à la marque avec clarté.</p></div><div class="gamad-layers"><span><b>GAMAD Core</b><small>Confiance et identité</small></span><i></i><span><b>DG Afrique</b><small>Portail visible</small></span><i></i><span><b>ZUMRA</b><small>Action collective</small></span></div></section>

                <section class="claude-section platforms-section" id="plateformes"><p>DÉCOUVREZ PROGRESSIVEMENT L’UNIVERS GAMAD</p><div><span><i class="dot-blue"></i>DG Afrique</span><span><i class="dot-green"></i>ZUMRA · en préparation</span><span class="muted"><i class="dot-cyan"></i>GamaDrive · à raccorder</span><span class="muted"><i class="dot-amber"></i>Wasplex · à raccorder</span><span class="muted"><i class="dot-blue"></i>G‑Market · à raccorder</span><span class="muted"><i class="dot-green"></i>G‑POS · à raccorder</span></div></section>
            </div>
        </main>

        <footer class="claude-footer"><div class="footer-grid"><div><h3>DG Afrique</h3><p>Le portail des capacités, des besoins et des opportunités. Propulsé par l’écosystème GAMAD.</p><div class="social-placeholders"><span>f</span><span>in</span><span>ig</span><span>yt</span></div></div><div><h3>Explorer</h3><span>Capacités · bientôt</span><span>Besoins · bientôt</span><span>Opportunités · bientôt</span><span>ZUMRA · en préparation</span></div><div><h3>Univers GAMAD</h3><span>GamaDrive</span><span>Wasplex</span><span>G‑Market</span><span>G‑POS</span></div><div><h3>Confiance</h3><p>Les données visibles sont publiées selon leur niveau de validation et de consentement.</p></div></div><div class="footer-bottom">DG Afrique · Un portail de l’écosystème GAMAD.</div></footer>
    </div>
</x-layouts.portal>
