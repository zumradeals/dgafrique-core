{{--
    Ligne pour les listes de Transmissions (Mes Transmissions). Titre et badge partagent une
    ligne qui peut se replier (le badge de statut Transmission peut être long, ex. « Terminée —
    validée par le contexte ») ; l'objectif d'apprentissage occupe toujours sa propre ligne
    pleine largeur, jamais compressé à quelques caractères par le badge.
--}}
@props(['transmission'])
<a href="{{ route('transmissions.show', $transmission) }}" style="display:block;padding:12px 4px;color:inherit;border-bottom:1px solid var(--dg-line)">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
        <div style="font-size:14px;font-weight:600;color:var(--dg-ink)">{{ $transmission->capability_label }}</div>
        <x-dg.badge :tone="\App\Models\Transmission::STATUS_BADGE_TONES[$transmission->status] ?? 'neutral'" style="flex:none">{{ \App\Models\Transmission::STATUS_LABELS[$transmission->status] ?? $transmission->status }}</x-dg.badge>
    </div>
    <div class="dg-meta" style="margin-top:4px">{{ \Illuminate\Support\Str::limit($transmission->learning_objective, 90) }}</div>
</a>
