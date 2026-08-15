<x-layouts.portal title="Accompagnement projets — Administration DG Afrique">
    @include('projects.partials.accompaniment-styles')
    <main class="admin-content"><div class="admin-heading"><div><p class="eyebrow dark">Administration DG Afrique · CAP‑016</p><h1>Piloter l’accompagnement des projets</h1><p>Configurer les formes d’appui disponibles et tracer les interventions réelles. Aucun réglage ici ne transfère la propriété ou le contrôle d’un projet.</p></div><div class="admin-links"><a href="{{ route('administration.projects.edit') }}">Configuration projets</a><a href="{{ route('projects.index') }}">Voir les projets</a><a href="{{ route('member.space') }}">Mon espace</a></div></div>
        @if(session('status'))<div class="alert success">{{ session('status') }}</div>@endif @if($errors->any())<div class="alert danger">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('administration.project-accompaniment.update') }}" class="admin-form">@csrf @method('PUT')<section><h2>Présentation</h2><label>Titre<input name="page_title" value="{{ $configuration['page_title'] }}"></label><label>Introduction<textarea name="page_intro">{{ $configuration['page_intro'] }}</textarea></label></section><section><h2>Appuis disponibles</h2><p>Ces codes déterminent les interventions que DG Afrique peut tracer actuellement. Une orientation financement reste un accompagnement, pas une transaction.</p>@foreach($configuration['action_types'] as $code=>$label)<div class="admin-sections"><label>Code<input name="action_type_codes[]" value="{{ $code }}"></label><label>Libellé<input name="action_type_labels[]" value="{{ $label }}"></label></div>@endforeach</section><button class="primary-button">Enregistrer la configuration</button></form>
        <section class="admin-accompaniment-list"><div class="section-heading"><div><p class="eyebrow dark">Accompagnements actifs</p><h2>Interventions à tracer</h2></div><small>{{ $activeAccompaniments->count() }} projet(s)</small></div>
            @forelse($activeAccompaniments as $accompaniment)
                <article class="admin-accompaniment-card"><header><div><span>{{ $accompaniment->project->domain }}</span><h3>{{ $accompaniment->project->name }}</h3><p>Activé le {{ $accompaniment->activated_at?->format('d/m/Y à H:i') }} · {{ $accompaniment->actions->count() }} intervention(s)</p></div></header>
                    <form method="POST" action="{{ route('administration.project-accompaniment.actions.store',$accompaniment->project) }}" class="accompaniment-action-form">@csrf
                        <label>Type<select name="action_type" required>@foreach($configuration['action_types'] as $code=>$label)<option value="{{ $code }}">{{ $label }}</option>@endforeach</select></label>
                        <label>Intervention<select name="delivery_source" required><option value="INTERNAL">DG Afrique</option><option value="PARTNER">Partenaire</option></select></label>
                        <label>Intervenant / partenaire<input name="provider_label" placeholder="DG Afrique par défaut"></label>
                        <label>Date de l’action<input type="datetime-local" name="occurred_at" max="{{ now()->format('Y-m-d\TH:i') }}"></label>
                        <label class="wide">Action réalisée<textarea name="summary" required minlength="20" maxlength="2400" placeholder="Décrire le fait concret, sans jugement ni score."></textarea></label>
                        <label class="wide">Prochaine étape<textarea name="next_step" maxlength="1200" placeholder="Facultatif"></textarea></label>
                        <button class="primary-button wide">Enregistrer l’intervention</button>
                    </form>
                </article>
            @empty<div class="accompaniment-empty"><strong>Aucun accompagnement actif.</strong><p>Les projets apparaissent ici après activation volontaire par leur porteur autorisé.</p></div>@endforelse
        </section>
    </main>
</x-layouts.portal>
