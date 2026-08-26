{{--
    Mes Missions — CAP-069. Neuf sections honnêtes (v0.4 §16/17), jamais un mur de
    statistiques ni un score de productivité. Chaque section reste vide et le dit quand
    rien n'est réellement disponible.

    UX-HARMONY-MISSIONS-001 — ce contenu est identique à l'ancien missions/index.blade.php,
    simplement déplacé sous /missions?scope=mine pour laisser la place à l'annuaire public de
    découverte des Missions sur /missions (nouvelle référence UX). Rien n'est retiré : ce
    tableau de bord personnel reste entier et accessible tel quel.
--}}
<x-layouts.portal title="Mes Missions — DG Afrique">
    <x-dg.shell :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page">
            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="saffron">Missions</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">Mes Missions</h1>
                    <p>Une Mission transforme une intention en engagement explicite. Proposer n’est pas décider, accepter n’est pas commencer, soumettre n’est pas valider.</p>
                </div>
                <a href="{{ route('missions.index') }}" class="dg-crumb">← Découvrir toutes les missions</a>
            </div>

            @if(session('status'))
                <div class="dg-band" style="margin-bottom:24px">{{ session('status') }}</div>
            @endif

            @php
                $sectionsMeta = [
                    'a_decider' => ['title' => 'À décider', 'empty' => 'Aucune proposition n’attend votre décision.'],
                    'a_valider' => ['title' => 'À valider', 'empty' => 'Aucun résultat soumis n’attend votre validation.'],
                    'bloquees' => ['title' => 'Bloquées', 'empty' => 'Aucune Mission bloquée actuellement.'],
                    'a_soumettre' => ['title' => 'À soumettre', 'empty' => 'Aucune Mission en cours n’attend une soumission de votre part.'],
                    'invitations' => ['title' => 'Invitations', 'empty' => 'Aucune invitation en attente.'],
                    'je_me_suis_propose' => ['title' => 'Je me suis proposé', 'empty' => 'Aucune offre de participation en attente.'],
                    'mes_engagements' => ['title' => 'Mes engagements', 'empty' => 'Aucun engagement actif pour le moment.'],
                    'mes_propositions' => ['title' => 'Mes propositions', 'empty' => 'Aucune proposition de Mission en cours.'],
                    'terminees' => ['title' => 'Terminées', 'empty' => 'Aucune Mission terminée pour le moment.'],
                ];
            @endphp

            <div class="dg-grid">
                @foreach($sectionsMeta as $key => $meta)
                    @php($items = $sections[$key] ?? collect())
                    <x-dg.card>
                        <div class="flex items-center justify-between gap-3">
                            <x-dg.label>{{ $meta['title'] }}</x-dg.label>
                            <span class="dg-meta">{{ $items->count() }}</span>
                        </div>
                        <div style="margin-top:12px">
                            @forelse($items->take(6) as $item)
                                @php($mission = $item instanceof \App\Models\Mission ? $item : $item->mission)
                                @if($mission)
                                    <x-dg.mission-row :mission="$mission" />
                                @endif
                            @empty
                                <x-dg.empty>
                                    <span>{{ $meta['empty'] }}</span>
                                </x-dg.empty>
                            @endforelse
                        </div>
                    </x-dg.card>
                @endforeach
            </div>
        </div>
    </x-dg.shell>
</x-layouts.portal>
