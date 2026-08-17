{{--
    Recommandations — orientations explicables, jamais un score humain (CAP-011). Chaque piste
    porte ses raisons en clair ; aucune recommandation sans consentement d'orientation.
--}}
<x-layouts.portal title="Mes recommandations — DG Afrique">
    <x-dg.shell current="personnes" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page">
            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="saffron">Orientations explicables</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">{{ $settings['title'] }}</h1>
                    <p>{{ $settings['introduction'] }}</p>
                </div>
            </div>

            @if(session('status'))
                <div class="dg-band" style="margin-bottom:20px">{{ session('status') }}</div>
            @endif

            <div class="dg-band" style="margin-bottom:24px;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px">
                <div>
                    <strong style="display:block;font-size:14px;color:var(--dg-forest);margin-bottom:4px">Aucun score humain.</strong>
                    {{ $settings['privacy_notice'] }}
                </div>
                <x-dg.btn variant="quiet" :href="route('member.profile.edit')">Contrôler mes consentements</x-dg.btn>
            </div>

            @if(! $memberProfile || ! $memberProfile->orientation_consent)
                <x-dg.deep>
                    <x-dg.label tone="saffron">Autorisation nécessaire</x-dg.label>
                    <h2 class="dg-display" style="font-size:28px;margin-top:8px">Votre autorisation est nécessaire.</h2>
                    <p style="margin:8px 0 16px;font-size:15px;line-height:1.65;color:var(--dg-on-deep-text)">Activez « Recevoir des orientations utiles » depuis votre profil. Sans ce choix, aucune recommandation personnalisée n’est calculée.</p>
                    <x-dg.btn variant="saffron" :href="route('member.profile.edit')">Ouvrir mon profil</x-dg.btn>
                </x-dg.deep>
            @elseif($recommendations === [])
                <x-dg.empty title="{{ $settings['empty_title'] }}">
                    <span>{{ $settings['empty_text'] }}</span>
                    <div style="margin-top:12px">
                        <x-dg.btn variant="quiet" :href="route('member.profile.edit')">Enrichir mon profil</x-dg.btn>
                    </div>
                </x-dg.empty>
            @else
                <div class="flex items-center justify-between gap-4" style="margin-bottom:16px">
                    <h2 class="dg-display" style="font-size:20px">{{ count($recommendations) }} piste(s) pour avancer</h2>
                    <span class="dg-meta">Chaque piste explique son origine</span>
                </div>
                <div class="dg-grid">
                    @foreach($recommendations as $recommendation)
                        @php($profile = $recommendation['profile'])
                        <article class="dg-card" style="display:flex;flex-direction:column;gap:14px">
                            <x-dg.label>Personne à découvrir</x-dg.label>
                            <x-dg.person :name="$profile->discovery_display_name" :role="collect([$profile->current_activity, $profile->city, $profile->country_code])->filter()->implode(' · ') ?: 'Contexte à compléter'" />
                            <div>
                                <x-dg.label>Pourquoi cette suggestion ?</x-dg.label>
                                <ul style="margin-top:8px;display:flex;flex-direction:column;gap:6px;font-size:14px;color:var(--dg-text)">
                                    @foreach($recommendation['reasons'] as $reason)
                                        <li>· {{ $reason }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            <x-dg.actions flush style="justify-content:space-between;align-items:center">
                                <x-dg.btn variant="quiet" :href="route('people.show', $profile->discovery_reference)">{{ $settings['detail_button'] }} →</x-dg.btn>
                                <form method="POST" action="{{ route('recommendations.hide', $profile->discovery_reference) }}">
                                    @csrf
                                    <button type="submit" class="dg-btn dg-btn--quiet">{{ $settings['hide_button'] }}</button>
                                </form>
                            </x-dg.actions>
                        </article>
                    @endforeach
                </div>
            @endif

            @if($hiddenProfiles->isNotEmpty())
                <details style="margin-top:24px">
                    <summary style="cursor:pointer;font-size:13px;font-weight:600;color:var(--dg-forest)">{{ $hiddenProfiles->count() }} suggestion(s) masquée(s) · Gérer</summary>
                    <div style="display:flex;flex-direction:column;gap:8px;margin-top:12px">
                        @foreach($hiddenProfiles as $profile)
                            <div class="dg-note" style="display:flex;align-items:center;justify-content:space-between;gap:12px">
                                <span>{{ $profile->discovery_display_name }}</span>
                                <form method="POST" action="{{ route('recommendations.restore', $profile->discovery_reference) }}">
                                    @csrf
                                    <button type="submit" class="dg-btn dg-btn--quiet">Réafficher</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </details>
            @endif
        </div>
    </x-dg.shell>
</x-layouts.portal>
