{{-- Chemin de repères complet (maturité projet, etc.) — jamais une note, jamais un pourcentage. --}}
@props(['stages', 'current'])
@php($currentIndex = array_search($current, array_keys($stages), true))
<div class="dg-stagewalk">
    @foreach($stages as $code => $stage)
        @php($index = array_search($code, array_keys($stages), true))
        <div class="dg-stagewalk__item" data-done="{{ $currentIndex !== false && $index < $currentIndex ? 'true' : 'false' }}" data-current="{{ $code === $current ? 'true' : 'false' }}">
            <span class="dg-stagewalk__mark">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
            <div>
                <strong style="display:block;font-size:14px;color:var(--dg-forest)">{{ $stage['label'] }}</strong>
                <span class="dg-meta" style="line-height:1.5">{{ $code === $current ? 'Repère actuel' : $stage['description'] }}</span>
            </div>
        </div>
    @endforeach
</div>
