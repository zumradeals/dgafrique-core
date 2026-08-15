<x-layouts.portal title="Les ZUMRA — DG Afrique">
    <div class="group-page">
        <header class="membership-topbar"><a href="{{ route('zumra.index') }}">← ZUMRA</a><strong><i>Z</i> Groupes humains</strong><a href="{{ route('member.space') }}">Mon espace</a></header>
        <main class="group-content">
            @if(session('status'))<div class="alert success">{{ session('status') }}</div>@endif
            <section class="group-directory-hero">
                <div><p class="eyebrow dark">ZUMRA · ACTION COLLECTIVE</p><h1>{{ $configuration['directory_title'] }}</h1><p>{{ $configuration['directory_intro'] }}</p></div>
                @if($membership?->status === 'ACTIVE')<a href="{{ route('zumra.groups.create') }}">Proposer une ZUMRA</a>@else<a href="{{ route('zumra.membership.show') }}">Activer mon adhésion</a>@endif
            </section>
            <div class="group-doctrine"><strong>Une ZUMRA n’est pas un simple groupe de discussion.</strong><span>Elle rassemble des personnes autour d’un objectif, d’une transmission et d’une action réelle. Une demande n’est jamais une adhésion automatique.</span></div>
            @if($groups->isEmpty())
                <section class="group-empty"><i>Z</i><h2>La première ZUMRA peut commencer ici.</h2><p>Aucune équipe fictive n’est affichée. Les groupes apparaîtront à mesure que des adhérents proposeront des projets humains réels.</p></section>
            @else
                <section class="group-grid">
                    @foreach($groups as $group)
                        <article class="group-card"><div class="group-card-top"><span>{{ mb_strtoupper(mb_substr($group->name,0,1)) }}</span><div><small>{{ $group->domain }}</small><h2>{{ $group->name }}</h2></div></div><p>{{ $group->founding_objective }}</p><div class="group-facts"><span>{{ match($group->participation_mode){'PHYSICAL'=>'Physique','DIGITAL'=>'Numérique',default=>'Hybride'} }}</span><span>{{ $group->active_member_count }} membre{{ $group->active_member_count > 1 ? 's' : '' }}</span><span>{{ $group->maturity === 'ESTABLISHED' ? 'Établie' : 'Émergente' }}</span></div>@if($myMemberships->has($group->id))<b>{{ match($myMemberships[$group->id]){'ACTIVE'=>'Vous êtes membre','INVITED'=>'Invitation reçue',default=>'Demande en attente'} }}</b>@endif<a href="{{ route('zumra.groups.show',$group) }}">Voir la ZUMRA →</a></article>
                    @endforeach
                </section>
                <div class="discovery-pagination">{{ $groups->links() }}</div>
            @endif
        </main>
    </div>
</x-layouts.portal>
