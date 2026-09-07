@props(['id', 'name' => null, 'invalid' => false])

<textarea
    id="{{ $id }}"
    name="{{ $name ?? $id }}"
    @if ($invalid) aria-invalid="true" @endif
    {{ $attributes->class('dg-input') }}
>{{ $slot }}</textarea>
