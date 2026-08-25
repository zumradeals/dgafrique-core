@php
    $typeTones = ['ZUMRA' => 'project', 'PROJECT' => 'action', 'NEED' => 'need', 'MEMBERSHIP' => 'decision', 'MODERATION' => 'danger'];
@endphp
<x-layouts.admin title="Journal — Administration" current="journal">
    <div class="ac-page-head">
        <div>
            <p class="dg-label dg-label--saffron">Pilotage</p>
            <h1>Journal</h1>
            <p>Agrège les journaux métier déjà existants (ZUMRA, Projet, Besoin, Adhésion, Modération) — jamais une nouvelle table d’audit générique, strictement en lecture.</p>
        </div>
    </div>

    <form method="GET" class="ac-filters">
        <div class="dg-field">
            <label for="type">Type</label>
            <select name="type" id="type" class="dg-select">
                <option value="">Tous</option>
                @foreach(\App\Application\Administration\AdminJournalAggregator::TYPES as $key => $label)
                    <option value="{{ $key }}" @selected($typeFilter === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="dg-btn dg-btn--quiet">Filtrer</button>
    </form>

    <div class="ac-table-wrap">
        <table class="ac-table">
            <thead><tr><th>Type</th><th>Événement</th><th>Acteur</th><th class="ac-wrap">Survenu</th></tr></thead>
            <tbody>
                @forelse($entries as $entry)
                    <tr>
                        <td><x-dg.badge :tone="$typeTones[$entry['type']] ?? 'neutral'">{{ $entry['type_label'] }}</x-dg.badge></td>
                        <td>{{ $entry['label'] }}</td>
                        <td>{{ $entry['actor'] }}</td>
                        <td class="ac-wrap">{{ \Illuminate\Support\Carbon::parse($entry['occurred_at'])->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="ac-empty">Aucun événement enregistré.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="ac-pagination">{{ $entries->links() }}</div>
</x-layouts.admin>
