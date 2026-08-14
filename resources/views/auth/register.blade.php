<x-layouts.portal title="Créer mon compte — DG Afrique">
    <main class="login-shell">
        <section class="login-story">
            <a href="/" class="brand"><span class="brand-mark">DG</span><span>DG Afrique</span></a>
            <div class="login-promise"><p class="eyebrow">Une porte d’entrée gratuite</p><h1>Créez votre espace personnel.</h1><p>Votre identité est protégée par le service de confiance de DG Afrique. Le compte ne vous inscrit pas automatiquement au Programme ZUMRA.</p></div>
            <p class="login-foot">Identité souveraine · Mot de passe jamais stocké par DG Afrique</p>
        </section>
        <section class="login-form-panel">
            <div class="login-form-wrap">
                <a href="/" class="brand mobile-brand"><span class="brand-mark">DG</span><span>DG Afrique</span></a>
                <p class="eyebrow">Nouveau membre</p><h2>Créer mon compte</h2>
                <p class="form-intro">Un code de vérification sera envoyé par e-mail.</p>
                @if ($errors->any())<div class="alert danger">{{ $errors->first() }}</div>@endif
                <form method="POST" action="{{ route('register.store') }}" class="auth-form">
                    @csrf
                    <label>Nom complet</label><input name="name" value="{{ old('name') }}" required autocomplete="name">
                    <label>Adresse e-mail</label><input type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                    <label>Mot de passe</label><input type="password" name="password" required autocomplete="new-password">
                    <label>Confirmer le mot de passe</label><input type="password" name="password_confirmation" required autocomplete="new-password">
                    <label class="check-row"><input type="checkbox" name="terms" value="1" required><span>J’accepte les conditions d’utilisation et la politique de confidentialité.</span></label>
                    <button class="primary-button" type="submit">Créer mon compte</button>
                </form>
                <p class="create-account">Déjà un compte ? <a href="{{ route('login') }}">Se connecter</a></p>
            </div>
        </section>
    </main>
</x-layouts.portal>
