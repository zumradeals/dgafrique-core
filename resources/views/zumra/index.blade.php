{{--
    Hub ZUMRA — espace de découverte, d'adhésion et d'organisation des collectifs.
    DG Afrique n'a qu'un seul Fil social (/activite) : cet écran n'en recrée jamais un second.
    Les mouvements ZUMRA se retrouvent dans le Fil global, filtré (activity.index?type=ZUMRA).
--}}
<x-layouts.portal title="ZUMRA — DG Afrique">
    <x-dg.shell current="zumra" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page">
            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="saffron">Le programme ZUMRA</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">Découvrir, rejoindre et organiser les collectifs</h1>
                    <p>Une ZUMRA est une équipe qui s’engage vraiment : un domaine, un objectif fondateur, une charte, cinq responsabilités nommées et acceptées explicitement.</p>
                </div>
                <x-dg.btn variant="quiet" :href="route('activity.index', ['type' => 'ZUMRA'])">Voir les activités ZUMRA →</x-dg.btn>
            </div>

            @if(! $membership)
                <x-dg.deep style="margin-bottom:28px">
                    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:24px">
                        <div style="display:flex;flex-direction:column;gap:8px;max-width:56ch">
                            <x-dg.label tone="saffron">Adhésion</x-dg.label>
                            <h2 class="dg-display" style="font-size:30px">Le compte DG Afrique est gratuit ; l’adhésion au Programme ZUMRA est un engagement distinct.</h2>
                            <p style="margin:0;font-size:15px;line-height:1.65;color:var(--dg-on-deep-text)">Aucune adhésion automatique, aucun rôle attribué en silence.</p>
                        </div>
                        <x-dg.btn variant="saffron" :href="route('zumra.membership.show')" style="padding:14px 22px">Découvrir l’adhésion</x-dg.btn>
                    </div>
                </x-dg.deep>
            @elseif($membership->status === \App\Models\ZumraProgramMembership::STATUS_PENDING_PAYMENT)
                <x-dg.deep style="margin-bottom:28px">
                    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:24px">
                        <div style="display:flex;flex-direction:column;gap:8px;max-width:56ch">
                            <x-dg.label tone="saffron">Adhésion en attente</x-dg.label>
                            <h2 class="dg-display" style="font-size:30px">Votre dossier est prêt, la contribution n’est pas encore réglée.</h2>
                        </div>
                        <x-dg.btn variant="saffron" :href="route('zumra.membership.show')" style="padding:14px 22px">Finaliser mon adhésion</x-dg.btn>
                    </div>
                </x-dg.deep>
            @endif

            @if($pendingRequestsToDecide->isNotEmpty())
                <div class="dg-band" style="margin-bottom:28px;display:flex;flex-direction:column;gap:10px">
                    <strong style="display:block;font-size:14px;color:var(--dg-forest)">Demandes à examiner</strong>
                    @foreach($pendingRequestsToDecide as $row)
                        <a href="{{ route('zumra.groups.show', $row['group']) }}" style="font-size:14px;color:var(--dg-forest-action)">{{ $row['count'] }} demande(s) d’adhésion pour {{ $row['group']->name }} →</a>
                    @endforeach
                </div>
            @endif

            <div class="dg-grid">
                <x-dg.card>
                    <x-dg.label>Mes ZUMRA</x-dg.label>
                    <div style="display:flex;flex-direction:column;gap:12px;margin-top:14px">
                        @forelse($myGroups as $row)
                            <a href="{{ route('zumra.groups.show', $row['group']) }}" class="dg-person" style="color:inherit">
                                <x-dg.avatar :initials="mb_strtoupper(mb_substr($row['group']->name, 0, 2))" />
                                <div>
                                    <div class="dg-person__name">{{ $row['group']->name }}</div>
                                    <div class="dg-person__role">
                                        @if($row['status'] === 'ACTIVE') Membre actif
                                        @elseif($row['status'] === 'INVITED') Invitation reçue
                                        @else Demande en attente
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @empty
                            <x-dg.empty title="Vous n’êtes membre d’aucune ZUMRA">
                                <span>Découvrez les ZUMRA existantes, ou proposez la vôtre une fois adhérent au Programme.</span>
                            </x-dg.empty>
                        @endforelse
                    </div>
                </x-dg.card>

                <x-dg.card>
                    <x-dg.label>Découvrir des ZUMRA</x-dg.label>
                    <p class="dg-body" style="margin-top:14px">Parcourir les collectifs existants, leur domaine et leur objectif fondateur.</p>
                    <div style="margin-top:16px">
                        <x-dg.btn variant="project" :href="route('zumra.groups.index')">Parcourir les ZUMRA</x-dg.btn>
                    </div>
                </x-dg.card>

                <x-dg.card>
                    <x-dg.label>Proposer une ZUMRA</x-dg.label>
                    <p class="dg-body" style="margin-top:14px">Un domaine, un objectif fondateur, une charte. Aucun rôle vacant n’est attribué automatiquement.</p>
                    <div style="margin-top:16px">
                        @if($membership && $membership->status === \App\Models\ZumraProgramMembership::STATUS_ACTIVE)
                            <x-dg.btn variant="project" :href="route('zumra.groups.create')">Proposer une ZUMRA</x-dg.btn>
                        @else
                            <x-dg.btn variant="quiet" disabled title="Réservé aux membres du Programme ZUMRA dont l’adhésion est active.">Proposer une ZUMRA</x-dg.btn>
                        @endif
                    </div>
                </x-dg.card>

                <x-dg.card>
                    <x-dg.label>Mon adhésion</x-dg.label>
                    <p class="dg-body" style="margin-top:14px">
                        @if(! $membership) Aucune adhésion au Programme ZUMRA n’est encore ouverte.
                        @else Statut actuel : <strong>{{ ucfirst(mb_strtolower(str_replace('_', ' ', $membership->status))) }}</strong>.
                        @endif
                    </p>
                    <div style="margin-top:16px">
                        <x-dg.btn variant="quiet" :href="route('zumra.membership.show')">Voir mon adhésion</x-dg.btn>
                    </div>
                </x-dg.card>

                <x-dg.card>
                    <x-dg.label>Ma Carte ZUMRA</x-dg.label>
                    <p class="dg-body" style="margin-top:14px">La carte n’existe que pour une adhésion active — elle sert à prouver votre appartenance au Programme.</p>
                    <div style="margin-top:16px">
                        <x-dg.btn variant="quiet" :href="route('zumra.card.show')">Voir ma carte</x-dg.btn>
                    </div>
                </x-dg.card>
            </div>
        </div>
    </x-dg.shell>
</x-layouts.portal>
