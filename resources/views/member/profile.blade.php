<x-layouts.member title="Mon profil" active="space">
<div class="dg-space">
    <a class="dg-space-text-link" href="{{ route('member.space') }}">← Mon espace</a>
    <header class="dg-space-heading"><div><p class="dg-space-eyebrow">À VOTRE RYTHME</p><h1>Ce qui vous ressemble.</h1><p>Vos savoir-faire, vos envies et les façons dont vous souhaitez participer.</p></div></header>
    <form method="POST" action="{{ route('member.profile.update') }}">
        @csrf @method('PUT')
        @php
            $sections = [
                'situation' => ['Votre situation', ['country_code' => ['Code pays (deux lettres)', 2], 'city' => ['Ville', 160], 'phone' => ['Téléphone', 40], 'current_activity' => ['Activité actuelle', 256], 'education_level' => ['Formation', 160]]],
                'skills' => ['Vos savoir-faire', ['existing_skills_text' => ['Ce que vous savez faire', 3000]]],
                'transmission' => ['Ce que vous aimeriez transmettre', ['transmission_offers_text' => ['Savoirs à transmettre', 3000]]],
                'learning' => ['Ce que vous aimeriez apprendre', ['learning_goals_text' => ['Envies d’apprentissage', 3000]]],
                'experience' => ['Votre expérience', ['experience_highlights_text' => ['Expériences marquantes', 3000], 'experience_proofs_text' => ['Références de vos réalisations', 3000]]],
                'needs' => ['Vos besoins et vos envies', ['declared_needs_text' => ['Besoins', 3000], 'interest_domains_text' => ['Domaines qui vous intéressent', 3000], 'intentions_text' => ['Vos intentions', 3000]]],
                'collaboration' => ['Participer et être découvert', ['collaboration_preferences_text' => ['Préférences de collaboration', 3000], 'discovery_display_name' => ['Nom à afficher', 120], 'discovery_bio' => ['Quelques mots sur vous', 600], 'availability_note' => ['Précisions sur vos disponibilités', 300]]],
            ];
        @endphp
        @foreach ($sections as $section => [$heading, $fields])
            @if ($profileConfiguration['sections'][$section]['enabled'] ?? false)
                <fieldset id="{{ $section }}" class="dg-space-section"><legend><h2>{{ $heading }}</h2></legend>
                    @foreach ($fields as $name => [$label, $maximum])
                        @php
                            $isList = str_ends_with($name, '_text');
                            $attribute = $isList ? substr($name, 0, -5) : $name;
                            $value = old($name, $isList ? implode("\n", $profile?->{$attribute} ?? []) : $profile?->{$attribute});
                            $required = (bool) ($profileConfiguration['required_fields'][$name] ?? false);
                        @endphp
                        <div class="dg-profile-field"><label for="{{ $name }}">{{ $label }}{{ $required ? ' *' : '' }}</label>
                            @if ($maximum > 300)
                                <textarea class="dg-input" id="{{ $name }}" name="{{ $name }}" rows="3" maxlength="{{ $maximum }}" @required($required) @if($errors->has($name)) aria-invalid="true" @endif>{{ $value }}</textarea>
                            @else
                                <x-dg.input :id="$name" :value="$value" :maxlength="$maximum" :required="$required" :invalid="$errors->has($name)" />
                            @endif
                            @if ($isList)<p class="dg-space-note">Une idée par ligne.</p>@endif
                        </div>
                    @endforeach
                    @if ($section === 'skills')
                        <input type="hidden" name="starts_without_skill" value="0"><label><input type="checkbox" name="starts_without_skill" value="1" @checked(old('starts_without_skill', $profile?->starts_without_skill))> Je débute et je souhaite apprendre.</label>
                    @endif
                    @if ($section === 'collaboration')
                        <label for="participation_mode">Mode de participation</label>
                        <select class="dg-input" id="participation_mode" name="participation_mode" @required($profileConfiguration['required_fields']['participation_mode'] ?? false)><option value="">Choisir</option>@foreach ($profileConfiguration['participation_modes'] ?? [] as $mode)<option value="{{ $mode['value'] }}" @selected(old('participation_mode', $profile?->participation_mode) === $mode['value'])>{{ $mode['label'] }}</option>@endforeach</select>
                        <label for="availability_status">Disponibilité</label>
                        <select class="dg-input" id="availability_status" name="availability_status"><option value="">Non précisée</option>@foreach (\App\Models\PersonProfile::AVAILABILITY_LABELS as $value => $label)<option value="{{ $value }}" @selected(old('availability_status', $profile?->availability_status) === $value)>{{ $label }}</option>@endforeach</select>
                        <div class="dg-profile-field"><input type="hidden" name="orientation_consent" value="0"><label><input type="checkbox" name="orientation_consent" value="1" @checked(old('orientation_consent', $profile?->orientation_consent))> J’accepte que mes informations servent à m’orienter vers des possibilités pertinentes.</label></div>
                        <div class="dg-profile-field"><input type="hidden" name="discovery_consent" value="0"><label><input type="checkbox" name="discovery_consent" value="1" @checked(old('discovery_consent', $profile?->discovery_consent))> Je souhaite être découvrable par les autres membres.</label><p class="dg-space-note">La découverte nécessite aussi votre accord d’orientation et un nom à afficher. Vous pouvez retirer ces accords.</p></div>
                    @endif
                </fieldset>
            @endif
        @endforeach
        <x-dg.button type="submit">Enregistrer mon profil</x-dg.button>
    </form>
</div>
</x-layouts.member>
