<div id="dg-agir-sheet" data-dg-agir-sheet hidden>
    <div class="dg-sheet-backdrop" data-dg-agir-close></div>
    <div class="dg-sheet" role="dialog" aria-modal="true" aria-labelledby="dg-agir-title">
        <div class="dg-sheet__grab" aria-hidden="true"></div>
        <h2 id="dg-agir-title" class="dg-sheet__heading">Que voulez-vous mettre en mouvement ?</h2>

        <a href="{{ route('needs.create') }}" class="dg-sheet__action">
            <span class="dg-sheet__action-mark" aria-hidden="true">!</span>
            <span style="flex:1">
                <strong>Exprimer un besoin</strong>
                <small>Dire clairement ce qu’il vous faut pour avancer.</small>
            </span>
            <span aria-hidden="true">→</span>
        </a>

        <a href="{{ route('projects.create') }}" class="dg-sheet__action dg-sheet__action--project">
            <span class="dg-sheet__action-mark" aria-hidden="true">＋</span>
            <span style="flex:1">
                <strong>Proposer un projet</strong>
                <small>Transformer une intention en action collective.</small>
            </span>
            <span aria-hidden="true">→</span>
        </a>

        {{-- UIUX-008 — ce lien pointait vers member.profile.edit (bogue trouvé par l'audit Phase A) :
             la seule porte globale vers la création de Transmission était inopérante. --}}
        <a href="{{ route('transmissions.create') }}" class="dg-sheet__action dg-sheet__action--project">
            <span class="dg-sheet__action-mark" aria-hidden="true">↗</span>
            <span style="flex:1">
                <strong>Proposer une transmission</strong>
                <small>Mettre une capacité ou un savoir au service du réseau.</small>
            </span>
            <span aria-hidden="true">→</span>
        </a>

        <span aria-disabled="true" title="Un partage avec contexte se fait depuis un besoin ou un projet précis.">Partager avec contexte</span>
        <button type="button" data-dg-agir-close style="margin-top:6px;color:var(--dg-muted);font-weight:500">Fermer</button>
    </div>
</div>
