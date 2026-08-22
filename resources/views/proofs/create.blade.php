{{--
    Enregistrer une preuve — fiche CARNET DE PREUVES §6/§7. Toujours valide de manière
    autonome (origine NONE/INTERACTION) ; un rattachement à un contexte reste facultatif et
    ne fabrique jamais d'accès à ce contexte.
--}}
<x-layouts.portal title="Enregistrer une preuve — DG Afrique">
    <x-dg.shell :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:820px">
            <a href="{{ route('proofs.index') }}" class="dg-crumb">← Mon Carnet de preuves</a>

            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="saffron">CAP-036 · Carnet de preuves</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">Enregistrer une preuve</h1>
                    <p>Décrivez une chose réelle qui s’est produite. Enregistrer une preuve ne certifie rien : un témoin ou une autorité de contexte peuvent la corroborer, jamais la garantir.</p>
                </div>
            </div>

            @if($errors->any())
                <div class="dg-band" style="margin-bottom:20px;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>
            @endif

            @if($prefillTransmission)
                {{-- UIUX-007 — contexte conservé depuis une Transmission réellement terminée à
                     laquelle vous avez réellement participé. Citer ce contexte ne certifie rien :
                     un témoin ou une autorité de contexte peuvent seulement le corroborer. --}}
                <div class="dg-band" style="margin-bottom:20px">Vous enregistrez cette preuve depuis la Transmission « {{ $prefillTransmission->capability_label }} », terminée.</div>
            @endif

            <form method="POST" action="{{ route('proofs.store') }}" style="display:flex;flex-direction:column;gap:20px">
                @csrf

                <x-dg.fieldset>
                    <legend><x-dg.label>Ce qui s’est passé</x-dg.label></legend>
                    <div class="dg-field">
                        <label for="title">Titre</label>
                        <input type="text" name="title" id="title" class="dg-input" value="{{ old('title') }}" maxlength="200" required>
                    </div>
                    <div class="dg-field">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="dg-textarea" rows="5" minlength="10" maxlength="3000" required>{{ old('description') }}</textarea>
                    </div>
                    <div class="dg-field">
                        <label for="capability_label">Capacité concernée (facultatif)</label>
                        <input type="text" name="capability_label" id="capability_label" class="dg-input" value="{{ old('capability_label', $prefillTransmission?->capability_label) }}" maxlength="200">
                    </div>
                    <div class="dg-field">
                        <label for="occurred_at">Date de l’événement</label>
                        <input type="date" name="occurred_at" id="occurred_at" class="dg-input" value="{{ old('occurred_at') }}">
                    </div>
                </x-dg.fieldset>

                <x-dg.fieldset>
                    <legend><x-dg.label>Porteur</x-dg.label></legend>
                    <div class="dg-field-grid">
                        <div class="dg-field">
                            <label for="owner_type">Cette preuve concerne</label>
                            <select name="owner_type" id="owner_type" class="dg-select" required>
                                <option value="PERSON" selected>Moi-même</option>
                                <option value="GROUP">Une ZUMRA que je dirige</option>
                            </select>
                        </div>
                        <div class="dg-field">
                            <label for="group_reference">Référence de la ZUMRA (si porteur collectif)</label>
                            <input type="text" name="group_reference" id="group_reference" class="dg-input" value="{{ old('group_reference') }}" placeholder="Référence publique de la ZUMRA">
                        </div>
                    </div>
                </x-dg.fieldset>

                <x-dg.fieldset>
                    <legend><x-dg.label>Origine et contexte (facultatif)</x-dg.label></legend>
                    <div class="dg-field-grid">
                        @php($originType = old('origin_type', $prefillTransmission ? 'TRANSMISSION' : 'INTERACTION'))
                        <div class="dg-field">
                            <label for="origin_type">Origine</label>
                            <select name="origin_type" id="origin_type" class="dg-select" required>
                                <option value="INTERACTION" {{ $originType === 'INTERACTION' ? 'selected' : '' }}>Une interaction ponctuelle</option>
                                <option value="NONE" {{ $originType === 'NONE' ? 'selected' : '' }}>Aucune — enregistrement autonome</option>
                                <option value="MISSION" {{ $originType === 'MISSION' ? 'selected' : '' }}>Une Mission</option>
                                <option value="TRANSMISSION" {{ $originType === 'TRANSMISSION' ? 'selected' : '' }}>Une Transmission</option>
                                <option value="NEED" {{ $originType === 'NEED' ? 'selected' : '' }}>Un Besoin</option>
                                <option value="PROJECT" {{ $originType === 'PROJECT' ? 'selected' : '' }}>Un Projet</option>
                                <option value="ZUMRA" {{ $originType === 'ZUMRA' ? 'selected' : '' }}>Une ZUMRA</option>
                            </select>
                        </div>
                        <div class="dg-field">
                            <label for="origin_reference">Référence publique de l’origine</label>
                            <input type="text" name="origin_reference" id="origin_reference" class="dg-input" value="{{ old('origin_reference', $prefillTransmission?->public_reference) }}" placeholder="Requise si une origine avec contexte est choisie">
                            <span class="dg-hint">Citer un contexte ne fabrique jamais d’accès à celui-ci ; il permet seulement une future reconnaissance par son autorité.</span>
                        </div>
                    </div>
                </x-dg.fieldset>

                <x-dg.fieldset>
                    <legend><x-dg.label>Référence (facultatif)</x-dg.label></legend>
                    <div class="dg-field-grid">
                        <div class="dg-field">
                            <label for="reference_type">Type</label>
                            <select name="reference_type" id="reference_type" class="dg-select">
                                <option value="">Aucune</option>
                                <option value="EXTERNAL_URL">Lien externe</option>
                                <option value="FREE_TEXT">Note libre</option>
                            </select>
                        </div>
                        <div class="dg-field">
                            <label for="reference_value">Valeur</label>
                            <input type="text" name="reference_value" id="reference_value" class="dg-input" value="{{ old('reference_value') }}" maxlength="2000">
                        </div>
                        <div class="dg-field">
                            <label for="reference_label">Libellé</label>
                            <input type="text" name="reference_label" id="reference_label" class="dg-input" value="{{ old('reference_label') }}" maxlength="200">
                        </div>
                    </div>
                    <p class="dg-hint">Le stockage documentaire fédéré (GamaDrive) n’existe pas encore : seuls un lien externe ou une note libre sont utilisables aujourd’hui.</p>
                </x-dg.fieldset>

                <x-dg.fieldset>
                    <legend><x-dg.label>Visibilité</x-dg.label></legend>
                    <div class="dg-field">
                        <select name="visibility" id="visibility" class="dg-select" required>
                            <option value="PRIVATE" selected>Privée (par défaut)</option>
                            <option value="DISCOVERABLE">Visible dans ma Mémoire d’expérience publique</option>
                        </select>
                    </div>
                </x-dg.fieldset>

                <button type="submit" class="dg-btn dg-btn--saffron" style="align-self:flex-start">Enregistrer la preuve</button>
            </form>
        </div>
    </x-dg.shell>
</x-layouts.portal>
