{{--
    Fiche ZUMRA — gouvernance, capacité collective, charte, demandes/invitations. Respect absolu
    des cinq responsabilités distinctes, aucune nomination automatique (CAP-011).
--}}
<x-layouts.portal title="{{ $group->name }} — ZUMRA">
    <x-dg.shell current="zumra" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page">
            @if(session('status'))
                <div class="dg-band" style="margin-bottom:20px">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="dg-band" style="margin-bottom:20px;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>
            @endif

            @php
                $acceptedRoles = $roles->where('status', 'ACCEPTED')->count();
                $nextEvent = $groupEvents->sortBy('scheduled_at')->first();
                $modeLabel = match($group->participation_mode) { 'PHYSICAL' => 'Physique', 'DIGITAL' => 'Numérique', default => 'Hybride' };
                $stateLabel = match($group->state) { 'CONSTITUTING' => 'En constitution', 'READY' => 'Prête à valider', 'VALIDATED' => 'Validée', 'ACTIVE' => 'Active', 'WARNED' => 'Avertie', 'SUSPENDED' => 'Suspendue', 'REHABILITATING' => 'En réhabilitation', default => $group->state };
                $initial = mb_strtoupper(mb_substr($group->name, 0, 1));
            @endphp

            <div class="zs-layout">
                <aside class="zs-left" aria-label="Navigation de l’espace ZUMRA">
                    <a href="{{ route('zumra.index') }}" class="zs-back">← Retour au monde ZUMRA</a>
                    <nav class="zs-nav">
                        <p class="zs-eyebrow">Espace ZUMRA</p>
                        <a class="is-active" href="#accueil">⌂ <span>Accueil</span></a>
                        <a href="#fil">◫ <span>Fil d’activités</span></a>
                        <a href="#conversation">▢ <span>Discussions</span></a>
                        <a href="#transmissions">◈ <span>Transmissions</span><b>{{ $groupMissions->count() }}</b></a>
                        <a href="#projets">□ <span>Projets</span><b>{{ $groupProjects->count() }}</b></a>
                        <a href="#besoins">◎ <span>Besoins</span><b>{{ $groupNeeds->count() }}</b></a>
                        <a href="#membres">♧ <span>Membres</span><b>{{ $group->active_member_count }}</b></a>
                        <a href="#activites">▣ <span>Activités</span><b>{{ $activities->count() + 1 }}</b></a>
                        <a href="#evenements">◫ <span>Événements</span><b>{{ $groupEvents->count() }}</b></a>
                        <a href="#ressources">▱ <span>Ressources</span></a>
                        <a href="#gouvernance">⬡ <span>À propos & Gouvernance</span></a>
                    </nav>
                    <section class="zs-help">
                        <h2>Besoin d’aide ?</h2>
                        <p>Le guide est là pour vous accompagner à chaque étape.</p>
                        <a href="{{ route('people.index') }}">Contacter un guide</a>
                    </section>
                    <section class="zs-status">
                        <p class="zs-eyebrow">Statut de la ZUMRA</p>
                        <strong><i></i>{{ $stateLabel }}</strong>
                        <span>{{ $acceptedRoles }}/5 responsabilités acceptées</span>
                    </section>
                </aside>

                <main class="zs-main" id="accueil">
                    <section class="zs-hero">
                        <div class="zs-avatar" aria-hidden="true">{{ $initial }}<i></i></div>
                        <div class="zs-hero-copy">
                            <div class="zs-tags"><span>{{ $group->domain }}</span><span>{{ $group->maturity === 'ESTABLISHED' ? 'Établie' : 'Émergente' }}</span></div>
                            <h1>{{ $group->name }}</h1>
                            <p>{{ $group->founding_objective }}</p>
                            <div class="zs-meta"><span>♧ {{ $modeLabel }}</span><span>⌖ {{ filled($group->location) ? $group->location : 'Lieu à préciser' }}</span><span>♙ {{ $group->active_member_count }} membre{{ $group->active_member_count > 1 ? 's' : '' }}</span></div>
                        </div>
                        <div class="zs-hero-state"><strong>{{ $group->active_member_count }}</strong><span>membre{{ $group->active_member_count > 1 ? 's' : '' }} actif{{ $group->active_member_count > 1 ? 's' : '' }}</span><small>{{ $acceptedRoles }}/5 responsabilités<br>acceptées</small></div>
                    </section>

                    <section class="zs-quick" aria-labelledby="zs-quick-title">
                        <h2 id="zs-quick-title">Que voulez-vous faire aujourd’hui ?</h2>
                        <div>
                            <a href="{{ route('comments.zumra-activity', $group) }}"><i>✎</i><span>Publier<br>sur le fil</span></a>
                            <a href="#conversation"><i>□</i><span>Lancer une<br>discussion</span></a>
                            <a href="{{ route('projects.create', ['group' => $group->public_reference]) }}"><i>◇</i><span>Créer un<br>projet</span></a>
                            <a href="{{ route('needs.create', ['zumra_group' => $group->public_reference]) }}"><i>♧</i><span>Déclarer un<br>besoin</span></a>
                            @if($membership?->status === 'ACTIVE')
                                <a href="{{ route('shares.group', $group) }}"><i>▱</i><span>Partager une<br>ressource</span></a>
                            @else
                                <span class="is-disabled" title="Réservé aux membres actifs"><i>▱</i><span>Partager une<br>ressource</span></span>
                            @endif
                        </div>
                    </section>

                    <div class="zs-center-grid">
                        <section class="zs-panel zs-feed" id="fil">
                            <div class="zs-panel-head"><h2>Fil d’activités</h2><a href="{{ route('comments.zumra-activity', $group) }}">Voir l’activité →</a></div>
                            @forelse($groupProjects->take(2) as $project)
                                <article><i class="zs-feed-icon">P</i><div><p><strong>Un projet porté par la ZUMRA</strong><span>{{ $project->created_at?->diffForHumans() }}</span></p><h3>{{ $project->name }}</h3><p>{{ \Illuminate\Support\Str::limit($project->summary ?? '', 150) }}</p><a href="{{ route('projects.show', $project) }}">Voir le projet →</a></div></article>
                            @empty
                                @foreach($groupNeeds->take(2) as $need)
                                    <article><i class="zs-feed-icon">B</i><div><p><strong>Un besoin exprimé par la ZUMRA</strong><span>{{ $need->created_at?->diffForHumans() }}</span></p><h3>{{ $need->title }}</h3><p>{{ \Illuminate\Support\Str::limit($need->context ?? $need->description ?? '', 150) }}</p><a href="{{ route('needs.show', $need) }}">Voir le besoin →</a></div></article>
                                @endforeach
                            @endforelse
                            @foreach($groupMissions->take(2) as $mission)
                                <article id="transmissions"><i class="zs-feed-icon">M</i><div><p><strong>Une mission de la ZUMRA</strong><span>{{ $mission->created_at?->diffForHumans() }}</span></p><h3>{{ $mission->title }}</h3><p>{{ \Illuminate\Support\Str::limit($mission->description, 150) }}</p><a href="{{ route('missions.show', $mission) }}">Voir la mission →</a></div></article>
                            @endforeach
                            @if($groupProjects->isEmpty() && $groupNeeds->isEmpty() && $groupMissions->isEmpty())
                                <div class="zs-empty"><strong>L’activité commence ici.</strong><p>Cette ZUMRA ne porte encore aucun Projet ni Besoin visible.</p><a href="{{ route('comments.zumra-activity', $group) }}">Publier une première contribution →</a></div>
                            @endif
                        </section>

                        <div class="zs-action-stack">
                            <section class="zs-panel" id="projets"><div class="zs-panel-head"><h2>Actions en cours</h2></div>
                                @if($collectivePriority)<a class="zs-action" href="{{ $collectivePriority['primary']['href'] }}"><i>◎</i><span><strong>{{ $collectivePriority['heading'] }}</strong><small>{{ $collectivePriority['body'] }}</small></span>→</a>@endif
                                @foreach($groupProjects->take(2) as $project)<a class="zs-action" href="{{ route('projects.show', $project) }}"><i>◇</i><span><strong>{{ $project->name }}</strong><small>Projet porté par la ZUMRA</small></span>→</a>@endforeach
                                @if(! $collectivePriority && $groupProjects->isEmpty())<p class="zs-muted">Aucune action collective visible pour le moment.</p>@endif
                            </section>
                            <section class="zs-panel" id="besoins"><div class="zs-panel-head"><h2>Besoins portés</h2><a href="{{ route('needs.index') }}">Voir tout →</a></div>
                                @forelse($groupNeeds->take(2) as $need)<a class="zs-action" href="{{ route('needs.show', $need) }}"><i>◎</i><span><strong>{{ $need->title }}</strong><small>Besoin visible de la ZUMRA</small></span>→</a>@empty<p class="zs-muted">Aucun besoin visible pour le moment.</p>@endforelse
                            </section>
                            <section class="zs-panel" id="evenements"><div class="zs-panel-head"><h2>Prochain événement</h2><a href="{{ route('community-events.zumra.index', $group) }}">Voir tout →</a></div>
                                @if($nextEvent)<div class="zs-event"><time><b>{{ $nextEvent->scheduled_at?->format('d') }}</b>{{ mb_strtoupper($nextEvent->scheduled_at?->translatedFormat('M') ?? '') }}</time><div><strong>{{ $nextEvent->title }}</strong><span>{{ $nextEvent->scheduled_at?->translatedFormat('l d F · H\hi') }}</span><span>{{ $nextEvent->location ?: $modeLabel }}</span><a href="{{ route('community-events.show', $nextEvent) }}">Voir l’événement</a></div></div>@else<p class="zs-muted">Aucun événement programmé.</p>@endif
                            </section>
                            <section class="zs-panel" id="membres"><div class="zs-panel-head"><h2>Membres</h2><a href="#gouvernance">Voir la gouvernance →</a></div><div class="zs-members">@foreach($roles->where('status', 'ACCEPTED')->take(5) as $role) @php($profile = $roleProfiles->get($role->core_identity_reference)) <span title="{{ $profile?->discovery_display_name ?: 'Membre attesté' }}">{{ mb_strtoupper(mb_substr($profile?->discovery_display_name ?: 'M', 0, 1)) }}</span> @endforeach @if($acceptedRoles === 0)<small>Aucune responsabilité encore acceptée.</small>@endif</div></section>
                        </div>
                    </div>
                </main>

                <aside class="zs-right">
                    <section class="zs-panel"><h2>◎ Notre intention</h2><p>{{ $group->founding_objective }}</p>@if($isLeader)<a href="#gouvernance">Modifier notre intention →</a>@endif</section>
                    <section class="zs-panel" id="activites"><div class="zs-panel-head"><h2>Activité principale</h2></div><span class="zs-domain">{{ $group->domain }}</span><p>{{ $group->domain }}</p></section>
                    <section class="zs-panel"><div class="zs-panel-head"><h2>Activités dérivées</h2></div>@forelse($activities as $activity)<div class="zs-derived"><i></i><span><strong>{{ $activity->label }}</strong><small>{{ $activity->relation_to_principal }}</small></span></div>@empty<p class="zs-muted">Aucune activité dérivée déclarée.</p>@endforelse</section>
                    <section class="zs-panel" id="conversation"><h2>Canaux rapides</h2><div class="zs-channels"><span># <b>général</b></span><span># <b>projets</b></span><span># <b>annonces</b></span></div><p class="zs-muted">Ces entrées préparent l’organisation future des échanges. La conversation actuelle reste unique.</p>@if($membership?->status === 'ACTIVE')<form method="POST" action="{{ route('messages.zumra', $group) }}">@csrf<button class="zs-outline" type="submit">Ouvrir la conversation →</button></form>@else<span class="zs-honest">Réservé aux membres actifs</span>@endif</section>
                    <section class="zs-panel" id="ressources"><h2>Ressources</h2><p class="zs-muted">Les partages utiles de cette ZUMRA apparaîtront ici progressivement.</p>@if($membership?->status === 'ACTIVE')<a href="{{ route('shares.group', $group) }}">Voir les partages utiles →</a>@endif</section>
                    <section class="zs-invite"><h2>Invitation</h2><p>Invitez d’autres personnes à rejoindre votre ZUMRA et à contribuer à votre mission.</p>@if($isLeader)<a href="#gouvernance">Inviter un adhérent</a>@else<span>Le premier responsable peut envoyer les invitations.</span>@endif</section>
                </aside>
            </div>

            <section class="zs-path" aria-label="Chemin collectif">
                <div><h2>Notre chemin ensemble</h2><p>Chaque ZUMRA avance par l’action collective, la transmission et la persévérance.</p></div>
                <ol><li><i>♧</i><span><strong>1. Se connaître</strong>Apprenons à nous connaître et construisons la confiance.</span></li><li><i>⌕</i><span><strong>2. Comprendre</strong>Clarifions notre besoin et notre direction.</span></li><li><i>↗</i><span><strong>3. Agir</strong>Passons à l’action avec nos projets.</span></li><li><i>▥</i><span><strong>4. Grandir</strong>Mesurons nos impacts et élargissons notre influence.</span></li></ol>
            </section>

            <details class="zs-governance" id="gouvernance">
                <summary><span><strong>À propos & Gouvernance</strong><small>Charte, responsabilités, adhésions, capacités et cadre complet de la ZUMRA</small></span><b>Ouvrir le cadre complet ＋</b></summary>
                <div class="zs-governance-body">

            <x-dg.deep style="margin-bottom:16px">
                <div style="display:flex;justify-content:space-between;gap:28px;flex-wrap:wrap">
                    <div style="display:flex;flex-direction:column;gap:12px;max-width:56ch">
                        <x-dg.badge tone="on-deep">{{ $group->domain }} · {{ $group->maturity === 'ESTABLISHED' ? 'Établie' : 'Émergente' }}</x-dg.badge>
                        <h1 class="dg-display" style="font-size:44px;line-height:1.06;color:var(--dg-on-deep-title)">{{ $group->name }}</h1>
                        <p style="margin:0;font-size:15px;line-height:1.7;color:var(--dg-on-deep-text)">{{ $group->founding_objective }}</p>
                        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:4px">
                            <x-dg.badge tone="on-deep">{{ match($group->participation_mode) { 'PHYSICAL' => 'Physique', 'DIGITAL' => 'Numérique', default => 'Hybride' } }}</x-dg.badge>
                            @if(filled($group->location))
                                <x-dg.badge tone="on-deep">{{ $group->location }}</x-dg.badge>
                            @endif
                            <x-dg.badge tone="on-deep">{{ match($group->state) { 'CONSTITUTING' => 'En constitution', 'READY' => 'Prête à valider', 'VALIDATED' => 'Validée', 'ACTIVE' => 'Active', 'WARNED' => 'Avertie', 'SUSPENDED' => 'Suspendue', 'REHABILITATING' => 'En réhabilitation', default => $group->state } }}</x-dg.badge>
                        </div>
                        @if(filled($group->welcome_capacity))
                            <p style="margin:6px 0 0;font-size:13px;color:var(--dg-on-deep-muted)">{{ \App\Models\ZumraGroup::WELCOME_CAPACITIES[$group->welcome_capacity] ?? $group->welcome_capacity }}</p>
                        @endif
                    </div>
                    <div style="text-align:right;flex:none">
                        <div style="font-family:var(--dg-font-display);font-size:26px;color:var(--dg-on-deep-title)">{{ $group->active_member_count }}</div>
                        <span style="font-size:13px;color:var(--dg-on-deep-muted)">membre{{ $group->active_member_count > 1 ? 's' : '' }} actif{{ $group->active_member_count > 1 ? 's' : '' }}</span>
                        <div class="dg-meta" style="color:var(--dg-on-deep-muted);margin-top:6px">{{ $roles->where('status', 'ACCEPTED')->count() }}/5 responsabilités acceptées</div>
                    </div>
                </div>
            </x-dg.deep>

            <details class="dg-disclosure" style="margin-bottom:20px">
                <summary><span class="dg-disclosure__mark">i</span> Aucune adhésion automatique, aucun rôle attribué en silence.</summary>
                <div class="dg-disclosure__body">Chaque responsabilité est acceptée explicitement par la personne qui la porte. Un siège vacant reste visible comme vacant — jamais rempli par un profil fictif. Rejoindre cette ZUMRA se fait par demande approuvée ou invitation acceptée, jamais automatiquement.</div>
            </details>

            @if($myPendingRoleProposal)
                {{-- UIUX-002 — décision #4 : découvrir/comprendre/accepter une responsabilité
                     proposée personnellement. Aucun refus n'est proposé ici : cette transition
                     n'existe pas dans le métier (ZumraGroupRole ne connaît que
                     VACANT → PROPOSED → ACCEPTED). --}}
                <div class="dg-card" style="margin-bottom:20px;border-color:var(--dg-saffron)">
                    <x-dg.label tone="saffron">Une responsabilité vous est proposée</x-dg.label>
                    <h2 class="dg-display" style="font-size:20px;margin-top:8px">{{ \App\Models\ZumraGroupRole::LABELS[$myPendingRoleProposal->role] ?? $myPendingRoleProposal->role }} — {{ $group->name }}</h2>
                    <p class="dg-body" style="margin-top:6px">Cette responsabilité fondatrice vous a été proposée par un responsable de cette ZUMRA. Accepter reste entièrement votre choix.</p>
                    <form method="POST" action="{{ route('zumra.groups.roles.accept', [$group, $myPendingRoleProposal->role]) }}" style="margin-top:14px">
                        @csrf
                        <button type="submit" class="dg-btn dg-btn--saffron">Accepter cette responsabilité</button>
                    </form>
                </div>
            @endif

            @if($collectivePriority)
                <div class="dg-card" style="margin-bottom:20px;border-color:var(--dg-saffron)">
                    <x-dg.label tone="saffron">Aujourd’hui — une seule chose compte pour cette ZUMRA</x-dg.label>
                    <h2 class="dg-display" style="font-size:20px;margin-top:8px">{{ $collectivePriority['heading'] }}</h2>
                    <p class="dg-body" style="margin-top:6px">{{ $collectivePriority['body'] }}</p>
                    <div style="margin-top:14px">
                        <a href="{{ $collectivePriority['primary']['href'] }}" class="dg-btn dg-btn--saffron">{{ $collectivePriority['primary']['label'] }}</a>
                    </div>
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div style="display:flex;flex-direction:column;gap:20px;min-width:0">
                    <x-dg.card>
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <x-dg.label>Gouvernance fondatrice</x-dg.label>
                                <h2 class="dg-display" style="font-size:20px;margin-top:6px">Cinq responsabilités, cinq personnes.</h2>
                            </div>
                            <x-dg.badge tone="{{ $roles->where('status', 'ACCEPTED')->count() === 5 ? 'action' : 'neutral' }}">{{ $roles->where('status', 'ACCEPTED')->count() === 5 ? 'Gouvernance complète' : 'Constitution en cours' }}</x-dg.badge>
                        </div>
                        <div style="margin-top:16px;display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px">
                            @foreach($roles as $role)
                                @php($profile = $roleProfiles->get($role->core_identity_reference))
                                <x-dg.seat
                                    :label="\App\Models\ZumraGroupRole::LABELS[$role->role]"
                                    :filled="$role->status === 'ACCEPTED'"
                                    :holder="$profile?->discovery_display_name ?: ($role->status === 'ACCEPTED' ? 'Membre attesté' : null)"
                                />
                            @endforeach
                        </div>
                    </x-dg.card>

                    <x-dg.card>
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <x-dg.label>Capacité collective</x-dg.label>
                                <h2 class="dg-display" style="font-size:20px;margin-top:6px">{{ $collectiveCapabilitySettings['section_title'] }}</h2>
                            </div>
                            <span class="dg-meta">{{ $collectiveCapabilities->count() }} force{{ $collectiveCapabilities->count() > 1 ? 's' : '' }} mobilisable{{ $collectiveCapabilities->count() > 1 ? 's' : '' }}</span>
                        </div>
                        <p class="dg-body" style="margin-top:10px">{{ $collectiveCapabilitySettings['section_intro'] }}</p>
                        @if($collectiveCapabilities->isEmpty())
                            <x-dg.empty style="margin-top:14px">
                                <span>{{ $collectiveCapabilitySettings['empty_text'] }}</span>
                            </x-dg.empty>
                        @else
                            <div style="display:flex;flex-direction:column;gap:10px;margin-top:14px">
                                @foreach($collectiveCapabilities as $capability)
                                    <div class="dg-note">
                                        <strong style="color:var(--dg-ink)">{{ $capability->label }}</strong>
                                        <p style="margin:4px 0 0">{{ $capability->member_count }} membre{{ $capability->member_count > 1 ? 's' : '' }} volontaire{{ $capability->member_count > 1 ? 's' : '' }} et mobilisable{{ $capability->member_count > 1 ? 's' : '' }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <p class="dg-hint" style="margin-top:14px">Le profil collectif agrège des capacités consenties. Il ne remplace pas les personnes et n’affiche aucune identité privée.</p>
                    </x-dg.card>

                    <div class="grid gap-5 lg:grid-cols-[1.3fr_1fr]" style="align-items:start">
                        <x-dg.card style="padding:0;overflow:hidden">
                            <div style="padding:22px 24px 14px;display:flex;align-items:baseline;justify-content:space-between">
                                <x-dg.label>Ce que porte cette ZUMRA</x-dg.label>
                                <span class="dg-meta">{{ $groupProjects->count() }} projet{{ $groupProjects->count() > 1 ? 's' : '' }} · {{ $groupNeeds->count() }} besoin{{ $groupNeeds->count() > 1 ? 's' : '' }}</span>
                            </div>
                            @if($groupProjects->isEmpty() && $groupNeeds->isEmpty())
                                <div style="padding:0 24px 20px">
                                    <x-dg.empty><span>Cette ZUMRA ne porte encore aucun Projet ni Besoin visible.</span></x-dg.empty>
                                </div>
                            @else
                                <div style="padding:0 24px 8px;border-top:1px solid var(--dg-line-inner)">
                                    @foreach($groupProjects as $project)
                                        <a href="{{ route('projects.show', $project) }}" style="display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 0;color:inherit;border-bottom:1px solid var(--dg-line-inner)">
                                            <span style="display:flex;align-items:center;gap:10px;min-width:0"><x-dg.badge tone="project">Projet</x-dg.badge><strong style="font-size:14px;color:var(--dg-forest);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $project->name }}</strong></span>
                                            <span class="dg-meta" style="flex:none">{{ ['PROPOSED'=>'Proposé','ADOPTED'=>'Adopté','IN_PROGRESS'=>'En action','COMPLETED'=>'Réalisé'][$project->status] ?? $project->status }}</span>
                                        </a>
                                    @endforeach
                                    @foreach($groupNeeds as $need)
                                        <a href="{{ route('needs.show', $need) }}" style="display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 0;color:inherit;border-bottom:1px solid var(--dg-line-inner)">
                                            <span style="display:flex;align-items:center;gap:10px;min-width:0"><x-dg.badge tone="need">Besoin</x-dg.badge><strong style="font-size:14px;color:var(--dg-forest);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $need->title }}</strong></span>
                                            <span class="dg-meta" style="flex:none">{{ ['PROPOSED'=>'Proposé','OPEN'=>'Ouvert','IN_PROGRESS'=>'En cours','RESOLVED'=>'Résolu'][$need->status] ?? $need->status }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                            @if($membership?->status === \App\Models\ZumraGroupMembership::STATUS_ACTIVE)
                                {{-- UIUX-007 — CTA contextuel : même autorité que NeedService/ProjectService
                                     (tout membre actif peut proposer, jamais une autorité nouvelle). --}}
                                <div style="padding:12px 24px;border-top:1px solid var(--dg-line-inner);display:flex;gap:16px;flex-wrap:wrap">
                                    <a href="{{ route('needs.create', ['group' => $group->public_reference]) }}" class="dg-meta" style="color:var(--dg-copper);font-weight:600">+ Créer un besoin pour cette ZUMRA</a>
                                    <a href="{{ route('projects.create', ['group' => $group->public_reference]) }}" class="dg-meta" style="color:var(--dg-copper);font-weight:600">+ Proposer un projet pour cette ZUMRA</a>
                                </div>
                            @endif
                        </x-dg.card>

                        <x-dg.card id="charte">
                            <x-dg.label>Charte interne</x-dg.label>
                            @if(filled($group->internal_charter))
                                <p class="dg-body" style="margin-top:10px">{{ \Illuminate\Support\Str::limit($group->internal_charter, 220) }}</p>
                                @if(mb_strlen($group->internal_charter) > 220)
                                    <details style="margin-top:8px">
                                        <summary style="cursor:pointer;font-size:12px;color:var(--dg-faint);font-weight:600">Lecture intégrale ⌄</summary>
                                        <div class="dg-body" style="margin-top:10px;white-space:pre-line">{{ $group->internal_charter }}</div>
                                    </details>
                                @endif
                            @elseif($canSetCharter)
                                <p class="dg-hint" style="margin-top:8px">Pas encore requise pour naître — nécessaire pour rendre cette ZUMRA prête à valider.</p>
                                <form method="POST" action="{{ route('zumra.groups.charter.set', $group) }}" style="margin-top:12px">
                                    @csrf
                                    <textarea name="internal_charter" class="dg-textarea" rows="6" minlength="80" maxlength="6000" placeholder="Précisez la mission, le respect mutuel, la hiérarchie, les décisions, l’admission, le départ et l’exclusion." required></textarea>
                                    <button type="submit" class="dg-btn dg-btn--saffron" style="margin-top:10px">Enregistrer la charte</button>
                                </form>
                            @else
                                <p class="dg-hint" style="margin-top:8px">Pas encore rédigée.</p>
                            @endif
                        </x-dg.card>

                        <x-dg.card>
                            <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px">
                                <x-dg.label>Activités dérivées</x-dg.label>
                                <span class="dg-meta">Activité principale : {{ $group->domain }}</span>
                            </div>
                            <p class="dg-hint" style="margin-top:8px">Des activités secondaires ou sous-activités, toujours rattachées à « {{ $group->domain }} ».</p>
                            @forelse($activities as $activity)
                                <div style="padding:10px 0;border-top:1px solid var(--dg-line-inner)">
                                    <strong style="font-size:14px;color:var(--dg-forest)">{{ $activity->label }}</strong>
                                    <p class="dg-body" style="margin-top:4px">{{ $activity->relation_to_principal }}</p>
                                </div>
                            @empty
                                <p class="dg-body" style="margin-top:8px">Aucune activité dérivée pour l’instant.</p>
                            @endforelse
                            @if($isLeader)
                                <form method="POST" action="{{ route('zumra.groups.activities.add', $group) }}" style="margin-top:14px;display:flex;flex-direction:column;gap:8px;border-top:1px solid var(--dg-line-inner);padding-top:14px">
                                    @csrf
                                    <input type="text" name="label" class="dg-input" maxlength="140" placeholder="Nom de l’activité dérivée" required>
                                    <textarea name="relation_to_principal" class="dg-textarea" rows="3" maxlength="600" placeholder="Comment dérive-t-elle de « {{ $group->domain }} » ?" required></textarea>
                                    <button type="submit" class="dg-btn dg-btn--quiet" style="align-self:flex-start">+ Ajouter une activité dérivée</button>
                                </form>
                            @endif
                        </x-dg.card>
                    </div>

                    @if(($isLeader || $membership?->status === \App\Models\ZumraGroupMembership::STATUS_ACTIVE) && ($groupMissions->isNotEmpty() || $groupEvents->isNotEmpty()))
                        {{-- UIUX-003 — décision #2 : Missions/Événements réels de cette ZUMRA, visibles
                             seulement pour un membre actif ou responsable (même autorité que les
                             services eux-mêmes exigent), jamais un catalogue global. --}}
                        <x-dg.card style="padding:0;overflow:hidden">
                            <div style="padding:22px 24px 14px;display:flex;align-items:baseline;justify-content:space-between">
                                <x-dg.label>Ce qui se passe dans cette ZUMRA</x-dg.label>
                                <span class="dg-meta">{{ $groupMissions->count() }} mission{{ $groupMissions->count() > 1 ? 's' : '' }} · {{ $groupEvents->count() }} événement{{ $groupEvents->count() > 1 ? 's' : '' }}</span>
                            </div>
                            <div style="padding:0 24px 8px;border-top:1px solid var(--dg-line-inner)">
                                @foreach($groupMissions as $mission)
                                    <a href="{{ route('missions.show', $mission) }}" style="display:flex;align-items:center;gap:10px;padding:12px 0;color:inherit;border-bottom:1px solid var(--dg-line-inner)">
                                        <x-dg.badge tone="neutral">Mission</x-dg.badge>
                                        <strong style="flex:1;min-width:0;font-size:14px;color:var(--dg-forest);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $mission->title }}</strong>
                                        <span class="dg-meta" style="flex:none">{{ \App\Models\Mission::STATUS_LABELS[$mission->status] ?? $mission->status }}</span>
                                    </a>
                                @endforeach
                                @foreach($groupEvents as $event)
                                    <a href="{{ route('community-events.show', $event) }}" style="display:flex;align-items:center;gap:10px;padding:12px 0;color:inherit;border-bottom:1px solid var(--dg-line-inner)">
                                        <x-dg.badge tone="saffron">Événement</x-dg.badge>
                                        <strong style="flex:1;min-width:0;font-size:14px;color:var(--dg-forest);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $event->title }}</strong>
                                        <span class="dg-meta" style="flex:none">{{ match($event->status) { 'CANCELLED' => 'Annulé', 'COMPLETED' => 'Tenu', default => 'À venir' } }} · {{ $event->scheduled_at->translatedFormat('d M') }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </x-dg.card>
                    @endif

                    @if($isLeader || $membership?->status === \App\Models\ZumraGroupMembership::STATUS_ACTIVE)
                        {{-- UIUX-005 — Partenariats réellement associés à cette ZUMRA (CAP-065),
                             même autorité que Missions/Événements ci-dessus. --}}
                        @if($groupPartnerships !== [])
                            <div>
                                <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:10px">
                                    <x-dg.label>Partenariats</x-dg.label>
                                    <span class="dg-meta">{{ count($groupPartnerships) }} partenariat{{ count($groupPartnerships) > 1 ? 's' : '' }}</span>
                                </div>
                                @foreach($groupPartnerships as $row)
                                    <div style="margin-bottom:12px"><x-dg.partnership-row :row="$row" /></div>
                                @endforeach
                            </div>
                        @endif

                        <x-dg.partnership-propose-form :organizations="$manageableOrganizations" :capabilities="$manageableOrganizationCapabilities" context-type="ZUMRA" :context-reference="$group->public_reference" />
                    @endif
                </div>

                <aside style="display:flex;flex-direction:column;gap:16px">
                    <x-dg.card tight>
                        <x-dg.label>Votre relation</x-dg.label>
                        <h2 class="dg-display" style="font-size:18px;margin-top:6px">{{ ! $membership ? 'Vous ne faites pas encore partie de cette ZUMRA.' : match($membership->status) { 'ACTIVE' => 'Vous êtes membre', 'INVITED' => 'Vous êtes invité', 'REQUESTED' => 'Votre demande est étudiée', 'LEFT' => 'Vous avez quitté cette ZUMRA', 'EXCLUDED' => 'Vous avez été retiré·e de cette ZUMRA', 'SUSPENDED' => 'Votre participation est suspendue', default => $membership->status } }}</h2>

                        @if(! $membership || in_array($membership->status, ['LEFT', 'EXCLUDED']))
                            <form method="POST" action="{{ route('zumra.groups.request', $group) }}" style="margin-top:14px;display:flex;flex-direction:column;gap:10px">
                                @csrf
                                <div class="dg-field">
                                    <label for="motivation">Pourquoi souhaitez-vous rejoindre cette équipe ?</label>
                                    <textarea id="motivation" name="motivation" class="dg-textarea" rows="4" maxlength="800"></textarea>
                                </div>
                                <button type="submit" class="dg-btn dg-btn--saffron">Envoyer ma demande</button>
                            </form>
                        @elseif($membership->status === 'INVITED')
                            <div style="margin-top:14px;display:flex;flex-direction:column;gap:8px">
                                <form method="POST" action="{{ route('messages.invitation', $group) }}">
                                    @csrf
                                    <button type="submit" class="dg-btn dg-btn--quiet" style="width:100%">Échanger avant d’accepter</button>
                                </form>
                                <form method="POST" action="{{ route('zumra.groups.invitation.accept', $group) }}">
                                    @csrf
                                    <button type="submit" class="dg-btn dg-btn--saffron" style="width:100%">Accepter l’invitation</button>
                                </form>
                            </div>
                        @elseif($membership->status === 'REQUESTED')
                            <p class="dg-hint" style="margin-top:10px">Attendez l’approbation d’un responsable. Une demande ne donne aucun droit de membre.</p>
                        @elseif($membership->status === 'ACTIVE')
                            <div style="margin-top:14px;display:flex;flex-direction:column;gap:8px">
                                <form method="POST" action="{{ route('messages.zumra', $group) }}">
                                    @csrf
                                    <button type="submit" class="dg-btn dg-btn--primary" style="width:100%">Ouvrir la conversation ZUMRA</button>
                                </form>
                                @if(! $isLeader)
                                    <form method="POST" action="{{ route('zumra.groups.leave', $group) }}">
                                        @csrf
                                        <button type="submit" class="dg-btn dg-btn--quiet" style="width:100%">Quitter librement la ZUMRA</button>
                                    </form>
                                @endif
                            </div>
                        @endif

                        @if($group->state !== \App\Models\ZumraGroup::STATE_SUSPENDED)
                            <div style="margin-top:12px;display:flex;flex-direction:column;gap:6px">
                                <x-dg.btn variant="quiet" :href="route('comments.zumra-activity', $group)">Contribuer à l’activité →</x-dg.btn>
                                @if($membership?->status === 'ACTIVE')
                                    <x-dg.btn variant="quiet" :href="route('shares.group', $group)">Partages utiles →</x-dg.btn>
                                @endif
                            </div>
                        @endif
                    </x-dg.card>

                    @if($membership?->status === 'ACTIVE')
                        <x-dg.card tight>
                            <x-dg.label>Mes capacités dans cette ZUMRA</x-dg.label>
                            <h2 class="dg-display" style="font-size:16px;margin-top:6px">Un choix volontaire, réversible.</h2>
                            <p class="dg-hint" style="margin-top:8px">Seules vos capacités déjà découvrables peuvent être comptées. Votre nom ne figure jamais dans l’agrégat.</p>
                            <form method="POST" action="{{ route('zumra.groups.collective-capabilities.consent', $group) }}" style="margin-top:12px;display:flex;flex-direction:column;gap:10px">
                                @csrf @method('PUT')
                                <label class="dg-consent">
                                    <input type="checkbox" name="collective_capability_consent" value="1" @checked($membership->collective_capability_consent)>
                                    <span>{{ $collectiveCapabilitySettings['consent_label'] }}</span>
                                </label>
                                <button type="submit" class="dg-btn dg-btn--quiet">Enregistrer mon choix</button>
                            </form>
                        </x-dg.card>
                    @endif

                    @if($isLeader)
                        <x-dg.card tight>
                            <x-dg.label>Espace responsable</x-dg.label>
                            <h2 class="dg-display" style="font-size:16px;margin-top:6px">Inviter un adhérent</h2>
                            <p class="dg-hint" style="margin-top:8px">Utilisez la référence publique visible sur son profil. L’invitation devra être acceptée.</p>
                            {{-- UIUX-007 — doctrine humaine §6/§7 : développer le collectif reste
                                 la mission du premier responsable, jamais un moteur de recrutement.
                                 Réutilise strictement la découverte de Personnes déjà existante. --}}
                            <a href="{{ route('people.index') }}" class="dg-meta" style="display:block;margin-top:8px;color:var(--dg-copper);font-weight:600">Trouver des collaborateurs →</a>
                            <form method="POST" action="{{ route('zumra.groups.invite', $group) }}" style="margin-top:12px;display:flex;flex-direction:column;gap:10px">
                                @csrf
                                <div class="dg-field">
                                    <label for="person_reference">Référence publique</label>
                                    <input type="text" id="person_reference" name="person_reference" class="dg-input" placeholder="xxxxxxxx-xxxx-…" required>
                                </div>
                                <button type="submit" class="dg-btn dg-btn--primary">Envoyer l’invitation</button>
                            </form>
                            <a href="{{ route('community-events.zumra.create', $group) }}" class="dg-meta" style="display:block;margin-top:12px;color:var(--dg-copper);font-weight:600">Organiser un événement →</a>
                        </x-dg.card>

                        @if($pendingRequests->isNotEmpty())
                            <x-dg.card tight id="demandes">
                                <x-dg.label>Demandes en attente</x-dg.label>
                                <div style="margin-top:12px;display:flex;flex-direction:column;gap:10px">
                                    @foreach($pendingRequests as $pending)
                                        <div class="dg-note">
                                            <strong style="color:var(--dg-ink)">{{ $requestProfiles->get($pending->core_identity_reference)?->discovery_display_name ?: 'Adhérent ZUMRA' }}</strong>
                                            <p style="margin:6px 0">{{ $pending->motivation ?: 'Aucune motivation renseignée.' }}</p>
                                            <form method="POST" action="{{ route('zumra.groups.requests.approve', [$group, $pending->id]) }}">
                                                @csrf
                                                <button type="submit" class="dg-btn dg-btn--primary">Approuver</button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            </x-dg.card>
                        @endif
                    @endif
                </aside>
            </div>
                </div>
            </details>
        </div>
    </x-dg.shell>
</x-layouts.portal>
