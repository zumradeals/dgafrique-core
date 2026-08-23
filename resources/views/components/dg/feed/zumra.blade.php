{{--
    Carte ZUMRA du Fil, sur surface d'ancrage. Contrat manquant documenté
    (voir docs/design/DIFFERENCES.md) : CAP-022 (partage avec contexte) ne couvre pas encore les
    ZUMRA, seulement les besoins et projets.
--}}
@props(['item'])
<article class="dg-deep" style="border-radius:22px;padding:28px 30px">
    <div style="display:flex;flex-direction:column;gap:14px">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <x-dg.badge tone="on-deep">{{ $item['event_label'] }}</x-dg.badge>
            <span style="font-size:13px;color:var(--dg-on-deep-muted)">{{ $item['occurred_at']->locale('fr')->diffForHumans() }}</span>
        </div>

        <h2 class="dg-display dg-display--card" style="max-width:26ch">
            <a href="{{ $item['action_url'] }}" style="color:inherit">{{ $item['title'] }}</a>
        </h2>
        <p style="margin:0;font-size:15px;line-height:1.7;color:var(--dg-on-deep-text);max-width:66ch">{{ $item['summary'] }}</p>

        @if($item['relevance_reason'] ?? null)
            <p style="margin:0;font-size:13px;color:var(--dg-on-deep-muted)">{{ $item['relevance_reason'] }}</p>
        @endif

        @if($item['seats'])
            <div class="flex flex-wrap gap-2">
                @foreach($item['seats'] as $seat)
                    @if($seat['filled'])
                        <span style="padding:7px 13px;border-radius:999px;background:rgba(239,230,214,.10);font-size:13px;color:#EFE6D6">{{ $seat['label'] }} ✓</span>
                    @else
                        <span style="padding:7px 13px;border-radius:999px;border:1px dashed rgba(217,160,43,.5);font-size:13px;color:var(--dg-saffron)">{{ $seat['label'] }} — vacant</span>
                    @endif
                @endforeach
            </div>
        @endif

        <x-dg.actions on-deep>
            <x-dg.btn variant="saffron" :href="$item['action_url']">Découvrir cette ZUMRA</x-dg.btn>
            @if($item['is_active_member'])
                <x-dg.btn variant="on-deep" :href="$item['contact_url']" method="POST">Contacter un responsable</x-dg.btn>
            @elseif($item['is_pending'])
                <x-dg.btn variant="on-deep" disabled title="Votre demande d’adhésion est déjà en cours d’examen par les responsables.">Demande en cours</x-dg.btn>
            @else
                <x-dg.btn variant="on-deep" :href="$item['request_url']" method="POST">Demander à rejoindre</x-dg.btn>
            @endif
            <x-dg.btn variant="on-deep" disabled title="Le partage avec contexte ne couvre pas encore les ZUMRA, seulement les besoins et projets.">Partager avec contexte</x-dg.btn>
        </x-dg.actions>
    </div>
</article>
