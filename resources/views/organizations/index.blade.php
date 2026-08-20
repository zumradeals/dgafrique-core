{{--
    CAP-066 — Liste minimale des Organisations visibles par l'identité connectée. Pas de
    back-office : chaque organisation renvoie vers sa fiche réelle.
--}}
<x-layouts.portal title="Organisations — DG Afrique">
    <x-dg.shell :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:1100px">
            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="forest">Structures durables du réseau</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">Organisations</h1>
                    <p>Une Organisation porte des responsabilités, des membres et des rôles dans la durée — distincte d’un Projet ou d’une ZUMRA.</p>
                </div>
                <x-dg.btn variant="primary" :href="route('organizations.create')">Créer une organisation</x-dg.btn>
            </div>

            @if(session('status'))
                <div class="dg-band" style="margin-bottom:20px">{{ session('status') }}</div>
            @endif

            @if($organizations->isEmpty())
                <x-dg.empty title="Aucune organisation visible pour le moment">
                    <span>Créez la première ou attendez qu’une organisation publique apparaisse.</span>
                </x-dg.empty>
            @else
                <div style="display:flex;flex-direction:column;gap:14px">
                    @foreach($organizations as $organization)
                        <x-dg.card>
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div>
                                    <x-dg.badge tone="forest">{{ \App\Models\Organization::TYPES[$organization->type] ?? $organization->type }}</x-dg.badge>
                                    <h2 class="dg-display dg-display--card" style="margin-top:8px">
                                        <a href="{{ route('organizations.show', $organization) }}" style="color:inherit">{{ $organization->name }}</a>
                                    </h2>
                                </div>
                                <x-dg.label>{{ $organization->visibility === 'PUBLIC' ? 'Publique' : 'Privée' }}</x-dg.label>
                            </div>
                            <p class="dg-body" style="margin-top:10px">{{ \Illuminate\Support\Str::limit($organization->description, 180) }}</p>
                        </x-dg.card>
                    @endforeach
                </div>
            @endif
        </div>
    </x-dg.shell>
</x-layouts.portal>
