<x-layouts.member :title="$project->name" active="projects" :wide="true">
@php
    $statusLabels = [
        'PROPOSED' => 'Proposé',
        'ADOPTED' => 'Adopté',
        'IN_PROGRESS' => 'En cours',
        'COMPLETED' => 'Terminé',
        'ARCHIVED' => 'Archivé',
    ];
    $modeLabels = ['PHYSICAL' => 'Sur place', 'DIGITAL' => 'À distance', 'HYBRID' => 'Hybride'];
    $eventLabels = [
        'PROJECT_PROPOSED' => 'a créé le projet',
        'PROJECT_ADOPTED' => 'a fait adopter le projet',
        'PROJECT_IN_PROGRESS' => 'a démarré le projet',
        'PROJECT_COMPLETED' => 'a marqué le projet terminé',
        'PROJECT_ARCHIVED' => 'a archivé le projet',
        'PROJECT_MATURITY_CHANGED' => 'a mis à jour le repère de maturité',
        'PROJECT_MILESTONE_COMPLETED' => 'a accompli un jalon',
        'AUTONOMY_PATHWAY_OPENED' => 'a ouvert une trajectoire vers l’autonomie',
        'AUTONOMY_PATHWAY_CLOSED' => 'a fermé la trajectoire vers l’autonomie',
    ];
    $missionLabels = \App\Models\Mission::STATUS_LABELS;
    $maturityKeys = array_keys($maturityStages);
    $maturityIndex = array_search((string) $project->maturity, $maturityKeys, true);
    $nextMilestone = $project->milestones->first(fn ($milestone) => $milestone->status !== \App\Models\ProjectMilestone::STATUS_COMPLETED);
    $isProjectCarrier = $project->initiator_core_reference === $identity->reference
        || ($project->owner_type === 'PERSON' && $project->owner_reference === $identity->reference);
    $activeFunding = $funding && $funding->status === \App\Models\ProjectFunding::STATUS_OPEN;
    $autonomyLabels = [
        'COMPANY' => 'Entreprise',
        'ASSOCIATION' => 'Association',
        'COOPERATIVE' => 'Coopérative',
        'STARTUP' => 'Startup',
        'PLATFORM' => 'Plateforme',
        'OTHER' => $project->autonomyPathway?->other_form_label ?: 'Autre forme',
    ];
    $hasProjectCover = filled($project->image_path)
        && \Illuminate\Support\Facades\Storage::disk('public')->exists($project->image_path)
        && is_file(public_path('storage/'.$project->image_path));
    $projectInitials = collect(preg_split('/\s+/u', trim($project->name)) ?: [])
        ->filter()
        ->take(2)
        ->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');
    $projectInitials = $projectInitials !== '' ? $projectInitials : 'G';
    $teamCount = $teamMembers->count();
    $teamLabel = $teamCount === 0
        ? 'Équipe à constituer'
        : ($teamCount === 1 ? '1 membre impliqué' : $teamCount.' membres impliqués');
@endphp

