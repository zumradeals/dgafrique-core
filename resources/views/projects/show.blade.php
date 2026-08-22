{{--
    Dossier Projet — fiche V2, fidèle à la maquette du 20 août 2026 (voir addendum daté
    docs/design/DESIGN-INVARIANTS.md §19). Même objet métier que dans le Fil ; la maturité utilise
    le chemin complet (x-dg.stagewalk, 8 repères), disposé horizontalement ici via CSS uniquement
    (même DOM/classes que le composant testé) — décisions toujours via ProjectService::transition().
    Les onglets internes (Activités/Équipe/Besoins/Ressources/Documents/Conversations) sont des
    ancres réelles vers des sections de cette même page, pas des pages fabriquées.
--}}
@php
    $statusLabels = ['PROPOSED' => 'Proposé', 'ADOPTED' => 'Adopté', 'IN_PROGRESS' => 'En action', 'COMPLETED' => 'Réalisé', 'ARCHIVED' => 'Archivé'];
    $visibilityLabels = ['PRIVATE' => 'Privé', 'GROUP' => 'Groupe', 'PROGRAM' => 'Programme', 'PUBLIC' => 'Public'];
    $needStatusLabels = ['PROPOSED' => 'Proposé', 'OPEN' => 'Ouvert', 'IN_PROGRESS' => 'En cours', 'RESOLVED' => 'Résolu'];
    $isArchived = $project->status === \App\Models\Project::STATUS_ARCHIVED;
    $followReason = 'Le suivi des mises à jour arrivera avec le système de notifications par objet.';
