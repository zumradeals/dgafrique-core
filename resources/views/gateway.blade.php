<x-layouts.public title="Le pouvoir d’agir ensemble" description="Des savoir-faire à partager, des besoins à faire avancer, des personnes avec qui agir. Découvrez DG Afrique, le réseau social d’action." :editorial="true">
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
        <x-dg.public-footer />
    </div>
</x-layouts.public>
