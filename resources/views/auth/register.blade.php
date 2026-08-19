<x-layouts.portal title="Créer mon compte — DG Afrique">
    <main class="av2-shell">
        <section class="av2-story" aria-label="DG Afrique">
            <span class="dg-brand" aria-hidden="true">
                <span class="dg-brandmark"><span class="dg-brandmark__d">D</span><span class="dg-brandmark__g">G</span></span>
                <span class="dg-brand__word">DG Afrique</span>
            </span>

            <div class="av2-promise">
                <span class="av2-eyebrow">Une porte d’entrée gratuite</span>
                <h1 class="av2-headline">Vos capacités<br>deviennent des <em>actions.</em></h1>
                <p class="av2-sub">Créez votre espace personnel. Déclarez ce que vous savez faire, rencontrez les bonnes personnes, agissez.</p>
                <div class="av2-identitynote">
                    <span class="mark">i</span>
                    <span>Votre identité est protégée par le service de confiance de DG Afrique. Ce compte ne vous inscrit pas automatiquement au Programme ZUMRA.</span>
                </div>
            </div>

            <p class="av2-foot">© DG Afrique — le réseau où les capacités deviennent des actions.</p>
        </section>

        <section class="av2-panel">
            <div class="av2-formwrap">
                <div class="av2-formhead">
                    <h1>Créer mon compte</h1>
                    <p>Un code de vérification sera envoyé par e-mail.</p>
                </div>

                @if ($errors->any())
                    <div class="av2-alert av2-alert--danger" role="alert">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('register.store') }}" class="av2-form">
                    @csrf

                    <div class="av2-field">
                        <label for="name">Nom complet</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Aminata Traoré" required autocomplete="name">
                    </div>

                    <div class="av2-field">
                        <label for="email">Adresse e-mail</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="vous@exemple.com" required autocomplete="email">
                    </div>

                    <div class="av2-field">
                        <label for="password">Mot de passe</label>
                        <input id="password" name="password" type="password" placeholder="••••••••" required autocomplete="new-password">
                    </div>

                    <div class="av2-field">
                        <label for="password_confirmation">Confirmer le mot de passe</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" placeholder="••••••••" required autocomplete="new-password">
                    </div>

                    <label class="av2-terms">
                        <input type="checkbox" name="terms" value="1" required>
                        <span>J’accepte les conditions d’utilisation et la politique de confidentialité.</span>
                    </label>

                    <button class="av2-btn-primary" type="submit">Créer mon compte</button>
                </form>

                <p class="av2-switchline">Déjà un compte ? <a href="{{ route('login') }}">Se connecter</a></p>
            </div>
        </section>
    </main>
</x-layouts.portal>
