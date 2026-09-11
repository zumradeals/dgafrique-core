<x-layouts.member title="Personnes" active="people" :wide="true">
    @php
        $modeQuery = request('mode');
        $countryQuery = request('country');
        $availabilityQuery = request('availability');
        $recentQuery = request('recent');
        $selfName = $selfProfile?->discovery_display_name ?: 'Votre présence GAMAD';
        $selfInitial = mb_strtoupper(mb_substr($selfName, 0, 1));
        $selfCapabilities = $selfProfile?->capabilityStatements?->take(5) ?? collect();
        $selfIsPublic = (bool) ($selfProfile?->orientation_consent && $selfProfile?->discovery_consent);
        $suggestions = array_slice($recommendations ?? [], 0, 5);
    @endphp

    <div class="people3-shell">
        <aside class="people3-left" aria-label="Navigation du Carrefour Personnes">
            <a class="people3-left__active" href="{{ route('people.index') }}">Carrefour Personnes</a>
            <a href="#personnes-decouvrir">Découvrir</a>
            <a href="#ma-presence">Ma présence</a>
            <a href="#capacites">Capacités</a>
            <a href="#territoires">Territoires</a>
            <a href="{{ route('people.index', ['recent' => 1]) }}#personnes-decouvrir">Nouveaux profils</a>
            <span class="people3-left__label">Explorer par</span>
            @foreach ($popularCapabilities->take(5) as $capability)
                <a href="{{ route('people.index', ['q' => $capability->label]) }}#personnes-decouvrir">{{ $capability->label }}</a>
            @endforeach
            <div class="people3-left__help">
                <strong>Besoin des bonnes personnes ?</strong>
                <p>Affinez votre présence pour recevoir de meilleurs rapprochements.</p>
                <a href="{{ url('/espace/profil') }}">Compléter mon profil</a>
            </div>
        </aside>

        <main class="people3-main">
            <section class="people3-hero" aria-labelledby="people3-title">
                <div class="people3-hero__copy">
                    <span>CARREFOUR PERSONNES · GAMAD</span>
                    <h1 id="people3-title">Des personnes pour transformer<br><em>vos idées en actions.</em></h1>
                    <p>Découvrez des talents, des expertises et des partenaires engagés pour apprendre, collaborer et agir ensemble.</p>
                    <form method="GET" action="{{ route('people.index') }}" class="people3-search">
                        <input name="q" value="{{ request('q') }}" maxlength="100" placeholder="Rechercher une personne, une capacité, une activité…" aria-label="Rechercher une personne">
                        <button type="submit">Rechercher</button>
                    </form>
                    <div class="people3-chips">
                        <a href="{{ route('people.index') }}#personnes-decouvrir">Toutes</a>
                        <a href="{{ route('people.index', ['availability' => \App\Models\PersonProfile::AVAILABILITY_OPEN]) }}#personnes-decouvrir">Disponibles</a>
                        @foreach ($popularCapabilities->take(6) as $capability)
                            <a href="{{ route('people.index', ['q' => $capability->label]) }}#personnes-decouvrir">{{ $capability->label }}</a>
                        @endforeach
                    </div>
                </div>
                <div class="people3-hero__visual">
                    <img src="{{ asset('images/entry/ensemble-640.webp') }}" srcset="{{ asset('images/entry/ensemble-640.webp') }} 640w, {{ asset('images/entry/ensemble-1280.webp') }} 1280w" sizes="(min-width: 1100px) 36vw, 100vw" alt="Des personnes qui collaborent" width="1280" height="853">
                    <div class="people3-hero__quote">Des talents.<br>Des actions.<br><strong>Un impact réel.</strong></div>
                </div>
            </section>

            <section class="people3-metrics" aria-label="Indicateurs du Carrefour Personnes">
                <article><span>◎</span><div><strong>{{ number_format($metrics['people'], 0, ',', ' ') }}</strong><small>personnes visibles</small></div></article>
                <article><span>◇</span><div><strong>{{ number_format($metrics['capabilities'], 0, ',', ' ') }}</strong><small>capacités partagées</small></div></article>
                <article><span>✓</span><div><strong>{{ number_format($metrics['available'], 0, ',', ' ') }}</strong><small>disponibles maintenant</small></div></article>
                <article><span>↗</span><div><strong>{{ number_format($metrics['recent'], 0, ',', ' ') }}</strong><small>nouveaux profils</small></div></article>
            </section>

            <section id="ma-presence" class="people3-self">
                <div class="people3-section-title"><div><h2>Ma présence dans GAMAD</h2><p>Votre profil, vos capacités et votre visibilité dans le réseau.</p></div><a href="{{ url('/espace/profil') }}">Voir mon profil →</a></div>
                <div class="people3-self__body">
                    <div class="people3-avatar people3-avatar--self">{{ $selfInitial }}</div>
                    <div class="people3-self__identity">
                        <strong>{{ $selfName }}</strong>
                        <span>{{ $selfProfile?->current_activity ?: 'Membre GAMAD' }}@if($selfProfile?->city) · {{ $selfProfile->city }}@endif</span>
                        <div class="people3-tags">
                            @forelse ($selfCapabilities as $statement)<span>{{ $statement->label }}</span>@empty<span>Capacités à compléter</span>@endforelse
                        </div>
                    </div>
                    <div class="people3-self__stats">
                        <div><strong>{{ $selfCapabilities->count() }}</strong><span>capacités visibles ici</span></div>
                        <div><strong>{{ $selfIsPublic ? 'Oui' : 'Non' }}</strong><span>profil public</span></div>
                    </div>
                </div>
            </section>

            <section id="personnes-decouvrir" class="people3-panel">
                <div class="people3-section-title"><div><h2>Personnes à découvrir</h2><p>Des membres visibles par choix, présentés par ce qu’ils peuvent apporter.</p></div><span>{{ $profiles->total() }} profil{{ $profiles->total() === 1 ? '' : 's' }}</span></div>

                <form class="people3-filterbar" method="GET" action="{{ route('people.index') }}">
                    <input name="q" value="{{ request('q') }}" maxlength="100" placeholder="Capacité ou activité">
                    @if ($settings['country_filter'] ?? true)<input name="country" value="{{ $countryQuery }}" maxlength="2" placeholder="Pays · CI">@endif
                    @if ($settings['mode_filter'] ?? true)
                        <select name="mode"><option value="">Tous les modes</option>@foreach ($modes as $mode)<option value="{{ $mode['value'] }}" @selected($modeQuery === $mode['value'])>{{ $mode['label'] ?? $mode['value'] }}</option>@endforeach</select>
                    @endif
                    <select name="availability"><option value="">Toutes disponibilités</option>@foreach (\App\Models\PersonProfile::AVAILABILITY_LABELS as $value => $label)<option value="{{ $value }}" @selected($availabilityQuery === $value)>{{ $label }}</option>@endforeach</select>
                    @if ($recentQuery === '1')<input type="hidden" name="recent" value="1">@endif
                    <button type="submit">Appliquer</button>
                    <a href="{{ route('people.index') }}#personnes-decouvrir">Effacer</a>
                </form>

                <div class="people3-cards">
                    @forelse ($profiles as $person)
                        @php($visibleCapabilities = $person->capabilityStatements->take(3))
                        <article class="people3-card">
                            <div class="people3-card__head"><div class="people3-avatar">{{ mb_strtoupper(mb_substr($person->discovery_display_name, 0, 1)) }}</div><span class="people3-card__status">●</span></div>
                            <h3>{{ $person->discovery_display_name }}</h3>
                            <p>{{ $person->current_activity ?: 'Membre GAMAD' }}</p>
                            @if($person->city)<small>⌖ {{ $person->city }}@if($person->country_code), {{ $person->country_code }}@endif</small>@endif
                            <div class="people3-tags">@forelse($visibleCapabilities as $statement)<span>{{ $statement->label }}</span>@empty<span>Capacités à découvrir</span>@endforelse</div>
                            <div class="people3-card__actions"><a href="{{ route('people.show', $person->discovery_reference) }}">Voir le profil</a><form method="POST" action="{{ route('messages.direct', $person->discovery_reference) }}">@csrf<button type="submit">Contacter</button></form></div>
                        </article>
                    @empty
                        <div class="people3-empty"><strong>{{ $settings['empty_title'] }}</strong><p>{{ $settings['empty_text'] }}</p><a href="{{ route('people.index') }}">Réinitialiser</a></div>
                    @endforelse
                </div>
                @if ($profiles->hasPages())<div class="people3-pagination">{{ $profiles->links() }}</div>@endif
            </section>

            <section id="capacites" class="people3-panel people3-explorer">
                <div class="people3-section-title"><div><h2>Explorer par capacité</h2><p>Trouvez des personnes selon ce qu’elles peuvent apporter.</p></div><a href="{{ url('/espace/profil') }}">Déclarer mes capacités →</a></div>
                <div class="people3-capabilities">
                    @forelse ($popularCapabilities as $capability)
                        <a href="{{ route('people.index', ['q' => $capability->label]) }}#personnes-decouvrir"><span>◈</span><strong>{{ $capability->label }}</strong><small>{{ $capability->people_count }} personne{{ (int)$capability->people_count === 1 ? '' : 's' }}</small></a>
                    @empty
                        <div class="people3-empty"><strong>Les premières capacités apparaîtront ici.</strong></div>
                    @endforelse
                </div>
            </section>

            <section id="territoires" class="people3-panel people3-explorer">
                <div class="people3-section-title"><div><h2>Explorer par territoire</h2><p>Découvrez les personnes engagées selon leur ville et leur pays.</p></div></div>
                <div class="people3-territories">
                    @forelse ($territoryCounts as $territory)
                        <a href="{{ route('people.index', ['country' => $territory->country_code]) }}#personnes-decouvrir"><span class="people3-territory__art">⌖</span><strong>{{ $territory->city }}</strong><small>{{ $territory->people_count }} personne{{ (int)$territory->people_count === 1 ? '' : 's' }} · {{ $territory->country_code }}</small></a>
                    @empty
                        <div class="people3-empty"><strong>Les territoires apparaîtront avec les profils publics.</strong></div>
                    @endforelse
                </div>
            </section>

            <section class="people3-why">
                <div><h2>Pourquoi les personnes sont au cœur de GAMAD ?</h2><p>Parce que chaque action commence par une capacité, une expérience ou une volonté de contribuer.</p></div>
                <div class="people3-why__grid">
                    <article><span>01</span><strong>Apprendre ensemble</strong><p>Trouver ceux qui savent déjà et ceux qui veulent progresser.</p></article>
                    <article><span>02</span><strong>Collaborer efficacement</strong><p>Rapprocher les bonnes personnes autour d’actions concrètes.</p></article>
                    <article><span>03</span><strong>Partager pour grandir</strong><p>Faire circuler les savoirs et l’expérience dans le réseau.</p></article>
                    <article><span>04</span><strong>Impacter durablement</strong><p>Transformer les capacités humaines en résultats utiles.</p></article>
                </div>
            </section>
        </main>

        <aside class="people3-right" aria-label="Suggestions du Carrefour Personnes">
            <section class="people3-right__panel">
                <div class="people3-right__title"><h2>Suggestions pour vous</h2>@if($suggestions !== [])<a href="{{ route('recommendations.index') }}">Voir tout →</a>@endif</div>
                <div class="people3-suggestions">
                    @forelse ($suggestions as $recommendation)
                        @php($recommended = $recommendation['profile'])
                        <a href="{{ route('people.show', $recommended->discovery_reference) }}"><span class="people3-avatar people3-avatar--mini">{{ mb_strtoupper(mb_substr($recommended->discovery_display_name, 0, 1)) }}</span><span><strong>{{ $recommended->discovery_display_name }}</strong><small>{{ $recommendation['reasons'][0] ?? ($recommended->current_activity ?: 'Rapprochement pertinent') }}</small></span><b>→</b></a>
                    @empty
                        @foreach ($recentProfiles->take(5) as $recent)
                            <a href="{{ route('people.show', $recent->discovery_reference) }}"><span class="people3-avatar people3-avatar--mini">{{ mb_strtoupper(mb_substr($recent->discovery_display_name, 0, 1)) }}</span><span><strong>{{ $recent->discovery_display_name }}</strong><small>{{ $recent->current_activity ?: 'Nouveau profil GAMAD' }}</small></span><b>→</b></a>
                        @endforeach
                    @endforelse
                </div>
            </section>
            <section class="people3-right__cta">
                <span>⌘</span><h2>Trouvez les bonnes personnes pour vos actions</h2><p>Plus votre profil est précis, plus les rapprochements deviennent utiles.</p><a href="{{ url('/espace/profil') }}">Enrichir mon profil</a>
            </section>
            <section class="people3-right__note"><strong>Confidentialité par choix</strong><p>Votre présence personnelle reste distincte de la découverte publique. Vos consentements continuent de décider ce que les autres voient.</p></section>
        </aside>
    </div>
</x-layouts.member>
