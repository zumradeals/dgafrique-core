@props([
    'href' => null,
    'type' => 'button',
    'variant' => 'primary',
    'icon' => null,
    'disabled' => false,
])

@php
    $classes = 'dg-button dg-button--'.$variant;
@endphp

@if ($href)
    <a
        href="{{ $disabled ? '#' : $href }}"
        @if ($disabled) aria-disabled="true" tabindex="-1" @endif
        {{ $attributes->class($classes) }}
    >
        @if ($icon)<x-dg.icon :name="$icon" size="20" />@endif
        <span>{{ $slot }}</span>
    </a>
@else
    <button
        type="{{ $type }}"
        @disabled($disabled)
        {{ $attributes->class($classes) }}
    >
        @if ($icon)<x-dg.icon :name="$icon" size="20" />@endif
        <span>{{ $slot }}</span>
    </button>
@endif
