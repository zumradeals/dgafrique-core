@php
    $statusTones = ['SUBMITTED' => 'decision', 'WITNESSED' => 'project', 'ACKNOWLEDGED' => 'success', 'DISPUTED' => 'danger'];
@endphp
<x-layouts.admin title="Preuves — Administration" current="proofs">
    <div class="ac-page-head">
        <div>
            <p class="dg-label dg-label--saffron">Projets</p>
            <h1>Preuves</h1>
            <p>Volume du Carnet de preuves, en lecture seule. Jamais un score caché, jamais un classement des personnes — uniquement des volumes.</p>
        </div>
    </div>

    <div class="ac-badge-row" style="margin-bottom:20px">
        @foreach($byStatus as $status => $count)
            <x-dg.badge :tone="$statusTones[$status] ?? 'neutral'">{{ \App\Models\Proof::STATUS_LABELS[$status] ?? $status }} · {{ $count }}</x-dg.badge>
        @endforeach
    </div>

    <form method="GET" class="ac-filters">
        <div class="dg-field">
            <label for="origin_type">Origine</label>
            <select name="origin_type" id="origin_type" class="dg-select">
                <option value="">Toutes</option>
                @foreach(\App\Models\Proof::ORIGIN_TYPES as $origin)<option value="{{ $origin }}" @selected($originFilter === $origin)>{{ $origin }}</option>@endforeach
            </select>
        </div>
        <button type="submit" class="dg-btn dg-btn--quiet">Filtrer</button>
    </form>

    <div class="ac-table-wrap">
        <table class="ac-table">
            <thead><tr><th>Titre</th><th>Porteur</th><th>Origine</th><th>Statut</th><th class="ac-wrap">Enregistrée</th></tr></thead>
            <tbody>
                @forelse($proofs as $proof)
                    <tr>
                        <td>{{ $proof->title }}</td>
                        <td>{{ $proof->owner_type }}</td>
                        <td>{{ $proof->origin_type }}</td>
                        <td><x-dg.badge :tone="$statusTones[$proof->status] ?? 'neutral'">{{ \App\Models\Proof::STATUS_LABELS[$proof->status] ?? $proof->status }}</x-dg.badge></td>
                        <td class="ac-wrap">{{ $proof->created_at?->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="ac-empty">Aucune preuve ne correspond à ces filtres.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="ac-pagination">{{ $proofs->links() }}</div>
</x-layouts.admin>
