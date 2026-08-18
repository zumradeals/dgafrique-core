{{--
    Besoins — extension visuelle des cartes du Fil (x-dg.feed.need). Cet écran ne recrée jamais
    un second fil social : c'est une liste filtrée du même objet métier, avec les mêmes états.
--}}
<x-layouts.portal title="Besoins — DG Afrique">
    <x-dg.shell current="besoins" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page">
            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="copper">Capacités · Besoins · Opportunités</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">{{ $configuration['directory_title'] }}</h1>
                    <p>{{ $configuration['directory_intro'] }}</p>
                </div>
                <x-dg.btn variant="need" :href="route('needs.create')">Exprimer un besoin</x-dg.btn>
            </div>

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
                <x-dg.btn variant="quiet" type="submit">Filtrer</x-dg.btn>
            </form>

            @if($needs->isEmpty())
                <x-dg.empty title="Aucun besoin visible ici">
                    <span>Un besoin réel et bien contextualisé pourra devenir un point de rencontre entre capacités.</span>
                </x-dg.empty>
            @else
                <div class="dg-grid">
                    @foreach($needs as $need)
                        <article class="dg-card" style="display:flex;flex-direction:column;gap:14px">
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <x-dg.badge tone="need">{{ $configuration['categories'][$need->category] ?? $need->category }}</x-dg.badge>
                                <span class="dg-meta">{{ ['PROPOSED' => 'Proposé', 'OPEN' => 'Ouvert', 'IN_PROGRESS' => 'En cours', 'RESOLVED' => 'Résolu'][$need->status] ?? $need->status }}</span>
                            </div>
                            <h2 class="dg-display dg-display--card" style="max-width:26ch">
                                <a href="{{ route('needs.show', $need) }}" style="color:inherit">{{ $need->title }}</a>
                            </h2>
                            <p class="dg-body">{{ \Illuminate\Support\Str::limit($need->context, 160) }}</p>
                            <x-dg.actions flush style="justify-content:space-between;align-items:center">
                                <span class="dg-meta">{{ match($need->owner_type) { 'GROUP' => $groups->get($need->owner_reference)?->name ?? 'ZUMRA', 'PROJECT' => $projects->get($need->owner_reference)?->name ?? 'Projet', default => 'Besoin personnel' } }}</span>
                                <x-dg.btn variant="quiet" :href="route('needs.show', $need)">Comprendre le besoin →</x-dg.btn>
                            </x-dg.actions>
                        </article>
                    @endforeach
                </div>
                <div>{{ $needs->links('pagination.dg') }}</div>
            @endif
        </div>
    </x-dg.shell>
</x-layouts.portal>
