{{--
    Navigation mobile canonique : Fil · Personnes · Agir · ZUMRA · Moi.
    « Agir » ouvre des gestes réels ; ce n'est jamais un bouton social générique.
--}}
@props(['current' => null])
<nav {{ $attributes->merge(['class' => 'dg-tabbar']) }} aria-label="Navigation mobile">
    <a href="{{ route('activity.index') }}" @if($current === 'fil') aria-current="page" @endif>
        <span class="dg-tabbar__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none"><path d="M4 5.5h16M4 12h16M4 18.5h10" stroke-width="1.8" stroke-linecap="round"/></svg>
        </span>
        Fil
    </a>

    <a href="{{ route('people.index') }}" @if($current === 'personnes') aria-current="page" @endif>
        <span class="dg-tabbar__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none"><path d="M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3.5 19c.5-3 2.4-4.7 5.5-4.7s5 1.7 5.5 4.7M16.5 10.5a2.5 2.5 0 1 0 0-5M16 14.5c2.6.1 4.1 1.5 4.5 4" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
        Personnes
    </a>

    <button type="button" data-dg-agir-open aria-haspopup="dialog" aria-controls="dg-agir-sheet">
        <span class="dg-tabbar__act" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke-width="2.2" stroke-linecap="round"/></svg>
        </span>
        <span class="dg-tabbar__act-label">Agir</span>
    </button>

    <a href="{{ route('zumra.index') }}" @if($current === 'zumra') aria-current="page" @endif>
        <span class="dg-tabbar__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="7.5" stroke-width="1.7"/><path d="M8.5 8.5h7l-7 7h7" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
        ZUMRA
    </a>

    <a href="{{ route('member.space') }}" @if($current === 'espace') aria-current="page" @endif>
        <span class="dg-tabbar__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.2" stroke-width="1.7"/><path d="M5.5 19c.6-3.5 2.8-5.3 6.5-5.3s5.9 1.8 6.5 5.3" stroke-width="1.7" stroke-linecap="round"/></svg>
        </span>
        Moi
    </a>
</nav>
