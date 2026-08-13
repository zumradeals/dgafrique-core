<x-layouts.portal title="Mon profil — DG Afrique">
    <div class="space-shell">
        <header class="space-header">
            <a class="brand" href="/"><span class="brand-mark">DG</span><span>DG Afrique</span></a>
            <div class="space-search">⌕ <span>Rechercher dans DG Afrique…</span></div>
            <div class="member-summary">
                <span class="avatar">{{ mb_strtoupper(mb_substr($identity->label, 0, 1)) }}</span>
                <span class="member-name">{{ $identity->label }}</span>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout-button">Se déconnecter</button></form>
            </div>
        </header>
        <div class="space-layout">
            <aside class="space-sidebar"><nav>
                <a href="{{ route('member.space') }}">Tableau de bord</a>
                <a class="active" href="{{ route('member.profile.edit') }}">Mon profil <small>CAP‑003</small></a>
                <span>ZUMRA <small>à venir</small></span><span>Mes demandes <small>à venir</small></span>
                <span>Satellites <small>à venir</small></span><span>Paramètres <small>à venir</small></span>
            </nav></aside>
            <main class="space-content profile-content">
                <p class="eyebrow dark">Profil de capacités</p>
                <h1>Ce que vous savez faire. Ce que vous voulez devenir.</h1>
                <p class="space-lead">Ce profil appartient à votre Compte DG Afrique. Il sert à mieux vous orienter, jamais à résumer votre valeur par un score.</p>
                <section class="identity-strip"><div><small>Identité reconnue par GAMAD Core</small><strong>{{ $identity->label }}</strong></div><code>{{ $identity->reference }}</code></section>
                @if (session('status'))<div class="alert success">{{ session('status') }}</div>@endif
                @if ($errors->any())<div class="alert danger">{{ $errors->first() }}</div>@endif
                <form method="POST" action="{{ route('member.profile.update') }}" class="profile-form">
                    @csrf @method('PUT')
                    <section class="profile-section"><div class="section-number">01</div><div class="section-body"><h2>Votre situation aujourd’hui</h2><p>Quelques repères concrets pour comprendre votre contexte.</p><div class="profile-grid">
                        <label>Pays (code à 2 lettres)<input name="country_code" maxlength="2" placeholder="CI" value="{{ old('country_code', $profile?->country_code) }}"></label>
                        <label>Ville ou localité<input name="city" value="{{ old('city', $profile?->city) }}" placeholder="Abidjan"></label>
                        <label class="wide">Activité actuelle<input name="current_activity" value="{{ old('current_activity', $profile?->current_activity) }}" placeholder="Ex. couturière, étudiant, commerçant..."></label>
                        <label>Téléphone facultatif<input name="phone" value="{{ old('phone', $profile?->phone) }}"></label>
                        <label>Niveau ou parcours de formation<input name="education_level" value="{{ old('education_level', $profile?->education_level) }}"></label>
                    </div></div></section>
                    <section class="profile-section"><div class="section-number">02</div><div class="section-body"><h2>Vos savoir-faire actuels</h2><p>Une compétence ou pratique par ligne. Les expériences informelles comptent aussi.</p>
                        <textarea name="existing_skills_text" rows="5" placeholder="Réparer des téléphones&#10;Gérer une petite caisse&#10;Parler dioula">{{ old('existing_skills_text', \App\Application\Profile\ProfileList::toText($profile?->existing_skills)) }}</textarea>
                        <label class="check-row"><input type="checkbox" name="starts_without_skill" value="1" @checked(old('starts_without_skill', $profile?->starts_without_skill))><span>Je commence sans compétence particulière à déclarer pour le moment.</span></label>
                    </div></section>
                    <section class="profile-section"><div class="section-number">03</div><div class="section-body"><h2>Ce que vous voulez apprendre</h2><p>Écrivez un objectif par ligne.</p><textarea name="learning_goals_text" rows="4" placeholder="Comptabilité simple&#10;Marketing numérique">{{ old('learning_goals_text', \App\Application\Profile\ProfileList::toText($profile?->learning_goals)) }}</textarea></div></section>
                    <section class="profile-section"><div class="section-number">04</div><div class="section-body"><h2>Vos domaines et intentions</h2><div class="profile-grid">
                        <label>Domaines qui vous intéressent<textarea name="interest_domains_text" rows="5" placeholder="Agriculture&#10;Numérique">{{ old('interest_domains_text', \App\Application\Profile\ProfileList::toText($profile?->interest_domains)) }}</textarea></label>
                        <label>Ce que vous cherchez à accomplir<textarea name="intentions_text" rows="5" placeholder="Trouver une formation&#10;Lancer une activité">{{ old('intentions_text', \App\Application\Profile\ProfileList::toText($profile?->intentions)) }}</textarea></label>
                        <label>Mode de participation préféré<select name="participation_mode"><option value="">Non précisé</option>@foreach(['EN_LIGNE'=>'En ligne','PRESENTIEL'=>'En présentiel','HYBRIDE'=>'Hybride'] as $value=>$label)<option value="{{ $value }}" @selected(old('participation_mode', $profile?->participation_mode)===$value)>{{ $label }}</option>@endforeach</select></label>
                    </div><label class="consent-card"><input type="checkbox" name="orientation_consent" value="1" @checked(old('orientation_consent', $profile?->orientation_consent))><span><strong>Recevoir des orientations utiles</strong><small>J’accepte que DG Afrique utilise ce profil pour me proposer des apprentissages ou prochaines actions. Ce choix est révocable.</small></span></label></div></section>
                    <div class="profile-submit"><p>Aucun score de valeur personnelle ne sera calculé.</p><button class="primary-button" type="submit">Enregistrer mon profil</button></div>
                </form>
            </main>
        </div>
    </div>
</x-layouts.portal>
