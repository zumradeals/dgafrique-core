{{--
    Fiche Mission — CAP-069, UX-HARMONY-MISSIONS-002. Chaque décision sensible passe par un
    formulaire réel et distinct (proposer ≠ officialiser ≠ démarrer ≠ soumettre ≠ valider). Aucune
    mutation silencieuse, aucun rôle ZUMRA/Projet créé ici, aucune finance. Le repère de progression
    (.md-stepper) est purement présentatif : il lit les horodatages réels déjà stockés sur la Mission
    (proposed_at/officialized_at/started_at/submitted_at/completed_at), il n'invente ni ne recalcule
    aucun état — le badge de statut en tête de page reste la seule source de vérité sur l'état courant.
--}}
@php
    $stepDefs = [
        ['key' => 'DRAFT', 'label' => 'Brouillon', 'done' => true],
        ['key' => 'PROPOSED', 'label' => 'Proposée', 'done' => $mission->proposed_at !== null],
        ['key' => 'OPEN', 'label' => 'Ouverte', 'done' => $mission->officialized_at !== null],
        ['key' => 'IN_PROGRESS', 'label' => 'En cours', 'done' => $mission->started_at !== null],
        ['key' => 'SUBMITTED', 'label' => 'Soumise', 'done' => $mission->submitted_at !== null],
        ['key' => 'COMPLETED', 'label' => 'Terminée', 'done' => $mission->completed_at !== null],
    ];
    $lastDoneKey = collect($stepDefs)->filter(fn ($s) => $s['done'])->last()['key'] ?? 'DRAFT';
