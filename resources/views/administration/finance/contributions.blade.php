@php
    $statusTones = ['PENDING' => 'decision', 'PROCESSING' => 'project', 'COMPLETED' => 'success', 'FAILED' => 'danger', 'CANCELLED' => 'neutral', 'REFUNDED' => 'neutral'];
@endphp
<x-layouts.admin title="Contributions & adhésions — Administration" current="contributions">
    <div class="ac-page-head">
        <div>
            <p class="dg-label dg-label--saffron">Finance</p>
            <h1>Contributions & adhésions</h1>
            <p>Tentatives de paiement, en lecture seule. {{ $contributionsActive }} engagement(s) de contribution actif(s) actuellement.</p>
        </div>
        <div class="ac-page-head__actions">
            <a href="{{ route('administration.contributions.edit') }}" class="dg-btn dg-btn--quiet">Configurer montants & finalités →</a>
        </div>
    </div>

    <section class="ac-section">
        <div class="ac-section__head"><h2>Paiements de contribution (CAP-061)</h2></div>
        <div class="ac-badge-row" style="margin-bottom:14px">
            @foreach($byStatus as $status => $count)
                <x-dg.badge :tone="$statusTones[$status] ?? 'neutral'">{{ $status }} · {{ $count }}</x-dg.badge>
            @endforeach
        </div>
        <form method="GET" class="ac-filters">
            <div class="dg-field">
                <label for="status">Statut</label>
                <select name="status" id="status" class="dg-select">
                    <option value="">Tous</option>
                    @foreach(array_keys($statusTones) as $status)<option value="{{ $status }}" @selected($statusFilter === $status)>{{ $status }}</option>@endforeach
                </select>
            </div>
            <button type="submit" class="dg-btn dg-btn--quiet">Filtrer</button>
        </form>
        <div class="ac-table-wrap">
            <table class="ac-table">
                <thead><tr><th>Période</th><th>Montant</th><th>Statut</th><th class="ac-wrap">Créé</th></tr></thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ $payment->period }}</td>
                            <td>{{ number_format($payment->amount, 0, ',', ' ') }} {{ $payment->currency }}</td>
                            <td><x-dg.badge :tone="$statusTones[$payment->status] ?? 'neutral'">{{ $payment->status }}</x-dg.badge></td>
                            <td class="ac-wrap">{{ $payment->created_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="ac-empty">Aucun paiement ne correspond à ces filtres.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="ac-pagination">{{ $payments->links() }}</div>
    </section>

    <section class="ac-section">
        <div class="ac-section__head"><h2>Paiements d’adhésion ZUMRA (CAP-007B)</h2></div>
        <p class="dg-hint" style="margin-bottom:10px">Prix verrouillé à 500 XOF — jamais un montant configurable ici.</p>
        <form method="GET" class="ac-filters">
            <div class="dg-field">
                <label for="membership_status">Statut</label>
                <select name="membership_status" id="membership_status" class="dg-select">
                    <option value="">Tous</option>
                    @foreach(array_keys($statusTones) as $status)<option value="{{ $status }}" @selected($membershipStatusFilter === $status)>{{ $status }}</option>@endforeach
                </select>
            </div>
            <button type="submit" class="dg-btn dg-btn--quiet">Filtrer</button>
        </form>
        <div class="ac-table-wrap">
            <table class="ac-table">
                <thead><tr><th>Identité</th><th>Montant</th><th>Statut</th><th class="ac-wrap">Créé</th></tr></thead>
                <tbody>
                    @forelse($membershipPayments as $payment)
                        @php($membership = $memberships->get($payment->membership_id))
                        <tr>
                            <td>{{ $membership?->core_identity_reference ?? '—' }}</td>
                            <td>{{ number_format($payment->amount, 0, ',', ' ') }} {{ $payment->currency }}</td>
                            <td><x-dg.badge :tone="$statusTones[$payment->status] ?? 'neutral'">{{ $payment->status }}</x-dg.badge></td>
                            <td class="ac-wrap">{{ $payment->created_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="ac-empty">Aucun paiement d’adhésion ne correspond à ces filtres.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="ac-pagination">{{ $membershipPayments->links() }}</div>
    </section>
</x-layouts.admin>
