{{--
    Fiche Preuve — CAP-036, harmonisée UX-HARMONY-TRANSMISSIONS-PROOFS-001 (famille .pf-*).
    Aucun état ne s'appelle « vérifiée » ni « certifiée ». Pas de trajectoire linéaire fabriquée :
    WITNESSED et ACKNOWLEDGED sont deux corroborations indépendantes, DISPUTED est une branche
    réversible — les .pf-signal reflètent l'état réel, jamais un score. Chaque décision passe par
    un formulaire réel et distinct, repris tel quel de la version précédente.
--}}
@php
    $hasConfirmedWitness = $proof->witnesses->contains(fn ($w) => $w->status === 'CONFIRMED');
    $isAcknowledged = $proof->context_acknowledged_at !== null;
    $isDisputed = $proof->status === 'DISPUTED';
@endphp
<x-layouts.portal title="{{ $proof->title }} — DG Afrique">
    <x-dg.shell :identity="$identity" :is-administrator="$isAdministrator">
        <div class="pf-page">
            <a href="{{ $contextUrl ?? route('proofs.index') }}" class="pf-crumb">← {{ $contextLabel ?? 'Mon Carnet de preuves' }}</a>

            @if(session('status'))
                <div class="dg-band" style="margin-bottom:16px">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="dg-band" style="margin-bottom:16px;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>
            @endif

            <section class="pf-hero">
                <div class="pf-hero-top">
                    <div class="pf-tags"><span>{{ $contextLabel ?? 'Preuve autonome' }}</span></div>
                    <x-dg.badge :tone="\App\Models\Proof::STATUS_BADGE_TONES[$proof->status] ?? 'neutral'">{{ \App\Models\Proof::STATUS_LABELS[$proof->status] ?? $proof->status }}</x-dg.badge>
                </div>
                <h1>{{ $proof->title }}</h1>
                <p>{{ $proof->description }}</p>
                <div class="pf-facts">
                    <span>Visibilité<strong>{{ \App\Models\Proof::VISIBILITY_LABELS[$proof->visibility] ?? $proof->visibility }}</strong></span>
                    @if($proof->capability_label)<span>Capacité<strong>{{ $proof->capability_label }}</strong></span>@endif
                    <span>Survenue le<strong>{{ $proof->occurred_at->format('d/m/Y') }}</strong></span>
                </div>
                <div class="pf-signals">
                    <span class="pf-signal {{ $hasConfirmedWitness ? 'is-positive' : '' }}"><i></i>Témoin {{ $hasConfirmedWitness ? 'confirmé' : 'aucun' }}</span>
                    <span class="pf-signal {{ $isAcknowledged ? 'is-positive' : '' }}"><i></i>{{ $isAcknowledged ? 'Reconnue par le contexte' : 'Non reconnue par un contexte' }}</span>
                    @if($isDisputed)
                        <span class="pf-signal is-negative"><i></i>Contestée</span>
                    @endif
                    @if($proof->archived_at)
                        <span class="pf-signal"><i></i>Archivée</span>
                    @endif
                </div>
            </section>

            <div class="pf-action-zone">
                @if($proof->archived_at)
                    <div class="dg-band">Cette preuve est archivée depuis le {{ $proof->archived_at->format('d/m/Y') }}. Elle reste conservée et lisible aux personnes autorisées.</div>
                @endif

                @if($isDisputed)
                    <x-dg.fieldset>
                        <legend><x-dg.label tone="copper">Contestation</x-dg.label></legend>
                        <p class="dg-body">{{ $proof->dispute_note ?: 'Aucune note de contestation fournie.' }}</p>
                    </x-dg.fieldset>
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
            </div>

            <nav class="pf-tabs" style="display:flex;overflow-x:auto;margin-top:16px;padding:0 6px;border:1px solid #e5e3dc;border-radius:14px;background:#fff;box-shadow:0 5px 18px rgba(12,47,35,.045)">
                <a href="#temoins" style="flex:none;padding:13px 16px;color:#26332d;text-decoration:none;font-size:12.5px;font-weight:700;white-space:nowrap">Témoins</a>
                <a href="#references" style="flex:none;padding:13px 16px;color:#26332d;text-decoration:none;font-size:12.5px;font-weight:700;white-space:nowrap">Références</a>
            </nav>

            <div class="pf-body">
                <div class="pf-stack">
                    {{-- ===== Témoins ===== --}}
                    <div class="pf-panel" id="temoins">
                        <div class="pf-panel-head"><h2>Témoins</h2></div>
                        <p class="dg-hint" style="margin-top:-4px">Le témoignage est optionnel. Une preuve sans témoin reste pleinement valide.</p>
                        <div style="display:flex;flex-direction:column;gap:10px;margin-top:12px">
                            @forelse($proof->witnesses as $witness)
                                <div class="dg-note" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                                    <div>
                                        <strong style="color:var(--dg-ink)">{{ hash_equals($witness->core_identity_reference, $identity->reference) ? 'Vous' : 'Membre DG Afrique' }}</strong>
                                        <span class="dg-meta"> · {{ \App\Models\ProofWitness::STATUS_LABELS[$witness->status] ?? $witness->status }}</span>
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
                    </div>

                    {{-- ===== Références ===== --}}
                    <div class="pf-panel" id="references">
                        <div class="pf-panel-head"><h2>Références</h2></div>
                        <div style="display:flex;flex-direction:column;gap:8px">
                            @forelse($proof->references as $reference)
                                <div class="dg-note">
                                    <span class="dg-meta">{{ \App\Models\ProofReference::TYPE_LABELS[$reference->type] ?? $reference->type }}</span>
                                    <p style="margin:4px 0 0">{{ $reference->label ?: $reference->value }}</p>
                                </div>
                            @empty
                                <x-dg.empty><span>Aucune référence jointe. Le stockage documentaire fédéré (GamaDrive) n’existe pas encore.</span></x-dg.empty>
                            @endforelse
                        </div>
                    </div>

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

                <aside class="pf-stack">
                    <div class="pf-panel pf-quick-actions">
                        <div class="pf-panel-head"><h2>Coordination</h2></div>
                        <p class="dg-hint" style="margin-top:-4px;margin-bottom:8px">Questions, précisions et partage restent attachés à cette preuve.</p>
                        <a href="{{ route('comments.proof', $proof) }}">Ouvrir les commentaires →</a>
                        <a href="{{ route('shares.proof', $proof) }}">Partager avec contexte →</a>
                    </div>
                </aside>
            </div>
        </div>
    </x-dg.shell>
</x-layouts.portal>
