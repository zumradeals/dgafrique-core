{{-- Ligne compacte pour les listes de Missions (Mes Missions, sous-Missions). --}}
@props(['mission'])
<a href="{{ route('missions.show', $mission) }}" style="display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 4px;color:inherit;border-bottom:1px solid var(--dg-line)">
    <div style="min-width:0">
        <div style="font-size:14px;font-weight:600;color:var(--dg-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $mission->title }}</div>
        <div class="dg-meta">{{ \Illuminate\Support\Str::limit($mission->description, 90) }}</div>
    </div>
    <x-dg.badge :tone="\App\Models\Mission::STATUS_BADGE_TONES[$mission->status] ?? 'neutral'" style="flex:none">{{ \App\Models\Mission::STATUS_LABELS[$mission->status] ?? $mission->status }}</x-dg.badge>
</a>
