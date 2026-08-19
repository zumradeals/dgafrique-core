{{-- Projets — même objet métier que dans le Fil ; Projet V2 ajoute une interface cognitive sans dupliquer le Core. --}}
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

            <section class="dg-card" style="margin-bottom:22px;padding:28px;display:grid;grid-template-columns:minmax(0,1fr) auto;gap:24px;align-items:center;background:linear-gradient(135deg,#fff 0%,#fff7ef 100%)">
                <div>
                    <x-dg.label tone="night">Cerveau du Projet</x-dg.label>
                    <h2 class="dg-display dg-display--card" style="margin:8px 0 7px">Qu’est-ce que vous voulez réaliser ?</h2>
                    <p style="max-width:720px;margin:0">Une idée encore floue suffit. Expliquez-la comme vous la raconteriez à quelqu’un. Nous avancerons une question utile à la fois.</p>
                </div>
                <x-dg.btn variant="project" :href="route('projects.brain.start')">Commencer la conversation →</x-dg.btn>
            </section>

            <form method="GET" class="dg-filters">
                <label>Domaine<select name="domain" class="dg-select"><option value="">Tous les domaines</option>@foreach($configuration['domains'] as $code => $label)<option value="{{ $code }}" @selected(request('domain') === $code)>{{ $label }}</option>@endforeach</select></label>
                <x-dg.btn variant="quiet" type="submit">Explorer</x-dg.btn>
            </form>

            @if($projects->isEmpty())
                <x-dg.empty title="Aucun projet pour le moment"><span>Pas besoin de remplir l’ancien formulaire : commencez simplement par raconter ce que vous voulez réaliser.</span></x-dg.empty>
            @else
                <div class="dg-grid">
                    @foreach($projects as $project)
                        <article class="dg-card" style="display:flex;flex-direction:column;gap:14px">
                            <div class="flex flex-wrap items-center justify-between gap-4"><x-dg.badge tone="project">{{ $configuration['domains'][$project->domain] ?? $project->domain }}</x-dg.badge><span class="dg-meta">{{ ['PROPOSED'=>'Proposé','ADOPTED'=>'Adopté','IN_PROGRESS'=>'En action','COMPLETED'=>'Réalisé'][$project->status] ?? $project->status }}</span></div>
                            <h2 class="dg-display dg-display--card" style="max-width:26ch"><a href="{{ route('projects.show',$project) }}" style="color:inherit">{{ $project->name }}</a></h2>
                            <p class="dg-body">{{ \Illuminate\Support\Str::limit($project->summary,160) }}</p>
                            <x-dg.maturity :stages="\App\Application\Projects\ProjectMaturityService::STAGES" :current="$project->maturity" />
                            <x-dg.actions flush style="justify-content:space-between;align-items:center">
                                <span class="dg-meta">{{ $project->owner_type === 'GROUP' ? ($groups->get($project->owner_reference)?->name ?? 'ZUMRA') : 'Projet personnel' }}</span>
                                <div style="display:flex;gap:8px;flex-wrap:wrap">
                                    <x-dg.btn variant="project" :href="route('projects.brain.show',$project)">Ouvrir le Cerveau ✦</x-dg.btn>
                                    @if(in_array($project->id,$manageableProjectIds,true))<x-dg.btn variant="quiet" :href="route('projects.matching',$project)">Trouver des capacités →</x-dg.btn>@endif
                                    <x-dg.btn variant="quiet" :href="route('projects.show',$project)">Dossier →</x-dg.btn>
                                </div>
                            </x-dg.actions>
                        </article>
                    @endforeach
                </div>
                <div>{{ $projects->links('pagination.dg') }}</div>
            @endif
        </div>
        <style>@media(max-width:760px){.dg-page section[style*="grid-template-columns"]{grid-template-columns:1fr!important}}</style>
    </x-dg.shell>
</x-layouts.portal>
