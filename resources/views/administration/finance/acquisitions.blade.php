@php
    $statusTones = ['PENDING' => 'decision', 'PROCESSING' => 'project', 'COMPLETED' => 'success', 'FAILED' => 'danger', 'CANCELLED' => 'neutral', 'REFUNDED' => 'neutral'];
@endphp
<x-layouts.admin title="Acquisitions GeniusPay — Administration" current="acquisitions">
    <div class="ac-page-head">
        <div>
            <p class="dg-label dg-label--saffron">Finance</p>
            <h1>Acquisitions GeniusPay</h1>
            <p>Tentatives d’achat de ZAHAB via un paiement externe, en lecture seule. Le crédit réel reste une écriture Ledger distincte, jamais déclenchée depuis cet écran.</p>
        </div>
    </div>

    <div class="ac-badge-row" style="margin-bottom:20px">
        @foreach($byStatus as $status => $count)
            <x-dg.badge :tone="$statusTones[$status] ?? 'neutral'">{{ $status }} · {{ $count }}</x-dg.badge>
        @endforeach
    </div>

    <form method="GET" class="ac-filters">
        <div class="dg-field">
            <label for="status">Statut</label>
            <select name="status" id="status" class="dg-select">
                <option value="">Tous</option>
                @foreach(array_keys($statusTones) as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>@endforeach
            </select>
        </div>
        <div class="dg-field"><label for="person_core_reference">Identité</label><input type="text" name="person_core_reference" id="person_core_reference" class="dg-input" value="{{ $filters['person_core_reference'] ?? '' }}"></div>
        <button type="submit" class="dg-btn dg-btn--quiet">Filtrer</button>
    </form>

    <div class="ac-table-wrap">
        <table class="ac-table">
            <thead><tr><th>Identité</th><th>Montant</th><th>Frais</th><th>Statut</th><th class="ac-wrap">Créée</th></tr></thead>
            <tbody>
                @forelse($acquisitions as $acquisition)
                    <tr>
                        <td>{{ $acquisition->person_core_reference }}</td>
                        <td>{{ number_format($acquisition->amount, 0, ',', ' ') }} {{ $acquisition->currency }}</td>
                        <td>{{ $acquisition->fees !== null ? number_format($acquisition->fees, 0, ',', ' ') : '—' }}</td>
                        <td><x-dg.badge :tone="$statusTones[$acquisition->status] ?? 'neutral'">{{ $acquisition->status }}</x-dg.badge></td>
                        <td class="ac-wrap">{{ $acquisition->created_at?->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="ac-empty">Aucune acquisition ne correspond à ces filtres.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="ac-pagination">{{ $acquisitions->links() }}</div>
</x-layouts.admin>
