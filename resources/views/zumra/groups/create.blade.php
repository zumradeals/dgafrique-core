{{--
    Naissance d'une ZUMRA — ZUMRA-HUMAN-BIRTH-001. Quatre moments humains, un seul écran : la
    naissance reste bien plus légère que la structuration (charte, activités dérivées, cinq
    responsabilités) qui vient ensuite, depuis la fiche. Aucune validation, financement ni
    nomination automatique.
--}}
<x-layouts.portal title="Faire naître une ZUMRA — DG Afrique">
    <x-dg.shell current="zumra" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:820px">
            <a href="{{ route('zumra.groups.index') }}" class="dg-crumb">← Les ZUMRA</a>

            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="saffron">Groupe humain</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">{{ $configuration['creation_title'] }}</h1>
                    <p>Une ZUMRA est un centre d’incubation autour d’une activité. Elle peut commencer avec vous seul·e — le reste (charte, activités dérivées, responsabilités) se construit ensuite, depuis sa fiche.</p>
                </div>
            </div>

            @if($errors->any())
                <div class="dg-band" style="margin-bottom:20px;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('zumra.groups.store') }}" style="display:flex;flex-direction:column;gap:20px">
                @csrf

                <x-dg.fieldset>
                    <legend>
                        <span style="font-family:var(--dg-font-mono);font-size:11px;color:var(--dg-faint)">01</span>
                        <x-dg.label>Votre activité</x-dg.label>
                    </legend>
                    <p class="dg-hint">Pas d’activité, pas de ZUMRA : c’est elle qui donne son identité au mouvement. Vous pourrez développer des activités dérivées ensuite, depuis sa fiche.</p>
                    <div class="dg-field">
                        <label for="domain">Votre activité principale</label>
                        <input type="text" id="domain" name="domain" class="dg-input" value="{{ old('domain') }}" maxlength="140" placeholder="Ex. Couture, numérique, agriculture…" required>
                    </div>
                </x-dg.fieldset>

                <x-dg.fieldset>
                    <legend>
                        <span style="font-family:var(--dg-font-mono);font-size:11px;color:var(--dg-faint)">02</span>
                        <x-dg.label>Ce que vous voulez changer</x-dg.label>
                    </legend>
                    <div class="dg-field">
                        <label for="founding_objective">Votre objectif fondateur</label>
                        <textarea id="founding_objective" name="founding_objective" class="dg-textarea" rows="5" minlength="40" maxlength="1800" required>{{ old('founding_objective') }}</textarea>
                        <span class="dg-hint">Décrivez ce que vous voulez apprendre, transmettre, construire ou résoudre à travers cette activité.</span>
                    </div>
                </x-dg.fieldset>

                <x-dg.fieldset>
                    <legend>
                        <span style="font-family:var(--dg-font-mono);font-size:11px;color:var(--dg-faint)">03</span>
                        <x-dg.label>Comment vous allez commencer</x-dg.label>
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
                    <div class="dg-field" style="margin-top:12px">
                        <label for="location">Où (si présentiel ou hybride)</label>
                        <input type="text" id="location" name="location" class="dg-input" value="{{ old('location') }}" maxlength="160" placeholder="Ville, quartier… laissez vide si entièrement en ligne">
                    </div>
                    <div class="dg-field" style="margin-top:16px">
                        <label>Comment votre ZUMRA pourra-t-elle accueillir des personnes qui souhaitent apprendre cette activité ?</label>
                        <div class="dg-radio-group" style="margin-top:8px">
                            @foreach($welcomeCapacities as $value => $label)
                                <label class="dg-radio">
                                    <input type="radio" name="welcome_capacity" value="{{ $value }}" @checked(old('welcome_capacity') === $value)>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <label class="dg-consent" style="margin-top:16px">
                        <input type="checkbox" name="assume_primary_lead" value="1" @checked(old('assume_primary_lead'))>
                        <span>
                            <strong>J’accepte d’être le premier responsable.</strong>
                            Vous pouvez commencer seul·e. Ce choix est explicite ; les quatre autres sièges resteront vacants jusqu’à de vraies acceptations.
                        </span>
                    </label>
                </x-dg.fieldset>

                <x-dg.fieldset>
                    <legend>
                        <span style="font-family:var(--dg-font-mono);font-size:11px;color:var(--dg-faint)">04</span>
                        <x-dg.label>Votre ZUMRA</x-dg.label>
                    </legend>
                    <div class="dg-field">
                        <label for="name">Comment souhaitez-vous l’appeler ?</label>
                        <input type="text" id="name" name="name" class="dg-input" value="{{ old('name') }}" maxlength="140" required>
                    </div>
                    <div class="dg-band" style="margin-top:12px">
                        <strong style="display:block;font-size:14px;color:var(--dg-forest);margin-bottom:4px">La charte interne n’est pas requise aujourd’hui</strong>
                        Vous pourrez la rédiger ensuite, depuis la fiche de votre ZUMRA — elle sera seulement nécessaire pour la rendre prête à valider.
                    </div>
                </x-dg.fieldset>

                <button type="submit" class="dg-btn dg-btn--saffron" style="align-self:flex-start">Faire naître ma ZUMRA</button>
            </form>
        </div>
    </x-dg.shell>
</x-layouts.portal>
