{{--
    CAP-066 — Création d'une Organisation. Types réutilisés tels quels de
    App\Application\Projects\ProjectAutonomyPathwayService::TARGET_FORMS pour un vocabulaire
    métier stable entre les deux capacités.
--}}
<x-layouts.portal title="Créer une organisation — DG Afrique">
    <x-dg.shell :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:820px">
            <a href="{{ route('organizations.index') }}" class="dg-crumb">← Toutes les organisations</a>

            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="forest">Une structure durable, pas un projet ponctuel</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">Créer une organisation</h1>
                    <p>Vous en devenez la fondatrice ou le fondateur. Aucune identité Core, aucun statut juridique n’est créé automatiquement.</p>
                </div>
            </div>

            @if($errors->any())
                <div class="dg-band" style="margin-bottom:20px;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('organizations.store') }}" style="display:flex;flex-direction:column;gap:20px">
                @csrf

                <x-dg.fieldset>
                    <legend><x-dg.label>Identité</x-dg.label></legend>
                    <label class="dg-field">
                        <span>Nom</span>
                        <input type="text" name="name" value="{{ old('name') }}" minlength="3" maxlength="140" required>
                    </label>
                    <label class="dg-field">
                        <span>Description</span>
                        <textarea name="description" rows="4" minlength="20" maxlength="3000" required>{{ old('description') }}</textarea>
                    </label>
                </x-dg.fieldset>

                <x-dg.fieldset>
                    <legend><x-dg.label>Forme envisagée</x-dg.label></legend>
                    <div class="dg-radio-group">
                        @foreach(\App\Models\Organization::TYPES as $value => $label)
                            <label class="dg-radio">
                                <input type="radio" name="type" value="{{ $value }}" @checked(old('type') === $value) required>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <label class="dg-field">
                        <span>Si « Autre forme pertinente », précisez</span>
                        <input type="text" name="other_type_label" value="{{ old('other_type_label') }}" maxlength="140">
                    </label>
                </x-dg.fieldset>

                <x-dg.fieldset>
                    <legend><x-dg.label>Visibilité</x-dg.label></legend>
                    <div class="dg-radio-group">
                        <label class="dg-radio">
                            <input type="radio" name="visibility" value="PRIVATE" @checked(old('visibility', 'PRIVATE') === 'PRIVATE')>
                            Privée — visible uniquement par ses membres
                        </label>
                        <label class="dg-radio">
                            <input type="radio" name="visibility" value="PUBLIC" @checked(old('visibility') === 'PUBLIC')>
                            Publique — visible par tout membre DG Afrique connecté
                        </label>
                    </div>
                </x-dg.fieldset>

                <x-dg.actions>
                    <x-dg.btn variant="primary" type="submit">Créer l’organisation</x-dg.btn>
                </x-dg.actions>
            </form>
        </div>
    </x-dg.shell>
</x-layouts.portal>
