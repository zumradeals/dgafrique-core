@props(['tone' => 'neutral'])
<span {{ $attributes->merge(['class' => 'dg-badge dg-badge--'.$tone]) }}>{{ $slot }}</span>
