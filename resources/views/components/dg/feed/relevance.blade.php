{{--
    CAP-055 : raison de pertinence personnelle, uniquement quand une relation métier réelle
    existe (porteur, équipe, mission assignée, ZUMRA active) — jamais un texte fabriqué.
--}}
@props(['item'])
@if($item['relevance_reason'] ?? null)
    <x-dg.note>{{ $item['relevance_reason'] }}</x-dg.note>
@endif
