<x-layouts.portal title="Découvrir des personnes — DG Afrique">
    <div class="discovery-page">
        <header class="space-header">
            <div class="space-identity"><a class="brand" href="/"><span class="brand-mark">G</span><span>DG Afrique</span></a></div>
            <div class="space-search">⌕ <span>Capacités, apprentissages et transmissions</span></div>
            <div class="space-identity"><a class="discovery-back" href="{{ route('member.space') }}">Mon espace</a><span class="avatar">{{ mb_strtoupper(mb_substr($identity->label, 0, 1)) }}</span></div>
        </header>

        <main class="discovery-content">
            <section class="discovery-hero"><div><p class="eyebrow dark">CAPACITÉS HUMAINES</p><h1>{{ $settings['title'] }}</h1><p>{{ $settings['introduction'] }}</p></div><a href="{{ route('member.profile.edit') }}">Gérer ma visibilité</a></section>

            <form class="discovery-search" method="GET" action="{{ route('people.index') }}">
                <label class="discovery-query"><span>Que recherchez-vous ?</span><input name="q" value="{{ request('q') }}" maxlength="100" placeholder="Ex. couture, comptabilité, informatique…"></label>
                @if($settings['country_filter'])<label><span>Pays</span><input name="country" value="{{ request('country') }}" maxlength="2" placeholder="CI"></label>@endif
                @if($settings['mode_filter'])<label><span>Mode</span><select name="mode"><option value="">Tous</option>@foreach($modes as $mode)<option value="{{ $mode['value'] }}" @selected(request('mode') === $mode['value'])>{{ $mode['label'] }}</option>@endforeach</select></label>@endif
                <button type="submit">Découvrir</button>
            </form>

            <div class="discovery-privacy"><strong>Une découverte avec consentement.</strong><span>{{ $settings['privacy_notice'] }}</span></div>

            @if($profiles->isEmpty())
                <section class="discovery-empty"><i>⌕</i><h2>{{ $settings['empty_title'] }}</h2><p>{{ $settings['empty_text'] }}</p></section>
            @else
                <div class="discovery-result-heading"><h2>{{ $profiles->total() }} profil(s) utile(s)</h2><span>Sans classement de valeur</span></div>
                <section class="people-grid">
                    @foreach($profiles as $profile)
                        @php($visibleCapabilities = $profile->capabilityStatements->take(5))
                        <article class="person-card">
                            <div class="person-card-head"><span>{{ mb_strtoupper(mb_substr($profile->discovery_display_name, 0, 1)) }}</span><div><h3>{{ $profile->discovery_display_name }}</h3><p>{{ collect([$profile->current_activity, $profile->city, $profile->country_code])->filter()->implode(' · ') ?: 'Contexte à compléter' }}</p></div></div>
                            @if($profile->discovery_bio)<p class="person-bio">{{ $profile->discovery_bio }}</p>@endif
                            <div class="person-reasons"><small>Pourquoi ce profil apparaît</small><div>@forelse($visibleCapabilities as $capability)<span>{{ $capability->label }}</span>@empty<span>Profil volontairement visible</span>@endforelse</div></div>
                            <a href="{{ route('people.show', $profile->discovery_reference) }}">{{ $settings['detail_button'] }} →</a>
                        </article>
                    @endforeach
                </section>
                @if($profiles->hasPages())<nav class="discovery-pagination" aria-label="Pagination">@if($profiles->onFirstPage())<span>← Précédent</span>@else<a href="{{ $profiles->previousPageUrl() }}">← Précédent</a>@endif<strong>Page {{ $profiles->currentPage() }} sur {{ $profiles->lastPage() }}</strong>@if($profiles->hasMorePages())<a href="{{ $profiles->nextPageUrl() }}">Suivant →</a>@else<span>Suivant →</span>@endif</nav>@endif
            @endif
        </main>
    </div>
</x-layouts.portal>
