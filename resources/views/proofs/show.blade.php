<x-layouts.member :title="$proof->title" active="space"><div class="dg-space">
<a class="dg-space-text-link" href="{{ route('proofs.index') }}">← Retour à la liste</a>
<p class="dg-space-eyebrow">{{ $proof::STATUS_LABELS[$proof->status] ?? 'État non précisé' }}</p><h1>{{ $proof->title }}</h1>
<p class="dg-space-description">{{ $proof->description }}</p>
@if ($contextLabel)<p>Contexte : {{ $contextLabel }}</p>@endif
@if ($myWitness?->status === 'INVITED')<section class="dg-space-section"><h2>Votre témoignage est demandé.</h2><p>Confirmez uniquement ce que vous pouvez attester.</p>
@foreach (['confirm' => 'Confirmer mon témoignage', 'decline' => 'Décliner la demande'] as $action => $label)
<form method="POST" action="{{ route('proofs.witnesses.'.$action, [$proof, $myWitness]) }}">@csrf<x-dg.button type="submit" :variant="$action === 'confirm' ? 'primary' : 'secondary'">{{ $label }}</x-dg.button></form>@endforeach</section>@endif
</div></x-layouts.member>
