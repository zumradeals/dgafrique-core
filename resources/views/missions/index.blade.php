{{--
    UX-HARMONY-MISSIONS-001 — implémentation fidèle de la maquette Missions (référence UX/produit
    officielle). Même famille visuelle que /besoins, /personnes et /projets. Le domaine n'existe pas
    comme colonne Mission : dérivé du contexte porteur (Project::domain / ZumraGroup::domain), les
    Missions issues d'un Besoin rejoignent honnêtement « Sans domaine identifié ». La priorité et les
    favoris n'ont aucun moteur métier actuel : la maquette conserve leur emplacement, désactivé et
    étiqueté « moteur à construire », plutôt que d'inventer une donnée. Le tableau de bord personnel
    CAP-069 (ancien /missions) reste entier sur /missions?scope=mine — voir missions/mine.blade.php.
--}}
@php
    $missionStatusLabels = \App\Models\Mission::STATUS_LABELS;
    $noFilters = $statusFilter === '' && $domainFilter === '' && $locationFilter === '' && $searchTerm === '';
    $contributorInitials = fn (int $n) => '★';
@endphp
<x-layouts.portal title="Missions — DG Afrique">
    <x-dg.shell :identity="$identity" :is-administrator="$isAdministrator">
        <div class="missions-page"><div class="missions-layout">

            <aside class="missions-left">
                <nav class="missions-nav">
                    <p>Découvrir les missions</p>
                    <a class="{{ $noFilters ? 'is-active' : '' }}" href="{{ route('missions.index') }}">⌂ <span>Vue d’ensemble</span></a>
                    <a href="#toutes">▤ <span>Toutes les missions</span></a>
                    <a href="#statuts">◔ <span>Par statut</span></a>
                    <a href="#domaines">◇ <span>Par domaine</span></a>
                    <a class="is-disabled" href="#" onclick="return false" title="UX présente — aucun champ de priorité n'existe encore sur la Mission (moteur à construire).">▲ <span>Par priorité</span></a>
                    <a href="#localisation">◷ <span>Par localisation</span></a>
                    <hr>
                    <a class="is-disabled" href="#" onclick="return false" title="Une Mission naît toujours d'un contexte (Projet, ZUMRA ou Besoin) : proposez-la depuis sa fiche.">＋ <span>Proposer une mission</span></a>
                </nav>
                <section>
                    <h2>Mes missions</h2>
                    <nav class="missions-nav" style="border:0;box-shadow:none;padding:8px 0 0">
                        <a href="{{ route('missions.index', ['scope' => 'mine']) }}">☑ <span>Assignées à moi</span><b>{{ $mineAssignedCount }}</b></a>
                        <a href="{{ route('missions.index', ['scope' => 'mine']) }}">◑ <span>Je contribue</span><b>{{ $mineEngagedCount }}</b></a>
                        <a class="is-disabled" href="#" onclick="return false" title="UX présente — moteur métier à construire.">☆ <span>Mes favoris</span><b>—</b></a>
                    </nav>
                </section>
                <section class="missions-cta">
                    <h2>Envie d’agir ?</h2>
                    <p>Chaque mission accomplie nous rapproche de notre impact collectif.</p>
                    <a href="{{ route('missions.index', ['scope' => 'mine']) }}">Voir mon tableau de bord →</a>
                </section>
            </aside>

            <main class="missions-main">
                <section class="missions-hero">
                    <div>
                        <p class="missions-kicker">Agir ensemble</p>
                        <h1>Des missions concrètes pour un impact réel.</h1>
                        <p>Découvrez les missions ouvertes, rejoignez une équipe, contribuez selon vos compétences et faites la différence.</p>
                        <div class="missions-hero-actions">
                            <x-dg.btn variant="saffron" :href="route('missions.index', ['status' => 'OPEN'])">Voir les missions ouvertes →</x-dg.btn>
                            <a class="missions-how" href="#comment-ca-marche">▷ Comment ça marche ?</a>
                        </div>
                    </div>
                    <div class="missions-hero-art" aria-hidden="true"><i>♧</i><i>◑</i><i>▤</i></div>
                </section>

                <section class="missions-metrics" aria-label="Chiffres réels des missions">
                    <article>
                        <i>▤</i>
                        <span><b>Missions ouvertes</b><strong>{{ number_format($bandeau['open'], 0, ',', ' ') }}</strong><small>prêtes à être rejointes</small></span>
                    </article>
                    <article>
                        <i>◑</i>
                        <span><b>En cours</b><strong>{{ number_format($bandeau['in_progress'], 0, ',', ' ') }}</strong><small>en progression active</small></span>
                    </article>
                    <article>
                        <i>✓</i>
                        <span><b>Terminées</b><strong>{{ number_format($bandeau['completed'], 0, ',', ' ') }}</strong><small>avec succès</small></span>
                    </article>
                    <article>
                        <i>♧</i>
                        <span><b>Contributeurs actifs</b><strong>{{ number_format($contributorsThisMonth, 0, ',', ' ') }}</strong><small>ce mois-ci</small></span>
                    </article>
                    <article class="is-disabled" title="UX présente — aucun moteur de mesure d'impact n'existe encore. CAP-069 interdit explicitement d'inventer un score de productivité.">
                        <i>◎</i>
                        <span><b>Taux d’impact</b><strong>—</strong><small>moteur à construire</small></span>
                    </article>
                </section>

                <nav class="missions-tabs">
                    <a class="{{ $noFilters ? 'is-active' : '' }}" href="{{ route('missions.index') }}">Toutes</a>
                    <a class="{{ $statusFilter === 'OPEN' ? 'is-active' : '' }}" href="{{ route('missions.index', ['status' => 'OPEN']) }}">Ouvertes</a>
                    <a class="{{ $statusFilter === 'IN_PROGRESS' ? 'is-active' : '' }}" href="{{ route('missions.index', ['status' => 'IN_PROGRESS']) }}">En cours</a>
                    <a class="{{ $statusFilter === 'COMPLETED' ? 'is-active' : '' }}" href="{{ route('missions.index', ['status' => 'COMPLETED']) }}">Terminées</a>
                    <a class="{{ $statusFilter === 'BLOCKED' ? 'is-active' : '' }}" href="{{ route('missions.index', ['status' => 'BLOCKED']) }}">Bloquées</a>
                </nav>

                <section class="missions-section" id="toutes">
                    <header>
                        <p>{{ $missionsPage->total() }} {{ \Illuminate\Support\Str::plural('mission trouvée', $missionsPage->total()) }}</p>
                        <span>Triées par date récente</span>
                    </header>

                    @if($missionsPage->isEmpty())
                        <div class="missions-empty">
                            <strong>Aucune mission visible ici</strong>
                            <p>Une mission naît toujours d’un contexte réel — Projet, ZUMRA ou Besoin — et apparaîtra ici dès qu’elle sera ouverte ou en cours.</p>
                        </div>
                    @else
                        <div class="missions-cards">
                            @foreach($missionsPage as $mission)
                                @php($stats = $checklistStats->get($mission->id))
                                @php($contributors = $contributorCounts->get($mission->id, 0))
                                <article class="mission-card">
                                    <div class="mission-card__head">
                                        <x-dg.badge tone="action">{{ $domainOf($mission) }}</x-dg.badge>
                                        <span class="mission-card__state" data-status="{{ $mission->status }}"><i aria-hidden="true"></i> {{ $missionStatusLabels[$mission->status] ?? $mission->status }}</span>
                                    </div>
                                    <h2 class="mission-card__title"><a href="{{ route('missions.show', $mission) }}">{{ $mission->title }}</a></h2>
                                    <p class="dg-body" style="max-width:none;margin:0">{{ \Illuminate\Support\Str::limit($mission->description, 170) }}</p>
                                    <div class="mission-card__meta">
                                        @if($mission->location)<span><x-dg.icon name="target" size="13" /> {{ $mission->location }}</span>@endif
                                        <span><x-dg.icon name="calendar" size="13" /> {{ $mission->created_at->diffForHumans() }}</span>
                                    </div>
                                    <div class="mission-card__body">
                                        <div class="mission-card__progress {{ $stats ? '' : 'is-disabled' }}">
                                            <b>Progression</b>
                                            @if($stats && $stats['total'] > 0)
                                                @php($pct = (int) round($stats['completed'] / $stats['total'] * 100))
                                                <strong>{{ $pct }}%</strong>
                                                <div class="mission-progress-bar"><i style="--value:{{ $pct }}%"></i></div>
                                                <small>{{ $stats['completed'] }}/{{ $stats['total'] }} étapes complétées</small>
                                            @else
                                                <strong>—</strong>
                                                <small>Aucune étape définie pour cette mission</small>
                                            @endif
                                        </div>
                                        <div class="mission-card__due">
                                            <b>Fin prévue</b>
                                            {{ $mission->due_at?->locale('fr')->isoFormat('D MMM YYYY') ?? 'Non renseignée' }}
                                        </div>
                                        <div class="mission-card__contributors">
                                            <div>
                                                <b>Contributeurs</b>
                                                <div class="mission-avatars" aria-hidden="true">
                                                    @for($i = 0; $i < min(4, $contributors); $i++)<i>{{ $contributorInitials($i) }}</i>@endfor
                                                    @if($contributors > 4)<i>+{{ $contributors - 4 }}</i>@endif
                                                    @if($contributors === 0)<i>0</i>@endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mission-card__foot">
                                        <x-dg.btn variant="quiet" :href="route('missions.show', $mission)">Voir la mission →</x-dg.btn>
                                        <button type="button" class="mission-card__save" disabled title="La sauvegarde de missions favorites arrivera prochainement." aria-label="Sauvegarder cette mission (bientôt disponible)"><x-dg.icon name="bookmark" size="16" /></button>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                        <div class="missions-pagination">{{ $missionsPage->links('pagination.dg') }}</div>
                    @endif
                </section>
            </main>

            <aside class="missions-right">
                <section id="statuts">
                    <header><h2>Missions par statut</h2></header>
                    <div class="missions-repartition">
                        @foreach(['open' => 'Ouvertes', 'in_progress' => 'En cours', 'completed' => 'Terminées', 'blocked' => 'Bloquées'] as $key => $label)
                            <a class="missions-repartition-row" href="{{ route('missions.index', ['status' => strtoupper($key === 'in_progress' ? 'IN_PROGRESS' : $key)]) }}" style="text-decoration:none;color:inherit">
                                <span>{{ $label }}</span><span>{{ $bandeau[$key] }}</span>
                                <i style="--value: {{ $bandeau['total'] > 0 ? min(100, round($bandeau[$key] / $bandeau['total'] * 100)) : 0 }}%"></i>
                            </a>
                        @endforeach
                    </div>
                </section>
                <section id="domaines">
                    <header><h2>Répartition par domaine</h2></header>
                    <div class="missions-repartition">
                        @forelse($byDomain->take(6) as $label => $count)
                            <a class="missions-repartition-row" href="{{ route('missions.index', ['domain' => $label]) }}" style="text-decoration:none;color:inherit">
                                <span>{{ $label }}</span><span>{{ $count }}</span>
                                <i style="--value: {{ $bandeau['total'] > 0 ? min(100, round($count / $bandeau['total'] * 100)) : 0 }}%"></i>
                            </a>
                        @empty
                            <p class="dg-meta">Aucune mission visible pour le moment.</p>
                        @endforelse
                    </div>
                </section>
                <section id="localisation">
                    <header><h2>Répartition par localisation</h2></header>
                    <div class="missions-repartition">
                        @forelse($byLocation->take(6) as $label => $count)
                            <a class="missions-repartition-row" href="{{ route('missions.index', ['location' => $label]) }}" style="text-decoration:none;color:inherit">
                                <span>{{ $label }}</span><span>{{ $count }}</span>
                                <i style="--value: {{ $bandeau['total'] > 0 ? min(100, round($count / $bandeau['total'] * 100)) : 0 }}%"></i>
                            </a>
                        @empty
                            <p class="dg-meta">Aucune localisation renseignée pour le moment.</p>
                        @endforelse
                    </div>
                </section>
                <section>
                    <header><h2>Échéances proches</h2></header>
                    <div class="missions-due-list">
                        @forelse($dueSoon->take(3) as $mission)
                            <a href="{{ route('missions.show', $mission) }}">
                                <i>◷</i>
                                <span><b>{{ \Illuminate\Support\Str::limit($mission->title, 46) }}</b><small>{{ $mission->due_at->locale('fr')->isoFormat('D MMMM') }}</small></span>
                            </a>
                        @empty
                            <p class="dg-meta">Aucune échéance dans les 7 prochains jours.</p>
                        @endforelse
                    </div>
                </section>
                <section class="missions-cta">
                    <h2>Vous avez des compétences ?</h2>
                    <p>Rejoignez une mission adaptée et contribuez là où vous pouvez apporter le plus.</p>
                    <a href="{{ route('missions.index', ['status' => 'OPEN']) }}">Explorer les missions →</a>
                </section>
            </aside>

        </div>

        <div class="missions-approach" id="comment-ca-marche">
            <div>
                <h2>Comment fonctionne une Mission ?</h2>
                <p>Proposer n’est pas décider, accepter n’est pas commencer, soumettre n’est pas valider — chaque étape reste une décision humaine explicite.</p>
            </div>
            <div class="missions-approach-step"><i>1</i><b>Proposer</b><small>Une intention claire, rattachée à un Projet, une ZUMRA ou un Besoin.</small></div>
            <div class="missions-approach-step"><i>2</i><b>Décider</b><small>L’autorité du contexte officialise ou renvoie la proposition.</small></div>
            <div class="missions-approach-step"><i>3</i><b>Réaliser</b><small>Les exécutants acceptés font avancer la mission.</small></div>
            <div class="missions-approach-step"><i>4</i><b>Soumettre</b><small>Un résultat concret est transmis pour examen.</small></div>
            <div class="missions-approach-step"><i>5</i><b>Valider</b><small>Soumis ne veut pas dire validé : une décision explicite clôt la mission.</small></div>
        </div>
        </div>

        <script>document.querySelector('[data-missions-filters]')?.addEventListener('click',()=>document.querySelector('.missions-filter-fields')?.classList.toggle('is-open'))</script>
    </x-dg.shell>
</x-layouts.portal>
