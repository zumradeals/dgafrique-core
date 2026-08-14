<x-layouts.portal title="Vérifier mon compte — DG Afrique">
    <main class="login-shell">
        <section class="login-story">
            <a href="/" class="brand"><span class="brand-mark">DG</span><span>DG Afrique</span></a>
            <div class="login-promise"><p class="eyebrow">Dernière étape</p><h1>Vérifiez votre adresse.</h1><p>Le code est émis par le service d’identité sécurisé. DG Afrique ne le conserve pas.</p></div>
            <p class="login-foot">Code à usage unique · Reprise bornée</p>
        </section>
        <section class="login-form-panel">
            <div class="login-form-wrap">
                <a href="/" class="brand mobile-brand"><span class="brand-mark">DG</span><span>DG Afrique</span></a>
                <p class="eyebrow">Vérification e-mail</p><h2>Saisissez les 6 chiffres</h2>
                <p class="form-intro">Code envoyé à <strong>{{ $pending->destination }}</strong>.</p>
                @if (session('status'))<div class="alert success">{{ session('status') }}</div>@endif
                @if ($errors->any())<div class="alert danger">{{ $errors->first() }}</div>@endif
                <form method="POST" action="{{ route('register.verify.store') }}" class="auth-form">
                    @csrf
                    <label>Code de vérification</label><input name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code" class="verification-code">
                    <button class="primary-button" type="submit">Vérifier mon compte</button>
                </form>
                <form method="POST" action="{{ route('register.verify.resend') }}">@csrf<button class="secondary-button" type="submit">Renvoyer un code</button></form>
                <p class="create-account"><a href="{{ route('register') }}">Recommencer avec une autre adresse</a></p>
            </div>
        </section>
    </main>
</x-layouts.portal>
