{{--
    Fiche personne — capacités volontairement découvrables uniquement. Aucune coordonnée privée
    exposée ; le premier contact passe par la conversation, jamais par un score ou un classement.
--}}
<x-layouts.portal title="Profil utile — DG Afrique">
    @php($groups = $profile->capabilityStatements->groupBy('kind'))
    <x-dg.shell current="personnes" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:1000px">
            <a href="{{ route('people.index') }}" class="dg-crumb">← Découverte</a>

            <div class="dg-page-header">
                <div class="flex items-center gap-4">
                    <x-dg.avatar :initials="mb_strtoupper(mb_substr($profile->discovery_display_name, 0, 1))" size="lg" tone="copper" />
                    <div>
                        <x-dg.label tone="saffron">Profil utile</x-dg.label>
                        <h1 class="dg-display dg-display--screen" style="margin-top:6px">{{ $profile->discovery_display_name }}</h1>
                        <p>{{ collect([$profile->current_activity, $profile->city, $profile->country_code])->filter()->implode(' · ') ?: 'Contexte à compléter' }}</p>
                    </div>
                </div>
                <div style="text-align:right;display:flex;flex-direction:column;gap:10px;align-items:flex-end">
                    <x-dg.label>Visible avec consentement</x-dg.label>
                    <x-dg.btn variant="primary" :href="route('messages.direct', $profile->discovery_reference)" method="POST">Écrire un message</x-dg.btn>
                    {{-- UIUX-007 — Personne → Transmission : réutilise strictement le moteur
                         Transmission existant (invitation explicite, acceptation requise) ; ne
                         suppose jamais que la personne acceptera. --}}
                    <x-dg.btn variant="quiet" :href="route('transmissions.create', ['invite' => $profile->discovery_reference])">Proposer une transmission</x-dg.btn>
                </div>
            </div>

            @if($profile->discovery_bio)
                <x-dg.card style="margin-bottom:24px">
                    <x-dg.label>À propos</x-dg.label>
                    <p class="dg-body" style="margin-top:10px">{{ $profile->discovery_bio }}</p>
                </x-dg.card>
            @endif

            <div class="dg-grid" style="margin-bottom:24px">
                <x-dg.card>
                    <x-dg.badge tone="need">Capacités actuelles</x-dg.badge>
                    <p class="dg-hint" style="margin-top:10px">Ce que cette personne déclare savoir faire.</p>
                    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:12px">
                        @forelse($groups->get(\App\Models\CapabilityStatement::KIND_POSSESSED, collect()) as $item)
                            <x-dg.badge tone="neutral">{{ $item->label }}</x-dg.badge>
                        @empty
                            <span class="dg-meta">Aucune capacité rendue visible.</span>
                        @endforelse
                    </div>
                </x-dg.card>
                <x-dg.card>
                    <x-dg.badge tone="action">Transmission</x-dg.badge>
                    <p class="dg-hint" style="margin-top:10px">Ce qu’elle accepte de partager ou d’expliquer.</p>
                    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:12px">
                        @forelse($groups->get(\App\Models\CapabilityStatement::KIND_TRANSMISSION, collect()) as $item)
                            <x-dg.badge tone="neutral">{{ $item->label }}</x-dg.badge>
                        @empty
                            <span class="dg-meta">Aucune transmission rendue visible.</span>
                        @endforelse
                    </div>
                </x-dg.card>
                <x-dg.card>
                    <x-dg.badge tone="decision">Apprentissage</x-dg.badge>
                    <p class="dg-hint" style="margin-top:10px">Ce qu’elle souhaite apprendre ou renforcer.</p>
                    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:12px">
                        @forelse($groups->get(\App\Models\CapabilityStatement::KIND_LEARNING, collect()) as $item)
                            <x-dg.badge tone="neutral">{{ $item->label }}</x-dg.badge>
                        @empty
                            <span class="dg-meta">Aucun apprentissage rendu visible.</span>
                        @endforelse
                    </div>
                </x-dg.card>
            </div>

            <x-dg.card style="margin-bottom:24px">
                <dl class="dg-dl lg:grid lg:grid-cols-2" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">
                    <div>
                        <dt>Mode de participation</dt>
                        <dd>{{ $profile->participation_mode ? str_replace('_', ' ', $profile->participation_mode) : 'Non précisé' }}</dd>
                    </div>
                    <div>
                        <dt>Disponibilité</dt>
                        <dd>{{ $profile->availability_status ? \App\Models\PersonProfile::AVAILABILITY_LABELS[$profile->availability_status] : 'Non précisée' }}{{ $profile->availability_note ? ' — '.$profile->availability_note : '' }}</dd>
                    </div>
                    <div>
                        <dt>Domaines d’intérêt</dt>
                        <dd>{{ collect($profile->interest_domains)->take(4)->implode(' · ') ?: 'Non précisés' }}</dd>
                    </div>
                </dl>
            </x-dg.card>

            <div class="dg-band">
                <strong style="display:block;font-size:14px;color:var(--dg-forest);margin-bottom:4px">Une rencontre doit rester consentie.</strong>
                Le premier contact passe par ce profil volontairement découvrable. Aucune coordonnée privée n’est exposée.
            </div>
        </div>
    </x-dg.shell>
</x-layouts.portal>
