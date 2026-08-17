{{--
    Découverte de personnes — capacités volontairement rendues visibles. Aucun score humain,
    aucun classement de valeur (CAP-011). Utilise x-dg.person pour rester cohérent avec le Fil.
--}}
<x-layouts.portal title="Découvrir des personnes — DG Afrique">
    <x-dg.shell current="personnes" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page">
            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="saffron">Capacités humaines</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">{{ $settings['title'] }}</h1>
                    <p>{{ $settings['introduction'] }}</p>
                </div>
                <x-dg.btn variant="quiet" :href="route('member.profile.edit')">Gérer ma visibilité</x-dg.btn>
            </div>

            <form method="GET" action="{{ route('people.index') }}" class="dg-filters">
                <label style="flex:1;min-width:220px">Que recherchez-vous ?
                    <input class="dg-input" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Ex. couture, comptabilité, informatique…">
                </label>
                @if($settings['country_filter'])
                    <label>Pays
                        <input class="dg-input" style="max-width:100px" name="country" value="{{ request('country') }}" maxlength="2" placeholder="CI">
                    </label>
                @endif
                @if($settings['mode_filter'])
                    <label>Mode
                        <select class="dg-select" name="mode">
                            <option value="">Tous</option>
                            @foreach($modes as $mode)
                                <option value="{{ $mode['value'] }}" @selected(request('mode') === $mode['value'])>{{ $mode['label'] }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                <x-dg.btn variant="quiet">Découvrir</x-dg.btn>
            </form>

            <div class="dg-band" style="margin-bottom:24px">
                <strong style="display:block;font-size:14px;color:var(--dg-forest);margin-bottom:4px">Une découverte avec consentement.</strong>
                {{ $settings['privacy_notice'] }}
            </div>

            @if($profiles->isEmpty())
                <x-dg.empty title="{{ $settings['empty_title'] }}">
                    <span>{{ $settings['empty_text'] }}</span>
                </x-dg.empty>
            @else
                <div class="flex items-center justify-between gap-4" style="margin-bottom:16px">
                    <h2 class="dg-display" style="font-size:20px">{{ $profiles->total() }} profil(s) utile(s)</h2>
                    <span class="dg-meta">Sans classement de valeur</span>
                </div>
                <div class="dg-grid">
                    @foreach($profiles as $profile)
                        @php($visibleCapabilities = $profile->capabilityStatements->take(5))
                        <article class="dg-card" style="display:flex;flex-direction:column;gap:14px">
                            <x-dg.person :name="$profile->discovery_display_name" :role="collect([$profile->current_activity, $profile->city, $profile->country_code])->filter()->implode(' · ') ?: 'Contexte à compléter'" />
                            @if($profile->discovery_bio)
                                <p class="dg-body">{{ $profile->discovery_bio }}</p>
                            @endif
                            <div>
                                <x-dg.label>Pourquoi ce profil apparaît</x-dg.label>
                                <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px">
                                    @forelse($visibleCapabilities as $capability)
                                        <x-dg.badge tone="neutral">{{ $capability->label }}</x-dg.badge>
                                    @empty
                                        <span class="dg-meta">Profil volontairement visible</span>
                                    @endforelse
                                </div>
                            </div>
                            <x-dg.actions flush>
                                <x-dg.btn variant="quiet" :href="route('people.show', $profile->discovery_reference)">{{ $settings['detail_button'] }} →</x-dg.btn>
                            </x-dg.actions>
                        </article>
                    @endforeach
                </div>
                <div>{{ $profiles->links('pagination.dg') }}</div>
            @endif
        </div>
    </x-dg.shell>
</x-layouts.portal>
