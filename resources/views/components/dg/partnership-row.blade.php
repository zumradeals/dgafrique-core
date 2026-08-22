{{--
    Ligne pour les listes de Partenariats (Organisation/Besoin/Projet/ZUMRA) — UIUX-005. Explique
    humainement qui apporte quoi, dans quel contexte, et dans quel état — jamais un centre produit
    autonome. Les actions ne s'affichent que si `canManageAsProvider`/`canManageAsContextAuthority`
    (calculées par PresentsPartnerships à partir de PartnershipService, jamais recalculées ici).
--}}
@props(['row'])
@php($partnership = $row['partnership'])
<div class="dg-card" style="padding:16px 18px">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
        <div style="min-width:0">
            <div style="font-size:14px;font-weight:600;color:var(--dg-ink)">
                @if($row['providerUrl'])
                    <a href="{{ $row['providerUrl'] }}" style="color:var(--dg-copper)">{{ $row['providerLabel'] }}</a>
                @else
                    {{ $row['providerLabel'] }}
                @endif
                apporte
            </div>
            <p class="dg-body" style="margin-top:4px">{{ $partnership->capability_label }}</p>
            @if($row['contextLabel'])
                <div class="dg-meta" style="margin-top:6px">
                    @if($row['contextUrl'])
                        <a href="{{ $row['contextUrl'] }}" style="color:inherit">{{ $row['contextLabel'] }}</a>
                    @else
                        {{ $row['contextLabel'] }}
                    @endif
                </div>
            @endif
            @if($partnership->description)
                <p class="dg-body" style="margin-top:8px">{{ $partnership->description }}</p>
            @endif
        </div>
        <x-dg.badge :tone="$row['statusTone']" style="flex:none">{{ $row['statusLabel'] }}</x-dg.badge>
    </div>

    @if($row['canManageAsContextAuthority'] && $partnership->status === \App\Models\Partnership::STATUS_PROPOSED)
        <form method="POST" action="{{ route('partnerships.activate', $partnership) }}" style="margin-top:12px">
            @csrf
            <button type="submit" class="dg-btn dg-btn--primary">Activer cette collaboration</button>
        </form>
    @endif

    @if($row['canManageAsContextAuthority'] && in_array($partnership->status, [\App\Models\Partnership::STATUS_ACTIVE, \App\Models\Partnership::STATUS_PAUSED], true))
        <details style="margin-top:12px">
            <summary style="cursor:pointer;font-size:13px;font-weight:600;color:var(--dg-muted)">Mettre fin à cette collaboration</summary>
            <form method="POST" action="{{ route('partnerships.end', $partnership) }}" style="margin-top:10px;display:flex;flex-direction:column;gap:8px" onsubmit="return confirm('Mettre fin à cette collaboration ?');">
                @csrf
                <textarea name="reason" class="dg-textarea" rows="2" placeholder="Raison (facultatif)" maxlength="1000"></textarea>
                <button type="submit" class="dg-btn dg-btn--quiet" style="align-self:flex-start">Mettre fin</button>
            </form>
        </details>
    @endif

    @if($row['canManageAsProvider'])
        <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap">
            @if($partnership->status === \App\Models\Partnership::STATUS_ACTIVE)
                <form method="POST" action="{{ route('partnerships.pause', $partnership) }}">
                    @csrf
                    <button type="submit" class="dg-btn dg-btn--quiet">Mettre en pause</button>
                </form>
            @endif
            @if($partnership->status === \App\Models\Partnership::STATUS_PAUSED)
                <form method="POST" action="{{ route('partnerships.resume', $partnership) }}">
                    @csrf
                    <button type="submit" class="dg-btn dg-btn--quiet">Reprendre</button>
                </form>
            @endif
            @if(in_array($partnership->status, [\App\Models\Partnership::STATUS_PROPOSED, \App\Models\Partnership::STATUS_ACTIVE, \App\Models\Partnership::STATUS_PAUSED], true))
                <form method="POST" action="{{ route('partnerships.withdraw', $partnership) }}" onsubmit="return confirm('Retirer votre capacité de cette collaboration ?');">
                    @csrf
                    <button type="submit" class="dg-btn dg-btn--quiet">Retirer notre capacité</button>
                </form>
            @endif
        </div>
    @endif
</div>
