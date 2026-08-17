{{--
    Constitution d'une ZUMRA — aucune validation, financement ni nomination automatique.
    Cinq personnes distinctes doivent accepter les cinq responsabilités fondatrices (CAP-011).
--}}
<x-layouts.portal title="Proposer une ZUMRA — DG Afrique">
    <x-dg.shell current="zumra" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:820px">
            <a href="{{ route('zumra.groups.index') }}" class="dg-crumb">← Les ZUMRA</a>

            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="saffron">CAP-011 · Groupe humain</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">{{ $configuration['creation_title'] }}</h1>
                    <p>Donnez une identité claire à l’équipe. La création ouvre une constitution : elle ne produit ni validation, ni financement, ni nomination automatique.</p>
                </div>
            </div>

            <div class="dg-band" style="margin-bottom:24px">
                <strong style="display:block;font-size:14px;color:var(--dg-forest);margin-bottom:4px">5 personnes distinctes</strong>
                doivent accepter les cinq responsabilités fondatrices avant que la ZUMRA soit prête à valider.
            </div>

            @if($errors->any())
                <div class="dg-band" style="margin-bottom:20px;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('zumra.groups.store') }}" style="display:flex;flex-direction:column;gap:20px">
                @csrf

                <x-dg.fieldset>
                    <legend>
                        <span style="font-family:var(--dg-font-mono);font-size:11px;color:var(--dg-faint)">01</span>
                        <x-dg.label>Identité fondatrice</x-dg.label>
                    </legend>
                    <p class="dg-hint">Cette identité doit rester cohérente pendant la vie de la ZUMRA.</p>
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
                        <span class="dg-hint">Décrivez ce que l’équipe veut apprendre, transmettre, construire ou résoudre.</span>
                    </div>
                </x-dg.fieldset>

                <x-dg.fieldset>
                    <legend>
                        <span style="font-family:var(--dg-font-mono);font-size:11px;color:var(--dg-faint)">02</span>
                        <x-dg.label>Mode de présence</x-dg.label>
                    </legend>
                    <p class="dg-hint">Une ZUMRA peut agir localement, en ligne ou dans les deux espaces.</p>
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
                    <p class="dg-hint">Elle organise l’équipe sans pouvoir contredire la Charte générale ZUMRA.</p>
                    <div class="dg-field">
                        <label for="internal_charter">Règles fondatrices</label>
                        <textarea id="internal_charter" name="internal_charter" class="dg-textarea" rows="8" minlength="80" maxlength="6000" required>{{ old('internal_charter') }}</textarea>
                        <span class="dg-hint">Précisez la mission, le respect mutuel, la hiérarchie, les décisions, l’admission, le départ et l’exclusion.</span>
                    </div>
                </x-dg.fieldset>

                <x-dg.fieldset>
                    <legend>
                        <span style="font-family:var(--dg-font-mono);font-size:11px;color:var(--dg-faint)">04</span>
                        <x-dg.label>Votre responsabilité</x-dg.label>
                    </legend>
                    <p class="dg-hint">Vous pouvez lancer l’idée sans vous attribuer automatiquement la direction.</p>
                    <label class="dg-consent">
                        <input type="checkbox" name="assume_primary_lead" value="1" @checked(old('assume_primary_lead'))>
                        <span>
                            <strong>J’accepte d’être le premier responsable.</strong>
                            Ce choix est explicite. Les quatre autres sièges resteront vacants jusqu’à de vraies acceptations.
                        </span>
                    </label>
                </x-dg.fieldset>

                <button type="submit" class="dg-btn dg-btn--saffron" style="align-self:flex-start">Créer le dossier de constitution</button>
            </form>
        </div>
    </x-dg.shell>
</x-layouts.portal>
