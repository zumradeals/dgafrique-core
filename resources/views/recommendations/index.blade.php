<x-layouts.portal title="Mes recommandations — DG Afrique">
    <div class="recommendation-page">
        <header class="space-header"><div class="space-identity"><a class="brand" href="/"><span class="brand-mark">G</span><span>DG Afrique</span></a></div><div class="space-search">✦ <span>Orientations fondées sur vos capacités</span></div><div class="space-identity"><a class="discovery-back" href="{{ route('member.space') }}">Mon espace</a><span class="avatar">{{ mb_strtoupper(mb_substr($identity->label, 0, 1)) }}</span></div></header>
        <main class="recommendation-content">
            <section class="recommendation-hero"><div><p class="eyebrow dark">ORIENTATIONS EXPLICABLES</p><h1>{{ $settings['title'] }}</h1><p>{{ $settings['introduction'] }}</p></div><div class="recommendation-mark"><span>✦</span><strong>Proposer<br>sans décider</strong></div></section>
            @if(session('status'))<div class="alert success">{{ session('status') }}</div>@endif
            <div class="recommendation-honesty"><strong>Aucun score humain.</strong><span>{{ $settings['privacy_notice'] }}</span><a href="{{ route('member.profile.edit') }}">Contrôler mes consentements</a></div>

            @if(! $memberProfile || ! $memberProfile->orientation_consent)
                <section class="recommendation-empty consent-required"><i>✦</i><h2>Votre autorisation est nécessaire.</h2><p>Activez « Recevoir des orientations utiles » depuis votre profil. Sans ce choix, aucune recommandation personnalisée n’est calculée.</p><a href="{{ route('member.profile.edit') }}">Ouvrir mon profil</a></section>
            @elseif($recommendations === [])
                <section class="recommendation-empty"><i>✦</i><h2>{{ $settings['empty_title'] }}</h2><p>{{ $settings['empty_text'] }}</p><a href="{{ route('member.profile.edit') }}">Enrichir mon profil</a></section>
            @else
                <div class="recommendation-heading"><h2>{{ count($recommendations) }} piste(s) pour avancer</h2><span>Chaque piste explique son origine</span></div>
                <section class="recommendation-grid">
                    @foreach($recommendations as $recommendation)
                        @php($profile = $recommendation['profile'])
                        <article class="recommendation-card">
                            <div class="recommendation-person"><span>{{ mb_strtoupper(mb_substr($profile->discovery_display_name, 0, 1)) }}</span><div><small>PERSONNE À DÉCOUVRIR</small><h3>{{ $profile->discovery_display_name }}</h3><p>{{ collect([$profile->current_activity, $profile->city, $profile->country_code])->filter()->implode(' · ') ?: 'Contexte à compléter' }}</p></div></div>
                            <div class="recommendation-why"><strong>Pourquoi cette suggestion ?</strong><ul>@foreach($recommendation['reasons'] as $reason)<li>{{ $reason }}</li>@endforeach</ul></div>
                            <div class="recommendation-actions"><a href="{{ route('people.show', $profile->discovery_reference) }}">{{ $settings['detail_button'] }} →</a><form method="POST" action="{{ route('recommendations.hide', $profile->discovery_reference) }}">@csrf<button type="submit">{{ $settings['hide_button'] }}</button></form></div>
                        </article>
                    @endforeach
                </section>
            @endif

            @if($hiddenProfiles->isNotEmpty())
                <details class="hidden-recommendations"><summary>{{ $hiddenProfiles->count() }} suggestion(s) masquée(s) · Gérer</summary><div>@foreach($hiddenProfiles as $profile)<article><span>{{ $profile->discovery_display_name }}</span><form method="POST" action="{{ route('recommendations.restore', $profile->discovery_reference) }}">@csrf<button type="submit">Réafficher</button></form></article>@endforeach</div></details>
            @endif
        </main>
    </div>
</x-layouts.portal>
