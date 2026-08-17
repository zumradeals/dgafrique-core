{{-- Ligne compacte pour les listes de Preuves (Mon Carnet de preuves). --}}
@props(['proof'])
<a href="{{ route('proofs.show', $proof) }}" style="display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 4px;color:inherit;border-bottom:1px solid var(--dg-line)">
    <div style="min-width:0">
        <div style="font-size:14px;font-weight:600;color:var(--dg-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $proof->title }}</div>
        <div class="dg-meta">{{ \Illuminate\Support\Str::limit($proof->description, 90) }}</div>
    </div>
    <x-dg.badge :tone="\App\Models\Proof::STATUS_BADGE_TONES[$proof->status] ?? 'neutral'" style="flex:none">{{ \App\Models\Proof::STATUS_LABELS[$proof->status] ?? $proof->status }}</x-dg.badge>
</a>
