<x-layouts.portal title="Vérifier mon compte — DG Afrique">
    <main class="av2-shell">
        <section class="av2-story" aria-label="DG Afrique">
            <span class="dg-brand" aria-hidden="true">
                <span class="dg-brandmark"><span class="dg-brandmark__d">D</span><span class="dg-brandmark__g">G</span></span>
                <span class="dg-brand__word">DG Afrique</span>
            </span>

            <div class="av2-promise">
                <span class="av2-eyebrow">Dernière étape</span>
                <h1 class="av2-headline">Vérifiez votre<br><em>adresse.</em></h1>
                <p class="av2-sub">Le code est émis par le service d’identité sécurisé. DG Afrique ne le conserve pas.</p>
            </div>

            <p class="av2-foot">Code à usage unique · Reprise bornée</p>
        </section>

        <section class="av2-panel">
            <div class="av2-formwrap">
                <div class="av2-formhead">
                    <h1>Saisissez les 6 chiffres</h1>
                    <p>Code envoyé à <strong>{{ $pending->destination }}</strong>.</p>
                </div>

                @if (session('status'))
                    <div class="av2-alert av2-alert--success" role="status">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="av2-alert av2-alert--danger" role="alert">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('register.verify.store') }}" class="av2-form">
                    @csrf
                    <div class="av2-field">
                        <label for="code">Code de vérification</label>
                        <input id="code" name="code" class="av2-code-input" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code">
                    </div>
                    <button class="av2-btn-primary" type="submit">Vérifier mon compte</button>
                </form>

                <form method="POST" action="{{ route('register.verify.resend') }}">
                    @csrf
                    <button class="av2-btn-outline" type="submit">Renvoyer un code</button>
                </form>

                <p class="av2-switchline"><a href="{{ route('register') }}">Recommencer avec une autre adresse</a></p>
            </div>
        </section>
    </main>
</x-layouts.portal>
