{{--
    Correspondances Transmission ↔ personnes. Une recommandation propose et explique ; elle
    ne décide jamais, n'assigne personne et ne produit aucun score de valeur humaine.
--}}
<x-layouts.portal title="Correspondances — {{ $transmission->capability_label }}">
    <x-dg.shell :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:1100px">
            <a href="{{ route('transmissions.show', $transmission) }}" class="dg-crumb">← {{ $transmission->capability_label }}</a>

            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="saffron">Capacités ↔ Transmission</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">
                        {{ $wantedRole === 'TRANSMITTER' ? 'Transmetteurs possibles' : 'Apprenants possibles' }}
                    </h1>
                    <p>{{ $transmission->capability_label }}</p>
                </div>
            </div>

            @if(session('status'))
                <div class="dg-band" style="margin-bottom:20px">{{ session('status') }}</div>
            @endif

            <div class="dg-band" style="margin-bottom:24px">
                <strong style="display:block;font-size:14px;color:var(--dg-forest);margin-bottom:4px">Orientation, pas affectation</strong>
                Chaque résultat repose sur une déclaration volontairement découvrable et consentie. Aucun contact, rôle ou engagement n’est créé automatiquement.
            </div>

            @if($recommendations === [])
                <x-dg.empty title="Aucune correspondance consentie">
                    <span>Attendez que des membres déclarent cette capacité dans leur profil avec un consentement de matching, ou invitez directement une personne connue.</span>
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
    </x-dg.shell>
</x-layouts.portal>
