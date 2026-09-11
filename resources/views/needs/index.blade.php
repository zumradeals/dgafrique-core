<x-layouts.member title="Besoins" active="needs">
@php
    $helpableNeeds = $needs->getCollection()
        ->filter(fn ($item) => in_array($item->status, [\App\Models\Need::STATUS_OPEN, \App\Models\Need::STATUS_IN_PROGRESS], true) && $item->author_core_reference !== $identity->reference)
        ->take(4);
    $categoryCounts = $byCategory->pluck('count', 'label');
    $statusLabels = [
        \App\Models\Need::STATUS_PROPOSED => 'Proposé',
        \App\Models\Need::STATUS_OPEN => 'Ouvert',
        \App\Models\Need::STATUS_IN_PROGRESS => 'En cours',
        \App\Models\Need::STATUS_RESOLVED => 'Résolu',
    ];
@endphp
<div class="dg-needs-hub">
    <div class="dg-needs-layout">
        <aside class="dg-needs-localnav dg-needs-panel" aria-label="Navigation Besoins">
            <a class="is-active" href="{{ route('needs.index') }}">⌂ <span>Carrefour Besoins</span></a>
            <a href="{{ route('needs.index') }}#besoins-recents">◉ <span>Découvrir</span></a>
            <a href="{{ route('needs.index', ['mine' => 1]) }}">▣ <span>Mes besoins</span></a>
            <a href="{{ route('needs.index', ['urgent' => 1]) }}">◷ <span>En attente de réponse</span></a>
            <a href="{{ route('needs.index', ['status' => \App\Models\Need::STATUS_RESOLVED]) }}">✓ <span>Besoins résolus</span></a>
            <a class="dg-needs-localnav__cta" href="{{ route('needs.create') }}">＋ <span>Exprimer un besoin</span></a>
            <p class="dg-needs-localnav__section">Explorer par</p>
            <a href="#categories">◇ <span>Catégories</span></a>
            <a href="#territoires">⌖ <span>Territoires</span></a>
            <a href="{{ route('needs.index', ['status' => \App\Models\Need::STATUS_OPEN]) }}">↗ <span>Besoins ouverts</span></a>
            <div class="dg-needs-help">
                <strong>Besoin d’aide ?</strong>
                <span>Comprendre comment exprimer un besoin utile.</span>
                <a href="#comprendre">Voir le principe</a>
            </div>
        </aside>

        <main class="dg-needs-main">
            <section class="dg-needs-hero dg-needs-panel">
                <div class="dg-needs-hero__copy">
                    <p class="dg-needs-eyebrow">CARREFOUR BESOINS · GAMAD</p>
                    <h1>Un besoin peut devenir le point de départ d’une action.</h1>
                    <p class="dg-needs-hero__lead">Exprimez un manque réel, trouvez les bonnes personnes, les bonnes compétences et les bons partenaires pour le transformer ensemble en impact concret.</p>
                    <form class="dg-needs-search" method="GET" action="{{ route('needs.index') }}">
                        <input type="search" name="q" value="{{ $searchTerm }}" placeholder="Rechercher un besoin : mot-clé, catégorie, territoire…" aria-label="Rechercher un besoin">
                        <button type="submit">Rechercher</button>
                    </form>
                    <div class="dg-needs-category-chips" aria-label="Catégories de besoins">
                        @foreach ($configuration['categories'] as $code => $label)
                            <a href="{{ route('needs.index', ['category' => $code]) }}">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
                <div class="dg-needs-hero__art" aria-hidden="true">
                    <picture>
                        <source media="(max-width: 700px)" srcset="{{ asset('images/entry/commencer-640.webp') }}">
                        <img src="{{ asset('images/entry/commencer-1280.webp') }}" alt="">
                    </picture>
                    <p class="dg-needs-hero__art-note">Des besoins d’aujourd’hui, des solutions pour demain.</p>
                </div>
            </section>

            <section class="dg-needs-stats" aria-label="Indicateurs Besoins">
                <article class="dg-needs-stat dg-needs-panel"><span class="dg-needs-stat__icon">◎</span><div><strong>{{ number_format($overview['open'], 0, ',', ' ') }}</strong><small>besoins ouverts</small></div></article>
                <article class="dg-needs-stat dg-needs-panel"><span class="dg-needs-stat__icon">↻</span><div><strong>{{ number_format($overview['pending'], 0, ',', ' ') }}</strong><small>propositions en attente</small></div></article>
                <article class="dg-needs-stat dg-needs-panel"><span class="dg-needs-stat__icon">✓</span><div><strong>{{ number_format($overview['resolved'], 0, ',', ' ') }}</strong><small>besoins résolus</small></div></article>
                <article class="dg-needs-stat dg-needs-panel"><span class="dg-needs-stat__icon">◷</span><div><strong>{{ number_format($bandeau['urgent'], 0, ',', ' ') }}</strong><small>attendent depuis 30 jours ou plus</small></div></article>
            </section>

            <section id="besoins-recents" class="dg-needs-section dg-needs-panel">
                <div class="dg-needs-section__head">
                    <div><h2>Besoins à découvrir</h2><p>Des besoins portés par des personnes, des ZUMRA et des projets.</p></div>
                    <a href="{{ route('needs.index') }}">Voir tous les besoins →</a>
                </div>

                <form class="dg-needs-filterbar" method="GET" action="{{ route('needs.index') }}">
                    <input type="search" name="q" value="{{ $searchTerm }}" placeholder="Rechercher…">
                    <select name="category" aria-label="Catégorie">
                        <option value="">Toutes les catégories</option>
                        @foreach ($configuration['categories'] as $code => $label)
                            <option value="{{ $code }}" @selected($categoryFilter === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="status" aria-label="Statut">
                        <option value="">Tous les statuts</option>
                        <option value="{{ \App\Models\Need::STATUS_OPEN }}" @selected($statusFilter === \App\Models\Need::STATUS_OPEN)>Ouverts</option>
                        <option value="{{ \App\Models\Need::STATUS_IN_PROGRESS }}" @selected($statusFilter === \App\Models\Need::STATUS_IN_PROGRESS)>En cours</option>
                        <option value="{{ \App\Models\Need::STATUS_RESOLVED }}" @selected($statusFilter === \App\Models\Need::STATUS_RESOLVED)>Résolus</option>
                    </select>
                    <button type="submit">Filtrer</button>
                </form>

                <div class="dg-needs-grid">
                    @forelse ($needs as $need)
                        @php
                            $ownerLabel = match ($need->owner_type) {
                                \App\Models\Need::OWNER_GROUP => optional($groups->get($need->owner_reference))->name ?? 'ZUMRA',
                                \App\Models\Need::OWNER_PROJECT => optional($projects->get($need->owner_reference))->name ?? 'Projet',
                                default => $need->author_core_reference === $identity->reference ? 'Vous' : 'Personne GAMAD',
                            };
                            $badgeClass = $need->status === \App\Models\Need::STATUS_RESOLVED ? 'is-resolved' : ($need->status === \App\Models\Need::STATUS_IN_PROGRESS ? 'is-progress' : '');
                        @endphp
                        <article class="dg-need-card">
                            <div class="dg-need-card__top">
                                <span class="dg-need-badge {{ $badgeClass }}">{{ $statusLabels[$need->status] ?? 'Besoin' }}</span>
                                <span class="dg-need-card__date">{{ $need->created_at?->diffForHumans() }}</span>
                            </div>
                            <h3>{{ $need->title }}</h3>
                            <p class="dg-need-card__context">{{ $need->context }}</p>
                            <div class="dg-need-card__meta">
                                @if ($need->location)<span>⌖ {{ $need->location }}</span>@endif
                                <span>◇ {{ $configuration['categories'][$need->category] ?? 'Besoin' }}</span>
                                @if ($need->capability_label)<span>◎ {{ $need->capability_label }}</span>@endif
                                <span>↔ {{ ucfirst(strtolower($need->collaboration_mode)) }}</span>
                            </div>
                            <div class="dg-need-card__owner">Porté par · {{ $ownerLabel }}</div>
                            <a class="dg-need-card__action" href="{{ route('needs.show', $need) }}">Voir le besoin</a>
                        </article>
                    @empty
                        <div class="dg-needs-empty">
                            <strong>Aucun besoin ne correspond à ces critères.</strong>
                            <p>Essayez une autre recherche ou exprimez un nouveau besoin.</p>
                        </div>
                    @endforelse
                </div>
                <div class="dg-needs-pagination">{{ $needs->links() }}</div>
            </section>

            <section id="categories" class="dg-needs-section dg-needs-panel">
                <div class="dg-needs-section__head"><div><h2>Explorer par catégorie</h2><p>Trouvez les besoins selon le type de soutien recherché.</p></div></div>
                <div class="dg-needs-category-grid">
                    @foreach ($configuration['categories'] as $code => $label)
                        <a class="dg-needs-category-tile" href="{{ route('needs.index', ['category' => $code]) }}"><strong>{{ $label }}</strong><small>{{ number_format((int) ($categoryCounts[$label] ?? 0), 0, ',', ' ') }} besoins visibles</small></a>
                    @endforeach
                </div>
            </section>

            <section id="territoires" class="dg-needs-section dg-needs-panel">
                <div class="dg-needs-section__head"><div><h2>Explorer par territoire</h2><p>Découvrez les besoins proches de vous ou ailleurs.</p></div></div>
                <div class="dg-needs-territories">
                    @forelse ($byLocation as $territory)
                        <a class="dg-needs-territory" href="{{ route('needs.index', ['q' => $territory['label']]) }}"><strong>⌖ {{ $territory['label'] }}</strong><small>{{ number_format($territory['count'], 0, ',', ' ') }} besoins visibles</small></a>
                    @empty
                        <div class="dg-needs-empty">Les territoires apparaîtront ici lorsque des besoins localisés seront publiés.</div>
                    @endforelse
                </div>
            </section>

            <section id="comprendre" class="dg-needs-why dg-needs-panel">
                <div><h2>Déclarer un besoin n’est pas créer un projet.</h2><p>Un besoin décrit précisément ce qui manque : une compétence, une ressource, un partenaire ou un appui. Il peut ensuite, si nécessaire, devenir le point de départ d’une action structurée.</p></div>
                <div class="dg-needs-step"><strong>1. Exprimez le besoin</strong><p>Décrivez clairement la situation et ce qui manque.</p></div>
                <div class="dg-needs-step"><strong>2. Touchez la bonne communauté</strong><p>Personnes, ZUMRA et projets peuvent découvrir le besoin selon sa visibilité.</p></div>
                <div class="dg-needs-step"><strong>3. Transformez en action</strong><p>Une aide, une mission ou une collaboration peut faire avancer la situation.</p></div>
            </section>
        </main>

        <aside class="dg-needs-aside" aria-label="Suggestions Besoins">
            <section class="dg-needs-aside-card dg-needs-panel">
                <div class="dg-needs-section__head"><div><h2>Où puis-je aider ?</h2><p>Des besoins ouverts que vous pouvez explorer.</p></div></div>
                @forelse ($helpableNeeds as $need)
                    <a class="dg-needs-mini" href="{{ route('needs.show', $need) }}"><strong>{{ $need->title }}</strong><span>{{ $configuration['categories'][$need->category] ?? 'Besoin' }}{{ $need->location ? ' · '.$need->location : '' }}</span></a>
                @empty
                    <p>Aucun besoin ouvert dans cette page pour le moment.</p>
                @endforelse
            </section>

            <section class="dg-needs-aside-card dg-needs-panel">
                <div class="dg-needs-section__head"><div><h2>Ceux qui attendent une réponse</h2><p>Ouverts ou en cours depuis au moins 30 jours.</p></div><a href="{{ route('needs.index', ['urgent' => 1]) }}">Voir →</a></div>
                @forelse ($urgentNeeds->take(4) as $need)
                    <a class="dg-needs-mini" href="{{ route('needs.show', $need) }}"><strong>{{ $need->title }}</strong><span>{{ $need->location ?: 'Territoire non précisé' }} · {{ $need->created_at?->diffForHumans() }}</span></a>
                @empty
                    <p>Aucun besoin ancien n’attend actuellement de réponse.</p>
                @endforelse
            </section>

            <section class="dg-needs-aside-cta dg-needs-panel">
                <div aria-hidden="true">✎</div>
                <h2>Exprimez un besoin</h2>
                <p>Vous avez identifié un manque réel ? Décrivez-le clairement pour toucher les personnes qui peuvent agir.</p>
                <a href="{{ route('needs.create') }}">＋ Publier un besoin</a>
            </section>
        </aside>
    </div>
</div>
</x-layouts.member>
