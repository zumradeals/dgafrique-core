@props([
    'id',
    'name' => null,
    'type' => 'text',
    'invalid' => false,
])

<input
    id="{{ $id }}"
    name="{{ $name ?? $id }}"
    type="{{ $type }}"
    @if ($invalid) aria-invalid="true" @endif
    {{ $attributes->class('dg-input') }}
>
