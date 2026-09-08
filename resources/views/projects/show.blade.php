<x-layouts.member :title="$project->name" active="projects"><div class="dg-space"><a class="dg-space-text-link" href="{{ route('projects.index') }}">← Les projets</a><p class="dg-space-eyebrow">{{ $configuration['domains'][$project->domain] ?? 'Projet collectif' }}</p><h1>{{ $project->name }}</h1><p>{{ $project->summary }}</p>
<section class="dg-space-section"><h2>Ce que nous souhaitons faire avancer.</h2><p class="dg-space-description">{{ $project->problem }}</p><p class="dg-space-description">{{ $project->proposed_solution }}</p>
@if ($project->beneficiaries)<p>Pour qui : {{ $project->beneficiaries }}</p>@endif
@if ($project->location)<p>Lieu : {{ $project->location }}</p>@endif</section>
<section class="dg-space-section"><h2>Participer au projet</h2>
@if ($myTeamMembership?->status === 'ACTIVE')<p>Vous faites partie de l’équipe.</p>
@elseif ($myTeamMembership?->status === 'REQUESTED')<p>Votre demande attend la réponse du porteur. Vous n’êtes pas encore dans l’équipe.</p>
@elseif ($myTeamMembership?->status === 'INVITED')<p>Vous avez reçu une invitation à rejoindre l’équipe.</p><form method="POST" action="{{ route('projects.team.invitation.accept', $project) }}">@csrf<x-dg.button type="submit">Accepter l’invitation</x-dg.button></form>
@elseif ($project->initiator_core_reference === $identity->reference || ($project->owner_type === 'PERSON' && $project->owner_reference === $identity->reference))<p>Vous portez ce projet.</p>
@else<form method="POST" action="{{ route('projects.team.request', $project) }}">@csrf<label for="motivation">Comment aimeriez-vous contribuer ? (facultatif)</label><textarea class="dg-input" id="motivation" name="motivation" rows="3" maxlength="800">{{ old('motivation') }}</textarea><p class="dg-space-note">Le porteur examinera votre demande. Vous ne rejoignez pas automatiquement l’équipe.</p><x-dg.button type="submit">Proposer ma participation</x-dg.button></form>@endif
</section>
@if ($canDecide && $pendingTeamRequests->isNotEmpty())<section class="dg-space-section"><h2>Demandes de participation</h2>@foreach ($pendingTeamRequests as $member)<article class="dg-space-section"><h3>Une personne souhaite participer</h3><p>{{ $member->motivation }}</p><form method="POST" action="{{ route('projects.team.requests.approve', [$project, $member]) }}">@csrf<x-dg.button type="submit">Accepter cette participation</x-dg.button></form></article>@endforeach</section>@endif
<section class="dg-space-section"><h2>Les besoins du projet</h2>@forelse ($projectNeeds as $need)<a class="dg-space-row" href="{{ route('needs.show', $need) }}"><strong>{{ $need->title }}</strong><span aria-hidden="true">→</span></a>@empty<p>Aucun besoin publié à afficher.</p>@endforelse
@if ($canProposeNeed)<a class="dg-space-text-link" href="{{ route('needs.create', ['project' => $project->public_reference]) }}">Exprimer un besoin pour ce projet →</a>@endif</section>
@if ($canProposeMission)<section class="dg-space-section"><h2>Organiser une action</h2><x-dg.button :href="route('projects.missions.create', $project)">Proposer une mission</x-dg.button></section>@endif
</div></x-layouts.member>
