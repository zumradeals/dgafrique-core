<x-layouts.member :title="'Fil · '.$group->name" active="zumra" :wide="true">
    <style>
        .za-wrap{max-width:1100px;margin:0 auto;padding:26px 18px 48px}.za-hero{border-radius:24px;padding:28px;background:linear-gradient(135deg,#0c2d48,#174f78);color:#fff;box-shadow:0 18px 45px rgba(12,45,72,.18)}.za-kicker{margin:0 0 6px;font-size:.78rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;opacity:.78}.za-hero h1{margin:0;font-size:clamp(1.8rem,5vw,3rem)}.za-hero p{max-width:700px;margin:10px 0 0;line-height:1.55;opacity:.92}.za-tabs{display:flex;gap:8px;overflow:auto;margin:16px 0 22px;padding-bottom:3px}.za-tabs a{white-space:nowrap;text-decoration:none;border:1px solid #dce6ed;border-radius:999px;padding:9px 14px;color:#27465d;background:#fff;font-weight:700}.za-tabs a.is-active{background:#0c2d48;color:#fff;border-color:#0c2d48}.za-grid{display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:22px}.za-feed{display:grid;gap:14px}.za-card{background:#fff;border:1px solid #e4ebf0;border-radius:18px;padding:18px;box-shadow:0 8px 28px rgba(33,59,77,.06)}.za-meta{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:9px}.za-kind,.za-event{display:inline-flex;border-radius:999px;padding:5px 9px;font-size:.75rem;font-weight:800}.za-kind{background:#eaf4fb;color:#075c94}.za-event{background:#fff3e9;color:#a94c05}.za-card h2{font-size:1.08rem;margin:0 0 7px;color:#17384f}.za-card p{margin:0;color:#5b6f7d;line-height:1.5}.za-context{margin-top:8px!important;font-size:.88rem}.za-foot{display:flex;justify-content:space-between;gap:14px;align-items:center;margin-top:14px}.za-time{font-size:.8rem;color:#8496a2}.za-action{text-decoration:none;font-weight:800;color:#075c94}.za-side{display:grid;gap:14px;align-content:start}.za-side .za-card h2{font-size:1rem}.za-empty{text-align:center;padding:42px 20px}.za-empty strong{display:block;font-size:1.15rem;margin-bottom:7px}.za-pagination{margin-top:18px}.za-note{font-size:.9rem;color:#6c7f8c;line-height:1.5}.za-side a{color:#075c94;text-decoration:none;font-weight:700}@media(max-width:800px){.za-grid{grid-template-columns:1fr}.za-side{order:-1}.za-hero{padding:22px}.za-wrap{padding-inline:12px}}
    </style>

    <div class="za-wrap">
        <section class="za-hero">
            <p class="za-kicker">FIL · ZUMRA</p>
            <h1>{{ $group->name }}</h1>
            <p>Ce qui bouge réellement dans cette ZUMRA : membres, missions, formation, preuves et événements. Aucun post libre n’est inventé ici ; le Fil reflète les actions déjà reconnues par GAMAD.</p>
        </section>

        <nav class="za-tabs" aria-label="Navigation ZUMRA">
            <a href="{{ route('zumra.groups.show', $group) }}">Accueil</a>
            <a class="is-active" href="{{ route('zumra.groups.activity', $group) }}">Fil</a>
            <a href="{{ route('zumra.groups.members', $group) }}">Membres</a>
            <a href="{{ route('zumra.groups.formation', $group) }}">Formation</a>
            <a href="{{ route('zumra.groups.discussion', $group) }}">Discussion</a>
            <a href="{{ route('community-events.zumra.index', $group) }}">Événements</a>
        </nav>

        <div class="za-grid">
            <main>
                <div class="za-feed" aria-label="Activité de la ZUMRA">
                    @forelse($feed as $item)
                        <article class="za-card">
                            <div class="za-meta">
                                <span class="za-kind">{{ $item['kind_label'] }}</span>
                                <span class="za-event">{{ $item['event_label'] }}</span>
                            </div>
                            <h2>{{ $item['title'] }}</h2>
                            @if(!empty($item['summary']))<p>{{ $item['summary'] }}</p>@endif
                            @if(!empty($item['context']))<p class="za-context">{{ $item['context'] }}</p>@endif
                            <div class="za-foot">
                                <time class="za-time" datetime="{{ $item['occurred_at']?->toIso8601String() }}">{{ $item['occurred_at']?->diffForHumans() }}</time>
                                <a class="za-action" href="{{ $item['action_url'] }}">{{ $item['action_label'] }} →</a>
                            </div>
                        </article>
                    @empty
                        <section class="za-card za-empty">
                            <strong>Le Fil démarre ici.</strong>
                            <p>Les prochaines actions reconnues de la ZUMRA apparaîtront automatiquement dans cet espace.</p>
                        </section>
                    @endforelse
                </div>

                @if($feed->hasPages())
                    <div class="za-pagination">{{ $feed->links() }}</div>
                @endif
            </main>

            <aside class="za-side">
                <section class="za-card">
                    <h2>Un Fil d’action</h2>
                    <p class="za-note">Le mini Fil est une projection en lecture. Discussion reste l’espace de conversation ; Formation, Missions, Preuves et Événements restent gouvernés par leurs moteurs métier respectifs.</p>
                </section>
                <section class="za-card">
                    <h2>Accès rapide</h2>
                    <p><a href="{{ route('zumra.groups.discussion', $group) }}">Ouvrir la Discussion</a></p>
                    <p><a href="{{ route('zumra.groups.formation', $group) }}">Entrer en Formation</a></p>
                    <p><a href="{{ route('community-events.zumra.index', $group) }}">Voir les Événements</a></p>
                </section>
            </aside>
        </div>
    </div>
</x-layouts.member>
