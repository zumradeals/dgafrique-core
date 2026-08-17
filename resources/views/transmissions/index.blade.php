{{--
    Mes Transmissions — fiche TRANSMISSION §20. Cinq sections honnêtes, jamais un mur de
    statistiques ni un score de productivité pédagogique.
--}}
<x-layouts.portal title="Mes Transmissions — DG Afrique">
    <x-dg.shell :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page">
            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="saffron">CAP-006 · Transmission</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">Mes Transmissions</h1>
                    <p>Une Transmission organise une rencontre humaine autour d’une capacité. Proposer n’est pas être accepté, déclarer sa part terminée n’est pas valider la Transmission.</p>
                </div>
                <a href="{{ route('transmissions.create') }}" class="dg-btn dg-btn--saffron">Proposer une Transmission</a>
            </div>

            @if(session('status'))
                <div class="dg-band" style="margin-bottom:24px">{{ session('status') }}</div>
            @endif

            @php
                $sectionsMeta = [
                    'propositions_recues' => ['title' => 'Propositions reçues', 'empty' => 'Aucune invitation n’attend votre réponse.'],
                    'mes_demandes' => ['title' => 'Mes demandes', 'empty' => 'Aucune Transmission proposée par vous n’attend encore d’acceptation.'],
                    'je_transmets' => ['title' => 'En cours — je transmets', 'empty' => 'Vous ne transmettez aucune capacité actuellement.'],
                    'j_apprends' => ['title' => 'En cours — j’apprends', 'empty' => 'Vous n’apprenez aucune capacité actuellement.'],
                    'terminees' => ['title' => 'Terminées', 'empty' => 'Aucune Transmission terminée pour le moment.'],
                ];
            @endphp

            <div class="dg-grid">
                @foreach($sectionsMeta as $key => $meta)
                    @php($items = $sections[$key] ?? collect())
                    <x-dg.card>
                        <div class="flex items-center justify-between gap-3">
                            <x-dg.label>{{ $meta['title'] }}</x-dg.label>
                            <span class="dg-meta">{{ $items->count() }}</span>
                        </div>
                        <div style="margin-top:12px">
                            @forelse($items->take(6) as $item)
                                @php($transmission = $item instanceof \App\Models\Transmission ? $item : $item->transmission)
                                @if($transmission)
                                    <x-dg.transmission-row :transmission="$transmission" />
                                @endif
                            @empty
                                <x-dg.empty>
                                    <span>{{ $meta['empty'] }}</span>
                                </x-dg.empty>
                            @endforelse
                        </div>
                    </x-dg.card>
                @endforeach
            </div>
        </div>
    </x-dg.shell>
</x-layouts.portal>
