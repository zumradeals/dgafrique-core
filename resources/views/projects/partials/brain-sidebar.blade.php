{{-- Colonne « Projets & conversations » du Cerveau — réutilisée en desktop et en tiroir mobile. --}}
@php($statusLabels = ['PROPOSED' => 'Proposé', 'ADOPTED' => 'Adopté', 'IN_PROGRESS' => 'En action', 'COMPLETED' => 'Réalisé'])
<a href="{{ route('projects.index') }}" class="dg-brain-sidebar__back">← Tous les projets</a>
<a href="{{ route('projects.create') }}" class="dg-brain-new"><x-dg.icon name="spark" size="16" /> Nouveau projet</a>
<div class="dg-brain-filter" aria-disabled="true" title="Le filtrage de la liste arrivera avec un plus grand nombre de projets.">
    <x-dg.icon name="search" size="15" /> Filtrer les projets…
</div>
<div class="dg-brain-kicker">Projets &amp; conversations</div>
<nav class="dg-brain-projects" aria-label="Mes projets">
    @forelse($visibleProjects as $candidate)
        <a href="{{ route('projects.brain.show', $candidate) }}" class="dg-brain-project" @if($candidate->id === $project->id) aria-current="page" @endif>
            <div class="dg-brain-project__row">
                <span>{{ $candidate->name }}</span>
                <x-dg.badge tone="{{ $candidate->id === $project->id ? 'action' : 'neutral' }}" style="flex:none">{{ $statusLabels[$candidate->status] ?? $candidate->status }}</x-dg.badge>
            </div>
            <div class="dg-brain-project__meta">
                @if(in_array($candidate->id, $conversationProjectIds, true))
                    <x-dg.icon name="feed" size="12" /> Conversation active
                @else
                    Pas encore de conversation
                @endif
            </div>
        </a>
    @empty
        <p style="color:rgba(255,255,255,.65);font-size:13px;padding:10px">Aucun projet accessible pour le moment.</p>
    @endforelse
</nav>
<span class="dg-brain-archived" aria-disabled="true" title="La vue dédiée aux projets archivés arrivera prochainement.">
    <span>Projets archivés</span>
    <span>{{ $archivedProjectsCount }}</span>
</span>
<div class="dg-brain-status"><i aria-hidden="true"></i> Cerveau opérationnel</div>
