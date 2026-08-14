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
            'collaboration' => ['participation_mode', 'collaboration_preferences_text', 'orientation_consent'],
        ];
        $initialStep = 0;
        foreach ($sections->keys()->values() as $stepIndex => $sectionKey) {
            if (collect($errorSections[$sectionKey] ?? [])->contains(fn ($field) => $errors->has($field))) {
                $initialStep = $stepIndex;
                break;
            }
        }
    @endphp
    <div class="space-shell progressive-profile">
        <header class="space-header">
            <div class="space-identity"><a class="brand" href="/"><span class="brand-mark">G</span><span>DG Afrique</span></a></div>
            <div class="space-search">⌕ <span>Rechercher une capacité, un besoin, une opportunité…</span></div>
            <div class="space-identity"><span class="space-notification" aria-label="Aucune nouvelle notification"><i></i></span><details class="account-menu"><summary class="member-summary"><span class="avatar">{{ mb_strtoupper(mb_substr($identity->label, 0, 1)) }}</span><span class="member-name">{{ $identity->label }}</span></summary><div><a href="{{ route('member.space') }}">Vue d’ensemble</a><a href="{{ route('member.profile.edit') }}">Mes capacités</a><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Se déconnecter</button></form></div></details></div>
        </header>

        <div class="space-layout">
            <aside class="space-sidebar"><nav><a class="nav-blue" href="{{ route('member.space') }}">Vue d’ensemble</a><a class="active nav-cyan" href="{{ route('member.profile.edit') }}">Mes capacités</a><span class="nav-amber">Mes besoins <small>à venir</small></span><span class="nav-blue">Opportunités <small>à venir</small></span><a class="nav-green" href="{{ route('zumra.index') }}">Mes ZUMRA <small>aperçu</small></a><span class="nav-amber">Mes projets <small>à venir</small></span><span class="nav-gray">Paramètres <small>à venir</small></span></nav></aside>

            <main class="space-content progressive-profile-content">
                <div class="profile-heading progressive-heading"><div><p class="eyebrow dark">PROFIL DE CAPACITÉS</p><h1>{{ $profileConfiguration['title'] }}</h1><p class="space-lead">{{ $profileConfiguration['introduction'] }}</p></div><div class="profile-avatar">{{ mb_strtoupper(mb_substr($identity->label, 0, 1)) }}</div></div>
                <section class="identity-strip"><div><small>Identité reconnue par DG Afrique</small><strong>{{ $identity->label }}</strong></div><code>Référence interne protégée</code><span>Compte vérifié</span></section>

                @if (session('status'))<div class="alert success">{{ session('status') }}</div>@endif
                @if ($errors->any())<div class="alert danger"><strong>Certains éléments sont à vérifier.</strong><br>{{ $errors->first() }}</div>@endif

                @if($sections->isEmpty())
                    <section class="profile-empty"><h2>Le parcours est momentanément fermé.</h2><p>Les sections du profil seront ouvertes depuis l’administration DG Afrique.</p></section>
                @else
                    <form method="POST" action="{{ route('member.profile.update') }}" class="profile-form progressive-form" data-profile-steps data-profile-initial="{{ $initialStep }}">@csrf @method('PUT')
                        <nav class="profile-stepper" aria-label="Étapes du profil">
                            @foreach($sections as $sectionKey => $section)
                                <button type="button" data-profile-step-target="{{ $loop->index }}" @class(['active' => $loop->index === $initialStep])><span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><strong>{{ $section['title'] }}@if(isset($capabilityKinds[$sectionKey]))<small>{{ $capabilitySummary[$capabilityKinds[$sectionKey]] ?? 0 }} déclaration(s)</small>@endif</strong></button>
                            @endforeach
                        </nav>

                        <div class="profile-stage-wrap">
                            @foreach($sections as $sectionKey => $section)
                                <section class="profile-stage" data-profile-step="{{ $loop->index }}" @if($loop->index !== $initialStep) hidden @endif>
                                    <div class="stage-heading"><span>ÉTAPE {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }} SUR {{ str_pad((string) $sections->count(), 2, '0', STR_PAD_LEFT) }}</span><h2>{{ $section['title'] }}</h2><p>{{ $section['help'] }}</p></div>

                                    @switch($sectionKey)
                                        @case('situation')
                                            <div class="profile-grid"><label>{{ $profileConfiguration['field_labels']['country_code'] }}<input name="country_code" list="country-suggestions" maxlength="2" value="{{ old('country_code', $profile?->country_code) }}" placeholder="CI"></label><datalist id="country-suggestions">@foreach($profileConfiguration['country_suggestions'] ?? [] as $country)<option value="{{ $country }}">@endforeach</datalist><label>{{ $profileConfiguration['field_labels']['city'] }}<input name="city" value="{{ old('city', $profile?->city) }}" placeholder="Abidjan"></label><label class="wide">{{ $profileConfiguration['field_labels']['current_activity'] }}<input name="current_activity" value="{{ old('current_activity', $profile?->current_activity) }}" placeholder="Votre activité, même informelle"></label><label>{{ $profileConfiguration['field_labels']['phone'] }}<input name="phone" value="{{ old('phone', $profile?->phone) }}" inputmode="tel"></label><label>{{ $profileConfiguration['field_labels']['education_level'] }}<input name="education_level" value="{{ old('education_level', $profile?->education_level) }}" placeholder="Études, formation ou apprentissage personnel"></label></div>
                                            @break
                                        @case('skills')
                                            <label class="profile-field">{{ $profileConfiguration['field_labels']['existing_skills'] }}<small>Écrivez une capacité par ligne. Les pratiques acquises hors de l’école comptent aussi.</small><textarea name="existing_skills_text" rows="7" placeholder="Couture&#10;Réparation de téléphones&#10;Gestion d’une petite activité">{{ old('existing_skills_text', \App\Application\Profile\ProfileList::toText($profile?->existing_skills)) }}</textarea></label><label class="check-row gentle-check"><input type="checkbox" name="starts_without_skill" value="1" @checked(old('starts_without_skill', $profile?->starts_without_skill))><span>{{ $profileConfiguration['field_labels']['starts_without_skill'] }}</span></label>
                                            @break
                                        @case('transmission')
                                            <label class="profile-field">{{ $profileConfiguration['field_labels']['transmission_offers'] }}<small>Une connaissance, une méthode ou un geste pratique par ligne.</small><textarea name="transmission_offers_text" rows="8" placeholder="Apprendre les bases de la couture&#10;Expliquer comment démarrer une petite boutique">{{ old('transmission_offers_text', \App\Application\Profile\ProfileList::toText($profile?->transmission_offers)) }}</textarea></label>
                                            @break
                                        @case('learning')
                                            <label class="profile-field">{{ $profileConfiguration['field_labels']['learning_goals'] }}<small>Indiquez ce que vous aimeriez réellement savoir faire.</small><textarea name="learning_goals_text" rows="8" placeholder="Marketing numérique&#10;Comptabilité simplifiée">{{ old('learning_goals_text', \App\Application\Profile\ProfileList::toText($profile?->learning_goals)) }}</textarea></label>
                                            @break
                                        @case('experience')
                                            <div class="profile-grid"><label>{{ $profileConfiguration['field_labels']['experience_highlights'] }}<small>Une expérience par ligne.</small><textarea name="experience_highlights_text" rows="8" placeholder="Trois années dans un atelier&#10;Organisation d’une activité communautaire">{{ old('experience_highlights_text', \App\Application\Profile\ProfileList::toText($profile?->experience_highlights)) }}</textarea></label><label>{{ $profileConfiguration['field_labels']['experience_proofs'] }}<small>Portfolio, certificat, photo, site ou personne de référence.</small><textarea name="experience_proofs_text" rows="8" placeholder="https://…&#10;Certificat disponible sur demande">{{ old('experience_proofs_text', \App\Application\Profile\ProfileList::toText($profile?->experience_proofs)) }}</textarea></label></div><p class="profile-privacy-note">Les preuves sont facultatives. Leur présence ne rend pas automatiquement leur contenu public.</p>
                                            @break
                                        @case('needs')
                                            <div class="profile-grid"><label class="wide">{{ $profileConfiguration['field_labels']['declared_needs'] }}<textarea name="declared_needs_text" rows="5" placeholder="Trouver un mentor&#10;Accéder à un espace de travail">{{ old('declared_needs_text', \App\Application\Profile\ProfileList::toText($profile?->declared_needs)) }}</textarea></label><label>{{ $profileConfiguration['field_labels']['interest_domains'] }}<textarea name="interest_domains_text" rows="5" placeholder="Artisanat&#10;Numérique">{{ old('interest_domains_text', \App\Application\Profile\ProfileList::toText($profile?->interest_domains)) }}</textarea></label><label>{{ $profileConfiguration['field_labels']['intentions'] }}<textarea name="intentions_text" rows="5" placeholder="Développer mon activité&#10;Participer à un projet collectif">{{ old('intentions_text', \App\Application\Profile\ProfileList::toText($profile?->intentions)) }}</textarea></label></div>
                                            @break
                                        @case('collaboration')
                                            <div class="profile-grid"><label>{{ $profileConfiguration['field_labels']['participation_mode'] }}<select name="participation_mode"><option value="">Non précisé</option>@foreach($modes as $mode)<option value="{{ $mode['value'] }}" @selected(old('participation_mode', $profile?->participation_mode)===$mode['value'])>{{ $mode['label'] }}</option>@endforeach</select></label><label>{{ $profileConfiguration['field_labels']['collaboration_preferences'] }}<textarea name="collaboration_preferences_text" rows="5" placeholder="Disponible le week-end&#10;Préférence pour les petits groupes">{{ old('collaboration_preferences_text', \App\Application\Profile\ProfileList::toText($profile?->collaboration_preferences)) }}</textarea></label></div><label class="consent-card"><input type="checkbox" name="orientation_consent" value="1" @checked(old('orientation_consent', $profile?->orientation_consent))><span><strong>{{ $profileConfiguration['orientation_consent_label'] }}</strong><small>{{ $profileConfiguration['orientation_consent_help'] }}</small></span></label>
                                            @break
                                    @endswitch

                                    <div class="stage-actions"><button type="button" class="stage-back" data-profile-previous @if($loop->first) disabled @endif>← Étape précédente</button>@if($loop->last)<button type="submit" class="stage-save">{{ $profileConfiguration['submit_label'] }}</button>@else<button type="button" class="stage-next" data-profile-next>Continuer →</button>@endif</div>
                                </section>
                            @endforeach
                        </div>

                        <div class="profile-submit progressive-submit"><p>{{ $profileConfiguration['no_score_notice'] }}</p><button class="secondary-button" type="submit">Enregistrer maintenant</button></div>
                    </form>
                @endif
            </main>
        </div>
    </div>
</x-layouts.portal>
