@props(['name', 'size' => 24])

<svg
    {{ $attributes->merge(['aria-hidden' => 'true', 'focusable' => 'false']) }}
    width="{{ $size }}"
    height="{{ $size }}"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.8"
    stroke-linecap="round"
    stroke-linejoin="round"
>
    @switch($name)
        @case('activity')
            <path d="M4 19V9m5 10V5m5 14v-7m5 7V3" />
            @break
        @case('discover')
            <circle cx="11" cy="11" r="6.5" />
            <path d="m16 16 4 4M9 9l4-2-2 4-4 2 2-4Z" />
            @break
        @case('act')
            <path d="M12 3v18M3 12h18" />
            @break
        @case('zumra')
            <circle cx="8" cy="8" r="3" />
            <circle cx="17" cy="9" r="2.5" />
            <path d="M2.5 20c.5-4 2.5-6 5.5-6s5 2 5.5 6M14 15c3.8-.8 6.4 1 7 5" />
            @break
        @case('space')
            <circle cx="12" cy="8" r="4" />
            <path d="M4.5 21c.7-4.5 3.2-7 7.5-7s6.8 2.5 7.5 7" />
            @break
        @case('people')
            <circle cx="9" cy="8" r="3" />
            <path d="M3.5 20c.5-4 2.3-6 5.5-6s5 2 5.5 6M15 5.5a3 3 0 0 1 0 5.5M16 14c2.8.3 4.3 2.3 4.5 6" />
            @break
        @case('need')
            <path d="M12 21s8-4.6 8-11a4.7 4.7 0 0 0-8-3.4A4.7 4.7 0 0 0 4 10c0 6.4 8 11 8 11Z" />
            @break
        @case('project')
            <path d="M4 7.5h16v12H4zM8 7.5V4h8v3.5M4 12h16M10 12v2h4v-2" />
            @break
        @case('transmission')
            <path d="M4 17V7l8-4 8 4v10l-8 4-8-4Z" />
            <path d="m8 10 4-2 4 2-4 2-4-2Zm4 2v5" />
            @break
        @case('proof')
            <path d="M6 3h9l4 4v14H6z" />
            <path d="M15 3v5h4M9 13l2 2 4-4" />
            @break
        @case('arrow')
            <path d="m9 18 6-6-6-6" />
            @break
        @case('close')
            <path d="m6 6 12 12M18 6 6 18" />
            @break
        @case('info')
            <circle cx="12" cy="12" r="9" />
            <path d="M12 11v6M12 7.5h.01" />
            @break
        @case('success')
            <circle cx="12" cy="12" r="9" />
            <path d="m8 12 2.5 2.5L16 9" />
            @break
        @case('warning')
            <path d="M12 3 2.8 20h18.4L12 3Z" />
            <path d="M12 9v5M12 17.5h.01" />
            @break
        @case('empty')
            <path d="M4 6h16v13H4zM8 3h8M8 11h8M8 15h5" />
            @break
        @case('offline')
            <path d="M3 8.5a14 14 0 0 1 18 0M6.5 12a9 9 0 0 1 11 0M10 15.5a4 4 0 0 1 4 0M3 3l18 18" />
            @break
        @case('lock')
            <rect x="5" y="10" width="14" height="11" rx="2" />
            <path d="M8 10V7a4 4 0 0 1 8 0v3" />
            @break
        @default
            <circle cx="12" cy="12" r="9" />
    @endswitch
</svg>
