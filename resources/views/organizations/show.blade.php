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
                <div style="display:flex;flex-direction:column;gap:16px">
                    <x-dg.card>
                        <x-dg.label>Description</x-dg.label>
                        <p class="dg-body" style="margin-top:12px;white-space:pre-line">{{ $organization->description }}</p>
                    </x-dg.card>

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
