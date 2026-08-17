{{--
    Navigation mobile : cinq onglets (Fil · Personnes · Agir · ZUMRA · Moi). Besoins et Projets ne
    disparaissent pas : ce sont les filtres du Fil (CAP-019). Le bouton central ouvre les gestes
    réellement disponibles, ce n'est pas un « + » de réseau social.
--}}
@props(['current' => null])
<nav {{ $attributes->merge(['class' => 'dg-tabbar']) }} aria-label="Navigation mobile">
    <a href="{{ route('activity.index') }}" @if($current === 'fil') aria-current="page" @endif>
        <span class="dg-tabbar__icon" aria-hidden="true"></span>Fil
    </a>
    <a href="{{ route('people.index') }}" @if($current === 'personnes') aria-current="page" @endif>
        <span class="dg-tabbar__icon" aria-hidden="true"></span>Personnes
    </a>
    <button type="button" data-dg-agir-open aria-haspopup="dialog" aria-controls="dg-agir-sheet">
        <span class="dg-tabbar__act">Agir</span>
    </button>
    <a href="{{ route('zumra.index') }}" @if($current === 'zumra') aria-current="page" @endif>
        <span class="dg-tabbar__icon" aria-hidden="true"></span>ZUMRA
    </a>
    <a href="{{ route('member.space') }}" @if($current === 'espace') aria-current="page" @endif>
        <span class="dg-tabbar__icon" aria-hidden="true"></span>Moi
    </a>
</nav>

<div id="dg-agir-sheet" data-dg-agir-sheet hidden>
    <div class="dg-sheet-backdrop" data-dg-agir-close></div>
    <div class="dg-sheet" role="dialog" aria-modal="true" aria-label="Agir">
        <div class="dg-sheet__grab" aria-hidden="true"></div>
        <a href="{{ route('needs.create') }}" style="color:var(--dg-copper)">Exprimer un besoin</a>
        <a href="{{ route('projects.create') }}" style="color:var(--dg-forest-action)">Proposer un projet</a>
        <a href="{{ route('member.profile.edit') }}" style="color:var(--dg-ink)">Proposer une transmission</a>
        <span aria-disabled="true" title="Un partage avec contexte se fait depuis un besoin ou un projet précis.">Partager avec contexte</span>
        <button type="button" data-dg-agir-close style="margin-top:6px;color:var(--dg-muted);font-weight:500">Fermer</button>
    </div>
</div>
