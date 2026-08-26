{{--
    Correspondances Mission ↔ capacités — UX-HARMONY-MISSIONS-002. Une recommandation propose et
    explique ; elle ne décide jamais, n'assigne personne et ne produit aucun score de valeur
    humaine. Moteur, données et action inchangés : seule la présentation devient une aide à la
    décision explicable plutôt qu'un classement opaque.
--}}
<x-layouts.portal title="Correspondances — {{ $mission->title }}">
    <x-dg.shell :identity="$identity" :is-administrator="$isAdministrator">
        <div class="md-page" style="max-width:1100px">
            <a href="{{ route('missions.show', $mission) }}" class="md-crumb">← {{ $mission->title }}</a>

            <section class="md-hero">
                <div class="md-hero-top">
                    <div class="md-tags"><span>Capacités ↔ Mission</span></div>
                </div>
                <h1>Correspondances possibles</h1>
                <p>Pour « {{ $mission->title }} » — chaque piste explique pourquoi elle a été suggérée. Aucun contact, rôle ou engagement n’est créé automatiquement.</p>
                <div class="md-facts">
                    <span>Nature<strong>Orientation, jamais une affectation</strong></span>
                    <span>Base<strong>Capacités volontairement découvrables et consenties</strong></span>
                    <span>Score de valeur humaine<strong>Aucun — jamais produit</strong></span>
                </div>
            </section>

            @if(session('status'))
                <div class="dg-band" style="margin:16px 0">{{ session('status') }}</div>
            @endif

            <div class="md-body" style="grid-template-columns:1fr;margin-top:16px">
                @if($recommendations === [])
                    <div class="md-panel">
                        <x-dg.empty title="Aucune correspondance consentie">
                            <span>Précisez les capacités recherchées de cette Mission, ou attendez que des membres rendent leurs capacités découvrables.</span>
                        </x-dg.empty>
                    </div>
                @else
                    <div class="dg-grid">
                        @foreach($recommendations as $match)
                            <article class="md-panel" style="display:flex;flex-direction:column;gap:14px">
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
                                    <form method="POST" action="{{ route('missions.matching.hide', [$mission, $match['profile']->discovery_reference]) }}">
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
