{{-- Projets — portail. Fidèle à la maquette du 20 août 2026 (voir addendum daté
     docs/design/DESIGN-INVARIANTS.md §18). Même objet métier que dans le Fil ; le Cerveau
     ajoute une interface cognitive sans dupliquer le Core. --}}
@php
    $domainIcons = ['DIGITAL'=>'device','EDUCATION'=>'book','HEALTH'=>'target','AGRICULTURE'=>'leaf','CRAFT'=>'compass','SOCIAL'=>'leaf','CULTURE'=>'book','OTHER'=>'compass','ENVIRONMENT'=>'leaf'];
    $statusLabels = ['PROPOSED'=>'Proposé','ADOPTED'=>'Adopté','IN_PROGRESS'=>'En action','COMPLETED'=>'Réalisé'];
    $maturityStages = \App\Application\Projects\ProjectMaturityService::STAGES;
    $visibleCount = $projects->total() + $demoCards->count();
@endphp
<x-layouts.portal title="Projets — DG Afrique">
    <x-dg.shell current="projets" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page">
            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="night">Du besoin à l’action</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">Des idées qui deviennent des projets vivants.</h1>
                    <p>Commencez avec vos propres mots. Le Cerveau vous aide à structurer la suite sans grand formulaire.</p>
                </div>
                <x-dg.btn variant="project" :href="route('projects.brain.start')">+ Parler de mon idée</x-dg.btn>
            </div>

            <section class="dg-card" style="margin-bottom:22px;padding:28px;display:grid;grid-template-columns:minmax(0,1fr) auto;gap:24px;align-items:center;background:linear-gradient(135deg,var(--dg-card) 0%,var(--dg-ivory) 100%)">
                <div>
                    <x-dg.label tone="night">Cerveau du Projet</x-dg.label>
                    <h2 class="dg-display dg-display--card" style="margin:8px 0 7px">Qu’est-ce que vous voulez réaliser ?</h2>
                    <p style="max-width:720px;margin:0 0 16px">Une idée encore floue suffit. Expliquez-la comme vous la raconteriez à quelqu’un. Nous avancerons une question utile à la fois.</p>
                    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
                        <x-dg.btn variant="project" :href="route('projects.brain.start')">Commencer la conversation →</x-dg.btn>
                        {{-- UIUX-008 — invariant : le Cerveau augmente DG Afrique, il ne le
                             conditionne pas (audit Phase A — /projets était le seul écran où les
                             trois CTA visibles menaient tous au Cerveau). Voie directe toujours
                             utilisable, sans dupliquer le formulaire ni dégrader le Cerveau. --}}
                        <a href="{{ route('projects.create') }}" class="dg-meta" style="color:var(--dg-copper);font-weight:600">Créer directement un projet →</a>
                    </div>
                </div>
                <span aria-hidden="true" style="display:grid;place-items:center;width:104px;height:104px;border-radius:28px;background:rgba(248,212,10,.16);color:var(--dg-saffron-ink)">
                    <x-dg.icon name="brain" size="52" />
                </span>
            </section>

            <div id="dg-projects-toolbar" class="dg-projects-toolbar">
                <form method="GET" class="dg-filters">
                    <label>Domaine<select name="domain" class="dg-select"><option value="">Tous les domaines</option>@foreach($configuration['domains'] as $code => $label)<option value="{{ $code }}" @selected(request('domain') === $code)>{{ $label }}</option>@endforeach</select></label>
                    <x-dg.btn variant="quiet" type="submit">Explorer</x-dg.btn>
                </form>

                <div class="dg-projects-search-row">
                    <div class="dg-projects-search" aria-disabled="true" title="La recherche par mot-clé sera activée avec l’index de projets réels.">
                        <x-dg.icon name="search" size="17" />
                        <input type="text" disabled placeholder="Rechercher un projet, un mot-clé…" aria-label="Rechercher un projet, un mot-clé (bientôt disponible)">
                    </div>
                    <div class="dg-projects-view-toggle" role="group" aria-label="Mode d’affichage">
                        <button type="button" aria-current="true" disabled title="Vue grille — vue par défaut.">
                            <x-dg.icon name="grid" size="17" />
                        </button>
                        <button type="button" disabled title="La vue liste arrivera avec un plus grand nombre de projets réels.">
                            <x-dg.icon name="list" size="17" />
                        </button>
                    </div>
                    <label class="dg-projects-sort">
                        Trier par
                        <select disabled title="Le tri se limite aujourd’hui aux projets les plus récents.">
                            <option>Récents</option>
                        </select>
                    </label>
                </div>
            </div>

            <div class="dg-projects-layout">
                <div>
                    <div class="dg-projects-section-head">
                        <h2 class="dg-display dg-display--card" style="margin:0">Projets proposés</h2>
                        <span class="dg-projects-count">{{ $visibleCount }}</span>
                    </div>

                    @if($projects->isEmpty() && $demoCards->isEmpty())
                        <x-dg.empty title="Aucun projet visible"><span>Pas besoin de remplir l’ancien formulaire : commencez simplement par raconter ce que vous voulez réaliser.</span></x-dg.empty>
                    @else
                        <div class="dg-grid">
                            @foreach($projects as $project)
                                <article class="dg-card dg-projects-card">
                                    <div class="dg-projects-card__visual" data-domain="{{ $project->domain }}">
                                        <span class="dg-projects-card__mark" aria-hidden="true"><x-dg.icon :name="$domainIcons[$project->domain] ?? 'compass'" size="26" /></span>
                                        <span class="dg-projects-card__state">{{ $statusLabels[$project->status] ?? $project->status }}</span>
                                    </div>
                                    <div class="dg-projects-card__body">
                                        <x-dg.badge tone="project">{{ $configuration['domains'][$project->domain] ?? $project->domain }}</x-dg.badge>
                                        <h3 class="dg-projects-card__title dg-display"><a href="{{ route('projects.show',$project) }}">{{ $project->name }}</a></h3>
                                        <p class="dg-body" style="max-width:none">{{ \Illuminate\Support\Str::limit($project->summary,140) }}</p>
                                        <div class="dg-projects-card__progress">
                                            <x-dg.maturity :stages="$maturityStages" :current="$project->maturity" />
                                            <span class="dg-projects-card__progress-label">{{ $maturityStages[$project->maturity]['label'] ?? $project->maturity }}</span>
                                        </div>
                                        <div class="dg-projects-card__meta">
                                            @if($project->location)<span><x-dg.icon name="target" size="13" /> {{ $project->location }}</span>@endif
                                            <span>Créé le {{ $project->created_at->format('d/m/Y') }}</span>
                                        </div>
                                        <x-dg.actions flush style="flex-direction:column;align-items:stretch">
                                            <x-dg.btn variant="project" :href="route('projects.brain.show',$project)">Ouvrir le Cerveau ✦</x-dg.btn>
                                            <div style="display:flex;gap:8px;flex-wrap:wrap">
                                                @if(in_array($project->id,$manageableProjectIds,true))<x-dg.btn variant="quiet" :href="route('projects.matching',$project)">Trouver des capacités →</x-dg.btn>@endif
                                                <x-dg.btn variant="quiet" :href="route('projects.show',$project)">Dossier →</x-dg.btn>
                                            </div>
                                        </x-dg.actions>
                                    </div>
                                </article>
                            @endforeach

                            @foreach($demoCards as $card)
                                <article class="dg-card dg-projects-card">
                                    <div class="dg-projects-card__visual" data-domain="{{ $card['domain_slug'] }}">
                                        <span class="dg-projects-card__mark" aria-hidden="true"><x-dg.icon :name="$card['icon']" size="26" /></span>
                                        <span class="dg-projects-card__state">Proposé</span>
                                    </div>
                                    <div class="dg-projects-card__body">
                                        <x-dg.badge tone="project">{{ $card['domain_label'] }} · Exemple</x-dg.badge>
                                        <h3 class="dg-projects-card__title dg-display">{{ $card['name'] }}</h3>
                                        <p class="dg-body" style="max-width:none">{{ $card['summary'] }}</p>
                                        <div class="dg-projects-card__progress">
                                            <x-dg.maturity :stages="$maturityStages" :current="$card['maturity']" />
                                            <span class="dg-projects-card__progress-label">{{ $maturityStages[$card['maturity']]['label'] ?? $card['maturity'] }}</span>
                                        </div>
                                        <div class="dg-projects-card__meta">
                                            <span><x-dg.icon name="target" size="13" /> {{ $card['location'] }}</span>
                                            <span>{{ $card['created_label'] }}</span>
                                        </div>
                                        <x-dg.actions flush style="flex-direction:column;align-items:stretch">
                                            <x-dg.btn variant="project" disabled title="Objet de démonstration — aucune action réelle n’est rattachée.">Ouvrir le Cerveau ✦</x-dg.btn>
                                            <div style="display:flex;gap:8px;flex-wrap:wrap">
                                                <x-dg.btn variant="quiet" disabled title="Objet de démonstration — aucune action réelle n’est rattachée.">Trouver des capacités →</x-dg.btn>
                                                <x-dg.btn variant="quiet" disabled title="Objet de démonstration — aucune action réelle n’est rattachée.">Dossier →</x-dg.btn>
                                            </div>
                                        </x-dg.actions>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        @if($projects->hasMorePages())
                            <div class="dg-projects-more">
                                <a href="{{ $projects->nextPageUrl() }}" class="dg-btn dg-btn--quiet">Voir plus de projets ↓</a>
                            </div>
                        @endif
                    @endif
                </div>

                <aside class="dg-projects-aside">
                    <div class="dg-card dg-projects-community">
                        <x-dg.label tone="night">Aperçu de la communauté · Exemple</x-dg.label>
                        <div class="dg-projects-stat">
                            <span class="dg-projects-stat__mark" aria-hidden="true"><x-dg.icon name="rocket" size="19" /></span>
                            <div>
                                <span class="dg-projects-stat__value">{{ $communityStats['projets_proposes'] }}</span>
                                <div class="dg-projects-stat__label">Projets proposés</div>
                                <div class="dg-projects-stat__delta">{{ $communityStats['projets_proposes_delta'] }}</div>
                            </div>
                        </div>
                        <div class="dg-projects-stat">
                            <span class="dg-projects-stat__mark" aria-hidden="true"><x-dg.icon name="team" size="19" /></span>
                            <div>
                                <span class="dg-projects-stat__value">{{ $communityStats['membres_impliques'] }}</span>
                                <div class="dg-projects-stat__label">Membres impliqués</div>
                                <div class="dg-projects-stat__delta">{{ $communityStats['membres_impliques_delta'] }}</div>
                            </div>
                        </div>
                        <div class="dg-projects-stat">
                            <span class="dg-projects-stat__mark" aria-hidden="true"><x-dg.icon name="target" size="19" /></span>
                            <div>
                                <span class="dg-projects-stat__value">{{ $communityStats['projets_en_cours'] }}</span>
                                <div class="dg-projects-stat__label">Projets en cours</div>
                                <div class="dg-projects-stat__delta">{{ $communityStats['projets_en_cours_delta'] }}</div>
                            </div>
                        </div>
                        <div class="dg-projects-stat">
                            <span class="dg-projects-stat__mark" aria-hidden="true"><x-dg.icon name="check-circle" size="19" /></span>
                            <div>
                                <span class="dg-projects-stat__value">{{ $communityStats['projets_realises'] }}</span>
                                <div class="dg-projects-stat__label">Projets réalisés</div>
                                <div class="dg-projects-stat__delta">{{ $communityStats['projets_realises_delta'] }}</div>
                            </div>
                        </div>
                        <x-dg.btn variant="project" :href="route('projects.index')" style="justify-content:center;margin-top:12px">Voir tous les projets →</x-dg.btn>
                    </div>
                </aside>
            </div>

            <nav class="dg-projects-footer" aria-label="Comment participer au réseau Projets">
                <a class="dg-projects-footer__item" href="#dg-projects-toolbar">
                    <span class="dg-projects-footer__mark" aria-hidden="true"><x-dg.icon name="compass" size="19" /></span>
                    <strong>Explorez les domaines</strong>
                    <span>Parcourez les domaines d’action et trouvez l’inspiration.</span>
                </a>
                <a class="dg-projects-footer__item" href="{{ route('people.index') }}">
                    <span class="dg-projects-footer__mark" aria-hidden="true"><x-dg.icon name="team" size="19" /></span>
                    <strong>Trouvez des capacités</strong>
                    <span>Identifiez les personnes et compétences pour faire avancer votre projet.</span>
                </a>
                <a class="dg-projects-footer__item" href="{{ route('projects.brain.start') }}">
                    <span class="dg-projects-footer__mark" aria-hidden="true"><x-dg.icon name="star" size="19" /></span>
                    <strong>Passez à l’action</strong>
                    <span>Structurez, collaborez et suivez l’impact de votre projet.</span>
                </a>
                <a class="dg-projects-footer__item" href="{{ route('activity.index') }}">
                    <span class="dg-projects-footer__mark" aria-hidden="true">
                        <span class="dg-brandmark" aria-hidden="true" style="transform:scale(.7)"><span class="dg-brandmark__d">D</span><span class="dg-brandmark__g">G</span></span>
                    </span>
                    <strong>Ici, chaque idée compte</strong>
                    <span>Ensemble, transformons les idées en réalité.</span>
                </a>
            </nav>
        </div>
    </x-dg.shell>
</x-layouts.portal>
