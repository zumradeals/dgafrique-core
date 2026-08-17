{{-- Siège / responsabilité ZUMRA — jamais complété avec un profil fictif, jamais nommé par matching. --}}
@props(['label', 'filled' => false, 'holder' => null])
<div class="dg-seat" @if(! $filled) data-vacant="true" @endif>
    <span class="dg-seat__mark">{{ $filled ? '✓' : '—' }}</span>
    <div>
        <strong style="display:block;font-size:14px;color:var(--dg-forest)">{{ $label }}</strong>
        <span class="dg-meta">{{ $filled ? ($holder ?? 'Responsabilité acceptée') : 'Siège vacant' }}</span>
    </div>
</div>
