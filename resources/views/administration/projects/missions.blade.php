@php
    $statusTones = ['DRAFT' => 'neutral', 'PROPOSED' => 'decision', 'CHANGES_REQUESTED' => 'decision', 'REJECTED' => 'neutral', 'OPEN' => 'action', 'IN_PROGRESS' => 'action', 'BLOCKED' => 'danger', 'SUBMITTED' => 'project', 'COMPLETED' => 'success', 'CANCELLED' => 'neutral'];
@endphp
<x-layouts.admin title="Missions — Administration" current="missions">
    <div class="ac-page-head">
        <div>
            <p class="dg-label dg-label--saffron">Projets</p>
            <h1>Missions</h1>
            <p>Vue de supervision, en lecture seule. La gouvernance de chaque Mission reste celle de son contexte (Projet, ZUMRA ou Besoin).</p>
        </div>
    </div>

    <div class="ac-badge-row" style="margin-bottom:20px">
        @foreach($byStatus as $status => $count)
            <x-dg.badge :tone="$statusTones[$status] ?? 'neutral'">{{ \App\Models\Mission::STATUS_LABELS[$status] ?? $status }} · {{ $count }}</x-dg.badge>
        @endforeach
    </div>

    <form method="GET" class="ac-filters">
        <div class="dg-field">
            <label for="status">Statut</label>
            <select name="status" id="status" class="dg-select">
                <option value="">Tous</option>
                @foreach(\App\Models\Mission::STATUS_LABELS as $status => $label)<option value="{{ $status }}" @selected($statusFilter === $status)>{{ $label }}</option>@endforeach
            </select>
        </div>
        <button type="submit" class="dg-btn dg-btn--quiet">Filtrer</button>
    </form>

    <div class="ac-table-wrap">
        <table class="ac-table">
            <thead><tr><th>Titre</th><th>Contexte</th><th>Statut</th><th class="ac-wrap">Créée</th></tr></thead>
            <tbody>
                @forelse($missions as $mission)
                    <tr>
                        <td><a href="{{ route('missions.show', $mission) }}">{{ $mission->title }}</a></td>
                        <td>{{ $mission->context_type }}</td>
                        <td><x-dg.badge :tone="$statusTones[$mission->status] ?? 'neutral'">{{ \App\Models\Mission::STATUS_LABELS[$mission->status] ?? $mission->status }}</x-dg.badge></td>
                        <td class="ac-wrap">{{ $mission->created_at?->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="ac-empty">Aucune Mission ne correspond à ces filtres.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="ac-pagination">{{ $missions->links() }}</div>
</x-layouts.admin>
