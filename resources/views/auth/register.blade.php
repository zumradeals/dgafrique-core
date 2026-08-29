<x-layouts.public title="Créer un compte" description="Créez votre compte DG Afrique pour commencer à agir avec le réseau.">
    <section class="mx-auto grid min-h-[100svh] w-full max-w-6xl gap-10 px-5 py-6 sm:px-8 lg:grid-cols-[.9fr_1.1fr] lg:items-center lg:px-12 lg:py-10">
        <div>
            <a href="{{ route('gateway') }}" class="dg-brand-text" aria-label="DG Afrique — accueil">DG Afrique</a>
            <p class="mt-10 text-sm font-bold uppercase tracking-[.18em] text-[var(--color-network)]">Première étape</p>
            <h1 class="mt-3 max-w-xl text-balance text-4xl font-black leading-[1.04] tracking-[-.04em] sm:text-5xl">Créez votre espace. Le reste viendra au bon moment.</h1>
            <p class="mt-5 max-w-lg text-lg leading-8 text-[var(--color-muted)]">Nous demandons seulement ce qui est nécessaire pour créer et vérifier votre compte. Vous pourrez ensuite préciser vos capacités, besoins et projets progressivement.</p>
            <div class="mt-7 rounded-2xl bg-white/70 p-5 text-sm leading-6 text-[var(--color-muted)]"><strong class="text-[var(--color-ink)]">À savoir :</strong> un compte DG Afrique vous donne accès au réseau. L’adhésion à une ZUMRA est une démarche distincte, avec ses propres règles.</div>
            <p class="mt-7 text-sm text-[var(--color-muted)]">Déjà inscrit ? <a class="font-semibold text-[var(--color-primary)] underline underline-offset-4" href="{{ route('login') }}">Se connecter</a></p>
        </div>

        <div class="rounded-[2rem] border border-black/5 bg-white p-6 shadow-[0_22px_70px_rgba(8,59,86,.10)] sm:p-8 lg:p-10">
            <h2 class="text-2xl font-black tracking-[-.025em]">Créer mon compte</h2>
            @if ($errors->any())
                <x-dg.notice class="mt-6" type="danger" title="Vérifiez les informations">Certaines informations doivent être corrigées avant de continuer.</x-dg.notice>
            @endif

            <form class="mt-7 grid gap-5" method="post" action="{{ route('register.store') }}" novalidate>
                @csrf
                <x-dg.field label="Nom" for="name" :error="$errors->first('name')" required>
                    <x-dg.input id="name" name="name" autocomplete="name" :value="old('name')" :invalid="$errors->has('name')" required autofocus />
                </x-dg.field>
                <x-dg.field label="Adresse e-mail" for="email" hint="Elle servira à vérifier et retrouver votre compte." :error="$errors->first('email')" required>
                    <x-dg.input id="email" name="email" type="email" autocomplete="email" :value="old('email')" :invalid="$errors->has('email')" required />
                </x-dg.field>
                <x-dg.field label="Mot de passe" for="password" hint="Au moins 8 caractères avec des lettres et des chiffres." :error="$errors->first('password')" required>
                    <x-dg.input id="password" name="password" type="password" autocomplete="new-password" :invalid="$errors->has('password')" required />
                </x-dg.field>
                <x-dg.field label="Confirmer le mot de passe" for="password_confirmation" required>
                    <x-dg.input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required />
                </x-dg.field>

                <label class="flex items-start gap-3 rounded-2xl bg-[var(--color-canvas)] p-4 text-sm leading-6">
                    <input class="mt-1 h-4 w-4" type="checkbox" name="terms" value="1" @checked(old('terms')) required>
                    <span>J’accepte les conditions nécessaires à la création de mon compte DG Afrique.</span>
                </label>
                @error('terms')<p class="dg-field__error">{{ $message }}</p>@enderror

                <x-dg.button type="submit" variant="primary" class="w-full justify-center">Créer mon compte</x-dg.button>
            </form>
        </div>
    </section>
</x-layouts.public>
