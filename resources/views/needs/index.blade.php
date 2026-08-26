{{--
    UX-HARMONY-BESOINS-001 — implémentation fidèle de la maquette Besoins (référence UX/produit
    officielle). Même famille visuelle que /personnes et /projets. Le financement des Besoins n'existe
    pas dans le domaine Need (aucune colonne, aucun moteur) : les emplacements correspondants de la
    maquette (« Besoins financés », « Montant engagé », onglet « Finançables ») restent visibles mais
    désactivés et honnêtement étiquetés « moteur à construire », plutôt que d'afficher un chiffre
    fabriqué — voir le rapport de livraison. Ne pas confondre avec PROJECT-FUNDING-002 (financement
    de Projet, objet métier distinct) ni CONTRIBUTION-ZAHAB-001 (contribution ZUMRA).
--}}
@php
    $needStatusLabels = ['PROPOSED' => 'Proposé', 'OPEN' => 'Ouvert', 'IN_PROGRESS' => 'En cours', 'RESOLVED' => 'Résolu'];
    $ownerLabel = fn ($need) => match ($need->owner_type) { 'GROUP' => $groups->get($need->owner_reference)?->name ?? 'ZUMRA', 'PROJECT' => $projects->get($need->owner_reference)?->name ?? 'Projet', default => 'Besoin personnel' };
    $isUrgent = fn ($need) => in_array($need->status, ['OPEN', 'IN_PROGRESS'], true) && $need->created_at->lte(now()->subDays(30));
    $noFilters = ! $categoryFilter && ! $statusFilter && ! $urgentOnly && ! $mineOnly && $searchTerm === '';
