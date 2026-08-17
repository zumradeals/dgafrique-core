{{-- Repères de maturité (CAP-017) : jamais une note, jamais un pourcentage. --}}
@props(['stages', 'current'])
@php($currentIndex = array_search($current, array_keys($stages), true))
<div class="dg-maturity">
    @foreach(array_keys($stages) as $index => $code)
        <i @if($currentIndex !== false && $index < $currentIndex) data-state="done"
           @elseif($index === $currentIndex) data-state="current" @endif></i>
    @endforeach
</div>
