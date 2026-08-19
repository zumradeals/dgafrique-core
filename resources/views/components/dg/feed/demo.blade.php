{{--
    Carte d'exemple du Fil (règle DEMO-FIRST, REAL-DATA-TAKES-OVER — voir
    docs/design/DESIGN-INVARIANTS.md §17 et resources/design-reference/fil-demo.json).
    N'apparaît que lorsque le filtre actif n'a aucune donnée réelle. Aucune action n'est câblée :
    la carte ne représente aucun objet métier existant.
--}}
@props(['card'])
@php
    $reason = 'Objet de démonstration — aucune action réelle n’est rattachée.';
    $badgeLabel = $card['badge_label'].' · Exemple';
@endphp

@if($card['kind'] === 'ZUMRA')
    <article class="dg-deep dg-card--demo" style="border-radius:22px;padding:28px 30px">
        <div style="display:flex;flex-direction:column;gap:14px">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <x-dg.badge tone="on-deep" class="dg-badge--demo">{{ $badgeLabel }}</x-dg.badge>
                <span style="font-size:13px;color:var(--dg-on-deep-muted)">{{ $card['time_label'] }}</span>
            </div>

            <div class="flex items-center gap-2.5">
                <x-dg.avatar :initials="mb_strtoupper(mb_substr($card['author'], 0, 2))" size="sm" tone="saffron" />
                <div>
                    <strong style="display:block;font-size:14px;color:var(--dg-on-deep-title)">{{ $card['author'] }}</strong>
                    <span style="font-size:12px;color:var(--dg-on-deep-muted)">{{ $card['location'] }}</span>
                </div>
            </div>

            <h2 class="dg-display dg-display--card" style="max-width:26ch">{{ $card['title'] }}</h2>
            <p style="margin:0;font-size:15px;line-height:1.7;color:var(--dg-on-deep-text);max-width:66ch">{{ $card['summary'] }}</p>

            <div class="flex flex-wrap gap-2">
                @foreach($card['tags'] as $tag)
                    <span style="padding:6px 12px;border-radius:999px;background:rgba(239,230,214,.10);font-size:12px;color:#EFE6D6">{{ $tag }}</span>
                @endforeach
            </div>
            <span style="font-size:13px;color:var(--dg-on-deep-muted)">{{ $card['meta'] }}</span>

            <x-dg.actions on-deep>
                <x-dg.btn variant="saffron" disabled :title="$reason">Découvrir cette ZUMRA</x-dg.btn>
                <x-dg.btn variant="on-deep" disabled :title="$reason">Demander à rejoindre</x-dg.btn>
                <x-dg.btn variant="on-deep" disabled :title="$reason">Partager avec contexte</x-dg.btn>
            </x-dg.actions>
        </div>
    </article>
@else
    @php
        $tone = $card['kind'] === 'NEEDS' ? 'copper' : 'night';
        $badgeTone = $card['kind'] === 'NEEDS' ? 'need' : 'project';
        $primaryVariant = $card['kind'] === 'NEEDS' ? 'need' : 'project';
        $primaryLabel = $card['kind'] === 'NEEDS' ? 'Je peux aider' : 'Voir le projet';
    @endphp
    <article class="dg-card dg-card--{{ $tone }} dg-card--demo" style="display:flex;flex-direction:column;gap:14px">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <x-dg.badge :tone="$badgeTone" class="dg-badge--demo">{{ $badgeLabel }}</x-dg.badge>
            <span class="dg-meta" style="color:var(--dg-faint)">{{ $card['time_label'] }}</span>
        </div>

        <div class="flex items-center gap-2.5">
            <x-dg.avatar :initials="mb_strtoupper(mb_substr($card['author'], 0, 2))" size="sm" :tone="$tone" />
            <div>
                <strong style="display:block;font-size:14px;color:var(--dg-ink)">{{ $card['author'] }}</strong>
                <span class="dg-meta">{{ $card['location'] }}</span>
            </div>
        </div>

        <h2 class="dg-display dg-display--card" style="max-width:26ch">{{ $card['title'] }}</h2>
        <p class="dg-body">{{ $card['summary'] }}</p>

        <div class="flex flex-wrap gap-2">
            @foreach($card['tags'] as $tag)
                <span style="padding:6px 12px;border-radius:999px;background:var(--dg-sand);font-size:12px;color:var(--dg-text)">{{ $tag }}</span>
            @endforeach
        </div>
        <span class="dg-meta">{{ $card['meta'] }}</span>

        <x-dg.actions>
            <x-dg.btn :variant="$primaryVariant" disabled :title="$reason">{{ $primaryLabel }}</x-dg.btn>
            @if($card['kind'] === 'NEEDS')
                <x-dg.btn variant="learn" disabled :title="$reason">Je veux apprendre</x-dg.btn>
            @endif
            <x-dg.btn variant="quiet" disabled :title="$reason">Commenter</x-dg.btn>
            <x-dg.btn variant="quiet" disabled :title="$reason">Partager avec contexte</x-dg.btn>
        </x-dg.actions>
    </article>
@endif
