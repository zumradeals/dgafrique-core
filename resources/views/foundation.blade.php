<x-layouts.portal title="DG Afrique — Le portail qui révèle les capacités africaines">
    <div class="public-page">
        <header class="public-header">
            <a class="brand" href="/" aria-label="Accueil DG Afrique"><span class="brand-mark">DG</span><span>DG Afrique</span></a>
            <nav class="public-nav" aria-label="Navigation principale">
                <a href="#possibilites">Découvrir</a><a href="#zumra">ZUMRA</a><a href="#ecosysteme">Écosystème</a>
            </nav>
            <div class="public-actions"><a class="ghost-link" href="{{ route('login') }}">Se connecter</a><a class="header-cta" href="{{ route('register') }}">Créer mon espace <span>→</span></a></div>
        </header>

        <main>
            <section class="public-hero">
                <div class="hero-copy">
                    <p class="hero-badge"><span></span> Un portail africain, humain et utile</p>
                    <h1>Vos capacités méritent un espace pour <em>grandir.</em></h1>
                    <p class="hero-lead">DG Afrique relie votre identité, vos savoir-faire et vos projets à un écosystème numérique pensé pour les réalités africaines.</p>
                    <div class="hero-actions"><a class="hero-primary" href="{{ route('register') }}">Créer gratuitement mon espace <span>→</span></a><a class="hero-secondary" href="#possibilites">Voir les possibilités</a></div>
                    <div class="hero-trust"><span>✓ Compte personnel gratuit</span><span>✓ Identité protégée par GAMAD</span><span>✓ ZUMRA reste une démarche distincte</span></div>
                </div>
                <div class="hero-visual" aria-label="Aperçu du portail DG Afrique">
                    <div class="africa-orbit orbit-one"></div><div class="africa-orbit orbit-two"></div>
                    <div class="hero-dashboard">
                        <div class="mini-top"><span class="mini-logo">DG</span><span class="mini-search"></span><span class="mini-avatar">K</span></div>
                        <div class="mini-welcome"><small>MON ESPACE</small><strong>Bonjour, votre avenir commence ici.</strong><span>Présentez ce que vous savez faire et ce que vous souhaitez apprendre.</span></div>
                        <div class="mini-grid"><article class="mini-dark"><small>IDENTITÉ</small><b>Reconnue</b><i>GAMAD Core</i></article><article><small>PROFIL</small><b>À votre rythme</b><i>Sans score personnel</i></article></div>
                        <div class="mini-step"><span class="step-icon">↗</span><div><small>PROCHAINE ACTION</small><b>Présenter mes capacités</b></div><i>→</i></div>
                    </div>
                    <div class="floating-note note-capacity"><span>✦</span><div><b>Vos savoir-faire comptent</b><small>Formels ou informels</small></div></div>
                    <div class="floating-note note-africa"><span>●</span><div><b>African First</b><small>Conçu depuis nos réalités</small></div></div>
                </div>
            </section>

            <section class="possibilities" id="possibilites">
                <div class="section-heading"><div><p class="section-kicker">Commencer simplement</p><h2>De quoi avez-vous besoin aujourd’hui ?</h2></div><p>Une porte d’entrée claire vers les services disponibles, sans promesse artificielle.</p></div>
                <div class="need-grid">
                    <a class="need-card need-blue" href="{{ route('register') }}"><span class="need-icon">◎</span><small>MON IDENTITÉ</small><h3>Créer mon espace</h3><p>Obtenez votre Compte DG Afrique et retrouvez une identité unique dans l’écosystème GAMAD.</p><b>Commencer maintenant →</b></a>
                    <a class="need-card need-cyan" href="{{ route('login') }}"><span class="need-icon">✦</span><small>MES CAPACITÉS</small><h3>Présenter mon profil</h3><p>Décrivez vos pratiques, vos objectifs d’apprentissage et ce que vous souhaitez accomplir.</p><b>Accéder à Mon espace →</b></a>
                    <article class="need-card need-amber"><span class="need-icon">↗</span><small>MES PROJETS</small><h3>Faire avancer une idée</h3><p>Les parcours d’accompagnement seront ouverts progressivement, avec des états clairs et réels.</p><b class="soon-label">En préparation</b></article>
                    <article class="need-card need-mint"><span class="need-icon">⌁</span><small>ÉCOSYSTÈME</small><h3>Utiliser les satellites</h3><p>Les services GAMAD validés apparaîtront ici au fur et à mesure de leur raccordement.</p><b class="soon-label">Raccordement progressif</b></article>
                </div>
            </section>

            <section class="zumra-feature" id="zumra">
                <div class="zumra-copy"><p class="zumra-label"><span>Z</span> PROGRAMME ZUMRA</p><h2>Seul on avance.<br><em>Ensemble, on bâtit.</em></h2><p>ZUMRA est le programme communautaire de DG Afrique. Le rejoindre est une démarche distincte du compte gratuit, soumise à ses propres conditions et engagements.</p><div class="zumra-points"><span><i>01</i> Une communauté structurée</span><span><i>02</i> Une contribution selon les règles du programme</span><span><i>03</i> Des projets attestés, jamais simulés</span></div><span class="zumra-wait">Ouverture dans un prochain gate validé</span></div>
                <div class="zumra-symbol" aria-hidden="true"><div class="z-ring ring-a"></div><div class="z-ring ring-b"></div><div class="z-core">Z</div><span class="z-dot dot-a"></span><span class="z-dot dot-b"></span><span class="z-dot dot-c"></span></div>
            </section>

            <section class="ecosystem" id="ecosysteme">
                <div><p class="section-kicker">Un compte, un écosystème</p><h2>Votre identité vous accompagne.</h2><p>DG Afrique devient votre porte d’entrée humaine vers les services GAMAD réellement disponibles.</p></div>
                <div class="ecosystem-list"><article><span>DG</span><div><b>DG Afrique</b><small>Portail principal</small></div><i>Disponible</i></article><article><span>G</span><div><b>GAMAD Core</b><small>Identité canonique</small></div><i>Connecté</i></article><article class="muted-service"><span>+</span><div><b>Autres satellites</b><small>Selon leur raccordement réel</small></div><i>Progressif</i></article></div>
            </section>

            <section class="public-cta"><div><p>Votre prochaine étape peut commencer maintenant.</p><h2>Créez l’espace qui grandira avec vous.</h2></div><a href="{{ route('register') }}">Créer mon Compte DG Afrique <span>→</span></a></section>
        </main>

        <footer class="public-footer"><div class="footer-brand"><a class="brand" href="/"><span class="brand-mark">DG</span><span>DG Afrique</span></a><p>Le portail numérique African‑first de l’écosystème GAMAD.</p></div><div><b>Accès</b><a href="{{ route('login') }}">Connexion</a><a href="{{ route('register') }}">Créer un compte</a></div><div><b>Programme</b><a href="#zumra">Découvrir ZUMRA</a><span>Conditions à venir</span></div><div><b>Confiance</b><span>Identité GAMAD Core</span><span>Données DG Afrique</span></div><small>© {{ date('Y') }} DG Afrique · African First</small></footer>
    </div>
</x-layouts.portal>
