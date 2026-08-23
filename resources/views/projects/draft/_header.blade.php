{{-- Chrome commun du parcours progressif — UIUX-009B. $step et $draft sont fournis par chaque vue d'étape. --}}
@php
    $stepIndex = array_search($step, \App\Application\Projects\ProjectDraftService::STEPS, true);
@endphp
<a href="{{ route('projects.index') }}" class="dg-crumb">← Tous les projets</a>

<div class="dg-steps" role="presentation" style="margin:6px 0 26px">
    @foreach(\App\Application\Projects\ProjectDraftService::STEPS as $i => $s)
        <span class="dg-step @if($s === $step) active @elseif($i < $stepIndex) visited @endif" style="cursor:default">{{ $i + 1 }} · {{ \App\Application\Projects\ProjectDraftService::STEP_LABELS[$s] }}</span>
    @endforeach
</div>

@if($errors->any())
    <div class="dg-band" style="margin-bottom:20px;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>
@endif
