@props(['tight' => false])
<div {{ $attributes->merge(['class' => 'dg-card'.($tight ? ' dg-card--tight' : '')]) }}>
    {{ $slot }}
</div>
