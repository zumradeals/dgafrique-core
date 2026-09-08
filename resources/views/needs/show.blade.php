<x-layouts.member :title="$need->title" active="needs"><div class="dg-space">
<a class="dg-space-text-link" href="{{ route('needs.index') }}">← Les besoins</a><p class="dg-space-eyebrow">{{ $configuration['categories'][$need->category] ?? 'Besoin' }}</p><h1>{{ $need->title }}</h1>
<p class="dg-space-description">{{ $need->context }}</p>
@if ($need->location)<p>Lieu : {{ $need->location }}</p>@endif
@if ($need->capability_label)<p>Savoir-faire recherché : {{ $need->capability_label }}</p>@endif
<p>{{ ['PROPOSED' => 'En attente d’une décision', 'OPEN' => 'Ouvert', 'IN_PROGRESS' => 'En cours', 'RESOLVED' => 'Résolu', 'ARCHIVED' => 'Archivé'][$need->status] ?? 'État non précisé' }}</p>
@if ($canDecide)<section class="dg-space-section"><h2>Suivre l’avancée du besoin</h2><form method="POST" action="{{ route('needs.transition', $need) }}">@csrf @method('PUT')<label for="status">Nouvel état</label><select class="dg-input" id="status" name="status">@foreach (['OPEN' => 'Ouvert', 'IN_PROGRESS' => 'En cours', 'RESOLVED' => 'Résolu', 'ARCHIVED' => 'Archivé'] as $value => $label)<option value="{{ $value }}" @selected($need->status === $value)>{{ $label }}</option>@endforeach</select><label for="resolution_note">Précisions sur la résolution</label><textarea class="dg-input" id="resolution_note" name="resolution_note" maxlength="1500">{{ old('resolution_note', $need->resolution_note) }}</textarea><x-dg.button type="submit">Enregistrer l’avancée</x-dg.button></form></section>@endif
@if ($canProposeMission)<section class="dg-space-section"><h2>Vous pouvez aider.</h2><p>Proposez une action concrète pour répondre à ce besoin.</p><x-dg.button :href="route('needs.missions.create', $need)">Proposer une mission</x-dg.button></section>@endif
</div></x-layouts.member>
