{{--
    Projets — extension visuelle des cartes du Fil (x-dg.feed.project). Même objet métier,
    mêmes états ; ceci n'est jamais un second fil social.
--}}
<x-layouts.portal title="Projets — DG Afrique">
    <x-dg.shell current="projets" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page">
            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="night">Du besoin à l’action</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">{{ $configuration['directory_title'] }}</h1>
                    <p>{{ $configuration['directory_intro'] }}</p>
                </div>
                <x-dg.btn variant="project" :href="route('projects.create')">Proposer un projet</x-dg.btn>
            </div>

            <form method="GET" class="dg-filters">
                <label>Domaine
                    <select name="domain" class="dg-select">
                        <option value="">Tous les domaines</option>
                        @foreach($configuration['domains'] as $code => $label)
                            <option value="{{ $code }}" @selected(request('domain') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <x-dg.btn variant="quiet" type="submit">Explorer</x-dg.btn>
            </form>

            @if($projects->isEmpty())
                <x-dg.empty title="Aucun projet visible">
                    <span>Une proposition structurée apparaîtra ici sans inventer de résultats ni de financement.</span>
                </x-dg.empty>
            @else
                <div class="dg-grid">
                    @foreach($projects as $project)
                        <article class="dg-card" style="display:flex;flex-direction:column;gap:14px">
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <x-dg.badge tone="project">{{ $configuration['domains'][$project->domain] ?? $project->domain }}</x-dg.badge>
                                <span class="dg-meta">{{ ['PROPOSED' => 'Proposé', 'ADOPTED' => 'Adopté', 'IN_PROGRESS' => 'En action', 'COMPLETED' => 'Réalisé'][$project->status] ?? $project->status }}</span>
                            </div>
                            <h2 class="dg-display dg-display--card" style="max-width:26ch">
                                <a href="{{ route('projects.show', $project) }}" style="color:inherit">{{ $project->name }}</a>
                            </h2>
                            <p class="dg-body">{{ \Illuminate\Support\Str::limit($project->summary, 160) }}</p>
                            <x-dg.maturity :stages="\App\Application\Projects\ProjectMaturityService::STAGES" :current="$project->maturity" />
                            <x-dg.actions flush style="justify-content:space-between;align-items:center">
                                <span class="dg-meta">{{ $project->owner_type === 'GROUP' ? ($groups->get($project->owner_reference)?->name ?? 'ZUMRA') : 'Projet personnel' }}</span>
                                <div style="display:flex;gap:8px">
                                    @if(in_array($project->id, $manageableProjectIds, true))
                                        <x-dg.btn variant="quiet" :href="route('projects.matching', $project)">Trouver des capacités →</x-dg.btn>
                                    @endif
                                    <x-dg.btn variant="quiet" :href="route('projects.show', $project)">Ouvrir le dossier →</x-dg.btn>
                                </div>
                            </x-dg.actions>
                        </article>
                    @endforeach
                </div>
                <div>{{ $projects->links('pagination.dg') }}</div>
            @endif
        </div>
    </x-dg.shell>
</x-layouts.portal>
