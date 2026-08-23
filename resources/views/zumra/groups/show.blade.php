{{--
    Fiche ZUMRA — gouvernance, capacité collective, charte, demandes/invitations. Respect absolu
    des cinq responsabilités distinctes, aucune nomination automatique (CAP-011).
--}}
<x-layouts.portal title="{{ $group->name }} — ZUMRA">
    <x-dg.shell current="zumra" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:1200px">
            <a href="{{ route('zumra.groups.index') }}" class="dg-crumb">← Les ZUMRA</a>

            @if(session('status'))
                <div class="dg-band" style="margin-bottom:20px">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="dg-band" style="margin-bottom:20px;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>
            @endif

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
    </x-dg.shell>
</x-layouts.portal>
