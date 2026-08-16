<x-layouts.portal title="Activité utile — DG Afrique">
    <main class="min-h-screen bg-slate-50">
        <div class="mx-auto w-full max-w-5xl px-4 py-8 sm:px-6 lg:py-12">
            <nav class="mb-7 flex flex-wrap items-center gap-3 text-sm font-semibold">
                <a class="text-sky-700 hover:text-sky-900" href="{{ route('member.space') }}">← Mon espace</a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-500">Fil d’activité utile</span>
            </nav>

            <header class="overflow-hidden rounded-3xl bg-slate-950 px-6 py-8 text-white shadow-xl shadow-slate-200 sm:px-9 sm:py-10">
                <p class="mb-3 text-xs font-black uppercase tracking-[.18em] text-cyan-300">CAP-019 · Communication au service de l’action</p>
                <div class="grid gap-6 lg:grid-cols-[1fr_auto] lg:items-end">
                    <div>
                        <h1 class="m-0 text-3xl font-black tracking-tight sm:text-4xl">Ce qui peut vous faire avancer</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">Besoins, projets et mouvements ZUMRA réellement enregistrés dans DG Afrique. Le fil privilégie l’utilité et s’arrête : aucun défilement infini, aucune course à la popularité.</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-xs leading-5 text-slate-300">
                        <strong class="block text-white">Votre visibilité est respectée</strong>
                        Vous ne voyez que ce que votre identité est autorisée à consulter.
                    </div>
                </div>
            </header>

            <form class="mt-6 flex flex-wrap gap-2" method="GET" aria-label="Filtrer l’activité">
                @foreach($filters as $code => $label)
                    <button
                        class="rounded-full border px-4 py-2 text-sm font-bold transition {{ $filter === $code ? 'border-sky-700 bg-sky-700 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700' }}"
                        type="submit"
                        name="type"
                        value="{{ $code }}"
                    >{{ $label }}</button>
                @endforeach
            </form>

            @if($feed->isEmpty())
                <section class="mt-8 rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                    <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-sky-50 text-xl font-black text-sky-700">A</span>
                    <h2 class="mt-4 text-xl font-black text-slate-900">Aucune activité utile à afficher</h2>
                    <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-500">Le fil ne fabrique rien. Des éléments apparaîtront lorsqu’un besoin, un projet ou une ZUMRA produira un événement réellement visible pour vous.</p>
                </section>
            @else
                <section class="mt-7 grid gap-4" aria-label="Activité">
                    @foreach($feed as $item)
                        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="rounded-full px-3 py-1 text-xs font-black {{ $item['kind'] === 'NEEDS' ? 'bg-amber-50 text-amber-800' : ($item['kind'] === 'PROJECTS' ? 'bg-sky-50 text-sky-800' : 'bg-emerald-50 text-emerald-800') }}">{{ $item['kind_label'] }}</span>
                                    <span class="text-xs font-bold text-slate-500">{{ $item['event_label'] }}</span>
                                </div>
                                <time class="text-xs font-semibold text-slate-400" datetime="{{ $item['occurred_at']->toAtomString() }}">{{ $item['occurred_at']->locale('fr')->diffForHumans() }}</time>
                            </div>

                            <h2 class="mt-4 text-xl font-black tracking-tight text-slate-950">{{ $item['title'] }}</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $item['summary'] }}</p>

                            @if($item['context'])
                                <p class="mt-3 rounded-2xl bg-slate-50 px-4 py-3 text-xs font-semibold leading-5 text-slate-600">{{ $item['context'] }}</p>
                            @endif

                            <footer class="mt-5 flex items-center justify-between gap-4 border-t border-slate-100 pt-4">
                                <span class="text-xs font-semibold text-slate-400">Information issue d’un événement métier DG Afrique</span>
                                <a class="shrink-0 text-sm font-black text-sky-700 hover:text-sky-900" href="{{ $item['action_url'] }}">{{ $item['action_label'] }} →</a>
                            </footer>
                        </article>
                    @endforeach
                </section>

                <div class="mt-8">{{ $feed->links() }}</div>
                <p class="mt-5 text-center text-xs font-semibold text-slate-400">Le fil s’arrête ici. Revenez quand une nouvelle action réelle apparaît.</p>
            @endif
        </div>
    </main>
</x-layouts.portal>
