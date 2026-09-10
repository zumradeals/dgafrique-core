<x-layouts.member :title="$group->name" active="zumra" :wide="true">
    @php
        $modeLabels = ['PHYSICAL' => 'Présentiel', 'DIGITAL' => 'À distance', 'HYBRID' => 'Hybride'];
        $stateLabels = [
            'CONSTITUTING' => 'En constitution', 'READY' => 'Prête', 'VALIDATED' => 'Validée', 'ACTIVE' => 'Active',
            'WARNED' => 'Sous vigilance', 'SUSPENDED' => 'Suspendue', 'REHABILITATING' => 'En réhabilitation',
        ];
        $projectStatusLabels = [
            'PROPOSED' => 'Proposé', 'ADOPTED' => 'Adopté', 'IN_PROGRESS' => 'Actif', 'COMPLETED' => 'Accompli', 'ARCHIVED' => 'Archivé',
        ];
        $projectSequence = $groupProjects->sortBy('created_at')->values();
        $primaryProject = $projectSequence->first();
        $derivedProjects = $projectSequence->slice(1)->values();
        $projectProgress = $primaryProject?->milestoneProgressPercentage();
        $projectHref = $primaryProject
            ? route('projects.show', $primaryProject)
            : route('projects.create', ['group' => $group->public_reference]);
        $projectAction = $primaryProject ? 'Voir le projet' : 'Créer un projet';
        $projectTitle = $primaryProject?->name ?? 'Donnez vie au premier projet';
        $projectSummary = $primaryProject?->summary ?? $group->founding_objective;
        $projectStatus = $primaryProject ? ($projectStatusLabels[$primaryProject->status] ?? $primaryProject->status) : 'À créer';
        $projectPhase = match ($primaryProject?->status) {
            'PROPOSED' => 'Proposition et structuration',
            'ADOPTED' => 'Adoption et préparation',
            'IN_PROGRESS' => 'Exécution',
            'COMPLETED' => 'Accompli',
            default => 'Idéation et structuration',
        };
        $nextStep = match ($primaryProject?->status) {
            'PROPOSED' => 'Faire adopter le projet par la ZUMRA.',
            'ADOPTED' => 'Préparer son démarrage et ses premiers jalons.',
            'IN_PROGRESS' => 'Faire progresser les jalons et documenter les preuves.',
            'COMPLETED' => 'Capitaliser les résultats et préparer la suite.',
            default => 'Créer le premier projet de cette ZUMRA dans GAMAD.',
        };
        $cover = \App\Support\ZumraDomainPresentation::cover($group->domain);
        $projectCover = $primaryProject?->image_path ? asset('storage/'.$primaryProject->image_path) : $cover;
        $initials = collect(preg_split('/\s+/', trim($group->name)) ?: [])->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
        $initials = $initials !== '' ? $initials : 'Z';
        $leaderRole = $roles->first(fn ($role) => $role->role === 'PRIMARY_LEAD' && $role->status === \App\Models\ZumraGroupRole::STATUS_ACCEPTED);
        $leaderProfile = $leaderRole ? $roleProfiles->get($leaderRole->core_identity_reference) : null;
        $leaderLabel = $leaderRole?->core_identity_reference === $identity->reference
            ? $identity->label
            : ($leaderProfile?->discovery_display_name ?: 'Fondateur');
        $leaderInitial = mb_strtoupper(mb_substr($leaderLabel, 0, 1));
        $memberCount = (int) $group->active_member_count;
        $pendingCount = $pendingRequests->count();
        $modeLabel = $modeLabels[$group->participation_mode] ?? $group->participation_mode;
        $stateLabel = $stateLabels[$group->state] ?? $group->state;
        $createdLabel = $group->created_at?->translatedFormat('j M Y') ?? '';
        $isActiveMember = $membership?->status === \App\Models\ZumraGroupMembership::STATUS_ACTIVE;
    @endphp

    <div class="dg-zumra-world">
        <section class="dg-zumra-world-hero" aria-labelledby="zumra-world-title">
            <img class="dg-zumra-world-hero__cover" src="{{ $cover }}" alt="" aria-hidden="true">
            <span class="dg-zumra-world-hero__shade" aria-hidden="true"></span>
            <div class="dg-zumra-world-hero__content">
                <div class="dg-zumra-world-mark" aria-hidden="true">{{ $initials }}</div>
                <div>
                    <p class="dg-zumra-world-hero__doctrine">FORMATION · TRAVAIL · ADORATION</p>
                    <h1 id="zumra-world-title">{{ $group->name }}</h1>
                    <p class="dg-zumra-world-hero__lead">{{ $group->founding_objective }}</p>
                    <div class="dg-zumra-world-hero__meta">
                        <span>⌖ {{ $group->location ?: 'Territoire non précisé' }}</span>
                        <span>◉ {{ $modeLabel }}</span>
                        <span>◌ {{ $group->domain ?: 'Domaine non précisé' }}</span>
                    </div>
                </div>
                <p class="dg-zumra-world-hero__motto">Des idées.<br>Des talents.<br>Un impact réel.</p>
            </div>
            @if ($isLeader)
                <span class="dg-zumra-world-cover-action" aria-disabled="true" title="La gestion de couverture sera raccordée dans une étape dédiée">▣ Modifier la couverture</span>
            @endif
        </section>

        <nav class="dg-zumra-world-tabs" aria-label="Navigation dans la ZUMRA">
            <a class="is-active" href="#accueil">⌂ Accueil</a>
            <a href="{{ route('zumra.groups.formation', $group) }}">◈ Formation</a>
            <a href="#projets">▣ Projets</a>
            <a href="#membres">♙ Membres</a>
            @if ($isLeader)
                <a href="#demandes">▤ Demandes @if($pendingCount > 0)<span class="dg-zumra-world-tabs__badge">{{ $pendingCount }}</span>@endif</a>
            @endif
            <span aria-disabled="true" title="Le canal de discussion sera raccordé à son moteur dédié">▢ Discussion</span>
            <a href="#evenements">▣ Événements</a>
            <a href="#besoins">♡ Besoins</a>
            <a href="#missions">◉ Missions</a>
            <span aria-disabled="true">Plus⌄</span>
            @if ($isLeader)<span class="dg-zumra-world-tabs__push" aria-disabled="true">⚙ Paramètres</span>@endif
        </nav>

        <div id="accueil" class="dg-zumra-world-layout">
            <aside class="dg-zumra-world-left">
                <section class="dg-zumra-world-card dg-zumra-world-about">
                    <h2>⚑ À propos</h2>
                    <p class="dg-zumra-world-about__objective">{{ $group->founding_objective }}</p>
                    <ul class="dg-zumra-world-meta-list">
                        <li>▣ <span>Créée le <strong>{{ $createdLabel }}</strong></span></li>
                        <li>♙ <span>Par <strong>{{ $leaderLabel }}</strong></span></li>
                        <li>◉ <span><strong>{{ $stateLabel }}</strong></span></li>
                        <li>♙ <span><strong>{{ $memberCount }}</strong> membre{{ $memberCount === 1 ? '' : 's' }}</span></li>
                        <li>◌ <span>{{ $group->domain ?: 'Domaine non précisé' }}</span></li>
                        <li>◎ <span>{{ $modeLabel }}</span></li>
                    </ul>
                    @if ($isLeader)
                        <span class="dg-zumra-world-button dg-zumra-world-button--full" aria-disabled="true">✎ Modifier les informations</span>
                    @endif
                </section>

                <section class="dg-zumra-world-card">
                    <h2>Navigation rapide</h2>
                    <nav class="dg-zumra-world-quicknav">
                        <a href="#accueil">▣ Tableau de bord</a>
                        <a href="{{ route('zumra.groups.formation', $group) }}">◈ Se former</a>
                        <a href="#projets">▣ Nos projets</a>
                        <a href="#a-faire">▤ Notre charte</a>
                        <a href="#activite">▤ Nos actualités</a>
                        <span aria-disabled="true">▦ Nos ressources</span>
                        <a href="#membres">♙ Inviter des membres</a>
                    </nav>
                </section>

                <section class="dg-zumra-world-card dg-zumra-world-vision">
                    <h2>◎ Vision</h2>
                    <p>« Faire grandir les personnes par la formation, transformer leurs capacités en projets utiles et, avec la maturité, faire naître des organisations durables qui restent liées à leur ZUMRA mère. »</p>
                </section>
            </aside>

            <main class="dg-zumra-world-center">
                <section id="formation" class="dg-zumra-world-card dg-zumra-world-formation">
                    <div class="dg-zumra-world-formation__head">
                        <div>
                            <p class="dg-zumra-world-formation__eyebrow">◈ FORMATION</p>
                            <h2>Apprendre, progresser, transmettre.</h2>
                            <p>La première mission d’une ZUMRA est de faire grandir ses membres. On peut rejoindre cette communauté d’abord pour apprendre, développer une capacité et évoluer progressivement avant de contribuer davantage aux projets.</p>
                        </div>
                        <a class="dg-zumra-world-button" href="{{ route('zumra.groups.formation', $group) }}">Entrer dans l’espace Formation</a>
                    </div>
                    <div class="dg-zumra-world-formation__path" aria-label="Chemin de progression dans la ZUMRA">
                        <article><span>01</span><strong>Apprendre</strong><p>Découvrir des savoirs et développer de nouvelles capacités avec la communauté.</p></article>
                        <article><span>02</span><strong>Pratiquer</strong><p>Mettre en application ce qui est appris dans des activités et des projets réels.</p></article>
                        <article><span>03</span><strong>Transmettre</strong><p>Partager son expérience à son tour et aider d’autres membres à progresser.</p></article>
                    </div>
                    <p class="dg-zumra-world-formation__note">Les apprentissages réels de cette ZUMRA sont désormais portés par les Transmissions GAMAD. Aucun cours, niveau ou résultat n’est inventé pour remplir cet espace.</p>
                </section>

                <section id="projets" class="dg-zumra-world-card dg-zumra-project-main">
                    <div class="dg-zumra-project-main__hero">
                        <img src="{{ $projectCover }}" alt="" aria-hidden="true">
                        <div class="dg-zumra-project-main__topline">
                            <p class="dg-zumra-project-main__eyebrow">◉ Projet <span class="dg-zumra-project-main__status">{{ $projectStatus }}</span></p>
                            <a class="dg-zumra-project-main__link" href="{{ $projectHref }}">{{ $projectAction }} →</a>
                        </div>
                        <h2>{{ $projectTitle }}</h2>
                        <p class="dg-zumra-project-main__summary">{{ $projectSummary }}</p>
                        <div class="dg-zumra-project-main__tags">
                            @if ($group->domain)<span>{{ $group->domain }}</span>@endif
                            <span>{{ $modeLabel }}</span>
                            @if ($group->location)<span>{{ $group->location }}</span>@endif
                        </div>
                    </div>
                    <div class="dg-zumra-project-main__foot">
                        <div class="dg-zumra-project-progress">
                            <h3>▣ Progression actuelle</h3>
                            <p>Phase : {{ $projectPhase }}</p>
                            <div class="dg-zumra-project-progress__row">
                                <span class="dg-zumra-project-progress__track"><span class="dg-zumra-project-progress__fill" style="width: {{ $projectProgress ?? 0 }}%"></span></span>
                                <span class="dg-zumra-project-progress__value">{{ $projectProgress === null ? 'Non mesurée' : $projectProgress.'%' }}</span>
                            </div>
                        </div>
                        <div class="dg-zumra-project-next">
                            <h3>⚑ Prochaine étape</h3>
                            <p>{{ $nextStep }}</p>
                            <a class="dg-zumra-world-button" href="{{ $projectHref }}">{{ $projectAction }}</a>
                        </div>
                    </div>
                </section>

                <section class="dg-zumra-world-kpis" aria-label="Situation de la ZUMRA">
                    <div class="dg-zumra-world-kpi"><strong>♙ {{ $memberCount }}</strong><span>Membre{{ $memberCount === 1 ? '' : 's' }}</span></div>
                    <div class="dg-zumra-world-kpi"><strong>▰ {{ $derivedProjects->count() }}</strong><span>Autre{{ $derivedProjects->count() === 1 ? '' : 's' }} projet{{ $derivedProjects->count() === 1 ? '' : 's' }}</span></div>
                    <div class="dg-zumra-world-kpi"><strong>◎ {{ $groupNeeds->count() }}</strong><span>Besoin{{ $groupNeeds->count() === 1 ? '' : 's' }}</span></div>
                    <div class="dg-zumra-world-kpi"><strong>✓ {{ $groupMissions->count() }}</strong><span>Mission{{ $groupMissions->count() === 1 ? '' : 's' }}</span></div>
                    <div id="evenements" class="dg-zumra-world-kpi"><strong>▣ {{ $groupEvents->count() }}</strong><span>Événement{{ $groupEvents->count() === 1 ? '' : 's' }}</span></div>
                </section>

                <section id="a-faire" class="dg-zumra-world-card dg-zumra-world-now">
                    <h2>▤ À faire maintenant</h2>
                    <div class="dg-zumra-world-task-list">
                        @if ($canSetCharter)
                            <div class="dg-zumra-world-task">
                                <span class="dg-zumra-world-task__icon">▤</span>
                                <div><strong>Rédiger la charte interne</strong><p>Définissez ensemble les valeurs, règles et engagements spécifiques à votre ZUMRA.</p></div>
                                <a class="dg-zumra-world-button" href="#charte">Rédiger</a>
                            </div>
                        @endif
                        @if (!$primaryProject)
                            <div class="dg-zumra-world-task">
                                <span class="dg-zumra-world-task__icon">▣</span>
                                <div><strong>Créer le premier projet</strong><p>Formalisez le projet autour duquel cette ZUMRA veut travailler et progresser.</p></div>
                                <a class="dg-zumra-world-button" href="{{ route('projects.create', ['group' => $group->public_reference]) }}">Créer un projet</a>
                            </div>
                        @endif
                        @if ($memberCount <= 1 && $isLeader)
                            <div class="dg-zumra-world-task">
                                <span class="dg-zumra-world-task__icon">♙</span>
                                <div><strong>Inviter vos premiers membres</strong><p>Commencez à constituer une communauté où chacun peut apprendre, progresser puis contribuer.</p></div>
                                <a class="dg-zumra-world-button" href="{{ route('people.index') }}">Explorer les personnes</a>
                            </div>
                        @endif
                        @if (!$canSetCharter && $primaryProject && $memberCount > 1 && !$collectivePriority)
                            <div class="dg-zumra-world-task">
                                <span class="dg-zumra-world-task__icon">✓</span>
                                <div><strong>Continuer à faire progresser la ZUMRA</strong><p>Le prochain geste utile dépend maintenant de l’apprentissage des membres, de l’activité des projets et des besoins réels.</p></div>
                                <a class="dg-zumra-world-button" href="{{ $projectHref }}">Voir le projet</a>
                            </div>
                        @endif
                    </div>

                    @if ($canSetCharter)
                        <div id="charte" style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--dg-border)">
                            <h3>Charte interne de {{ $group->name }}</h3>
                            <form method="POST" action="{{ route('zumra.groups.charter.set', $group) }}" style="display:grid;gap:.65rem;margin-top:.65rem">
                                @csrf
                                <textarea name="internal_charter" rows="5" minlength="80" maxlength="6000" required placeholder="Écrivez ici les règles et engagements propres à cette ZUMRA…" style="width:100%;border:1px solid var(--dg-border);border-radius:.7rem;padding:.8rem;font:inherit"></textarea>
                                <div><button class="dg-zumra-world-button dg-zumra-world-button--solar" type="submit">Enregistrer la charte</button></div>
                            </form>
                        </div>
                    @endif
                </section>

                @if ($myPendingRoleProposal)
                    <section class="dg-zumra-world-card">
                        <h2>Une responsabilité vous est proposée</h2>
                        <p>{{ \App\Models\ZumraGroupRole::LABELS[$myPendingRoleProposal->role] ?? $myPendingRoleProposal->role }} · accepter reste entièrement votre choix.</p>
                        <form method="POST" action="{{ route('zumra.groups.roles.accept', [$group, $myPendingRoleProposal->role]) }}">@csrf<button class="dg-zumra-world-button dg-zumra-world-button--solar" type="submit">Accepter cette responsabilité</button></form>
                    </section>
                @endif

                @if ($isLeader)
                    <section id="demandes" class="dg-zumra-world-card">
                        <h2>Demandes à examiner</h2>
                        @forelse ($pendingRequests as $requestMembership)
                            <div class="dg-zumra-world-task">
                                <span class="dg-zumra-world-task__icon">♙</span>
                                <div><strong>Une personne souhaite rejoindre la ZUMRA</strong><p>La demande attend votre décision.</p></div>
                                <form method="POST" action="{{ route('zumra.groups.requests.approve', [$group, $requestMembership]) }}">@csrf<button class="dg-zumra-world-button" type="submit">Accepter</button></form>
                            </div>
                        @empty
                            <p>Aucune demande en attente.</p>
                        @endforelse
                    </section>
                @endif

                <section class="dg-zumra-world-triple">
                    <article id="besoins" class="dg-zumra-world-card">
                        <h2>◎ Besoins actuels</h2>
                        @if ($groupNeeds->isEmpty())
                            <div class="dg-zumra-world-empty">
                                <span class="dg-zumra-world-empty__plus">+</span>
                                <strong>Aucun besoin pour le moment</strong>
                                <p>Ajoutez un besoin pour mobiliser des ressources et des compétences.</p>
                                @if($isActiveMember)<a class="dg-zumra-world-button" href="{{ route('needs.create', ['group' => $group->public_reference]) }}">Ajouter un besoin</a>@endif
                            </div>
                        @else
                            <div class="dg-zumra-world-list">@foreach($groupNeeds->take(4) as $need)<a href="{{ route('needs.show', $need) }}"><span>{{ $need->title }}</span><span>→</span></a>@endforeach</div>
                        @endif
                    </article>

                    <article class="dg-zumra-world-card">
                        <h2>▰ Autres projets</h2>
                        @if ($derivedProjects->isEmpty())
                            <div class="dg-zumra-world-empty">
                                <span class="dg-zumra-world-empty__plus">+</span>
                                <strong>Aucun autre projet pour le moment</strong>
                                <p>Une ZUMRA peut très bien avancer avec un seul projet. Un autre projet naît lorsqu’un besoin réel ou une nouvelle spécialisation le justifie.</p>
                                @if($isActiveMember)
                                    <a class="dg-zumra-world-button" href="{{ route('projects.create', ['group' => $group->public_reference]) }}">{{ $primaryProject ? 'Proposer un projet' : 'Créer un projet' }}</a>
                                @endif
                            </div>
                        @else
                            <div class="dg-zumra-world-list">@foreach($derivedProjects->take(4) as $project)<a href="{{ route('projects.show', $project) }}"><span>{{ $project->name }}</span><span>→</span></a>@endforeach</div>
                            @if($isActiveMember)<a class="dg-zumra-world-button" style="margin-top:.75rem" href="{{ route('projects.create', ['group' => $group->public_reference]) }}">Proposer un projet</a>@endif
                        @endif
                    </article>

                    <article id="missions" class="dg-zumra-world-card">
                        <h2>✓ Missions</h2>
                        @if ($groupMissions->isEmpty())
                            <div class="dg-zumra-world-empty">
                                <span class="dg-zumra-world-empty__plus">+</span>
                                <strong>Aucune mission pour le moment</strong>
                                <p>Les missions apparaîtront ici lorsqu’elles seront réellement rattachées à cette ZUMRA.</p>
                            </div>
                        @else
                            <div class="dg-zumra-world-list">@foreach($groupMissions->take(4) as $mission)<a href="{{ route('missions.show', $mission) }}"><span>{{ $mission->title }}</span><span>→</span></a>@endforeach</div>
                        @endif
                    </article>
                </section>
            </main>

            <aside class="dg-zumra-world-right">
                <section class="dg-zumra-world-card dg-zumra-world-action">
                    <h2>🚀 Agir maintenant</h2>
                    <p>Votre ZUMRA grandit par la formation et l’action. Invitez des personnes, apprenez ensemble, faites émerger des projets et identifiez des besoins réels.</p>
                    <div class="dg-zumra-world-action__buttons">
                        @if ($isLeader)<a class="dg-zumra-world-button dg-zumra-world-button--primary" href="{{ route('people.index') }}">♙ Inviter des membres</a>@endif
                        @if ($isActiveMember)<a class="dg-zumra-world-button" href="{{ route('projects.create', ['group' => $group->public_reference]) }}">▣ {{ $primaryProject ? 'Proposer un projet' : 'Créer un projet' }}</a>@endif
                        @if ($isActiveMember)<a class="dg-zumra-world-button" href="{{ route('needs.create', ['group' => $group->public_reference]) }}">◎ Ajouter un besoin</a>@endif
                        <span class="dg-zumra-world-button" aria-disabled="true" title="Le mini-fil ZUMRA sera raccordé dans une étape dédiée">✎ Créer une publication</span>
                    </div>
                </section>

                <section id="membres" class="dg-zumra-world-card dg-zumra-world-members">
                    <div class="dg-zumra-world-members__head"><h2>♙ Membres</h2><span class="dg-zumra-world-small-link">{{ $memberCount }} au total</span></div>
                    <div class="dg-zumra-world-member">
                        <span class="dg-zumra-world-avatar">{{ $leaderInitial }}</span>
                        <div><strong>{{ $leaderLabel }} <span class="dg-zumra-world-member__role">Fondateur</span></strong><small>Membre depuis {{ $group->created_at?->translatedFormat('M Y') }}</small></div>
                    </div>
                    @if($isLeader)<a class="dg-zumra-world-button dg-zumra-world-button--full" href="{{ route('people.index') }}" style="margin-top:.8rem">♙ Inviter des membres</a>@endif
                </section>

                <section id="activite" class="dg-zumra-world-card dg-zumra-world-activity">
                    <div class="dg-zumra-world-activity__head"><h2>⚑ Activité récente</h2><span class="dg-zumra-world-small-link">Réelle</span></div>
                    <div class="dg-zumra-world-activity-item">
                        <span class="dg-zumra-world-avatar">{{ $leaderInitial }}</span>
                        <div><p><strong>{{ $leaderLabel }}</strong> a créé la ZUMRA <strong>{{ $group->name }}</strong>.</p><small>{{ $group->created_at?->diffForHumans() }}</small></div>
                    </div>
                    @if($primaryProject)
                        <div class="dg-zumra-world-activity-item"><span class="dg-zumra-world-avatar">P</span><div><p>Le projet <strong>{{ $primaryProject->name }}</strong> est maintenant rattaché à cette ZUMRA.</p><small>{{ $primaryProject->created_at?->diffForHumans() }}</small></div></div>
                    @endif
                </section>

                <section class="dg-zumra-world-card dg-zumra-world-quote"><p>« Les grandes réalisations naissent de communautés qui apprennent, travaillent et croient en un même but. »</p><strong>— GAMAD</strong></section>
            </aside>
        </div>

        <section class="dg-zumra-world-reminder">
            <span aria-hidden="true">🌱</span>
            <div><strong>Rappel</strong><p>Une ZUMRA peut très bien avancer avec un seul projet. D’autres projets naissent lorsqu’un besoin réel ou une nouvelle spécialisation émerge.</p></div>
            <a class="dg-zumra-world-button" href="#projets">{{ $primaryProject ? 'Voir le projet' : 'Créer un projet' }}</a>
        </section>
    </div>
</x-layouts.member>
