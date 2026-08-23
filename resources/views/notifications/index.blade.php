{{--
    Mes Notifications — CAP-054, fiche §9/§16. Un journal simple, jamais un centre de
    notifications anxiogène : pas de badge numérique, pas de compteur, pas de classement.
    Mon espace garde sa priorité dominante unique — cet écran ne la remplace pas.
--}}
<x-layouts.portal title="Notifications — DG Afrique">
    <x-dg.shell :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page">
            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="saffron">Notifications</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">Notifications</h1>
                    <p>Ce qui vous concerne, à travers Missions, Transmission, Preuves, Besoins, Projets et ZUMRA — jamais un score ni une pression à réagir vite.</p>
                </div>
            </div>

            @php
                $sourceLabels = [
                    'MISSION' => 'Mission', 'TRANSMISSION' => 'Transmission', 'PROOF' => 'Preuve',
                    'NEED' => 'Besoin', 'PROJECT' => 'Projet', 'ZUMRA' => 'ZUMRA',
                ];
            @endphp

            <section style="margin-bottom:32px">
                <div class="flex items-center justify-between gap-3" style="margin-bottom:12px">
                    <x-dg.label>À traiter</x-dg.label>
                </div>
                <div style="display:flex;flex-direction:column;gap:10px;max-width:760px">
                    @forelse($sections['a_traiter'] as $item)
                        <x-dg.card>
                            <div class="flex items-center justify-between gap-3">
                                <span class="dg-meta">{{ $sourceLabels[$item['source_type']] ?? $item['source_type'] }} · {{ $item['occurred_at']->locale('fr')->isoFormat('D MMMM YYYY') }}</span>
                            </div>
                            <p class="dg-body" style="margin-top:6px;font-weight:600;color:var(--dg-night)">{{ $item['title'] }}</p>
                            @if($item['body'])
                                <p class="dg-body" style="margin-top:4px">{{ $item['body'] }}</p>
                            @endif
                            @if($item['action_url'])
                                <div style="margin-top:10px">
                                    <a href="{{ $item['action_url'] }}" class="dg-btn dg-btn--quiet">Ouvrir →</a>
                                </div>
                            @endif
                        </x-dg.card>
                    @empty
                        <x-dg.empty>
                            <span>Rien n’attend votre décision pour le moment.</span>
                        </x-dg.empty>
                    @endforelse
                </div>
            </section>

            <section>
                <div class="flex items-center justify-between gap-3" style="margin-bottom:12px">
                    <x-dg.label>Récentes</x-dg.label>
                </div>
                <div style="display:flex;flex-direction:column;gap:10px;max-width:760px">
                    @forelse($sections['recentes'] as $item)
                        <x-dg.card>
                            <div class="flex items-center justify-between gap-3">
                                <span class="dg-meta">{{ $sourceLabels[$item['source_type']] ?? $item['source_type'] }} · {{ $item['occurred_at']->locale('fr')->isoFormat('D MMMM YYYY') }}</span>
                                @unless($item['read'])
                                    <span class="dg-meta" style="color:var(--dg-saffron)">Nouveau</span>
                                @endunless
                            </div>
                            <p class="dg-body" style="margin-top:6px">{{ $item['title'] }}</p>
                            @if($item['action_url'])
                                <div style="margin-top:10px">
                                    <a href="{{ $item['action_url'] }}" class="dg-btn dg-btn--quiet">Ouvrir →</a>
                                </div>
                            @endif
                        </x-dg.card>
                    @empty
                        <x-dg.empty>
                            <span>Aucune nouvelle récente.</span>
                        </x-dg.empty>
                    @endforelse
                </div>
            </section>
        </div>
    </x-dg.shell>
</x-layouts.portal>
