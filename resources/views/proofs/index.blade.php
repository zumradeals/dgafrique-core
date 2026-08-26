{{--
    Mon Carnet de preuves — fiche CARNET DE PREUVES §16, harmonisée UX-HARMONY-TRANSMISSIONS-
    PROOFS-001. Trois sections honnêtes, jamais un mur de statistiques ni un score.
--}}
<x-layouts.portal title="Mon Carnet de preuves — DG Afrique">
    <x-dg.shell :identity="$identity" :is-administrator="$isAdministrator">
        <div class="pf-page">
            <section class="pf-dash-hero">
                <div class="pf-tags"><span>Carnet de preuves</span></div>
                <h1>Mon Carnet de preuves</h1>
                <p>Une preuve enregistre qu’une chose réelle s’est produite. Elle ne certifie jamais une compétence, un niveau ou une vérité.</p>
                <div class="pf-dash-actions">
                    <a href="{{ route('proofs.memory.self') }}" class="dg-btn dg-btn--quiet">Ma mémoire d’expérience</a>
                    <a href="{{ route('proofs.create') }}" class="dg-btn dg-btn--saffron">Enregistrer une preuve</a>
                </div>
            </section>

            @if(session('status'))
                <div class="dg-band" style="margin-bottom:20px">{{ session('status') }}</div>
            @endif

            @php
                $sectionsMeta = [
                    'temoignages_demandes' => ['title' => 'Témoignages demandés', 'empty' => 'Aucune demande de témoignage en attente.'],
                    'a_reconnaitre' => ['title' => 'À reconnaître', 'empty' => 'Aucune preuve rattachée à un contexte que vous dirigez n’attend de reconnaissance.'],
                    'mes_preuves' => ['title' => 'Mes preuves', 'empty' => 'Aucune preuve enregistrée pour le moment.'],
                ];
            @endphp

            <div class="dg-grid">
                @foreach($sectionsMeta as $key => $meta)
                    @php($items = $sections[$key] ?? collect())
                    <div class="pf-section-card">
                        <div class="flex items-center justify-between gap-3">
                            <h2>{{ $meta['title'] }}</h2>
                            <span class="dg-meta">{{ $items->count() }}</span>
                        </div>
                        <div style="margin-top:12px">
                            @forelse($items->take(6) as $item)
                                @php($proof = $item instanceof \App\Models\Proof ? $item : $item->proof)
                                @if($proof)
                                    <x-dg.proof-row :proof="$proof" />
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
