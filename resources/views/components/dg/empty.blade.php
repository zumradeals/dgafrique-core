{{-- Bloc d'état vide secondaire (cadre pointillé). Toujours précédé d'un accueil x-dg.deep, jamais isolé. --}}
@props(['title' => null])
<div {{ $attributes->merge(['class' => 'dg-empty']) }}>
    @if($title)
        <strong>{{ $title }}</strong>
    @endif
    {{ $slot }}
</div>
