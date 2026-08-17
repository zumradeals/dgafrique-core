{{-- Traitement des personnes : « Membre DG Afrique » pour un profil non découvrable, « Vous » pour soi. --}}
@props([
    'initials' => null,
    'name' => null,
    'role' => null,
    'tone' => null,
    'size' => null,
    'anonymous' => false,
])
@php
    $displayName = $anonymous ? 'Membre DG Afrique' : $name;
    $displayInitials = $anonymous ? 'M' : ($initials ?: mb_strtoupper(mb_substr($displayName ?? '?', 0, 1)));
@endphp
<div {{ $attributes->merge(['class' => 'dg-person']) }}>
    <x-dg.avatar :initials="$displayInitials" :tone="$tone" :size="$size" :anonymous="$anonymous" />
    <div>
        <div class="dg-person__name">{{ $displayName }}</div>
        @if($role)
            <div class="dg-person__role">{{ $role }}</div>
        @endif
    </div>
</div>
