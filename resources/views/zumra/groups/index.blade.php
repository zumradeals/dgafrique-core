{{--
    Répertoire des ZUMRA — extension du Hub ZUMRA (/zumra), jamais un fil autonome. Aucune équipe
    fictive : les groupes n'apparaissent qu'à mesure de propositions réelles.
--}}
<x-layouts.portal title="Les ZUMRA — DG Afrique">
    <x-dg.shell current="zumra" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page">
            <a href="{{ route('zumra.index') }}" class="dg-crumb">← ZUMRA</a>

            @if(session('status'))
                <div class="dg-band" style="margin-bottom:20px">{{ session('status') }}</div>
            @endif

            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="saffron">ZUMRA · Action collective</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">{{ $configuration['directory_title'] }}</h1>
                    <p>{{ $configuration['directory_intro'] }}</p>
                </div>
                @if($membership?->status === 'ACTIVE')
                    <x-dg.btn variant="saffron" :href="route('zumra.groups.create')">Proposer une ZUMRA</x-dg.btn>
                @else
                    <x-dg.btn variant="quiet" :href="route('zumra.membership.show')">Activer mon adhésion</x-dg.btn>
                @endif
            </div>

            <form method="GET" action="{{ route('zumra.groups.index') }}" role="search" style="display:flex;gap:10px;margin-bottom:20px">
                <label for="directory-search" class="sr-only">Rechercher une activité, un nom ou un objectif</label>
                <input type="search" id="directory-search" name="q" class="dg-input" style="flex:1" placeholder="Chercher une activité, un nom, un objectif…" value="{{ $query ?? '' }}">
                <button type="submit" class="dg-btn dg-btn--primary">Chercher</button>
                @if(filled($query ?? null))
                    <a href="{{ route('zumra.groups.index') }}" class="dg-btn dg-btn--quiet">Effacer</a>
                @endif
            </form>

            <div class="dg-band" style="margin-bottom:24px">
                <strong style="display:block;font-size:14px;color:var(--dg-forest);margin-bottom:4px">Une ZUMRA n’est pas un simple groupe de discussion.</strong>
                Elle rassemble des personnes autour d’un objectif, d’une transmission et d’une action réelle. Une demande n’est jamais une adhésion automatique.
            </div>

            @if($groups->isEmpty())
                <x-dg.empty :title="filled($query ?? null) ? 'Aucune ZUMRA ne correspond à « '.$query.' ».' : 'La première ZUMRA peut commencer ici.'">
                    <span>Aucune équipe fictive n’est affichée. Les groupes apparaîtront à mesure que des adhérents proposeront des projets humains réels.</span>
                </x-dg.empty>
            @else
                <div class="dg-grid">
                    @foreach($groups as $group)
                        <article class="dg-card" style="display:flex;flex-direction:column;gap:14px">
                            <div class="flex items-center gap-3">
                                <x-dg.avatar :initials="mb_strtoupper(mb_substr($group->name, 0, 1))" tone="night" />
                                <div>
                                    <div class="dg-meta">{{ $group->domain }}</div>
                                    <h2 class="dg-display dg-display--card" style="font-size:20px">{{ $group->name }}</h2>
                                </div>
                            </div>
                            <p class="dg-body">{{ \Illuminate\Support\Str::limit($group->founding_objective, 140) }}</p>
                            <div style="display:flex;flex-wrap:wrap;gap:6px">
                                <x-dg.badge tone="neutral">{{ match($group->participation_mode) { 'PHYSICAL' => 'Physique', 'DIGITAL' => 'Numérique', default => 'Hybride' } }}</x-dg.badge>
                                <x-dg.badge tone="neutral">{{ $group->active_member_count }} membre{{ $group->active_member_count > 1 ? 's' : '' }}</x-dg.badge>
                                <x-dg.badge tone="neutral">{{ $group->maturity === 'ESTABLISHED' ? 'Établie' : 'Émergente' }}</x-dg.badge>
                            </div>
                            @if($myMemberships->has($group->id))
                                <x-dg.label tone="saffron">{{ match($myMemberships[$group->id]) { 'ACTIVE' => 'Vous êtes membre', 'INVITED' => 'Invitation reçue', default => 'Demande en attente' } }}</x-dg.label>
                            @endif
                            <x-dg.actions flush>
                                <x-dg.btn variant="quiet" :href="route('zumra.groups.show', $group)">Voir la ZUMRA →</x-dg.btn>
                            </x-dg.actions>
                        </article>
                    @endforeach
                </div>
                <div>{{ $groups->links('pagination.dg') }}</div>
            @endif
        </div>
    </x-dg.shell>
</x-layouts.portal>
