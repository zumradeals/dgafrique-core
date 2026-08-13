<x-layouts.portal title="Connexion — DG Afrique">
    <main class="login-shell">
        <section class="login-story" aria-label="DG Afrique">
            <a class="brand" href="/" aria-label="Accueil DG Afrique">
                <span class="brand-mark">G</span>
                <span>DG Afrique</span>
            </a>

            <div class="login-promise">
                <p class="eyebrow">Un satellite de l’écosystème GAMAD</p>
                <h1>Le numérique qui fait avancer vos projets.</h1>
                <p>Connectez-vous pour accéder à votre espace, suivre vos demandes et rejoindre ZUMRA.</p>
            </div>

            <p class="login-foot">© DG Afrique · Écosystème GAMAD</p>
        </section>

        <section class="login-form-panel">
            <div class="login-form-wrap">
                <a class="mobile-brand" href="/">
                    <span class="brand-mark">G</span><span>DG Afrique</span>
                </a>

                <h2>Connexion</h2>
                <p class="form-intro">Accédez à votre espace DG Afrique.</p>

                @if (session('status'))
                    <div class="alert success" role="status">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert danger" role="alert">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="auth-form">
                    @csrf
                    <input type="hidden" name="next" value="{{ old('next', $next) }}">

                    <label for="identifier">E-mail ou téléphone</label>
                    <input
                        id="identifier"
                        name="identifier"
                        type="text"
                        value="{{ old('identifier') }}"
                        placeholder="vous@exemple.com"
                        autocomplete="username"
                        maxlength="512"
                        required
                        autofocus
                    >

                    <label for="secret">Mot de passe</label>
                    <input
                        id="secret"
                        name="secret"
                        type="password"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        maxlength="4096"
                        required
                    >

                    <div class="auth-options">
                        <label class="remember-choice">
                            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                            <span>Se souvenir de moi</span>
                        </label>
                        <span class="future-link" title="Disponible dans un prochain lot">Mot de passe oublié ?</span>
                    </div>

                    <button class="primary-button" type="submit">Se connecter</button>
                </form>

                <button class="whatsapp-button" type="button" disabled>
                    Continuer avec WhatsApp <small>— bientôt</small>
                </button>

                <p class="create-account">Pas encore de compte ? <a href="{{ route('register') }}">Créer un compte</a></p>
                <p class="identity-note">Votre Compte GAMAD reste distinct de l’adhésion au Programme ZUMRA.</p>
            </div>
        </section>
    </main>
</x-layouts.portal>
