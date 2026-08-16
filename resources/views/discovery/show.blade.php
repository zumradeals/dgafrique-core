<x-layouts.portal title="Profil utile — DG Afrique">
    @php($groups = $profile->capabilityStatements->groupBy('kind'))
    <div class="discovery-page">
        <header class="space-header"><div class="space-identity"><a class="brand" href="/"><span class="brand-mark">G</span><span>DG Afrique</span></a></div><div class="space-search">⌕ <span>Profil de capacités volontairement visible</span></div><div class="space-identity"><a class="discovery-back" href="{{ route('people.index') }}">← Découverte</a><span class="avatar">{{ mb_strtoupper(mb_substr($identity->label, 0, 1)) }}</span></div></header>
        <main class="discovery-content person-detail">
            <section class="person-detail-hero"><div class="person-detail-avatar">{{ mb_strtoupper(mb_substr($profile->discovery_display_name, 0, 1)) }}</div><div><p class="eyebrow dark">PROFIL UTILE</p><h1>{{ $profile->discovery_display_name }}</h1><p>{{ collect([$profile->current_activity, $profile->city, $profile->country_code])->filter()->implode(' · ') ?: 'Contexte à compléter' }}</p></div><div style="display:grid;gap:.65rem;justify-items:end"><span>Visible avec consentement</span><form method="POST" action="{{ route('messages.direct',$profile->discovery_reference) }}">@csrf<button type="submit" style="border:0;border-radius:.75rem;padding:.7rem 1rem;background:var(--navy);color:#fff;font:inherit;font-size:.72rem;font-weight:850;cursor:pointer">Écrire un message</button></form></div></section>
            @if($profile->discovery_bio)<section class="person-detail-bio"><h2>À propos</h2><p>{{ $profile->discovery_bio }}</p></section>@endif
            <section class="capability-columns">
                <article><span class="capability-icon cyan">C</span><h2>Capacités actuelles</h2><p>Ce que cette personne déclare savoir faire.</p><div>@forelse($groups->get(\App\Models\CapabilityStatement::KIND_POSSESSED, collect()) as $item)<span>{{ $item->label }}</span>@empty<small>Aucune capacité rendue visible.</small>@endforelse</div></article>
                <article><span class="capability-icon green">T</span><h2>Transmission</h2><p>Ce qu’elle accepte de partager ou d’expliquer.</p><div>@forelse($groups->get(\App\Models\CapabilityStatement::KIND_TRANSMISSION, collect()) as $item)<span>{{ $item->label }}</span>@empty<small>Aucune transmission rendue visible.</small>@endforelse</div></article>
                <article><span class="capability-icon amber">A</span><h2>Apprentissage</h2><p>Ce qu’elle souhaite apprendre ou renforcer.</p><div>@forelse($groups->get(\App\Models\CapabilityStatement::KIND_LEARNING, collect()) as $item)<span>{{ $item->label }}</span>@empty<small>Aucun apprentissage rendu visible.</small>@endforelse</div></article>
            </section>
            <section class="person-context"><div><small>Mode de participation</small><strong>{{ $profile->participation_mode ? str_replace('_', ' ', $profile->participation_mode) : 'Non précisé' }}</strong></div><div><small>Domaines d’intérêt</small><strong>{{ collect($profile->interest_domains)->take(4)->implode(' · ') ?: 'Non précisés' }}</strong></div></section>
            <div class="discovery-privacy"><strong>Une rencontre doit rester consentie.</strong><span>Le premier contact passe par ce profil volontairement découvrable. Aucune coordonnée privée n’est exposée.</span></div>
        </main>
    </div>
</x-layouts.portal>
