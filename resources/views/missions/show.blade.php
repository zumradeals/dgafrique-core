<x-layouts.member :title="$mission->title" active="space"><div class="dg-space">
<a class="dg-space-text-link" href="{{ route('missions.index') }}">← Retour à la liste</a>
<p class="dg-space-eyebrow">{{ $mission::STATUS_LABELS[$mission->status] ?? 'État non précisé' }}</p><h1>{{ $mission->title }}</h1>
<p class="dg-space-description">{{ $mission->description }}</p>
@if ($contextLabel)<p>Contexte : {{ $contextLabel }}</p>@endif
@if ($myAssignment?->status === 'INVITED')
<section class="dg-space-section"><h2>Votre participation reste votre choix.</h2>
@foreach (['accept' => 'Accepter l’invitation', 'decline' => 'Décliner l’invitation'] as $action => $label)
<form method="POST" action="{{ route('missions.assignments.invitation.'.$action, [$mission, $myAssignment]) }}">@csrf<x-dg.button type="submit" :variant="$action === 'accept' ? 'primary' : 'secondary'">{{ $label }}</x-dg.button></form>
@endforeach</section>@endif
@if ($mission->status === 'DRAFT' && ($canOfficialize || $mission->created_by_core_reference === $identity->reference))
<section class="dg-space-section"><h2>Votre brouillon est prêt ?</h2><form method="POST" action="{{ route('missions.propose', $mission) }}">@csrf<x-dg.button type="submit">Soumettre la proposition</x-dg.button></form></section>
@endif
<section class="dg-space-section"><h2>Résultat attendu</h2><p>{{ $mission->expected_result ?: 'Le résultat attendu n’est pas encore précisé.' }}</p></section>
</div></x-layouts.member>
