{{--
    CAP-066 — Fiche Organisation. UIUX-006 : regroupement visuel en quatre temps — identité, ce
    qu'elle peut apporter, dans le réseau, gestion (manager) — jamais une fusion de modèle
    métier : Partnership reste Partnership, CommunityEvent reste CommunityEvent, CapabilityStatement
    reste CapabilityStatement. La gouvernance fine (invitation, approbation, retrait de membre,
    modification de l'identité) reste au niveau service pour cette V1 — aucune interface non
    cadrée n'est fabriquée ici pour remplir la section Gestion.
--}}
<x-layouts.portal title="{{ $organization->name }} — DG Afrique">
    <x-dg.shell :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:1100px">
            <a href="{{ route('organizations.index') }}" class="dg-crumb">← Toutes les organisations</a>

            @if(session('status'))
                <div class="dg-band" style="margin-bottom:20px">{{ session('status') }}</div>
            @endif

            {{-- Identité --}}
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

                    @if($organizationCapabilities->isNotEmpty() || $isManager)
                        {{-- CAP-067 — ce que l'Organisation sait apporter : un fait métier explicite,
                             déclaré volontairement par un manager habilité, jamais déduit d'un
                             Partnership, d'un Projet ou d'un événement. Aucun score ni classement.
                             Présentation en lecture seule ici ; les gestes de déclaration/retrait
                             vivent désormais dans « Gestion » ci-dessous, réservée au manager. --}}
                        <x-dg.card>
                            <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:10px">
                                <x-dg.label>Ce que cette organisation peut apporter</x-dg.label>
                                @if($organizationCapabilities->isNotEmpty())
                                    <span class="dg-meta">{{ $organizationCapabilities->count() }} capacité{{ $organizationCapabilities->count() > 1 ? 's' : '' }}</span>
                                @endif
                            </div>
                            @forelse($organizationCapabilities as $capability)
                                <div style="padding:8px 0;border-bottom:1px solid var(--dg-line-inner)">
                                    <span class="dg-body">{{ $capability->label }}</span>
                                </div>
                            @empty
                                <x-dg.empty><span>Aucune capacité déclarée pour le moment.</span></x-dg.empty>
                            @endforelse
                        </x-dg.card>
                    @endif

                    @if($organizationPartnerships !== [] || $organizationEvents->isNotEmpty() || $isManager)
                        {{-- UIUX-006 — « Dans le réseau » regroupe visuellement deux faits déjà
                             réels et déjà distincts : les Collaborations (Partnership, CAP-065/067)
                             et les Événements organisés (CommunityEvent, CAP-068). Aucune fusion de
                             modèle, aucune activité fabriquée : une absence des deux reste un état
                             discret, jamais une grande carte vide. --}}
                        <div>
                            <x-dg.label>Dans le réseau</x-dg.label>

                            @if($organizationPartnerships !== [])
                                <div style="margin-top:10px">
                                    <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:8px">
                                        <span class="dg-meta" style="font-weight:600">Collaborations</span>
                                        <span class="dg-meta">{{ count($organizationPartnerships) }} partenariat{{ count($organizationPartnerships) > 1 ? 's' : '' }}</span>
                                    </div>
                                    @foreach($organizationPartnerships as $row)
                                        <div style="margin-bottom:12px"><x-dg.partnership-row :row="$row" /></div>
                                    @endforeach
                                </div>
                            @endif

                            @if($organizationEvents->isNotEmpty())
                                <div style="margin-top:{{ $organizationPartnerships !== [] ? '18px' : '10px' }}">
                                    <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:8px">
                                        <span class="dg-meta" style="font-weight:600">Événements organisés</span>
                                        <span class="dg-meta">{{ $organizationEvents->count() }} événement{{ $organizationEvents->count() > 1 ? 's' : '' }}</span>
                                    </div>
                                    <x-dg.card style="padding:0;overflow:hidden">
                                        <div style="padding:0 24px">
                                            @foreach($organizationEvents as $event)
                                                <a href="{{ route('community-events.show', $event) }}" style="display:flex;align-items:center;gap:10px;padding:12px 0;color:inherit;border-bottom:1px solid var(--dg-line-inner)">
                                                    <x-dg.badge tone="saffron">Événement</x-dg.badge>
                                                    <strong style="flex:1;min-width:0;font-size:14px;color:var(--dg-forest);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $event->title }}</strong>
                                                    <span class="dg-meta" style="flex:none">{{ match($event->status) { 'CANCELLED' => 'Annulé', 'COMPLETED' => 'Tenu', default => 'À venir' } }} · {{ $event->scheduled_at->translatedFormat('d M') }}</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    </x-dg.card>
                                </div>
                            @endif

                            @if($organizationPartnerships === [] && $organizationEvents->isEmpty())
                                <x-dg.empty><span>Aucune activité dans le réseau pour le moment. Les collaborations et événements réels de cette organisation apparaîtront ici.</span></x-dg.empty>
                            @endif
                        </div>
                    @endif

                    @if($isManager)
                        {{-- UIUX-006 — « Gestion » regroupe les gestes réellement disponibles pour
                             le manager, séparés de la présentation publique ci-dessus : « voici
                             notre présence → ce que nous apportons → notre activité → ce que je
                             peux gérer ». N'expose que des parcours déjà cadrés et sûrs (CAP-067) —
                             invitation/approbation/retrait de membre et modification de l'identité
                             restent hors de cette PR, aucune interface non cadrée n'est fabriquée. --}}
                        {{-- `min-width:0` neutralise le calcul de largeur intrinsèque propre à
                             `<fieldset>` (le navigateur ignore sinon `min-width:0` de ses enfants
                             flex et peut forcer un débordement horizontal sur mobile). --}}
                        <x-dg.fieldset style="min-width:0">
                            <legend><x-dg.label>Gestion</x-dg.label></legend>
                            <form method="POST" action="{{ route('organizations.capabilities.store', $organization) }}">
                                @csrf
                                <label class="dg-field">
                                    <span>Déclarer une nouvelle capacité</span>
                                    <input type="text" name="label" maxlength="200" required placeholder="Ex. Formation en gestion comptable associative">
                                </label>
                                <x-dg.actions flush>
                                    <x-dg.btn variant="primary" type="submit">Déclarer cette capacité</x-dg.btn>
                                </x-dg.actions>
                            </form>

                            @if($organizationCapabilities->isNotEmpty())
                                <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--dg-line-inner)">
                                    <span class="dg-meta" style="font-weight:600">Retirer une capacité déclarée</span>
                                    @foreach($organizationCapabilities as $capability)
                                        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:8px 0;flex-wrap:wrap">
                                            <span class="dg-body" style="flex:1;min-width:0">{{ $capability->label }}</span>
                                            <x-dg.btn variant="quiet" :href="route('organizations.capabilities.destroy', [$organization, $capability])" method="DELETE">Retirer</x-dg.btn>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </x-dg.fieldset>
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
                            @foreach($members as $member)
                                <div class="flex items-center justify-between gap-3">
                                    <span class="dg-body" style="font-weight:600">{{ $member['label'] }}</span>
                                    <span class="dg-meta">{{ $member['role'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </x-dg.card>
                </div>
            </div>
        </div>
    </x-dg.shell>
</x-layouts.portal>
