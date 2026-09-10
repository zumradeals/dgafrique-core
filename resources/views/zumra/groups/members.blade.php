<x-layouts.member :title="'Membres · '.$group->name" active="zumra" :wide="true">
    <div class="dg-zumra-members">
        <header class="dg-zumra-members__hero">
            <a class="dg-zumra-members__back" href="{{ route('zumra.groups.show', $group) }}">← Retour à {{ $group->name }}</a>
            <p class="dg-zumra-members__eyebrow">MEMBRES · ZUMRA</p>
            <div class="dg-zumra-members__headline">
                <div>
                    <h1>Les personnes qui font vivre {{ $group->name }}</h1>
                    <p>Un espace humain de la communauté : membres actifs et responsabilités réellement acceptées dans la ZUMRA.</p>
                </div>
                <div class="dg-zumra-members__count"><strong>{{ $membersCount }}</strong><span>membre{{ $membersCount === 1 ? '' : 's' }} actif{{ $membersCount === 1 ? '' : 's' }}</span></div>
            </div>
        </header>

        <section class="dg-zumra-members__intro" aria-label="Règle de visibilité">
            <strong>Nous, dans cette ZUMRA.</strong>
            <p>Les informations personnelles affichées ici proviennent uniquement du profil GAMAD lorsqu’un membre a choisi d’être découvrable. L’appartenance à la ZUMRA reste visible sans forcer l’exposition de son profil.</p>
        </section>

        <main class="dg-zumra-members__grid">
            @forelse ($members as $member)
                <article class="dg-zumra-member-card">
                    <div class="dg-zumra-member-card__top">
                        <span class="dg-zumra-member-card__avatar" aria-hidden="true">{{ $member['initial'] }}</span>
                        <div>
                            <h2>{{ $member['display_name'] }} @if($member['is_self'])<small>Vous</small>@endif</h2>
                            @if ($member['role_label'])<p class="dg-zumra-member-card__role">{{ $member['role_label'] }}</p>@else<p class="dg-zumra-member-card__role dg-zumra-member-card__role--member">Membre</p>@endif
                        </div>
                    </div>

                    @if ($member['bio'])<p class="dg-zumra-member-card__bio">{{ $member['bio'] }}</p>@endif
                    <div class="dg-zumra-member-card__meta">
                        @if ($member['current_activity'])<span>◈ {{ $member['current_activity'] }}</span>@endif
                        @if ($member['city'])<span>⌖ {{ $member['city'] }}</span>@endif
                        @if ($member['joined_at'])<span>Depuis {{ $member['joined_at']->translatedFormat('M Y') }}</span>@endif
                    </div>

                    @if ($member['discovery_reference'])
                        <a class="dg-zumra-member-card__action" href="{{ route('people.show', $member['discovery_reference']) }}">Voir le profil GAMAD →</a>
                    @elseif (!$member['is_self'])
                        <span class="dg-zumra-member-card__private">Profil non publié dans Découvrir</span>
                    @endif
                </article>
            @empty
                <section class="dg-zumra-members__empty">
                    <strong>Aucun membre actif à afficher.</strong>
                    <p>Cette surface ne fabrique jamais de membres pour remplir l’espace.</p>
                </section>
            @endforelse
        </main>
    </div>
</x-layouts.member>
