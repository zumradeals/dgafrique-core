@props(['tone' => null])
<span {{ $attributes->merge(['class' => trim('dg-label'.($tone ? ' dg-label--'.$tone : ''))]) }}>{{ $slot }}</span>
