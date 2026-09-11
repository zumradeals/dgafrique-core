<x-layouts.member title="Personnes" active="people" :wide="true">
    @php
        $modeQuery = request('mode');
        $countryQuery = request('country');
        $availabilityQuery = request('availability');
        $recentQuery = request('recent');
    @endphp

    <div class="dg-people">
        <section class="dg-people-hero" aria-labelledby="people-title">
            <p class="dg-people-eyebrow">CARREFOUR PERSONNES · GAMAD</p>
            <h1 id="people-title">Des personnes avec qui agir.</h1>
            <p>{{ $settings['introduction'] }}</p>
            <form class="dg-people-search" method="GET" action="{{ route('people.index') }}">
                <input name="q" value="{{ request('q') }}" maxlength="100" aria-label="Rechercher une personne ou une capacité" placeholder="Rechercher une personne, une capacité, une activité…">
                <button type="submit">Rechercher</button>
            </form>
        </section>

        <nav class="dg-people-quick" aria-label="Raccourcis de découverte">
            <a href="{{ route('people.index') }}"><span>◎</span>Toutes les personnes</a>
            <a href="{{ route('people.index', ['availability' => \App\Models\PersonProfile::AVAILABILITY_OPEN]) }}"><span>✓</span>Disponibles</a>
            <a href="{{ route('people.index') }}#capacites"><span>◈</span>Par capacité</a>
            <a href="{{ route('people.index', ['recent' => 1]) }}"><span>↗</span>Nouveaux profils</a>
        </nav>

        <section class="dg-people-metrics" aria-label="Repères du réseau">
            <article class="dg-people-metric"><strong>{{ number_format($metrics['people'], 0, ',', ' ') }}</strong><span>personnes découvrables</span></article>
            <article class="dg-people-metric"><strong>{{ number_format($metrics['capabilities'], 0, ',', ' ') }}</strong><span>capacités partagées</span></article>
            <article class="dg-people-metric"><strong>{{ number_format($metrics['available'], 0, ',', ' ') }}</strong><span>disponibles pour contribuer</span></article>
            <article class="dg-people-metric"><strong>{{ number_format($metrics['recent'], 0, ',', ' ') }}</strong><span>nouveaux profils ce mois</span></article>
        </section>

        @if ($recommendations !== [])
            <section class="dg-people-panel" aria-labelledby="people-recommendations-title">
                <div class="dg-people-section-head">
                    <div>
                        <p class="dg-people-eyebrow">POUR VOUS</p>
                        <h2 id="people-recommendations-title">Des rapprochements qui ont du sens</h2>
                    </div>
                    <a class="dg-people-chip" href="{{ route('recommendations.index') }}">Toutes mes recommandations →</a>
                </div>
                <div class="dg-people-recos">
                    @foreach (array_slice($recommendations, 0, 3) as $recommendation)
                        @php
                            $recommended = $recommendation['profile'];
                            $firstReason = $recommendation['reasons'][0] ?? null;
                        @endphp
                        <article class="dg-people-reco">
                            <div class="dg-people-reco-top">
                                <span class="dg-people-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($recommended->discovery_display_name, 0, 1)) }}</span>
                                <div>
                                    <h3>{{ $recommended->discovery_display_name }}</h3>
                                    <small>{{ $recommended->current_activity ?: 'Profil GAMAD' }}</small>
                                </div>
                            </div>
                            @if ($firstReason)
                                <p>{{ $firstReason }}</p>
                            @endif
                            <p><a class="dg-people-chip" href="{{ route('people.show', $recommended->discovery_reference) }}">Voir le profil →</a></p>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="dg-people-layout">
            <aside class="dg-people-filter" aria-label="Filtres personnes">
                <h2>Affiner</h2>
                <form method="GET" action="{{ route('people.index') }}">
                    <label for="people-q">Capacité ou activité</label>
                    <input id="people-q" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Ex. comptabilité">

                    @if ($settings['country_filter'] ?? true)
                        <label for="people-country">Pays</label>
                        <input id="people-country" name="country" value="{{ $countryQuery }}" maxlength="2" placeholder="CI">
                    @endif

                    @if ($settings['mode_filter'] ?? true)
                        <label for="people-mode">Mode de participation</label>
                        <select id="people-mode" name="mode">
                            <option value="">Tous les modes</option>
                            @foreach ($modes as $mode)
                                <option value="{{ $mode['value'] }}" @selected($modeQuery === $mode['value'])>{{ $mode['label'] ?? $mode['value'] }}</option>
                            @endforeach
                        </select>
                    @endif

                    <label for="people-availability">Disponibilité</label>
                    <select id="people-availability" name="availability">
                        <option value="">Toutes</option>
                        @foreach (\App\Models\PersonProfile::AVAILABILITY_LABELS as $value => $label)
                            <option value="{{ $value }}" @selected($availabilityQuery === $value)>{{ $label }}</option>
                        @endforeach
                    </select>

                    @if ($recentQuery === '1')
                        <input type="hidden" name="recent" value="1">
                    @endif

                    <div class="dg-people-filter-actions">
                        <button type="submit">Appliquer</button>
                        <a href="{{ route('people.index') }}">Réinitialiser</a>
                    </div>
                </form>

                <div id="capacites" style="margin-top:1.25rem">
                    <p class="dg-people-eyebrow">CAPACITÉS PRÉSENTES</p>
                    <div class="dg-people-chips">
                        @forelse ($popularCapabilities as $capability)
                            <a class="dg-people-chip" href="{{ route('people.index', ['q' => $capability->label]) }}">{{ $capability->label }} · {{ $capability->people_count }}</a>
                        @empty
                            <span class="dg-person-empty">Pas encore assez de capacités publiques.</span>
                        @endforelse
                    </div>
                </div>
            </aside>

            <main class="dg-people-panel">
                <div class="dg-people-section-head">
                    <div>
                        <p class="dg-people-eyebrow">PERSONNES À DÉCOUVRIR</p>
                        <h2>{{ $profiles->total() }} résultat{{ $profiles->total() === 1 ? '' : 's' }}</h2>
                        @if (request('q'))
                            <p>Recherche : « {{ request('q') }} »</p>
                        @else
                            <p>Des personnes visibles par choix, à découvrir par ce qu’elles peuvent apporter.</p>
                        @endif
                    </div>
                </div>

                <div class="dg-people-list">
                    @forelse ($profiles as $person)
                        @php
                            $visibleCapabilities = $person->capabilityStatements->take(4);
                            $isOpen = $person->availability_status === \App\Models\PersonProfile::AVAILABILITY_OPEN;
                        @endphp
                        <article class="dg-person-card">
                            <span class="dg-person-card__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($person->discovery_display_name, 0, 1)) }}</span>
                            <div>
                                <h3>{{ $person->discovery_display_name }}</h3>
                                <div class="dg-person-card__meta">
                                    @if ($person->city)
                                        <span>⌖ {{ $person->city }}@if ($person->country_code), {{ $person->country_code }}@endif</span>
                                    @endif
                                    @if ($person->current_activity)
                                        <span>{{ $person->current_activity }}</span>
                                    @endif
                                    @if ($person->availability_status)
                                        <span class="dg-person-card__availability {{ $isOpen ? 'is-open' : '' }}">{{ \App\Models\PersonProfile::AVAILABILITY_LABELS[$person->availability_status] ?? $person->availability_status }}</span>
                                    @endif
                                </div>
                                <div class="dg-people-chips">
                                    @foreach ($visibleCapabilities as $statement)
                                        <span class="dg-people-chip">{{ $statement->label }}</span>
                                    @endforeach
                                </div>
                                @if ($person->discovery_bio)
                                    <p>{{ \Illuminate\Support\Str::limit($person->discovery_bio, 170) }}</p>
                                @endif
                                <p class="dg-person-card__reason">
                                    <strong>Pourquoi ce profil apparaît :</strong>
                                    {{ request('q') ? 'il correspond à votre recherche ou à une capacité visible.' : 'cette personne a choisi d’être découvrable dans GAMAD.' }}
                                </p>
                            </div>
                            <div class="dg-person-card__actions">
                                <a href="{{ route('people.show', $person->discovery_reference) }}">{{ $settings['detail_button'] }}</a>
                                <form method="POST" action="{{ route('messages.direct', $person->discovery_reference) }}">
                                    @csrf
                                    <button type="submit">Contacter</button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <section class="dg-people-empty">
                            <h2>{{ $settings['empty_title'] }}</h2>
                            <p>{{ $settings['empty_text'] }}</p>
                        </section>
                    @endforelse
                </div>

                @if ($profiles->hasPages())
                    <div class="dg-people-pagination">{{ $profiles->links() }}</div>
                @endif
            </main>
        </div>

        <p style="margin:1rem 0 0;color:#7b8b95;font-size:.82rem">{{ $settings['privacy_notice'] }}</p>
    </div>
</x-layouts.member>
