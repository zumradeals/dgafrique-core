{{--
    Opportunités (CAP-064), UIUX-002 décision #5 — surface humaine dédiée. Projection en lecture
    seule d'OpportunityEngine::forIdentity() : jamais de score, jamais l'entier `priority` interne,
    jamais un identifiant technique. Chaque carte explique « pourquoi » à partir des `reasons`
    déjà produites par le moteur.
--}}
<x-layouts.portal title="Opportunités — DG Afrique">
    <x-dg.shell :identity="$identity">
        <div class="dg-page">
            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="saffron">Opportunités</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">Ce que vous pourriez rejoindre</h1>
                    <p>Des Missions réelles rapprochées de ce que vous avez déclaré savoir faire — jamais un classement, jamais une obligation.</p>
                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:12px;max-width:760px">
                @forelse($opportunities as $opportunity)
                    <x-dg.card>
                        <p class="dg-body" style="margin:0;font-weight:600;color:var(--dg-night)">{{ $opportunity['title'] }}</p>

                        @if(! empty($opportunity['reasons']))
                            <ul style="margin:8px 0 0;padding-left:18px;display:flex;flex-direction:column;gap:4px">
                                @foreach($opportunity['reasons'] as $reason)
                                    <li class="dg-body" style="font-size:14px">{{ $reason }}</li>
                                @endforeach
                            </ul>
                        @endif

                        <div style="margin-top:10px">
                            <a href="{{ route('missions.show', ['mission' => $opportunity['reference']]) }}" class="dg-btn dg-btn--quiet">Voir la mission →</a>
                        </div>
                    </x-dg.card>
                @empty
                    <x-dg.empty title="Aucune opportunité pour le moment">
                        <span>Une opportunité apparaît lorsqu’une capacité que vous avez déclarée, avec votre consentement, correspond à une Mission ouverte. <a href="{{ route('member.profile.edit') }}">Gérer mes capacités et mon consentement →</a></span>
                    </x-dg.empty>
                @endforelse
            </div>
        </div>
    </x-dg.shell>
</x-layouts.portal>
