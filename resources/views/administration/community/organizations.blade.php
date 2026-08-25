@php
    $statusLabels = ['ACTIVE' => 'Active', 'ARCHIVED' => 'Archivée'];
@endphp
<x-layouts.admin title="Organisations — Administration" current="organizations">
    <div class="ac-page-head">
        <div>
            <p class="dg-label dg-label--saffron">Communauté</p>
            <h1>Organisations</h1>
            <p>Vue globale, en lecture seule. La gouvernance de chaque Organisation reste déléguée à son fondateur — jamais à l’administration DG Afrique.</p>
        </div>
    </div>

    <div class="ac-badge-row" style="margin-bottom:20px">
        @forelse($byStatus as $status => $count)
            <x-dg.badge :tone="$status === 'ACTIVE' ? 'success' : 'neutral'">{{ $statusLabels[$status] ?? $status }} · {{ $count }}</x-dg.badge>
        @empty
            <span class="dg-meta">Aucune organisation enregistrée.</span>
        @endforelse
    </div>

    <form method="GET" class="ac-filters">
        <div class="dg-field">
            <label for="status">Statut</label>
            <select name="status" id="status" class="dg-select">
                <option value="">Tous</option>
                @foreach($statusLabels as $status => $label)
                    <option value="{{ $status }}" @selected($statusFilter === $status)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="dg-btn dg-btn--quiet">Filtrer</button>
    </form>

    <div class="ac-table-wrap">
        <table class="ac-table">
            <thead><tr><th>Nom</th><th>Type</th><th>Visibilité</th><th>Statut</th><th>Rattachement Core</th><th class="ac-wrap">Créée</th></tr></thead>
            <tbody>
                @forelse($organizations as $organization)
                    <tr>
                        <td>{{ $organization->name }}</td>
                        <td>{{ $organization->type }}</td>
                        <td>{{ $organization->visibility }}</td>
                        <td><x-dg.badge :tone="$organization->status === 'ACTIVE' ? 'success' : 'neutral'">{{ $statusLabels[$organization->status] ?? $organization->status }}</x-dg.badge></td>
                        <td>{{ $organization->core_link_status }}</td>
                        <td class="ac-wrap">{{ $organization->created_at?->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="ac-empty">Aucune organisation ne correspond à ces filtres.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="ac-pagination">{{ $organizations->links() }}</div>
</x-layouts.admin>
