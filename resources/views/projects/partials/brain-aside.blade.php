{{-- Colonne « Projet vivant » du Cerveau — réutilisée en desktop et en tiroir mobile. Les besoins,
     missions, l'équipe et le prochain jalon sont réels ; seuls l'avancement, les preuves et
     l'opportunité restent des projections métier, toujours annoncées « · Exemple ». --}}
<h2>Projet vivant</h2>

<div class="dg-brain-section">
    <div class="dg-brain-section__head"><span><x-dg.icon name="target" size="14" /> Aujourd’hui</span></div>
    <div class="dg-brain-stats">
        <div class="dg-brain-stat">
            <small>Avancement</small>
            <strong>{{ $progressSeed }}%</strong>
            <em>Projection</em>
        </div>
        <div class="dg-brain-stat">
            <small>Prochain jalon</small>
            <strong>{{ $nextMilestone ? \Illuminate\Support\Str::limit($nextMilestone->title, 16) : '—' }}</strong>
            <em>{{ $nextMilestone ? 'Prévue' : 'Aucune étape restante' }}</em>
        </div>
        <div class="dg-brain-stat">
            <small>Équipe active</small>
            <strong>{{ $teamMembers->count() }}</strong>
            <em>{{ Illuminate\Support\Str::plural('membre', $teamMembers->count()) }}</em>
        </div>
    </div>
</div>

<div class="dg-brain-section">
    <div class="dg-brain-section__head">
        <span>Besoins ({{ $projectNeeds->count() }})</span>
        <a href="{{ route('projects.show', $project) }}#dg-project-besoins" class="dg-brain-section__link">Voir tout</a>
    </div>
    @forelse($projectNeeds->take(3) as $need)
        <a href="{{ route('needs.show', $need) }}" class="dg-brain-card">
            <strong>{{ $need->title }}</strong>
            <small>{{ $needConfiguration['categories'][$need->category] ?? $need->category }} · {{ ['PROPOSED'=>'proposé','OPEN'=>'ouvert','IN_PROGRESS'=>'en cours','RESOLVED'=>'résolu'][$need->status] ?? mb_strtolower($need->status) }}</small>
        </a>
    @empty
        <p class="dg-meta" style="margin:0">Aucun besoin exprimé pour ce projet pour le moment.</p>
    @endforelse
</div>

<div class="dg-brain-section">
    <div class="dg-brain-section__head">
        <span>Missions ({{ $missions->count() }})</span>
        <a href="{{ route('projects.missions.create', $project) }}" class="dg-brain-section__link">Proposer</a>
    </div>
    @forelse($missions->take(3) as $mission)
        <a href="{{ route('missions.show', $mission) }}" class="dg-brain-card">
            <strong>{{ $mission->title }}</strong>
            <small>{{ $mission->due_at?->format('d/m/Y') ?? 'Sans échéance' }} · {{ \App\Models\Mission::STATUS_LABELS[$mission->status] ?? $mission->status }}</small>
        </a>
    @empty
        <p class="dg-meta" style="margin:0">Aucune Mission proposée pour ce projet pour le moment.</p>
    @endforelse
</div>

<div class="dg-brain-section">
    <div class="dg-brain-section__head">
        <span>Équipe ({{ $teamMembers->count() }})</span>
        <a href="{{ route('projects.show', $project) }}#dg-project-equipe-detail" class="dg-brain-section__link">Gérer</a>
    </div>
    @if($teamMembers->isEmpty())
        <p class="dg-meta" style="margin:0">Aucune personne n’a encore rejoint l’équipe.</p>
    @else
        <div class="dg-brain-avatars">
            @foreach($teamMembers->take(6) as $member)
                @php($name = $teamProfiles->get($member->core_identity_reference)?->discovery_display_name)
                <x-dg.avatar :initials="$name ? mb_strtoupper(mb_substr($name, 0, 1)) : '?'" :anonymous="! $name" size="sm" tone="night" />
            @endforeach
        </div>
    @endif
</div>

<div class="dg-brain-section">
    <div class="dg-brain-section__head"><span>Preuves récentes</span></div>
    <p class="dg-meta" style="margin:0 0 8px">Aucune preuve rattachée à ce projet pour le moment.</p>
    <div class="dg-brain-card" aria-disabled="true" title="L’espace documentaire du projet (GamaDrive) n’est pas encore relié au Cerveau.">
        <strong>Étude marché Bouaké.pdf</strong>
        <small>Exemple · document non rattaché</small>
    </div>
</div>

<div class="dg-brain-section">
    <div class="dg-brain-section__head"><span>Opportunités pour vous</span></div>
    <div class="dg-brain-opportunity" aria-disabled="true" title="Le rapprochement projet ↔ besoins ↔ capacités ↔ opportunités arrivera avec un futur module.">
        <strong>Appel à projets — Formation Numérique</strong>
        Financement jusqu’à 5 000 000 F CFA · Exemple
    </div>
</div>
