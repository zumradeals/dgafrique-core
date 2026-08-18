@props(['item'])
<article class="dg-card dg-card--forest" style="display:flex;flex-direction:column;gap:14px">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <x-dg.badge tone="action">{{ $item['event_label'] }}</x-dg.badge>
        <span class="dg-meta" style="color:var(--dg-faint)">{{ $item['occurred_at']->locale('fr')->diffForHumans() }}</span>
    </div>

    <h2 class="dg-display dg-display--card" style="font-size:26px;max-width:30ch">{{ $item['title'] }}</h2>
    @if($item['resolution_note'])
        <p class="dg-body">Note de résolution : « {{ $item['resolution_note'] }} »</p>
    @else
        <p class="dg-body">{{ $item['summary'] }}</p>
    @endif

    <x-dg.actions>
        <x-dg.btn variant="learn" disabled title="L’intention « je veux apprendre » n’est pas encore distinguée par le besoin métier.">Je veux apprendre à faire pareil</x-dg.btn>
        <x-dg.btn variant="quiet" :href="$item['action_url']">Voir comment ça s’est fait</x-dg.btn>
        <x-dg.btn variant="quiet" :href="$item['share_url']">Partager avec contexte</x-dg.btn>
    </x-dg.actions>
</article>
