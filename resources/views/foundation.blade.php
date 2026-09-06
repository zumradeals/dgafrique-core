<x-layouts.public title="Découvrir" description="Découvrez les besoins et les projets partagés publiquement sur DG Afrique." :full-width="true">
    <div class="dg-entry dg-discovery">
        <header class="dg-entry-header">
            <a href="{{ route('gateway') }}" class="dg-entry-brand" aria-label="DG Afrique — accueil"><span>DG</span> Afrique</a>
            <nav aria-label="Navigation publique" class="dg-entry-nav">
                <a href="{{ route('login') }}" class="dg-entry-login">Se connecter</a>
                <x-dg.button :href="route('register')" variant="primary">Créer un compte</x-dg.button>
            </nav>
        </header>
        <section class="dg-discovery-heading">
            <p class="dg-entry-eyebrow">Le pouvoir d’agir ensemble</p>
            <h1>Les rencontres font avancer les idées.</h1>
            <p>Découvrez les besoins et les projets partagés publiquement.</p>
        </section>
        @if ($realMoments->isEmpty())
            <section class="dg-discovery-empty" aria-labelledby="network-title">
                <figure class="dg-discovery-art">
                    <picture>
                        <source srcset="{{ asset('images/entry/construire-ensemble-768.webp') }} 768w, {{ asset('images/entry/construire-ensemble-1536.webp') }} 1536w" sizes="(min-width: 900px) 50vw, 100vw" type="image/webp">
                        <img src="{{ asset('images/entry/construire-ensemble-1536.webp') }}" alt="Illustration de deux personnes construisant ensemble un bac en bois." width="1536" height="1024" fetchpriority="high">
                    </picture>
                    <figcaption class="dg-entry-handwritten">Des idées<br>au service<br>du réel <span aria-hidden="true">—</span></figcaption>
                </figure>
                <div class="dg-discovery-invitation">
                    <h2 id="network-title">Le réseau commence<br>avec vous.</h2>
                    <p>Aucun besoin ou projet public n’est disponible pour le moment.<br>Votre première contribution peut ouvrir la voie.</p>
                    <x-dg.button :href="route('register')" variant="primary">Créer mon compte</x-dg.button>
                    <p class="dg-entry-account">Compte gratuit · Adhésion ZUMRA distincte</p>
                </div>
            </section>
        @else
            <section class="dg-discovery-moments" aria-labelledby="moments-title">
                <h2 id="moments-title">Besoins et projets publics</h2>
                <div class="dg-moments-grid">
                    @foreach ($realMoments as $moment)
                        <article class="dg-moment">
                            <span class="dg-entry-eyebrow">{{ $moment['type'] }}</span>
                            <h3>{{ $moment['titre'] }}</h3>
                            @if ($moment['lieu'])<p>{{ $moment['lieu'] }}</p>@endif
                            <p class="dg-moment-meta">{{ $moment['meta'] }}</p>
                        </article>
                    @endforeach
                </div>
                <div class="dg-discovery-join"><p>Une idée, un savoir-faire, l’envie de participer ?</p><x-dg.button :href="route('register')" variant="primary">Créer mon compte</x-dg.button></div>
            </section>
        @endif
        <section class="dg-entry-how" aria-labelledby="how-title">
            <h2 id="how-title">Comment ça marche ?</h2>
            <ol><li>Partager un savoir-faire</li><li>Rencontrer</li><li>Agir ensemble</li></ol>
            <p>Des rencontres d’aujourd’hui<br>pour des solutions de demain.</p>
        </section>
        <footer class="dg-entry-footer"><a href="{{ route('gateway') }}">Pourquoi DG Afrique ?</a><span>Le pouvoir d’agir ensemble</span></footer>
    </div>
</x-layouts.public>