<div class="dg-project-cv">
    @if ($group)
        <a class="dg-project-cv__back" href="{{ route('zumra.groups.show', $group) }}">← Retour à {{ $group->name }}</a>
    @else
        <a class="dg-project-cv__back" href="{{ route('projects.index') }}">← Retour aux projets</a>
    @endif

    <section class="dg-project-cv__hero" aria-labelledby="project-title">
        <div class="dg-project-cv__hero-main">
            <div class="dg-project-cv__cover">
                @if ($hasProjectCover)
                    <img src="{{ asset('storage/'.$project->image_path) }}" alt="Illustration du projet {{ $project->name }}">
                @else
                    <div class="dg-project-cv__cover-fallback" aria-label="Identité visuelle par défaut du projet">
                        <strong>{{ $projectInitials }}</strong>
                        <small>Projet GAMAD</small>
                    </div>
                @endif
            </div>
            <div class="dg-project-cv__identity">
                <div class="dg-project-cv__badges">
                    <span class="dg-project-cv__badge dg-project-cv__badge--status">{{ $statusLabels[$project->status] ?? $project->status }}</span>
                    <span class="dg-project-cv__badge">{{ $maturityStages[$project->maturity]['label'] ?? $project->maturity }}</span>
                </div>
                <h1 id="project-title">{{ $project->name }}</h1>
                <p class="dg-project-cv__summary">{{ $project->summary }}</p>
                <div class="dg-project-cv__meta">
                    @if ($group)<span>◉ {{ $group->name }}</span>@endif
                    <span>⌖ {{ $project->location ?: 'Territoire non précisé' }}</span>
                    <span>♟ {{ $teamLabel }}</span>
                    <span>{{ $configuration['domains'][$project->domain] ?? $project->domain }}</span>
                </div>
            </div>
        </div>

        <div class="dg-project-cv__hero-state">
            <div class="dg-project-cv__progress-ring" style="--project-progress: {{ $progressPercentage ?? 0 }}%;">
                <strong>{{ $progressPercentage !== null ? $progressPercentage.'%' : '—' }}</strong>
            </div>
            <div>
                <span class="dg-project-cv__state-label">Progression par jalons</span>
                <strong>{{ $progressPercentage !== null ? 'Mesurée sur les jalons' : 'Non mesurée' }}</strong>
            </div>
            <dl class="dg-project-cv__hero-facts">
                <div><dt>Démarrage</dt><dd>{{ $project->started_at?->translatedFormat('d M Y') ?? 'Pas encore démarré' }}</dd></div>
                <div><dt>Prochain jalon</dt><dd>{{ $nextMilestone?->title ?? 'Aucun jalon à venir' }}</dd></div>
            </dl>
        </div>
    </section>

    <nav class="dg-project-cv__tabs" aria-label="Navigation du projet">
        <a class="is-active" href="#vue-ensemble">▣ Vue d’ensemble</a>
        <a href="#jalons">⚑ Jalons</a>
        <a href="#equipe">♙ Équipe</a>
        <a href="#besoins">♡ Besoins</a>
        <a href="#missions">✓ Missions</a>
        <a href="#ressources">▤ Ressources</a>
        <a href="#financement">◫ Financement</a>
        <a href="#trajectoire">◎ Trajectoire</a>
    </nav>

    <div class="dg-project-cv__layout">
        <aside class="dg-project-cv__left">
            @if ($group)
                <section class="dg-project-cv__panel dg-project-cv__zumra-card">
                    <p class="dg-project-cv__eyebrow">ZUMRA MÈRE</p>
                    <h2>{{ $group->name }}</h2>
                    <p>{{ $group->founding_objective }}</p>
                    <div class="dg-project-cv__zumra-nav">
                        <a href="{{ route('zumra.groups.show', $group) }}">⌂ Accueil ZUMRA</a>
                        <a href="{{ route('zumra.groups.formation', $group) }}">✦ Formation</a>
                        <a class="is-active" href="{{ route('projects.index', ['group' => $group->public_reference]) }}">▣ Projets</a>
                        <a href="{{ route('zumra.groups.show', $group) }}#besoins">♡ Besoins</a>
                        <a href="{{ route('zumra.groups.show', $group) }}#membres">♙ Membres</a>
                    </div>
                </section>
            @endif

            <section class="dg-project-cv__panel">
                <p class="dg-project-cv__eyebrow">REPÈRES</p>
                <dl class="dg-project-cv__facts">
                    <div><dt>Domaine</dt><dd>{{ $configuration['domains'][$project->domain] ?? $project->domain }}</dd></div>
                    <div><dt>Mode</dt><dd>{{ $modeLabels[$project->participation_mode] ?? $project->participation_mode }}</dd></div>
                    <div><dt>Statut</dt><dd>{{ $statusLabels[$project->status] ?? $project->status }}</dd></div>
                    <div><dt>Maturité</dt><dd>{{ $maturityStages[$project->maturity]['label'] ?? $project->maturity }}</dd></div>
                    <div><dt>Créé</dt><dd>{{ $project->created_at->translatedFormat('d M Y') }}</dd></div>
                </dl>
            </section>

            @if ($canDecide)
                <section class="dg-project-cv__panel">
                    <p class="dg-project-cv__eyebrow">PILOTER</p>
                    <div class="dg-project-cv__stack-links">
                        <a href="{{ route('projects.matching', $project) }}">Trouver des personnes →</a>
                        <a href="{{ route('projects.accompaniment.show', $project) }}">Accompagnement →</a>
                        <a href="{{ route('projects.autonomy.show', $project) }}">Trajectoire d’autonomie →</a>
                    </div>
                </section>
            @endif
        </aside>

        <main class="dg-project-cv__main">
            <section class="dg-project-cv__grid-2" id="vue-ensemble">
                <article class="dg-project-cv__panel dg-project-cv__overview">
                    <div class="dg-project-cv__section-head"><div><p class="dg-project-cv__eyebrow">LE PROJET</p><h2>Comprendre en quelques secondes.</h2></div></div>
                    <div class="dg-project-cv__story">
                        <div><h3>Le problème</h3><p>{{ $project->problem }}</p></div>
                        <div><h3>La réponse proposée</h3><p>{{ $project->proposed_solution }}</p></div>
                        <div><h3>À qui cela sera utile</h3><p>{{ $project->beneficiaries }}</p></div>
                    </div>
                    <div class="dg-project-cv__metric-row">
                        <div><span>Domaine</span><strong>{{ $configuration['domains'][$project->domain] ?? $project->domain }}</strong></div>
                        <div><span>Bénéficiaires</span><strong>{{ \Illuminate\Support\Str::limit($project->beneficiaries, 54) }}</strong></div>
                        <div><span>Financement</span><strong>{{ $funding ? number_format($funding->target_amount, 0, ',', ' ').' '.$funding->currency : 'Non déclaré' }}</strong></div>
                        <div><span>Mode</span><strong>{{ $modeLabels[$project->participation_mode] ?? $project->participation_mode }}</strong></div>
                    </div>
                </article>

                <article class="dg-project-cv__panel" id="jalons">
                    <div class="dg-project-cv__section-head"><div><p class="dg-project-cv__eyebrow">AVANCEMENT</p><h2>Jalons du projet</h2></div><span>{{ $progressPercentage !== null ? $progressPercentage.'%' : 'Non mesuré' }}</span></div>
                    @forelse ($project->milestones as $milestone)
                        @php($milestoneDone = $milestone->status === \App\Models\ProjectMilestone::STATUS_COMPLETED)
                        <div class="dg-project-cv__milestone {{ $milestoneDone ? 'is-done' : '' }}">
                            <span class="dg-project-cv__milestone-dot">{{ $milestoneDone ? '✓' : '○' }}</span>
                            <div><strong>{{ $milestone->title }}</strong><small>{{ $milestoneDone ? 'Terminé' : 'À faire' }}</small></div>
                            @if ($canDecide && ! $milestoneDone)
                                <form method="POST" action="{{ route('projects.milestones.complete', [$project, $milestone]) }}">@csrf @method('PUT')<button type="submit">Marquer accompli</button></form>
                            @endif
                        </div>
                    @empty
                        <div class="dg-project-cv__empty"><strong>Aucun jalon formalisé.</strong><p>La progression reste volontairement non mesurée tant que le projet n’a pas défini de jalons.</p></div>
                    @endforelse
                </article>
            </section>

            <section class="dg-project-cv__grid-2">
                <article class="dg-project-cv__panel" id="activite">
                    <div class="dg-project-cv__section-head"><div><p class="dg-project-cv__eyebrow">ACTIVITÉ RÉELLE</p><h2>Ce qui a bougé récemment</h2></div></div>
                    @forelse ($recentEvents as $event)
                        @php($actor = $eventActorProfiles[$event->actor_core_reference] ?? null)
                        <div class="dg-project-cv__activity-row">
                            <span class="dg-project-cv__avatar">{{ mb_strtoupper(mb_substr($actor?->discovery_display_name ?: ($event->actor_core_reference === $identity->reference ? $identity->label : 'Un membre GAMAD'), 0, 1)) }}</span>
                            <div><strong>{{ $actor?->discovery_display_name ?: ($event->actor_core_reference === $identity->reference ? $identity->label : 'Un membre GAMAD') }}</strong> {{ $eventLabels[$event->event] ?? 'a fait évoluer le projet' }}.<small>{{ $event->occurred_at?->diffForHumans() }}</small></div>
                        </div>
                    @empty
                        <div class="dg-project-cv__empty"><strong>Aucune activité enregistrée.</strong><p>Les événements réels du projet apparaîtront ici.</p></div>
                    @endforelse
                </article>

                <article class="dg-project-cv__panel" id="missions">
                    <div class="dg-project-cv__section-head"><div><p class="dg-project-cv__eyebrow">TRAVAIL</p><h2>Missions du projet</h2></div>@if ($canProposeMission)<a href="{{ route('projects.missions.create', $project) }}">+ Proposer une mission</a>@endif</div>
                    @forelse ($projectMissions as $mission)
                        <a class="dg-project-cv__mission-row" href="{{ route('missions.show', $mission) }}">
                            <div><strong>{{ $mission->title }}</strong><small>{{ $missionLabels[$mission->status] ?? $mission->status }}@if ($mission->due_at) · échéance {{ $mission->due_at->translatedFormat('d M') }}@endif</small></div>
                            <span>→</span>
                        </a>
                    @empty
                        <div class="dg-project-cv__empty"><strong>Aucune mission visible pour le moment.</strong><p>Une mission transforme un objectif du projet en action concrète.</p>@if ($canProposeMission)<a class="dg-project-cv__inline-action" href="{{ route('projects.missions.create', $project) }}">Proposer la première mission →</a>@endif</div>
                    @endforelse
                </article>
            </section>

            <section class="dg-project-cv__panel" id="ressources">
                <div class="dg-project-cv__section-head"><div><p class="dg-project-cv__eyebrow">CAPACITÉS & RESSOURCES</p><h2>Ce qu’il faut réunir pour avancer.</h2></div></div>
                <div class="dg-project-cv__grid-3">
                    <div class="dg-project-cv__list-card"><h3>Objectifs</h3>@forelse ($project->objectives ?? [] as $item)<p>✓ {{ $item }}</p>@empty<p>Pas encore précisés.</p>@endforelse</div>
                    <div class="dg-project-cv__list-card"><h3>Savoir-faire nécessaires</h3>@forelse ($project->required_capabilities ?? [] as $item)<p>◇ {{ $item }}</p>@empty<p>Pas encore précisés.</p>@endforelse</div>
                    <div class="dg-project-cv__list-card"><h3>Ressources nécessaires</h3>@forelse ($project->required_resources ?? [] as $item)<p>▤ {{ $item }}</p>@empty<p>Pas encore précisées.</p>@endforelse</div>
                </div>
                @if (! empty($project->risks))
                    <div class="dg-project-cv__risk"><strong>Difficultés à anticiper</strong><span>{{ implode(' · ', $project->risks) }}</span></div>
                @endif
            </section>

            @if ($canDecide && $pendingTeamRequests->isNotEmpty())
                <section class="dg-project-cv__panel">
                    <div class="dg-project-cv__section-head"><div><p class="dg-project-cv__eyebrow">DÉCISION</p><h2>Demandes de participation</h2></div><span>{{ $pendingTeamRequests->count() }}</span></div>
                    @foreach ($pendingTeamRequests as $member)
                        @php($profile = $teamProfiles[$member->core_identity_reference] ?? null)
                        <div class="dg-project-cv__request-row">
                            <div><strong>{{ $profile?->discovery_display_name ?? 'Un membre GAMAD' }}</strong><p>{{ $member->motivation ?: 'Aucune motivation précisée.' }}</p></div>
                            <form method="POST" action="{{ route('projects.team.requests.approve', [$project, $member]) }}">@csrf<button type="submit">Accepter</button></form>
                        </div>
                    @endforeach
                </section>
            @endif

            <section class="dg-project-cv__panel" id="trajectoire">
                <div class="dg-project-cv__section-head"><div><p class="dg-project-cv__eyebrow">DU PROJET À L’IMPACT</p><h2>Notre chemin vers la réussite.</h2><p>Chaque repère décrit une maturité réelle. Il ne crée automatiquement ni entreprise ni statut juridique.</p></div></div>
                <div class="dg-project-cv__maturity-path">
                    @foreach ($maturityStages as $key => $stage)
                        <div class="dg-project-cv__maturity-step {{ $project->maturity === $key ? 'is-current' : '' }}">
                            <span>{{ str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT) }}</span>
                            <strong>{{ $stage['label'] }}</strong>
                            @if ($project->maturity === $key)<small>Repère actuel</small>@endif
                        </div>
                    @endforeach
                </div>
                <div class="dg-project-cv__autonomy">
                    <div><strong>Vers une organisation</strong><p>À maturité suffisante, ce projet peut donner naissance à une startup ou une autre organisation durable. Rien n’est créé automatiquement : cette évolution reste une décision explicite. L’organisation issue du projet reste liée à sa ZUMRA mère dans GAMAD, afin que talents, expérience et opportunités continuent à circuler dans les deux sens.</p></div>
                    @if ($project->autonomyPathway)
                        <span>Trajectoire : {{ $autonomyLabels[$project->autonomyPathway->target_form] ?? $project->autonomyPathway->target_form }} · {{ $project->autonomyPathway->status }}</span>
                    @elseif ($canDecide)
                        <a href="{{ route('projects.autonomy.show', $project) }}">Voir les conditions d’autonomie →</a>
                    @endif
                </div>
            </section>
        </main>

        <aside class="dg-project-cv__right">
            <section class="dg-project-cv__panel dg-project-cv__state-card">
                <p class="dg-project-cv__eyebrow">ÉTAT DU PROJET</p>
                <span class="dg-project-cv__badge dg-project-cv__badge--status">{{ $statusLabels[$project->status] ?? $project->status }}</span>
                <h2>{{ $maturityStages[$project->maturity]['label'] ?? $project->maturity }}</h2>
                <p>{{ $maturityStages[$project->maturity]['description'] ?? 'Repère de maturité du projet.' }}</p>
                <small>Dernière activité : {{ $lastActivityAt?->diffForHumans() }}</small>
            </section>

            <section class="dg-project-cv__panel" id="besoins">
                <div class="dg-project-cv__section-head"><div><p class="dg-project-cv__eyebrow">BESOINS DU PROJET</p><h2>{{ $projectNeeds->count() }} visible{{ $projectNeeds->count() > 1 ? 's' : '' }}</h2></div></div>
                @forelse ($projectNeeds->take(4) as $need)
                    <a class="dg-project-cv__need-row" href="{{ route('needs.show', $need) }}"><span>◇</span><div><strong>{{ $need->title }}</strong><small>{{ str_replace('_', ' ', mb_strtolower($need->status)) }}</small></div></a>
                @empty
                    <p class="dg-project-cv__muted">Aucun besoin publié à afficher.</p>
                @endforelse
                @if ($canProposeNeed)<a class="dg-project-cv__wide-action" href="{{ route('needs.create', ['project' => $project->public_reference]) }}">Exprimer un besoin</a>@endif
            </section>

            <section class="dg-project-cv__panel" id="equipe">
                <div class="dg-project-cv__section-head"><div><p class="dg-project-cv__eyebrow">ÉQUIPE</p><h2>{{ $teamLabel }}</h2></div></div>
                @if ($teamMembers->isNotEmpty())
                    <div class="dg-project-cv__avatars">
                        @foreach ($teamMembers->take(8) as $member)
                            @php($profile = $teamProfiles[$member->core_identity_reference] ?? null)
                            <span title="{{ $profile?->discovery_display_name ?? 'Membre GAMAD' }}">{{ mb_strtoupper(mb_substr($profile?->discovery_display_name ?? 'G', 0, 1)) }}</span>
                        @endforeach
                        @if ($teamMembers->count() > 8)<span>+{{ $teamMembers->count() - 8 }}</span>@endif
                    </div>
                @endif

                @if ($myTeamMembership?->status === 'ACTIVE')
                    <p class="dg-project-cv__muted">Vous faites partie de l’équipe.</p>
                @elseif ($myTeamMembership?->status === 'REQUESTED')
                    <p class="dg-project-cv__muted">Votre demande de participation est en attente.</p>
                @elseif ($myTeamMembership?->status === 'INVITED')
                    <form method="POST" action="{{ route('projects.team.invitation.accept', $project) }}">@csrf<button class="dg-project-cv__wide-action" type="submit">Accepter l’invitation</button></form>
                @elseif ($isProjectCarrier)
                    <p class="dg-project-cv__muted">Vous portez ce projet.@if ($teamMembers->isEmpty()) L’équipe se constituera au fil des participations.@endif</p>
                @else
                    <form class="dg-project-cv__join" method="POST" action="{{ route('projects.team.request', $project) }}">@csrf
                        <label for="motivation">Comment souhaitez-vous contribuer ?</label>
                        <textarea id="motivation" name="motivation" rows="3" maxlength="800" placeholder="Facultatif"></textarea>
                        <button type="submit">Proposer ma participation</button>
                    </form>
                @endif
            </section>

            <section class="dg-project-cv__panel" id="financement">
                <div class="dg-project-cv__section-head"><div><p class="dg-project-cv__eyebrow">FINANCEMENT</p><h2>{{ $funding ? 'Déclaration existante' : 'Non déclaré' }}</h2></div></div>
                @if ($funding)
                    <dl class="dg-project-cv__funding">
                        <div><dt>Objectif</dt><dd>{{ number_format($funding->target_amount, 0, ',', ' ') }} {{ $funding->currency }}</dd></div>
                        <div><dt>Collecté</dt><dd>{{ number_format($fundingCollected, 0, ',', ' ') }} {{ $funding->currency }}</dd></div>
                        <div><dt>Restant</dt><dd>{{ number_format($fundingRemaining, 0, ',', ' ') }} {{ $funding->currency }}</dd></div>
                    </dl>
                    <p class="dg-project-cv__muted">{{ $funding->purpose }}</p>
                    @if ($activeFunding)<span class="dg-project-cv__badge">Ouvert</span>@endif
                @else
                    <p class="dg-project-cv__muted">Aucun budget n’est inventé tant qu’une déclaration financière n’existe pas.</p>
                @endif
            </section>

            <section class="dg-project-cv__panel">
                <p class="dg-project-cv__eyebrow">PARTENAIRES & ACCOMPAGNEMENT</p>
                <h2>{{ count($projectPartnerships) }} partenariat{{ count($projectPartnerships) > 1 ? 's' : '' }} visible{{ count($projectPartnerships) > 1 ? 's' : '' }}</h2>
                <p class="dg-project-cv__muted">{{ $accompaniment ? 'Un accompagnement est rattaché à ce projet.' : 'Aucun accompagnement affichable pour votre rôle.' }}</p>
            </section>

            <section class="dg-project-cv__panel dg-project-cv__quick-actions">
                <p class="dg-project-cv__eyebrow">ACTIONS RAPIDES</p>
                @if ($canProposeMission)<a href="{{ route('projects.missions.create', $project) }}">✓ Proposer une mission</a>@endif
                @if ($canProposeNeed)<a href="{{ route('needs.create', ['project' => $project->public_reference]) }}">♡ Exprimer un besoin</a>@endif
                <a href="{{ route('projects.matching', $project) }}">♙ Trouver des compétences</a>
                @if ($group)<a href="{{ route('zumra.groups.show', $group) }}">◉ Retour à la ZUMRA</a>@endif
            </section>
        </aside>
    </div>
</div>
</x-layouts.member>
