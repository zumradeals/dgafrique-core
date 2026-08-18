{{--
    Fiche Projet — même objet métier que dans le Fil (x-dg.feed.project). La maturité utilise le
    chemin complet (x-dg.stagewalk, 8 repères) ; les décisions passent par ProjectService::transition().
--}}
<x-layouts.portal title="{{ $project->name }} — DG Afrique">
    <x-dg.shell current="projets" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:1100px">
            <a href="{{ route('projects.index') }}" class="dg-crumb">← Tous les projets</a>

            @if(session('status'))
                <div class="dg-band" style="margin-bottom:20px">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="dg-band" style="margin-bottom:20px;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>
            @endif

            <div class="dg-page-header">
                <div>
                    <x-dg.badge tone="project">{{ $configuration['domains'][$project->domain] ?? $project->domain }}</x-dg.badge>
                    <h1 class="dg-display dg-display--screen" style="margin-top:10px">{{ $project->name }}</h1>
                    <p>{{ $project->owner_type === 'GROUP' ? ($group?->name ?? 'ZUMRA') : 'Projet personnel accompagné' }}</p>
                </div>
                <div style="text-align:right">
                    <x-dg.label>{{ ['PROPOSED' => 'Proposé', 'ADOPTED' => 'Adopté', 'IN_PROGRESS' => 'En action', 'COMPLETED' => 'Réalisé', 'ARCHIVED' => 'Archivé'][$project->status] ?? $project->status }}</x-dg.label>
                    <div style="margin-top:6px;font-size:14px;font-weight:600;color:var(--dg-forest)">{{ $maturityStages[$project->maturity]['label'] ?? str_replace('_', ' ', mb_strtolower($project->maturity)) }}</div>
                    <div class="dg-meta">Maturité indicative, non juridique</div>
                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:16px">
                @if($project->image_path)
                    <img src="{{ Illuminate\Support\Facades\Storage::disk('public')->url($project->image_path) }}" alt=""
                         style="width:100%;max-height:420px;object-fit:cover;border-radius:var(--dg-radius-card);border:1px solid var(--dg-line)">
                @endif
                <x-dg.card>
                    <div style="display:grid;gap:16px" class="lg:grid-cols-3">
                        <div>
                            <x-dg.label>Le problème</x-dg.label>
                            <p class="dg-body" style="margin-top:8px">{{ $project->problem }}</p>
                        </div>
                        <div>
                            <x-dg.label>La réponse envisagée</x-dg.label>
                            <p class="dg-body" style="margin-top:8px">{{ $project->proposed_solution }}</p>
                        </div>
                        <div>
                            <x-dg.label>Les bénéficiaires</x-dg.label>
                            <p class="dg-body" style="margin-top:8px">{{ $project->beneficiaries }}</p>
                        </div>
                    </div>
                </x-dg.card>

                <x-dg.card>
                    <div style="display:grid;gap:20px" class="lg:grid-cols-3">
                        <div>
                            <x-dg.label>Ce que le projet veut accomplir</x-dg.label>
                            <ul style="margin-top:10px;display:flex;flex-direction:column;gap:6px;font-size:14px;color:var(--dg-text)">
                                @foreach($project->objectives as $item)
                                    <li>· {{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <div>
                            <x-dg.label>Capacités nécessaires</x-dg.label>
                            <ul style="margin-top:10px;display:flex;flex-direction:column;gap:6px;font-size:14px;color:var(--dg-text)">
                                @forelse($project->required_capabilities as $item)
                                    <li>· {{ $item }}</li>
                                @empty
                                    <li class="dg-meta">À préciser</li>
                                @endforelse
                            </ul>
                        </div>
                        <div>
                            <x-dg.label>Ressources nécessaires</x-dg.label>
                            <ul style="margin-top:10px;display:flex;flex-direction:column;gap:6px;font-size:14px;color:var(--dg-text)">
                                @forelse($project->required_resources as $item)
                                    <li>· {{ $item }}</li>
                                @empty
                                    <li class="dg-meta">À préciser</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </x-dg.card>

                <x-dg.card>
                    <x-dg.label>Maturité — repères de capacité</x-dg.label>
                    <p class="dg-hint" style="margin-top:6px">Ces repères décrivent l’avancement du projet. Ils ne constituent ni un statut juridique ni une décision institutionnelle.</p>
                    <div style="margin-top:16px">
                        <x-dg.stagewalk :stages="$maturityStages" :current="$project->maturity" />
                    </div>

                    <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--dg-line-dashed)">
                        <x-dg.label>Signaux observés</x-dg.label>
                        <p class="dg-hint" style="margin-top:6px">Ces signaux n’attribuent ni ne modifient aucun repère. Seul le porteur décide.</p>
                        @if(empty($maturitySignals))
                            <p class="dg-meta" style="margin-top:10px">Aucun signal observé pour l’instant.</p>
                        @else
                            <ul style="margin-top:10px;display:flex;flex-direction:column;gap:6px;font-size:14px;color:var(--dg-text)">
                                @foreach($maturitySignals as $signal)
                                    <li>· {{ $signal }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </x-dg.card>

                @if($canDecide)
                    <x-dg.fieldset>
                        <legend><x-dg.label>Repositionner la maturité</x-dg.label></legend>
                        <p class="dg-body" style="margin:0">Choisissez le repère qui décrit le mieux la situation réelle aujourd’hui. Le projet peut avancer ou revenir à un repère antérieur.</p>
                        <form method="POST" action="{{ route('projects.maturity.update', $project) }}" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-start">
                            @csrf @method('PUT')
                            <select name="maturity" class="dg-select" style="max-width:280px" required>
                                @foreach($maturityStages as $code => $stage)
                                    <option value="{{ $code }}" @selected($project->maturity === $code)>{{ $stage['label'] }}</option>
                                @endforeach
                            </select>
                            <textarea name="note" class="dg-textarea" rows="2" maxlength="1200" placeholder="Note factuelle facultative" style="flex:1;min-width:220px;min-height:auto">{{ old('note') }}</textarea>
                            <button type="submit" class="dg-btn dg-btn--primary">Enregistrer le repère</button>
                        </form>

                        @if($maturityHistory->isNotEmpty())
                            <details style="margin-top:4px">
                                <summary style="cursor:pointer;font-size:13px;font-weight:600;color:var(--dg-forest)">Historique des changements</summary>
                                <div style="display:flex;flex-direction:column;gap:8px;margin-top:12px">
                                    @foreach($maturityHistory as $event)
                                        <div class="dg-note">
                                            <strong style="color:var(--dg-ink)">{{ $maturityStages[$event->context['from']]['label'] ?? $event->context['from'] }} → {{ $maturityStages[$event->context['to']]['label'] ?? $event->context['to'] }}</strong>
                                            <span class="dg-meta"> · {{ $event->occurred_at?->format('d/m/Y H:i') }}</span>
                                            @if(! empty($event->context['note']))
                                                <p style="margin:6px 0 0">{{ $event->context['note'] }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @endif
                    </x-dg.fieldset>
                @endif

                <x-dg.card>
                    <x-dg.label>Chemin envisagé</x-dg.label>
                    <div style="margin-top:12px;display:flex;flex-direction:column;gap:2px">
                        @foreach($project->milestones as $step)
                            <div style="display:flex;align-items:center;gap:14px;padding:10px 4px">
                                <span class="dg-meta" style="font-family:var(--dg-font-mono)">{{ str_pad((string) $step->position, 2, '0', STR_PAD_LEFT) }}</span>
                                <span style="font-size:14px;color:var(--dg-ink);flex:1">{{ $step->title }}</span>
                                <x-dg.label tone="{{ $step->status === 'COMPLETED' ? 'saffron' : null }}">{{ $step->status === 'COMPLETED' ? 'Réalisée' : 'Prévue' }}</x-dg.label>
                            </div>
                        @endforeach
                    </div>
                </x-dg.card>

                <x-dg.card>
                    <x-dg.label>Équipe du projet</x-dg.label>
                    <p class="dg-hint" style="margin-top:6px">Les personnes qui ont réellement rejoint ce projet, jamais un classement ni un score.</p>

                    <div style="margin-top:14px;display:flex;flex-direction:column;gap:8px">
                        @forelse($teamMembers as $member)
                            <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--dg-line-dashed)">
                                <div>
                                    <strong style="color:var(--dg-ink)">{{ $teamProfiles->get($member->core_identity_reference)?->discovery_display_name ?: 'Membre du projet' }}</strong>
                                    @if($member->role)
                                        <span class="dg-meta"> · {{ $member->role }}</span>
                                    @endif
                                </div>
                                @if($canDecide)
                                    <form method="POST" action="{{ route('projects.team.remove', [$project, $member]) }}" onsubmit="return confirm('Retirer cette personne de l’équipe ?');" style="display:flex;gap:6px;align-items:center">
                                        @csrf
                                        <input type="text" name="reason" class="dg-input" placeholder="Raison" required style="max-width:180px">
                                        <button type="submit" class="dg-btn dg-btn--quiet">Retirer</button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <p class="dg-meta">Aucune personne n’a encore rejoint l’équipe.</p>
                        @endforelse
                    </div>

                    @if($myTeamMembership?->status === 'INVITED')
                        <form method="POST" action="{{ route('projects.team.invitation.accept', $project) }}" style="margin-top:14px">
                            @csrf
                            <button type="submit" class="dg-btn dg-btn--primary">Accepter l’invitation à rejoindre l’équipe</button>
                        </form>
                    @elseif($myTeamMembership?->status === 'ACTIVE')
                        <form method="POST" action="{{ route('projects.team.leave', $project) }}" style="margin-top:14px">
                            @csrf
                            <button type="submit" class="dg-btn dg-btn--quiet">Quitter l’équipe</button>
                        </form>
                    @elseif($myTeamMembership?->status === 'REQUESTED')
                        <p class="dg-meta" style="margin-top:14px">Votre demande pour rejoindre l’équipe est en attente.</p>
                    @elseif(! $canDecide)
                        <form method="POST" action="{{ route('projects.team.request', $project) }}" style="margin-top:14px;display:flex;flex-direction:column;gap:10px">
                            @csrf
                            <textarea name="motivation" class="dg-textarea" rows="2" maxlength="800" placeholder="Motivation facultative"></textarea>
                            <button type="submit" class="dg-btn dg-btn--primary" style="align-self:flex-start">Demander à rejoindre l’équipe</button>
                        </form>
                    @endif

                    @if($canDecide)
                        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--dg-line-dashed)">
                            <x-dg.label>Espace porteur</x-dg.label>
                            <p class="dg-hint" style="margin-top:8px">Utilisez la référence publique visible sur son profil. L’invitation devra être acceptée.</p>
                            <form method="POST" action="{{ route('projects.team.invite', $project) }}" style="margin-top:10px;display:flex;flex-wrap:wrap;gap:10px;align-items:flex-start">
                                @csrf
                                <input type="text" name="person_reference" class="dg-input" placeholder="Référence publique xxxxxxxx-xxxx-…" required style="flex:1;min-width:220px">
                                <button type="submit" class="dg-btn dg-btn--primary">Envoyer l’invitation</button>
                            </form>

                            @if($pendingTeamRequests->isNotEmpty())
                                <div style="margin-top:16px;display:flex;flex-direction:column;gap:10px">
                                    @foreach($pendingTeamRequests as $pending)
                                        <div class="dg-note">
                                            <strong style="color:var(--dg-ink)">{{ $teamProfiles->get($pending->core_identity_reference)?->discovery_display_name ?: 'Personne' }}</strong>
                                            <p style="margin:6px 0">{{ $pending->motivation ?: 'Aucune motivation renseignée.' }}</p>
                                            <form method="POST" action="{{ route('projects.team.requests.approve', [$project, $pending]) }}">
                                                @csrf
                                                <button type="submit" class="dg-btn dg-btn--primary">Approuver</button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif
                </x-dg.card>

                <x-dg.card>
                    <x-dg.label>Besoins du projet</x-dg.label>
                    <p class="dg-hint" style="margin-top:6px">Ce que le projet exprime au fil de son avancement, distinct de l’instantané initial ci-dessus.</p>

                    <div style="margin-top:14px;display:flex;flex-direction:column;gap:8px">
                        @forelse($projectNeeds as $need)
                            <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--dg-line-dashed)">
                                <a href="{{ route('needs.show', $need) }}" style="color:var(--dg-ink);font-weight:600">{{ $need->title }}</a>
                                <x-dg.label>{{ ['PROPOSED' => 'Proposé', 'OPEN' => 'Ouvert', 'IN_PROGRESS' => 'En cours', 'RESOLVED' => 'Résolu'][$need->status] ?? $need->status }}</x-dg.label>
                            </div>
                        @empty
                            <p class="dg-meta">Aucun besoin exprimé pour ce projet.</p>
                        @endforelse
                    </div>

                    @if($canProposeNeed)
                        <x-dg.actions flush style="margin-top:14px">
                            <x-dg.btn variant="quiet" :href="route('needs.create', ['project' => $project->public_reference])">Exprimer un besoin pour ce projet →</x-dg.btn>
                        </x-dg.actions>
                    @endif
                </x-dg.card>

                <details class="dg-disclosure">
                    <summary><span class="dg-disclosure__mark">i</span> Aucun financement n’est ouvert ici.</summary>
                    <div class="dg-disclosure__body">Cette fiche organise le projet ; elle ne constitue ni une collecte, ni une promesse d’accompagnement. Le projet reste la propriété de son porteur et son adhésion à un dispositif n’est jamais automatique.</div>
                </details>

                <x-dg.card>
                    <x-dg.label>Contributions et circulation utiles</x-dg.label>
                    <p class="dg-body" style="margin-top:8px">Questions, précisions, conseils, ressources, coordination et partage avec contexte restent attachés à ce projet.</p>
                    <x-dg.actions flush>
                        <x-dg.btn variant="quiet" :href="route('comments.project', $project)">Ouvrir les commentaires →</x-dg.btn>
                        @if($project->status !== 'ARCHIVED')
                            <x-dg.btn variant="quiet" :href="route('shares.project', $project)">Partager avec contexte →</x-dg.btn>
                        @endif
                    </x-dg.actions>
                </x-dg.card>

                @if($canDecide)
                    <x-dg.fieldset>
                        <legend style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
                            <x-dg.label>Faire évoluer le dossier</x-dg.label>
                            <div style="display:flex;gap:14px;flex-wrap:wrap">
                                <x-dg.btn variant="quiet" :href="route('projects.accompaniment.show', $project)">Accompagnement DG Afrique →</x-dg.btn>
                                @if(in_array($project->maturity, ['POTENTIAL_STRUCTURE', 'POTENTIAL_SATELLITE']) || $project->autonomyPathway)
                                    <x-dg.btn variant="quiet" :href="route('projects.autonomy.show', $project)">Parcours d’autonomie →</x-dg.btn>
                                @endif
                                <x-dg.btn variant="quiet" :href="route('messages.project', $project)" method="POST">Conversation du projet →</x-dg.btn>
                            </div>
                        </legend>
                        <p class="dg-hint">La conversation coordonne les personnes autorisées ; elle ne crée aucun statut de membre du projet.</p>

                        <x-dg.actions flush>
                            @if($project->owner_type === 'GROUP' && $project->status === 'PROPOSED')
                                <form method="POST" action="{{ route('projects.transition', $project) }}">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="status" value="ADOPTED">
                                    <button type="submit" class="dg-btn dg-btn--primary">Adopter pour la ZUMRA</button>
                                </form>
                            @endif
                            @if(in_array($project->status, ['PROPOSED', 'ADOPTED']))
                                <form method="POST" action="{{ route('projects.transition', $project) }}">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="status" value="IN_PROGRESS">
                                    <button type="submit" class="dg-btn dg-btn--primary">Démarrer</button>
                                </form>
                            @endif
                            @if($project->status === 'IN_PROGRESS')
                                <form method="POST" action="{{ route('projects.transition', $project) }}">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="status" value="COMPLETED">
                                    <button type="submit" class="dg-btn dg-btn--project">Marquer réalisé</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('projects.transition', $project) }}">
                                @csrf @method('PUT')
                                <input type="hidden" name="status" value="ARCHIVED">
                                <button type="submit" class="dg-btn dg-btn--quiet">Archiver</button>
                            </form>
                        </x-dg.actions>
                    </x-dg.fieldset>
                @endif
            </div>
        </div>
    </x-dg.shell>
</x-layouts.portal>
