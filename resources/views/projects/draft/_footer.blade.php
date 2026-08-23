{{-- Actions communes de bas d'étape, à l'intérieur du <form> de l'étape. $previousStep fourni par le contrôleur. --}}
<div class="dg-actions" style="margin-top:24px;flex-wrap:wrap;gap:10px">
    @if($previousStep)
        <button type="submit" name="_intent" value="back" formnovalidate class="dg-btn dg-btn--quiet">← Retour</button>
    @endif
    <button type="submit" name="_intent" value="save_later" formnovalidate class="dg-btn dg-btn--quiet">Enregistrer et continuer plus tard</button>
    <button type="submit" name="_intent" value="continue" class="dg-btn dg-btn--project" style="margin-left:auto">{{ $continueLabel ?? 'Continuer →' }}</button>
</div>
