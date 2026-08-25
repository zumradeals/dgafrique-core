<x-layouts.admin title="Vue financière — Administration" current="finance">
    <div class="ac-page-head">
        <div>
            <p class="dg-label dg-label--saffron">Finance</p>
            <h1>Vue financière</h1>
            <p>Strictement en lecture. Le Ledger reste l’unique vérité, les soldes restent dérivés — aucun crédit, débit, ou transfert n’est possible depuis cet écran.</p>
        </div>
    </div>

    <div class="ac-stat-grid">
        <div class="ac-stat"><div class="ac-stat__label">Masse ZAHAB en circulation</div><div class="ac-stat__value">{{ number_format($massZahab, 0, ',', ' ') }}</div><div class="ac-stat__meta">dérivée du Ledger (crédits − débits)</div></div>
        <div class="ac-stat"><div class="ac-stat__label">Écritures Ledger</div><div class="ac-stat__value">{{ number_format($ledgerEntriesTotal, 0, ',', ' ') }}</div><a href="{{ route('administration.ledger.index') }}" style="font-size:11px">Ouvrir le Ledger →</a></div>
    </div>

    <div class="ac-section-grid">
        <section class="ac-section">
            <div class="ac-section__head"><h2>Mouvements par raison métier</h2><a href="{{ route('administration.ledger.index') }}">Investiguer →</a></div>
            <div class="ac-table-wrap">
                <table class="ac-table">
                    <thead><tr><th>Raison</th><th>Écritures</th><th>Volume</th></tr></thead>
                    <tbody>
                        @forelse($byPurpose as $row)
                            <tr><td>{{ $row->purpose_code ?? '—' }}</td><td>{{ $row->total }}</td><td>{{ number_format((int) $row->volume, 0, ',', ' ') }}</td></tr>
                        @empty
                            <tr><td colspan="3"><div class="ac-empty">Aucune écriture Ledger enregistrée.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="ac-section">
            <div class="ac-section__head"><h2>Acquisitions GeniusPay</h2><a href="{{ route('administration.finance.acquisitions') }}">Ouvrir →</a></div>
            <div class="ac-badge-row">
                @forelse($acquisitionsByStatus as $status => $row)
                    <x-dg.badge :tone="$status === 'COMPLETED' ? 'success' : ($status === 'FAILED' ? 'danger' : 'neutral')">{{ $status }} · {{ $row->total }} · {{ number_format((int) $row->volume, 0, ',', ' ') }}</x-dg.badge>
                @empty
                    <span class="dg-meta">Aucune acquisition enregistrée.</span>
                @endforelse
            </div>
        </section>

        <section class="ac-section">
            <div class="ac-section__head"><h2>Paiements de contribution</h2><a href="{{ route('administration.finance.contributions') }}">Ouvrir →</a></div>
            <div class="ac-badge-row">
                @forelse($contributionPaymentsByStatus as $status => $row)
                    <x-dg.badge :tone="$status === 'COMPLETED' ? 'success' : ($status === 'FAILED' ? 'danger' : 'neutral')">{{ $status }} · {{ $row->total }} · {{ number_format((int) $row->volume, 0, ',', ' ') }}</x-dg.badge>
                @empty
                    <span class="dg-meta">Aucun paiement enregistré.</span>
                @endforelse
            </div>
        </section>

        <section class="ac-section">
            <div class="ac-section__head"><h2>Paiements d’adhésion</h2><a href="{{ route('administration.finance.contributions') }}">Ouvrir →</a></div>
            <div class="ac-badge-row">
                @forelse($membershipPaymentsByStatus as $status => $row)
                    <x-dg.badge :tone="$status === 'COMPLETED' ? 'success' : ($status === 'FAILED' ? 'danger' : 'neutral')">{{ $status }} · {{ $row->total }} · {{ number_format((int) $row->volume, 0, ',', ' ') }}</x-dg.badge>
                @empty
                    <span class="dg-meta">Aucun paiement d’adhésion enregistré.</span>
                @endforelse
            </div>
            <p class="dg-hint" style="margin-top:10px">Prix verrouillé à 500 XOF (CAP-007B) — jamais un réglage administrable.</p>
        </section>
    </div>
</x-layouts.admin>
