<x-layouts.admin title="Ledger — Administration" current="ledger">
    <div class="ac-page-head">
        <div>
            <p class="dg-label dg-label--saffron">Finance</p>
            <h1>Ledger</h1>
            <p>Journal financier immuable, seule vérité des mouvements. Lecture seule — aucune écriture manuelle n’est possible ici.</p>
        </div>
    </div>

    <form method="GET" class="ac-filters">
        <div class="dg-field">
            <label for="purpose_code">Raison métier</label>
            <select name="purpose_code" id="purpose_code" class="dg-select">
                <option value="">Toutes</option>
                @foreach($purposeCodes as $code)
                    <option value="{{ $code }}" @selected(($filters['purpose_code'] ?? '') === $code)>{{ $code }}</option>
                @endforeach
            </select>
        </div>
        <div class="dg-field">
            <label for="direction">Sens</label>
            <select name="direction" id="direction" class="dg-select">
                <option value="">Tous</option>
                <option value="CREDIT" @selected(($filters['direction'] ?? '') === 'CREDIT')>Crédit</option>
                <option value="DEBIT" @selected(($filters['direction'] ?? '') === 'DEBIT')>Débit</option>
            </select>
        </div>
        <div class="dg-field"><label for="subject_reference">Référence du sujet</label><input type="text" name="subject_reference" id="subject_reference" class="dg-input" value="{{ $filters['subject_reference'] ?? '' }}"></div>
        <div class="dg-field"><label for="from">Du</label><input type="date" name="from" id="from" class="dg-input" value="{{ $filters['from'] ?? '' }}"></div>
        <div class="dg-field"><label for="to">Au</label><input type="date" name="to" id="to" class="dg-input" value="{{ $filters['to'] ?? '' }}"></div>
        <button type="submit" class="dg-btn dg-btn--quiet">Filtrer</button>
    </form>

    <div class="ac-table-wrap">
        <table class="ac-table">
            <thead><tr><th>Raison</th><th>Sens</th><th>Montant</th><th>Sujet</th><th>Acteur</th><th class="ac-wrap">Survenu</th></tr></thead>
            <tbody>
                @forelse($entries as $entry)
                    <tr>
                        <td>{{ $entry->purpose_code ?? '—' }}</td>
                        <td><x-dg.badge :tone="$entry->direction === 'CREDIT' ? 'success' : 'need'">{{ $entry->direction }}</x-dg.badge></td>
                        <td>{{ number_format($entry->amount, 0, ',', ' ') }} {{ $entry->currency }}</td>
                        <td>{{ $entry->subject_type }} · {{ $entry->subject_reference }}</td>
                        <td>{{ $entry->payer_core_reference }}</td>
                        <td class="ac-wrap">{{ $entry->occurred_at?->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="ac-empty">Aucune écriture ne correspond à ces filtres.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="ac-pagination">{{ $entries->links() }}</div>
</x-layouts.admin>
