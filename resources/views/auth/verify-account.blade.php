<x-layouts.public title="Vérifier le compte" description="Vérifiez votre compte DG Afrique avec le code reçu.">
    <section class="mx-auto flex min-h-[100svh] w-full max-w-3xl items-center px-5 py-8 sm:px-8">
        <div class="w-full rounded-[2rem] border border-black/5 bg-white p-6 shadow-[0_22px_70px_rgba(8,59,86,.10)] sm:p-10">
            <a href="{{ route('gateway') }}" class="dg-brand-text" aria-label="DG Afrique — accueil">DG Afrique</a>
            <p class="mt-8 text-sm font-bold uppercase tracking-[.18em] text-[var(--color-growth)]">Vérification</p>
            <h1 class="mt-3 text-balance text-3xl font-black tracking-[-.035em] sm:text-4xl">Confirmez que cette adresse vous appartient.</h1>
            <p class="mt-4 max-w-2xl leading-7 text-[var(--color-muted)]">Saisissez le code à 6 chiffres envoyé à <strong class="text-[var(--color-ink)]">{{ $pending->destination }}</strong>.</p>

            @if (session('status'))
                <x-dg.notice class="mt-6" type="success" title="Information">{{ session('status') }}</x-dg.notice>
            @endif
            @if (!$pending->delivered)
                <x-dg.notice class="mt-6" type="warning" title="Code non livré">Le compte existe, mais le dernier code n’a pas été livré. Vous pouvez demander un nouvel envoi.</x-dg.notice>
            @endif

            <form class="mt-7 grid gap-5" method="post" action="{{ route('register.verify.store') }}" novalidate>
                @csrf
                <x-dg.field label="Code à 6 chiffres" for="code" hint="Le code est temporaire et ne sera jamais mémorisé dans le formulaire." :error="$errors->first('code')" required>
                    <x-dg.input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" :invalid="$errors->has('code')" required autofocus />
                </x-dg.field>
                <x-dg.button type="submit" variant="primary" class="w-full justify-center">Vérifier mon compte</x-dg.button>
            </form>

            <div class="mt-6 flex flex-col gap-4 border-t border-black/10 pt-6 sm:flex-row sm:items-center sm:justify-between">
                <form method="post" action="{{ route('register.verify.resend') }}">
                    @csrf
                    <button type="submit" class="text-sm font-semibold text-[var(--color-primary)] underline underline-offset-4">Renvoyer un code</button>
                </form>
                <a href="{{ route('register') }}" class="text-sm text-[var(--color-muted)] underline underline-offset-4">Recommencer la création du compte</a>
            </div>
        </div>
    </section>
</x-layouts.public>
