<x-layouts.portal title="Lanceur de satellites — Administration DG Afrique">
<main class="admin-content">
    <div class="admin-heading">
        <div>
            <p class="eyebrow dark">Administration DG Afrique</p>
            <h1>Lanceur de satellites</h1>
            <p>Repérer les projets suffisamment mûrs et suivre leur parcours vers une autonomie organisationnelle plus forte, sans créer automatiquement une structure ni en déterminer la propriété.</p>
        </div>
        <div class="admin-links">
            <a href="{{ route('administration.project-accompaniment.edit') }}">Accompagnement CAP‑016</a>
            <a href="{{ route('administration.projects.edit') }}">Projets</a>
            <a href="{{ route('member.space') }}">Mon espace</a>
        </div>
    </div>

    <section class="admin-form">
        <h2>Parcours actifs</h2>
        @forelse($activePathways as $pathway)
            <div class="admin-sections">
                <div>
                    <strong>{{ $pathway->project?->name ?? 'Projet indisponible' }}</strong>
                    <p>{{ $targetForms[$pathway->target_form] ?? $pathway->target_form }} @if($pathway->other_form_label) — {{ $pathway->other_form_label }} @endif</p>
                </div>
                <div>
                    <small>Maturité actuelle</small>
                    <p>{{ str_replace('_',' ',mb_strtolower((string)$pathway->project?->maturity)) }}</p>
                </div>
            </div>
        @empty
            <p>Aucun parcours d’autonomie actif.</p>
        @endforelse
    </section>

    <section class="admin-form" style="margin-top:1rem">
        <h2>Projets mûrs à examiner</h2>
        <p>Cette liste est déterministe : seuls les repères « Structure potentielle » et « Satellite potentiel » sont retenus. Aucun score caché n’est calculé.</p>
        @forelse($eligibleProjects as $project)
            <div class="admin-sections">
                <div>
                    <strong>{{ $project->name }}</strong>
                    <p>{{ str_replace('_',' ',mb_strtolower($project->maturity)) }}</p>
                </div>
                <div>
                    <small>Parcours</small>
                    <p>{{ $project->autonomyPathway?->status ?? 'NON OUVERT' }}</p>
                </div>
            </div>
        @empty
            <p>Aucun projet n’a encore atteint les repères d’autonomie de CAP‑018.</p>
        @endforelse
    </section>
</main>
</x-layouts.portal>
