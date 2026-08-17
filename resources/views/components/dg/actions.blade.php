@props(['onDeep' => false, 'flush' => false])
@php
    $class = trim('dg-actions'.($onDeep ? ' dg-actions--on-deep' : '').($flush ? ' dg-actions--flush' : ''));
@endphp
<div {{ $attributes->merge(['class' => $class]) }}>
    {{ $slot }}
</div>
