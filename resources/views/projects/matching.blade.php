{{--
    Correspondances capacité ↔ projet. Chaque raison est une phrase, jamais un score — le
    PersonRecommendationEngine ne produit pas de shape numérique (voir docs/design/DESIGN-INVARIANTS.md).
--}}
<x-layouts.portal title="Correspondances — {{ $project->name }}">
    <x-dg.shell current="projets" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:1100px">
            <a href="{{ route('projects.show', $project) }}" class="dg-crumb">← Retour au projet</a>

            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="night">Capacités ↔ projet</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">{{ $configuration['page_title'] }}</h1>
                    <p>{{ $configuration['page_intro'] }}</p>
                    <p style="font-weight:600;color:var(--dg-forest)">{{ $project->name }}</p>
                </div>
            </div>

            @if(session('status'))
                <div class="dg-band" style="margin-bottom:20px">{{ session('status') }}</div>
            @endif

            <div class="dg-band" style="margin-bottom:24px">
                <strong style="display:block;font-size:14px;color:var(--dg-forest);margin-bottom:4px">Orientation, pas affectation</strong>
                Chaque résultat repose sur une capacité volontairement découvrable. Aucun contact, rôle ou engagement n’est créé.
            </div>

            @if($matches === [])
                <x-dg.empty title="Aucune correspondance consentie">
                    <span>Précisez les capacités recherchées ou attendez que des membres choisissent de rendre leurs capacités découvrables.</span>
                </x-dg.empty>
            @else
                <div class="dg-grid">
                    @foreach($matches as $match)
                        <article class="dg-card" style="display:flex;flex-direction:column;gap:14px">
                            <x-dg.person :name="$match['profile']->discovery_display_name" :role="($match['profile']->activity ?: 'Activité non précisée').($match['profile']->city ? ' · '.$match['profile']->city : '')" />
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
                                <form method="POST" action="{{ route('projects.matching.hide', [$project, $match['profile']->discovery_reference]) }}">
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
