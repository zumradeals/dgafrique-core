{{--
    Besoins — refonte visuelle V1, fidèle à la maquette du 20 août 2026 (voir addendum daté
    docs/design/DESIGN-INVARIANTS.md §21). Même objet métier que dans le Fil (x-dg.feed.need) et
    même langage visuel que /projets : cet écran ne recrée jamais un second fil social, c'est une
    liste filtrée du même objet métier, avec les mêmes états.
--}}
@php
    $needStatusLabels = ['PROPOSED' => 'Proposé', 'OPEN' => 'Ouvert', 'IN_PROGRESS' => 'En cours', 'RESOLVED' => 'Résolu'];
    $visibleCount = $needs->total() + $demoCards->count();
@endphp
<x-layouts.portal title="Besoins — DG Afrique">
    <x-dg.shell current="besoins" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page">
            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="copper">Capacités · Besoins · Opportunités</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">{{ $configuration['directory_title'] }}</h1>
                    <p>{{ $configuration['directory_intro'] }}</p>
                </div>
                <x-dg.btn variant="need" :href="route('needs.create')">Exprimer un besoin +</x-dg.btn>
            </div>

            <div class="dg-needs-toolbar">
                <form method="GET" class="dg-filters">
                    <label>Catégorie
                        <select name="category" class="dg-select">
                            <option value="">Toutes les catégories</option>
                            @foreach($configuration['categories'] as $code => $label)
                                <option value="{{ $code }}" @selected(request('category') === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>État
                        <select name="status" class="dg-select">
                            <option value="">Tous les états</option>
                            <option value="OPEN" @selected(request('status') === 'OPEN')>Ouvert</option>
                            <option value="IN_PROGRESS" @selected(request('status') === 'IN_PROGRESS')>En cours</option>
                            <option value="RESOLVED" @selected(request('status') === 'RESOLVED')>Résolu</option>
                        </select>
                    </label>
                    <label>Trier par
                        <select class="dg-select" disabled title="Le tri se limite aujourd’hui aux besoins les plus récents.">
                            <option>Plus récents</option>
                        </select>
                    </label>
                    <x-dg.btn variant="quiet" type="submit">Filtrer</x-dg.btn>
                </form>

                <div class="dg-needs-view-toggle" role="group" aria-label="Mode d’affichage">
                    <button type="button" aria-current="true" disabled title="Vue grille — vue par défaut."><x-dg.icon name="grid" size="17" /></button>
                    <button type="button" disabled title="La vue liste arrivera avec un plus grand nombre de besoins réels."><x-dg.icon name="list" size="17" /></button>
                </div>
            </div>

            <div class="dg-needs-section-head">
                <span><strong>{{ $visibleCount }}</strong> {{ Illuminate\Support\Str::plural('besoin trouvé', $visibleCount) }}</span>
                <span class="dg-needs-refresh">Mise à jour : aujourd’hui</span>
            </div>

            <div class="dg-needs-layout">
                <div>
                    @if($needs->isEmpty() && $demoCards->isEmpty())
                        <x-dg.empty title="Aucun besoin visible ici">
                            <span>Un besoin réel et bien contextualisé pourra devenir un point de rencontre entre capacités.</span>
                        </x-dg.empty>
                    @else
                        <div class="dg-grid">
                            @foreach($needs as $need)
                                <article class="dg-card dg-needs-card">
                                    <div class="dg-needs-card__head">
                                        <x-dg.badge tone="need">{{ $configuration['categories'][$need->category] ?? $need->category }}</x-dg.badge>
                                        <span class="dg-needs-card__state" data-status="{{ $need->status }}"><i aria-hidden="true"></i> {{ $needStatusLabels[$need->status] ?? $need->status }}</span>
                                    </div>
                                    <h2 class="dg-needs-card__title dg-display"><a href="{{ route('needs.show', $need) }}">{{ $need->title }}</a></h2>
                                    <p class="dg-body" style="max-width:none">{{ \Illuminate\Support\Str::limit($need->context, 160) }}</p>
                                    <div class="dg-needs-card__meta">
                                        <span><x-dg.icon name="team" size="13" /> {{ match($need->owner_type) { 'GROUP' => $groups->get($need->owner_reference)?->name ?? 'ZUMRA', 'PROJECT' => $projects->get($need->owner_reference)?->name ?? 'Projet', default => 'Besoin personnel' } }}</span>
                                        @if($need->location)<span><x-dg.icon name="target" size="13" /> {{ $need->location }}</span>@endif
                                        <span><x-dg.icon name="calendar" size="13" /> Créé le {{ $need->created_at->format('d/m/Y') }}</span>
                                    </div>
                                    @if($need->capability_label)
                                        <div class="dg-needs-card__tags"><span class="dg-needs-card__tag">{{ $need->capability_label }}</span></div>
                                    @endif
                                    <div class="dg-needs-card__foot">
                                        <x-dg.btn variant="quiet" :href="route('needs.show', $need)">Comprendre le besoin →</x-dg.btn>
                                        <button type="button" class="dg-needs-card__save" disabled title="La sauvegarde de besoins favoris arrivera prochainement." aria-label="Sauvegarder ce besoin (bientôt disponible)"><x-dg.icon name="bookmark" size="16" /></button>
                                    </div>
                                </article>
                            @endforeach

                            @foreach($demoCards as $card)
                                <article class="dg-card dg-needs-card">
                                    <div class="dg-needs-card__head">
                                        <x-dg.badge tone="need">{{ $configuration['categories'][$card['category']] ?? $card['category'] }} · Exemple</x-dg.badge>
                                        <span class="dg-needs-card__state" data-status="OPEN"><i aria-hidden="true"></i> Ouvert</span>
                                    </div>
                                    <h2 class="dg-needs-card__title dg-display">{{ $card['title'] }}</h2>
                                    <p class="dg-body" style="max-width:none">{{ $card['context'] }}</p>
                                    <div class="dg-needs-card__meta">
                                        <span><x-dg.icon name="team" size="13" /> {{ $card['owner_label'] }}</span>
                                        <span><x-dg.icon name="target" size="13" /> {{ $card['location'] }}</span>
                                        <span><x-dg.icon name="calendar" size="13" /> {{ $card['created_label'] }}</span>
                                    </div>
                                    <div class="dg-needs-card__tags">
                                        @foreach($card['tags'] as $tag)
                                            <span class="dg-needs-card__tag">{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                    <div class="dg-needs-card__foot">
                                        <x-dg.btn variant="quiet" disabled title="Objet de démonstration — aucune action réelle n’est rattachée.">Comprendre le besoin →</x-dg.btn>
                                        <button type="button" class="dg-needs-card__save" disabled title="Objet de démonstration — aucune action réelle n’est rattachée." aria-label="Sauvegarder ce besoin (démonstration)"><x-dg.icon name="bookmark" size="16" /></button>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                        <div>{{ $needs->links('pagination.dg') }}</div>
                    @endif
                </div>

                <aside class="dg-needs-aside">
                    <div class="dg-card dg-needs-overview">
                        <x-dg.label tone="night">Aperçu des besoins</x-dg.label>
                        <div class="dg-needs-stat">
                            <span class="dg-needs-stat__mark" aria-hidden="true"><x-dg.icon name="check-circle" size="19" /></span>
                            <div>
                                <span class="dg-needs-stat__value">{{ $overview['open'] }}</span>
                                <div class="dg-needs-stat__label">Besoins ouverts</div>
                                <div class="dg-needs-stat__delta">Actifs actuellement</div>
                            </div>
                        </div>
                        <div class="dg-needs-stat">
                            <span class="dg-needs-stat__mark" aria-hidden="true"><x-dg.icon name="feed" size="19" /></span>
                            <div>
                                <span class="dg-needs-stat__value">{{ $overview['pending'] }}</span>
                                <div class="dg-needs-stat__label">En attente</div>
                                <div class="dg-needs-stat__delta">En cours de clarification</div>
                            </div>
                        </div>
                        <div class="dg-needs-stat">
                            <span class="dg-needs-stat__mark" aria-hidden="true"><x-dg.icon name="star" size="19" /></span>
                            <div>
                                <span class="dg-needs-stat__value">{{ $overview['resolved'] }}</span>
                                <div class="dg-needs-stat__label">Pourvus</div>
                                <div class="dg-needs-stat__delta">Besoins satisfaits</div>
                            </div>
                        </div>
                        <x-dg.btn variant="need" :href="route('needs.index')" style="justify-content:center;margin-top:12px">Voir tous les besoins →</x-dg.btn>
                    </div>
                </aside>
            </div>
        </div>
    </x-dg.shell>
</x-layouts.portal>
