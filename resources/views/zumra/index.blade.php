{{--
    UIUX-010 — Le carrefour du monde ZUMRA. Reconstruction fidèle à la maquette validée par le
    propriétaire produit : trois colonnes desktop (univers ZUMRA / carrefour central / contexte
    personnel), découverte par activité en avant, naissance intégrée à l'univers ZUMRA plutôt
    qu'un formulaire isolé. Chaque bloc réutilise une capacité déjà réelle ; deux surfaces restent
    des vitrines de démonstration explicitement documentées (Fil ZUMRA détaillé, proximité
    géographique — voir EXPERIENCE-PRODUIT-CANONIQUE.md §35).
--}}
<x-layouts.portal title="ZUMRA — DG Afrique">
    <x-dg.shell current="zumra" :identity="$identity" :is-administrator="$isAdministrator">
        @php
            $membershipActive = $membership && $membership->status === \App\Models\ZumraProgramMembership::STATUS_ACTIVE;
        @endphp

        <div class="dg-page zw">
            <div class="zw-layout">
                <aside class="zw-left" aria-label="Univers ZUMRA">
                    <div class="zw-birth-cta">
                        @if($membershipActive)
                            <a href="{{ route('zumra.groups.create') }}" class="zw-birth-btn"><span aria-hidden="true">＋</span> Faire naître une ZUMRA</a>
                            <p>Créer votre espace d’activité</p>
                        @else
                            <a href="{{ route('zumra.membership.show') }}" class="zw-birth-btn"><span aria-hidden="true">＋</span> Faire naître une ZUMRA</a>
                            <p>Réservé aux membres dont l’adhésion au Programme ZUMRA est active.</p>
                        @endif
                    </div>

                    <nav class="zw-nav" aria-label="Navigation ZUMRA">
                        <a href="{{ route('zumra.index') }}" class="zw-nav-item is-active" aria-current="page">Découvrir</a>
                        <a href="{{ route('zumra.groups.index', ['view' => 'mine']) }}" class="zw-nav-item">Mes ZUMRA <span class="zw-nav-badge">{{ $navCounts['mine'] }}</span></a>
                        <a href="{{ route('zumra.groups.index', ['view' => 'invited']) }}" class="zw-nav-item">Invitations <span class="zw-nav-badge">{{ $navCounts['invitations'] }}</span></a>
                        <a href="{{ route('zumra.groups.index', ['view' => 'requested']) }}" class="zw-nav-item">Mes demandes <span class="zw-nav-badge">{{ $navCounts['requests'] }}</span></a>
                        <a href="#zw-attention" class="zw-nav-item">À faire maintenant <span class="zw-nav-badge">{{ $navCounts['attention'] }}</span></a>
                    </nav>

                    <div class="zw-explore" aria-labelledby="zw-explore-title">
                        <h2 id="zw-explore-title">Explorer par activité</h2>
                        @if($discoverDomains->isNotEmpty())
                            <div class="zw-explore-list">
                                @foreach($discoverDomains as $d)
                                    <a href="{{ route('zumra.groups.index', ['q' => $d['domain']]) }}" class="zw-explore-item">
                                        <x-dg.zumra-domain-icon :domain="$d['domain']" />
                                        <span class="zw-explore-label">{{ $d['domain'] }}</span>
                                        <span class="zw-explore-count">{{ $d['count'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <p style="font-size:.84rem;color:var(--zw-muted)">Les activités apparaîtront ici à mesure que des ZUMRA seront proposées.</p>
                        @endif
                    </div>

                    <div class="zw-fil-panel" aria-labelledby="zw-fil-title">
                        <h2 id="zw-fil-title">Le Fil ZUMRA</h2>
                        <p>Suivez en temps réel ce qui se passe dans les ZUMRA du réseau.</p>
                        <a href="{{ $fil['href'] }}" class="zw-fil-cta">Voir le Fil ZUMRA <span aria-hidden="true">→</span></a>
                        @if(!empty($fil['avatars']))
                            <div class="zw-fil-avatars">
                                @foreach($fil['avatars'] as $initial)
                                    <span class="zw-fil-avatar">{{ $initial }}</span>
                                @endforeach
                                @if($fil['remainder'] > 0)
                                    <span class="zw-fil-remainder">+{{ $fil['remainder'] }}</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </aside>

                <main class="zw-main">
                    <section class="zw-hero" aria-labelledby="zw-hero-title">
                        <div class="zw-hero-copy">
                            <span class="zw-hero-eyebrow">Programme ZUMRA</span>
                            <h1 id="zw-hero-title" class="zw-hero-title">Le monde ZUMRA</h1>
                            <p class="zw-hero-tagline">Apprendre. Transmettre. Construire. Agir ensemble.</p>
                            <p class="zw-hero-lead">Découvrez des ZUMRA, rejoignez celles qui vous inspirent ou faites naître la vôtre.</p>

                            <form method="GET" action="{{ route('zumra.groups.index') }}" role="search" class="zw-search">
                                <label for="zw-q" class="sr-only">Rechercher une activité, une ZUMRA, un mot-clé</label>
                                <input type="search" id="zw-q" name="q" class="zw-search-q" placeholder="Rechercher une activité, une ZUMRA, un mot-clé…">
                                <label for="zw-mode" class="sr-only">Mode</label>
                                <select id="zw-mode" name="mode" class="zw-search-select">
                                    <option value="">Toutes activités</option>
                                    <option value="PHYSICAL">Physique</option>
                                    <option value="DIGITAL">Numérique</option>
                                    <option value="HYBRID">Hybride</option>
                                </select>
                                <label for="zw-location" class="sr-only">Lieu</label>
                                <input type="text" id="zw-location" name="location" class="zw-search-location" placeholder="Lieu">
                                <button type="submit" class="zw-search-submit">Rechercher</button>
                            </form>

                            @if($popularActivities->isNotEmpty())
                                <div class="zw-popular">
                                    <span class="zw-popular-label">Populaires :</span>
                                    @foreach($popularActivities as $label)
                                        <a href="{{ route('zumra.groups.index', ['q' => $label]) }}" class="zw-popular-chip">{{ $label }}</a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="zw-hero-art">
                            <img src="{{ asset('images/zumra/hero-monde-zumra.svg') }}" alt="Un réseau de personnes reliées autour d’activités communes">
                        </div>
                    </section>

                    <section aria-labelledby="zw-discover-title" id="zw-discover">
                        <div class="zw-section-heading">
                            <h2 id="zw-discover-title">ZUMRA à découvrir</h2>
                            <a href="{{ route('zumra.groups.index') }}">Voir toutes <span aria-hidden="true">→</span></a>
                        </div>

                        @if($discoverGroups->isNotEmpty())
                            <div class="zw-discover-grid">
                                @foreach($discoverGroups as $row)
                                    @php($group = $row['group'])
                                    <article class="zw-discover-card">
                                        <div class="zw-discover-cover">
                                            <img src="{{ $row['cover'] }}" alt="">
                                            <span class="zw-discover-domain-badge">{{ $group->domain }}</span>
                                            <span class="zw-discover-bookmark" aria-hidden="true">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M6 3h12v18l-6-4-6 4V3Z"/></svg>
                                            </span>
                                        </div>
                                        <div class="zw-discover-body">
                                            <div class="zw-discover-identity">
                                                <span class="zw-discover-mark">{{ $row['initials'] }}</span>
                                                <h3 class="zw-discover-name">{{ $group->name }}</h3>
                                            </div>
                                            <p class="zw-discover-desc">{{ \Illuminate\Support\Str::limit($group->founding_objective, 130) }}</p>
                                            <div class="zw-discover-meta">
                                                <span>{{ $group->active_member_count }} membre{{ $group->active_member_count > 1 ? 's' : '' }}</span>
                                                @if($group->location)
                                                    <span>{{ $group->location }}</span>
                                                @endif
                                                <span>{{ $row['mode_label'] }}</span>
                                            </div>
                                            @if($row['welcome_open'])
                                                <span class="zw-discover-open">Ouverte à de nouveaux membres</span>
                                            @else
                                                <span class="zw-discover-closed">Cherche d’abord des transmetteurs</span>
                                            @endif
                                            <a href="{{ route('zumra.groups.show', $group) }}" class="zw-discover-cta">Voir la ZUMRA</a>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <x-dg.empty title="La première ZUMRA peut commencer ici.">
                                <span>Aucune équipe fictive n’est affichée. Les ZUMRA apparaissent à mesure que des adhérents proposent des activités réelles.</span>
                            </x-dg.empty>
                        @endif
                    </section>
                </main>

                <aside class="zw-right" aria-label="Votre contexte personnel">
                    <div class="zw-card zw-membership-card">
                        <h2>Mon adhésion ZUMRA</h2>
                        <div class="zw-membership-row">
                            <span class="zw-membership-mark {{ $membershipActive ? '' : 'is-muted' }}">{{ $membershipActive ? '✓' : '○' }}</span>
                            <div>
                                <strong>{{ $membershipActive ? 'Adhésion active' : 'Adhésion à activer' }}</strong>
                                <p>{{ $membershipActive ? 'Vous pouvez proposer, rejoindre et contribuer aux ZUMRA.' : 'Activez votre adhésion pour proposer ou rejoindre une ZUMRA.' }}</p>
                                @if($membershipActive)
                                    <a href="{{ route('zumra.card.show') }}">Voir ma carte <span aria-hidden="true">→</span></a>
                                @else
                                    <a href="{{ route('zumra.membership.show') }}">Activer mon adhésion <span aria-hidden="true">→</span></a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="zw-card" id="zw-attention">
                        <h2>À faire maintenant</h2>
                        @if($attentionItems->isNotEmpty())
                            <div class="zw-attention-list">
                                @foreach($attentionItems as $item)
                                    <a href="{{ $item['action_href'] }}" class="zw-attention-item">
                                        <span class="zw-attention-eyebrow">{{ $item['eyebrow'] }}</span>
                                        <strong>{{ $item['heading'] }}</strong>
                                        <span>{{ $item['action_label'] }} →</span>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="zw-attention-clear">
                                <span class="zw-attention-clear-mark" aria-hidden="true">✓</span>
                                <span>Aucune action ZUMRA ne demande votre attention pour le moment.</span>
                            </div>
                        @endif
                        <a href="{{ route('zumra.groups.index', ['view' => 'mine']) }}" class="zw-card-footer">Voir toutes mes actions <span aria-hidden="true">→</span></a>
                    </div>

                    <div class="zw-card zw-nearby-card">
                        <h2>Près de vous</h2>
                        @if($nearby->isNotEmpty())
                            <div class="zw-nearby-list">
                                @foreach($nearby as $n)
                                    <div class="zw-nearby-item">
                                        <span class="zw-nearby-mark">{{ mb_strtoupper(mb_substr($n->title, 0, 1)) }}</span>
                                        <div>
                                            <strong>{{ $n->title }}</strong>
                                            <small>{{ $n->activity_label }}</small>
                                        </div>
                                        <span class="zw-nearby-distance">{{ $n->distance_label }}</span>
                                    </div>
                                @endforeach
                            </div>
                            <a href="{{ route('zumra.groups.index') }}" class="zw-card-footer">Voir plus <span aria-hidden="true">→</span></a>
                            <p style="margin:.6rem 0 0;font-size:.72rem;color:var(--zw-muted)">Sélection illustrative en attendant un vrai rapprochement géographique — DG Afrique ne collecte pas encore de localisation précise.</p>
                        @else
                            <p style="font-size:.84rem;color:var(--zw-muted)">Le rapprochement par proximité arrive bientôt.</p>
                        @endif
                    </div>

                    <div class="zw-card zw-inspiration-card">
                        <h2>Besoin d’inspiration ?</h2>
                        <p>Explorez des ZUMRA qui transforment des idées en actions concrètes.</p>
                        <a href="{{ $fil['href'] }}" class="zw-card-footer">Voir les histoires <span aria-hidden="true">→</span></a>
                    </div>
                </aside>
            </div>

            <div class="zw-lower">
                <section class="zw-participate" aria-labelledby="zw-participate-title">
                <h2 id="zw-participate-title">Comment participer dans une ZUMRA ?</h2>
                <div class="zw-participate-grid">
                    <div class="zw-participate-step">
                        <span class="zw-participate-num" aria-hidden="true">1</span>
                        <strong>Découvrez</strong>
                        <p>Trouvez une ZUMRA correspondant à vos intérêts et à votre localisation.</p>
                    </div>
                    <div class="zw-participate-step">
                        <span class="zw-participate-num" aria-hidden="true">2</span>
                        <strong>Rejoignez</strong>
                        <p>Demandez à rejoindre ou répondez à une invitation d’un responsable.</p>
                    </div>
                    <div class="zw-participate-step">
                        <span class="zw-participate-num" aria-hidden="true">3</span>
                        <strong>Apprenez &amp; contribuez</strong>
                        <p>Apprenez, transmettez, partagez vos compétences et participez aux actions.</p>
                    </div>
                    <div class="zw-participate-step">
                        <span class="zw-participate-num" aria-hidden="true">4</span>
                        <strong>Construisez ensemble</strong>
                        <p>Contribuez aux projets et faites naître de nouvelles initiatives utiles à tous.</p>
                    </div>
                </div>
                </section>

                <section class="zw-stats" aria-label="Le réseau ZUMRA en chiffres">
                <div class="zw-stat">
                    <strong>{{ number_format($stats['groups'], 0, ',', ' ') }}</strong>
                    <span>ZUMRA actives</span>
                    @if($stats['groups_delta'] > 0)
                        <small>+{{ $stats['groups_delta'] }} ce mois</small>
                    @endif
                </div>
                <div class="zw-stat">
                    <strong>{{ number_format($stats['members'], 0, ',', ' ') }}</strong>
                    <span>Membres engagés</span>
                    @if($stats['members_delta'] > 0)
                        <small>+{{ $stats['members_delta'] }} ce mois</small>
                    @endif
                </div>
                <div class="zw-stat">
                    <strong>{{ $stats['domains'] }}</strong>
                    <span>Domaines d’activité</span>
                    <small style="color:rgba(255,255,255,.68)">Tous les secteurs</small>
                </div>
                <div class="zw-stat">
                    <strong>{{ number_format($stats['actions'], 0, ',', ' ') }}</strong>
                    <span>Actions en cours</span>
                    <small style="color:rgba(255,255,255,.68)">Projets, besoins, événements</small>
                </div>
                <blockquote class="zw-stats-quote">
                    « Une ZUMRA, c’est plus qu’un collectif : c’est un espace d’apprentissage, de transmission et d’action pour bâtir notre avenir. »
                    <cite>— DG Afrique</cite>
                </blockquote>
                </section>
            </div>
        </div>
    </x-dg.shell>
</x-layouts.portal>
