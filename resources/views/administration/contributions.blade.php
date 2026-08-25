<x-layouts.admin title="Contributions — Configuration — Administration" current="configuration">
    <div class="ac-page-head">
        <div>
            <p class="dg-label dg-label--saffron">Configuration</p>
            <h1>Contributions (CAP-061)</h1>
            <p>Montants, activation et finalités. Le prix d’adhésion (500 XOF) reste verrouillé ailleurs (CAP-007B) — jamais réglable ici.</p>
        </div>
        <div class="ac-page-head__actions">
            <a href="{{ route('administration.finance.contributions') }}" class="dg-btn dg-btn--quiet">Voir les paiements →</a>
        </div>
    </div>

    <form method="PUT" action="{{ route('administration.contributions.update') }}" class="admin-form">
        @csrf @method('PUT')
        <section>
            <h2>Contribution individuelle</h2>
            <label class="check-row"><input type="checkbox" name="individual_enabled" value="1" @checked($configuration['individual_enabled'])> Activée</label>
            <label>Montant (XOF)<input type="number" name="individual_amount" min="1" max="1000000" value="{{ old('individual_amount', $configuration['individual_amount']) }}" required></label>
        </section>
        <section>
            <h2>Contribution collective</h2>
            <label class="check-row"><input type="checkbox" name="collective_enabled" value="1" @checked($configuration['collective_enabled'])> Activée</label>
            <label>Montant (XOF)<input type="number" name="collective_amount" min="1" max="10000000" value="{{ old('collective_amount', $configuration['collective_amount']) }}" required></label>
        </section>
        <section>
            <h2>Devise</h2>
            <label>Code devise (3 lettres)<input type="text" name="currency" maxlength="3" value="{{ old('currency', $configuration['currency']) }}" required></label>
        </section>
        <button type="submit" class="primary-button">Enregistrer la configuration</button>
    </form>

    <section class="ac-section" style="margin-top:24px">
        <div class="ac-section__head"><h2>Finalités des contributions</h2></div>
        <div class="ac-table-wrap">
            <table class="ac-table">
                <thead><tr><th>Code</th><th>Libellé</th><th>Statut</th><th>Action</th></tr></thead>
                <tbody>
                    @foreach($purposes as $purpose)
                        <tr>
                            <td>{{ $purpose->code }}</td>
                            <td>{{ $purpose->label }}</td>
                            <td><x-dg.badge :tone="$purpose->status === 'ACTIVE' ? 'success' : 'neutral'">{{ $purpose->status }}</x-dg.badge></td>
                            <td>
                                @if($purpose->status === 'ACTIVE')
                                    <form method="POST" action="{{ route('administration.contributions.purposes.retire', $purpose) }}">@csrf<button type="submit" class="dg-btn dg-btn--quiet" style="padding:6px 12px;font-size:11px">Retirer</button></form>
                                @else
                                    <form method="POST" action="{{ route('administration.contributions.purposes.reactivate', $purpose) }}">@csrf<button type="submit" class="dg-btn dg-btn--quiet" style="padding:6px 12px;font-size:11px">Réactiver</button></form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.admin>
