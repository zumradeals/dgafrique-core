{{--
    Fiche Preuve — CAP-036. Aucun état ne s'appelle « vérifiée » ni « certifiée ». Chaque
    décision passe par un formulaire réel et distinct.
--}}
<x-layouts.portal title="{{ $proof->title }} — DG Afrique">
    <x-dg.shell :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:1100px">
            <a href="{{ $contextUrl ?? route('proofs.index') }}" class="dg-crumb">← {{ $contextLabel ?? 'Mon Carnet de preuves' }}</a>

            @if(session('status'))
                <div class="dg-band" style="margin-bottom:20px">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="dg-band" style="margin-bottom:20px;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>
            @endif

            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="saffron">{{ $contextLabel ?? 'Preuve autonome' }}</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">{{ $proof->title }}</h1>
                    <p>{{ $proof->description }}</p>
                </div>
                <x-dg.badge :tone="\App\Models\Proof::STATUS_BADGE_TONES[$proof->status] ?? 'neutral'">{{ \App\Models\Proof::STATUS_LABELS[$proof->status] ?? $proof->status }}</x-dg.badge>
            </div>

            @if($proof->archived_at)
                <div class="dg-band" style="margin-bottom:20px">Cette preuve est archivée depuis le {{ $proof->archived_at->format('d/m/Y') }}. Elle reste conservée et lisible aux personnes autorisées.</div>
            @endif

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div style="display:flex;flex-direction:column;gap:16px;min-width:0">

                    @if($proof->status === 'DISPUTED')
                        <x-dg.card>
                            <x-dg.label tone="copper">Contestation</x-dg.label>
                            <p class="dg-body" style="margin-top:8px">{{ $proof->dispute_note ?: 'Aucune note de contestation fournie.' }}</p>
                        </x-dg.card>
                    @endif

                    @if($canAcknowledge)
                        <x-dg.fieldset>
                            <legend><x-dg.label>Reconnaissance par le contexte</x-dg.label></legend>
                            <p class="dg-hint">Reconnaître cette preuve confirme son inscription dans {{ $contextLabel }} — cela ne garantit jamais sa véracité.</p>
                            <form method="POST" action="{{ route('proofs.acknowledge', $proof) }}">
                                @csrf
                                <button type="submit" class="dg-btn dg-btn--primary">Reconnaître cette preuve</button>
                            </form>
                        </x-dg.fieldset>
                    @endif

                    {{-- ===== Témoins ===== --}}
                    <x-dg.card>
                        <x-dg.label>Témoins</x-dg.label>
                        <p class="dg-hint" style="margin-top:6px">Le témoignage est optionnel. Une preuve sans témoin reste pleinement valide.</p>
                        <div style="display:flex;flex-direction:column;gap:10px;margin-top:12px">
                            @forelse($proof->witnesses as $witness)
                                <div class="dg-note" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                                    <div>
                                        <strong style="color:var(--dg-ink)">{{ hash_equals($witness->core_identity_reference, $identity->reference) ? 'Vous' : 'Membre DG Afrique' }}</strong>
                                        <span class="dg-meta"> · {{ $witness->status }}</span>
                                    </div>
                                    @if($witness->status === 'INVITED' && hash_equals($witness->core_identity_reference, $identity->reference))
                                        <div style="display:flex;gap:8px">
                                            <form method="POST" action="{{ route('proofs.witnesses.confirm', [$proof, $witness]) }}">@csrf<button type="submit" class="dg-btn dg-btn--primary">Confirmer</button></form>
                                            <form method="POST" action="{{ route('proofs.witnesses.decline', [$proof, $witness]) }}">@csrf<button type="submit" class="dg-btn dg-btn--quiet">Décliner</button></form>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <x-dg.empty><span>Aucun témoin invité pour le moment.</span></x-dg.empty>
                            @endforelse
                        </div>
                        @if($canInviteWitness && ! $proof->archived_at)
                            <form method="POST" action="{{ route('proofs.witnesses.invite', $proof) }}" style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap">
                                @csrf
                                <input type="text" name="discovery_reference" class="dg-input" placeholder="Référence publique de la personne" style="flex:1;min-width:220px" required>
                                <button type="submit" class="dg-btn dg-btn--quiet">Inviter un témoin</button>
                            </form>
                        @endif
                    </x-dg.card>

                    {{-- ===== Références ===== --}}
                    <x-dg.card>
                        <x-dg.label>Références</x-dg.label>
                        <div style="display:flex;flex-direction:column;gap:8px;margin-top:12px">
                            @forelse($proof->references as $reference)
                                <div class="dg-note">
                                    <span class="dg-meta">{{ $reference->type }}</span>
                                    <p style="margin:4px 0 0">{{ $reference->label ?: $reference->value }}</p>
                                </div>
                            @empty
                                <x-dg.empty><span>Aucune référence jointe. Le stockage documentaire fédéré (GamaDrive) n’existe pas encore.</span></x-dg.empty>
                            @endforelse
                        </div>
                    </x-dg.card>

                    {{-- ===== Coordination (CAP-020/021/022) ===== --}}
                    <x-dg.card>
                        <x-dg.label>Coordination et circulation</x-dg.label>
                        <p class="dg-body" style="margin-top:8px">Questions, précisions et partage avec contexte restent attachés à cette preuve.</p>
                        <x-dg.actions flush>
                            <x-dg.btn variant="quiet" :href="route('comments.proof', $proof)">Ouvrir les commentaires →</x-dg.btn>
                            <x-dg.btn variant="quiet" :href="route('shares.proof', $proof)">Partager avec contexte →</x-dg.btn>
                        </x-dg.actions>
                    </x-dg.card>

                    @if($canDispute || $canArchive || $canRestore)
                        <details>
                            <summary style="cursor:pointer;font-size:13px;font-weight:600;color:var(--dg-muted)">Autres actions</summary>
                            <div style="margin-top:10px;display:flex;flex-direction:column;gap:14px;max-width:480px">
                                @if($canDispute)
                                    <form method="POST" action="{{ route('proofs.dispute', $proof) }}" style="display:flex;flex-direction:column;gap:10px">
                                        @csrf
                                        <textarea name="note" class="dg-textarea" rows="2" placeholder="Raison de la contestation (facultatif)"></textarea>
                                        <button type="submit" class="dg-btn dg-btn--quiet" style="align-self:flex-start">Contester cette preuve</button>
                                    </form>
                                @endif
                                @if($canArchive)
                                    <form method="POST" action="{{ route('proofs.archive', $proof) }}">
                                        @csrf
                                        <button type="submit" class="dg-btn dg-btn--quiet">Archiver (réversible)</button>
                                    </form>
                                @endif
                                @if($canRestore)
                                    <form method="POST" action="{{ route('proofs.restore', $proof) }}">
                                        @csrf
                                        <button type="submit" class="dg-btn dg-btn--quiet">Restaurer</button>
                                    </form>
                                @endif
                            </div>
                        </details>
                    @endif
                </div>

                <aside style="display:flex;flex-direction:column;gap:16px">
                    <x-dg.card tight>
                        <dl class="dg-dl">
                            <div><dt>Contexte</dt><dd>{{ $contextLabel ?? 'Aucun — autonome' }}</dd></div>
                            <div><dt>Visibilité</dt><dd>{{ $proof->visibility }}</dd></div>
                            @if($proof->capability_label)<div><dt>Capacité</dt><dd>{{ $proof->capability_label }}</dd></div>@endif
                            <div><dt>Survenue le</dt><dd>{{ $proof->occurred_at->format('d/m/Y') }}</dd></div>
                        </dl>
                    </x-dg.card>
                </aside>
            </div>
        </div>
    </x-dg.shell>
</x-layouts.portal>
