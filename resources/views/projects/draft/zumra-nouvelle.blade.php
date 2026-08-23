{{--
    PROJET-ZUMRA-INVARIANT-001 — naissance explicite d'une ZUMRA solo depuis le parcours Projet.
    Jamais silencieuse : un vrai formulaire, les mêmes champs et la même autorité que
    ZumraGroupController::store() (aucune logique dupliquée, ZumraGroupService::create() appelé à
    l'identique). Le brouillon Projet n'est jamais perdu — la soumission y revient directement.
--}}
<x-layouts.portal title="Commencer ma ZUMRA — DG Afrique">
    <x-dg.shell current="projets" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:820px">
            <a href="{{ route('projects.draft.show', [$draft, 'audience']) }}" class="dg-crumb">← Revenir à votre projet</a>

            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="saffron">Groupe humain</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">Commencer votre ZUMRA</h1>
                    <p>Elle peut commencer avec vous seul·e. Votre idée de projet reste enregistrée pendant ce temps — vous y reviendrez juste après.</p>
                </div>
            </div>

            <div class="dg-band" style="margin-bottom:24px">
                <strong style="display:block;font-size:14px;color:var(--dg-forest);margin-bottom:4px">Aucun rôle imposé</strong>
                Les quatre autres responsabilités fondatrices restent vacantes jusqu’à de vraies acceptations — rien n’exige de les pourvoir pour commencer.
            </div>

            @if($errors->any())
                <div class="dg-band" style="margin-bottom:20px;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('projects.draft.zumra.store', $draft) }}" style="display:flex;flex-direction:column;gap:20px">
                @csrf

                <x-dg.fieldset>
                    <legend>
                        <span style="font-family:var(--dg-font-mono);font-size:11px;color:var(--dg-faint)">01</span>
                        <x-dg.label>Identité fondatrice</x-dg.label>
                    </legend>
                    <div class="dg-field">
                        <label for="name">Nom de la ZUMRA</label>
                        <input type="text" id="name" name="name" class="dg-input" value="{{ old('name') }}" maxlength="140" required>
                    </div>
                    <div class="dg-field">
                        <label for="domain">Domaine principal</label>
                        <input type="text" id="domain" name="domain" class="dg-input" value="{{ old('domain') }}" maxlength="140" placeholder="Ex. Couture, numérique, agriculture…" required>
                    </div>
                    <div class="dg-field">
                        <label for="founding_objective">Objectif fondateur</label>
                        <textarea id="founding_objective" name="founding_objective" class="dg-textarea" rows="5" minlength="40" maxlength="1800" required>{{ old('founding_objective') }}</textarea>
                        <span class="dg-hint">Décrivez ce que vous voulez apprendre, transmettre, construire ou résoudre — même seul·e pour l’instant.</span>
                    </div>
                </x-dg.fieldset>

                <x-dg.fieldset>
                    <legend>
                        <span style="font-family:var(--dg-font-mono);font-size:11px;color:var(--dg-faint)">02</span>
                        <x-dg.label>Mode de présence</x-dg.label>
                    </legend>
                    <div class="dg-radio-group">
                        @foreach(['PHYSICAL' => 'Physique', 'DIGITAL' => 'Numérique', 'HYBRID' => 'Hybride'] as $value => $label)
                            <label class="dg-radio">
                                <input type="radio" name="participation_mode" value="{{ $value }}" @checked(old('participation_mode', 'HYBRID') === $value)>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </x-dg.fieldset>

                <x-dg.fieldset>
                    <legend>
                        <span style="font-family:var(--dg-font-mono);font-size:11px;color:var(--dg-faint)">03</span>
                        <x-dg.label>Charte interne</x-dg.label>
                    </legend>
                    <div class="dg-field">
                        <label for="internal_charter">Règles fondatrices</label>
                        <textarea id="internal_charter" name="internal_charter" class="dg-textarea" rows="8" minlength="80" maxlength="6000" required>{{ old('internal_charter') }}</textarea>
                        <span class="dg-hint">Précisez la mission, le respect mutuel, les décisions, l’admission, le départ et l’exclusion — même provisoire, elle doit exister.</span>
                    </div>
                </x-dg.fieldset>

                <p class="dg-hint">En commençant cette ZUMRA, vous en devenez la première responsable — un choix explicite, propre à ce démarrage solo.</p>

                <button type="submit" class="dg-btn dg-btn--saffron" style="align-self:flex-start">Créer ma ZUMRA et revenir à mon projet →</button>
            </form>
        </div>
    </x-dg.shell>
</x-layouts.portal>
