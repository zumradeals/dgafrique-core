@php
    $statusLabels = ['PROPOSED' => 'Proposé', 'ADOPTED' => 'Adopté', 'IN_PROGRESS' => 'En cours', 'COMPLETED' => 'Terminé', 'ARCHIVED' => 'Archivé'];
    $statusTones = ['PROPOSED' => 'decision', 'ADOPTED' => 'project', 'IN_PROGRESS' => 'action', 'COMPLETED' => 'success', 'ARCHIVED' => 'neutral'];
@endphp
<x-layouts.admin title="Projets — Administration" current="projects">
    <div class="ac-page-head">
        <div>
            <p class="dg-label dg-label--saffron">Projets</p>
            <h1>Projets</h1>
            <p>Vue de supervision, en lecture seule. Chaque décision de Projet reste gouvernée par ProjectAuthority.</p>
        </div>
        <div class="ac-page-head__actions">
            <a href="{{ route('administration.projects.edit') }}" class="dg-btn dg-btn--quiet">Configurer les domaines/quotas →</a>
        </div>
    </div>

    <div class="ac-badge-row" style="margin-bottom:20px">
        @foreach($byStatus as $status => $count)
            <x-dg.badge :tone="$statusTones[$status] ?? 'neutral'">{{ $statusLabels[$status] ?? $status }} · {{ $count }}</x-dg.badge>
        @endforeach
    </div>

    <form method="GET" class="ac-filters">
        <div class="dg-field"><label for="q">Recherche</label><input type="text" name="q" id="q" class="dg-input" value="{{ $search }}" placeholder="Nom du projet"></div>
        <div class="dg-field">
            <label for="status">Statut</label>
            <select name="status" id="status" class="dg-select">
                <option value="">Tous</option>
                @foreach($statusLabels as $status => $label)<option value="{{ $status }}" @selected($statusFilter === $status)>{{ $label }}</option>@endforeach
            </select>
        </div>
        <button type="submit" class="dg-btn dg-btn--quiet">Filtrer</button>
    </form>

    <div class="ac-table-wrap">
        <table class="ac-table">
            <thead><tr><th>Nom</th><th>Porteur</th><th>Domaine</th><th>Statut</th><th>Maturité</th><th class="ac-wrap">Créé</th></tr></thead>
            <tbody>
                @forelse($projects as $project)
                    <tr>
                        <td><a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a></td>
                        <td>{{ $project->owner_type }}</td>
                        <td>{{ $project->domain }}</td>
                        <td><x-dg.badge :tone="$statusTones[$project->status] ?? 'neutral'">{{ $statusLabels[$project->status] ?? $project->status }}</x-dg.badge></td>
                        <td>{{ $project->maturity }}</td>
                        <td class="ac-wrap">{{ $project->created_at?->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="ac-empty">Aucun projet ne correspond à ces filtres.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="ac-pagination">{{ $projects->links() }}</div>
</x-layouts.admin>
