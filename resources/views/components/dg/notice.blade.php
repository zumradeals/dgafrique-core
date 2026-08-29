@props([
    'type' => 'info',
    'title',
])

@php
    $icons = ['info' => 'info', 'success' => 'success', 'warning' => 'warning', 'danger' => 'warning'];
    $role = $type === 'danger' ? 'alert' : 'status';
@endphp

<div role="{{ $role }}" {{ $attributes->class('dg-notice dg-notice--'.$type) }}>
    <x-dg.icon :name="$icons[$type] ?? 'info'" size="22" />
    <div>
        <p class="dg-notice__title">{{ $title }}</p>
        <div class="dg-notice__message">{{ $slot }}</div>
    </div>
</div>
