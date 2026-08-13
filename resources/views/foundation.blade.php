<x-layouts.portal title="DG Afrique — Votre réseau d’opportunités numériques">
    <div class="claude-portal">
        <header class="claude-header">
            <div class="claude-header-left">
                <a class="claude-brand" href="/" aria-label="Accueil DG Afrique"><span>G</span><strong>DG Afrique</strong></a>
                <nav aria-label="Navigation principale"><a href="#besoins">Entreprises</a><a href="#besoins">Talents</a><span aria-disabled="true">Emploi</span><a href="#plateformes">Services <i></i></a></nav>
            </div>
            <div class="claude-header-actions">
                <span class="visibility-cta" title="Parcours en préparation">Booster ma visibilité</span>
                <details class="apps-menu"><summary aria-label="Ouvrir le lanceur de plateformes"><i></i><i></i><i></i><i></i></summary><div><small>Ouvrir un satellite</small><a href="/"><b class="sat-blue">D</b><span>DG Afrique <em class="active-pill">Actif</em></span></a><span><b class="sat-green">Z</b><span>ZUMRA <em>À venir</em></span></span><span><b class="sat-cyan">G</b><span>GamaDrive <em>À raccorder</em></span></span></div></details>
                <span class="notification-placeholder" aria-label="Notifications bientôt disponibles"><i></i></span>
                <a class="claude-login" href="{{ route('login') }}">Connexion</a>
            </div>
        </header>

        <main>
            <section class="claude-hero">
                <div class="cover-placeholder"><span>photo de couverture</span></div>
                <div class="claude-hero-inner">
                    <h1>Votre réseau <em>d’opportunités numériques</em> en Afrique</h1>
                    <p>Nous connectons particuliers, professionnels et entreprises aux satellites de l’écosystème GAMAD.</p>
                    <nav class="search-tabs" aria-label="Catégories de découverte"><a class="active" href="#besoins">Services</a><a href="#besoins">Professionnels</a><a href="#plateformes">Satellites</a><span>Emploi</span></nav>
                    <div class="claude-search" role="search"><div><small>QUOI, QUI ?</small><span>Nom d’un service, d’un satellite, d’une offre…</span></div><div><small>OÙ ?</small><span>Pays, ville…</span></div><a href="#besoins">Rechercher</a></div>
                    <p class="search-honesty">La recherche unifiée ouvrira lorsqu’un index réel sera disponible. Utilisez les raccourcis ci-dessous.</p>
                </div>
            </section>

            <div class="claude-main-flow">
                <section class="claude-section needs-section" id="besoins">
                    <div class="claude-heading"><h2>Quel est votre besoin <em>numérique</em> ?</h2><p>Nous vous orientons uniquement vers les parcours réellement accessibles.</p></div>
                    <div class="claude-needs-grid">
                        <article><i></i><h3>Trouver un prestataire</h3><p>L’annuaire professionnel sera ouvert avec son référentiel vérifié.</p><span class="coming-link">Annuaire · bientôt</span></article>
                        <article><i></i><h3>Recruter ou trouver une mission</h3><p>Les offres et candidatures seront activées dans un prochain gate.</p><span class="coming-link">Emploi · bientôt</span></article>
                        <article class="featured"><b>Mise en avant</b><i></i><h3>Gagner en visibilité</h3><p>Le Pôle numérique sera raccordé ici dès que son parcours réel sera disponible.</p><span>Voir les offres · bientôt</span></article>
                        <article><i></i><h3>Rejoindre ZUMRA</h3><p>L’adhésion reste distincte du Compte DG Afrique et dépend de règles propres.</p><span class="coming-link">Réseau ZUMRA · à venir</span></article>
                        <article><i></i><h3>Ouvrir un satellite</h3><p>Accédez progressivement aux plateformes GAMAD effectivement raccordées.</p><a href="#plateformes">Tous les outils →</a></article>
                        <article><i></i><h3>Créer mon espace</h3><p>Ouvrez gratuitement votre Compte DG Afrique gouverné par GAMAD Core.</p><a href="{{ route('register') }}">Créer mon compte →</a></article>
                    </div>
                </section>

                <section class="claude-zumra">
                    <div class="zumra-inner">
                        <div class="zumra-text"><h2>Nous ne créons pas seulement des connexions.<br><em>Nous bâtissons des opportunités, avec ZUMRA.</em></h2><p>ZUMRA est le réseau social d’action de l’écosystème GAMAD. Son adhésion et sa contribution mensuelle obéissent à leurs propres règles.</p><div class="zumra-states"><div><strong>Distinct</strong><small>du compte gratuit</small></div><div><strong>Conditionné</strong><small>par une adhésion réelle</small></div><div><strong>Attesté</strong><small>sans données inventées</small></div><div><strong>À venir</strong><small>après validation du gate</small></div></div><div class="zumra-join"><div class="empty-avatars"><i></i><i></i><i></i><i></i></div><p>Le réseau prendra vie ici avec de vrais membres et de vrais projets.</p><span>Rejoindre ZUMRA · bientôt</span></div></div>
                        <div class="zumra-collage" aria-label="Emplacements destinés aux portraits réels"><div>portrait</div><div>portrait</div></div>
                    </div>
                </section>

                <section class="claude-section trust-section"><div class="claude-heading"><h2>Ils nous font <em class="green">confiance</em></h2><p>Les partenaires seront publiés ici après validation.</p></div><div class="trust-grid">@for($i=1;$i<=5;$i++)<div>Emplacement partenaire</div>@endfor</div><span class="visibility-cta">Plus de visibilité digitale ? Bientôt disponible</span></section>

                <section class="claude-section trends-section">
                    <div class="claude-heading"><h2>Ça bouge sur <em class="green">ZUMRA</em></h2><p>Le fil apparaîtra ici lorsque les capacités sociales et leurs données réelles seront ouvertes.</p></div>
                    <div class="trends-grid"><aside><h3>Satellites de l’écosystème</h3>@foreach([['D','DG Afrique','Actif','blue'],['Z','ZUMRA','À venir','green'],['G','GamaDrive','À raccorder','cyan'],['W','Wasplex','À raccorder','amber'],['G','G‑POS','À raccorder','green']] as $sat)<div class="sat-row"><b class="sat-{{ $sat[3] }}">{{ $sat[0] }}</b><span>{{ $sat[1] }}</span><em>{{ $sat[2] }}</em></div>@endforeach</aside><div class="feed-empty"><h3><b>Z</b> Fil ZUMRA — publications tendances</h3><article><div class="empty-author"><i></i><span></span></div><p>Aucune publication n’est affichée avant l’ouverture du réseau social réel.</p><div class="media-placeholder">image publication</div><small>Fil indisponible pour le moment</small></article></div><aside class="people-empty"><h3>Professionnels les plus suivis</h3>@for($i=0;$i<5;$i++)<div><i></i><span></span></div>@endfor<p>Les profils apparaîtront à partir de données attestées.</p></aside></div>
                </section>

                <section class="claude-section articles-section"><div class="articles-title"><h2>Nos derniers articles</h2><span>Contenus bientôt raccordés</span></div><div class="articles-grid">@for($i=0;$i<4;$i++)<article><div>image article</div><h3>Article à publier depuis l’espace de contenu</h3><p>Aucun contenu fictif n’est présenté comme une actualité.</p></article>@endfor</div></section>

                <section class="claude-section platforms-section" id="plateformes"><p>DÉCOUVREZ AUSSI NOS PLATEFORMES</p><div><span><i class="dot-blue"></i>DG Afrique</span><span><i class="dot-green"></i>ZUMRA · bientôt</span><span class="muted"><i class="dot-cyan"></i>GamaDrive · bientôt</span><span class="muted"><i class="dot-amber"></i>Wasplex · bientôt</span><span class="muted"><i class="dot-blue"></i>G‑Market · bientôt</span><span class="muted"><i class="dot-green"></i>G‑POS · bientôt</span></div></section>
            </div>
        </main>

        <footer class="claude-footer"><div class="footer-grid"><div><h3>DG Afrique</h3><p>Le portail humain de l’écosystème GAMAD, pensé pour les réalités africaines.</p><div class="social-placeholders"><span>f</span><span>in</span><span>ig</span><span>yt</span></div></div><div><h3>Écosystème GAMAD</h3><span>ZUMRA</span><span>GamaDrive</span><span>Wasplex</span><span>G‑Market</span></div><div><h3>Infos &amp; liens</h3><span>Qui sommes-nous ? · bientôt</span><span>FAQ · bientôt</span><span>Confidentialité · bientôt</span><span>Contact · bientôt</span></div><div><h3>Présence</h3><p>Les pays seront publiés lorsque les implantations seront attestées.</p></div></div><div class="footer-bottom">Un portail de l’écosystème GAMAD.</div></footer>
    </div>
</x-layouts.portal>
