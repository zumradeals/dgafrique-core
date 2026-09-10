<x-layouts.member title="ZUMRA" active="zumra" :wide="true">
    @php
        $statusLabels = [
            'ACTIVE' => 'Membre',
            'INVITED' => 'Invitation reçue',
            'REQUESTED' => 'Demande en cours',
        ];
        $categoryChips = ['Agriculture', 'Éducation', 'Santé', 'Entrepreneuriat', 'Technologies', 'Environnement', 'Social', 'Culture'];
        $featuredTerritories = ['Abidjan', 'Yamoussoukro', 'Bouaké', 'Korhogo', 'San Pedro', 'Man', 'Grand-Bassam'];
        $territoryCount = function (string $label) use ($territoryCounts): ?int {
            $needle = mb_strtolower($label);
            $row = $territoryCounts->first(fn (array $row): bool => str_contains(mb_strtolower((string) $row['location']), $needle));
            return $row ? (int) $row['count'] : null;
        };
    @endphp

    <div class="dg-zumra-hub">
        <div class="dg-zumra-grid">
            <div class="dg-zumra-main">
                <section id="comprendre-zumra" class="dg-zumra-panel dg-zumra-hero" aria-labelledby="zumra-hub-title">
                    <div class="dg-zumra-hero__copy">
                        <p class="dg-zumra-eyebrow">COMMUNAUTÉS · PROJETS · IMPACT</p>
                        <h1 id="zumra-hub-title">ZUMRA</h1>
                        <h2>Grandir et agir ensemble.</h2>
                        <p class="dg-zumra-hero__lead">Une ZUMRA réunit des personnes autour d’un domaine, d’un territoire ou d’un objectif commun pour apprendre, travailler et avoir un impact durable.</p>

                        <div class="dg-zumra-info-note">
                            <span class="dg-zumra-info-note__icon" aria-hidden="true">●</span>
                            <span><strong>Vous pouvez utiliser GAMAD sans appartenir à une ZUMRA.</strong><small>Rejoignez une communauté quand cela correspond à ce qui compte pour vous.</small></span>
                        </div>

                        <div class="dg-zumra-hero__actions">
                            <x-dg.button href="#zumra-decouvrir" variant="solar"><span aria-hidden="true">⌕</span> Découvrir les ZUMRA</x-dg.button>
                            <x-dg.button :href="$canCreateGroup ? route('zumra.groups.create') : route('zumra.membership.show')" variant="secondary"><x-dg.icon name="people" /> Créer une ZUMRA</x-dg.button>
                        </div>
                    </div>

                    <div class="dg-zumra-hero__art">
                        <img
                            src="{{ asset('images/entry/commencer-640.webp') }}"
                            srcset="{{ asset('images/entry/commencer-640.webp') }} 640w, {{ asset('images/entry/commencer-1280.webp') }} 1280w"
                            sizes="(min-width: 1100px) 40vw, 100vw"
                            alt="Des personnes réunies pour réfléchir et agir ensemble"
                            width="1280"
                            height="853"
                        >
                        <p class="dg-zumra-hero__art-copy">Des idées, talents et actions pour une Afrique meilleure&nbsp;!</p>
                        <p class="dg-zumra-hero__sticker">Ensemble, on va plus loin.</p>
                    </div>
                </section>

                <section class="dg-zumra-panel dg-zumra-search-panel" aria-label="Rechercher et filtrer les ZUMRA">
                    <form class="dg-zumra-search" method="GET" action="{{ route('zumra.index') }}">
                        <x-dg.input id="q" name="q" :value="$query" placeholder="Rechercher une ZUMRA (nom, domaine, lieu, mot-clé...)" aria-label="Rechercher une ZUMRA" />
                        @if ($location !== '')<input type="hidden" name="location" value="{{ $location }}">@endif
                        @if ($mode !== null)<input type="hidden" name="mode" value="{{ $mode }}">@endif
                        <x-dg.button type="submit">Rechercher</x-dg.button>
                    </form>

                    <div class="dg-zumra-chips" aria-label="Explorer par domaine">
                        <a class="dg-zumra-chip" href="{{ route('zumra.index') }}#zumra-decouvrir" aria-current="{{ $query === '' ? 'true' : 'false' }}">Toutes</a>
                        @foreach ($categoryChips as $category)
                            <a class="dg-zumra-chip" href="{{ route('zumra.index', ['q' => $category]) }}#zumra-decouvrir" aria-current="{{ mb_strtolower($query) === mb_strtolower($category) ? 'true' : 'false' }}">{{ $category }}</a>
                        @endforeach
                        <details class="dg-zumra-more">
                            <summary>Plus ↓</summary>
                            <div class="dg-zumra-more__menu">
                                @forelse ($discoverDomains as $domain)
                                    <a href="{{ route('zumra.index', ['q' => $domain['domain']]) }}#zumra-decouvrir">{{ $domain['domain'] }} <small>({{ $domain['count'] }})</small></a>
                                @empty
                                    @foreach ($popularActivities as $activity)
                                        <a href="{{ route('zumra.index', ['q' => $activity]) }}#zumra-decouvrir">{{ $activity }}</a>
                                    @endforeach
                                    @if ($popularActivities->isEmpty())<a href="{{ route('zumra.index') }}#zumra-decouvrir">Toutes les activités</a>@endif
                                @endforelse
                            </div>
                        </details>
                    </div>
                </section>

                <section id="zumra-decouvrir" class="dg-zumra-panel dg-zumra-directory" aria-labelledby="discover-title">
                    <div class="dg-zumra-section-heading">
                        <div>
                            <h2 id="discover-title">ZUMRA à découvrir</h2>
                            <p>Des communautés qui construisent des solutions concrètes.</p>
                        </div>
                        @if (!$isExhaustive)
                            <a class="dg-zumra-link" href="{{ route('zumra.index', ['all' => 1]) }}#zumra-decouvrir">Voir toutes les ZUMRA →</a>
                        @elseif ($showAll && $query === '' && $location === '' && $mode === null && $personalFilter === null)
                            <a class="dg-zumra-link" href="{{ route('zumra.index') }}#zumra-decouvrir">Voir une sélection →</a>
                        @endif
                    </div>

                    <div class="dg-zumra-cards">
                        @forelse ($discoverGroups as $row)
                            <article class="dg-zumra-card">
                                <div class="dg-zumra-card__media">
                                    <img src="{{ $row['cover'] }}" alt="" loading="lazy" width="640" height="360">
                                    <span class="dg-zumra-card__domain">{{ $row['group']->domain ?: 'Autres' }}</span>
                                </div>
                                <div class="dg-zumra-card__body">
                                    <div class="dg-zumra-card__title-row">
                                        <span class="dg-zumra-card__mark" aria-hidden="true">{{ $row['initials'] }}</span>
                                        <h3>{{ $row['group']->name }}</h3>
                                    </div>
                                    <p class="dg-zumra-card__objective">{{ $row['group']->founding_objective }}</p>
                                    <div class="dg-zumra-card__meta">
                                        <div class="dg-zumra-card__meta-line"><span aria-hidden="true">⌖</span><span>{{ $row['group']->location ?: 'Territoire non précisé' }}</span></div>
                                        <div class="dg-zumra-card__meta-line"><span aria-hidden="true">◌</span><span>{{ $row['mode_label'] }}</span></div>
                                    </div>
                                    <div class="dg-zumra-card__numbers">
                                        <span>♙ {{ (int) $row['group']->active_member_count }} membre{{ (int) $row['group']->active_member_count === 1 ? '' : 's' }}</span>
                                        <span>▣ {{ $row['projects_count'] }} projet{{ $row['projects_count'] === 1 ? '' : 's' }}</span>
                                    </div>
                                    <a class="dg-zumra-card__button" href="{{ route('zumra.groups.show', $row['group']) }}">Voir la ZUMRA</a>
                                </div>
                            </article>
                        @empty
                            <div class="dg-zumra-directory-empty">
                                @if ($isExhaustive)
                                    <h3>Aucune ZUMRA ne correspond à cette recherche.</h3>
                                    <p>Modifiez votre recherche ou explorez un autre domaine ou territoire.</p>
                                    <div class="dg-zumra-directory-empty__actions">
                                        <x-dg.button :href="route('zumra.index').'#zumra-decouvrir'" variant="secondary">Réinitialiser la recherche</x-dg.button>
                                        <x-dg.button href="#territoires-zumra" variant="solar">Explorer les territoires</x-dg.button>
                                    </div>
                                @else
                                    <h3>Les premières ZUMRA apparaîtront ici.</h3>
                                    <p>Explorez les territoires, recherchez une communauté ou créez celle qui correspond à votre objectif.</p>
                                    <div class="dg-zumra-directory-empty__actions">
                                        <x-dg.button href="#territoires-zumra" variant="secondary">Explorer les territoires</x-dg.button>
                                        <x-dg.button :href="$canCreateGroup ? route('zumra.groups.create') : route('zumra.membership.show')" variant="solar">Créer une ZUMRA</x-dg.button>
                                    </div>
                                @endif
                            </div>
                        @endforelse
                    </div>

                    @if ($isExhaustive)
                        <div class="dg-zumra-pagination">{{ $discoverGroups->links() }}</div>
                    @endif
                </section>
            </div>

            <aside class="dg-zumra-sidebar" aria-label="Votre univers ZUMRA et la découverte du réseau">
                <section class="dg-zumra-panel dg-zumra-sidebar-card" aria-labelledby="my-zumra-title">
                    <div class="dg-zumra-sidebar-heading">
                        <h2 id="my-zumra-title">Mes ZUMRA</h2>
                        <a class="dg-zumra-link" href="{{ route('zumra.index', ['view' => 'mine']) }}#zumra-decouvrir">Voir toutes →</a>
                    </div>
                    <div class="dg-zumra-my-list">
                        @forelse ($myGroups->take(3) as $row)
                            <a class="dg-zumra-my-row" href="{{ route('zumra.groups.show', $row['group']) }}">
                                <span class="dg-zumra-my-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($row['group']->name, 0, 1)) }}</span>
                                <span><strong>{{ $row['group']->name }}</strong><small>{{ $statusLabels[$row['status']] ?? 'Participation' }} @if($row['role_label']) · {{ $row['role_label'] }} @endif <span class="dg-zumra-membership-dot">●</span></small></span>
                                <span aria-hidden="true">→</span>
                            </a>
                        @empty
                            <p class="dg-zumra-empty-mini">Vous n’appartenez encore à aucune ZUMRA. Explorez le répertoire et entrez dans le monde qui vous correspond.</p>
                        @endforelse
                    </div>
                    @if ($myGroups->isNotEmpty())
                        <div class="dg-zumra-sidebar-cta"><x-dg.button :href="route('zumra.index', ['view' => 'mine'])" variant="secondary">Voir toutes mes ZUMRA</x-dg.button></div>
                    @else
                        <div class="dg-zumra-sidebar-cta"><x-dg.button href="#zumra-decouvrir" variant="secondary">Découvrir les ZUMRA</x-dg.button></div>
                    @endif
                </section>

                @if ($attentionItems->isNotEmpty())
                    <section class="dg-zumra-panel dg-zumra-sidebar-card dg-zumra-attention" aria-labelledby="zumra-attention-title">
                        <div class="dg-zumra-sidebar-heading"><h2 id="zumra-attention-title">À faire maintenant</h2></div>
                        @foreach ($attentionItems as $item)
                            <div class="dg-zumra-attention-item">
                                <p class="dg-zumra-eyebrow">{{ $item['eyebrow'] }}</p>
                                <h3>{{ $item['heading'] }}</h3>
                                <p>{{ $item['body'] }}</p>
                                <a class="dg-zumra-link" href="{{ $item['action_href'] }}">{{ $item['action_label'] }} →</a>
                            </div>
                        @endforeach
                    </section>
                @endif

                <section class="dg-zumra-panel dg-zumra-sidebar-card" aria-labelledby="zumra-stats-title">
                    <div class="dg-zumra-sidebar-heading"><h2 id="zumra-stats-title">Les ZUMRA en chiffres</h2></div>
                    <div class="dg-zumra-stats">
                        <div class="dg-zumra-stat"><span class="dg-zumra-stat__icon">⌘</span><span><strong>{{ $stats['groups'] }}</strong><small>ZUMRA actives</small></span></div>
                        <div class="dg-zumra-stat"><span class="dg-zumra-stat__icon">♙</span><span><strong>{{ $stats['members'] }}</strong><small>membres</small></span></div>
                        <div class="dg-zumra-stat"><span class="dg-zumra-stat__icon">▣</span><span><strong>{{ $stats['projects'] }}</strong><small>projets en cours</small></span></div>
                        <div class="dg-zumra-stat"><span class="dg-zumra-stat__icon">◎</span><span><strong>{{ $stats['territories'] }}</strong><small>territoires renseignés</small></span></div>
                    </div>
                </section>

                <section class="dg-zumra-panel dg-zumra-sidebar-card dg-zumra-create-card" aria-labelledby="create-zumra-title">
                    <h2 id="create-zumra-title">Vous ne trouvez pas de ZUMRA qui correspond ?</h2>
                    <p>Créez votre propre ZUMRA et rassemblez des personnes autour de ce qui compte pour vous.</p>
                    <x-dg.button :href="$canCreateGroup ? route('zumra.groups.create') : route('zumra.membership.show')" variant="solar"><x-dg.icon name="people" /> Créer une ZUMRA</x-dg.button>
                    @if (!$canCreateGroup)<p class="dg-zumra-territory-note">Une adhésion ZUMRA active est nécessaire pour lancer une communauté.</p>@endif
                </section>
            </aside>
        </div>

        <section id="territoires-zumra" class="dg-zumra-panel dg-zumra-wide-section dg-zumra-territory dg-zumra-territory--wide" aria-labelledby="territory-title">
            <div class="dg-zumra-wide-heading">
                <div>
                    <p class="dg-zumra-eyebrow">PROXIMITÉ · TERRITOIRES · ACTION</p>
                    <h2 id="territory-title">Explorer par territoire</h2>
                    <p>Découvrez les communautés qui agissent dans les territoires qui comptent pour vous.</p>
                </div>
                <a class="dg-zumra-link" href="{{ route('zumra.index') }}#zumra-decouvrir">Toutes les régions →</a>
            </div>

            <div class="dg-zumra-territory__body">
                <div class="dg-zumra-map-stage">
                    <svg class="dg-zumra-map" viewBox="0 0 250 300" role="img" aria-label="Exploration des ZUMRA par grands territoires de Côte d’Ivoire">
                        <path class="dg-zumra-map__shape" d="M70 24 L111 16 L144 29 L178 24 L203 54 L198 88 L219 115 L205 150 L215 187 L193 216 L187 250 L151 274 L117 265 L82 278 L58 253 L35 218 L40 181 L24 145 L39 112 L35 78 L55 56 Z" />
                        <circle class="dg-zumra-map__pin" cx="142" cy="230" r="4"/><text x="150" y="234">Abidjan</text>
                        <circle class="dg-zumra-map__pin" cx="124" cy="174" r="4"/><text x="132" y="178">Yamoussoukro</text>
                        <circle class="dg-zumra-map__pin" cx="160" cy="137" r="4"/><text x="168" y="141">Bouaké</text>
                        <circle class="dg-zumra-map__pin" cx="135" cy="70" r="4"/><text x="143" y="74">Korhogo</text>
                        <circle class="dg-zumra-map__pin" cx="73" cy="231" r="4"/><text x="23" y="245">San Pedro</text>
                        <circle class="dg-zumra-map__pin" cx="61" cy="133" r="4"/><text x="34" y="126">Man</text>
                    </svg>
                </div>

                <div class="dg-zumra-territory-list" aria-label="Territoires proposés">
                    @foreach ($featuredTerritories as $territory)
                        <a href="{{ route('zumra.index', ['location' => $territory]) }}#zumra-decouvrir"><span>{{ $territory }}</span>@if($territoryCount($territory) !== null)<small>{{ $territoryCount($territory) }} ZUMRA</small>@else<span aria-hidden="true">→</span>@endif</a>
                    @endforeach
                    <a href="{{ route('zumra.index') }}#zumra-decouvrir"><span>Autres régions</span><span aria-hidden="true">→</span></a>
                </div>
            </div>
            <p class="dg-zumra-territory-note">La carte facilite l’exploration. Les résultats sont issus uniquement des localisations réellement déclarées par les ZUMRA.</p>
        </section>

        <section class="dg-zumra-panel dg-zumra-wide-section dg-zumra-what-wide" aria-labelledby="what-zumra-title">
            <div class="dg-zumra-what-wide__intro">
                <span class="dg-zumra-what__icon" aria-hidden="true">?</span>
                <div>
                    <p class="dg-zumra-eyebrow">COMPRENDRE AVANT D’ENTRER</p>
                    <h2 id="what-zumra-title">Qu’est-ce qu’une ZUMRA ?</h2>
                    <p>Une ZUMRA est un monde communautaire organisé autour de personnes, d’un projet commun et d’actions concrètes.</p>
                </div>
            </div>

            <div class="dg-zumra-what-pillars">
                <article>
                    <span class="dg-zumra-pillar-number">01</span>
                    <h3>Une communauté</h3>
                    <p>Des personnes choisissent de grandir, apprendre et agir ensemble autour de ce qui les rassemble.</p>
                </article>
                <article>
                    <span class="dg-zumra-pillar-number">02</span>
                    <h3>Un projet commun</h3>
                    <p>Une ZUMRA peut faire naître et porter un projet autour duquel ses membres apprennent, travaillent et agissent ensemble. D’autres projets peuvent émerger lorsqu’un besoin réel le justifie.</p>
                </article>
                <article>
                    <span class="dg-zumra-pillar-number">03</span>
                    <h3>Un monde d’action</h3>
                    <p>Besoins, projets, échanges et contributions prennent vie dans cet espace avant de rayonner dans GAMAD.</p>
                </article>
            </div>
        </section>

        <footer class="dg-zumra-footer">
            <div><strong>GAMAD</strong><small>Des personnes. Des actions. Un impact réel.</small></div>
            <nav aria-label="Liens de fin de page"><a href="{{ route('gateway') }}">À propos</a><a href="{{ route('member.profile.edit') }}">Mon profil</a><a href="mailto:contact@dgafrique.com">Contact</a></nav>
        </footer>
    </div>
</x-layouts.member>