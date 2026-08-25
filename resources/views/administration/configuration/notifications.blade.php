<x-layouts.admin title="Notifications — Configuration — Administration" current="configuration">
    <div class="ac-page-head">
        <div>
            <p class="dg-label dg-label--saffron">Configuration</p>
            <h1>Notifications (FYI)</h1>
            <p>Fenêtres et limites d’affichage du moteur de notifications « pour information ». Jamais un mur de statistiques — ces limites gardent l’affichage sobre.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('administration.configuration.notifications.update') }}" class="admin-form">
        @csrf @method('PUT')
        <section>
            <h2>Fenêtres et limites</h2>
            <label>Fenêtre de rétroaction (jours)<input type="number" name="lookback_days" min="1" max="365" value="{{ old('lookback_days', $configuration['lookback_days']) }}" required></label>
            <label>Maximum d’éléments actionnables<input type="number" name="max_actionable" min="1" max="500" value="{{ old('max_actionable', $configuration['max_actionable']) }}" required></label>
            <label>Maximum d’éléments récents<input type="number" name="max_recent" min="1" max="500" value="{{ old('max_recent', $configuration['max_recent']) }}" required></label>
            <label>Bassin de balayage maximal<input type="number" name="scan_limit" min="1" max="2000" value="{{ old('scan_limit', $configuration['scan_limit']) }}" required></label>
        </section>
        <section>
            <h2>Types activés</h2>
            <label class="check-row"><input type="checkbox" name="mission_fyi_enabled" value="1" @checked($configuration['mission_fyi_enabled'])> Missions</label>
            <label class="check-row"><input type="checkbox" name="transmission_fyi_enabled" value="1" @checked($configuration['transmission_fyi_enabled'])> Transmissions</label>
            <label class="check-row"><input type="checkbox" name="proof_fyi_enabled" value="1" @checked($configuration['proof_fyi_enabled'])> Preuves</label>
        </section>
        <button type="submit" class="primary-button">Enregistrer la configuration</button>
    </form>
</x-layouts.admin>
