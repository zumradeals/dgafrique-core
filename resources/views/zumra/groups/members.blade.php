<x-layouts.member :title="'Membres · '.$group->name" active="zumra" :wide="true">
    <style>
        .dg-zumra-members{max-width:1180px;margin:0 auto;padding:1.25rem 1rem 4rem}.dg-zumra-members__hero{padding:2rem;border-radius:28px;background:linear-gradient(135deg,#071f35,#123d5d);color:#fff;box-shadow:0 20px 50px rgba(7,31,53,.16)}.dg-zumra-members__back{color:#c8e7ff;text-decoration:none;font-weight:700}.dg-zumra-members__eyebrow{margin:2rem 0 .4rem;font-size:.76rem;font-weight:900;letter-spacing:.16em;color:#8fd0ff}.dg-zumra-members__headline{display:flex;align-items:flex-end;justify-content:space-between;gap:2rem}.dg-zumra-members__headline h1{max-width:760px;margin:.2rem 0 .7rem;font-size:clamp(2rem,4vw,3.5rem);line-height:1.02}.dg-zumra-members__headline p{max-width:720px;margin:0;color:#d9e9f4}.dg-zumra-members__count{min-width:130px;padding:1rem 1.25rem;border:1px solid rgba(255,255,255,.18);border-radius:18px;background:rgba(255,255,255,.08);text-align:center}.dg-zumra-members__count strong{display:block;font-size:2rem}.dg-zumra-members__count span{font-size:.8rem}.dg-zumra-members__intro{margin:1rem 0 1.25rem;padding:1rem 1.2rem;border:1px solid #dfe8ef;border-radius:18px;background:#fff}.dg-zumra-members__intro p{margin:.25rem 0 0;color:#607180}.dg-zumra-members__grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}.dg-zumra-member-card{display:flex;min-height:250px;flex-direction:column;padding:1.25rem;border:1px solid #e0e8ee;border-radius:22px;background:#fff;box-shadow:0 10px 30px rgba(17,47,70,.06)}.dg-zumra-member-card__top{display:flex;align-items:center;gap:.85rem}.dg-zumra-member-card__avatar{display:grid;width:54px;height:54px;flex:0 0 54px;place-items:center;border-radius:18px;background:#eaf5fd;color:#0d659b;font-size:1.2rem;font-weight:900}.dg-zumra-member-card h2{margin:0;font-size:1.05rem}.dg-zumra-member-card h2 small{margin-left:.35rem;padding:.2rem .45rem;border-radius:999px;background:#eef7ee;color:#32753b;font-size:.65rem}.dg-zumra-member-card__role{margin:.25rem 0 0;color:#0d659b;font-size:.8rem;font-weight:800}.dg-zumra-member-card__role--member{color:#70808b}.dg-zumra-member-card__bio{margin:1rem 0;color:#405564;line-height:1.55}.dg-zumra-member-card__meta{display:flex;flex-wrap:wrap;gap:.4rem;margin-top:auto}.dg-zumra-member-card__meta span{padding:.3rem .55rem;border-radius:999px;background:#f3f6f8;color:#60717d;font-size:.72rem}.dg-zumra-member-card__action,.dg-zumra-member-card__private{margin-top:1rem;font-size:.8rem;font-weight:800}.dg-zumra-member-card__action{color:#0d659b;text-decoration:none}.dg-zumra-member-card__private{color:#89959d}.dg-zumra-members__empty{grid-column:1/-1;padding:3rem;border:1px dashed #cad7df;border-radius:22px;text-align:center}.dg-zumra-members__empty p{color:#70808b}@media(max-width:900px){.dg-zumra-members__grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:640px){.dg-zumra-members{padding:.75rem .75rem 5rem}.dg-zumra-members__hero{padding:1.35rem;border-radius:22px}.dg-zumra-members__headline{align-items:stretch;flex-direction:column;gap:1rem}.dg-zumra-members__count{display:flex;align-items:baseline;gap:.5rem;text-align:left}.dg-zumra-members__count strong{font-size:1.6rem}.dg-zumra-members__grid{grid-template-columns:1fr}.dg-zumra-member-card{min-height:0}}
    </style>

    <div class="dg-zumra-members">
        <header class="dg-zumra-members__hero">
            <a class="dg-zumra-members__back" href="{{ route('zumra.groups.show', $group) }}">← Retour à {{ $group->name }}</a>
            <p class="dg-zumra-members__eyebrow">MEMBRES · ZUMRA</p>
            <div class="dg-zumra-members__headline">
                <div><h1>Les personnes qui font vivre {{ $group->name }}</h1><p>Un espace humain de la communauté : membres actifs et responsabilités réellement acceptées dans la ZUMRA.</p></div>
                <div class="dg-zumra-members__count"><strong>{{ $membersCount }}</strong><span>membre{{ $membersCount === 1 ? '' : 's' }} actif{{ $membersCount === 1 ? '' : 's' }}</span></div>
            </div>
        </header>

        <section class="dg-zumra-members__intro" aria-label="Règle de visibilité"><strong>Nous, dans cette ZUMRA.</strong><p>Les informations personnelles affichées ici proviennent uniquement du profil GAMAD lorsqu’un membre a choisi d’être découvrable. L’appartenance à la ZUMRA reste visible sans forcer l’exposition de son profil.</p></section>

        <main class="dg-zumra-members__grid">
            @forelse ($members as $member)
                <article class="dg-zumra-member-card">
                    <div class="dg-zumra-member-card__top"><span class="dg-zumra-member-card__avatar" aria-hidden="true">{{ $member['initial'] }}</span><div><h2>{{ $member['display_name'] }} @if($member['is_self'])<small>Vous</small>@endif</h2>@if ($member['role_label'])<p class="dg-zumra-member-card__role">{{ $member['role_label'] }}</p>@else<p class="dg-zumra-member-card__role dg-zumra-member-card__role--member">Membre</p>@endif</div></div>
                    @if ($member['bio'])<p class="dg-zumra-member-card__bio">{{ $member['bio'] }}</p>@endif
                    <div class="dg-zumra-member-card__meta">@if ($member['current_activity'])<span>◈ {{ $member['current_activity'] }}</span>@endif @if ($member['city'])<span>⌖ {{ $member['city'] }}</span>@endif @if ($member['joined_at'])<span>Depuis {{ $member['joined_at']->translatedFormat('M Y') }}</span>@endif</div>
                    @if ($member['discovery_reference'])<a class="dg-zumra-member-card__action" href="{{ route('people.show', $member['discovery_reference']) }}">Voir le profil GAMAD →</a>@elseif (!$member['is_self'])<span class="dg-zumra-member-card__private">Profil non publié dans Découvrir</span>@endif
                </article>
            @empty
                <section class="dg-zumra-members__empty"><strong>Aucun membre actif à afficher.</strong><p>Cette surface ne fabrique jamais de membres pour remplir l’espace.</p></section>
            @endforelse
        </main>
    </div>
</x-layouts.member>
