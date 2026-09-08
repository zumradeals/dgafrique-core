<x-layouts.public title="Réseau social d’action et projets collectifs" description="Vous avez un savoir-faire, un besoin ou l’envie de participer. GAMAD vous relie aux bonnes personnes pour passer à l’action." :editorial="true" canonical="https://dgafrique.com/">
    <div class="dg-entry">
        <x-dg.public-header />
        <section class="dg-entry-hero" aria-labelledby="entry-title">
            <div class="dg-entry-hero__copy">
                <p class="dg-entry-eyebrow">Réseau social d’action</p>
                <h1 id="entry-title" class="dg-entry-title"><span>De vos idées.</span> <span>À nos actions.</span></h1>
                <p class="dg-entry-lead">Vous avez un savoir-faire, un besoin ou l’envie de participer. GAMAD vous relie aux bonnes personnes pour passer à l’action.</p>
                <div class="dg-entry-actions">
                    <x-dg.button :href="route('register')" variant="primary">Créer mon compte</x-dg.button>
                    <a class="dg-entry-link" href="#comment-agir">Voir comment ça marche <span aria-hidden="true">↓</span></a>
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
            <ul>
                <li><a class="flex min-h-12 flex-col justify-center text-white no-underline hover:underline" href="{{ route('register') }}"><strong>J’ai un savoir-faire</strong><span class="text-sm font-normal">Je peux aider ou transmettre.</span></a></li>
                <li><a class="flex min-h-12 flex-col justify-center text-white no-underline hover:underline" href="{{ route('register') }}"><strong>J’ai un besoin</strong><span class="text-sm font-normal">Je cherche un appui ou une ressource.</span></a></li>
                <li><a class="flex min-h-12 flex-col justify-center text-white no-underline hover:underline" href="{{ route('register') }}"><strong>Je veux participer</strong><span class="text-sm font-normal">Je souhaite rejoindre une action.</span></a></li>
            </ul>
        </section>

        <nav class="dg-story-nav" aria-label="Comprendre GAMAD">
            <a href="#vision">Pourquoi GAMAD</a><a href="#votre-place">Votre point de départ</a><a href="#comment-agir">Comment ça marche</a><a href="#zumra">La ZUMRA</a><a href="#questions">Questions</a>
        </nav>

        <section class="dg-story-section dg-story-vision" id="vision" aria-labelledby="vision-title">
            <div><p class="dg-entry-eyebrow">Pourquoi GAMAD</p><h2 id="vision-title">Les talents existent.<br>Créons les liens<br>pour agir.</h2></div>
            <div class="dg-story-prose">
                <p class="dg-story-intro">Un savoir-faire peut répondre à un besoin. Une rencontre peut devenir une action utile.</p>
                <p>GAMAD est un réseau social d’action : il relie les personnes, leurs capacités et les besoins concrets pour faire avancer des actions et des projets ensemble.</p>
                <p>Ancré en Afrique et ouvert au monde, GAMAD permet de commencer simplement par ce que l’on sait faire, ce que l’on cherche ou ce à quoi l’on souhaite contribuer.</p>
            </div>
        </section>

        <section class="dg-story-section dg-story-place" id="votre-place" aria-labelledby="place-title">
            <p class="dg-entry-eyebrow">Votre point de départ</p>
            <h2 id="place-title">Vous n’avez pas besoin<br>d’avoir déjà un projet.</h2>
            <p class="dg-story-intro">Venez avec ce que vous avez aujourd’hui : une compétence, un besoin, une idée ou simplement l’envie d’aider. GAMAD vous accompagne à partir de là.</p>
            <div class="dg-story-columns">
                <article><span class="dg-story-number" aria-hidden="true">01</span><h3>Je peux apporter quelque chose.</h3><p>Un métier, une expérience, une connaissance ou du temps à partager.</p></article>
                <article><span class="dg-story-number" aria-hidden="true">02</span><h3>J’ai besoin de quelque chose.</h3><p>Une compétence, une ressource, un appui ou une difficulté à faire avancer.</p></article>
                <article><span class="dg-story-number" aria-hidden="true">03</span><h3>Je veux prendre part à quelque chose.</h3><p>Apprendre, contribuer ou rejoindre une action qui existe déjà.</p></article>
            </div>
        </section>

        <section class="dg-story-section" id="comment-agir" aria-labelledby="steps-title">
            <div class="dg-story-heading"><p class="dg-entry-eyebrow">De l’intention à la réalisation</p><h2 id="steps-title">Se rencontrer.<br>Construire. Faire avancer.</h2><p>GAMAD cache la complexité et rend chaque prochaine étape compréhensible.</p></div>
            <ol class="dg-story-steps">
                <li><h3>Dites ce qui compte pour vous</h3><p>Un savoir-faire, un besoin, une idée ou l’envie de participer.</p></li>
                <li><h3>Rencontrez les bonnes personnes</h3><p>GAMAD rapproche les besoins, les capacités et les intentions qui peuvent avancer ensemble.</p></li>
                <li><h3>Passez à l’action ensemble</h3><p>Mission, projet, entraide ou initiative collective : chacun sait ce qu’il peut faire.</p></li>
                <li><h3>Montrez ce qui a été réalisé</h3><p>Les contributions, les apprentissages et les réalisations deviennent des preuves de progression.</p></li>
            </ol>
        </section>

        <section class="dg-story-zumra" id="zumra" aria-labelledby="zumra-title">
            <div class="dg-story-zumra__art"><img src="{{ asset('images/entry/commencer-1280.webp') }}" srcset="{{ asset('images/entry/commencer-640.webp') }} 640w, {{ asset('images/entry/commencer-1280.webp') }} 1280w" sizes="(min-width: 960px) 48vw, 100vw" width="1536" height="1024" loading="lazy" decoding="async" alt="Illustration : deux personnes construisent un espace de travail commun."></div>
            <div class="dg-story-zumra__copy"><p class="dg-entry-eyebrow">Pour aller plus loin ensemble</p><h2 id="zumra-title">Une ZUMRA,<br>pour grandir et<br>agir ensemble.</h2><p>Une ZUMRA est une communauté organisée autour d’un domaine, d’un territoire ou d’un objectif commun. Elle réunit des personnes qui apprennent, transmettent et agissent ensemble.</p><p><strong>Vous pouvez utiliser GAMAD sans appartenir à une ZUMRA.</strong> Le compte GAMAD et l’adhésion au Programme ZUMRA restent deux démarches distinctes.</p><p class="dg-story-motto">Formation · Travail · Adoration</p><a class="dg-entry-link" href="#questions">Comprendre la différence <span aria-hidden="true">↓</span></a></div>
        </section>

        <section class="dg-story-section dg-story-trust" aria-labelledby="trust-title">
            <p class="dg-entry-eyebrow">Ce qui nous guide</p><h2 id="trust-title">La confiance se construit<br>dans les actes.</h2>
            <div class="dg-story-columns">
                <article><h3>La personne décide.</h3><p>Vous choisissez ce que vous partagez et avec qui vous agissez.</p></article>
                <article><h3>La visibilité se choisit.</h3><p>Tout n’a pas vocation à être public. Les informations restent soumises à vos choix et aux droits d’accès.</p></article>
                <article><h3>Les réalisations comptent.</h3><p>Ici, la valeur ne se mesure pas en likes. Elle se construit par les contributions, les apprentissages et ce qui est réellement réalisé.</p></article>
            </div>
        </section>

        <section class="dg-story-section dg-story-faq" id="questions" aria-labelledby="faq-title">
            <div><p class="dg-entry-eyebrow">Avant de commencer</p><h2 id="faq-title">Vos questions.<br>Des réponses simples.</h2></div>
            <div class="dg-story-faq__list">
                <details><summary>Qu’est-ce qu’un réseau social d’action ?</summary><p>C’est un réseau où la rencontre sert à apprendre, répondre à un besoin, contribuer et construire quelque chose de réel avec d’autres personnes.</p></details>
                <details><summary>Faut-il déjà avoir un projet ?</summary><p>Non. Une compétence, un besoin, une idée ou l’envie de participer suffisent pour commencer. La personne vient avant le projet et la structure.</p></details>
                <details><summary>Le compte GAMAD est-il gratuit ?</summary><p>Oui. La création du compte GAMAD est gratuite.</p></details>
                <details><summary>Dois-je appartenir à une ZUMRA ?</summary><p>Non. Vous pouvez utiliser GAMAD sans appartenir à une ZUMRA. L’adhésion au Programme ZUMRA est une démarche distincte avec ses propres règles.</p></details>
            </div>
        </section>

        <section class="dg-story-final" aria-labelledby="start-title"><p class="dg-entry-eyebrow">Le pouvoir d’agir ensemble</p><h2 id="start-title">La prochaine rencontre<br>peut commencer avec vous.</h2><p>Un savoir-faire. Un besoin. Une envie de participer. Il suffit d’un point de départ.</p><div class="dg-entry-actions"><x-dg.button :href="route('register')" variant="solar">Créer mon compte</x-dg.button><a class="dg-entry-link" href="{{ route('login') }}">J’ai déjà un compte <span aria-hidden="true">→</span></a></div><p class="dg-entry-note">Compte gratuit · Adhésion ZUMRA distincte.</p></section>
        <x-dg.public-footer />
    </div>
</x-layouts.public>
