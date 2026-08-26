{{--
    UIUX-007 — gabarit minimal partagé pour organiser un Événement (CAP-068), gap identifié par
    l'audit Phase A : `storeForZumraGroup()`/`storeForOrganization()` existaient déjà, aucune vue
    GET ne les exposait. Un seul formulaire réutilisé pour les deux organisateurs (ZUMRA et
    Organisation) — même champs validés par `CommunityEventController::validated()`, aucune
    logique métier dupliquée ici.
--}}
<x-layouts.portal title="Organiser un événement — DG Afrique">
    <x-dg.shell :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page zd-page zd-event-page" style="max-width:1280px">
            <a href="{{ $backUrl }}" class="dg-crumb">← {{ $organizerLabel }}</a>

            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="saffron">{{ $organizerLabel }}</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">Organiser un événement</h1>
                    <p>Une rencontre située dans le temps, jamais une activité récurrente ni une Mission.</p>
                </div>
            </div>

            @if($errors->any())
                <div class="dg-band" style="margin-bottom:20px;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ $storeUrl }}" style="display:flex;flex-direction:column;gap:20px">
                @csrf

                <x-dg.fieldset>
                    <legend><x-dg.label>Événement</x-dg.label></legend>
                    <label class="dg-field">
                        <span>Titre</span>
                        <input type="text" name="title" value="{{ old('title') }}" minlength="3" maxlength="160" required>
                    </label>
                    <label class="dg-field">
                        <span>Description</span>
                        <textarea name="description" rows="4" minlength="10" maxlength="4000" required>{{ old('description') }}</textarea>
                    </label>
                    <label class="dg-field">
                        <span>Lieu (facultatif — laisser vide pour un événement numérique)</span>
                        <input type="text" name="location" value="{{ old('location') }}" maxlength="160">
                    </label>
                    <label class="dg-field">
                        <span>Date et heure</span>
                        <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}" required>
                    </label>
                </x-dg.fieldset>

                <x-dg.fieldset>
                    <legend><x-dg.label>Visibilité</x-dg.label></legend>
                    <div class="dg-radio-group">
                        <label class="dg-radio">
                            <input type="radio" name="visibility" value="INTERNAL" @checked(old('visibility', 'INTERNAL') === 'INTERNAL')>
                            Interne — visibilité déléguée à ce contexte
                        </label>
                        <label class="dg-radio">
                            <input type="radio" name="visibility" value="PUBLIC" @checked(old('visibility') === 'PUBLIC')>
                            Publique
                        </label>
                    </div>
                </x-dg.fieldset>

                <x-dg.actions>
                    <x-dg.btn variant="primary" type="submit">Organiser l’événement</x-dg.btn>
                </x-dg.actions>
            </form>
        </div>
    </x-dg.shell>
</x-layouts.portal>
