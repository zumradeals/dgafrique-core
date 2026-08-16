<x-layouts.portal title="Messages — DG Afrique">
    <div class="min-h-screen bg-slate-50">
        <header class="space-header">
            <div class="space-identity"><a class="brand" href="/"><span class="brand-mark">G</span><span>DG Afrique</span></a></div>
            <div class="space-search">⌕ <span>Coordination privée et contextuelle</span></div>
            <div class="space-identity"><a class="text-sm font-bold text-white no-underline" href="{{ route('member.space') }}">← Mon espace</a><span class="avatar">{{ mb_strtoupper(mb_substr($identity->label,0,1)) }}</span></div>
        </header>

        <main class="mx-auto w-full max-w-5xl px-4 py-8 sm:px-6">
            @if(session('status'))<div class="alert success">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="alert danger">{{ $errors->first() }}</div>@endif

            <section class="rounded-[1.5rem] bg-slate-950 p-6 text-white shadow-xl sm:p-8">
                <div class="flex flex-wrap items-end justify-between gap-5">
                    <div><p class="mb-2 text-xs font-black uppercase tracking-[.16em] text-cyan-300">CAP-020 · coordination</p><h1 class="m-0 text-3xl font-black tracking-tight">Vos conversations d’action</h1><p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">Échangez avec une personne, une ZUMRA ou autour d’un besoin et d’un projet. La messagerie reste privée et au service d’une action concrète.</p></div>
                    <form method="POST" action="{{ route('messages.support') }}">@csrf<button class="rounded-xl border-0 bg-cyan-300 px-4 py-3 text-sm font-black text-slate-950">Écrire à DG Afrique</button></form>
                </div>
            </section>

            <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 text-sm leading-6 text-slate-600">
                Pour contacter une personne pour la première fois, ouvrez son profil dans <a class="font-black text-sky-700" href="{{ route('people.index') }}">Découvrir des personnes</a>. Les conversations liées à une ZUMRA, un besoin ou un projet se démarrent depuis l’objet concerné.
            </section>

            <section class="mt-6 grid gap-3">
                @forelse($conversations as $item)
                    <a class="block rounded-2xl border border-slate-200 bg-white p-5 no-underline shadow-sm transition hover:border-sky-200" href="{{ route('messages.show',$item['conversation']) }}">
                        <div class="flex flex-wrap items-start justify-between gap-3"><div><span class="text-xs font-black uppercase tracking-wide text-sky-700">{{ $item['context_label'] }}</span><h2 class="mt-1 text-lg font-black text-slate-950">{{ $item['title'] }}</h2></div><div class="flex items-center gap-2">@if($item['unread'])<span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-black text-amber-800">Nouveau</span>@endif<time class="text-xs font-semibold text-slate-400">{{ $item['last_message_at']?->locale('fr')->diffForHumans() }}</time></div></div>
                        <p class="mt-2 mb-0 text-sm leading-6 text-slate-500">{{ $item['last_message'] ? \Illuminate\Support\Str::limit($item['last_message'],160) : 'Conversation ouverte. Aucun message envoyé.' }}</p>
                    </a>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center"><h2 class="text-xl font-black text-slate-950">Aucune conversation pour le moment</h2><p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-500">La messagerie ne fabrique pas de contacts. Elle s’ouvre lorsqu’un contexte réel justifie une coordination.</p></div>
                @endforelse
            </section>
        </main>
    </div>
</x-layouts.portal>
