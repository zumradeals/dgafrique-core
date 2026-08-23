{{--
    Porte d'entrée sociale DG Afrique V2.
    La racine du domaine doit expliquer le réseau et permettre d'y entrer sans scroll.
    La landing éditoriale longue reste disponible via /decouvrir.
--}}
<x-layouts.portal
    title="DG Afrique — le réseau des capacités en action"
    :styles="['resources/css/gateway-v2.css']"
>
    <main class="dgg-shell">
        <header class="dgg-header">
            <a href="{{ route('gateway') }}" class="dg-brand" aria-label="DG Afrique — accueil">
                <span class="dg-brandmark" aria-hidden="true">
                    <span class="dg-brandmark__d">D</span>
                    <span class="dg-brandmark__g">G</span>
                </span>
                <span class="dg-brand__word">DG Afrique</span>
            </a>

            <div class="dgg-header__links">
                <a href="{{ route('landing') }}">Découvrir DG Afrique</a>
                <a href="{{ route('register') }}" class="dgg-header__join">Rejoindre</a>
            </div>
        </header>

        <section class="dgg-stage">
            <div class="dgg-story">
                <div class="dgg-story__copy">
                    <span class="dgg-eyebrow">Le réseau des capacités en action</span>
                    <h1>Votre capacité peut <em>changer une réalité.</em></h1>
                    <p>DG Afrique relie les personnes, les besoins, les projets et les collectifs pour transformer ce qui existe déjà en action utile.</p>

                    <div class="dgg-intents" aria-label="Ce que vous pouvez faire dans DG Afrique">
                        <span><b aria-hidden="true">+</b> Je peux aider</span>
                        <span><b aria-hidden="true">!</b> J’ai un besoin</span>
                        <span><b aria-hidden="true">↗</b> Je veux apprendre</span>
                    </div>

                    {{-- Aperçu illustratif — jamais une donnée métier réelle (§11 DESIGN-INVARIANTS.md). --}}
                    <p class="dgg-example-preview">{{ $exampleMoment['titre'] }} · Exemple</p>

                    <a href="{{ route('landing') }}" class="dgg-discover">Découvrir comment fonctionne le réseau <span aria-hidden="true">→</span></a>
                </div>

                <div class="dgg-africa" aria-hidden="true">
                    <x-dg.africa-network />
                </div>
            </div>

            <section class="dgg-entry" aria-labelledby="dgg-entry-title">
                <div class="dgg-entry__head">
                    <span class="dgg-entry__kicker">Entrez dans le réseau</span>
                    <h2 id="dgg-entry-title">Retrouvez ce qui peut avancer avec vous.</h2>
                </div>

                @if (session('status'))
                    <div class="dgg-alert dgg-alert--success" role="status">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="dgg-alert dgg-alert--danger" role="alert">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="dgg-form">
                    @csrf
                    <input type="hidden" name="next" value="{{ route('activity.index', absolute: false) }}">

                    <label class="dgg-field" for="gateway-identifier">
                        <span>E-mail ou téléphone</span>
                        <input
                            id="gateway-identifier"
                            name="identifier"
                            type="text"
                            value="{{ old('identifier') }}"
                            placeholder="vous@exemple.com"
                            autocomplete="username"
                            maxlength="512"
                            required
                        >
                    </label>

                    <label class="dgg-field" for="gateway-secret">
                        <span>Mot de passe</span>
                        <input
                            id="gateway-secret"
                            name="secret"
                            type="password"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            maxlength="4096"
                            required
                        >
                    </label>

                    <button type="submit" class="dgg-login">Se connecter</button>
                </form>

                <div class="dgg-entry__divider"><span>ou</span></div>

                <a href="{{ route('register') }}" class="dgg-register">Créer gratuitement mon compte</a>

                <p class="dgg-entry__foot">
                    Pas encore prêt ? <a href="{{ route('landing') }}">Explorez DG Afrique</a> avant de rejoindre le réseau.
                </p>
            </section>
        </section>

        <footer class="dgg-footer">
            <span>DG Afrique — le réseau où les capacités deviennent des actions.</span>
            <a href="{{ route('landing') }}">À propos du réseau</a>
        </footer>
    </main>
</x-layouts.portal>
