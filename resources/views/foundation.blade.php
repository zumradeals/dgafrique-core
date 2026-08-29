<x-layouts.public title="Découvrir" description="Découvrez les besoins et projets réellement publics sur DG Afrique.">
    <div class="mx-auto w-full max-w-6xl px-5 py-6 sm:px-8 lg:px-12 lg:py-10">
        <header class="flex items-center justify-between gap-4">
            <a href="{{ route('gateway') }}" class="dg-brand-text" aria-label="DG Afrique — accueil">DG Afrique</a>
            <div class="flex items-center gap-3">
                <a href="{{ route('login') }}" class="text-sm font-semibold text-[var(--color-primary)]">Se connecter</a>
                <x-dg.button :href="route('register')" variant="primary">Créer un compte</x-dg.button>
            </div>
        </header>

        <section class="py-12 lg:py-16">
            <p class="text-sm font-bold uppercase tracking-[.18em] text-[var(--color-network)]">Découvrir sans compte</p>
            <div class="mt-4 grid gap-8 lg:grid-cols-[1fr_auto] lg:items-end">
                <div>
                    <h1 class="max-w-3xl text-balance text-4xl font-black leading-[1.04] tracking-[-.04em] sm:text-5xl">Voyez ce qui avance réellement autour de vous.</h1>
                    <p class="mt-5 max-w-2xl text-lg leading-8 text-[var(--color-muted)]">Cette page ne montre que des informations rendues publiques par leurs auteurs. Rien n’est inventé pour remplir l’écran.</p>
                </div>
                <a href="{{ route('gateway') }}" class="text-sm font-semibold text-[var(--color-primary)]">Pourquoi DG Afrique ?</a>
            </div>
        </section>

        <section aria-labelledby="public-stats-title" class="grid gap-4 sm:grid-cols-3">
            <h2 class="sr-only" id="public-stats-title">Aperçu public réel</h2>
            <div class="rounded-3xl border border-black/5 bg-white p-6"><span class="text-3xl font-black">{{ number_format($publicStats['people'], 0, ',', ' ') }}</span><span class="mt-1 block text-sm text-[var(--color-muted)]">personnes découvrables</span></div>
            <div class="rounded-3xl border border-black/5 bg-white p-6"><span class="text-3xl font-black">{{ number_format($publicStats['projects'], 0, ',', ' ') }}</span><span class="mt-1 block text-sm text-[var(--color-muted)]">projets publics actifs</span></div>
            <div class="rounded-3xl border border-black/5 bg-white p-6"><span class="text-3xl font-black">{{ number_format($publicStats['countries'], 0, ',', ' ') }}</span><span class="mt-1 block text-sm text-[var(--color-muted)]">pays représentés publiquement</span></div>
        </section>

        <section class="py-12" aria-labelledby="moments-title">
            <div class="flex items-end justify-between gap-4">
                <div><p class="text-sm font-bold uppercase tracking-[.18em] text-[var(--color-growth)]">En mouvement</p><h2 id="moments-title" class="mt-2 text-3xl font-black tracking-[-.03em]">Besoins et projets publics</h2></div>
            </div>

            @if ($realMoments->isEmpty())
                <div class="mt-6 rounded-3xl border border-dashed border-black/15 bg-white/70 p-8 sm:p-10">
                    <h3 class="text-xl font-black">Le réseau public démarre ici.</h3>
                    <p class="mt-2 max-w-2xl text-[var(--color-muted)]">Aucun besoin ou projet public n’est disponible pour le moment. Nous préférons vous le dire clairement plutôt que d’afficher de faux contenus.</p>
                    <div class="mt-6"><x-dg.button :href="route('register')" variant="primary">Créer mon compte</x-dg.button></div>
                </div>
            @else
                <div class="mt-6 grid gap-4 lg:grid-cols-3">
                    @foreach ($realMoments as $moment)
                        <article class="rounded-3xl border border-black/5 bg-white p-6">
                            <span class="text-xs font-bold uppercase tracking-[.16em] text-[var(--color-primary)]">{{ $moment['type'] }}</span>
                            <h3 class="mt-3 text-xl font-black leading-tight">{{ $moment['titre'] }}</h3>
                            @if ($moment['lieu'])<p class="mt-3 text-sm text-[var(--color-muted)]">{{ $moment['lieu'] }}</p>@endif
                            <p class="mt-5 text-xs font-medium text-[var(--color-muted)]">{{ $moment['meta'] }}</p>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="rounded-[2rem] bg-[var(--color-deep)] px-6 py-8 text-white sm:px-8 lg:flex lg:items-center lg:justify-between lg:gap-8">
            <div><h2 class="text-2xl font-black">Vous voulez participer plutôt que regarder ?</h2><p class="mt-2 max-w-xl text-white/75">Créez un compte pour rendre votre intention actionnable et retrouver votre espace personnel.</p></div>
            <div class="mt-6 flex flex-col gap-3 sm:flex-row lg:mt-0"><x-dg.button :href="route('register')" variant="solar">Créer mon compte</x-dg.button><a href="{{ route('login') }}" class="inline-flex min-h-12 items-center justify-center font-semibold text-white underline underline-offset-4">J’ai déjà un compte</a></div>
        </section>
    </div>
</x-layouts.public>
