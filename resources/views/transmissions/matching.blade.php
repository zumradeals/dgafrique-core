{{--
    Correspondances Transmission ↔ personnes — harmonisée UX-HARMONY-TRANSMISSIONS-PROOFS-001.
    Une recommandation propose et explique ; elle ne décide jamais, n'assigne personne et ne
    produit aucun score de valeur humaine.
--}}
<x-layouts.portal title="Correspondances — {{ $transmission->capability_label }}">
    <x-dg.shell :identity="$identity" :is-administrator="$isAdministrator">
        <div class="tr-page" style="max-width:1200px">
            <a href="{{ route('transmissions.show', $transmission) }}" class="tr-crumb">← {{ $transmission->capability_label }}</a>

            <section class="tr-hero">
                <div class="tr-hero-top"><div class="tr-tags"><span>Capacités ↔ Transmission</span></div></div>
                <h1>{{ $wantedRole === 'TRANSMITTER' ? 'Transmetteurs possibles' : 'Apprenants possibles' }}</h1>
                <p>{{ $transmission->capability_label }}</p>
                <div class="tr-facts">
                    <span>Nature<strong>Orientation, jamais une affectation</strong></span>
                    <span>Base<strong>Capacités volontairement découvrables et consenties</strong></span>
                    <span>Score de valeur humaine<strong>Aucun — jamais produit</strong></span>
                </div>
            </section>

            @if(session('status'))
                <div class="dg-band" style="margin:16px 0">{{ session('status') }}</div>
            @endif

            <div style="margin-top:16px">
                @if($recommendations === [])
                    <x-dg.empty title="Aucune correspondance consentie">
                        <span>Attendez que des membres déclarent cette capacité dans leur profil avec un consentement à être mis en relation, ou invitez directement une personne connue.</span>
                    </x-dg.empty>
                @else
                    <div class="dg-grid">
                        @foreach($recommendations as $match)
                            <article class="dg-card" style="display:flex;flex-direction:column;gap:14px">
                                <x-dg.person :name="$match['profile']->discovery_display_name" :role="($match['profile']->current_activity ?: 'Activité non précisée').($match['profile']->city ? ' · '.$match['profile']->city : '')" />
                                <div>
                                    <x-dg.label>Pourquoi cette piste ?</x-dg.label>
                                    <ul style="margin-top:8px;display:flex;flex-direction:column;gap:6px;font-size:14px;color:var(--dg-text)">
                                        @foreach($match['reasons'] as $reason)
                                            <li>· {{ $reason }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                <x-dg.actions flush style="justify-content:space-between;align-items:center">
                                    <x-dg.btn variant="quiet" :href="route('people.show', $match['profile']->discovery_reference)">Voir le profil public</x-dg.btn>
                                    <form method="POST" action="{{ route('transmissions.matching.hide', [$transmission, $match['profile']->discovery_reference]) }}">
                                        @csrf
                                        <button type="submit" class="dg-btn dg-btn--quiet">Masquer cette piste</button>
                                    </form>
                                </x-dg.actions>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </x-dg.shell>
</x-layouts.portal>
