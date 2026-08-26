<x-layouts.portal title="{{ $title }} — DG Afrique">
    <main class="min-h-screen bg-slate-50 px-4 py-8 sm:px-6 lg:px-8 {{ request()->routeIs('shares.group') ? 'zd-context' : '' }}">
        <div class="mx-auto max-w-5xl {{ request()->routeIs('shares.group') ? 'zd-context__inner' : '' }}">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <a class="text-sm font-black text-sky-700 no-underline" href="{{ $backUrl }}">← {{ $backLabel }}</a>
                <a class="text-sm font-black text-slate-600 no-underline" href="{{ route('activity.index') }}">Activité utile</a>
            </div>

            <header class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <p class="text-xs font-black uppercase tracking-[.18em] text-sky-700">CIRCULATION UTILE</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950">{{ $title }}</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">{{ $intro }}</p>
            </header>

            @if($shares->isEmpty())
                <section class="mt-6 rounded-3xl border border-dashed border-slate-200 bg-white p-8 text-center">
                    <h2 class="text-xl font-black text-slate-900">Aucun partage visible pour le moment</h2>
                    <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-500">Cette liste ne fabrique aucun contenu. Un partage apparaît seulement lorsqu’un membre vous transmet un objet réel auquel vous avez encore accès.</p>
                </section>
            @else
                <div class="mt-6 grid gap-4">
                    @foreach($shares as $share)
                        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="text-xs font-black uppercase tracking-wide {{ $share['source_type']==='NEED' ? 'text-amber-700' : 'text-sky-700' }}">{{ $share['source_label'] }} partagé</span>
                                <time class="text-xs font-semibold text-slate-400">{{ $share['shared_at']?->locale('fr')->diffForHumans() }}</time>
                            </div>
                            <p class="mt-3 text-xs font-bold uppercase tracking-wide text-slate-500">{{ $share['sharer_label'] }} vous transmet cet objet avec ce contexte :</p>
                            <blockquote class="mt-2 rounded-2xl border-l-4 border-sky-500 bg-sky-50 p-4 text-sm font-semibold leading-6 text-slate-800">{{ $share['context_note'] }}</blockquote>
                            <div class="mt-5 border-t border-slate-100 pt-5">
                                <h2 class="text-lg font-black text-slate-950">{{ $share['source_title'] }}</h2>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $share['source_summary'] }}</p>
                                <a class="mt-4 inline-block text-sm font-black text-sky-700 no-underline" href="{{ $share['source_url'] }}">Ouvrir l’objet d’origine →</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif

            <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 text-xs leading-5 text-slate-500">Cette liste ne mesure ni popularité ni influence. Les droits sur chaque besoin ou projet restent ceux de l’objet d’origine et sont vérifiés au moment où vous consultez cette page.</div>
        </div>
    </main>
</x-layouts.portal>