@endphp
<x-layouts.portal title="{{ $mission->title }} — DG Afrique">
    <x-dg.shell :identity="$identity" :is-administrator="$isAdministrator">
        <div class="md-page">
            <a href="{{ $contextUrl ?? route('missions.index') }}" class="md-crumb">← {{ $contextLabel }}</a>

            @if(session('status'))
                <div class="dg-band" style="margin-bottom:16px">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="dg-band" style="margin-bottom:16px;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>
            @endif

            <section class="md-hero">
                <div class="md-hero-top">
                    <div class="md-tags"><span>{{ $contextLabel }}</span></div>
                    <x-dg.badge :tone="\App\Models\Mission::STATUS_BADGE_TONES[$mission->status] ?? 'neutral'">{{ \App\Models\Mission::STATUS_LABELS[$mission->status] ?? $mission->status }}</x-dg.badge>
                </div>
                <h1>{{ $mission->title }}</h1>
                <p>{{ $mission->description }}</p>
                <div class="md-facts">
                    <span>Contexte<strong>{{ $contextLabel }}</strong></span>
                    <span>Visibilité<strong>{{ \App\Models\Mission::VISIBILITY_LABELS[$mission->visibility] ?? $mission->visibility }}</strong></span>
                    @if($mission->location)<span>Lieu<strong>{{ $mission->location }}</strong></span>@endif
                    @if($mission->due_at)<span>Échéance<strong>{{ $mission->due_at->format('d/m/Y') }}</strong></span>@endif
                    <span>Exécutants<strong>{{ $mission->min_executors }}{{ $mission->max_executors ? ' – '.$mission->max_executors : ' minimum' }}</strong></span>
                </div>
                <div class="md-stepper" aria-label="Progression réelle de la Mission">
                    @foreach($stepDefs as $step)
                        <i class="{{ $step['done'] ? 'is-done' : '' }} {{ $step['key'] === $lastDoneKey ? 'is-current' : '' }}">{{ $loop->iteration }}</i>
                        <span class="{{ $step['key'] === $lastDoneKey ? 'is-current' : '' }}">{{ $step['label'] }}</span>
                        @unless($loop->last)<em class="{{ $step['done'] ? 'is-done' : '' }}"></em>@endunless
                    @endforeach
                </div>
            </section>

            {{-- ===== Action requise maintenant : chaque bloc reste mutuellement exclusif selon le
                 statut réel de la Mission, exactement comme avant — seulement déplacé juste sous le
                 repère de progression pour rester la première chose vue. ===== --}}
            <div class="md-action-zone">
                @if($mission->status === 'DRAFT')
                    <x-dg.fieldset>
                        <legend><x-dg.label>Brouillon</x-dg.label></legend>
                        <p class="dg-hint">Ce brouillon n’est visible qu’aux personnes autorisées. Proposez-le pour ouvrir une décision distincte.</p>
                        <form method="POST" action="{{ route('missions.propose', $mission) }}">
                            @csrf
                            <button type="submit" class="dg-btn dg-btn--saffron">Proposer cette Mission</button>
                        </form>
                    </x-dg.fieldset>
                @endif

                @if($mission->status === 'PROPOSED' && $canOfficialize)
                    <x-dg.fieldset>
                        <legend><x-dg.label>Décision</x-dg.label></legend>
                        <p class="dg-hint">Proposer n’est pas décider : officialiser, demander une modification ou rejeter sont trois décisions distinctes et tracées.</p>
                        <form method="POST" action="{{ route('missions.officialize', $mission) }}" style="display:flex;flex-direction:column;gap:10px">
                            @csrf
                            @unless($mission->expected_result)
                                <div class="dg-field">
                                    <label for="expected_result">Résultat attendu (obligatoire pour ouvrir)</label>
                                    <textarea name="expected_result" id="expected_result" class="dg-textarea" rows="3" required></textarea>
                                </div>
                            @endunless
                            <button type="submit" class="dg-btn dg-btn--primary" style="align-self:flex-start">Officialiser et ouvrir</button>
                        </form>
                        <details style="margin-top:4px">
                            <summary style="cursor:pointer;font-size:13px;font-weight:600;color:var(--dg-copper)">Demander des modifications</summary>
                            <form method="POST" action="{{ route('missions.request-changes', $mission) }}" style="margin-top:10px;display:flex;flex-direction:column;gap:10px">
                                @csrf
                                <textarea name="reason" class="dg-textarea" rows="3" placeholder="Qu’est-ce qui doit être précisé ?" required></textarea>
                                <button type="submit" class="dg-btn dg-btn--quiet" style="align-self:flex-start">Demander des modifications</button>
                            </form>
                        </details>
                        <details style="margin-top:4px">
                            <summary style="cursor:pointer;font-size:13px;font-weight:600;color:var(--dg-muted)">Rejeter la proposition</summary>
                            <form method="POST" action="{{ route('missions.reject', $mission) }}" style="margin-top:10px;display:flex;flex-direction:column;gap:10px">
                                @csrf
                                <textarea name="reason" class="dg-textarea" rows="3" placeholder="Raison du rejet" required></textarea>
                                <button type="submit" class="dg-btn dg-btn--quiet" style="align-self:flex-start">Rejeter</button>
                            </form>
                        </details>
                    </x-dg.fieldset>
                @endif

                @if($mission->status === 'CHANGES_REQUESTED')
                    <x-dg.fieldset>
                        <legend><x-dg.label>Modifications demandées</x-dg.label></legend>
                        <p class="dg-hint">Reprécisez la Mission puis proposez-la à nouveau.</p>
                        <form method="POST" action="{{ route('missions.resubmit', $mission) }}" style="display:flex;flex-direction:column;gap:10px">
                            @csrf
                            <div class="dg-field">
                                <label for="description">Description corrigée</label>
                                <textarea name="description" id="description" class="dg-textarea" rows="4">{{ $mission->description }}</textarea>
                            </div>
                            <button type="submit" class="dg-btn dg-btn--saffron" style="align-self:flex-start">Proposer à nouveau</button>
                        </form>
                    </x-dg.fieldset>
                @endif

                @if($mission->status === 'OPEN' && ($canManageAssignments || ($myAssignment?->status === 'ACCEPTED' && in_array($myAssignment->role, ['EXECUTOR','CO_EXECUTOR','COORDINATOR'], true))))
                    <x-dg.fieldset>
                        <legend><x-dg.label>Démarrage</x-dg.label></legend>
                        <p class="dg-hint">Un exécutant accepté peut démarrer le travail. Accepter une participation n’entraîne jamais automatiquement le démarrage.</p>
                        <form method="POST" action="{{ route('missions.start', $mission) }}">
                            @csrf
                            <button type="submit" class="dg-btn dg-btn--primary">Démarrer la Mission</button>
                        </form>
                    </x-dg.fieldset>
                @endif

                @if($mission->status === 'IN_PROGRESS' && $canReportBlocker)
                    <x-dg.fieldset>
                        <legend><x-dg.label>Signaler un blocage</x-dg.label></legend>
                        <form method="POST" action="{{ route('missions.block', $mission) }}" style="display:flex;flex-direction:column;gap:10px">
                            @csrf
                            <div class="dg-field-grid">
                                <div class="dg-field">
                                    <label for="type">Type</label>
                                    <select name="type" id="type" class="dg-select">
                                        @foreach(\App\Models\MissionBlocker::TYPES as $type)
                                            <option value="{{ $type }}">{{ \App\Models\MissionBlocker::TYPE_LABELS[$type] ?? $type }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <textarea name="description" class="dg-textarea" rows="3" placeholder="Décrivez ce qui manque" required></textarea>
                            <button type="submit" class="dg-btn dg-btn--quiet" style="align-self:flex-start">Signaler ce blocage</button>
                        </form>
                    </x-dg.fieldset>
                @endif

                @if($mission->status === 'IN_PROGRESS' && $canConsolidateSubmission)
                    <x-dg.fieldset>
                        <legend><x-dg.label>Soumettre le résultat</x-dg.label></legend>
                        <p class="dg-hint">S’il existe un coordinateur accepté, lui seul consolide et soumet. Sinon, tout exécutant accepté peut soumettre.</p>
                        <form method="POST" action="{{ route('missions.submit', $mission) }}" style="display:flex;flex-direction:column;gap:10px">
                            @csrf
                            <textarea name="result_summary" class="dg-textarea" rows="4" placeholder="Décrivez le résultat produit" required minlength="10"></textarea>
                            <button type="submit" class="dg-btn dg-btn--primary" style="align-self:flex-start">Soumettre pour validation</button>
                        </form>
                    </x-dg.fieldset>
                @endif

                @if($mission->status === 'BLOCKED')
                    <x-dg.card>
                        <x-dg.label tone="copper">Blocages actifs</x-dg.label>
                        <div style="display:flex;flex-direction:column;gap:10px;margin-top:12px">
                            @forelse($mission->blockers->whereNull('resolved_at') as $blocker)
                                <div class="dg-note">
                                    <strong style="color:var(--dg-ink)">{{ \App\Models\MissionBlocker::TYPE_LABELS[$blocker->type] ?? $blocker->type }}</strong>
                                    <p style="margin:6px 0">{{ $blocker->description }}</p>
                                    @if($canReportBlocker)
                                        <div style="display:flex;gap:10px;flex-wrap:wrap">
                                            <form method="POST" action="{{ route('missions.blockers.resolve', [$mission, $blocker]) }}" style="display:flex;gap:8px;align-items:center;flex:1;min-width:220px">
                                                @csrf
                                                <input type="text" name="resolution_note" class="dg-input" placeholder="Note de résolution (facultatif)" style="flex:1">
                                                <button type="submit" class="dg-btn dg-btn--primary">Résoudre</button>
                                            </form>
                                            <a href="{{ route('missions.blockers.express-need.create', [$mission, $blocker]) }}" class="dg-btn dg-btn--quiet">Exprimer ce manque comme besoin →</a>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <x-dg.empty><span>Aucun blocage actif enregistré.</span></x-dg.empty>
                            @endforelse
                        </div>
                    </x-dg.card>
                @endif

                @if($mission->status === 'SUBMITTED' && $canValidate)
                    @php($latest = $mission->submissions->sortByDesc('version')->first())
                    <x-dg.fieldset>
                        <legend><x-dg.label>Validation du résultat — v{{ $latest?->version }}</x-dg.label></legend>
                        <p class="dg-body" style="margin:0 0 10px">{{ $latest?->result_summary }}</p>
                        <div style="display:flex;gap:10px;flex-wrap:wrap">
                            <form method="POST" action="{{ route('missions.validate', $mission) }}">
                                @csrf
                                <button type="submit" class="dg-btn dg-btn--primary">Valider et terminer</button>
                            </form>
                            <details>
                                <summary style="cursor:pointer;font-size:13px;font-weight:600;color:var(--dg-copper)">Demander une correction</summary>
                                <form method="POST" action="{{ route('missions.request-correction', $mission) }}" style="margin-top:10px;display:flex;flex-direction:column;gap:10px">
                                    @csrf
                                    <textarea name="note" class="dg-textarea" rows="3" placeholder="Que faut-il corriger ?" required></textarea>
                                    <button type="submit" class="dg-btn dg-btn--quiet" style="align-self:flex-start">Demander une correction</button>
                                </form>
                            </details>
                        </div>
                    </x-dg.fieldset>
                @endif

                @if($mission->status === 'COMPLETED' && $canReopen)
                    <x-dg.fieldset>
                        <legend><x-dg.label>Réouverture motivée</x-dg.label></legend>
                        <form method="POST" action="{{ route('missions.reopen', $mission) }}" style="display:flex;flex-direction:column;gap:10px">
                            @csrf
                            <textarea name="reason" class="dg-textarea" rows="3" placeholder="Raison de la réouverture" required></textarea>
                            <button type="submit" class="dg-btn dg-btn--quiet" style="align-self:flex-start">Rouvrir la Mission</button>
                        </form>
                    </x-dg.fieldset>
                @endif
            </div>

            <nav class="md-tabs" aria-label="Sections de la Mission">
                <a href="#vue-ensemble">Vue d’ensemble</a>
                <a href="#participation">Participation</a>
                <a href="#checklist">Checklist</a>
                <a href="#ressources">Capacités & ressources</a>
                <a href="#sous-missions">Sous-Missions</a>
            </nav>

            <div class="md-body">
                <div class="md-stack">

                    <div id="vue-ensemble" class="md-stack">
                        @if($mission->expected_result)
                            <section class="md-panel">
                                <x-dg.label>Résultat attendu</x-dg.label>
                                <p class="dg-body" style="margin-top:10px">{{ $mission->expected_result }}</p>
                            </section>
                        @endif

                        {{-- ===== Contributions et soumissions ===== --}}
                        <section class="md-panel">
                            <div class="md-panel-head"><h2>Contributions individuelles</h2></div>
                            <div style="display:flex;flex-direction:column;gap:8px">
                                @forelse($mission->contributions->sortByDesc('submitted_at') as $contribution)
                                    <div class="dg-note"><p style="margin:0">{{ $contribution->summary }}</p></div>
                                @empty
                                    <x-dg.empty><span>Aucune contribution déposée. Une contribution personnelle ne clôture jamais seule la Mission.</span></x-dg.empty>
                                @endforelse
                            </div>
                            @if($myAssignment?->status === 'ACCEPTED')
                                <form method="POST" action="{{ route('missions.contributions.store', $mission) }}" style="margin-top:12px;display:flex;gap:8px">
                                    @csrf
                                    <input type="text" name="summary" class="dg-input" placeholder="Décrivez votre apport" style="flex:1">
                                    <button type="submit" class="dg-btn dg-btn--quiet">Ajouter</button>
                                </form>
                            @endif
                        </section>

                        @if($mission->submissions->isNotEmpty())
                            <section class="md-panel">
                                <div class="md-panel-head"><h2>Historique des soumissions</h2></div>
                                <div style="display:flex;flex-direction:column;gap:10px">
                                    @foreach($mission->submissions->sortByDesc('version') as $submission)
                                        <div class="dg-note">
                                            <strong style="color:var(--dg-ink)">Version {{ $submission->version }}</strong>
                                            <span class="dg-meta"> · {{ $submission->decision ?? 'en attente de décision' }}</span>
                                            <p style="margin:6px 0 0">{{ $submission->result_summary }}</p>
                                            @if($submission->decision_note)<p class="dg-hint" style="margin-top:6px">{{ $submission->decision_note }}</p>@endif
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    </div>

                    {{-- ===== Participation ===== --}}
                    <section id="participation" class="md-panel">
                        <div class="md-panel-head"><h2>Participation</h2></div>
                        <div style="display:flex;flex-direction:column;gap:10px">
                            @forelse($mission->assignments->whereIn('status', ['OFFERED','INVITED','ACCEPTED']) as $assignment)
                                <div class="dg-note" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                                    <div>
                                        <strong style="color:var(--dg-ink)">{{ $participantLabels[$assignment->id] ?? 'Membre DG Afrique' }}</strong>
                                        <span class="dg-meta"> · {{ \App\Models\MissionAssignment::ROLE_LABELS[$assignment->role] ?? $assignment->role }} · {{ \App\Models\MissionAssignment::STATUS_LABELS[$assignment->status] ?? $assignment->status }}</span>
                                    </div>
                                    <div style="display:flex;gap:8px;flex-wrap:wrap">
                                        @if($assignment->status === 'OFFERED' && $canManageAssignments)
                                            <form method="POST" action="{{ route('missions.assignments.offer.accept', [$mission, $assignment]) }}"><input type="hidden">@csrf<button type="submit" class="dg-btn dg-btn--primary">Accepter</button></form>
                                            <form method="POST" action="{{ route('missions.assignments.offer.decline', [$mission, $assignment]) }}">@csrf<button type="submit" class="dg-btn dg-btn--quiet">Décliner</button></form>
                                        @endif
                                        @if($assignment->status === 'OFFERED' && hash_equals($assignment->core_identity_reference, $identity->reference))
                                            <form method="POST" action="{{ route('missions.assignments.offer.withdraw', [$mission, $assignment]) }}">@csrf<button type="submit" class="dg-btn dg-btn--quiet">Retirer mon offre</button></form>
                                        @endif
                                        @if($assignment->status === 'INVITED' && hash_equals($assignment->core_identity_reference, $identity->reference))
                                            <form method="POST" action="{{ route('missions.assignments.invitation.accept', [$mission, $assignment]) }}">@csrf<button type="submit" class="dg-btn dg-btn--primary">Accepter l’invitation</button></form>
                                            <form method="POST" action="{{ route('missions.assignments.invitation.decline', [$mission, $assignment]) }}">@csrf<button type="submit" class="dg-btn dg-btn--quiet">Décliner</button></form>
                                        @endif
                                        @if($assignment->status === 'ACCEPTED' && hash_equals($assignment->core_identity_reference, $identity->reference))
                                            <form method="POST" action="{{ route('missions.assignments.release', [$mission, $assignment]) }}">@csrf<button type="submit" class="dg-btn dg-btn--quiet">Me libérer</button></form>
                                        @endif
                                        @if($assignment->status === 'ACCEPTED' && $canManageAssignments && ! hash_equals($assignment->core_identity_reference, $identity->reference))
                                            <form method="POST" action="{{ route('missions.assignments.remove', [$mission, $assignment]) }}" onsubmit="return prompt('Raison du retrait ?') !== null">
                                                @csrf
                                                <input type="hidden" name="reason" value="Retrait décidé par l’autorité du contexte.">
                                                <button type="submit" class="dg-btn dg-btn--quiet">Retirer</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <x-dg.empty><span>Aucune participation active. Une Mission ouverte peut accueillir une offre ou une invitation.</span></x-dg.empty>
                            @endforelse
                        </div>

                        @if(in_array($mission->status, ['OPEN','IN_PROGRESS','BLOCKED'], true) && ! $myAssignment)
                            <form method="POST" action="{{ route('missions.assignments.offer', $mission) }}" style="margin-top:14px;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                                @csrf
                                <select name="role" class="dg-select" style="max-width:220px">
                                    <option value="EXECUTOR">Exécutant</option>
                                    <option value="CO_EXECUTOR">Co-exécutant</option>
                                    <option value="COORDINATOR">Coordinateur (Mission uniquement)</option>
                                    <option value="LEARNER">Apprenant</option>
                                    <option value="OBSERVER">Observateur</option>
                                </select>
                                <button type="submit" class="dg-btn dg-btn--saffron">Je me propose</button>
                            </form>
                        @endif

                        @if($canManageAssignments && in_array($mission->status, ['OPEN','IN_PROGRESS','BLOCKED'], true))
                            @if(empty($invitationCandidates))
                                <p class="dg-hint" style="margin-top:10px">Aucune personne découvrable et déjà autorisée sur ce contexte n’est disponible à inviter actuellement.</p>
                            @else
                                <form method="POST" action="{{ route('missions.assignments.invite', $mission) }}" style="margin-top:10px;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                                    @csrf
                                    <select name="discovery_reference" class="dg-select" style="flex:1;min-width:220px" required>
                                        <option value="">Choisir une personne à inviter</option>
                                        @foreach($invitationCandidates as $candidate)
                                            <option value="{{ $candidate['reference'] }}">{{ $candidate['label'] }}</option>
                                        @endforeach
                                    </select>
                                    <select name="role" class="dg-select" style="max-width:200px">
                                        <option value="EXECUTOR">Exécutant</option>
                                        <option value="CO_EXECUTOR">Co-exécutant</option>
                                        <option value="COORDINATOR">Coordinateur</option>
                                        <option value="LEARNER">Apprenant</option>
                                        <option value="OBSERVER">Observateur</option>
                                    </select>
                                    <button type="submit" class="dg-btn dg-btn--quiet">Inviter</button>
                                </form>
                            @endif
                            <div style="margin-top:10px">
                                <a href="{{ route('missions.matching', $mission) }}" class="dg-btn dg-btn--quiet">Voir les correspondances de capacités →</a>
                            </div>
                        @endif
                    </section>

                    {{-- ===== Checklist ===== --}}
                    <section id="checklist" class="md-panel">
                        <div class="md-panel-head"><h2>Checklist</h2></div>
                        <p class="dg-hint">La checklist ne clôture jamais automatiquement la Mission, même complétée à 100 %.</p>
                        <div style="display:flex;flex-direction:column;gap:8px;margin-top:12px">
                            @forelse($mission->checklistItems->sortBy('position') as $item)
                                <div style="display:flex;align-items:center;gap:10px">
                                    @if($canToggleChecklist)
                                        <form method="PUT" action="{{ route('missions.checklist.update', [$mission, $item]) }}" style="display:contents">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="completed" value="{{ $item->completed_at ? '0' : '1' }}">
                                            <button type="submit" class="dg-btn dg-btn--quiet" style="padding:6px 10px">{{ $item->completed_at ? '✓' : '—' }}</button>
                                        </form>
                                    @else
                                        <span style="padding:6px 10px">{{ $item->completed_at ? '✓' : '—' }}</span>
                                    @endif
                                    <span style="font-size:14px;{{ $item->completed_at ? 'color:var(--dg-faint);text-decoration:line-through' : 'color:var(--dg-ink)' }}">{{ $item->label }}</span>
                                    @if($item->is_required)<span class="dg-meta">obligatoire</span>@endif
                                </div>
                            @empty
                                <x-dg.empty><span>Aucun élément de checklist.</span></x-dg.empty>
                            @endforelse
                        </div>
                        @if($canOfficialize)
                            <form method="POST" action="{{ route('missions.checklist.store', $mission) }}" style="margin-top:12px;display:flex;gap:10px">
                                @csrf
                                <input type="text" name="label" class="dg-input" placeholder="Nouvel élément" style="flex:1">
                                <button type="submit" class="dg-btn dg-btn--quiet">Ajouter</button>
                            </form>
                        @endif
                    </section>

                    {{-- ===== Capacités et ressources ===== --}}
                    <div id="ressources" class="dg-grid">
                        <x-dg.card>
                            <x-dg.label>Capacités recherchées</x-dg.label>
                            <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:10px">
                                @forelse($mission->capabilityRequirements as $requirement)
                                    <x-dg.badge tone="neutral">{{ $requirement->label }}@if($requirement->requirement_level === 'PREFERRED') (souhaitée) @endif</x-dg.badge>
                                @empty
                                    <span class="dg-meta">Aucune capacité déclarée.</span>
                                @endforelse
                            </div>
                            @if($canOfficialize)
                                <form method="POST" action="{{ route('missions.requirements.capability.store', $mission) }}" style="margin-top:12px;display:flex;gap:8px">
                                    @csrf
                                    <input type="text" name="label" class="dg-input" placeholder="Capacité" style="flex:1">
                                    <input type="hidden" name="requirement_level" value="REQUIRED">
                                    <button type="submit" class="dg-btn dg-btn--quiet">Ajouter</button>
                                </form>
                            @endif
                        </x-dg.card>

                        <x-dg.card>
                            <x-dg.label>Ressources nécessaires</x-dg.label>
                            <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:10px">
                                @forelse($mission->resourceRequirements as $requirement)
                                    <x-dg.badge tone="neutral">{{ $requirement->label }}</x-dg.badge>
                                @empty
                                    <span class="dg-meta">Aucune ressource déclarée.</span>
                                @endforelse
                            </div>
                            @if($canOfficialize)
                                <form method="POST" action="{{ route('missions.requirements.resource.store', $mission) }}" style="margin-top:12px;display:flex;gap:8px">
                                    @csrf
                                    <input type="text" name="type" class="dg-input" placeholder="Type" style="max-width:120px">
                                    <input type="text" name="label" class="dg-input" placeholder="Ressource" style="flex:1">
                                    <button type="submit" class="dg-btn dg-btn--quiet">Ajouter</button>
                                </form>
                            @endif
                        </x-dg.card>
                    </div>

                    {{-- ===== Sous-Missions ===== --}}
                    <section id="sous-missions" class="md-panel">
                        <div class="md-panel-head">
                            <h2>Sous-Missions</h2>
                            @if($canOfficialize && ! in_array($mission->status, \App\Models\Mission::TERMINAL_STATUSES, true))
                                <a href="{{ route('missions.children.create', $mission) }}" class="dg-btn dg-btn--quiet" style="padding:8px 14px">Ajouter →</a>
                            @endif
                        </div>
                        <div>
                            @forelse($children as $child)
                                <x-dg.mission-row :mission="$child" />
                            @empty
                                <x-dg.empty><span>Aucune sous-Mission. Profondeur maximale : 3 niveaux.</span></x-dg.empty>
                            @endforelse
                        </div>
                    </section>

                    {{-- ===== Contributions et circulation utiles (CAP-020/021/022) ===== --}}
                    <section class="md-panel">
                        <div class="md-panel-head"><h2>Coordination et circulation</h2></div>
                        <p class="dg-body">Questions, précisions et partage avec contexte restent attachés à cette Mission. La conversation coordonne ; elle ne remplace jamais une décision Mission.</p>
                        <x-dg.actions flush>
                            @if($myAssignment?->status === 'ACCEPTED' && ! in_array($mission->status, \App\Models\Mission::TERMINAL_STATUSES, true))
                                <form method="POST" action="{{ route('messages.mission', $mission) }}" class="contents">
                                    @csrf
                                    <button type="submit" class="dg-btn dg-btn--quiet">Ouvrir la conversation Mission →</button>
                                </form>
                            @endif
                            <x-dg.btn variant="quiet" :href="route('comments.mission', $mission)">Ouvrir les commentaires →</x-dg.btn>
                            <x-dg.btn variant="quiet" :href="route('shares.mission', $mission)">Partager avec contexte →</x-dg.btn>
                        </x-dg.actions>
                    </section>

                    {{-- ===== Annulation ===== --}}
                    @if($canCancel && in_array($mission->status, ['OPEN','IN_PROGRESS','BLOCKED','SUBMITTED'], true))
                        <details>
                            <summary style="cursor:pointer;font-size:13px;font-weight:600;color:var(--dg-muted)">Annuler cette Mission</summary>
                            <form method="POST" action="{{ route('missions.cancel', $mission) }}" style="margin-top:10px;display:flex;flex-direction:column;gap:10px;max-width:480px">
                                @csrf
                                <textarea name="reason" class="dg-textarea" rows="3" placeholder="Raison de l’annulation" required></textarea>
                                <button type="submit" class="dg-btn dg-btn--quiet" style="align-self:flex-start">Annuler la Mission</button>
                            </form>
                        </details>
                    @endif
                </div>

                <aside class="md-stack">
                    {{-- BETA-READY-004 (LOT 3) — lien contextuel vers le formulaire de preuve existant,
                         visible uniquement une fois qu'un résultat réel a pu être produit ; le formulaire
                         lui-même ne préremplit ce contexte que si ProofContextService confirme la
                         visibilité (ProofController::create()), jamais une confiance au seul lien. --}}
                    @if(in_array($mission->status, ['IN_PROGRESS', 'SUBMITTED', 'COMPLETED'], true) && ($myAssignment?->status === 'ACCEPTED' || $canOfficialize))
                        <div class="md-panel">
                            <x-dg.label>Preuve</x-dg.label>
                            <p class="dg-hint" style="margin-top:6px">Ce que vous avez réellement produit ici peut devenir une preuve dans votre Carnet.</p>
                            <a href="{{ route('proofs.create', ['origin_type' => 'MISSION', 'origin_reference' => $mission->public_reference]) }}" class="dg-btn dg-btn--quiet" style="margin-top:10px;display:inline-block">Publier une preuve →</a>
                        </div>
                    @endif

                    <div class="md-panel md-quick-actions">
                        <x-dg.label>Actions rapides</x-dg.label>
                        <div style="margin-top:8px">
                            <a href="{{ route('missions.matching', $mission) }}">Voir les correspondances →</a>
                            <a href="{{ route('comments.mission', $mission) }}">Ouvrir les commentaires →</a>
                            <a href="{{ route('shares.mission', $mission) }}">Partager avec contexte →</a>
                        </div>
                    </div>

                    @if($mission->dependencies->isNotEmpty() || $canManageAssignments || ($canOfficialize && $mission->parent_mission_id === null))
                        <details class="md-deep">
                            <summary>Dépendances & récurrence</summary>
                            <div class="md-deep-body">
                                <section>
                                    <x-dg.label>Dépendances</x-dg.label>
                                    <div style="display:flex;flex-direction:column;gap:8px;margin-top:10px">
                                        @forelse($mission->dependencies as $dependency)
                                            @php($dependsOn = \App\Models\Mission::find($dependency->depends_on_mission_id))
                                            <div class="dg-note" style="display:flex;align-items:center;justify-content:space-between;gap:8px">
                                                <span style="font-size:13px">{{ \App\Models\MissionDependency::TYPE_LABELS[$dependency->type] ?? $dependency->type }} · {{ $dependsOn?->title }}</span>
                                                @if($canManageAssignments)
                                                    <form method="POST" action="{{ route('missions.dependencies.destroy', [$mission, $dependency]) }}">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="dg-btn dg-btn--quiet" style="padding:4px 8px">×</button>
                                                    </form>
                                                @endif
                                            </div>
                                        @empty
                                            <span class="dg-meta">Aucune dépendance.</span>
                                        @endforelse
                                    </div>
                                </section>

                                @if($canOfficialize && $mission->parent_mission_id === null)
                                    <section>
                                        <x-dg.label>Récurrence</x-dg.label>
                                        @if($recurrence)
                                            <p class="dg-body" style="margin-top:8px">{{ $recurrence->status }} — {{ $recurrence->rrule }}</p>
                                            <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap">
                                                @if($recurrence->status !== 'ACTIVE')
                                                    <form method="PUT" action="{{ route('missions.recurrence.resume', [$mission, $recurrence]) }}">@csrf @method('PUT')<button type="submit" class="dg-btn dg-btn--quiet">Réactiver</button></form>
                                                @endif
                                                @if($recurrence->status === 'ACTIVE')
                                                    <form method="PUT" action="{{ route('missions.recurrence.pause', [$mission, $recurrence]) }}">@csrf @method('PUT')<button type="submit" class="dg-btn dg-btn--quiet">Pause</button></form>
                                                @endif
                                                @if($recurrence->status !== 'STOPPED')
                                                    <form method="PUT" action="{{ route('missions.recurrence.stop', [$mission, $recurrence]) }}">@csrf @method('PUT')<button type="submit" class="dg-btn dg-btn--quiet">Arrêter</button></form>
                                                @endif
                                            </div>
                                        @else
                                            <form method="POST" action="{{ route('missions.recurrence.store', $mission) }}" style="margin-top:10px;display:flex;flex-direction:column;gap:8px">
                                                @csrf
                                                <input type="text" name="rrule" class="dg-input" placeholder="Ex. FREQ=WEEKLY;BYDAY=MO">
                                                <input type="text" name="timezone" class="dg-input" placeholder="Africa/Abidjan" value="Africa/Abidjan">
                                                <button type="submit" class="dg-btn dg-btn--quiet">Activer une récurrence</button>
                                            </form>
                                        @endif
                                    </section>
                                @endif
                            </div>
                        </details>
                    @endif
                </aside>
            </div>
        </div>
    </x-dg.shell>
</x-layouts.portal>
