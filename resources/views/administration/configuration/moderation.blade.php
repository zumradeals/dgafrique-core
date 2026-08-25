<x-layouts.admin title="Modération — Configuration — Administration" current="configuration">
    <div class="ac-page-head">
        <div>
            <p class="dg-label dg-label--saffron">Configuration</p>
            <h1>Modération</h1>
            <p>Durées par défaut des sanctions disciplinaires. Les règles de modération elles-mêmes (raisons, autorité, recours) restent invariantes — seules ces deux durées sont administrables.</p>
        </div>
        <div class="ac-page-head__actions">
            <a href="{{ route('administration.moderation.index') }}" class="dg-btn dg-btn--quiet">Voir les signalements →</a>
        </div>
    </div>

    <form method="PUT" action="{{ route('administration.configuration.moderation.update') }}" class="admin-form">
        @csrf @method('PUT')
        <section>
            <h2>Durées par défaut</h2>
            <label>Avertissement (jours)<input type="number" name="warning_default_duration_days" min="1" max="365" value="{{ old('warning_default_duration_days', $configuration['warning_default_duration_days']) }}" required></label>
            <label>Suspension (jours)<input type="number" name="suspension_default_duration_days" min="1" max="365" value="{{ old('suspension_default_duration_days', $configuration['suspension_default_duration_days']) }}" required></label>
        </section>
        <button type="submit" class="primary-button">Enregistrer la configuration</button>
    </form>
</x-layouts.admin>
