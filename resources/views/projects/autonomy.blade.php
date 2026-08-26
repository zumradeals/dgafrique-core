<x-layouts.portal title="Autonomie — {{ $project->name }} — DG Afrique">
<x-dg.shell current="projets" :identity="$identity" :is-administrator="$isAdministrator">
<main class="project-page">
    <div class="project-detail">
        <a href="{{ route('projects.show',$project) }}" class="dg-crumb">← {{ $project->name }}</a>

        @if(session('status'))<div class="alert success">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="alert danger">{{ $errors->first() }}</div>@endif

        <header>
            <div>
                <p class="eyebrow">CAP‑018 · Parcours d’autonomie</p>
                <h1>Vers une structure plus autonome</h1>
                <p>DG Afrique peut accompagner un projet suffisamment mûr vers une forme organisationnelle distincte, sans décider à sa place de sa forme juridique ni de sa propriété.</p>
            </div>
            <aside>
                <span>{{ $project->status }}</span>
                <strong>{{ str_replace('_',' ',mb_strtolower($project->maturity)) }}</strong>
                <small>{{ $eligible ? 'Repère compatible avec l’exploration d’une structure' : 'Repère encore insuffisant pour ouvrir ce parcours' }}</small>
            </aside>
        </header>

        <div class="project-finance-boundary">
            <strong>Aucun satellite créé</strong>
            <span>Ce parcours prépare une éventuelle structure distincte. Il ne crée ni entreprise, association, coopérative, startup, plateforme ou satellite et ne transfère aucune propriété du projet.</span>
        </div>

        <section class="project-map">
            <div>
                <h2>Identifier</h2>
                <p>Le parcours est ouvert uniquement aux projets situés aux repères « Structure potentielle » ou « Autonomie à explorer ».</p>
            </div>
            <div>
                <h2>Structurer</h2>
                <p>Le porteur peut préciser une forme envisagée puis utiliser l’accompagnement DG Afrique pour travailler les prochaines étapes utiles.</p>
            </div>
            <div>
                <h2>Préserver les connexions futures</h2>
                <p>Le projet source et ses références restent traçables afin de ne pas fermer la possibilité d’un raccordement futur à des services communs, si la future structure et l’autorité compétente le décident.</p>
            </div>
        </section>

        @if($pathway && $pathway->status === \App\Models\ProjectAutonomyPathway::STATUS_ACTIVE)
            <section class="project-decisions">
                <h2>Parcours actif</h2>
                <p style="color:var(--muted);font-size:.67rem;line-height:1.65">
                    Forme actuellement envisagée :
                    <strong>{{ $targetForms[$pathway->target_form] ?? $pathway->target_form }}</strong>
                    @if($pathway->other_form_label) — {{ $pathway->other_form_label }} @endif
                </p>
                <p style="color:var(--muted);font-size:.65rem;line-height:1.6">
                    Cette indication reste préparatoire. La forme réelle pourra évoluer et aucune identité organisationnelle n’est créée ici.
                </p>
                <div>
                    <a href="{{ route('projects.accompaniment.show',$project) }}" style="color:var(--ocean);font-size:.68rem;font-weight:900;text-decoration:none">Accompagnement DG Afrique →</a>
                    <form method="POST" action="{{ route('projects.autonomy.close',$project) }}">
                        @csrf @method('PUT')
                        <button class="quiet">Fermer le parcours</button>
                    </form>
                </div>
            </section>
        @elseif($eligible)
            <section class="project-decisions">
                <h2>Ouvrir le parcours d’autonomie</h2>
                <p style="color:var(--muted);font-size:.65rem;line-height:1.6">Choisissez une forme envisagée. Ce choix est exploratoire et ne constitue pas un statut juridique.</p>
                <form method="POST" action="{{ route('projects.autonomy.open',$project) }}">
                    @csrf
                    <select name="target_form" required>
                        @foreach($targetForms as $code=>$label)
                            <option value="{{ $code }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <input name="other_form_label" maxlength="180" placeholder="Préciser si « autre »">
                    <button>Ouvrir le parcours</button>
                </form>
            </section>
        @else
            <section class="project-decisions">
                <h2>Projet pas encore éligible</h2>
                <p style="color:var(--muted);font-size:.65rem;line-height:1.6">CAP‑018 n’invente pas un score de maturité. Repositionnez d’abord le projet, lorsque la situation réelle le justifie, vers « Structure potentielle » ou « Autonomie à explorer ».</p>
                <a href="{{ route('projects.show',$project) }}" style="color:var(--ocean);font-size:.68rem;font-weight:900;text-decoration:none">Retour à la maturité du projet →</a>
            </section>
        @endif
    </div>
</main>
</x-dg.shell>
</x-layouts.portal>
