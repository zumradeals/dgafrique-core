<x-layouts.portal title="Accompagnement — {{ $project->name }}">
    @include('projects.partials.accompaniment-styles')
    <main class="accompaniment-page"><div class="accompaniment-shell">
        <nav class="accompaniment-nav"><a href="{{ route('projects.show',$project) }}">← {{ $project->name }}</a><a href="{{ route('projects.index') }}">Projets</a></nav>
        @if(session('status'))<div class="alert success">{{ session('status') }}</div>@endif @if($errors->any())<div class="alert danger">{{ $errors->first() }}</div>@endif
        <header class="accompaniment-hero"><div><p class="eyebrow dark">CAP‑016 · Accompagnement DG Afrique</p><h1>{{ $configuration['page_title'] }}</h1><p>{{ $configuration['page_intro'] }}</p></div><aside><span>Projet</span><strong>{{ $project->name }}</strong><small>{{ $project->status }}</small></aside></header>
        <section class="accompaniment-boundary"><strong>Le projet reste autonome.</strong><p>Activer cet accompagnement n’accorde à DG Afrique ni propriété, ni contrôle, ni pouvoir de décision sur le projet. Aucun financement n’est créé par cette page.</p></section>
        @if(!$accompaniment || $accompaniment->status===\App\Models\ProjectAccompaniment::STATUS_ENDED)
            <section class="accompaniment-start"><div><h2>{{ $accompaniment?'Reprendre un accompagnement':'Activer un accompagnement' }}</h2><p>Le porteur autorisé ouvre volontairement ce parcours. Les interventions pourront ensuite être apportées par DG Afrique ou coordonnées avec un partenaire.</p></div><form method="POST" action="{{ route('projects.accompaniment.activate',$project) }}">@csrf<button class="primary-button">Activer l’accompagnement</button></form></section>
        @else
            <section class="accompaniment-active"><div><p class="eyebrow dark">Accompagnement actif</p><h2>Un appui progressif, action par action.</h2><p>Activé le {{ $accompaniment->activated_at?->format('d/m/Y à H:i') }}. Chaque intervention est conservée dans la chronologie.</p></div><form method="POST" action="{{ route('projects.accompaniment.end',$project) }}">@csrf @method('PUT')<button class="secondary-button">Terminer l’accompagnement</button></form></section>
        @endif
        <section class="accompaniment-types"><h2>Appuis disponibles</h2><div>@foreach($configuration['action_types'] as $label)<span>{{ $label }}</span>@endforeach</div></section>
        <section class="accompaniment-timeline"><div class="section-heading"><div><p class="eyebrow dark">Traçabilité</p><h2>Interventions enregistrées</h2></div>@if($accompaniment)<small>{{ $accompaniment->actions->count() }} action(s)</small>@endif</div>
            @forelse($accompaniment?->actions??[] as $action)
                <article><div class="timeline-mark"></div><div><div class="timeline-meta"><strong>{{ $configuration['action_types'][$action->action_type]??str_replace('_',' ',$action->action_type) }}</strong><span>{{ $action->delivery_source===\App\Models\ProjectAccompanimentAction::SOURCE_PARTNER?'Partenaire':'DG Afrique' }}</span><time>{{ $action->occurred_at?->format('d/m/Y H:i') }}</time></div><h3>{{ $action->provider_label }}</h3><p>{{ $action->summary }}</p>@if($action->next_step)<aside><strong>Prochaine étape</strong><span>{{ $action->next_step }}</span></aside>@endif</div></article>
            @empty<div class="accompaniment-empty"><strong>Aucune intervention enregistrée.</strong><p>L’accompagnement peut commencer sans dossier lourd ; la chronologie s’enrichira au fur et à mesure des actions réelles.</p></div>@endforelse
        </section>
    </div></main>
</x-layouts.portal>
