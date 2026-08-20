{{--
    Icônes ligne minimalistes, dessinées à la main (pas de dépendance d'icônes). Usage strictement
    décoratif à côté d'un libellé texte — aria-hidden dans tous les cas.
--}}
@props(['name', 'size' => '20'])
@php($stroke = 'stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" fill="none"')
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" aria-hidden="true" {{ $attributes }}>
    @switch($name)
        @case('search')
            <circle cx="10.5" cy="10.5" r="6.5" {!! $stroke !!} />
            <path d="M20 20l-4.8-4.8" {!! $stroke !!} />
            @break
        @case('bell')
            <path d="M12 3.5c-2.7 0-4.5 2.1-4.5 4.9v3.2c0 .9-.4 1.8-1 2.5l-.9 1c-.5.6-.1 1.4.6 1.4h11.6c.7 0 1.1-.9.6-1.4l-.9-1c-.6-.7-1-1.6-1-2.5V8.4c0-2.8-1.8-4.9-4.5-4.9Z" {!! $stroke !!} />
            <path d="M10 19.5a2 2 0 0 0 4 0" {!! $stroke !!} />
            @break
        @case('menu')
            <path d="M4 6.5h16M4 12h16M4 17.5h16" {!! $stroke !!} />
            @break
        @case('feed')
            <rect x="4" y="4" width="16" height="16" rx="3" {!! $stroke !!} />
            <path d="M8 9h8M8 12.5h8M8 16h5" {!! $stroke !!} />
            @break
        @case('heart')
            <path d="M12 20s-7-4.4-9.3-8.9C1.3 8 2.6 4.8 5.8 4.1c2-.4 3.7.5 4.9 2.1a1.6 1.6 0 0 0 2.6 0c1.2-1.6 2.9-2.5 4.9-2.1 3.2.7 4.5 3.9 3.1 7C19 15.6 12 20 12 20Z" {!! $stroke !!} />
            @break
        @case('team')
            <circle cx="8.5" cy="8" r="2.8" {!! $stroke !!} />
            <circle cx="16" cy="9" r="2.3" {!! $stroke !!} />
            <path d="M3.5 19c.5-3 2.5-4.8 5-4.8s4.5 1.8 5 4.8" {!! $stroke !!} />
            <path d="M14.5 14.7c2.1.2 3.6 1.9 4 4.3" {!! $stroke !!} />
            @break
        @case('zumra')
            <circle cx="12" cy="7" r="2.6" {!! $stroke !!} />
            <circle cx="6.5" cy="16" r="2.2" {!! $stroke !!} />
            <circle cx="17.5" cy="16" r="2.2" {!! $stroke !!} />
            <path d="M9.8 8.7 7.9 14M14.2 8.7l1.9 5.3M8.6 16h6.8" {!! $stroke !!} />
            @break
        @case('people')
            <circle cx="9" cy="8" r="3" {!! $stroke !!} />
            <path d="M3.5 19.5c.6-3.6 2.8-5.7 5.5-5.7s4.9 2.1 5.5 5.7" {!! $stroke !!} />
            <path d="M15.5 6.2c1.6.4 2.7 1.8 2.7 3.5 0 1.5-.9 2.8-2.1 3.4" {!! $stroke !!} />
            <path d="M17 14.3c2 .6 3.4 2.4 3.8 5.2" {!! $stroke !!} />
            @break
        @case('handshake')
            <path d="M3 11.5l4-3.7 4 2.6 3-2.6 5 4.2" {!! $stroke !!} />
            <path d="M7 10l4.2 4.4a1.4 1.4 0 0 0 2-2l-3.4-3.5" {!! $stroke !!} />
            <path d="M14 12l1.8 1.8a1.3 1.3 0 0 0 1.9-1.8" {!! $stroke !!} />
            <path d="M3 11.5v4.3l2.3 2.1M21 11v4.5l-2.6 2" {!! $stroke !!} />
            @break
        @case('rocket')
            <path d="M12 3c2.8 1 5 4 5 8-1.6 1-3 1.6-5 1.6S8.6 12 7 11c0-4 2.2-7 5-8Z" {!! $stroke !!} />
            <circle cx="12" cy="9" r="1.6" {!! $stroke !!} />
            <path d="M9.3 14 7 21l3.2-1.8M14.7 14 17 21l-3.2-1.8" {!! $stroke !!} />
            @break
        @case('chart')
            <path d="M4 20V10M10 20V4M16 20v-7M21 20H3" {!! $stroke !!} />
            @break
        @case('facebook')
            <path d="M14 21v-7h2.3l.4-3H14V9c0-.9.2-1.5 1.6-1.5H17V4.9c-.3 0-1.2-.1-2.3-.1-2.3 0-3.7 1.4-3.7 3.9V11H8.5v3H11v7h3Z" {!! $stroke !!} />
            @break
        @case('linkedin')
            <rect x="3.5" y="3.5" width="17" height="17" rx="3" {!! $stroke !!} />
            <path d="M8 10.5v6M8 7.7v.1M12.3 16.5v-3.5c0-1.4.9-2.2 2-2.2s1.8.8 1.8 2.2v3.5" {!! $stroke !!} />
            @break
        @case('twitter')
            <path d="M20 6.4c-.6.3-1.3.5-2 .6a3.4 3.4 0 0 0 1.5-1.9c-.7.4-1.5.7-2.3.9a3.4 3.4 0 0 0-5.9 3.1A9.8 9.8 0 0 1 4.2 5.6a3.4 3.4 0 0 0 1.1 4.6c-.6 0-1.1-.2-1.6-.4v.1c0 1.7 1.2 3 2.7 3.4-.5.1-1 .1-1.5.1l-.4-.1c.4 1.4 1.7 2.4 3.2 2.4A6.9 6.9 0 0 1 3 17.1a9.7 9.7 0 0 0 5.3 1.6c6.4 0 9.9-5.3 9.9-9.9v-.5c.7-.5 1.3-1.1 1.8-1.9Z" {!! $stroke !!} />
            @break
        @case('youtube')
            <rect x="3" y="6" width="18" height="12" rx="3.5" {!! $stroke !!} />
            <path d="M10.5 9.5v5l4.5-2.5-4.5-2.5Z" {!! $stroke !!} />
            @break
        @case('instagram')
            <rect x="3.5" y="3.5" width="17" height="17" rx="5" {!! $stroke !!} />
            <circle cx="12" cy="12" r="3.6" {!! $stroke !!} />
            <circle cx="17" cy="7" r=".9" fill="currentColor" stroke="none" />
            @break
        @case('device')
            <rect x="3.5" y="5" width="17" height="11" rx="2" {!! $stroke !!} />
            <path d="M8.5 20h7M12 16v4" {!! $stroke !!} />
            <path d="M7.5 9h5M7.5 12h3.5" {!! $stroke !!} />
            @break
        @case('book')
            <path d="M12 6.3c-1.6-1.2-3.6-1.8-6-1.8v13c2.4 0 4.4.6 6 1.8 1.6-1.2 3.6-1.8 6-1.8v-13c-2.4 0-4.4.6-6 1.8Z" {!! $stroke !!} />
            <path d="M12 6.3v13" {!! $stroke !!} />
            @break
        @case('leaf')
            <path d="M6 19c-1.6-5.6.8-11 9.5-12.7C17 12.6 13.7 17.7 6 19Z" {!! $stroke !!} />
            <path d="M6 19c1-3.6 3-6.5 8-9.4" {!! $stroke !!} />
            @break
        @case('target')
            <circle cx="12" cy="12" r="8" {!! $stroke !!} />
            <circle cx="12" cy="12" r="4" {!! $stroke !!} />
            <circle cx="12" cy="12" r=".6" fill="currentColor" stroke="none" />
            @break
        @case('check-circle')
            <circle cx="12" cy="12" r="8" {!! $stroke !!} />
            <path d="M8.3 12.3 11 15l4.7-5.6" {!! $stroke !!} />
            @break
        @case('grid')
            <rect x="4" y="4" width="7" height="7" rx="1.5" {!! $stroke !!} />
            <rect x="13" y="4" width="7" height="7" rx="1.5" {!! $stroke !!} />
            <rect x="4" y="13" width="7" height="7" rx="1.5" {!! $stroke !!} />
            <rect x="13" y="13" width="7" height="7" rx="1.5" {!! $stroke !!} />
            @break
        @case('list')
            <path d="M8.5 6.5h11M8.5 12h11M8.5 17.5h11" {!! $stroke !!} />
            <path d="M4.5 6.5h.01M4.5 12h.01M4.5 17.5h.01" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" />
            @break
        @case('compass')
            <circle cx="12" cy="12" r="8.5" {!! $stroke !!} />
            <path d="M14.8 9.2 13 13l-3.8 1.8L11 11l3.8-1.8Z" {!! $stroke !!} />
            @break
        @case('spark')
            <path d="M12 4v4M12 16v4M4 12h4M16 12h4M6.3 6.3l2.8 2.8M14.9 14.9l2.8 2.8M17.7 6.3l-2.8 2.8M9.1 14.9l-2.8 2.8" {!! $stroke !!} />
            @break
        @case('star')
            <path d="M12 3.6 14.3 9l5.9.5-4.5 3.9 1.4 5.8L12 16.1l-5.1 3.1 1.4-5.8L3.8 9.5 9.7 9Z" {!! $stroke !!} stroke-linejoin="round" />
            @break
        @case('brain')
            <path d="M9.3 4.6c-1.9 0-3.4 1.5-3.4 3.3 0 .4.06.8.2 1.1-1.2.5-2 1.7-2 3 0 1.1.6 2.1 1.5 2.7-.2.4-.3.9-.3 1.4 0 1.8 1.5 3.3 3.3 3.3.3 0 .6 0 .9-.1.4 1 1.4 1.7 2.5 1.7V6.1c0-.8-.9-1.5-2.7-1.5Z" {!! $stroke !!} />
            <path d="M14.7 4.6c1.9 0 3.4 1.5 3.4 3.3 0 .4-.06.8-.2 1.1 1.2.5 2 1.7 2 3 0 1.1-.6 2.1-1.5 2.7.2.4.3.9.3 1.4 0 1.8-1.5 3.3-3.3 3.3-.3 0-.6 0-.9-.1-.4 1-1.4 1.7-2.5 1.7V6.1c0-.8.9-1.5 2.7-1.5Z" {!! $stroke !!} />
            <path d="M12 6.8v14M8.3 10.2c.8.3 1.6.3 2.4 0M13.3 10.2c.8.3 1.6.3 2.4 0M7.8 14.6c.8.3 1.7.3 2.5 0M13.7 14.6c.8.3 1.7.3 2.5 0" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" opacity=".7" />
            @break
        @case('paperclip')
            <path d="M8 12.5 14.8 5.7a3 3 0 0 1 4.2 4.2l-8.3 8.3a5 5 0 0 1-7-7l7.6-7.6" {!! $stroke !!} />
            @break
        @case('image')
            <rect x="3.5" y="4.5" width="17" height="15" rx="2.5" {!! $stroke !!} />
            <circle cx="9" cy="10" r="1.6" {!! $stroke !!} />
            <path d="M4 17.5 9 13l3 2.6 4-3.6 4 4" {!! $stroke !!} />
            @break
        @case('document')
            <path d="M7 3.5h7l4 4v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-16a1 1 0 0 1 1-1Z" {!! $stroke !!} />
            <path d="M14 3.5v4h4M9 13h6M9 16.5h6" {!! $stroke !!} />
            @break
        @case('mic')
            <rect x="9.3" y="3.5" width="5.4" height="10.5" rx="2.7" {!! $stroke !!} />
            <path d="M6 11.5a6 6 0 0 0 12 0M12 17.5v3M9.3 20.5h5.4" {!! $stroke !!} />
            @break
        @case('send')
            <path d="M4 12 20 4l-6 16-3-7-7-1Z" {!! $stroke !!} stroke-linejoin="round" />
            @break
        @case('settings')
            <circle cx="12" cy="12" r="3" {!! $stroke !!} />
            <path d="M12 3.5v2.2M12 18.3v2.2M20.5 12h-2.2M5.7 12H3.5M17.7 6.3l-1.6 1.6M7.9 16.1l-1.6 1.6M17.7 17.7l-1.6-1.6M7.9 7.9 6.3 6.3" {!! $stroke !!} />
            @break
        @case('chevron-down')
            <path d="M6 9.5 12 15l6-5.5" {!! $stroke !!} />
            @break
    @endswitch
</svg>
