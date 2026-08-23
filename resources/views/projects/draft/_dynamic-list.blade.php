{{--
    Collection dynamique réutilisable (ajouter / modifier / retirer), sans JavaScript requis —
    UIUX-009B. Attend : $key (nom du champ), $items (tableau de chaînes déjà enregistrées),
    $placeholder. Chaque ligne existante peut être modifiée en place ou retirée ; une ligne vide
    est toujours proposée en fin de liste pour en ajouter une nouvelle.
--}}
<div style="display:flex;flex-direction:column;gap:8px">
    @foreach($items as $i => $value)
        <div style="display:flex;gap:8px;align-items:center">
            <input type="text" name="{{ $key }}[]" value="{{ $value }}" class="dg-input" style="flex:1">
            <button type="submit" name="_intent" value="remove:{{ $key }}:{{ $i }}" formnovalidate class="dg-btn dg-btn--quiet" style="flex:none;min-height:var(--dg-touch-min)">Retirer</button>
        </div>
    @endforeach
    <div style="display:flex;gap:8px;align-items:center">
        <input type="text" name="{{ $key }}[]" value="" class="dg-input" style="flex:1" placeholder="{{ $placeholder }}">
        <button type="submit" name="_intent" value="add" formnovalidate class="dg-btn dg-btn--quiet" style="flex:none;min-height:var(--dg-touch-min)">+ Ajouter</button>
    </div>
</div>
