<x-layouts.admin title="Personnes & capacités — Administration" current="people">
    <div class="ac-page-head">
        <div>
            <p class="dg-label dg-label--saffron">Communauté</p>
            <h1>Personnes & capacités</h1>
            <p>Vue d’ensemble des profils et des capacités déclarées. Aucun profil individuel n’est modifiable ici — chaque personne reste seule responsable de son profil.</p>
        </div>
        <div class="ac-page-head__actions">
            <a href="{{ route('administration.profile.edit') }}" class="dg-btn dg-btn--quiet">Configurer le formulaire de profil →</a>
            <a href="{{ route('administration.discovery.edit') }}" class="dg-btn dg-btn--quiet">Configurer la découverte →</a>
        </div>
    </div>

    <div class="ac-stat-grid">
        <div class="ac-stat"><div class="ac-stat__label">Profils créés</div><div class="ac-stat__value">{{ $profileCount }}</div></div>
        <div class="ac-stat"><div class="ac-stat__label">Consentement orientation</div><div class="ac-stat__value">{{ $orientationOptIn }}</div><div class="ac-stat__meta">sur {{ $profileCount }} profil(s)</div></div>
        <div class="ac-stat"><div class="ac-stat__label">Consentement découverte</div><div class="ac-stat__value">{{ $discoveryOptIn }}</div><div class="ac-stat__meta">sur {{ $profileCount }} profil(s)</div></div>
    </div>

    <div class="ac-section-grid">
        <section class="ac-section">
            <div class="ac-section__head"><h2>Disponibilité déclarée</h2></div>
            <div class="ac-badge-row">
                @forelse($availabilityByStatus as $status => $count)
                    <x-dg.badge tone="neutral">{{ $status }} · {{ $count }}</x-dg.badge>
                @empty
                    <span class="dg-meta">Aucune donnée.</span>
                @endforelse
            </div>
        </section>
        <section class="ac-section">
            <div class="ac-section__head"><h2>Capacités déclarées par type</h2></div>
            <div class="ac-badge-row">
                @forelse($capabilitiesByKind as $kind => $count)
                    <x-dg.badge tone="project">{{ $kind }} · {{ $count }}</x-dg.badge>
                @empty
                    <span class="dg-meta">Aucune capacité déclarée.</span>
                @endforelse
            </div>
        </section>
        <section class="ac-section">
            <div class="ac-section__head"><h2>Capacités par statut de reconnaissance</h2></div>
            <div class="ac-badge-row">
                @forelse($capabilitiesByStatus as $status => $count)
                    <x-dg.badge tone="action">{{ $status }} · {{ $count }}</x-dg.badge>
                @empty
                    <span class="dg-meta">Aucune capacité déclarée.</span>
                @endforelse
            </div>
            <p class="dg-hint" style="margin-top:10px">Jamais un classement de personnes — ces chiffres décrivent uniquement le volume déclaré.</p>
        </section>
    </div>
</x-layouts.admin>
