<x-layouts.portal title="Mes capacités — DG Afrique">
    @php
        $sections = collect($profileConfiguration['sections'] ?? [])->filter(fn ($section) => $section['enabled'] ?? false)->sortBy('order');
        $modes = $profileConfiguration['participation_modes'] ?? [];
        $capabilityKinds = [
            'skills' => \App\Models\CapabilityStatement::KIND_POSSESSED,
            'learning' => \App\Models\CapabilityStatement::KIND_LEARNING,
            'transmission' => \App\Models\CapabilityStatement::KIND_TRANSMISSION,
        ];
        $errorSections = [
            'situation' => ['country_code', 'city', 'phone', 'current_activity', 'education_level'],
            'skills' => ['existing_skills_text', 'starts_without_skill'],
            'transmission' => ['transmission_offers_text'],
            'learning' => ['learning_goals_text'],
            'experience' => ['experience_highlights_text', 'experience_proofs_text'],
            'needs' => ['declared_needs_text', 'interest_domains_text', 'intentions_text'],
            'collaboration' => ['participation_mode', 'collaboration_preferences_text', 'orientation_consent', 'discovery_display_name', 'discovery_bio', 'discovery_consent'],
        ];
        $initialStep = 0;
        foreach ($sections->keys()->values() as $stepIndex => $sectionKey) {
            if (collect($errorSections[$sectionKey] ?? [])->contains(fn ($field) => $errors->has($field))) {
                $initialStep = $stepIndex;
                break;
            }
        }
    @endphp
    <x-dg.shell current="espace" :identity="$identity" :is-administrator="$isAdministrator ?? false">
        <div class="dg-page" style="max-width:920px">
            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="copper">Profil de capacités</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">{{ $profileConfiguration['title'] }}</h1>
                    <p>{{ $profileConfiguration['introduction'] }}</p>
                </div>
            </div>

            @if(session('status'))
                <div class="dg-band" style="margin-bottom:20px">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="dg-band" style="margin-bottom:20px;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>
            @endif

            @if($sections->isEmpty())
                <x-dg.empty title="Le parcours est momentanément fermé.">
                    <span>Les sections du profil seront ouvertes depuis l’administration DG Afrique.</span>
                </x-dg.empty>
            @else
                <form method="POST" action="{{ route('member.profile.update') }}" data-profile-steps data-profile-initial="{{ $initialStep }}" style="display:flex;flex-direction:column;gap:20px">
                    @csrf @method('PUT')

                    <nav class="dg-steps" aria-label="Étapes du profil">
                        @foreach($sections as $sectionKey => $section)
                            <button type="button" class="dg-step @if($loop->index === $initialStep) active @endif" data-profile-step-target="{{ $loop->index }}">
                                <span style="font-family:var(--dg-font-mono);font-size:11px">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                {{ $section['title'] }}
                                @if(isset($capabilityKinds[$sectionKey]))
                                    <span style="font-size:11px;opacity:.8">· {{ $capabilitySummary[$capabilityKinds[$sectionKey]] ?? 0 }} déclaration(s)</span>
                                @endif
                            </button>
                        @endforeach
                    </nav>

                    @foreach($sections as $sectionKey => $section)
                        <x-dg.fieldset data-profile-step="{{ $loop->index }}" :hidden="$loop->index !== $initialStep">
                            <legend style="display:block">
                                <span class="dg-meta">Étape {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }} sur {{ str_pad((string) $sections->count(), 2, '0', STR_PAD_LEFT) }}</span>
                                <h2 class="dg-display" style="font-size:22px;margin-top:4px">{{ $section['title'] }}</h2>
                                <p class="dg-hint" style="margin-top:4px">{{ $section['help'] }}</p>
                            </legend>

                            @switch($sectionKey)
                                @case('situation')
                                    <div class="dg-field-grid">
                                        <div class="dg-field">
                                            <label for="country_code">{{ $profileConfiguration['field_labels']['country_code'] }}</label>
                                            <input class="dg-input" id="country_code" name="country_code" list="country-suggestions" maxlength="2" value="{{ old('country_code', $profile?->country_code) }}" placeholder="CI">
                                            <datalist id="country-suggestions">@foreach($profileConfiguration['country_suggestions'] ?? [] as $country)<option value="{{ $country }}">@endforeach</datalist>
                                        </div>
                                        <div class="dg-field">
                                            <label for="city">{{ $profileConfiguration['field_labels']['city'] }}</label>
                                            <input class="dg-input" id="city" name="city" value="{{ old('city', $profile?->city) }}" placeholder="Abidjan">
                                        </div>
                                        <div class="dg-field" style="grid-column:1/-1">
                                            <label for="current_activity">{{ $profileConfiguration['field_labels']['current_activity'] }}</label>
                                            <input class="dg-input" id="current_activity" name="current_activity" value="{{ old('current_activity', $profile?->current_activity) }}" placeholder="Votre activité, même informelle">
                                        </div>
                                        <div class="dg-field">
                                            <label for="phone">{{ $profileConfiguration['field_labels']['phone'] }}</label>
                                            <input class="dg-input" id="phone" name="phone" value="{{ old('phone', $profile?->phone) }}" inputmode="tel">
                                        </div>
                                        <div class="dg-field">
                                            <label for="education_level">{{ $profileConfiguration['field_labels']['education_level'] }}</label>
                                            <input class="dg-input" id="education_level" name="education_level" value="{{ old('education_level', $profile?->education_level) }}" placeholder="Études, formation ou apprentissage personnel">
                                        </div>
                                    </div>
                                    @break
                                @case('skills')
                                    <div class="dg-field">
                                        <label for="existing_skills_text">{{ $profileConfiguration['field_labels']['existing_skills'] }}</label>
                                        <span class="dg-hint">Écrivez une capacité par ligne. Les pratiques acquises hors de l’école comptent aussi.</span>
                                        <textarea class="dg-textarea" id="existing_skills_text" name="existing_skills_text" rows="7" placeholder="Couture&#10;Réparation de téléphones&#10;Gestion d’une petite activité">{{ old('existing_skills_text', \App\Application\Profile\ProfileList::toText($profile?->existing_skills)) }}</textarea>
                                    </div>
                                    <label class="dg-radio" style="width:fit-content">
                                        <input type="checkbox" name="starts_without_skill" value="1" @checked(old('starts_without_skill', $profile?->starts_without_skill))>
                                        {{ $profileConfiguration['field_labels']['starts_without_skill'] }}
                                    </label>
                                    @break
                                @case('transmission')
                                    <div class="dg-field">
                                        <label for="transmission_offers_text">{{ $profileConfiguration['field_labels']['transmission_offers'] }}</label>
                                        <span class="dg-hint">Une connaissance, une méthode ou un geste pratique par ligne.</span>
                                        <textarea class="dg-textarea" id="transmission_offers_text" name="transmission_offers_text" rows="8" placeholder="Apprendre les bases de la couture&#10;Expliquer comment démarrer une petite boutique">{{ old('transmission_offers_text', \App\Application\Profile\ProfileList::toText($profile?->transmission_offers)) }}</textarea>
                                    </div>
                                    @break
                                @case('learning')
                                    <div class="dg-field">
                                        <label for="learning_goals_text">{{ $profileConfiguration['field_labels']['learning_goals'] }}</label>
                                        <span class="dg-hint">Indiquez ce que vous aimeriez réellement savoir faire.</span>
                                        <textarea class="dg-textarea" id="learning_goals_text" name="learning_goals_text" rows="8" placeholder="Marketing numérique&#10;Comptabilité simplifiée">{{ old('learning_goals_text', \App\Application\Profile\ProfileList::toText($profile?->learning_goals)) }}</textarea>
                                    </div>
                                    @break
                                @case('experience')
                                    <div class="dg-field-grid">
                                        <div class="dg-field">
                                            <label for="experience_highlights_text">{{ $profileConfiguration['field_labels']['experience_highlights'] }}</label>
                                            <span class="dg-hint">Une expérience par ligne.</span>
                                            <textarea class="dg-textarea" id="experience_highlights_text" name="experience_highlights_text" rows="8" placeholder="Trois années dans un atelier&#10;Organisation d’une activité communautaire">{{ old('experience_highlights_text', \App\Application\Profile\ProfileList::toText($profile?->experience_highlights)) }}</textarea>
                                        </div>
                                        <div class="dg-field">
                                            <label for="experience_proofs_text">{{ $profileConfiguration['field_labels']['experience_proofs'] }}</label>
                                            <span class="dg-hint">Portfolio, certificat, photo, site ou personne de référence.</span>
                                            <textarea class="dg-textarea" id="experience_proofs_text" name="experience_proofs_text" rows="8" placeholder="https://…&#10;Certificat disponible sur demande">{{ old('experience_proofs_text', \App\Application\Profile\ProfileList::toText($profile?->experience_proofs)) }}</textarea>
                                        </div>
                                    </div>
                                    <p class="dg-hint">Les preuves sont facultatives. Leur présence ne rend pas automatiquement leur contenu public.</p>
                                    @break
                                @case('needs')
                                    <div class="dg-field-grid">
                                        <div class="dg-field" style="grid-column:1/-1">
                                            <label for="declared_needs_text">{{ $profileConfiguration['field_labels']['declared_needs'] }}</label>
                                            <textarea class="dg-textarea" id="declared_needs_text" name="declared_needs_text" rows="5" placeholder="Trouver un mentor&#10;Accéder à un espace de travail">{{ old('declared_needs_text', \App\Application\Profile\ProfileList::toText($profile?->declared_needs)) }}</textarea>
                                        </div>
                                        <div class="dg-field">
                                            <label for="interest_domains_text">{{ $profileConfiguration['field_labels']['interest_domains'] }}</label>
                                            <textarea class="dg-textarea" id="interest_domains_text" name="interest_domains_text" rows="5" placeholder="Artisanat&#10;Numérique">{{ old('interest_domains_text', \App\Application\Profile\ProfileList::toText($profile?->interest_domains)) }}</textarea>
                                        </div>
                                        <div class="dg-field">
                                            <label for="intentions_text">{{ $profileConfiguration['field_labels']['intentions'] }}</label>
                                            <textarea class="dg-textarea" id="intentions_text" name="intentions_text" rows="5" placeholder="Développer mon activité&#10;Participer à un projet collectif">{{ old('intentions_text', \App\Application\Profile\ProfileList::toText($profile?->intentions)) }}</textarea>
                                        </div>
                                    </div>
                                    @break
                                @case('collaboration')
                                    <div class="dg-field-grid">
                                        <div class="dg-field">
                                            <label for="participation_mode">{{ $profileConfiguration['field_labels']['participation_mode'] }}</label>
                                            <select class="dg-select" id="participation_mode" name="participation_mode">
                                                <option value="">Non précisé</option>
                                                @foreach($modes as $mode)
                                                    <option value="{{ $mode['value'] }}" @selected(old('participation_mode', $profile?->participation_mode) === $mode['value'])>{{ $mode['label'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="dg-field">
                                            <label for="collaboration_preferences_text">{{ $profileConfiguration['field_labels']['collaboration_preferences'] }}</label>
                                            <textarea class="dg-textarea" id="collaboration_preferences_text" name="collaboration_preferences_text" rows="5" placeholder="Disponible le week-end&#10;Préférence pour les petits groupes">{{ old('collaboration_preferences_text', \App\Application\Profile\ProfileList::toText($profile?->collaboration_preferences)) }}</textarea>
                                        </div>
                                        <div class="dg-field">
                                            <label for="discovery_display_name">{{ $profileConfiguration['field_labels']['discovery_display_name'] }}</label>
                                            <input class="dg-input" id="discovery_display_name" name="discovery_display_name" maxlength="120" value="{{ old('discovery_display_name', $profile?->discovery_display_name) }}" placeholder="Votre nom, prénom ou nom d’activité">
                                        </div>
                                        <div class="dg-field">
                                            <label for="discovery_bio">{{ $profileConfiguration['field_labels']['discovery_bio'] }}</label>
                                            <textarea class="dg-textarea" id="discovery_bio" name="discovery_bio" rows="5" maxlength="600" placeholder="Ce que vous faites et le type de rencontre utile que vous recherchez">{{ old('discovery_bio', $profile?->discovery_bio) }}</textarea>
                                        </div>
                                    </div>
                                    <label class="dg-consent">
                                        <input type="checkbox" name="orientation_consent" value="1" @checked(old('orientation_consent', $profile?->orientation_consent))>
                                        <span>
                                            <strong>{{ $profileConfiguration['orientation_consent_label'] }}</strong>
                                            {{ $profileConfiguration['orientation_consent_help'] }}
                                        </span>
                                    </label>
                                    <label class="dg-consent">
                                        <input type="checkbox" name="discovery_consent" value="1" @checked(old('discovery_consent', $profile?->discovery_consent))>
                                        <span>
                                            <strong>{{ $profileConfiguration['discovery_consent_label'] }}</strong>
                                            {{ $profileConfiguration['discovery_consent_help'] }}
                                        </span>
                                    </label>
                                    @break
                            @endswitch

                            <div class="flex items-center justify-between gap-4" style="padding-top:6px">
                                <button type="button" class="dg-btn dg-btn--quiet" data-profile-previous @if($loop->first) disabled @endif>← Étape précédente</button>
                                @if($loop->last)
                                    <button type="submit" class="dg-btn dg-btn--primary">{{ $profileConfiguration['submit_label'] }}</button>
                                @else
                                    <button type="button" class="dg-btn dg-btn--primary" data-profile-next>Continuer →</button>
                                @endif
                            </div>
                        </x-dg.fieldset>
                    @endforeach

                    <div class="dg-band" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px">
                        <span>{{ $profileConfiguration['no_score_notice'] }}</span>
                        <button type="submit" class="dg-btn dg-btn--quiet">Enregistrer maintenant</button>
                    </div>
                </form>
            @endif
        </div>
    </x-dg.shell>
</x-layouts.portal>
