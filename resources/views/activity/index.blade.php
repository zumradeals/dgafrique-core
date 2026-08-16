<x-layouts.portal title="Fil d’action — DG Afrique">
    <main class="dg-shell">
        <x-portal.action-nav />

        <div class="dg-container dg-feed">
            <header class="dg-feed-hero">
                <div>
                    <p class="dg-kicker">Fil d’action · CAP-019</p>
                    <h1>Ce qui peut vous faire avancer</h1>
                    <p class="dg-feed-hero__lead">Besoins, projets et mouvements ZUMRA réellement enregistrés dans DG Afrique. Le fil privilégie l’utilité, la transmission et l’action concrète — sans score humain, sans course à la popularité et sans défilement infini.</p>
                </div>
                <aside class="dg-trust-note">
                    <strong>Votre visibilité est respectée</strong>
                    Vous ne voyez que ce que votre identité est autorisée à consulter. Le design n’élargit jamais les permissions métier.
                </aside>
            </header>

            <form class="dg-filter-row" method="GET" aria-label="Filtrer l’activité">
                @foreach($filters as $code => $label)
                    <button
                        class="dg-filter {{ $filter === $code ? 'is-active' : '' }}"
                        type="submit"
                        name="type"
                        value="{{ $code }}"
                    >{{ $label }}</button>
                @endforeach
            </form>

            @if($feed->isEmpty())
                <section class="dg-empty">
                    <span class="dg-empty__mark" aria-hidden="true">A</span>
                    <h2>Aucune activité utile à afficher</h2>
                    <p>Le fil ne fabrique rien. Des éléments apparaîtront lorsqu’un besoin, un projet ou une ZUMRA produira un événement réellement visible pour vous.</p>
                </section>
            @else
                <section class="dg-feed-list" aria-label="Activité">
                    @foreach($feed as $item)
                        <article class="dg-feed-card" data-kind="{{ $item['kind'] }}">
                            <div class="dg-card-meta">
                                <div class="dg-card-meta__left">
                                    <span class="dg-kind {{ $item['kind'] === 'NEEDS' ? 'dg-kind--needs' : ($item['kind'] === 'PROJECTS' ? 'dg-kind--projects' : '') }}">{{ $item['kind_label'] }}</span>
                                    <span class="dg-event">{{ $item['event_label'] }}</span>
                                </div>
                                <time class="dg-time" datetime="{{ $item['occurred_at']->toAtomString() }}">{{ $item['occurred_at']->locale('fr')->diffForHumans() }}</time>
                            </div>

                            <h2>{{ $item['title'] }}</h2>
                            <p class="dg-feed-card__summary">{{ $item['summary'] }}</p>

                            @if($item['context'])
                                <p class="dg-context">{{ $item['context'] }}</p>
                            @endif

                            <footer class="dg-card-footer">
                                <span class="dg-source">Information issue d’un événement métier DG Afrique</span>
                                <div class="dg-actions">
                                    <a class="dg-action-link dg-action-link--secondary" href="{{ $item['comment_url'] }}">Contribuer</a>
                                    <a class="dg-action-link dg-action-link--primary" href="{{ $item['action_url'] }}">{{ $item['action_label'] }} →</a>
                                </div>
                            </footer>
                        </article>
                    @endforeach
                </section>

                <div class="dg-pagination">{{ $feed->links() }}</div>
                <p class="dg-feed-end">Le fil s’arrête ici. Revenez quand une nouvelle action réelle apparaît.</p>
            @endif
        </div>
    </main>
</x-layouts.portal>
