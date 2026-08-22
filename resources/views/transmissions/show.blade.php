{{--
    Fiche Transmission — CAP-006. Chaque décision sensible passe par un formulaire réel et
    distinct (démarrer ≠ déclarer terminé ≠ confirmer la clôture ≠ valider par le contexte).
    Aucune certification automatique, aucun rôle ZUMRA/Projet créé ici, aucune finance.
--}}
<x-layouts.portal title="{{ $transmission->capability_label }} — DG Afrique">
    <x-dg.shell :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:1200px">
            <a href="{{ $contextUrl ?? route('transmissions.index') }}" class="dg-crumb">← {{ $contextLabel ?? 'Mes Transmissions' }}</a>

            @if(session('status'))
                <div class="dg-band" style="margin-bottom:20px">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="dg-band" style="margin-bottom:20px;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>
            @endif

            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="saffron">{{ $contextLabel ?? 'Transmission privée' }}</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">{{ $transmission->capability_label }}</h1>
                    <p>{{ $transmission->learning_objective }}</p>
                </div>
                <x-dg.badge :tone="\App\Models\Transmission::STATUS_BADGE_TONES[$transmission->status] ?? 'neutral'">{{ \App\Models\Transmission::STATUS_LABELS[$transmission->status] ?? $transmission->status }}</x-dg.badge>
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div style="display:flex;flex-direction:column;gap:16px;min-width:0">

                    @if($canOfficializeContext && $transmission->context_officialized_at === null)
                        <x-dg.fieldset>
                            <legend><x-dg.label>Officialiser le rattachement contextuel</x-dg.label></legend>
                            <p class="dg-hint">Officialiser rend cette Transmission reconnue comme activité du contexte. Cela n’accepte jamais une participation à la place d’un participant.</p>
                            <form method="POST" action="{{ route('transmissions.officialize-context', $transmission) }}" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                                @csrf
                                <select name="visibility" class="dg-select" style="max-width:260px">
                                    <option value="">Garder la visibilité actuelle</option>
                                    <option value="CONTEXT">Élargir au contexte</option>
                                    <option value="PROGRAM">Élargir au Programme ZUMRA</option>
                                </select>
                                <button type="submit" class="dg-btn dg-btn--quiet">Officialiser</button>
                            </form>
                        </x-dg.fieldset>
                    @endif

                    @if($canStart)
                        <x-dg.fieldset>
                            <legend><x-dg.label>Démarrage</x-dg.label></legend>
                            <p class="dg-hint">Un participant accepté peut démarrer la Transmission. Accepter une participation n’entraîne jamais automatiquement le démarrage.</p>
                            <form method="POST" action="{{ route('transmissions.start', $transmission) }}">
                                @csrf
                                <button type="submit" class="dg-btn dg-btn--primary">Démarrer la Transmission</button>
                            </form>
                        </x-dg.fieldset>
                    @endif

                    @if($canDeclareDone || $canConfirmCompletion)
                        <x-dg.fieldset>
                            <legend><x-dg.label>Clôture</x-dg.label></legend>
                            <p class="dg-hint">Déclarer votre part terminée ne valide jamais seul la Transmission : la confirmation reste une décision distincte, une fois qu’un transmetteur et un apprenant ont chacun déclaré leur part.</p>
                            @if($canDeclareDone)
                                <form method="POST" action="{{ route('transmissions.declare-done', $transmission) }}" style="display:flex;gap:10px;margin-bottom:10px;flex-wrap:wrap">
                                    @csrf
                                    <input type="text" name="note" class="dg-input" placeholder="Note facultative" style="flex:1;min-width:200px">
                                    <button type="submit" class="dg-btn dg-btn--quiet">Déclarer ma part terminée</button>
                                </form>
                            @endif
                            @if($canConfirmCompletion)
                                <form method="POST" action="{{ route('transmissions.confirm-completion', $transmission) }}" style="display:flex;flex-direction:column;gap:10px">
                                    @csrf
                                    <textarea name="summary" class="dg-textarea" rows="3" minlength="5" placeholder="Résumé court de ce qui a été transmis" required></textarea>
                                    <button type="submit" class="dg-btn dg-btn--primary" style="align-self:flex-start">Confirmer la clôture</button>
                                </form>
                            @endif
                        </x-dg.fieldset>
                    @endif

                    @if($canValidateByContext)
                        <x-dg.fieldset>
                            <legend><x-dg.label>Validation par le contexte</x-dg.label></legend>
                            <p class="dg-hint">L’autorité de {{ $contextLabel }} peut valider la réalisation de cette Transmission.</p>
                            <form method="POST" action="{{ route('transmissions.validate-by-context', $transmission) }}" style="display:flex;flex-direction:column;gap:10px">
                                @csrf
                                <textarea name="note" class="dg-textarea" rows="3" placeholder="Note de validation (facultatif)"></textarea>
                                <button type="submit" class="dg-btn dg-btn--primary" style="align-self:flex-start">Valider la réalisation</button>
                            </form>
                        </x-dg.fieldset>
                    @endif

                    {{-- ===== Participation ===== --}}
                    <x-dg.card>
                        <x-dg.label>Participants</x-dg.label>
                        <div style="display:flex;flex-direction:column;gap:10px;margin-top:12px">
                            @forelse($transmission->participants->whereIn('status', ['OFFERED','INVITED','ACCEPTED']) as $participant)
                                <div class="dg-note" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                                    <div>
                                        <strong style="color:var(--dg-ink)">{{ hash_equals($participant->core_identity_reference, $identity->reference) ? 'Vous' : 'Membre DG Afrique' }}</strong>
                                        <span class="dg-meta"> · {{ $participant->role === 'TRANSMITTER' ? 'Transmetteur' : 'Apprenant' }} · {{ $participant->status }}@if($participant->declared_done_at) · part déclarée terminée @endif</span>
                                    </div>
                                    <div style="display:flex;gap:8px;flex-wrap:wrap">
                                        @if($participant->status === 'OFFERED' && $canManageParticipation)
                                            <form method="POST" action="{{ route('transmissions.participants.offer.accept', [$transmission, $participant]) }}">@csrf<button type="submit" class="dg-btn dg-btn--primary">Accepter</button></form>
                                            <form method="POST" action="{{ route('transmissions.participants.offer.decline', [$transmission, $participant]) }}">@csrf<button type="submit" class="dg-btn dg-btn--quiet">Décliner</button></form>
                                        @endif
                                        @if($participant->status === 'OFFERED' && hash_equals($participant->core_identity_reference, $identity->reference))
                                            <form method="POST" action="{{ route('transmissions.participants.offer.withdraw', [$transmission, $participant]) }}">@csrf<button type="submit" class="dg-btn dg-btn--quiet">Retirer ma proposition</button></form>
                                        @endif
                                        @if($participant->status === 'INVITED' && hash_equals($participant->core_identity_reference, $identity->reference))
                                            <form method="POST" action="{{ route('transmissions.participants.invitation.accept', [$transmission, $participant]) }}">@csrf<button type="submit" class="dg-btn dg-btn--primary">Accepter l’invitation</button></form>
                                            <form method="POST" action="{{ route('transmissions.participants.invitation.decline', [$transmission, $participant]) }}">@csrf<button type="submit" class="dg-btn dg-btn--quiet">Décliner</button></form>
                                        @endif
                                        @if($participant->status === 'ACCEPTED' && hash_equals($participant->core_identity_reference, $identity->reference))
                                            <form method="POST" action="{{ route('transmissions.participants.leave', [$transmission, $participant]) }}">@csrf<button type="submit" class="dg-btn dg-btn--quiet">Quitter</button></form>
                                        @endif
                                        @if($participant->status === 'ACCEPTED' && $canManageParticipation && ! hash_equals($participant->core_identity_reference, $identity->reference))
                                            <form method="POST" action="{{ route('transmissions.participants.remove', [$transmission, $participant]) }}" onsubmit="return prompt('Raison du retrait ?') !== null">
                                                @csrf
                                                <input type="hidden" name="reason" value="Retrait décidé par un transmetteur accepté.">
                                                <button type="submit" class="dg-btn dg-btn--quiet">Retirer</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <x-dg.empty><span>Aucun participant actif.</span></x-dg.empty>
                            @endforelse
                        </div>

                        @if(! $myParticipant && ! in_array($transmission->status, \App\Models\Transmission::TERMINAL_STATUSES, true))
                            <form method="POST" action="{{ route('transmissions.participants.offer', $transmission) }}" style="margin-top:14px;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                                @csrf
                                <select name="role" class="dg-select" style="max-width:260px">
                                    <option value="LEARNER">Je propose d’apprendre</option>
                                    <option value="TRANSMITTER">Je propose de transmettre</option>
                                </select>
                                <button type="submit" class="dg-btn dg-btn--saffron">Je me propose</button>
                            </form>
                        @endif

                        @if($canManageParticipation && ! in_array($transmission->status, \App\Models\Transmission::TERMINAL_STATUSES, true))
                            @if(empty($invitationCandidates))
                                <p class="dg-hint" style="margin-top:10px">Aucune personne découvrable n’est disponible à inviter actuellement.</p>
                            @else
                                <form method="POST" action="{{ route('transmissions.participants.invite', $transmission) }}" style="margin-top:10px;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                                    @csrf
                                    <select name="discovery_reference" class="dg-select" style="flex:1;min-width:220px" required>
                                        <option value="">Choisir une personne à inviter</option>
                                        @foreach($invitationCandidates as $candidate)
                                            <option value="{{ $candidate['reference'] }}">{{ $candidate['label'] }}</option>
                                        @endforeach
                                    </select>
                                    <select name="role" class="dg-select" style="max-width:200px">
                                        <option value="LEARNER">Apprenant</option>
                                        <option value="TRANSMITTER">Transmetteur</option>
                                    </select>
                                    <button type="submit" class="dg-btn dg-btn--quiet">Inviter</button>
                                </form>
                            @endif
                            <div style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap">
                                <a href="{{ route('transmissions.matching', ['transmission' => $transmission, 'role' => 'LEARNER']) }}" class="dg-btn dg-btn--quiet">Trouver des apprenants →</a>
                                <a href="{{ route('transmissions.matching', ['transmission' => $transmission, 'role' => 'TRANSMITTER']) }}" class="dg-btn dg-btn--quiet">Trouver des transmetteurs →</a>
                            </div>
                        @endif
                    </x-dg.card>

                    {{-- ===== Jalons ===== --}}
                    <x-dg.card>
                        <x-dg.label>Étapes</x-dg.label>
                        <p class="dg-hint" style="margin-top:6px">Les étapes sont facultatives et ne clôturent jamais automatiquement la Transmission, même complétées à 100 %.</p>
                        <div style="display:flex;flex-direction:column;gap:8px;margin-top:12px">
                            @forelse($transmission->milestones->sortBy('position') as $milestone)
                                <div style="display:flex;align-items:center;gap:10px">
                                    @if($canAddMilestoneOrContribution)
                                        <form method="PUT" action="{{ route('transmissions.milestones.update', [$transmission, $milestone]) }}" style="display:contents">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="completed" value="{{ $milestone->completed_at ? '0' : '1' }}">
                                            <button type="submit" class="dg-btn dg-btn--quiet" style="padding:6px 10px">{{ $milestone->completed_at ? '✓' : '—' }}</button>
                                        </form>
                                    @else
                                        <span style="padding:6px 10px">{{ $milestone->completed_at ? '✓' : '—' }}</span>
                                    @endif
                                    <span style="font-size:14px;{{ $milestone->completed_at ? 'color:var(--dg-faint);text-decoration:line-through' : 'color:var(--dg-ink)' }}">{{ $milestone->label }}</span>
                                </div>
                            @empty
                                <x-dg.empty><span>Aucune étape déclarée.</span></x-dg.empty>
                            @endforelse
                        </div>
                        @if($canAddMilestoneOrContribution)
                            <form method="POST" action="{{ route('transmissions.milestones.store', $transmission) }}" style="margin-top:12px;display:flex;gap:10px">
                                @csrf
                                <input type="text" name="label" class="dg-input" placeholder="Nouvelle étape" style="flex:1">
                                <button type="submit" class="dg-btn dg-btn--quiet">Ajouter</button>
                            </form>
                        @endif
                    </x-dg.card>

                    {{-- ===== Contributions / traces ===== --}}
                    <x-dg.card>
                        <x-dg.label>Traces de la Transmission</x-dg.label>
                        <p class="dg-hint" style="margin-top:6px">Ces notes constituent une trace d’expérience contextualisée — jamais une preuve certifiée automatiquement.</p>
                        <div style="display:flex;flex-direction:column;gap:8px;margin-top:12px">
                            @forelse($transmission->contributions->sortByDesc('occurred_at') as $contribution)
                                <div class="dg-note"><p style="margin:0">{{ $contribution->note }}</p></div>
                            @empty
                                <x-dg.empty><span>Aucune trace déposée pour le moment.</span></x-dg.empty>
                            @endforelse
                        </div>
                        @if($canAddMilestoneOrContribution)
                            <form method="POST" action="{{ route('transmissions.contributions.store', $transmission) }}" style="margin-top:12px;display:flex;gap:8px">
                                @csrf
                                <input type="text" name="note" class="dg-input" placeholder="Décrivez ce qui s’est passé" style="flex:1">
                                <button type="submit" class="dg-btn dg-btn--quiet">Ajouter</button>
                            </form>
                        @endif
                    </x-dg.card>

                    @if($transmission->completion_summary)
                        <x-dg.card>
                            <x-dg.label tone="copper">Résumé de clôture</x-dg.label>
                            <p class="dg-body" style="margin-top:10px">{{ $transmission->completion_summary }}</p>
                        </x-dg.card>
                    @endif

                    @if($myParticipant?->status === \App\Models\TransmissionParticipant::STATUS_ACCEPTED && in_array($transmission->status, [\App\Models\Transmission::STATUS_COMPLETED_CONFIRMED, \App\Models\Transmission::STATUS_COMPLETED_BY_CONTEXT], true))
                        {{-- UIUX-007 — Transmission → Preuve : une Transmission réellement
                             terminée ouvre une porte facultative vers le Carnet de preuves.
                             Jamais de preuve créée automatiquement ; jamais une certification de
                             compétence — les règles de témoin/reconnaissance/contestation restent
                             entièrement celles du Carnet de preuves. --}}
                        <x-dg.card>
                            <x-dg.label>Garder une trace</x-dg.label>
                            <p class="dg-hint" style="margin-top:6px">Cette Transmission est terminée. Vous pouvez, si vous le souhaitez, en garder une trace dans votre Carnet de preuves — cela ne certifie rien automatiquement.</p>
                            <x-dg.actions flush>
                                <x-dg.btn variant="quiet" :href="route('proofs.create', ['origin_type' => 'TRANSMISSION', 'origin_reference' => $transmission->public_reference])">Enregistrer une preuve →</x-dg.btn>
                            </x-dg.actions>
                        </x-dg.card>
                    @endif

                    {{-- ===== Coordination (CAP-020/021/022) ===== --}}
                    <x-dg.card>
                        <x-dg.label>Coordination et circulation</x-dg.label>
                        <p class="dg-body" style="margin-top:8px">Questions, précisions et partage avec contexte restent attachés à cette Transmission. La conversation coordonne ; elle ne remplace jamais une décision.</p>
                        <x-dg.actions flush>
                            @if($myParticipant?->status === 'ACCEPTED' && ! in_array($transmission->status, \App\Models\Transmission::TERMINAL_STATUSES, true))
                                <form method="POST" action="{{ route('messages.transmission', $transmission) }}" class="contents">
                                    @csrf
                                    <button type="submit" class="dg-btn dg-btn--quiet">Ouvrir la conversation →</button>
                                </form>
                            @endif
                            <x-dg.btn variant="quiet" :href="route('comments.transmission', $transmission)">Ouvrir les commentaires →</x-dg.btn>
                            <x-dg.btn variant="quiet" :href="route('shares.transmission', $transmission)">Partager avec contexte →</x-dg.btn>
                        </x-dg.actions>
                    </x-dg.card>

                    {{-- ===== Fin ===== --}}
                    @if($canEnd || $canCancel)
                        <details>
                            <summary style="cursor:pointer;font-size:13px;font-weight:600;color:var(--dg-muted)">Arrêter ou annuler cette Transmission</summary>
                            <div style="margin-top:10px;display:flex;flex-direction:column;gap:14px;max-width:480px">
                                @if($canEnd)
                                    <form method="POST" action="{{ route('transmissions.end', $transmission) }}" style="display:flex;flex-direction:column;gap:10px">
                                        @csrf
                                        <textarea name="reason" class="dg-textarea" rows="2" placeholder="Raison de l’arrêt (facultatif)"></textarea>
                                        <button type="submit" class="dg-btn dg-btn--quiet" style="align-self:flex-start">Arrêter, sans valider de résultat</button>
                                    </form>
                                @endif
                                @if($canCancel)
                                    <form method="POST" action="{{ route('transmissions.cancel', $transmission) }}" style="display:flex;flex-direction:column;gap:10px">
                                        @csrf
                                        <textarea name="reason" class="dg-textarea" rows="2" placeholder="Raison de l’annulation (facultatif)"></textarea>
                                        <button type="submit" class="dg-btn dg-btn--quiet" style="align-self:flex-start">Annuler avant réalisation</button>
                                    </form>
                                @endif
                            </div>
                        </details>
                    @endif
                </div>

                <aside style="display:flex;flex-direction:column;gap:16px">
                    <x-dg.card tight>
                        <dl class="dg-dl">
                            <div><dt>Contexte</dt><dd>{{ $contextLabel ?? 'Aucun — privée' }}</dd></div>
                            <div><dt>Visibilité</dt><dd>{{ $transmission->visibility }}</dd></div>
                            @if($transmission->availability_note)<div><dt>Disponibilité</dt><dd>{{ $transmission->availability_note }}</dd></div>@endif
                            <div><dt>Origine</dt><dd>{{ $transmission->origin_type }}</dd></div>
                        </dl>
                    </x-dg.card>
                </aside>
            </div>
        </div>
    </x-dg.shell>
</x-layouts.portal>
