{{--
    Ligne pour les listes de Preuves (Mon Carnet de preuves). Titre et badge partagent une
    ligne qui peut se replier (le badge de statut Preuve peut être long, ex. « Terminée —
    validée par le contexte ») ; la description occupe toujours sa propre ligne pleine largeur,
    jamais compressée à quelques caractères par le badge.
--}}
@props(['proof'])
<a href="{{ route('proofs.show', $proof) }}" style="display:block;padding:12px 4px;color:inherit;border-bottom:1px solid var(--dg-line)">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
        <div style="font-size:14px;font-weight:600;color:var(--dg-ink)">{{ $proof->title }}</div>
        <x-dg.badge :tone="\App\Models\Proof::STATUS_BADGE_TONES[$proof->status] ?? 'neutral'" style="flex:none">{{ \App\Models\Proof::STATUS_LABELS[$proof->status] ?? $proof->status }}</x-dg.badge>
    </div>
    <div class="dg-meta" style="margin-top:4px">{{ \Illuminate\Support\Str::limit($proof->description, 90) }}</div>
</a>
