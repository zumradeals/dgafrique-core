{{-- Ligne compacte pour les listes de Transmissions (Mes Transmissions). --}}
@props(['transmission'])
<a href="{{ route('transmissions.show', $transmission) }}" style="display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 4px;color:inherit;border-bottom:1px solid var(--dg-line)">
    <div style="min-width:0">
        <div style="font-size:14px;font-weight:600;color:var(--dg-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $transmission->capability_label }}</div>
        <div class="dg-meta">{{ \Illuminate\Support\Str::limit($transmission->learning_objective, 90) }}</div>
    </div>
    <x-dg.badge :tone="\App\Models\Transmission::STATUS_BADGE_TONES[$transmission->status] ?? 'neutral'" style="flex:none">{{ \App\Models\Transmission::STATUS_LABELS[$transmission->status] ?? $transmission->status }}</x-dg.badge>
</a>
