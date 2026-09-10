<x-layouts.member :title="'Événements · '.$group->name" active="zumra" :wide="true">
    <div class="dg-event-space">
        <header class="dg-event-space__hero">
            <div>
                <a class="dg-event-space__back" href="{{ route('zumra.groups.show', $group) }}">← Retour à {{ $group->name }}</a>
                <p class="dg-event-space__eyebrow">ÉVÉNEMENTS ZUMRA</p>
                <h1>Se retrouver pour apprendre et agir.</h1>
                <p>Les rencontres, ateliers et activités programmées par {{ $group->name }}. Chaque événement reste un objet CAP-068 réel, distinct d’une mission ou d’un journal technique.</p>
            </div>
            @if ($isLeader)
                <a class="dg-event-button dg-event-button--primary" href="{{ route('community-events.zumra.create', $group) }}">+ Créer un événement</a>
            @endif
        </header>

        <nav class="dg-event-tabs" aria-label="Navigation dans la ZUMRA">
            <a href="{{ route('zumra.groups.show', $group) }}">⌂ Accueil</a>
            <a href="{{ route('zumra.groups.formation', $group) }}">◈ Formation</a>
            <a href="{{ route('zumra.groups.discussion', $group) }}">▢ Discussion</a>
            <a class="is-active" href="{{ route('community-events.zumra.index', $group) }}">▣ Événements</a>
        </nav>

        @if (session('status'))
            <div class="dg-event-status" role="status">{{ session('status') }}</div>
        @endif

        <section class="dg-event-space__section" aria-labelledby="events-title">
            <div class="dg-event-space__section-head">
                <div>
                    <p class="dg-event-space__eyebrow">AGENDA RÉEL</p>
                    <h2 id="events-title">Les événements de la ZUMRA</h2>
                </div>
                <span>{{ $events->count() }} événement{{ $events->count() === 1 ? '' : 's' }}</span>
            </div>

            @forelse ($events as $event)
                @php
                    $statusLabel = match ($event->status) {
                        \App\Models\CommunityEvent::STATUS_COMPLETED => 'Tenu',
                        \App\Models\CommunityEvent::STATUS_CANCELLED => 'Annulé',
                        default => 'À venir',
                    };
                    $isRegistered = in_array($event->id, $registeredEventIds, true);
                @endphp
                <article class="dg-event-card">
                    <div class="dg-event-card__date" aria-hidden="true">
                        <strong>{{ $event->scheduled_at->format('d') }}</strong>
                        <span>{{ mb_strtoupper($event->scheduled_at->translatedFormat('M')) }}</span>
                    </div>
                    <div class="dg-event-card__body">
                        <div class="dg-event-card__topline">
                            <span class="dg-event-pill">{{ $statusLabel }}</span>
                            <span>{{ $event->visibility === \App\Models\CommunityEvent::VISIBILITY_PUBLIC ? 'Public' : 'Membres de la ZUMRA' }}</span>
                        </div>
                        <h3><a href="{{ route('community-events.show', $event) }}">{{ $event->title }}</a></h3>
                        <p>{{ $event->description }}</p>
                        <div class="dg-event-card__meta">
                            <span>◷ {{ $event->scheduled_at->translatedFormat('d F Y · H:i') }}</span>
                            @if ($event->location)<span>⌖ {{ $event->location }}</span>@endif
                            @if ($isRegistered)<span>✓ Vous êtes inscrit</span>@endif
                        </div>
                    </div>
                    <a class="dg-event-button" href="{{ route('community-events.show', $event) }}">Voir l’événement</a>
                </article>
            @empty
                <div class="dg-event-empty">
                    <span aria-hidden="true">▣</span>
                    <h3>Aucun événement programmé</h3>
                    <p>Les événements réels de cette ZUMRA apparaîtront ici dès qu’ils seront créés.</p>
                    @if ($isLeader)
                        <a class="dg-event-button dg-event-button--primary" href="{{ route('community-events.zumra.create', $group) }}">Créer le premier événement</a>
                    @endif
                </div>
            @endforelse
        </section>
    </div>
</x-layouts.member>
