{{--
    Formulaire d'expression de besoin — mêmes règles que NeedController::store() : un besoin de
    groupe proposé par un membre attend la publication officielle d'un responsable (STATUS_PROPOSED).
--}}
<x-layouts.portal title="Exprimer un besoin — DG Afrique">
    <x-dg.shell current="besoins" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:820px">
            <a href="{{ route('needs.index') }}" class="dg-crumb">← Tous les besoins</a>

            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="copper">Un manque réel, pas une promesse</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">{{ $configuration['creation_title'] }}</h1>
                    <p>Décrivez ce qui manque, pourquoi cela compte et comment une personne pourrait collaborer. Aucun financement ni résultat n’est promis.</p>
                </div>
            </div>

            @if($errors->any())
                <div class="dg-band" style="margin-bottom:20px;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('needs.store') }}" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:20px">
                @csrf

                <x-dg.fieldset>
                    <legend><x-dg.label>Qui porte ce besoin ?</x-dg.label></legend>
                    <div class="dg-radio-group">
                        <label class="dg-radio">
                            <input type="radio" name="owner_type" value="PERSON" @checked(old('owner_type', 'PERSON') === 'PERSON')>
                            Moi-même
                        </label>
                        @if($groups->isNotEmpty())
                            <label class="dg-radio">
                                <input type="radio" name="owner_type" value="GROUP" @checked(old('owner_type', $preselectedGroup ? 'GROUP' : null) === 'GROUP')>
                                Une ZUMRA dont je suis membre
                            </label>
                        @endif
                        @if($projects->isNotEmpty())
                            <label class="dg-radio">
                                <input type="radio" name="owner_type" value="PROJECT" @checked(old('owner_type', $preselectedProject ? 'PROJECT' : null) === 'PROJECT')>
                                Un projet que je porte ou dans l’équipe duquel je suis actif
                            </label>
                        @endif
                    </div>
                    @if($groups->isNotEmpty())
                        <div class="dg-field" style="max-width:340px">
                            <label for="group_reference">ZUMRA</label>
                            <select name="group_reference" id="group_reference" class="dg-select">
                                <option value="">Choisir</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->public_reference }}" @selected(old('group_reference', $preselectedGroup?->public_reference) === $group->public_reference)>{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if($projects->isNotEmpty())
                        <div class="dg-field" style="max-width:340px">
                            <label for="project_reference">Projet</label>
                            <select name="project_reference" id="project_reference" class="dg-select">
                                <option value="">Choisir</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->public_reference }}" @selected(old('project_reference', $preselectedProject?->public_reference) === $project->public_reference)>{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </x-dg.fieldset>

                <x-dg.fieldset>
                    <legend><x-dg.label>Le besoin</x-dg.label></legend>
                    <div class="dg-field">
                        <label for="title">Titre</label>
                        <input type="text" name="title" id="title" class="dg-input" value="{{ old('title') }}" maxlength="180" required>
                    </div>
                    <div class="dg-field">
                        <label for="context">Contexte</label>
                        <textarea name="context" id="context" class="dg-textarea" rows="7" minlength="40" maxlength="3000" required>{{ old('context') }}</textarea>
                    </div>
                    <div class="dg-field-grid">
                        <div class="dg-field">
                            <label for="category">Catégorie</label>
                            <select name="category" id="category" class="dg-select" required>
                                @foreach($configuration['categories'] as $code => $label)
                                    <option value="{{ $code }}" @selected(old('category') === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="dg-field">
                            <label for="capability_label">Capacité recherchée, si applicable</label>
                            <input type="text" name="capability_label" id="capability_label" class="dg-input" value="{{ old('capability_label') }}">
                        </div>
                        <div class="dg-field">
                            <label for="collaboration_mode">Mode de collaboration</label>
                            <select name="collaboration_mode" id="collaboration_mode" class="dg-select">
                                <option value="ANY">Indifférent</option>
                                <option value="LOCAL">Sur place</option>
                                <option value="REMOTE">À distance</option>
                                <option value="HYBRID">Hybride</option>
                            </select>
                        </div>
                        <div class="dg-field">
                            <label for="location">Localisation</label>
                            <input type="text" name="location" id="location" class="dg-input" value="{{ old('location') }}">
                        </div>
                    </div>
                    <div class="dg-field">
                        <label for="image">Image (facultative)</label>
                        <input type="file" name="image" id="image" class="dg-input" accept="image/png,image/jpeg,image/webp">
                        <p class="dg-hint">Un seul visuel, 4 Mo maximum. Aucune galerie.</p>
                    </div>
                </x-dg.fieldset>

                <x-dg.fieldset>
                    <legend><x-dg.label>Audience</x-dg.label></legend>
                    <div class="dg-field" style="max-width:340px">
                        <label for="visibility">Visibilité</label>
                        <select name="visibility" id="visibility" class="dg-select">
                            <option value="PRIVATE">Privé</option>
                            <option value="GROUP">Ma ZUMRA</option>
                            <option value="PROGRAM">Membres ZUMRA</option>
                            <option value="PUBLIC">Réseau DG Afrique</option>
                        </select>
                    </div>
                    <p class="dg-hint">Un besoin de groupe proposé par un membre attend la publication officielle d’un responsable.</p>
                </x-dg.fieldset>

                <button type="submit" class="dg-btn dg-btn--need" style="align-self:flex-start">Publier avec clarté</button>
            </form>
        </div>
    </x-dg.shell>
</x-layouts.portal>
