{{--
    Exprimer un blocage comme besoin — jamais automatique. Confirmation humaine explicite
    requise ; le Need passe strictement par NeedService puis se lie au blocage.
--}}
<x-layouts.portal title="Exprimer ce manque comme besoin — DG Afrique">
    <x-dg.shell :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:720px">
            <a href="{{ route('missions.show', $mission) }}" class="dg-crumb">← {{ $mission->title }}</a>

            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="copper">Blocage → besoin</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">Exprimer ce manque comme besoin</h1>
                    <p>{{ $blocker->description }}</p>
                </div>
            </div>

            @if($errors->any())
                <div class="dg-band" style="margin-bottom:20px;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('missions.blockers.express-need', [$mission, $blocker]) }}" style="display:flex;flex-direction:column;gap:20px">
                @csrf

                <x-dg.fieldset>
                    <legend><x-dg.label>Le besoin</x-dg.label></legend>
                    <div class="dg-field">
                        <label for="title">Titre</label>
                        <input type="text" name="title" id="title" class="dg-input" value="{{ old('title', $mission->title) }}" maxlength="180" required>
                    </div>
                    <div class="dg-field">
                        <label for="context">Contexte</label>
                        <textarea name="context" id="context" class="dg-textarea" rows="6" minlength="40" maxlength="3000" required>{{ old('context', $blocker->description) }}</textarea>
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
                            <label for="collaboration_mode">Mode de collaboration</label>
                            <select name="collaboration_mode" id="collaboration_mode" class="dg-select">
                                <option value="ANY">Indifférent</option>
                                <option value="LOCAL">Sur place</option>
                                <option value="REMOTE">À distance</option>
                                <option value="HYBRID">Hybride</option>
                            </select>
                        </div>
                        <div class="dg-field">
                            <label for="visibility">Visibilité</label>
                            <select name="visibility" id="visibility" class="dg-select">
                                <option value="PRIVATE">Privé</option>
                                <option value="PROGRAM">Membres ZUMRA</option>
                                <option value="PUBLIC">Réseau DG Afrique</option>
                            </select>
                        </div>
                    </div>
                </x-dg.fieldset>

                <label class="dg-consent">
                    <input type="checkbox" name="confirm" value="1" required>
                    <span>
                        <strong>Je confirme vouloir publier ce besoin.</strong>
                        Un besoin réel sera créé et lié à ce blocage. Résoudre ce besoin plus tard ne débloquera pas automatiquement la Mission.
                    </span>
                </label>

                <button type="submit" class="dg-btn dg-btn--need" style="align-self:flex-start">Créer et lier ce besoin</button>
            </form>
        </div>
    </x-dg.shell>
</x-layouts.portal>
