<x-layouts.portal title="Connexion — DG Afrique">
    <main class="av2-shell">
        <section class="av2-story" aria-label="DG Afrique">
            <span class="dg-brand" aria-hidden="true">
                <span class="dg-brandmark"><span class="dg-brandmark__d">D</span><span class="dg-brandmark__g">G</span></span>
                <span class="dg-brand__word">DG Afrique</span>
            </span>

            <div class="av2-promise">
                <span class="av2-eyebrow">Le réseau d’action</span>
                <h1 class="av2-headline">Reprenez le<br><em>mouvement.</em></h1>
                <p class="av2-sub">Connectez-vous pour retrouver votre fil, vos besoins et vos projets en cours.</p>
            </div>

            <p class="av2-foot">© DG Afrique — le réseau où les capacités deviennent des actions.</p>
        </section>

        <section class="av2-panel">
            <div class="av2-formwrap">
                <div class="av2-formhead">
                    <h1>Connexion</h1>
                    <p>Accédez à votre espace DG Afrique.</p>
                </div>

                @if (session('status'))
                    <div class="av2-alert av2-alert--success" role="status">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="av2-alert av2-alert--danger" role="alert">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="av2-form">
                    @csrf
                    <input type="hidden" name="next" value="{{ old('next', $next) }}">

                    <div class="av2-field">
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
                    </div>

                    <div class="av2-field">
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
                    </div>

                    <div class="av2-row-between">
                        <label class="av2-check">
                            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                            <span>Se souvenir de moi</span>
                        </label>
                        <span class="av2-quiet" title="Disponible dans un prochain lot">Mot de passe oublié ?</span>
                    </div>

                    <button class="av2-btn-primary" type="submit">Se connecter</button>
                </form>

                <button class="av2-btn-secondary" type="button" disabled>
                    Continuer avec WhatsApp <small>— bientôt</small>
                </button>

                <p class="av2-switchline">Pas encore de compte ? <a href="{{ route('register') }}">Créer un compte</a></p>
                <p class="av2-note">Votre Compte DG Afrique reste distinct de l’adhésion au Programme ZUMRA.</p>
            </div>
        </section>
    </main>
</x-layouts.portal>
