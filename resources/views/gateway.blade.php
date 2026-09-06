<x-layouts.public title="Bienvenue" description="Des savoir-faire à partager. Des besoins à faire avancer. Des personnes avec qui agir." :full-width="true">
    <div class="dg-entry">
        <header class="dg-entry-header">
            <a href="{{ route('gateway') }}" class="dg-entry-brand" aria-label="DG Afrique — accueil"><span>DG</span> Afrique</a>
            <nav aria-label="Navigation publique" class="dg-entry-nav">
                <a href="{{ route('landing') }}" class="dg-entry-discover-link">Découvrir</a>
                <a href="{{ route('login') }}" class="dg-entry-login">Se connecter</a>
            </nav>
        </header>
        <section class="dg-entry-hero" aria-labelledby="entry-title">
            <div class="dg-entry-copy">
                <p class="dg-entry-eyebrow">Le Réseau social d’action</p>
                <h1 id="entry-title">De vos idées.<br>À nos actions.</h1>
                <p class="dg-entry-intro">Des savoir-faire à partager. Des besoins à faire avancer.<br class="dg-wide-break"> Des personnes avec qui agir.</p>
                <div class="dg-entry-actions">
                    <x-dg.button :href="route('register')" variant="primary">Créer mon compte</x-dg.button>
                    <a href="{{ route('landing') }}" class="dg-entry-text-link">Découvrir le réseau <span aria-hidden="true">→</span></a>
                </div>
                <p class="dg-entry-account" aria-label="Compte gratuit, distinct de toute adhésion à une ZUMRA">Compte gratuit · Adhésion ZUMRA distincte</p>
            </div>
            <figure class="dg-entry-art">
                <picture>
                    <source srcset="{{ asset('images/entry/agir-ensemble-768.webp') }} 768w, {{ asset('images/entry/agir-ensemble-1536.webp') }} 1536w" sizes="(min-width: 900px) 58vw, 100vw" type="image/webp">
                    <img src="{{ asset('images/entry/agir-ensemble-1536.webp') }}" alt="Illustration de quatre personnes réunissant leurs savoir-faire autour d’un projet commun." width="1536" height="1024" fetchpriority="high">
                </picture>
                <figcaption class="dg-entry-handwritten">Plus loin<br>ensemble <span aria-hidden="true">—</span></figcaption>
            </figure>
        </section>
        <section class="dg-entry-contribution" aria-labelledby="contribution-title">
            <h2 id="contribution-title">Chacun peut apporter quelque chose.</h2>
            <ul><li>Un savoir-faire</li><li>Un besoin</li><li>L’envie de participer</li></ul>
        </section>
        <footer class="dg-entry-footer"><span>DG Afrique · Le pouvoir d’agir ensemble</span><a href="{{ route('landing') }}">Faisons connaissance <span aria-hidden="true">↗</span></a></footer>
    </div>
</x-layouts.public>
