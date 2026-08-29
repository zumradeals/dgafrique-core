<x-layouts.public title="Connexion" description="Connectez-vous à votre espace DG Afrique.">
    <section class="mx-auto grid min-h-[100svh] w-full max-w-6xl gap-10 px-5 py-6 sm:px-8 lg:grid-cols-[.9fr_1.1fr] lg:items-center lg:px-12 lg:py-10">
        <div class="order-2 lg:order-1">
            <a href="{{ route('gateway') }}" class="dg-brand-text" aria-label="DG Afrique — accueil">DG Afrique</a>
            <p class="mt-10 text-sm font-bold uppercase tracking-[.18em] text-[var(--color-growth)]">Votre espace</p>
            <h1 class="mt-3 max-w-xl text-balance text-4xl font-black leading-[1.04] tracking-[-.04em] sm:text-5xl">Reprenez là où vous en étiez.</h1>
            <p class="mt-5 max-w-lg text-lg leading-8 text-[var(--color-muted)]">Connectez-vous pour retrouver vos priorités, vos collaborations et la prochaine action qui vous concerne.</p>
            <p class="mt-7 text-sm text-[var(--color-muted)]">Pas encore de compte ? <a class="font-semibold text-[var(--color-primary)] underline underline-offset-4" href="{{ route('register') }}">Créer mon compte</a></p>
        </div>

        <div class="order-1 rounded-[2rem] border border-black/5 bg-white p-6 shadow-[0_22px_70px_rgba(8,59,86,.10)] sm:p-8 lg:order-2 lg:p-10">
            <h2 class="text-2xl font-black tracking-[-.025em]">Se connecter</h2>
            <p class="mt-2 text-sm leading-6 text-[var(--color-muted)]">Utilisez votre identifiant habituel et votre moyen d’accès.</p>

            @if (session('status'))
                <x-dg.notice class="mt-6" type="success" title="Information">{{ session('status') }}</x-dg.notice>
            @endif

            <form class="mt-7 grid gap-5" method="post" action="{{ route('login.store') }}" novalidate>
                @csrf
                <input type="hidden" name="next" value="{{ $next }}">
                <x-dg.field label="Identifiant" for="identifier" :error="$errors->first('identifier')" required>
                    <x-dg.input id="identifier" name="identifier" autocomplete="username" :value="old('identifier')" :invalid="$errors->has('identifier')" required autofocus />
                </x-dg.field>
                <x-dg.field label="Moyen d’accès" for="secret" hint="Votre moyen d’accès n’est jamais réaffiché après un échec." :error="$errors->first('secret')" required>
                    <x-dg.input id="secret" name="secret" type="password" autocomplete="current-password" :invalid="$errors->has('secret')" required />
                </x-dg.field>
                <x-dg.button type="submit" variant="primary" class="w-full justify-center">Entrer dans mon espace</x-dg.button>
            </form>

            <div class="mt-6 border-t border-black/10 pt-5 text-sm text-[var(--color-muted)]">
                <a href="{{ route('landing') }}" class="font-semibold text-[var(--color-primary)]">Découvrir DG Afrique sans se connecter</a>
            </div>
        </div>
    </section>
</x-layouts.public>
