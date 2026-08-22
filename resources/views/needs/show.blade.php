{{--
    Fiche Besoin — même objet métier que dans le Fil (x-dg.feed.need), avec l'historique de décision
    et les transitions réelles de NeedService::transition(). Aucune décision n'est simulée ici.
--}}
<x-layouts.portal title="{{ $need->title }} — DG Afrique">
    <x-dg.shell current="besoins" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:1100px">
            <a href="{{ route('needs.index') }}" class="dg-crumb">← Tous les besoins</a>

            @if(session('status'))
                <div class="dg-band" style="margin-bottom:20px">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="dg-band" style="margin-bottom:20px;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>
            @endif

            <div class="dg-page-header">
                <div>
                    <x-dg.badge tone="need">{{ $configuration['categories'][$need->category] ?? $need->category }}</x-dg.badge>
                    <h1 class="dg-display dg-display--screen" style="margin-top:10px">{{ $need->title }}</h1>
                    {{-- UIUX-003 : retour réel vers le contexte porteur, même patron que
                         missions.show/proofs.show (contextUrl/contextLabel) — jamais un lien
                         fabriqué : seul un propriétaire réellement visible devient un lien. --}}
                    @if($need->owner_type === 'GROUP' && $group)
                        <p><a href="{{ route('zumra.groups.show', $group) }}" style="color:var(--dg-copper);font-weight:600">{{ $group->name }}</a></p>
                    @elseif($need->owner_type === 'PROJECT' && $project)
                        <p><a href="{{ route('projects.show', $project) }}" style="color:var(--dg-copper);font-weight:600">{{ $project->name }}</a></p>
                    @else
                        <p>{{ match($need->owner_type) { 'GROUP' => 'ZUMRA', 'PROJECT' => 'Projet', default => 'Besoin personnel' } }}</p>
                    @endif
                </div>
                <x-dg.label>{{ ['PROPOSED' => 'Proposé', 'OPEN' => 'Ouvert', 'IN_PROGRESS' => 'En cours', 'RESOLVED' => 'Résolu', 'ARCHIVED' => 'Archivé'][$need->status] ?? $need->status }}</x-dg.label>
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_300px]">
                <div style="display:flex;flex-direction:column;gap:16px">
                    @if($need->image_path)
                        <img src="{{ Illuminate\Support\Facades\Storage::disk('public')->url($need->image_path) }}" alt=""
                             style="width:100%;max-height:420px;object-fit:cover;border-radius:var(--dg-radius-card);border:1px solid var(--dg-line)">
                    @endif
                    <x-dg.card>
                        <x-dg.label>Contexte</x-dg.label>
                        <p class="dg-body" style="margin-top:12px;white-space:pre-line">{{ $need->context }}</p>
                        @if($need->resolution_note)
                            <x-dg.note style="margin-top:16px">
                                <strong style="display:block;color:var(--dg-forest);margin-bottom:4px">Résolution déclarée</strong>
                                {{ $need->resolution_note }}
                            </x-dg.note>
                        @endif
                    </x-dg.card>

                    @if($canDecide)
                        <x-dg.fieldset>
                            <legend>
                                <x-dg.label>Faire évoluer ce besoin</x-dg.label>
                            </legend>
                            <p class="dg-body" style="margin:0">Chaque décision est journalisée. Résoudre signifie décrire ce qui a réellement changé.</p>
                            <x-dg.actions flush>
                                @if($need->status === 'PROPOSED')
                                    <form method="POST" action="{{ route('needs.transition', $need) }}">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="status" value="OPEN">
                                        <button type="submit" class="dg-btn dg-btn--primary">Publier officiellement</button>
                                    </form>
                                @endif
                                @if($need->status === 'OPEN')
                                    <form method="POST" action="{{ route('needs.transition', $need) }}">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="status" value="IN_PROGRESS">
                                        <button type="submit" class="dg-btn dg-btn--primary">Marquer en cours</button>
                                    </form>
                                @endif
                                @if(in_array($need->status, ['OPEN', 'IN_PROGRESS']))
                                    <details style="width:100%">
                                        <summary class="dg-btn dg-btn--project" style="cursor:pointer;display:inline-flex">Déclarer résolu</summary>
                                        <form method="POST" action="{{ route('needs.transition', $need) }}" style="margin-top:12px;display:flex;flex-direction:column;gap:10px">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="status" value="RESOLVED">
                                            <textarea name="resolution_note" class="dg-textarea" placeholder="Comment le besoin a-t-il été résolu ?" required></textarea>
                                            <button type="submit" class="dg-btn dg-btn--primary" style="align-self:flex-start">Confirmer la résolution</button>
                                        </form>
                                    </details>
                                @endif
                                @if($need->status !== 'ARCHIVED')
                                    <form method="POST" action="{{ route('needs.transition', $need) }}">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="status" value="ARCHIVED">
                                        <button type="submit" class="dg-btn dg-btn--quiet">Archiver</button>
                                    </form>
                                @endif
                            </x-dg.actions>
                        </x-dg.fieldset>
                    @endif

                    {{-- UIUX-005 — Partenariats réellement associés à ce Besoin (CAP-065), filtrés
                         par PartnershipService::canView(), jamais recalculée en Blade. --}}
                    @if($needPartnerships !== [])
                        <div>
                            <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:10px">
                                <x-dg.label>Partenariats</x-dg.label>
                                <span class="dg-meta">{{ count($needPartnerships) }} partenariat{{ count($needPartnerships) > 1 ? 's' : '' }}</span>
                            </div>
                            @foreach($needPartnerships as $row)
                                <div style="margin-bottom:12px"><x-dg.partnership-row :row="$row" /></div>
                            @endforeach
                        </div>
                    @endif

                    <x-dg.partnership-propose-form :organizations="$manageableOrganizations" context-type="NEED" :context-reference="$need->public_reference" />
                </div>

                <aside style="display:flex;flex-direction:column;gap:16px">
                    <x-dg.card tight>
                        <dl class="dg-dl">
                            <div>
                                <dt>Collaboration</dt>
                                <dd>{{ ['LOCAL' => 'Sur place', 'REMOTE' => 'À distance', 'HYBRID' => 'Hybride', 'ANY' => 'Indifférent'][$need->collaboration_mode] ?? $need->collaboration_mode }}</dd>
                            </div>
                            <div>
                                <dt>Localisation</dt>
                                <dd>{{ $need->location ?: 'Non précisée' }}</dd>
                            </div>
                            <div>
                                <dt>Visibilité</dt>
                                <dd>{{ ['PRIVATE' => 'Privée', 'GROUP' => 'ZUMRA', 'PROGRAM' => 'Membres ZUMRA', 'PUBLIC' => 'Réseau DG Afrique'][$need->visibility] ?? $need->visibility }}</dd>
                            </div>
                        </dl>

                        @if(! $canDecide && in_array($need->status, ['OPEN', 'IN_PROGRESS', 'RESOLVED']))
                            <div style="margin-top:16px">
                                <x-dg.btn variant="need" :href="route('messages.need', $need)" method="POST">Coordonner autour de ce besoin</x-dg.btn>
                            </div>
                        @endif
                        <div style="margin-top:10px;display:flex;flex-direction:column;gap:8px">
                            <x-dg.btn variant="quiet" :href="route('comments.need', $need)">Contributions contextuelles →</x-dg.btn>
                            @if($need->status !== 'ARCHIVED')
                                <x-dg.btn variant="quiet" :href="route('shares.need', $need)">Partager avec contexte →</x-dg.btn>
                            @endif
                        </div>
                    </x-dg.card>
                </aside>
            </div>
        </div>
    </x-dg.shell>
</x-layouts.portal>
