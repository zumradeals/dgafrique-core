<x-layouts.portal title="Partager utilement — DG Afrique">
    <main class="min-h-screen bg-slate-50 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <a class="text-sm font-black text-sky-700 no-underline" href="{{ $source['url'] }}">← Retour à l’objet d’origine</a>
                <a class="text-sm font-black text-slate-600 no-underline" href="{{ route('shares.index') }}">Mes partages reçus</a>
            </div>

            @if(session('status'))
                <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-900">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-900">{{ $errors->first() }}</div>
            @endif

            <header class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <p class="text-xs font-black uppercase tracking-[.18em] text-sky-700">Partage avec contexte</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950">Partager utilement</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">Transmettez cet objet à une personne ou à l’une de vos ZUMRA en expliquant pourquoi il peut soutenir une action réelle. Le partage pointe toujours vers l’objet d’origine : il ne le copie pas.</p>
            </header>

            <section class="mt-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="text-xs font-black uppercase tracking-wide text-amber-700">{{ $source['label'] }} d’origine</span>
                    <a class="text-xs font-black text-sky-700 no-underline" href="{{ $source['url'] }}">Ouvrir la source →</a>
                </div>
                <h2 class="mt-2 text-xl font-black text-slate-950">{{ $source['title'] }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $source['summary'] }}</p>
            </section>

            <div class="mt-5 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm leading-6 text-sky-950">
                <strong>Le partage ne donne aucun droit supplémentaire.</strong> Le destinataire devra toujours être autorisé à consulter l’objet d’origine. Si cette visibilité change, le partage n’ouvre aucune porte de contournement.
            </div>

            <div class="mt-6 grid gap-5 lg:grid-cols-2">
                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[.16em] text-sky-700">À UNE PERSONNE</p>
                    <h2 class="mt-2 text-xl font-black text-slate-950">Choisir une personne qui peut déjà voir cet objet</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Seules les personnes actuellement découvrables et déjà autorisées à consulter la source sont proposées.</p>

                    @if($people->isEmpty())
                        <div class="mt-5 rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-5 text-sm leading-6 text-slate-500">Aucune personne découvrable éligible n’est disponible pour ce partage actuellement.</div>
                    @else
                        <form class="mt-5 grid gap-4" method="POST" action="{{ $storeUrl }}">
                            @csrf
                            <input type="hidden" name="target_type" value="PERSON">
                            <label class="grid gap-2 text-sm font-bold text-slate-800">Personne
                                <select class="rounded-xl border border-slate-300 bg-white p-3 font-normal" name="target_reference" required>
                                    <option value="">Choisir une personne</option>
                                    @foreach($people as $person)
                                        <option value="{{ $person['reference'] }}" @selected(old('target_type')==='PERSON' && old('target_reference')===$person['reference'])>{{ $person['label'] }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="grid gap-2 text-sm font-bold text-slate-800">Pourquoi lui transmettre cet objet ?
                                <textarea class="min-h-28 rounded-xl border border-slate-300 p-3 font-normal" name="context_note" maxlength="{{ $maxContextLength }}" required placeholder="Ex. : ce besoin correspond à la capacité que vous avez rendue visible…">{{ old('target_type')==='PERSON' ? old('context_note') : '' }}</textarea>
                            </label>
                            <button class="rounded-xl border-0 bg-sky-700 px-4 py-3 text-sm font-black text-white" type="submit">Partager avec cette personne</button>
                        </form>
                    @endif
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[.16em] text-emerald-700">À UNE ZUMRA</p>
                    <h2 class="mt-2 text-xl font-black text-slate-950">Faire circuler vers une équipe à laquelle vous appartenez</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Le partage est adressé à la ZUMRA. Chaque membre conserve ses propres droits sur l’objet d’origine.</p>

                    @if($groups->isEmpty())
                        <div class="mt-5 rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-5 text-sm leading-6 text-slate-500">Vous n’avez actuellement aucune ZUMRA active disponible comme destination.</div>
                    @else
                        <form class="mt-5 grid gap-4" method="POST" action="{{ $storeUrl }}">
                            @csrf
                            <input type="hidden" name="target_type" value="ZUMRA">
                            <label class="grid gap-2 text-sm font-bold text-slate-800">ZUMRA
                                <select class="rounded-xl border border-slate-300 bg-white p-3 font-normal" name="target_reference" required>
                                    <option value="">Choisir une ZUMRA</option>
                                    @foreach($groups as $group)
                                        <option value="{{ $group['reference'] }}" @selected(old('target_type')==='ZUMRA' && old('target_reference')===$group['reference'])>{{ $group['label'] }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="grid gap-2 text-sm font-bold text-slate-800">Pourquoi cet objet peut-il servir à cette ZUMRA ?
                                <textarea class="min-h-28 rounded-xl border border-slate-300 p-3 font-normal" name="context_note" maxlength="{{ $maxContextLength }}" required placeholder="Ex. : ce projet peut mobiliser notre capacité collective en…">{{ old('target_type')==='ZUMRA' ? old('context_note') : '' }}</textarea>
                            </label>
                            <button class="rounded-xl border-0 bg-emerald-700 px-4 py-3 text-sm font-black text-white" type="submit">Partager avec cette ZUMRA</button>
                        </form>
                    @endif
                </section>
            </div>

            <p class="mt-6 text-center text-xs leading-5 text-slate-400">Aucun compteur de partage, score d’engagement ou classement de popularité n’est créé ici.</p>
        </div>
    </main>
</x-layouts.portal>
