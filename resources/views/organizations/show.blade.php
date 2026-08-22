{{--
    CAP-066 — Fiche Organisation : identité, membres actifs, demande d'adhésion. La gouvernance
    fine (invitation, approbation, retrait) reste au niveau service pour cette V1.
--}}
<x-layouts.portal title="{{ $organization->name }} — DG Afrique">
    <x-dg.shell :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:1100px">
            <a href="{{ route('organizations.index') }}" class="dg-crumb">← Toutes les organisations</a>

            @if(session('status'))
                <div class="dg-band" style="margin-bottom:20px">{{ session('status') }}</div>
            @endif

            <div class="dg-page-header">
                <div>
                    <x-dg.badge tone="forest">{{ \App\Models\Organization::TYPES[$organization->type] ?? $organization->type }}</x-dg.badge>
                    <h1 class="dg-display dg-display--screen" style="margin-top:10px">{{ $organization->name }}</h1>
                    <p>{{ $organization->active_member_count }} membre(s) actif(s) · {{ $organization->visibility === 'PUBLIC' ? 'Publique' : 'Privée' }}</p>
                </div>
                <x-dg.label>{{ $organization->status === 'ARCHIVED' ? 'Archivée' : 'Active' }}</x-dg.label>
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_300px]">
                <div style="display:flex;flex-direction:column;gap:16px;min-width:0">
                    <x-dg.card>
                        <x-dg.label>Description</x-dg.label>
                        <p class="dg-body" style="margin-top:12px;white-space:pre-line">{{ $organization->description }}</p>
                    </x-dg.card>

                    @if($organizationEvents->isNotEmpty())
                        {{-- UIUX-003 — décision #4 : Événements réellement organisés par cette
                             Organisation, jamais un catalogue global. --}}
                        <x-dg.card style="padding:0;overflow:hidden">
                            <div style="padding:22px 24px 14px;display:flex;align-items:baseline;justify-content:space-between">
                                <x-dg.label>Événements organisés</x-dg.label>
                                <span class="dg-meta">{{ $organizationEvents->count() }} événement{{ $organizationEvents->count() > 1 ? 's' : '' }}</span>
                            </div>
                            <div style="padding:0 24px 8px;border-top:1px solid var(--dg-line-inner)">
                                @foreach($organizationEvents as $event)
                                    <a href="{{ route('community-events.show', $event) }}" style="display:flex;align-items:center;gap:10px;padding:12px 0;color:inherit;border-bottom:1px solid var(--dg-line-inner)">
                                        <x-dg.badge tone="saffron">Événement</x-dg.badge>
                                        <strong style="flex:1;min-width:0;font-size:14px;color:var(--dg-forest);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $event->title }}</strong>
                                        <span class="dg-meta" style="flex:none">{{ match($event->status) { 'CANCELLED' => 'Annulé', 'COMPLETED' => 'Tenu', default => 'À venir' } }} · {{ $event->scheduled_at->translatedFormat('d M') }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </x-dg.card>
                    @endif

                    @if($organizationPartnerships !== [] || $isManager)
                        {{-- UIUX-005 — Collaborations réelles de cette Organisation comme fournisseur
                             (CommunityEventService::canView() → PartnershipService::canView(), même
                             discipline). Jamais une déduction de « capacités » à partir de ces
                             partenariats : ceci montre des collaborations concrètes, pas un catalogue. --}}
                        <div>
                            <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:10px">
                                <x-dg.label>Collaborations</x-dg.label>
                                @if($organizationPartnerships !== [])
                                    <span class="dg-meta">{{ count($organizationPartnerships) }} partenariat{{ count($organizationPartnerships) > 1 ? 's' : '' }}</span>
                                @endif
                            </div>
                            @forelse($organizationPartnerships as $row)
                                <div style="margin-bottom:12px"><x-dg.partnership-row :row="$row" /></div>
                            @empty
                                <x-dg.card>
                                    <x-dg.empty><span>Aucune collaboration pour le moment. Elles apparaissent lorsque cette organisation propose d'apporter une capacité à un Besoin, un Projet ou une ZUMRA.</span></x-dg.empty>
                                </x-dg.card>
                            @endforelse
                        </div>
                    @endif

                    @if(! $isMember)
                        <x-dg.fieldset>
                            <legend><x-dg.label>Rejoindre cette organisation</x-dg.label></legend>
                            <form method="POST" action="{{ route('organizations.join', $organization) }}">
                                @csrf
                                <label class="dg-field">
                                    <span>Motivation (facultatif)</span>
                                    <textarea name="motivation" rows="3" maxlength="1500"></textarea>
                                </label>
                                <x-dg.actions flush>
                                    <x-dg.btn variant="primary" type="submit">Demander à rejoindre</x-dg.btn>
                                </x-dg.actions>
                            </form>
                        </x-dg.fieldset>
                    @endif
                </div>

                <div style="display:flex;flex-direction:column;gap:16px">
                    <x-dg.card>
                        <x-dg.label>Membres</x-dg.label>
                        <div style="display:flex;flex-direction:column;gap:10px;margin-top:12px">
                            @foreach($members as $membership)
                                <div class="flex items-center justify-between gap-3">
                                    <span class="dg-meta">{{ \App\Models\OrganizationMembership::ROLES[$membership->role] ?? $membership->role }}</span>
                                </div>
                            @endforeach
                        </div>
                    </x-dg.card>
                </div>
            </div>
        </div>
    </x-dg.shell>
</x-layouts.portal>
