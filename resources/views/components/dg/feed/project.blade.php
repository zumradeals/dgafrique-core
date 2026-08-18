{{--
    Carte Projet du Fil. Contrat manquant documenté (voir docs/design/DIFFERENCES.md) :
    « Participer » et « Contacter le porteur » n'ont pas de comportement métier pour un membre
    qui ne gère pas déjà le projet — seule la conversation de gestion (canDecide) existe (CAP-020).
--}}
@props(['item'])
<article class="dg-card dg-card--night" style="display:flex;flex-direction:column;gap:14px">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <x-dg.badge tone="project">{{ $item['event_label'] }}</x-dg.badge>
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

    @if($item['maturity'])
        <x-dg.maturity :stages="array_slice(\App\Application\Projects\ProjectMaturityService::STAGES, 0, 6, true)" :current="$item['maturity']" />
    @endif

    <x-dg.actions>
        <x-dg.btn variant="project" :href="$item['action_url']">Voir le projet</x-dg.btn>
        <x-dg.btn variant="learn" disabled title="Aucune action de participation n’existe encore pour un membre qui ne gère pas ce projet.">Participer</x-dg.btn>
        <x-dg.btn variant="quiet" :href="$item['comment_url']">Commenter</x-dg.btn>
        <x-dg.btn variant="quiet" :href="$item['share_url']">Partager avec contexte</x-dg.btn>
        <x-dg.btn variant="quiet" disabled title="Le contact direct du porteur n’est ouvert qu’aux personnes qui gèrent déjà ce projet.">Contacter le porteur</x-dg.btn>
    </x-dg.actions>
</article>
