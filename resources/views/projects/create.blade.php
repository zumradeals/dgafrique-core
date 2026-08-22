{{--
    Formulaire de proposition de projet — mêmes règles que ProjectController::store().
    La maturité initiale reste « idée » ; elle n'évolue qu'avec des signes observables.
--}}
<x-layouts.portal title="Proposer un projet — DG Afrique">
    <x-dg.shell current="projets" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:820px">
            <a href="{{ route('projects.index') }}" class="dg-crumb">← Tous les projets</a>

            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="night">Proposition structurée</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">{{ $configuration['creation_title'] }}</h1>
                    <p>Le dossier décrit une direction de travail. Il ne transfère aucune propriété et n’ouvre aucun paiement.</p>
                </div>
            </div>

            @if($errors->any())
                <div class="dg-band" style="margin-bottom:20px;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('projects.store') }}" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:20px">
                @csrf

                <x-dg.fieldset>
                    <legend><x-dg.label>01 · Porteur et origine</x-dg.label></legend>
                    <div class="dg-radio-group">
                        <label class="dg-radio">
                            <input type="radio" name="owner_type" value="PERSON" @checked(old('owner_type', 'PERSON') === 'PERSON')>
                            Projet personnel accompagné
                        </label>
                        @if($groups->isNotEmpty())
                            <label class="dg-radio">
                                <input type="radio" name="owner_type" value="GROUP" @checked(old('owner_type', $preselectedGroup ? 'GROUP' : null) === 'GROUP')>
                                Projet porté par une ZUMRA
                            </label>
                        @endif
                    </div>
                    <div class="dg-field-grid">
                        @if($groups->isNotEmpty())
                            <div class="dg-field">
                                <label for="group_reference">ZUMRA</label>
                                <select name="group_reference" id="group_reference" class="dg-select">
                                    <option value="">Choisir</option>
                                    @foreach($groups as $group)
                                        <option value="{{ $group->public_reference }}" @selected(old('group_reference', $preselectedGroup?->public_reference) === $group->public_reference)>{{ $group->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="dg-field">
                            <label for="source_need_reference">Besoin d’origine facultatif</label>
                            <select name="source_need_reference" id="source_need_reference" class="dg-select">
                                <option value="">Aucun</option>
                                @foreach($needs as $need)
                                    <option value="{{ $need->public_reference }}" @selected(old('source_need_reference') === $need->public_reference)>{{ $need->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="dg-field">
                            <label for="property_regime">Régime explicite</label>
                            <select name="property_regime" id="property_regime" class="dg-select">
                                <option value="PERSONAL_SUPPORTED">Personnel accompagné</option>
                                <option value="ZUMRA_COLLECTIVE">Collectif de ZUMRA</option>
                                <option value="PARTNERSHIP">En partenariat</option>
                                <option value="COMMUNITY_INTEREST">Intérêt communautaire</option>
                            </select>
                        </div>
                    </div>
                </x-dg.fieldset>

                <x-dg.fieldset>
                    <legend><x-dg.label>02 · Problème et réponse</x-dg.label></legend>
                    <div class="dg-field">
                        <label for="name">Nom du projet</label>
                        <input type="text" name="name" id="name" class="dg-input" value="{{ old('name') }}" required>
                    </div>
                    <div class="dg-field">
                        <label for="summary">Résumé</label>
                        <textarea name="summary" id="summary" class="dg-textarea" rows="4" required>{{ old('summary') }}</textarea>
                    </div>
                    <div class="dg-field">
                        <label for="problem">Problème observé</label>
                        <textarea name="problem" id="problem" class="dg-textarea" rows="5" required>{{ old('problem') }}</textarea>
                    </div>
                    <div class="dg-field">
                        <label for="proposed_solution">Solution envisagée</label>
                        <textarea name="proposed_solution" id="proposed_solution" class="dg-textarea" rows="5" required>{{ old('proposed_solution') }}</textarea>
                    </div>
                    <div class="dg-field">
                        <label for="beneficiaries">Bénéficiaires</label>
                        <textarea name="beneficiaries" id="beneficiaries" class="dg-textarea" rows="3" required>{{ old('beneficiaries') }}</textarea>
                    </div>
                </x-dg.fieldset>

                <x-dg.fieldset>
                    <legend><x-dg.label>03 · Mobilisation</x-dg.label></legend>
                    <div class="dg-field-grid">
                        <div class="dg-field">
                            <label for="domain">Domaine</label>
                            <select name="domain" id="domain" class="dg-select">
                                @foreach($configuration['domains'] as $code => $label)
                                    <option value="{{ $code }}" @selected(old('domain') === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="dg-field">
                            <label for="participation_mode">Mode</label>
                            <select name="participation_mode" id="participation_mode" class="dg-select">
                                <option value="PHYSICAL">Physique</option>
                                <option value="DIGITAL">Numérique</option>
                                <option value="HYBRID">Hybride</option>
                            </select>
                        </div>
                        <div class="dg-field">
                            <label for="location">Lieu</label>
                            <input type="text" name="location" id="location" class="dg-input" value="{{ old('location') }}">
                        </div>
                        <div class="dg-field">
                            <label for="image">Image (facultative)</label>
                            <input type="file" name="image" id="image" class="dg-input" accept="image/png,image/jpeg,image/webp">
                            <p class="dg-hint">Un seul visuel, 4 Mo maximum.</p>
                        </div>
                        <div class="dg-field">
                            <label for="visibility">Visibilité</label>
                            <select name="visibility" id="visibility" class="dg-select">
                                <option value="PRIVATE">Privée</option>
                                <option value="GROUP">ZUMRA</option>
                                <option value="PROGRAM">Membres ZUMRA</option>
                                <option value="PUBLIC">Réseau DG Afrique</option>
                            </select>
                        </div>
                    </div>
                    <div class="dg-field">
                        <label for="objectives">Objectifs — un par ligne</label>
                        <textarea name="objectives" id="objectives" class="dg-textarea" rows="4" required>{{ old('objectives') }}</textarea>
                    </div>
                    <div class="dg-field">
                        <label for="required_capabilities">Capacités nécessaires — une par ligne</label>
                        <textarea name="required_capabilities" id="required_capabilities" class="dg-textarea" rows="4">{{ old('required_capabilities') }}</textarea>
                    </div>
                    <div class="dg-field">
                        <label for="required_resources">Ressources nécessaires — une par ligne</label>
                        <textarea name="required_resources" id="required_resources" class="dg-textarea" rows="4">{{ old('required_resources') }}</textarea>
                    </div>
                    <div class="dg-field">
                        <label for="risks">Risques connus — un par ligne</label>
                        <textarea name="risks" id="risks" class="dg-textarea" rows="4">{{ old('risks') }}</textarea>
                    </div>
                </x-dg.fieldset>

                <x-dg.fieldset>
                    <legend><x-dg.label>04 · Premières étapes</x-dg.label></legend>
                    <div class="dg-field">
                        <label for="milestones">Étapes — une par ligne</label>
                        <textarea name="milestones" id="milestones" class="dg-textarea" rows="6" required>{{ old('milestones') }}</textarea>
                    </div>
                    <p class="dg-hint">La maturité initiale reste « idée ». Elle évoluera seulement avec des signes observables.</p>
                </x-dg.fieldset>

                <button type="submit" class="dg-btn dg-btn--project" style="align-self:flex-start">Enregistrer la proposition</button>
            </form>
        </div>
    </x-dg.shell>
</x-layouts.portal>
