<x-layouts.portal title="Commentaires — DG Afrique">
    <main class="min-h-screen bg-slate-50">
        <div class="mx-auto w-full max-w-4xl px-4 py-8 sm:px-6 lg:py-12">
            <nav class="mb-6 text-sm font-bold">
                <a class="text-sky-700 hover:text-sky-900" href="{{ $context['back_url'] }}">← {{ $context['back_label'] }}</a>
            </nav>

            <header class="rounded-3xl bg-slate-950 px-6 py-8 text-white shadow-xl shadow-slate-200 sm:px-9">
                <p class="text-xs font-black uppercase tracking-[.18em] text-cyan-300">Contribution contextuelle</p>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-black">{{ $context['label'] }}</span>
                    <span class="text-xs font-semibold text-slate-400">Le commentaire reste attaché à ce contexte</span>
                </div>
                <h1 class="mt-4 text-3xl font-black tracking-tight">{{ $context['title'] }}</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">{{ $context['summary'] }}</p>
            </header>

            @if(session('status'))
                <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">{{ $errors->first() }}</div>
            @endif

            <section class="mt-6 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[.16em] text-sky-700">Contribution courte</p>
                        <h2 class="mt-1 text-xl font-black text-slate-950">Ajouter quelque chose d’utile</h2>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Posez une question, apportez une précision, conseillez, signalez une ressource ou coordonnez une action. Aucun like, aucun classement, aucune course à l’attention.</p>
                    </div>
                </div>

                @if($context['can_comment'])
                    <form class="mt-5 grid gap-4" method="POST" action="{{ request()->url() }}">
                        @csrf
                        <label class="grid gap-2 text-sm font-bold text-slate-700">
                            Intention de la contribution
                            <select class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800" name="purpose" required>
                                @foreach($purposeLabels as $code => $label)
                                    <option value="{{ $code }}" @selected(old('purpose') === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="grid gap-2 text-sm font-bold text-slate-700">
                            Votre contribution
                            <textarea class="min-h-32 rounded-2xl border border-slate-200 px-4 py-3 text-sm leading-6 text-slate-800" name="body" maxlength="1200" required placeholder="Une question, une précision, un conseil, une ressource ou une prochaine action…">{{ old('body') }}</textarea>
                        </label>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <span class="text-xs font-semibold text-slate-400">1 200 caractères maximum · pas de publication indépendante</span>
                            <button class="rounded-2xl bg-sky-700 px-5 py-3 text-sm font-black text-white hover:bg-sky-800" type="submit">Ajouter la contribution</button>
                        </div>
                    </form>
                @else
                    <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-800">{{ $context['closed_text'] }}</div>
                @endif
            </section>

            <section class="mt-6 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div><p class="text-xs font-black uppercase tracking-[.16em] text-slate-400">Historique contextuel</p><h2 class="mt-1 text-xl font-black text-slate-950">Contributions</h2></div>
                    <span class="text-xs font-semibold text-slate-400">{{ $comments->count() }} contribution{{ $comments->count() > 1 ? 's' : '' }} affichée{{ $comments->count() > 1 ? 's' : '' }}</span>
                </div>

                @if($comments->isEmpty())
                    <div class="mt-5 rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-6 text-center text-sm leading-6 text-slate-500">Aucune contribution pour le moment. Le système n’invente aucun commentaire pour remplir l’espace.</div>
                @else
                    <div class="mt-5 grid gap-3">
                        @foreach($comments as $comment)
                            <article class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex flex-wrap items-center gap-2"><span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-black text-sky-800">{{ $comment['purpose_label'] }}</span><strong class="text-sm text-slate-900">{{ $comment['author_label'] }}</strong></div>
                                    <time class="text-xs font-semibold text-slate-400" datetime="{{ $comment['posted_at']->toAtomString() }}">{{ $comment['posted_at']->locale('fr')->diffForHumans() }}</time>
                                </div>
                                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $comment['body'] }}</p>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <p class="mt-5 text-center text-xs font-semibold leading-5 text-slate-400">Les commentaires servent le contexte et l’action. Ils ne deviennent ni un fil social autonome, ni un signal de popularité.</p>
        </div>
    </main>
</x-layouts.portal>
