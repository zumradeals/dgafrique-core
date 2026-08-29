<x-layouts.public title="Bienvenue" description="DG Afrique relie les personnes, les besoins et les projets pour transformer une intention en action concrète.">
    <section class="mx-auto flex min-h-[100svh] w-full max-w-6xl flex-col justify-between px-5 py-6 sm:px-8 lg:px-12 lg:py-10">
        <header class="flex items-center justify-between gap-4">
            <a href="{{ route('gateway') }}" class="dg-brand-text" aria-label="DG Afrique — accueil">DG Afrique</a>
            <a href="{{ route('login') }}" class="text-sm font-semibold text-[var(--color-primary)] underline-offset-4 hover:underline">Se connecter</a>
        </header>

        <div class="grid gap-10 py-12 lg:grid-cols-[1.08fr_.92fr] lg:items-center">
            <div class="max-w-2xl">
                <p class="mb-4 text-sm font-bold uppercase tracking-[.18em] text-[var(--color-growth)]">Réseau social d’action</p>
                <h1 class="text-balance text-4xl font-black leading-[1.02] tracking-[-.045em] text-[var(--color-ink)] sm:text-5xl lg:text-7xl">
                    Des personnes qui transforment des intentions en actions réelles.
                </h1>
                <p class="mt-6 max-w-xl text-lg leading-8 text-[var(--color-muted)]">
                    DG Afrique vous aide à trouver des savoir-faire, exprimer un besoin, rejoindre un projet et avancer avec les bonnes personnes — sans avoir à comprendre la technologie qui relie tout cela.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <x-dg.button :href="route('register')" variant="primary">Créer mon compte</x-dg.button>
                    <x-dg.button :href="route('landing')" variant="secondary">Découvrir d’abord</x-dg.button>
                </div>
                <p class="mt-4 text-sm leading-6 text-[var(--color-muted)]">Créer un compte DG Afrique ne vous inscrit pas automatiquement à une ZUMRA.</p>
            </div>

            <aside class="rounded-[2rem] border border-black/5 bg-white p-6 shadow-[0_22px_70px_rgba(8,59,86,.10)] sm:p-8" aria-label="Ce que vous pouvez faire sur DG Afrique">
                <div class="mb-7 flex h-14 w-14 items-center justify-center rounded-2xl bg-[var(--color-solar)] text-[var(--color-ink)]">
                    <x-dg.icon name="act" size="28" />
                </div>
                <h2 class="text-2xl font-black tracking-[-.025em]">Commencez par ce qui compte pour vous.</h2>
                <div class="mt-6 grid gap-4">
                    <div class="rounded-2xl bg-[var(--color-canvas)] p-4"><strong class="block">J’ai quelque chose à apporter</strong><span class="mt-1 block text-sm text-[var(--color-muted)]">Rendez vos savoir-faire et disponibilités découvrables.</span></div>
                    <div class="rounded-2xl bg-[var(--color-canvas)] p-4"><strong class="block">J’ai un besoin concret</strong><span class="mt-1 block text-sm text-[var(--color-muted)]">Expliquez ce qui manque pour faire avancer une action.</span></div>
                    <div class="rounded-2xl bg-[var(--color-canvas)] p-4"><strong class="block">Je veux découvrir</strong><span class="mt-1 block text-sm text-[var(--color-muted)]">Explorez uniquement ce qui est réellement public.</span></div>
                </div>
            </aside>
        </div>

        <footer class="flex flex-col gap-2 border-t border-black/10 pt-5 text-sm text-[var(--color-muted)] sm:flex-row sm:items-center sm:justify-between">
            <span>DG Afrique · agir, collaborer, prouver.</span>
            <a href="{{ route('landing') }}" class="font-semibold text-[var(--color-primary)]">Voir le réseau public</a>
        </footer>
    </section>
</x-layouts.public>
