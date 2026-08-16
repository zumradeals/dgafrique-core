<x-layouts.portal title="{{ $title }} — Messages DG Afrique">
    <div class="min-h-screen bg-slate-50">
        <header class="space-header">
            <div class="space-identity"><a class="brand" href="/"><span class="brand-mark">G</span><span>DG Afrique</span></a></div>
            <div class="space-search">⌕ <span>Conversation privée de coordination</span></div>
            <div class="space-identity"><a class="text-sm font-bold text-white no-underline" href="{{ route('messages.index') }}">← Messages</a><span class="avatar">{{ mb_strtoupper(mb_substr($identity->label,0,1)) }}</span></div>
        </header>

        <main class="mx-auto w-full max-w-5xl px-4 py-8 sm:px-6">
            @if(session('status'))<div class="alert success">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="alert danger">{{ $errors->first() }}</div>@endif

            <section class="rounded-[1.4rem] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-4"><div><p class="mb-1 text-xs font-black uppercase tracking-[.14em] text-sky-700">{{ $contextLabel }}</p><h1 class="m-0 text-2xl font-black text-slate-950">{{ $title }}</h1><p class="mt-2 text-sm text-slate-500">{{ $participantLabels->implode(' · ') }}</p></div>@if($contextUrl)<a class="rounded-xl bg-slate-100 px-3 py-2 text-sm font-black text-slate-700 no-underline" href="{{ $contextUrl }}">Voir le contexte →</a>@endif</div>

                @if($canManageParticipants)
                    <form class="mt-5 flex flex-wrap gap-2 rounded-xl bg-sky-50 p-4" method="POST" action="{{ route('messages.participants.add',$conversation) }}">@csrf<input class="min-w-0 flex-1 rounded-lg border border-sky-200 bg-white px-3 py-2 text-sm" name="person_reference" placeholder="Référence publique de la personne" required><button class="rounded-lg border-0 bg-sky-700 px-4 py-2 text-sm font-black text-white">Ajouter à la coordination</button><small class="w-full text-xs leading-5 text-sky-800">Ajoute uniquement à cette conversation. Aucun statut de membre du projet n’est créé.</small></form>
                @endif
            </section>

            <section class="mt-5 rounded-[1.4rem] border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                <div class="grid max-h-[34rem] gap-3 overflow-y-auto pr-1">
                    @forelse($entries as $entry)
                        @php($mine=$entry->sender_core_reference===$identity->reference)
                        <article class="{{ $mine ? 'ml-auto bg-sky-700 text-white' : 'mr-auto bg-slate-100 text-slate-800' }} max-w-[82%] rounded-2xl px-4 py-3">
                            <div class="flex items-center justify-between gap-4"><strong class="text-xs">{{ $senderLabels[$entry->sender_core_reference] ?? 'Membre DG Afrique' }}</strong><time class="text-[.68rem] opacity-70">{{ $entry->sent_at?->format('d/m H:i') }}</time></div><p class="mt-2 mb-0 whitespace-pre-wrap text-sm leading-6">{{ $entry->body }}</p>
                        </article>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-500">La conversation est ouverte. Écrivez seulement ce qui aide à coordonner l’action.</div>
                    @endforelse
                </div>

                <form class="mt-5 border-t border-slate-100 pt-5" method="POST" action="{{ route('messages.send',$conversation) }}">@csrf<textarea class="w-full rounded-xl border border-slate-200 p-3 text-sm leading-6" name="body" rows="4" maxlength="3000" placeholder="Écrire un message utile à la coordination…" required>{{ old('body') }}</textarea><div class="mt-2 flex flex-wrap items-center justify-between gap-3"><small class="text-xs text-slate-400">3 000 caractères max · historique factuel</small><button class="rounded-xl border-0 bg-slate-950 px-5 py-2.5 text-sm font-black text-white">Envoyer</button></div></form>
            </section>

            <p class="mt-4 text-center text-xs leading-5 text-slate-400">Cette messagerie n’ajoute ni commentaire public, ni partage, ni réaction. Ces capacités restent séparées.</p>
        </main>
    </div>
</x-layouts.portal>
