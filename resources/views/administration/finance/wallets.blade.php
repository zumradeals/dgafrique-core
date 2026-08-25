<x-layouts.admin title="Wallets ZAHAB — Administration" current="wallets">
    <div class="ac-page-head">
        <div>
            <p class="dg-label dg-label--saffron">Finance</p>
            <h1>Wallets ZAHAB</h1>
            <p>Consultation globale uniquement — aucun bouton « ajouter du ZAHAB » ici. Chaque solde est recalculé depuis le Ledger, jamais stocké.</p>
        </div>
    </div>

    <form method="GET" class="ac-filters">
        <div class="dg-field">
            <label for="subject_type">Type de sujet</label>
            <select name="subject_type" id="subject_type" class="dg-select">
                <option value="">Tous</option>
                @foreach(\App\Models\ZahabWallet::SUBJECTS as $subject)
                    <option value="{{ $subject }}" @selected(($filters['subject_type'] ?? '') === $subject)>{{ $subject }}</option>
                @endforeach
            </select>
        </div>
        <div class="dg-field">
            <label for="subject_reference">Référence du sujet</label>
            <input type="text" name="subject_reference" id="subject_reference" class="dg-input" value="{{ $filters['subject_reference'] ?? '' }}" placeholder="Référence exacte ou partielle">
        </div>
        <button type="submit" class="dg-btn dg-btn--quiet">Filtrer</button>
    </form>

    <div class="ac-table-wrap">
        <table class="ac-table">
            <thead><tr><th>Type</th><th>Référence</th><th>Solde dérivé</th><th class="ac-wrap">Créé</th></tr></thead>
            <tbody>
                @forelse($wallets as $wallet)
                    <tr>
                        <td>{{ $wallet->subject_type }}</td>
                        <td>{{ $wallet->subject_reference }}</td>
                        <td><strong>{{ number_format($wallet->derived_balance, 0, ',', ' ') }}</strong> ZAHAB</td>
                        <td class="ac-wrap">{{ $wallet->created_at?->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="ac-empty">Aucun Wallet ne correspond à ces filtres.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="ac-pagination">{{ $wallets->links() }}</div>
</x-layouts.admin>
