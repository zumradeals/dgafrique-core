@php
    $statusLabels = ['PROPOSED' => 'Proposé', 'OPEN' => 'Ouvert', 'IN_PROGRESS' => 'En cours', 'RESOLVED' => 'Résolu', 'ARCHIVED' => 'Archivé'];
    $statusTones = ['PROPOSED' => 'decision', 'OPEN' => 'action', 'IN_PROGRESS' => 'project', 'RESOLVED' => 'success', 'ARCHIVED' => 'neutral'];
@endphp
<x-layouts.admin title="Besoins — Administration" current="needs">
    <div class="ac-page-head">
        <div>
            <p class="dg-label dg-label--saffron">Communauté</p>
            <h1>Besoins</h1>
            <p>Volume et statut des besoins exprimés. Seul le décideur légitime (auteur, leader ou porteur de projet) peut transitionner un besoin — aucune modération centralisée n’est introduite ici.</p>
        </div>
        <div class="ac-page-head__actions">
            <a href="{{ route('administration.needs.edit') }}" class="dg-btn dg-btn--quiet">Configurer les catégories →</a>
        </div>
    </div>

    <div class="ac-badge-row" style="margin-bottom:20px">
        @foreach($byStatus as $status => $count)
            <x-dg.badge :tone="$statusTones[$status] ?? 'neutral'">{{ $statusLabels[$status] ?? $status }} · {{ $count }}</x-dg.badge>
        @endforeach
        @if($stagnant > 0)
            <x-dg.badge tone="danger">{{ $stagnant }} besoin(s) ouvert(s) depuis plus de 30 jours</x-dg.badge>
        @endif
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
            <thead><tr><th>Titre</th><th>Porteur</th><th>Catégorie</th><th>Statut</th><th class="ac-wrap">Créé</th></tr></thead>
            <tbody>
                @forelse($needs as $need)
                    <tr>
                        <td><a href="{{ route('needs.show', $need) }}">{{ $need->title }}</a></td>
                        <td>{{ $need->owner_type }}</td>
                        <td>{{ $need->category }}</td>
                        <td><x-dg.badge :tone="$statusTones[$need->status] ?? 'neutral'">{{ $statusLabels[$need->status] ?? $need->status }}</x-dg.badge></td>
                        <td class="ac-wrap">{{ $need->created_at?->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="ac-empty">Aucun besoin ne correspond à ces filtres.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="ac-pagination">{{ $needs->links() }}</div>
</x-layouts.admin>
