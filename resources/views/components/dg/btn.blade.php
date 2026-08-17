{{--
    Bouton DG Afrique. Une action ne s'affiche jamais avec un faux comportement :
    - $href sans $method → lien GET réel ;
    - $href avec $method → formulaire réel (CSRF inclus) ;
    - $disabled → action visuellement conservée mais non câblée, avec la raison en $title
      (contrat métier manquant, documenté dans docs/design/DIFFERENCES.md).
--}}
@props([
    'variant' => 'quiet',
    'href' => null,
    'method' => null,
    'disabled' => false,
    'title' => null,
    'type' => 'button',
])
@php($class = 'dg-btn dg-btn--'.$variant)
@if($disabled)
    <span {{ $attributes->merge(['class' => $class]) }} aria-disabled="true" role="button" @if($title) title="{{ $title }}" @endif>{{ $slot }}</span>
@elseif($method && strtoupper($method) !== 'GET')
    <form method="POST" action="{{ $href }}" class="contents">
        @csrf
        @unless(strtoupper($method) === 'POST')
            @method($method)
        @endunless
        <button type="submit" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</button>
    </form>
@elseif($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</button>
@endif
