{{--
    Glyphe décoratif pour une famille d'activité ZUMRA (sidebar « Explorer par activité »,
    badges de carte). Purement visuel — voir App\Support\ZumraDomainPresentation.
--}}
@props(['domain' => null])
@php($key = \App\Support\ZumraDomainPresentation::key($domain))
<span {{ $attributes->merge(['class' => 'zw-domain-icon zw-domain-icon--'.$key, 'aria-hidden' => 'true']) }}>
    @switch($key)
        @case('numerique')
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="5" width="16" height="11" rx="2"/><path d="M9 20h6M12 16v4"/></svg>
            @break
        @case('artisanat')
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l7-7 3 3-7 7H3v-3Z"/><path d="M13 10l4-4 3 3-4 4"/></svg>
            @break
        @case('education')
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6.5C6 5 9 4.5 12 6c3-1.5 6-1 8 .5v11c-2-1.5-5-2-8-.5-3-1.5-6-1-8 .5v-11Z"/><path d="M12 6v11.5"/></svg>
            @break
        @case('agriculture')
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20V9"/><path d="M12 12c-4 0-6-2-6-6 4 0 6 2 6 6Z"/><path d="M12 9c4 0 6-2.5 6-6.5-4 0-6 2.5-6 6.5Z"/></svg>
            @break
        @case('sante')
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6c2-3 7-2 7 2.2 0 4-4.5 7-7 9.3-2.5-2.3-7-5.3-7-9.3C5 4 10 3 12 6Z"/></svg>
            @break
        @case('social')
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="9" r="3"/><circle cx="16" cy="9" r="3"/><path d="M3 19c.6-3 2.6-4.5 5-4.5s4.4 1.5 5 4.5"/><path d="M11 19c.6-3 2.6-4.5 5-4.5s4.4 1.5 5 4.5"/></svg>
            @break
        @case('culture')
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3c-4.5 0-7 2.4-7 6.3 0 3 1.6 5 4 6.2.5.3.4 1.5-.6 1.5H7"/><circle cx="9.3" cy="8.5" r="1"/><circle cx="14.7" cy="8.5" r="1"/><circle cx="12" cy="12" r="1"/></svg>
            @break
        @default
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 5.8H20l-4.9 3.6 1.9 5.8-4.9-3.6-4.9 3.6 1.9-5.8L4 8.8h6.1L12 3Z"/></svg>
    @endswitch
</span>
