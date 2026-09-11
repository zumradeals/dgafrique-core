<x-layouts.member :title="$profile->discovery_display_name" active="people" :wide="true">
    @php
        $possessed = $profile->capabilityStatements->where('kind', \App\Models\CapabilityStatement::KIND_POSSESSED);
        $learning = $profile->capabilityStatements->where('kind', \App\Models\CapabilityStatement::KIND_LEARNING);
        $transmission = $profile->capabilityStatements->where('kind', \App\Models\CapabilityStatement::KIND_TRANSMISSION);
        $availabilityLabel = $profile->availability_status
            ? (\App\Models\PersonProfile::AVAILABILITY_LABELS[$profile->availability_status] ?? $profile->availability_status)
            : 'Non précisée';
    @endphp

    <div class="dg-person-profile">
        <a class="dg-person-profile__back" href="{{ route('people.index') }}">← Carrefour Personnes</a>

        <header class="dg-person-profile__hero">
            <div class="dg-person-profile__hero-main">
                <div class="dg-person-profile__identity">
                    <span class="dg-person-profile__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($profile->discovery_display_name, 0, 1)) }}</span>
                    <div>
                        <p class="dg-people-eyebrow">PROFIL PERSONNE · GAMAD</p>
                        <h1>{{ $profile->discovery_display_name }}</h1>
                        <p>{{ $profile->current_activity ?: 'Membre du réseau GAMAD' }}</p>
                    </div>
                </div>
                <div class="dg-person-profile__actions">
                    <form method="POST" action="{{ route('messages.direct', $profile->discovery_reference) }}">@csrf<button type="submit">Contacter</button></form>
                </div>
            </div>
        </header>

        <div class="dg-person-profile__layout">
            <main style="display:grid;gap:1rem">
                <section class="dg-person-profile__panel">
                    <p class="dg-people-eyebrow">À PROPOS</p>
                    <h2>Une personne, avant tout</h2>
                    <p>{{ $profile->discovery_bio ?: 'Cette personne a choisi d’être découvrable mais n’a pas encore ajouté de présentation publique.' }}</p>
                    <div class="dg-person-profile__facts">
                        @if ($profile->city)<span>⌖ {{ $profile->city }}@if($profile->country_code), {{ $profile->country_code }}@endif</span>@endif
                        @if ($profile->participation_mode)<span>Mode · {{ str_replace('_', ' ', mb_strtolower($profile->participation_mode)) }}</span>@endif
                        @if ($profile->current_activity)<span>Activité · {{ $profile->current_activity }}</span>@endif
                    </div>
                </section>

                <section class="dg-person-profile__panel">
                    <p class="dg-people-eyebrow">CAPACITÉS & INTENTIONS</p>
                    <h2>Savoir-faire et envies partagés</h2>
                    <div class="dg-person-cap-grid">
                        <article class="dg-person-cap">
                            <h3>Capacités actuelles</h3>
                            @if ($possessed->isEmpty())<p class="dg-person-empty">Aucune capacité publique déclarée.</p>@else<ul>@foreach($possessed as $statement)<li>{{ $statement->label }}</li>@endforeach</ul>@endif
                        </article>
                        <article class="dg-person-cap">
                            <h3>Apprentissage</h3>
                            @if ($learning->isEmpty())<p class="dg-person-empty">Aucun objectif d’apprentissage public.</p>@else<ul>@foreach($learning as $statement)<li>{{ $statement->label }}</li>@endforeach</ul>@endif
                        </article>
                        <article class="dg-person-cap">
                            <h3>Transmission</h3>
                            @if ($transmission->isEmpty())<p class="dg-person-empty">Aucune offre de transmission publique.</p>@else<ul>@foreach($transmission as $statement)<li>{{ $statement->label }}</li>@endforeach</ul>@endif
                        </article>
                    </div>
                </section>

                @if (($profile->interest_domains ?? []) !== [])
                    <section class="dg-person-profile__panel">
                        <p class="dg-people-eyebrow">CENTRES D’INTÉRÊT</p>
                        <h2>Ce qui l’intéresse</h2>
                        <div class="dg-person-interest">@foreach($profile->interest_domains as $domain)<span>{{ $domain }}</span>@endforeach</div>
                    </section>
                @endif
            </main>

            <aside style="display:grid;gap:1rem;align-content:start">
                <section class="dg-person-profile__panel">
                    <p class="dg-people-eyebrow">DISPONIBILITÉ</p>
                    <div class="dg-person-availability">
                        <strong>{{ $availabilityLabel }}</strong>
                        @if ($profile->availability_note)<p>{{ $profile->availability_note }}</p>@endif
                    </div>
                </section>

                @if (($profile->collaboration_preferences ?? []) !== [])
                    <section class="dg-person-profile__panel">
                        <p class="dg-people-eyebrow">COLLABORER</p>
                        <h2>Préférences déclarées</h2>
                        <div class="dg-person-interest">@foreach($profile->collaboration_preferences as $preference)<span>{{ $preference }}</span>@endforeach</div>
                    </section>
                @endif

                <section class="dg-person-profile__panel">
                    <p class="dg-people-eyebrow">PRINCIPE GAMAD</p>
                    <h2>Le profil reste sous contrôle de la personne.</h2>
                    <p>Seules les informations rendues découvrables par consentement sont affichées ici. Téléphone, preuves privées et référence d’identité restent masqués.</p>
                </section>
            </aside>
        </div>
    </div>
</x-layouts.member>
