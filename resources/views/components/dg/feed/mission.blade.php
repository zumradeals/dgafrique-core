{{--
    Carte Mission du Fil. Une Mission ne crée jamais un second fil : elle s'intègre au Fil
    global unique, avec les mêmes actions revalidées à la projection (CAP-069 §15).
--}}
@props(['item'])
<article class="dg-card" style="display:flex;flex-direction:column;gap:14px">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-2.5">
            <x-dg.badge :tone="$item['badge_tone']">{{ $item['event_label'] }}</x-dg.badge>
            @if($item['context'])
                <span class="dg-meta">{{ $item['context'] }}</span>
            @endif
        </div>
        <span class="dg-meta" style="color:var(--dg-faint)">{{ $item['occurred_at']->locale('fr')->diffForHumans() }}</span>
    </div>

    <h2 class="dg-display dg-display--card" style="max-width:26ch">
        <a href="{{ $item['action_url'] }}" style="color:inherit">{{ $item['title'] }}</a>
    </h2>
    <p class="dg-body">{{ $item['summary'] }}</p>

    <x-dg.actions>
        <x-dg.btn variant="quiet" :href="$item['action_url']">Voir la Mission</x-dg.btn>
        @if($item['comment_url'])
            <x-dg.btn variant="quiet" :href="$item['comment_url']">Commenter</x-dg.btn>
        @endif
        @if($item['share_url'])
            <x-dg.btn variant="quiet" :href="$item['share_url']">Partager avec contexte</x-dg.btn>
        @endif
    </x-dg.actions>
</article>
