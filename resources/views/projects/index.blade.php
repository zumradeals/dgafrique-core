<x-layouts.member title="Projets" active="projects">
@php
    $featuredProjects = $projects->getCollection()->take(4);
    $statusLabels = [
        \App\Models\Project::STATUS_PROPOSED => 'Nouveau',
        \App\Models\Project::STATUS_ADOPTED => 'Adopté',
        \App\Models\Project::STATUS_IN_PROGRESS => 'En cours',
        \App\Models\Project::STATUS_COMPLETED => 'Terminé',
    ];
@endphp
<div class="project-hub">
    <div class="project-hub__layout">
        <aside class="project-hub__rail" aria-label="Navigation du Carrefour Projets">
            <section class="project-hub__panel">
                <nav class="project-hub__nav">
                    <a class="is-active" href="{{ route('projects.index') }}">▣ Carrefour Projets</a>
                    <a href="#projets-vedette">◉ Découvrir</a>
                    <a href="{{ route('projects.index', ['status' => \App\Models\Project::STATUS_IN_PROGRESS]) }}">▤ Projets en cours</a>
                    <a href="{{ route('projects.index', ['status' => \App\Models\Project::STATUS_COMPLETED]) }}">✓ Projets finalisés</a>
                    <a href="#domaines">◎ Domaines d'action</a>
                    <a href="#territoires">⌖ Territoires</a>
                    <a href="{{ route('needs.index') }}">♡ Besoins liés</a>
                </nav>
                <a class="project-hub__primary" href="{{ route('projects.create') }}">＋ Proposer un projet</a>
            </section>
            <section class="project-hub__panel">
                <p class="project-hub__rail-title">Explorer par</p>
                <nav class="project-hub__nav">
                    <a href="#domaines">Domaines d'action</a>
                    <a href="#territoires">Territoires</a>
                    <a href="#projets-vedette">ZUMRA porteuses</a>
                    <a href="{{ route('projects.index', ['status' => \App\Models\Project::STATUS_PROPOSED]) }}">Nouveaux projets</a>
                </nav>
            </section>
            <section class="project-hub__panel">
                <strong>Vous avez une idée ?</strong>
                <p style="margin:.35rem 0 .7rem;color:#668096;font-size:.82rem">Un projet GAMAD se construit dans un cadre ZUMRA et progresse par étapes.</p>
                <a class="project-hub__primary" href="{{ route('projects.create') }}">Commencer</a>
            </section>
        </aside>

        <main class="project-hub__main">
            <section class="project-hub__hero">
                <div class="project-hub__hero-copy">
                    <p class="project-hub__eyebrow">CARREFOUR PROJETS · GAMAD</p>
                    <h1>Des idées qui deviennent des actions. Des actions qui produisent de l'impact.</h1>
                    <p class="project-hub__hero-lead">Découvrez et rejoignez des projets portés par des ZUMRA. Suivez leur progression, leurs besoins et les opportunités concrètes de contribution.</p>
                    <form class="project-hub__search" method="GET" action="{{ route('projects.index') }}">
                        <input id="q" name="q" value="{{ request('q') }}" maxlength="120" placeholder="Rechercher un projet (mot-clé, domaine, territoire…)" aria-label="Rechercher un projet">
                        <button type="submit">Rechercher</button>
                    </form>
                    <div class="project-hub__chips" aria-label="Domaines de projet">
                        @foreach (array_slice($configuration['domains'], 0, 7, true) as $code => $label)
                            <a class="project-hub__chip" href="{{ route('projects.index', ['domain' => $code]) }}">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
                <div class="project-hub__hero-art" aria-hidden="true">
                    <p class="project-hub__hero-quote">Agir pour des territoires plus forts.</p>
                </div>
            </section>

            <section class="project-hub__stats" aria-label="Indicateurs du réseau projets">
                <article class="project-hub__stat"><span class="project-hub__stat-icon">▱</span><div><strong>{{ number_format($networkStats['projects'], 0, ',', ' ') }}</strong><small>projets en mouvement</small></div></article>
                <article class="project-hub__stat"><span class="project-hub__stat-icon">◉</span><div><strong>{{ number_format($networkStats['groups'], 0, ',', ' ') }}</strong><small>ZUMRA mobilisées</small></div></article>
                <article class="project-hub__stat"><span class="project-hub__stat-icon">✓</span><div><strong>{{ number_format($networkStats['completed'], 0, ',', ' ') }}</strong><small>projets finalisés</small></div></article>
                <article class="project-hub__stat"><span class="project-hub__stat-icon">♙</span><div><strong>{{ number_format($networkStats['members'], 0, ',', ' ') }}</strong><small>participants actifs</small></div></article>
            </section>

            <section id="projets-vedette" class="project-hub__section">
                <div class="project-hub__section-head">
                    <div><h2>Projets en vedette</h2><p>Des projets visibles du réseau, présentés avec leur progression réelle.</p></div>
                    <a class="project-hub__link" href="{{ route('projects.index') }}">Voir tous les projets →</a>
                </div>
                @if ($featuredProjects->isNotEmpty())
                    <div class="project-hub__cards">
                        @foreach ($featuredProjects as $project)
                            @php
                                $card = $cards[$project->id] ?? null;
                                $group = $groups[$project->zumra_group_id] ?? null;
                                $progress = $card['progress'] ?? null;
                            @endphp
                            <article class="project-card">
                                <div class="project-card__visual" @if($project->image_path) style="background-image:url('{{ asset('storage/'.$project->image_path) }}')" @endif>
                                    <span class="project-card__status">{{ $card['display_status'] ?? ($statusLabels[$project->status] ?? $project->status) }}</span>
                                </div>
                                <div class="project-card__body">
                                    <h3>{{ $project->name }}</h3>
                                    <p class="project-card__summary">{{ $project->summary }}</p>
                                    <div class="project-card__meta">
                                        @if ($project->location)<span>⌖ {{ $project->location }}</span>@endif
                                        <span>◎ <strong>{{ $configuration['domains'][$project->domain] ?? $project->domain }}</strong></span>
                                        @if ($group)<span>▣ {{ $group->name }}</span>@endif
                                    </div>
                                    <div class="project-card__progress">
                                        <div class="project-card__bar"><span style="width:{{ $progress ?? 0 }}%"></span></div>
                                        <small>{{ $progress === null ? 'Non mesuré' : $progress.'%' }}</small>
                                    </div>
                                    <div class="project-card__footer"><span>♙ {{ $card['members'] ?? 0 }} membre(s)</span><span>{{ $card['progress_label'] ?? '' }}</span></div>
                                    <a class="project-card__cta" href="{{ route('projects.show', $project) }}">Voir le projet</a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="project-hub__empty"><strong>Aucun projet à afficher pour cette recherche.</strong><p>Essayez un autre filtre ou découvrez les besoins du réseau.</p></div>
                @endif
                <div class="project-hub__pagination">{{ $projects->links() }}</div>
            </section>

            <section id="domaines" class="project-hub__section">
                <div class="project-hub__section-head"><div><h2>Explorer par domaine d'action</h2><p>Trouvez les projets selon leur centre d'intérêt.</p></div></div>
                <div class="project-hub__domains">
                    @forelse ($categoryDistribution as $domain)
                        <a class="project-hub__domain" href="{{ route('projects.index', ['domain' => $domain['code']]) }}"><strong>{{ $domain['label'] }}</strong><small>{{ $domain['count'] }} projet(s)</small></a>
                    @empty
                        @foreach ($configuration['domains'] as $code => $label)
                            <a class="project-hub__domain" href="{{ route('projects.index', ['domain' => $code]) }}"><strong>{{ $label }}</strong><small>Explorer</small></a>
                        @endforeach
                    @endforelse
                </div>
            </section>

            <section id="territoires" class="project-hub__section">
                <div class="project-hub__section-head"><div><h2>Explorer par territoire</h2><p>Découvrez les projets là où ils prennent vie.</p></div></div>
                <div class="project-hub__territories">
                    @forelse ($filterLocations->take(7) as $location)
                        <a class="project-hub__territory" href="{{ route('projects.index', ['country' => $location]) }}">{{ $location }}</a>
                    @empty
                        <div class="project-hub__empty">Les territoires apparaîtront à mesure que les projets seront localisés.</div>
                    @endforelse
                </div>
            </section>

            <section class="project-hub__pedagogy">
                <div><h2>Un projet n'est pas un besoin.</h2><p>Un besoin décrit un manque. Un projet organise une réponse structurée avec une ZUMRA, des objectifs, des étapes, des personnes et des résultats observables.</p></div>
                <div class="project-hub__principle"><strong>Des projets réels</strong><p>Chaque projet est ancré dans une ZUMRA et repose sur les règles métier existantes.</p></div>
                <div class="project-hub__principle"><strong>Des opportunités de collaboration</strong><p>Compétences, ressources, missions et besoins peuvent converger autour d'une action.</p></div>
                <div class="project-hub__principle"><strong>Un impact mesurable</strong><p>La progression repose sur les jalons réellement définis, jamais sur un score inventé.</p></div>
            </section>
        </main>

        <aside class="project-hub__aside">
            <section class="project-hub__aside-card project-hub__aside-card--blue">
                <h2>Vous portez un projet ?</h2>
                <p>Le parcours de création vérifie automatiquement votre cadre ZUMRA avant de créer quoi que ce soit.</p>
                <a class="project-hub__primary project-hub__primary--yellow" href="{{ route('projects.create') }}">Proposer un projet →</a>
            </section>
            <section class="project-hub__aside-card">
                <h2>Comment ça marche ?</h2>
                <div class="project-hub__steps">
                    <div class="project-hub__step"><b>1</b><div><strong>Découvrez</strong><small>Explorez les projets par domaine ou territoire.</small></div></div>
                    <div class="project-hub__step"><b>2</b><div><strong>Rejoignez ou soutenez</strong><small>Consultez les besoins, missions et possibilités réellement ouvertes.</small></div></div>
                    <div class="project-hub__step"><b>3</b><div><strong>Suivez l'impact</strong><small>Observez les jalons, l'activité et les résultats du projet.</small></div></div>
                </div>
            </section>
            <section class="project-hub__aside-card">
                <div class="project-hub__section-head"><h2>Projets récents</h2><a class="project-hub__link" href="{{ route('projects.index') }}">Voir tous →</a></div>
                <div class="project-hub__recent">
                    @forelse ($recentProjects as $project)
                        @php $recentGroup = $recentGroups[$project->zumra_group_id] ?? null; @endphp
                        <a href="{{ route('projects.show', $project) }}">
                            <span class="project-hub__recent-thumb" @if($project->image_path) style="background-image:url('{{ asset('storage/'.$project->image_path) }}')" @endif></span>
                            <span><strong>{{ $project->name }}</strong><small>{{ $project->location ?: ($recentGroup?->name ?? 'Projet GAMAD') }}</small></span>
                        </a>
                    @empty
                        <p>Aucun projet récent visible.</p>
                    @endforelse
                </div>
            </section>
            <section class="project-hub__aside-card project-hub__quote">
                <blockquote>« Un projet bien accompagné peut transformer des vies. »</blockquote>
                <p>Des idées structurées, des personnes engagées et des preuves d'avancement.</p>
            </section>
        </aside>
    </div>
</div>
</x-layouts.member>