@endphp
<x-layouts.portal title="Besoins — DG Afrique">
    <x-dg.shell current="besoins" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="needs-page"><div class="needs-layout">

            <aside class="needs-left">
                <nav class="needs-nav">
                    <p>Découvrir les besoins</p>
                    <a class="{{ $noFilters ? 'is-active' : '' }}" href="{{ route('needs.index') }}">⌂ <span>Vue d’ensemble</span></a>
                    <a href="#tous">▤ <span>Tous les besoins</span></a>
                    <a href="#categories">◇ <span>Par catégorie</span></a>
                    <a href="#localisation">◷ <span>Par localisation</span></a>
                    <a class="{{ $urgentOnly ? 'is-active' : '' }}" href="{{ route('needs.index', ['urgent' => 1]) }}">♥ <span>Besoins urgents</span><b>{{ $bandeau['urgent'] }}</b></a>
                    <a class="is-disabled" href="#" onclick="return false" title="UX présente — moteur de financement des Besoins à construire (distinct du financement de Projet).">◈ <span>Besoins financés</span></a>
                    <a class="is-disabled" href="#" onclick="return false" title="UX présente — moteur métier à construire.">☆ <span>Mes suivis</span></a>
                    <hr>
                    <a href="{{ route('needs.create') }}">＋ <span>Créer un besoin</span></a>
                </nav>
                <section>
                    <h2>Mes actions</h2>
                    <nav class="needs-nav" style="border:0;box-shadow:none;padding:8px 0 0">
                        <a class="is-disabled" href="#" onclick="return false" title="UX présente — moteur métier à construire.">✎ <span>Brouillons</span><b>—</b></a>
                        <a class="is-disabled" href="#" onclick="return false" title="UX présente — moteur métier à construire.">◑ <span>Mes contributions</span></a>
                        <a class="{{ $mineOnly ? 'is-active' : '' }}" href="{{ route('needs.index', ['mine' => 1]) }}">▣ <span>Mes déclarations</span><b>{{ $mineCount }}</b></a>
                    </nav>
                </section>
                <section class="needs-cta">
                    <h2>Un besoin à déclarer ?</h2>
                    <p>Décrivez clairement le besoin, l’impact attendu et les ressources nécessaires.</p>
                    <a href="{{ route('needs.create') }}">Déclarer un besoin →</a>
                </section>
                <section>
                    <h2>Comment ça marche ?</h2>
                    <p>Comprenez le processus, les critères et les règles de publication.</p>
                    <a href="#approche">Voir le guide →</a>
                </section>
            </aside>

            <main class="needs-main">
                <section class="needs-hero">
                    <div>
                        <p class="needs-kicker">Répondre aux vrais besoins</p>
                        <h1>{{ $configuration['directory_title'] }}</h1>
                        <p>{{ $configuration['directory_intro'] }}</p>
                    </div>
                    <div class="needs-hero-art" aria-hidden="true"><i>♥</i><i>✓</i><i>◇</i></div>
                    <form method="GET" action="{{ route('needs.index') }}" class="dg-filters">
                        <label><span>⌕</span><input name="q" value="{{ $searchTerm }}" maxlength="120" placeholder="Rechercher un besoin, une catégorie, un lieu…"></label>
                        <button type="button" data-needs-filters>☷ Filtres</button>
                        <button class="needs-submit-sr" type="submit">Rechercher</button>
                        <div class="needs-filter-fields" id="filtres">
                            <label>Catégorie
                                <select name="category">
                                    <option value="">Toutes les catégories</option>
                                    @foreach($configuration['categories'] as $code => $label)
                                        <option value="{{ $code }}" @selected($categoryFilter === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>État
                                <select name="status">
                                    <option value="">Tous les états</option>
                                    <option value="OPEN" @selected($statusFilter === 'OPEN')>Ouvert</option>
                                    <option value="IN_PROGRESS" @selected($statusFilter === 'IN_PROGRESS')>En cours</option>
                                    <option value="RESOLVED" @selected($statusFilter === 'RESOLVED')>Résolu</option>
                                </select>
                            </label>
                            <button type="submit">Appliquer les filtres</button>
                        </div>
                    </form>
                </section>

                <section class="needs-metrics" aria-label="Chiffres réels des besoins">
                    <article>
                        <i>▤</i>
                        <span><b>Tous les besoins</b><strong>{{ number_format($bandeau['total'], 0, ',', ' ') }}</strong><small>besoins déclarés</small></span>
                    </article>
                    <article>
                        <i>♥</i>
                        <span><b>Besoins urgents</b><strong>{{ number_format($bandeau['urgent'], 0, ',', ' ') }}</strong><small>nécessitent attention</small></span>
                    </article>
                    <article class="is-disabled" title="UX présente — moteur de financement des Besoins à construire.">
                        <i>◈</i>
                        <span><b>Besoins financés</b><strong>—</strong><small>moteur à construire</small></span>
                    </article>
                    <article>
                        <i>✓</i>
                        <span><b>Besoins satisfaits</b><strong>{{ number_format($bandeau['resolved'], 0, ',', ' ') }}</strong><small>impact réalisé</small></span>
                    </article>
                    <article class="is-disabled" title="Le financement se fait au niveau Projet (PROJECT-FUNDING-002) ou ZUMRA, jamais au niveau Besoin — moteur à construire.">
                        <i>$</i>
                        <span><b>Montant engagé</b><strong>—</strong><small>moteur à construire</small></span>
                    </article>
                </section>

                <nav class="needs-tabs">
                    <a class="{{ $noFilters ? 'is-active' : '' }}" href="{{ route('needs.index') }}">Tous</a>
                    <a class="{{ $urgentOnly ? 'is-active' : '' }}" href="{{ route('needs.index', ['urgent' => 1]) }}">Urgents</a>
                    <a class="is-disabled" href="#" onclick="return false" title="UX présente — moteur de financement des Besoins à construire.">Finançables</a>
                    <a class="{{ $statusFilter === 'IN_PROGRESS' ? 'is-active' : '' }}" href="{{ route('needs.index', ['status' => 'IN_PROGRESS']) }}">En cours</a>
                    <a class="{{ $statusFilter === 'RESOLVED' ? 'is-active' : '' }}" href="{{ route('needs.index', ['status' => 'RESOLVED']) }}">Satisfaits</a>
                </nav>

                <section class="needs-section" id="tous">
                    <header>
                        <p>{{ $needs->total() + $demoCards->count() }} {{ \Illuminate\Support\Str::plural('besoin trouvé', $needs->total() + $demoCards->count()) }}</p>
                        <span>Triés par date récente</span>
                    </header>

                    @if($needs->isEmpty() && $demoCards->isEmpty())
                        <div class="needs-empty">
                            <strong>Aucun besoin visible ici</strong>
                            <p>Un besoin réel et bien contextualisé pourra devenir un point de rencontre entre capacités.</p>
                        </div>
                    @else
                        <div class="needs-cards">
                            @foreach($needs as $need)
                                <article class="need-card">
                                    <div class="need-card__head">
                                        <x-dg.badge tone="need">{{ $configuration['categories'][$need->category] ?? $need->category }}</x-dg.badge>
                                        <div style="display:flex;align-items:center;gap:8px">
                                            @if($isUrgent($need))<span class="need-card__urgent">♥ Urgent</span>@endif
                                            <span class="need-card__state" data-status="{{ $need->status }}"><i aria-hidden="true"></i> {{ $needStatusLabels[$need->status] ?? $need->status }}</span>
                                        </div>
                                    </div>
                                    <h2 class="need-card__title"><a href="{{ route('needs.show', $need) }}">{{ $need->title }}</a></h2>
                                    <p class="dg-body" style="max-width:none;margin:0">{{ \Illuminate\Support\Str::limit($need->context, 170) }}</p>
                                    <div class="need-card__meta">
                                        <span><x-dg.icon name="team" size="13" /> {{ $ownerLabel($need) }}</span>
                                        @if($need->location)<span><x-dg.icon name="target" size="13" /> {{ $need->location }}</span>@endif
                                        <span><x-dg.icon name="calendar" size="13" /> {{ $need->created_at->diffForHumans() }}</span>
                                    </div>
                                    @if($need->capability_label)
                                        <div class="need-card__tags"><span class="need-card__tag">{{ $need->capability_label }}</span></div>
                                    @endif
                                    <div class="need-card__foot">
                                        <x-dg.btn variant="quiet" :href="route('needs.show', $need)">Comprendre le besoin →</x-dg.btn>
                                        <button type="button" class="need-card__save" disabled title="La sauvegarde de besoins favoris arrivera prochainement." aria-label="Sauvegarder ce besoin (bientôt disponible)"><x-dg.icon name="bookmark" size="16" /></button>
                                    </div>
                                </article>
                            @endforeach

                            @foreach($demoCards as $card)
                                <article class="need-card">
                                    <div class="need-card__head">
                                        <x-dg.badge tone="need">{{ $configuration['categories'][$card['category']] ?? $card['category'] }} · Exemple</x-dg.badge>
                                        <span class="need-card__state" data-status="OPEN"><i aria-hidden="true"></i> Ouvert</span>
                                    </div>
                                    <h2 class="need-card__title">{{ $card['title'] }}</h2>
                                    <p class="dg-body" style="max-width:none;margin:0">{{ $card['context'] }}</p>
                                    <div class="need-card__meta">
                                        <span><x-dg.icon name="team" size="13" /> {{ $card['owner_label'] }}</span>
                                        <span><x-dg.icon name="target" size="13" /> {{ $card['location'] }}</span>
                                        <span><x-dg.icon name="calendar" size="13" /> {{ $card['created_label'] }}</span>
                                    </div>
                                    <div class="need-card__tags">
                                        @foreach($card['tags'] as $tag)
                                            <span class="need-card__tag">{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                    <div class="need-card__foot">
                                        <x-dg.btn variant="quiet" disabled title="Objet de démonstration — aucune action réelle n’est rattachée.">Comprendre le besoin →</x-dg.btn>
                                        <button type="button" class="need-card__save" disabled title="Objet de démonstration — aucune action réelle n’est rattachée." aria-label="Sauvegarder ce besoin (démonstration)"><x-dg.icon name="bookmark" size="16" /></button>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                        <div class="needs-pagination">{{ $needs->links('pagination.dg') }}</div>
                    @endif
                </section>
            </main>

            <aside class="needs-right">
                <section id="categories">
                    <header><h2>Répartition par catégorie</h2></header>
                    <div class="needs-repartition">
                        @forelse($byCategory as $row)
                            <a class="needs-repartition-row" href="{{ route('needs.index', ['category' => array_search($row['label'], $configuration['categories'], true)]) }}" style="text-decoration:none;color:inherit">
                                <span>{{ $row['label'] }}</span><span>{{ $row['count'] }}</span>
                                <i style="--value: {{ $bandeau['total'] > 0 ? min(100, round($row['count'] / $bandeau['total'] * 100)) : 0 }}%"></i>
                            </a>
                        @empty
                            <p class="dg-meta">Aucun besoin visible pour le moment.</p>
                        @endforelse
                    </div>
                </section>
                <section id="localisation">
                    <header><h2>Répartition par localisation</h2></header>
                    <div class="needs-repartition">
                        @forelse($byLocation as $row)
                            <a class="needs-repartition-row" href="{{ route('needs.index', ['q' => $row['label']]) }}" style="text-decoration:none;color:inherit">
                                <span>{{ $row['label'] }}</span><span>{{ $row['count'] }}</span>
                                <i style="--value: {{ $bandeau['total'] > 0 ? min(100, round($row['count'] / $bandeau['total'] * 100)) : 0 }}%"></i>
                            </a>
                        @empty
                            <p class="dg-meta">Aucune localisation renseignée pour le moment.</p>
                        @endforelse
                    </div>
                </section>
                <section>
                    <header><h2>Besoins urgents</h2></header>
                    <div class="needs-urgent-list">
                        @forelse($urgentNeeds->take(3) as $need)
                            <a href="{{ route('needs.show', $need) }}">
                                <i>♥</i>
                                <span><b>{{ \Illuminate\Support\Str::limit($need->title, 46) }}</b><small>{{ $need->location ?: $ownerLabel($need) }}</small></span>
                            </a>
                        @empty
                            <p class="dg-meta">Aucun besoin ouvert depuis plus de 30 jours en ce moment.</p>
                        @endforelse
                    </div>
                    @if($urgentNeeds->isNotEmpty())
                        <a href="{{ route('needs.index', ['urgent' => 1]) }}" style="display:block;margin-top:10px">Voir tout →</a>
                    @endif
                </section>
                <section class="needs-cta">
                    <h2>Contribuez à l’impact</h2>
                    <p>Chaque réponse à un besoin réel, petite ou grande, fait avancer nos communautés.</p>
                    <a href="{{ route('needs.index') }}">Explorer les besoins →</a>
                </section>
            </aside>

        </div>

        <div class="needs-approach" id="approche">
            <div>
                <h2>Notre approche des besoins</h2>
                <p>Nous nous assurons qu’un besoin déclaré soit utile, réellement contextualisé et suivi jusqu’à sa résolution.</p>
            </div>
            <div class="needs-approach-step"><i>1</i><b>Déclarer</b><small>Un besoin clair et documenté.</small></div>
            <div class="needs-approach-step"><i>2</i><b>Évaluer</b><small>Vérification, contexte et faisabilité.</small></div>
            <div class="needs-approach-step"><i>3</i><b>Prioriser</b><small>Selon urgence, impact et ressources.</small></div>
            <div class="needs-approach-step"><i>4</i><b>Connecter aux ressources</b><small>Via une ZUMRA, un Projet ou une capacité disponible.</small></div>
            <div class="needs-approach-step"><i>5</i><b>Suivre & réaliser</b><small>Jusqu’à la résolution effective du besoin.</small></div>
        </div>

        <div class="needs-approach" style="margin-top:16px;padding:18px 22px">
            <div style="margin:0"><p style="margin:0">Vous ne trouvez pas le besoin qu’il vous faut ? <a href="{{ route('needs.create') }}">Déclarer un nouveau besoin →</a></p></div>
        </div>
        </div>

        <script>document.querySelector('[data-needs-filters]')?.addEventListener('click',()=>document.querySelector('.needs-filter-fields')?.classList.toggle('is-open'))</script>
    </x-dg.shell>
</x-layouts.portal>
