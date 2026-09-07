<x-layouts.public title="Découvrir" description="Découvrez les besoins et les projets partagés publiquement sur DG Afrique." :editorial="true" canonical="https://dgafrique.com/decouvrir">
    <div class="dg-entry dg-discovery">
        <x-dg.public-header :discovery="true" />
        <section class="dg-discovery-intro" aria-labelledby="discovery-title">
            <p class="dg-entry-eyebrow">Découvrir sans compte</p>
            <h1 id="discovery-title" class="dg-discovery-title">Les rencontres font<br>avancer les idées.</h1>
            <p class="dg-entry-lead">Découvrez les besoins et les projets partagés publiquement.</p>
        </section>
        @if ($realMoments->isEmpty())
            <section class="dg-discovery-empty" aria-labelledby="empty-title" data-public-empty>
                <img class="dg-discovery-empty__art" src="{{ asset('images/entry/commencer-1280.webp') }}"
                     srcset="{{ asset('images/entry/commencer-640.webp') }} 640w, {{ asset('images/entry/commencer-1280.webp') }} 1280w"
                     sizes="(min-width: 960px) 45vw, 100vw" width="1536" height="1024"
                     decoding="async" alt="Illustration : deux personnes préparent un espace pour construire ensemble.">
                <div class="dg-discovery-empty__copy">
                    <p class="dg-entry-eyebrow">Une première rencontre. Un premier pas.</p>
                    <h2 id="empty-title">Le réseau commence<br>avec vous.</h2>
                    <p>Aucun besoin ou projet public n’est disponible pour le moment. Votre première contribution peut ouvrir la voie.</p>
                    <x-dg.button :href="route('register')" variant="primary">Créer mon compte</x-dg.button>
                    <p class="dg-entry-note">Compte gratuit · L’adhésion à une ZUMRA est distincte.</p>
                </div>
            </section>
        @else
            <section class="dg-public-moments" aria-labelledby="moments-title">
                <div class="dg-public-moments__heading">
                    <h2 id="moments-title">Besoins et projets publics</h2><span>En mouvement</span>
                </div>
                <div class="dg-public-moments__grid">
                    @foreach ($realMoments as $moment)
                        <article class="dg-public-moment">
                            <span class="dg-entry-eyebrow">{{ $moment['type'] }}</span>
                            <h3>{{ $moment['titre'] }}</h3>
                            @if ($moment['lieu'])<p>{{ $moment['lieu'] }}</p>@endif
                            <p class="dg-public-moment__meta">{{ $moment['meta'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
            <section class="dg-entry-band dg-entry-band--join" aria-labelledby="join-title">
                <div><h2 id="join-title">Votre place est dans l’action.</h2><p>Partagez ce que vous savez faire. Avancez avec d’autres.</p></div>
                <x-dg.button :href="route('register')" variant="solar">Créer mon compte</x-dg.button>
            </section>
        @endif
        <section class="dg-entry-how" aria-labelledby="how-title">
            <h2 id="how-title">Comment ça marche ?</h2>
            <ol><li>Partager un savoir-faire</li><li>Rencontrer</li><li>Agir ensemble</li></ol>
            <a class="dg-entry-link" href="{{ route('gateway') }}">Pourquoi DG Afrique ? <span aria-hidden="true">→</span></a>
        </section>
        <x-dg.public-footer />
    </div>
</x-layouts.public>
