{{-- Avatar typographique : initiales sur aplat de couleur, jamais un cercle de réseau social. --}}
@props([
    'initials' => '?',
    'tone' => null, // copper|night|saffron
    'size' => null, // sm|md|lg
    'anonymous' => false,
])
@php
    $class = trim('dg-avatar'
        .($size ? ' dg-avatar--'.$size : '')
        .($anonymous ? ' dg-avatar--anonymous' : ($tone ? ' dg-avatar--'.$tone : '')));
@endphp
<span {{ $attributes->merge(['class' => $class]) }}>{{ $anonymous ? 'M' : $initials }}</span>
