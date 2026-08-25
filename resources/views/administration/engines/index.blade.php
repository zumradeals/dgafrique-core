<x-layouts.admin title="Moteurs — Administration" current="engines">
    <div class="ac-page-head">
        <div>
            <p class="dg-label dg-label--saffron">Pilotage</p>
            <h1>Moteurs</h1>
            <p>Matching Projet et Recommandations, regroupés. Aucune mesure de performance fiable n’existe aujourd’hui — seuls les masquages (HIDDEN) sont tracés, jamais une acceptation. Jamais un score de personne, jamais un classement.</p>
        </div>
    </div>

    <div class="ac-section-grid">
        <section class="ac-section">
            <div class="ac-section__head"><h2>Matching Projet</h2><a href="{{ route('administration.project-matching.edit') }}">Modifier →</a></div>
            <dl class="dg-dl">
                <div><dt>Bassin de candidats</dt><dd>{{ $matchingConfiguration['candidate_pool'] }}</dd></div>
                <div><dt>Résultats max.</dt><dd>{{ $matchingConfiguration['max_results'] }}</dd></div>
                <div><dt>Raisons max.</dt><dd>{{ $matchingConfiguration['max_reasons'] }}</dd></div>
            </dl>
            <p class="dg-hint" style="margin-top:10px">{{ $projectMatchHidden }} correspondance(s) masquée(s) par leurs destinataires (seule donnée tracée).</p>
        </section>

        <section class="ac-section">
            <div class="ac-section__head"><h2>Recommandations de personnes</h2><a href="{{ route('administration.recommendations.edit') }}">Modifier →</a></div>
            <dl class="dg-dl">
                <div><dt>Bassin de candidats</dt><dd>{{ $recommendationConfiguration['candidate_pool'] }}</dd></div>
                <div><dt>Résultats max.</dt><dd>{{ $recommendationConfiguration['max_results'] }}</dd></div>
                <div><dt>Raisons max.</dt><dd>{{ $recommendationConfiguration['max_reasons'] }}</dd></div>
            </dl>
            <div class="ac-badge-row" style="margin-top:10px">
                @foreach(['learning_transmission','transmission_learning','proof_evidence','shared_capability','shared_domain','location_context','participation_context','availability_declared'] as $flag)
                    @if(array_key_exists($flag, $recommendationConfiguration))
                        <x-dg.badge :tone="$recommendationConfiguration[$flag] ? 'success' : 'neutral'">{{ $flag }} · {{ $recommendationConfiguration[$flag] ? 'actif' : 'inactif' }}</x-dg.badge>
                    @endif
                @endforeach
            </div>
            <p class="dg-hint" style="margin-top:10px">{{ $recommendationHidden }} recommandation(s) masquée(s) par leurs destinataires (seule donnée tracée).</p>
        </section>
    </div>
</x-layouts.admin>
