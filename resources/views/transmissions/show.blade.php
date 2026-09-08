<x-layouts.member :title="$transmission->capability_label" active="space"><div class="dg-space">
<a class="dg-space-text-link" href="{{ route('transmissions.index') }}">← Retour à la liste</a>
<p class="dg-space-eyebrow">{{ $transmission::STATUS_LABELS[$transmission->status] ?? 'État non précisé' }}</p><h1>{{ $transmission->capability_label }}</h1>
<p class="dg-space-description">{{ $transmission->learning_objective }}</p>
@if ($contextLabel)<p>Contexte : {{ $contextLabel }}</p>@endif
@if ($myParticipant?->status === 'INVITED')<section class="dg-space-section"><h2>Souhaitez-vous participer ?</h2>
@foreach (['accept' => 'Accepter l’invitation', 'decline' => 'Décliner l’invitation'] as $action => $label)
<form method="POST" action="{{ route('transmissions.participants.invitation.'.$action, [$transmission, $myParticipant]) }}">@csrf<x-dg.button type="submit" :variant="$action === 'accept' ? 'primary' : 'secondary'">{{ $label }}</x-dg.button></form>@endforeach
</section>@endif
</div></x-layouts.member>
