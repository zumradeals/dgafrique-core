@props([
    'label',
    'for',
    'hint' => null,
    'error' => null,
    'required' => false,
])

@php
    $hintId = $hint ? $for.'-hint' : null;
    $errorId = $error ? $for.'-error' : null;
@endphp

<div {{ $attributes->class('dg-field') }}>
    <label class="dg-field__label" for="{{ $for }}">
        {{ $label }}
        @if ($required)<span aria-hidden="true">*</span><span class="sr-only"> (obligatoire)</span>@endif
    </label>
    {{ $slot }}
    @if ($hint)<p class="dg-field__hint" id="{{ $hintId }}">{{ $hint }}</p>@endif
    @if ($error)<p class="dg-field__error" id="{{ $errorId }}">{{ $error }}</p>@endif
</div>
