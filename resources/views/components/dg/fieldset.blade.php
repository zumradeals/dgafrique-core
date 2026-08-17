{{-- Section de formulaire ou de décision groupée. Utiliser un <legend> réel dans le slot. --}}
@props(['hidden' => false])
<fieldset {{ $attributes->merge(['class' => 'dg-fieldset']) }} @if($hidden) hidden @endif>
    {{ $slot }}
</fieldset>
