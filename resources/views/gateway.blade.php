<x-layouts.public title="Réseau social d’action et projets collectifs" description="Des savoir-faire à partager, des besoins à faire avancer, des personnes avec qui agir. Découvrez DG Afrique, le réseau social d’action." :editorial="true" canonical="https://dgafrique.com/">
    <div class="dg-entry">
        <x-dg.public-header />
        <section class="dg-entry-hero" aria-labelledby="entry-title">
            <div class="dg-entry-hero__copy">
                <p class="dg-entry-eyebrow">Réseau social d’action</p>
                <h1 id="entry-title" class="dg-entry-title"><span>De vos idées.</span> <span>À nos actions.</span></h1>
                <p class="dg-entry-lead">Des savoir-faire à partager.<br>Des besoins à faire avancer.<br>Des personnes avec qui agir.</p>
                <div class="dg-entry-actions">
                    <x-dg.button :href="route('register')" variant="primary">Créer mon compte</x-dg.button>
                    <a class="dg-entry-link" href="{{ route('landing') }}">Découvrir le réseau <span aria-hidden="true">→</span></a>
                </div>
                <p class="dg-entry-note">Compte gratuit · L’adhésion à une ZUMRA est distincte.</p>
            </div>
            <div class="dg-entry-hero__art">
                <img src="{{ asset('images/entry/ensemble-1280.webp') }}"
                     srcset="{{ asset('images/entry/ensemble-640.webp') }} 640w, {{ asset('images/entry/ensemble-1280.webp') }} 1280w"
                     sizes="(min-width: 960px) 57vw, 100vw" width="1536" height="1024"
                     fetchpriority="high" decoding="async"
                     alt="Illustration : quatre personnes réunissent leurs idées autour d’un projet commun.">
            </div>
        </section>
        <section class="dg-entry-band" aria-labelledby="contribution-title">
            <h2 id="contribution-title">Chacun peut apporter<br>quelque chose.</h2>
            <ul><li>Un savoir-faire</li><li>Un besoin</li><li>L’envie de participer</li></ul>
        </section>
        <nav class="dg-story-nav" aria-label="Comprendre DG Afrique">
            <a href="#vision">La vision</a><a href="#votre-place">Votre place</a><a href="#comment-agir">Comment agir</a><a href="#zumra">Les ZUMRA</a><a href="#questions">Questions fréquentes</a>
        </nav>

        <section class="dg-story-section dg-story-vision" id="vision" aria-labelledby="vision-title">
            <div><p class="dg-entry-eyebrow">Pourquoi DG Afrique</p><h2 id="vision-title">Les talents existent.<br>Créons les liens<br>pour agir.</h2></div>
            <div class="dg-story-prose">
                <p class="dg-story-intro">Un savoir-faire peut répondre à un besoin. Une rencontre peut donner naissance à une action. Ensemble, ces actions peuvent faire grandir une communauté.</p>
                <p>DG Afrique est un réseau social d’action au service du développement humain et de la collaboration. Sa vocation : relier les personnes, leurs capacités et les besoins concrets pour faire émerger des projets utiles.</p>
                <p>Ancré en Afrique et ouvert au monde, le réseau donne une place à l’apprentissage, au travail collectif et à la transmission. Chaque personne peut commencer par ce qu’elle sait, ce qu’elle cherche ou ce qu’elle souhaite construire.</p>
            </div>
        </section>

        <section class="dg-story-section dg-story-place" id="votre-place" aria-labelledby="place-title">
            <p class="dg-entry-eyebrow">Votre place dans le réseau</p>
            <h2 id="place-title">Tout commence avec<br>ce qui compte pour vous.</h2>
            <div class="dg-story-columns">
                <article><span class="dg-story-number" aria-hidden="true">01</span><h3>Vous avez un savoir-faire.</h3><p>Un métier, une expérience, une connaissance à transmettre. Votre capacité peut devenir le point de départ d’une rencontre utile.</p></article>
                <article><span class="dg-story-number" aria-hidden="true">02</span><h3>Vous voyez un besoin.</h3><p>Une difficulté à résoudre, une ressource qui manque, une idée à préciser. Mettre des mots sur ce besoin aide à comprendre avec qui avancer.</p></article>
                <article><span class="dg-story-number" aria-hidden="true">03</span><h3>Vous voulez participer.</h3><p>Apprendre, donner du temps, rejoindre un effort collectif. Vous pouvez avoir une place avant même de porter votre propre projet.</p></article>
            </div>
        </section>

        <section class="dg-story-section" id="comment-agir" aria-labelledby="steps-title">
            <div class="dg-story-heading"><p class="dg-entry-eyebrow">De l’intention à la réalisation</p><h2 id="steps-title">Se rencontrer.<br>Construire. Faire avancer.</h2><p>Un parcours qui se dessine à partir des personnes et des besoins, sans imposer le même point de départ à chacun.</p></div>
            <ol class="dg-story-steps">
                <li><h3>Exprimer ce qui vous anime</h3><p>Un savoir-faire, un besoin ou une intention : un point de départ compréhensible par les autres.</p></li>
                <li><h3>Réunir les bonnes contributions</h3><p>Identifier les capacités utiles, apprendre à se connaître et préciser ce que chacun souhaite apporter.</p></li>
                <li><h3>Organiser l’action ensemble</h3><p>Donner un cadre au projet, répartir les responsabilités et rendre la prochaine étape claire.</p></li>
                <li><h3>Rendre les avancées visibles</h3><p>Documenter les réalisations, reconnaître les contributions et transmettre ce que l’expérience a permis d’apprendre.</p></li>
            </ol>
        </section>

        <section class="dg-story-zumra" id="zumra" aria-labelledby="zumra-title">
            <div class="dg-story-zumra__art"><img src="{{ asset('images/entry/commencer-1280.webp') }}" srcset="{{ asset('images/entry/commencer-640.webp') }} 640w, {{ asset('images/entry/commencer-1280.webp') }} 1280w" sizes="(min-width: 960px) 48vw, 100vw" width="1536" height="1024" loading="lazy" decoding="async" alt="Illustration : deux personnes construisent un espace de travail commun."></div>
            <div class="dg-story-zumra__copy"><p class="dg-entry-eyebrow">Le collectif prend forme</p><h2 id="zumra-title">Une ZUMRA,<br>pour grandir et<br>agir ensemble.</h2><p>Une ZUMRA est une communauté organisée autour d’un domaine, d’un objectif et d’un projet commun. Elle réunit des personnes qui apprennent, transmettent et travaillent ensemble.</p><p>Le projet porte une action à réaliser. La ZUMRA donne un cadre humain à cette action et à la vie du collectif.</p><p class="dg-story-motto">Formation · Travail · Adoration</p><p class="dg-entry-note">Créer un compte DG Afrique est gratuit. L’adhésion au Programme ZUMRA et l’entrée dans une communauté sont des démarches distinctes, avec leurs propres règles.</p><a class="dg-entry-link" href="#questions">Comprendre avant de rejoindre <span aria-hidden="true">↓</span></a></div>
        </section>

        <section class="dg-story-section dg-story-trust" aria-labelledby="trust-title">
            <p class="dg-entry-eyebrow">Ce qui nous guide</p><h2 id="trust-title">La confiance se construit<br>dans les actes.</h2>
            <div class="dg-story-columns">
                <article><h3>La personne décide.</h3><p>Les outils accompagnent les choix. Les engagements et les décisions qui comptent restent humains.</p></article>
                <article><h3>La visibilité se choisit.</h3><p>La découverte publique se limite aux contenus autorisés. Les espaces et les informations réservés restent soumis à leurs droits d’accès.</p></article>
                <article><h3>Les avancées se prouvent.</h3><p>Le réseau privilégie les contributions, les réalisations et les apprentissages. Il ne classe pas la valeur des personnes par leur popularité.</p></article>
            </div>
        </section>

        <section class="dg-story-section dg-story-faq" id="questions" aria-labelledby="faq-title">
            <div><p class="dg-entry-eyebrow">Avant de commencer</p><h2 id="faq-title">Vos questions.<br>Des réponses simples.</h2></div>
            <div class="dg-story-faq__list">
                <details><summary>Qu’est-ce qu’un réseau social d’action ?</summary><p>C’est un réseau où les échanges servent à apprendre, à répondre à des besoins et à construire ensemble. Sur DG Afrique, la rencontre s’inscrit dans un chemin vers l’action, la collaboration et des réalisations vérifiables.</p></details>
                <details><summary>À qui s’adresse DG Afrique ?</summary><p>Aux personnes qui souhaitent apporter un savoir-faire, apprendre, exprimer un besoin ou participer à une initiative collective. Le réseau est ancré en Afrique et ouvert aux personnes qui souhaitent y contribuer, où qu’elles vivent.</p></details>
                <details><summary>Faut-il déjà avoir un projet ?</summary><p>Non. Un intérêt, une compétence, une question ou l’envie d’apprendre peuvent être un point de départ. La vision de DG Afrique place la personne avant le projet et la structure.</p></details>
                <details><summary>Le compte DG Afrique est-il gratuit ?</summary><p>Oui. La création du compte est gratuite. Elle ne vaut pas adhésion au Programme ZUMRA et n’entraîne pas automatiquement votre admission dans une ZUMRA.</p></details>
                <details><summary>Quelle différence entre une ZUMRA et un projet ?</summary><p>Une ZUMRA est une communauté organisée qui apprend et agit ensemble. Un projet est une action structurée, avec un objectif et des réalisations attendues. Les projets s’inscrivent dans ce cadre collectif tout en pouvant être initiés par une personne.</p></details>
                <details><summary>Que puis-je découvrir aujourd’hui sans compte ?</summary><p>La page <a href="{{ route('landing') }}">Découvrir</a> présente les besoins et les projets réellement partagés publiquement. Si aucun contenu n’est disponible, elle le précise. Les autres parcours du réseau se construisent progressivement.</p></details>
                <details><summary>Mes informations sont-elles toutes publiques ?</summary><p>Non. Créer un compte ne rend pas toutes vos informations publiques. La visibilité des contenus dépend des choix et des règles d’accès associés à chaque espace.</p></details>
            </div>
        </section>

        <section class="dg-story-final" aria-labelledby="start-title"><p class="dg-entry-eyebrow">Le pouvoir d’agir ensemble</p><h2 id="start-title">La prochaine rencontre<br>peut commencer avec vous.</h2><p>Un savoir-faire. Un besoin. L’envie de participer.</p><div class="dg-entry-actions"><x-dg.button :href="route('register')" variant="solar">Créer mon compte</x-dg.button><a class="dg-entry-link" href="{{ route('landing') }}">Découvrir le réseau <span aria-hidden="true">→</span></a></div><p class="dg-entry-note">Compte gratuit · Adhésion ZUMRA distincte.</p></section>
        <x-dg.public-footer />
    </div>
</x-layouts.public>