@endphp
<x-layouts.portal title="{{ $project->name }} — DG Afrique">
    <x-dg.shell current="projets" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:1180px">
            <a href="{{ route('projects.index') }}" class="dg-crumb">← Tous les projets</a>

            @if(session('status'))
                <div class="dg-band" style="margin-bottom:20px">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="dg-band" style="margin-bottom:20px;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>
            @endif

            <x-dg.badge tone="project">{{ $configuration['domains'][$project->domain] ?? $project->domain }}</x-dg.badge>

            <div class="dg-project-head" style="margin-top:10px">
                <div class="dg-project-head__title">
                    <h1 class="dg-display dg-display--screen" style="margin:0">{{ $project->name }}</h1>
                    <x-dg.badge tone="{{ $isArchived ? 'neutral' : 'action' }}">{{ $isArchived ? 'Archivé' : 'Actif' }}</x-dg.badge>
                </div>
                <div class="dg-project-head__actions">
                    <x-dg.btn variant="quiet" :href="route('shares.project', $project)">Partager</x-dg.btn>
                    <x-dg.btn variant="quiet" disabled :title="$followReason">Suivre</x-dg.btn>
                    <x-dg.btn variant="project" :href="route('projects.brain.show', $project)">Ouvrir le Cerveau →</x-dg.btn>
                </div>
            </div>
            {{-- UIUX-003 : retour réel vers la ZUMRA porteuse, même patron que
                 missions.show/proofs.show (contextUrl/contextLabel) — jamais un lien fabriqué. --}}
            @if($project->owner_type === 'GROUP' && $group)
                <p class="dg-body" style="margin-top:4px"><a href="{{ route('zumra.groups.show', $group) }}" style="color:var(--dg-copper);font-weight:600">{{ $group->name }}</a></p>
            @else
                <p class="dg-body" style="margin-top:4px">{{ $project->owner_type === 'GROUP' ? 'ZUMRA' : 'Projet personnel accompagné' }}</p>
            @endif

            <nav class="dg-project-tabs" aria-label="Sections du dossier">
                <a href="#dg-project-top" aria-current="page">Vue d’ensemble</a>
                <a href="#dg-project-activite">Activités</a>
                <a href="#dg-project-equipe-detail">Équipe</a>
                <a href="#dg-project-besoins">Besoins</a>
                <a href="#dg-project-ressources">Ressources</a>
                <a href="#dg-project-documents">Documents</a>
                <a href="#dg-project-conversations">Conversations</a>
            </nav>

            <span id="dg-project-top"></span>

            <div class="dg-project-layout">
                <div style="display:flex;flex-direction:column;gap:18px">
                    @if($project->image_path)
                        <img src="{{ Illuminate\Support\Facades\Storage::disk('public')->url($project->image_path) }}" alt=""
                             style="width:100%;max-height:360px;object-fit:cover;border-radius:var(--dg-radius-card);border:1px solid var(--dg-line)">
                    @endif

                    {{-- Synthèse du projet --}}
                    <x-dg.card>
                        <x-dg.label>Synthèse du projet</x-dg.label>
                        <div class="dg-project-meta-row" style="margin-top:14px">
                            @if($project->location)
                                <span><x-dg.icon name="target" size="15" /> <strong>{{ $project->location }}</strong></span>
                            @endif
                            <span><x-dg.icon name="grid" size="15" /> Créé le <strong>{{ $project->created_at->format('d/m/Y') }}</strong></span>
                            <span><x-dg.icon name="spark" size="15" /> <strong>{{ $maturityStages[$project->maturity]['label'] ?? $project->maturity }}</strong> — Niveau de maturité</span>
                        </div>
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

                    {{-- Maturité — Repères du projet --}}
                    <x-dg.card>
                        <x-dg.label>Maturité — Repères du projet</x-dg.label>
                        <p class="dg-hint" style="margin-top:6px">Ces repères décrivent l’avancement du projet. Ils ne constituent ni un statut juridique ni une décision institutionnelle.</p>
                        <div class="dg-maturity-horizontal" style="margin-top:18px">
                            <x-dg.stagewalk :stages="$maturityStages" :current="$project->maturity" />
                        </div>

                        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--dg-line-dashed)">
                            <x-dg.label>Signaux observés</x-dg.label>
                            <p class="dg-hint" style="margin-top:6px">Ces signaux n’attribuent ni ne modifient aucun repère. Seul le porteur décide.</p>
                            @if(empty($maturitySignals))
                                <p class="dg-meta" style="margin-top:10px">Aucun signal observé pour l’instant.</p>
                            @else
                                <div style="margin-top:10px;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:6px 20px;font-size:14px;color:var(--dg-text)">
                                    @foreach($maturitySignals as $signal)
                                        <span>· {{ $signal }}</span>
                                    @endforeach
                                </div>
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

                    {{-- Chemin envisagé --}}
                    <x-dg.card>
                        <x-dg.label>Chemin envisagé</x-dg.label>
                        <div style="margin-top:12px;display:flex;flex-direction:column;gap:2px">
                            @forelse($project->milestones as $step)
                                <div style="display:flex;align-items:center;gap:14px;padding:10px 4px">
                                    <span class="dg-meta" style="font-family:var(--dg-font-mono)">{{ str_pad((string) $step->position, 2, '0', STR_PAD_LEFT) }}</span>
                                    <span style="font-size:14px;color:var(--dg-ink);flex:1">{{ $step->title }}</span>
                                    <x-dg.label tone="{{ $step->status === 'COMPLETED' ? 'saffron' : null }}">{{ $step->status === 'COMPLETED' ? 'Réalisée' : 'Prévue' }}</x-dg.label>
                                </div>
                            @empty
                                <p class="dg-meta">Aucune étape définie pour le moment.</p>
                            @endforelse
                        </div>
                        <x-dg.actions flush style="margin-top:6px">
                            <x-dg.btn variant="quiet" disabled title="Le suivi détaillé du chemin (jalons, dates, responsables) arrivera avec le moteur Missions.">Voir le détail du chemin →</x-dg.btn>
                        </x-dg.actions>
                    </x-dg.card>

                    {{-- Besoins du projet + Équipe (aperçu) --}}
                    <div class="grid gap-5 lg:grid-cols-2" style="align-items:start">
                        <x-dg.card id="dg-project-besoins">
                            <x-dg.label>Besoins du projet</x-dg.label>
                            <p class="dg-hint" style="margin-top:6px">Ce que le projet exprime au fil de son avancement, distinct de l’instantané initial ci-dessus.</p>

                            <div style="margin-top:14px;display:flex;flex-direction:column;gap:8px">
                                @forelse($projectNeeds as $need)
                                    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--dg-line-dashed)">
                                        <a href="{{ route('needs.show', $need) }}" style="color:var(--dg-ink);font-weight:600">{{ $need->title }}</a>
                                        <x-dg.label>{{ $needStatusLabels[$need->status] ?? $need->status }}</x-dg.label>
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

                        <x-dg.card>
                            <x-dg.label>Équipe du projet</x-dg.label>
                            <p class="dg-hint" style="margin-top:6px">Les personnes qui ont réellement rejoint ce projet, jamais un classement ni un score.</p>

                            <div class="dg-project-avatars" style="margin-top:14px">
                                @foreach($teamMembers->take(6) as $member)
                                    @php($name = $teamProfiles->get($member->core_identity_reference)?->discovery_display_name)
                                    <x-dg.avatar :initials="$name ? mb_strtoupper(mb_substr($name, 0, 1)) : '?'" :anonymous="! $name" size="sm" tone="night" />
                                @endforeach
                                @if($teamMembers->count() > 6)
                                    <span class="dg-project-avatars__more">+{{ $teamMembers->count() - 6 }}</span>
                                @endif
                            </div>
                            @if($teamMembers->isEmpty())
                                <p class="dg-meta" style="margin-top:12px">Aucune personne n’a encore rejoint l’équipe.</p>
                            @endif

                            <x-dg.actions flush style="margin-top:14px">
                                <x-dg.btn variant="quiet" href="#dg-project-equipe-detail">Voir toute l’équipe →</x-dg.btn>
                            </x-dg.actions>
                        </x-dg.card>
                    </div>

                    {{-- Ressources nécessaires --}}
                    <x-dg.card id="dg-project-ressources">
                        <x-dg.label>Ressources nécessaires</x-dg.label>
                        <ul style="margin-top:10px;display:flex;flex-direction:column;gap:6px;font-size:14px;color:var(--dg-text)">
                            @forelse($project->required_resources as $item)
                                <li>· {{ $item }}</li>
                            @empty
                                <li class="dg-meta">À préciser</li>
                            @endforelse
                        </ul>
                    </x-dg.card>

                    <x-dg.card>
                        <div style="display:grid;gap:20px" class="lg:grid-cols-2">
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
                        </div>
                    </x-dg.card>

                    {{-- Contributions & Soutien utiles --}}
                    <x-dg.card id="dg-project-conversations">
                        <x-dg.label>Contributions & Soutien utiles</x-dg.label>
                        <p class="dg-body" style="margin-top:8px">Questions, précisions, conseils, ressources, coordination et partage avec contexte restent attachés à ce projet.</p>
                        <x-dg.actions flush>
                            <x-dg.btn variant="quiet" :href="route('comments.project', $project)">Ouvrir les commentaires →</x-dg.btn>
                            @if(! $isArchived)
                                <x-dg.btn variant="quiet" :href="route('shares.project', $project)">Partager avec contexte →</x-dg.btn>
                            @endif
                        </x-dg.actions>
                    </x-dg.card>

                    @if($canDecide)
                        {{-- Espace porteur --}}
                        <x-dg.card>
                            <x-dg.label>Espace porteur</x-dg.label>
                            <p class="dg-hint" style="margin-top:8px">Utilisez la référence publique visible sur son profil. L’invitation devra être acceptée.</p>
                            <form method="POST" action="{{ route('projects.team.invite', $project) }}" style="margin-top:10px;display:flex;flex-wrap:wrap;gap:10px;align-items:flex-start">
                                @csrf
                                <input type="text" name="person_reference" class="dg-input" placeholder="Référence publique xxxxxxxx-xxxx-…" required style="flex:1;min-width:220px">
                                <button type="submit" class="dg-btn dg-btn--primary">Envoyer l’invitation</button>
                            </form>
                        </x-dg.card>

                        {{-- Pistes d’action avec DG Afrique --}}
                        <x-dg.card>
                            <x-dg.label>Pistes d’action avec DG Afrique</x-dg.label>
                            <x-dg.actions flush style="margin-top:12px">
                                <x-dg.btn variant="quiet" :href="route('projects.accompaniment.show', $project)">Accompagnement DG Afrique →</x-dg.btn>
                                <x-dg.btn variant="quiet" :href="route('messages.project', $project)" method="POST">Conversation du projet →</x-dg.btn>
                                @if(in_array($project->maturity, ['POTENTIAL_STRUCTURE', 'AUTONOMY_READY']) || $project->autonomyPathway)
                                    <x-dg.btn variant="quiet" :href="route('projects.autonomy.show', $project)">Parcours d’autonomie →</x-dg.btn>
                                @endif
                            </x-dg.actions>
                            <p class="dg-hint" style="margin-top:10px">La conversation coordonne les personnes autorisées, elle ne crée aucun statut de membre du projet.</p>
                        </x-dg.card>
                    @endif

                    @if($accompaniment && $canDecide)
                        <x-dg.card>
                            <div class="flex items-center justify-between gap-4">
                                <x-dg.label>Accompagnement DG Afrique</x-dg.label>
                                <x-dg.badge tone="decision">{{ $accompaniment->status === 'ACTIVE' ? 'Actif' : 'Terminé' }}</x-dg.badge>
                            </div>
                            <p class="dg-body" style="margin-top:10px">{{ $accompaniment->status === 'ACTIVE' ? 'Un accompagnement est actif sur ce projet.' : 'Un accompagnement a été mené sur ce projet et s’est terminé.' }}</p>
                            <x-dg.actions flush style="margin-top:12px">
                                <x-dg.btn variant="quiet" :href="route('projects.accompaniment.show', $project)">Voir le détail →</x-dg.btn>
                            </x-dg.actions>
                        </x-dg.card>
                    @endif

                    {{-- Documents & Preuves --}}
                    <x-dg.card id="dg-project-documents">
                        <x-dg.label>Documents & Preuves</x-dg.label>
                        <p class="dg-meta" style="margin-top:10px">Aucun document pour le moment.</p>
                        <x-dg.actions flush style="margin-top:10px">
                            <x-dg.btn variant="quiet" disabled title="L’espace documentaire du projet (GamaDrive) n’est pas encore relié à cette fiche.">Ajouter un document</x-dg.btn>
                        </x-dg.actions>
                    </x-dg.card>

                    {{-- Activité récente --}}
                    <x-dg.card id="dg-project-activite">
                        <x-dg.label>Activité récente</x-dg.label>
                        <div class="dg-project-activity" style="margin-top:12px">
                            @forelse($recentEvents as $event)
                                @php($actorName = $eventActorProfiles->get($event->actor_core_reference)?->discovery_display_name)
                                <div class="dg-project-activity__row">
                                    <div><strong>{{ $event->label() }}</strong> <span class="dg-meta">· {{ $actorName ?: 'Membre DG Afrique' }}</span></div>
                                    <span class="dg-meta">{{ $event->occurred_at->format('d/m/Y') }}</span>
                                </div>
                            @empty
                                <p class="dg-meta">Aucune activité enregistrée pour le moment.</p>
                            @endforelse
                        </div>
                        <x-dg.actions flush style="margin-top:10px">
                            <x-dg.btn variant="quiet" disabled title="Le journal d’activité complet arrivera avec l’historique dédié du dossier.">Voir toute l’activité →</x-dg.btn>
                        </x-dg.actions>
                    </x-dg.card>

                    {{-- Équipe du projet (gestion complète) --}}
                    <x-dg.card id="dg-project-equipe-detail">
                        <x-dg.label>Équipe du projet — gestion</x-dg.label>

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

                        @if($canDecide && $pendingTeamRequests->isNotEmpty())
                            <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--dg-line-dashed)">
                                <x-dg.label>Demandes en attente</x-dg.label>
                                <div style="margin-top:12px;display:flex;flex-direction:column;gap:10px">
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
                            </div>
                        @endif
                    </x-dg.card>

                    <details class="dg-disclosure">
                        <summary><span class="dg-disclosure__mark">i</span> Aucun financement n’est ouvert ici.</summary>
                        <div class="dg-disclosure__body">Cette fiche organise le projet ; elle ne constitue ni une collecte, ni une promesse d’accompagnement. Le projet reste la propriété de son porteur et son adhésion à un dispositif n’est jamais automatique.</div>
                    </details>

                    @if($canDecide)
                        <x-dg.fieldset>
                            <legend><x-dg.label>Faire évoluer le statut du dossier</x-dg.label></legend>
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

                <aside class="dg-project-aside">
                    <x-dg.card>
                        <x-dg.label>Progression globale</x-dg.label>
                        <div class="dg-project-progress__value" style="margin-top:10px">{{ $progressSeed }}%</div>
                        <div class="dg-meta">Avancement estimé · Projection</div>
                        <div class="dg-project-progress__bar"><i style="width:{{ $progressSeed }}%"></i></div>
                        <p class="dg-hint">Le projet avance régulièrement.</p>
                        <x-dg.btn variant="quiet" disabled title="Le détail du calcul d’avancement arrivera avec le moteur de progression canonique." style="justify-content:center;margin-top:8px">Voir le détail →</x-dg.btn>
                    </x-dg.card>

                    <x-dg.card>
                        <x-dg.label>Informations clés</x-dg.label>
                        <div style="margin-top:10px">
                            <div class="dg-project-info-row"><span>Domaine</span><span>{{ $configuration['domains'][$project->domain] ?? $project->domain }}</span></div>
                            <div class="dg-project-info-row"><span>Statut</span><span>{{ $statusLabels[$project->status] ?? $project->status }}</span></div>
                            <div class="dg-project-info-row"><span>Visibilité</span><span>{{ $visibilityLabels[$project->visibility] ?? $project->visibility }}</span></div>
                            <div class="dg-project-info-row"><span>Créé le</span><span>{{ $project->created_at->format('d/m/Y') }}</span></div>
                            <div class="dg-project-info-row"><span>Dernière activité</span><span>{{ $lastActivityAt->format('d/m/Y') }}</span></div>
                        </div>
                    </x-dg.card>

                    <x-dg.card>
                        <x-dg.label>Actions rapides</x-dg.label>
                        <div class="dg-project-quick-actions" style="margin-top:12px">
                            <x-dg.btn variant="project" :href="route('projects.brain.show', $project)">Ouvrir le Cerveau →</x-dg.btn>
                            @if($canDecide)
                                <x-dg.btn variant="quiet" :href="route('projects.matching', $project)">Trouver des capacités →</x-dg.btn>
                            @endif
                            <x-dg.btn variant="quiet" :href="route('shares.project', $project)">Partager ce projet →</x-dg.btn>
                            <x-dg.btn variant="quiet" disabled :title="$followReason">Suivre les mises à jour</x-dg.btn>
                        </div>
                    </x-dg.card>
                </aside>
            </div>
        </div>
    </x-dg.shell>
</x-layouts.portal>
