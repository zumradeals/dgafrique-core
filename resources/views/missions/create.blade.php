{{--
    Création d'une Mission — toujours dans un contexte réel (Projet/ZUMRA/Besoin, ou une
    Mission parente). Le brouillon créé ici doit ensuite être proposé puis officialisé :
    deux décisions distinctes, jamais une mutation silencieuse.
--}}
<x-layouts.portal title="Proposer une Mission — DG Afrique">
    <x-dg.shell :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:820px">
            <a href="{{ $backUrl }}" class="dg-crumb">← Retour</a>

            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="saffron">CAP-069 · Missions</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">
                        {{ $parent ? 'Proposer une sous-Mission' : 'Proposer une Mission' }}
                    </h1>
                    <p>Décrivez une action explicite, attribuable et vérifiable. Elle reste un brouillon tant qu’elle n’est pas proposée puis officialisée.</p>
                </div>
            </div>

            @if($errors->any())
                <div class="dg-band" style="margin-bottom:20px;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ $storeUrl }}" style="display:flex;flex-direction:column;gap:20px">
                @csrf

                <x-dg.fieldset>
                    <legend><x-dg.label>La Mission</x-dg.label></legend>
                    <div class="dg-field">
                        <label for="title">Titre</label>
                        <input type="text" name="title" id="title" class="dg-input" value="{{ old('title') }}" maxlength="180" required>
                    </div>
                    <div class="dg-field">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="dg-textarea" rows="5" minlength="20" maxlength="4000" required>{{ old('description') }}</textarea>
                    </div>
                    <div class="dg-field">
                        <label for="expected_result">Résultat attendu</label>
                        <textarea name="expected_result" id="expected_result" class="dg-textarea" rows="3" maxlength="2000">{{ old('expected_result') }}</textarea>
                        <span class="dg-hint">Facultatif au brouillon, obligatoire avant l’officialisation.</span>
                    </div>
                </x-dg.fieldset>

                <x-dg.fieldset>
                    <legend><x-dg.label>Modalités</x-dg.label></legend>
                    <div class="dg-field-grid">
                        <div class="dg-field">
                            <label for="visibility">Visibilité</label>
                            <select name="visibility" id="visibility" class="dg-select" required>
                                <option value="CONTEXT" selected>Contexte (participants autorisés)</option>
                                <option value="PRIVATE">Privée</option>
                                <option value="PROGRAM">Membres du Programme ZUMRA</option>
                                <option value="PUBLIC">Réseau DG Afrique</option>
                            </select>
                            <span class="dg-hint">Ne peut jamais dépasser la visibilité du contexte d’origine.</span>
                        </div>
                        <div class="dg-field">
                            <label for="participation_mode">Mode de participation</label>
                            <select name="participation_mode" id="participation_mode" class="dg-select">
                                <option value="">Non précisé</option>
                                <option value="PHYSICAL">Physique</option>
                                <option value="DIGITAL">Numérique</option>
                                <option value="HYBRID">Hybride</option>
                            </select>
                        </div>
                        <div class="dg-field">
                            <label for="location">Lieu</label>
                            <input type="text" name="location" id="location" class="dg-input" value="{{ old('location') }}" maxlength="200">
                        </div>
                        <div class="dg-field">
                            <label for="due_at">Échéance</label>
                            <input type="date" name="due_at" id="due_at" class="dg-input" value="{{ old('due_at') }}">
                        </div>
                        <div class="dg-field">
                            <label for="min_executors">Exécutants minimum</label>
                            <input type="number" name="min_executors" id="min_executors" class="dg-input" min="1" max="200" value="{{ old('min_executors', 1) }}">
                        </div>
                        <div class="dg-field">
                            <label for="max_executors">Exécutants maximum</label>
                            <input type="number" name="max_executors" id="max_executors" class="dg-input" min="1" max="200" value="{{ old('max_executors') }}">
                        </div>
                    </div>
                </x-dg.fieldset>

                <button type="submit" class="dg-btn dg-btn--saffron" style="align-self:flex-start">Enregistrer le brouillon</button>
            </form>
        </div>
    </x-dg.shell>
</x-layouts.portal>
