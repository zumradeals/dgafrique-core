{{--
    Carte Besoin du Fil. Contrat manquant documenté (voir docs/design/DIFFERENCES.md) :
    « Je veux apprendre » n'a pas encore de comportement métier distinct de « Je peux aider » —
    les deux ouvriraient aujourd'hui la même conversation de coordination (CAP-020).
--}}
@props(['item'])
<article class="dg-card dg-card--copper" style="display:flex;flex-direction:column;gap:14px">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-2.5">
            <x-dg.badge tone="need">{{ $item['event_label'] }}</x-dg.badge>
            @if($item['location'])
                <span class="dg-meta">{{ $item['location'] }}</span>
            @endif
        </div>
        <span class="dg-meta" style="color:var(--dg-faint)">{{ $item['occurred_at']->locale('fr')->diffForHumans() }}</span>
    </div>

    <h2 class="dg-display dg-display--card" style="max-width:26ch">
        <a href="{{ $item['action_url'] }}" style="color:inherit">{{ $item['title'] }}</a>
    </h2>
    <p class="dg-body">{{ $item['summary'] }}</p>

    @if($item['image_url'] ?? null)
        <img src="{{ $item['image_url'] }}" alt="" style="width:100%;max-height:340px;object-fit:cover;border-radius:var(--dg-radius-control)">
    @endif

    @if($item['context'])
        <x-dg.note>{{ $item['context'] }}</x-dg.note>
    @endif
    <x-dg.feed.relevance :item="$item" />

    <x-dg.actions>
        @if(! $item['can_decide'])
            <x-dg.btn variant="need" :href="$item['contact_url']" method="POST">Je peux aider</x-dg.btn>
        @endif
        <x-dg.btn variant="learn" disabled title="L’intention « je veux apprendre » n’est pas encore distinguée par le besoin métier.">Je veux apprendre</x-dg.btn>
        <x-dg.btn variant="quiet" :href="$item['comment_url']">Commenter</x-dg.btn>
        <x-dg.btn variant="quiet" :href="$item['share_url']">Partager avec contexte</x-dg.btn>
    </x-dg.actions>
</article>
