<x-layouts.portal title="Mon profil — DG Afrique">
    @php
        $sections = collect($profileConfiguration['sections'] ?? [])->filter(fn ($section) => $section['enabled'] ?? false)->sortBy('order');
        $modes = $profileConfiguration['participation_modes'] ?? [];
    @endphp
    <div class="space-shell">
        <header class="space-header"><a class="brand" href="/"><span class="brand-mark">DG</span><span>DG Afrique</span></a><div class="space-search">⌕ <span>Rechercher dans DG Afrique…</span></div><div class="member-summary"><span class="avatar">{{ mb_strtoupper(mb_substr($identity->label, 0, 1)) }}</span><span class="member-name">{{ $identity->label }}</span><form method="POST" action="{{ route('logout') }}">@csrf<button class="logout-button">Se déconnecter</button></form></div></header>
        <div class="space-layout">
            <aside class="space-sidebar"><nav><a href="{{ route('member.space') }}">Tableau de bord</a><a class="active" href="{{ route('member.profile.edit') }}">Mon profil <small>CAP‑003</small></a><span>ZUMRA <small>à venir</small></span><span>Mes demandes <small>à venir</small></span><span>Satellites <small>à venir</small></span><span>Paramètres <small>à venir</small></span></nav></aside>
            <main class="space-content profile-content">
                <p class="eyebrow dark">Profil de capacités</p><h1>{{ $profileConfiguration['title'] }}</h1><p class="space-lead">{{ $profileConfiguration['introduction'] }}</p>
                <section class="identity-strip"><div><small>Identité reconnue par GAMAD Core</small><strong>{{ $identity->label }}</strong></div><code>{{ $identity->reference }}</code></section>
                @if (session('status'))<div class="alert success">{{ session('status') }}</div>@endif @if ($errors->any())<div class="alert danger">{{ $errors->first() }}</div>@endif
                <form method="POST" action="{{ route('member.profile.update') }}" class="profile-form">@csrf @method('PUT')
                    @foreach($sections as $sectionKey => $section)
                        <section class="profile-section"><div class="section-number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</div><div class="section-body"><h2>{{ $section['title'] }}</h2><p>{{ $section['help'] }}</p>
                        @switch($sectionKey)
                            @case('situation')
                                <div class="profile-grid"><label>{{ $profileConfiguration['field_labels']['country_code'] }}<input name="country_code" list="country-suggestions" maxlength="2" value="{{ old('country_code', $profile?->country_code) }}"></label><datalist id="country-suggestions">@foreach($profileConfiguration['country_suggestions'] ?? [] as $country)<option value="{{ $country }}">@endforeach</datalist><label>{{ $profileConfiguration['field_labels']['city'] }}<input name="city" value="{{ old('city', $profile?->city) }}"></label><label class="wide">{{ $profileConfiguration['field_labels']['current_activity'] }}<input name="current_activity" value="{{ old('current_activity', $profile?->current_activity) }}"></label><label>{{ $profileConfiguration['field_labels']['phone'] }}<input name="phone" value="{{ old('phone', $profile?->phone) }}"></label><label>{{ $profileConfiguration['field_labels']['education_level'] }}<input name="education_level" value="{{ old('education_level', $profile?->education_level) }}"></label></div>
                                @break
                            @case('skills')
                                <textarea name="existing_skills_text" rows="5">{{ old('existing_skills_text', \App\Application\Profile\ProfileList::toText($profile?->existing_skills)) }}</textarea><label class="check-row"><input type="checkbox" name="starts_without_skill" value="1" @checked(old('starts_without_skill', $profile?->starts_without_skill))><span>{{ $profileConfiguration['field_labels']['starts_without_skill'] }}</span></label>
                                @break
                            @case('learning')
                                <textarea name="learning_goals_text" rows="4">{{ old('learning_goals_text', \App\Application\Profile\ProfileList::toText($profile?->learning_goals)) }}</textarea>
                                @break
                            @case('intentions')
                                <div class="profile-grid"><label>{{ $profileConfiguration['field_labels']['interest_domains'] }}<textarea name="interest_domains_text" rows="5">{{ old('interest_domains_text', \App\Application\Profile\ProfileList::toText($profile?->interest_domains)) }}</textarea></label><label>{{ $profileConfiguration['field_labels']['intentions'] }}<textarea name="intentions_text" rows="5">{{ old('intentions_text', \App\Application\Profile\ProfileList::toText($profile?->intentions)) }}</textarea></label><label>{{ $profileConfiguration['field_labels']['participation_mode'] }}<select name="participation_mode"><option value="">Non précisé</option>@foreach($modes as $mode)<option value="{{ $mode['value'] }}" @selected(old('participation_mode', $profile?->participation_mode)===$mode['value'])>{{ $mode['label'] }}</option>@endforeach</select></label></div><label class="consent-card"><input type="checkbox" name="orientation_consent" value="1" @checked(old('orientation_consent', $profile?->orientation_consent))><span><strong>{{ $profileConfiguration['orientation_consent_label'] }}</strong><small>{{ $profileConfiguration['orientation_consent_help'] }}</small></span></label>
                                @break
                        @endswitch
                        </div></section>
                    @endforeach
                    <div class="profile-submit"><p>{{ $profileConfiguration['no_score_notice'] }}</p><button class="primary-button" type="submit">{{ $profileConfiguration['submit_label'] }}</button></div>
                </form>
            </main>
        </div>
    </div>
</x-layouts.portal>
