@php
    $statusLabels = ['OPEN' => 'Ouverte', 'CLOSED' => 'Clôturée', 'CANCELLED' => 'Annulée', 'FUNDED' => 'Cible atteinte'];
    $statusTones = ['OPEN' => 'action', 'CLOSED' => 'neutral', 'CANCELLED' => 'danger', 'FUNDED' => 'success'];
@endphp
<x-layouts.admin title="Financements de Projet — Administration" current="fundings">
    <div class="ac-page-head">
        <div>
            <p class="dg-label dg-label--saffron">Projets</p>
            <h1>Financements de Projet</h1>
            <p>Déclarations financières (CAP-063), en lecture seule. Uniquement en ZAHAB, jamais un décaissement — voir la fiche Projet pour l’historique complet des contributions.</p>
        </div>
    </div>

    <div class="ac-stat-grid">
        <div class="ac-stat"><div class="ac-stat__label">Ouvertes</div><div class="ac-stat__value">{{ $byStatus['OPEN'] ?? 0 }}</div><div class="ac-stat__meta">{{ number_format($targetOpen, 0, ',', ' ') }} ZAHAB visés</div></div>
        @foreach($statusLabels as $status => $label)
            @continue($status === 'OPEN')
            <div class="ac-stat"><div class="ac-stat__label">{{ $label }}</div><div class="ac-stat__value">{{ $byStatus[$status] ?? 0 }}</div></div>
        @endforeach
    </div>

    <form method="GET" class="ac-filters">
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
            <thead><tr><th>Projet</th><th>Objet</th><th>Cible</th><th>Statut</th><th class="ac-wrap">Ouverte</th></tr></thead>
            <tbody>
                @forelse($fundings as $funding)
                    @php($project = $projects->get($funding->project_id))
                    <tr>
                        <td>@if($project)<a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a>@else <span class="dg-meta">Projet introuvable</span> @endif</td>
                        <td class="ac-wrap">{{ \Illuminate\Support\Str::limit($funding->purpose, 60) }}</td>
                        <td>{{ number_format($funding->target_amount, 0, ',', ' ') }} {{ $funding->currency }}</td>
                        <td><x-dg.badge :tone="$statusTones[$funding->status] ?? 'neutral'">{{ $statusLabels[$funding->status] ?? $funding->status }}</x-dg.badge></td>
                        <td class="ac-wrap">{{ $funding->opened_at?->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="ac-empty">Aucune déclaration de financement ne correspond à ces filtres.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="ac-pagination">{{ $fundings->links() }}</div>
</x-layouts.admin>
