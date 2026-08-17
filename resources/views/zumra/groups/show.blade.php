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

            <div class="dg-page-header">
                <div class="flex items-center gap-4">
                    <x-dg.avatar :initials="mb_strtoupper(mb_substr($group->name, 0, 1))" size="lg" tone="night" />
                    <div>
                        <x-dg.label>{{ $group->domain }}</x-dg.label>
                        <h1 class="dg-display dg-display--screen" style="margin-top:6px">{{ $group->name }}</h1>
                        <p>{{ $group->founding_objective }}</p>
                        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:10px">
                            <x-dg.badge tone="neutral">{{ match($group->participation_mode) { 'PHYSICAL' => 'Physique', 'DIGITAL' => 'Numérique', default => 'Hybride' } }}</x-dg.badge>
                            <x-dg.badge tone="neutral">{{ $group->active_member_count }} membre{{ $group->active_member_count > 1 ? 's' : '' }}</x-dg.badge>
                            <x-dg.badge tone="neutral">{{ $group->maturity === 'ESTABLISHED' ? 'ZUMRA établie' : 'ZUMRA émergente' }}</x-dg.badge>
                        </div>
                    </div>
                </div>
                <div style="text-align:right">
                    <x-dg.label>État opérationnel</x-dg.label>
                    <div style="margin-top:6px;font-size:15px;font-weight:600;color:var(--dg-forest)">{{ match($group->state) { 'CONSTITUTING' => 'En constitution', 'READY' => 'Prête à valider', 'VALIDATED' => 'Validée', 'ACTIVE' => 'Active', 'WARNED' => 'Avertie', 'SUSPENDED' => 'Suspendue', 'REHABILITATING' => 'En réhabilitation', default => $group->state } }}</div>
                    <div class="dg-meta">{{ $roles->where('status', 'ACCEPTED')->count() }}/5 responsabilités acceptées</div>
                </div>
            </div>

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
                        <div style="margin-top:16px;display:flex;flex-direction:column;gap:8px">
                            @foreach($roles as $role)
                                @php($profile = $roleProfiles->get($role->core_identity_reference))
                                <x-dg.seat
                                    :label="\App\Models\ZumraGroupRole::LABELS[$role->role]"
                                    :filled="$role->status === 'ACCEPTED'"
                                    :holder="$profile?->discovery_display_name ?: ($role->status === 'ACCEPTED' ? 'Membre attesté' : null)"
                                />
                            @endforeach
                        </div>
                        <p class="dg-hint" style="margin-top:14px">Le système ne complète jamais les sièges avec des profils fictifs et ne nomme personne par matching.</p>
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

                    <x-dg.card>
                        <x-dg.label>Charte interne</x-dg.label>
                        <h2 class="dg-display" style="font-size:20px;margin-top:6px">Les règles de cette équipe</h2>
                        <div class="dg-body" style="margin-top:12px;white-space:pre-line">{{ $group->internal_charter }}</div>
                    </x-dg.card>
                </div>

                <aside style="display:flex;flex-direction:column;gap:16px">
                    <x-dg.card tight>
                        <x-dg.label>Votre relation</x-dg.label>
                        <h2 class="dg-display" style="font-size:18px;margin-top:6px">{{ ! $membership ? 'Vous ne faites pas encore partie de cette ZUMRA.' : match($membership->status) { 'ACTIVE' => 'Vous êtes membre', 'INVITED' => 'Vous êtes invité', 'REQUESTED' => 'Votre demande est étudiée', 'LEFT' => 'Vous avez quitté cette ZUMRA', default => $membership->status } }}</h2>

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
                            <form method="POST" action="{{ route('zumra.groups.invite', $group) }}" style="margin-top:12px;display:flex;flex-direction:column;gap:10px">
                                @csrf
                                <div class="dg-field">
                                    <label for="person_reference">Référence publique</label>
                                    <input type="text" id="person_reference" name="person_reference" class="dg-input" placeholder="xxxxxxxx-xxxx-…" required>
                                </div>
                                <button type="submit" class="dg-btn dg-btn--primary">Envoyer l’invitation</button>
                            </form>
                        </x-dg.card>

                        @if($pendingRequests->isNotEmpty())
                            <x-dg.card tight>
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
