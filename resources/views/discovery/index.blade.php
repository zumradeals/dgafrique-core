<x-layouts.member title="Personnes" active="people" :wide="true">
    @php
        $modeQuery = request('mode');
        $countryQuery = request('country');
        $availabilityQuery = request('availability');
        $recentQuery = request('recent');
        $selfName = $selfProfile?->discovery_display_name ?: 'Votre présence GAMAD';
        $selfInitial = mb_strtoupper(mb_substr($selfName, 0, 1));
        $selfCapabilities = $selfProfile?->capabilityStatements?->take(4) ?? collect();
        $selfIsPublic = (bool) ($selfProfile?->orientation_consent && $selfProfile?->discovery_consent);
    @endphp

    <div class="dg-people dg-people-v2">
        <div class="dg-people-hub-grid">
            <div class="dg-people-main">
                <section class="dg-people-panel dg-people-hero-v2" aria-labelledby="people-title">
                    <div class="dg-people-hero-v2__copy">
                        <p class="dg-people-eyebrow">HUMAINS · CAPACITÉS · ACTION</p>
                        <h1 id="people-title">PERSONNES</h1>
                        <h2>Découvrir qui peut agir avec vous.</h2>
                        <p class="dg-people-hero-v2__lead">GAMAD commence par l’humain : ce qu’il sait faire, ce qu’il veut apprendre, ce qu’il peut transmettre et la manière dont il souhaite contribuer.</p>
                        <div class="dg-people-info-note">
                            <span aria-hidden="true">●</span>
                            <span><strong>Une personne existe pleinement dans GAMAD avant toute ZUMRA.</strong><small>Les communautés et les projets viennent ensuite relier les capacités aux actions.</small></span>
                        </div>
                        <div class="dg-people-hero-v2__actions">
                            <a class="dg-people-button dg-people-button--solar" href="#personnes-decouvrir">Découvrir les personnes</a>
                            <a class="dg-people-button dg-people-button--secondary" href="{{ url('/espace/profil') }}">Compléter mon profil</a>
                        </div>
                    </div>
                    <div class="dg-people-hero-v2__art">
                        <img src="{{ asset('images/entry/commencer-640.webp') }}" srcset="{{ asset('images/entry/commencer-640.webp') }} 640w, {{ asset('images/entry/commencer-1280.webp') }} 1280w" sizes="(min-width: 1100px) 40vw, 100vw" alt="Des personnes qui réfléchissent et construisent ensemble" width="1280" height="853">
                        <p class="dg-people-hero-v2__art-copy">Des talents, des savoirs et des volontés à relier.</p>
                        <p class="dg-people-hero-v2__sticker">L’humain d’abord.</p>
                    </div>
                </section>

                <section class="dg-people-panel dg-people-search-panel" aria-label="Rechercher et filtrer les personnes">
                    <form class="dg-people-search-v2" method="GET" action="{{ route('people.index') }}">
                        <input name="q" value="{{ request('q') }}" maxlength="100" aria-label="Rechercher une personne ou une capacité" placeholder="Rechercher une personne, une capacité, une activité…">
                        <button type="submit">Rechercher</button>
                    </form>
                    <div class="dg-people-explore-chips" aria-label="Explorer les personnes">
                        <a href="{{ route('people.index') }}#personnes-decouvrir">Toutes</a>
                        <a href="{{ route('people.index', ['availability' => \App\Models\PersonProfile::AVAILABILITY_OPEN]) }}#personnes-decouvrir">Disponibles</a>
                        <a href="#capacites">Par capacité</a>
                        <a href="{{ route('people.index', ['recent' => 1]) }}#personnes-decouvrir">Nouveaux profils</a>
                        @foreach ($popularCapabilities->take(4) as $capability)
                            <a href="{{ route('people.index', ['q' => $capability->label]) }}#personnes-decouvrir">{{ $capability->label }}</a>
                        @endforeach
                    </div>
                </section>

                <section id="personnes-decouvrir" class="dg-people-panel dg-people-directory" aria-labelledby="people-directory-title">
                    <div class="dg-people-section-heading">
                        <div>
                            <h2 id="people-directory-title">Personnes à découvrir</h2>
                            @if (request('q'))
                                <p>{{ $profiles->total() }} résultat{{ $profiles->total() === 1 ? '' : 's' }} pour « {{ request('q') }} ».</p>
                            @else
                                <p>Des personnes visibles par choix, à découvrir par ce qu’elles peuvent apporter.</p>
                            @endif
                        </div>
                        <span class="dg-people-directory-count">{{ $profiles->total() }} profil{{ $profiles->total() === 1 ? '' : 's' }}</span>
                    </div>

                    <div class="dg-people-filterbar">
                        <form method="GET" action="{{ route('people.index') }}">
                            <input name="q" value="{{ request('q') }}" maxlength="100" placeholder="Capacité ou activité">
                            @if ($settings['country_filter'] ?? true)
                                <input name="country" value="{{ $countryQuery }}" maxlength="2" placeholder="Pays · CI">
                            @endif
                            @if ($settings['mode_filter'] ?? true)
                                <select name="mode">
                                    <option value="">Tous les modes</option>
                                    @foreach ($modes as $mode)
                                        <option value="{{ $mode['value'] }}" @selected($modeQuery === $mode['value'])>{{ $mode['label'] ?? $mode['value'] }}</option>
                                    @endforeach
                                </select>
                            @endif
                            <select name="availability">
                                <option value="">Toutes disponibilités</option>
                                @foreach (\App\Models\PersonProfile::AVAILABILITY_LABELS as $value => $label)
                                    <option value="{{ $value }}" @selected($availabilityQuery === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @if ($recentQuery === '1')<input type="hidden" name="recent" value="1">@endif
                            <button type="submit">Appliquer</button>
                            <a href="{{ route('people.index') }}#personnes-decouvrir">Réinitialiser</a>
                        </form>
                    </div>

                    <div class="dg-people-card-grid">
                        @forelse ($profiles as $person)
                            @php
                                $visibleCapabilities = $person->capabilityStatements->take(4);
                                $isOpen = $person->availability_status === \App\Models\PersonProfile::AVAILABILITY_OPEN;
                            @endphp
                            <article class="dg-people-card">
                                <div class="dg-people-card__top">
                                    <span class="dg-people-card__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($person->discovery_display_name, 0, 1)) }}</span>
                                    <div>
                                        <h3>{{ $person->discovery_display_name }}</h3>
                                        <p>{{ $person->current_activity ?: 'Membre GAMAD' }}</p>
                                    </div>
                                </div>
                                <div class="dg-people-card__meta">
                                    @if ($person->city)<span>⌖ {{ $person->city }}@if ($person->country_code), {{ $person->country_code }}@endif</span>@endif
                                    @if ($person->availability_status)<span class="{{ $isOpen ? 'is-open' : '' }}">{{ \App\Models\PersonProfile::AVAILABILITY_LABELS[$person->availability_status] ?? $person->availability_status }}</span>@endif
                                </div>
                                <div class="dg-people-card__caps">
                                    @forelse ($visibleCapabilities as $statement)
                                        <span>{{ $statement->label }}</span>
                                    @empty
                                        <span>Capacités à découvrir</span>
                                    @endforelse
                                </div>
                                @if ($person->discovery_bio)<p class="dg-people-card__bio">{{ \Illuminate\Support\Str::limit($person->discovery_bio, 145) }}</p>@endif
                                <p class="dg-people-card__reason"><strong>Pourquoi ce profil apparaît :</strong> {{ request('q') ? 'il correspond à votre recherche ou à une capacité visible.' : 'cette personne a choisi d’être découvrable dans GAMAD.' }}</p>
                                <div class="dg-people-card__actions">
                                    <a href="{{ route('people.show', $person->discovery_reference) }}">{{ $settings['detail_button'] }}</a>
                                    <form method="POST" action="{{ route('messages.direct', $person->discovery_reference) }}">@csrf<button type="submit">Contacter</button></form>
                                </div>
                            </article>
                        @empty
                            <div class="dg-people-directory-empty">
                                <h3>{{ $settings['empty_title'] }}</h3>
                                <p>{{ $settings['empty_text'] }}</p>
                                <p>Aucun profil ne correspond encore.</p>
                                <a class="dg-people-button dg-people-button--solar" href="{{ route('people.index') }}">Réinitialiser la recherche</a>
                            </div>
                        @endforelse
                    </div>

                    @if ($profiles->hasPages())<div class="dg-people-pagination">{{ $profiles->links() }}</div>@endif
                </section>
            </div>

            <aside class="dg-people-sidebar" aria-label="Votre présence et les repères du Carrefour Personnes">
                <section class="dg-people-panel dg-people-sidebar-card dg-people-self" aria-labelledby="people-self-title">
                    <div class="dg-people-sidebar-heading"><h2 id="people-self-title">Ma présence dans GAMAD</h2><span class="dg-people-status-dot {{ $selfIsPublic ? 'is-public' : '' }}">●</span></div>
                    <div class="dg-people-self__identity">
                        <span class="dg-people-self__avatar">{{ $selfInitial }}</span>
                        <div><strong>{{ $selfName }}</strong><small>{{ $selfProfile?->current_activity ?: 'Profil personnel GAMAD' }}</small></div>
                    </div>
                    @if ($selfProfile)
                        <div class="dg-people-self__meta">
                            @if ($selfProfile->city)<span>⌖ {{ $selfProfile->city }}@if($selfProfile->country_code), {{ $selfProfile->country_code }}@endif</span>@endif
                            <span>{{ $selfIsPublic ? 'Visible dans le réseau' : 'Visible seulement par vous' }}</span>
                        </div>
                        <div class="dg-people-self__caps">
                            @forelse ($selfCapabilities as $statement)<span>{{ $statement->label }}</span>@empty<span>Aucune capacité déclarée</span>@endforelse
                        </div>
                    @else
                        <p>Votre identité existe dans GAMAD, mais votre profil d’action n’est pas encore complété.</p>
                    @endif
                    <a class="dg-people-button dg-people-button--secondary dg-people-button--full" href="{{ url('/espace/profil') }}">Voir / compléter mon profil</a>
                </section>

                <section class="dg-people-panel dg-people-sidebar-card" aria-labelledby="people-stats-title">
                    <div class="dg-people-sidebar-heading"><h2 id="people-stats-title">Les personnes en chiffres</h2></div>
                    <div class="dg-people-stats">
                        <div><span>◎</span><strong>{{ number_format($metrics['people'], 0, ',', ' ') }}</strong><small>présences dans ce carrefour</small></div>
                        <div><span>◈</span><strong>{{ number_format($metrics['capabilities'], 0, ',', ' ') }}</strong><small>capacités partagées</small></div>
                        <div><span>✓</span><strong>{{ number_format($metrics['available'], 0, ',', ' ') }}</strong><small>disponibles</small></div>
                        <div><span>↗</span><strong>{{ number_format($metrics['recent'], 0, ',', ' ') }}</strong><small>nouveaux profils</small></div>
                    </div>
                </section>

                @if ($recommendations !== [])
                    <section class="dg-people-panel dg-people-sidebar-card" aria-labelledby="people-recommendations-title">
                        <div class="dg-people-sidebar-heading"><h2 id="people-recommendations-title">Pour vous</h2><a href="{{ route('recommendations.index') }}">Voir tout →</a></div>
                        <div class="dg-people-reco-list">
                            @foreach (array_slice($recommendations, 0, 3) as $recommendation)
                                @php($recommended = $recommendation['profile'])
                                <a href="{{ route('people.show', $recommended->discovery_reference) }}">
                                    <span>{{ mb_strtoupper(mb_substr($recommended->discovery_display_name, 0, 1)) }}</span>
                                    <span><strong>{{ $recommended->discovery_display_name }}</strong><small>{{ $recommendation['reasons'][0] ?? ($recommended->current_activity ?: 'Rapprochement pertinent') }}</small></span>
                                    <b>→</b>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif

                <section class="dg-people-panel dg-people-sidebar-card dg-people-contribute-card">
                    <h2>Que voulez-vous apporter aujourd’hui ?</h2>
                    <p>Déclarez vos capacités, vos apprentissages et ce que vous pouvez transmettre pour rendre les rapprochements plus utiles.</p>
                    <a class="dg-people-button dg-people-button--solar dg-people-button--full" href="{{ url('/espace/profil') }}">Enrichir ma présence</a>
                </section>
            </aside>
        </div>

        <section id="capacites" class="dg-people-panel dg-people-wide-section dg-people-capability-world" aria-labelledby="capabilities-title">
            <div class="dg-people-wide-heading">
                <div><p class="dg-people-eyebrow">SAVOIR-FAIRE · APPRENTISSAGE · TRANSMISSION</p><h2 id="capabilities-title">Explorer par capacité</h2><p>Les personnes ne sont pas seulement des profils : elles portent des savoirs, des envies d’apprendre et des capacités à transmettre.</p></div>
                <a href="{{ url('/espace/profil') }}">Déclarer mes capacités →</a>
            </div>
            <div class="dg-people-capability-layout">
                <div class="dg-people-capability-chips">
                    @forelse ($popularCapabilities as $capability)
                        <a href="{{ route('people.index', ['q' => $capability->label]) }}#personnes-decouvrir"><strong>{{ $capability->label }}</strong><small>{{ $capability->people_count }} personne{{ (int)$capability->people_count === 1 ? '' : 's' }}</small></a>
                    @empty
                        <div class="dg-people-capability-empty"><strong>Les premières capacités apparaîtront ici.</strong><span>Complétez votre profil pour commencer à rendre le réseau humain plus lisible.</span></div>
                    @endforelse
                </div>
                <div class="dg-people-action-triad">
                    <article><span>01</span><h3>Je peux aider</h3><p>Rendre visibles les capacités que vous pouvez mobiliser au service d’un besoin ou d’une action.</p></article>
                    <article><span>02</span><h3>Je veux apprendre</h3><p>Exprimer ce que vous souhaitez acquérir afin que GAMAD puisse rapprocher apprentissage et transmission.</p></article>
                    <article><span>03</span><h3>Je peux transmettre</h3><p>Faire circuler l’expérience et les savoirs vers les personnes qui en ont besoin.</p></article>
                </div>
            </div>
        </section>

        <section class="dg-people-panel dg-people-wide-section dg-people-territories" aria-labelledby="people-territory-title">
            <div class="dg-people-wide-heading">
                <div><p class="dg-people-eyebrow">PROXIMITÉ · TERRITOIRES · LIENS</p><h2 id="people-territory-title">Des personnes proches de l’action</h2><p>Découvrez les territoires où des membres ont choisi de rendre leur présence visible.</p></div>
            </div>
            <div class="dg-people-territory-grid">
                <div class="dg-people-territory-visual">
                    <div class="dg-people-territory-orbit"><span>PERSONNES</span><strong>GAMAD</strong><small>Les capacités prennent sens quand elles rencontrent un besoin, un territoire et d’autres humains.</small></div>
                </div>
                <div class="dg-people-territory-list">
                    @forelse ($territoryCounts as $territory)
                        <a href="{{ route('people.index', ['country' => $territory->country_code]) }}#personnes-decouvrir"><strong>{{ $territory->city }}</strong><span>{{ $territory->people_count }} personne{{ (int)$territory->people_count === 1 ? '' : 's' }} · {{ $territory->country_code }}</span></a>
                    @empty
                        <div class="dg-people-territory-empty">Les territoires apparaîtront lorsque les personnes auront choisi de les renseigner publiquement.</div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="dg-people-panel dg-people-wide-section dg-people-why" aria-labelledby="people-why-title">
            <div class="dg-people-why__heading"><span>?</span><div><p class="dg-people-eyebrow">COMPRENDRE LE CARREFOUR</p><h2 id="people-why-title">Pourquoi les personnes sont-elles au cœur de GAMAD ?</h2><p>Parce qu’avant une communauté, un projet ou une preuve, il y a une personne capable de comprendre, d’apprendre, de transmettre et d’agir.</p></div></div>
            <div class="dg-people-why__grid">
                <article><span>01</span><h3>Une identité d’action</h3><p>Votre présence décrit ce que vous pouvez apporter sans vous réduire à un simple profil social.</p></article>
                <article><span>02</span><h3>Des capacités qui circulent</h3><p>Les savoirs, apprentissages et transmissions alimentent les futurs rapprochements dans tout GAMAD.</p></article>
                <article><span>03</span><h3>Des liens vers l’action</h3><p>Une personne peut ensuite rencontrer un besoin, rejoindre une ZUMRA et contribuer à un projet concret.</p></article>
            </div>
        </section>

        <p class="dg-people-privacy">GAMAD n’affiche ni téléphone, ni preuve privée, ni référence d’identité dans le Carrefour Personnes. La visibilité dépend des consentements choisis par chaque personne.</p>
    </div>
</x-layouts.member>
