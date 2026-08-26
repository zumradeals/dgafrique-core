{{--
    Mes Transmissions — fiche TRANSMISSION §20, harmonisée UX-HARMONY-TRANSMISSIONS-PROOFS-001.
    Reste le tableau de bord personnel réel (myTransmissionsSections) — aucune découverte
    publique n'existe pour ce domaine, aucune inventée ici. Cinq sections honnêtes, jamais un
    mur de statistiques ni un score de productivité pédagogique.
--}}
<x-layouts.portal title="Mes Transmissions — DG Afrique">
    <x-dg.shell :identity="$identity" :is-administrator="$isAdministrator">
        <div class="tr-page">
            <section class="tr-dash-hero">
                <div class="tr-tags"><span>Transmission</span></div>
                <h1>Mes Transmissions</h1>
                <p>Une Transmission organise une rencontre humaine autour d’une capacité. Proposer n’est pas être accepté, déclarer sa part terminée n’est pas valider la Transmission.</p>
                <div class="tr-dash-actions">
                    <a href="{{ route('transmissions.create') }}" class="dg-btn dg-btn--saffron">Proposer une Transmission</a>
                </div>
            </section>

            @if(session('status'))
                <div class="dg-band" style="margin-bottom:20px">{{ session('status') }}</div>
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
                    <div class="tr-section-card">
                        <div class="flex items-center justify-between gap-3">
                            <h2>{{ $meta['title'] }}</h2>
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
                    </div>
                @endforeach
            </div>
        </div>
    </x-dg.shell>
</x-layouts.portal>
