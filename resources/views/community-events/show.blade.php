<x-layouts.member :title="$event->title" active="zumra" :wide="true">
    @php
        $statusLabel = match ($event->status) {
            \App\Models\CommunityEvent::STATUS_COMPLETED => 'Tenu',
            \App\Models\CommunityEvent::STATUS_CANCELLED => 'Annulé',
            default => 'À venir',
        };
        $isScheduled = $event->status === \App\Models\CommunityEvent::STATUS_SCHEDULED;
    @endphp

    <div class="dg-event-space dg-event-space--detail">
        <header class="dg-event-detail__hero">
            @if ($organizerUrl)
                <a class="dg-event-space__back" href="{{ $organizerUrl }}">← Retour à {{ $organizerLabel }}</a>
            @endif
            <div class="dg-event-detail__headline">
                <div>
                    <p class="dg-event-space__eyebrow">ÉVÉNEMENT · {{ mb_strtoupper($statusLabel) }}</p>
                    <h1>{{ $event->title }}</h1>
                    <p>{{ $event->description }}</p>
                </div>
                <div class="dg-event-detail__date">
                    <strong>{{ $event->scheduled_at->format('d') }}</strong>
                    <span>{{ mb_strtoupper($event->scheduled_at->translatedFormat('M Y')) }}</span>
                    <small>{{ $event->scheduled_at->format('H:i') }}</small>
                </div>
            </div>
        </header>

        @if (session('status'))
            <div class="dg-event-status" role="status">{{ session('status') }}</div>
        @endif

        <div class="dg-event-detail__grid">
            <main class="dg-event-panel">
                <h2>Informations</h2>
                <dl class="dg-event-facts">
                    <div><dt>Organisé par</dt><dd>{{ $organizerLabel ?: 'Communauté GAMAD' }}</dd></div>
                    <div><dt>Date</dt><dd>{{ $event->scheduled_at->translatedFormat('d F Y à H:i') }}</dd></div>
                    <div><dt>Lieu</dt><dd>{{ $event->location ?: 'À préciser' }}</dd></div>
                    <div><dt>Visibilité</dt><dd>{{ $event->visibility === \App\Models\CommunityEvent::VISIBILITY_PUBLIC ? 'Public' : 'Membres de la communauté' }}</dd></div>
                    <div><dt>État</dt><dd>{{ $statusLabel }}</dd></div>
                </dl>

                @if ($canParticipate)
                    <section class="dg-event-participation">
                        @if ($isRegistered)
                            <strong>✓ Vous êtes inscrit</strong>
                            <p>Votre participation à cet événement est enregistrée.</p>
                            <form method="POST" action="{{ route('community-events.unregister', $event) }}">@csrf<button class="dg-event-button" type="submit">Me désinscrire</button></form>
                        @else
                            <strong>Cet événement est ouvert à l'inscription</strong>
                            <p>Inscrivez-vous si vous prévoyez d’y participer.</p>
                            <form method="POST" action="{{ route('community-events.register', $event) }}">@csrf<button class="dg-event-button dg-event-button--primary" type="submit">M'inscrire</button></form>
                        @endif
                    </section>
                @else
                    <p class="dg-event-terminal">Cet événement est terminé ou n’accepte plus d’inscription.</p>
                @endif
            </main>

            @if ($isOrganizerAuthority && $isScheduled)
                <aside class="dg-event-panel dg-event-panel--manage">
                    <p class="dg-event-space__eyebrow">ORGANISATION</p>
                    <h2>Vous organisez cet événement</h2>
                    @if ($participantsCount !== null)<p><strong>{{ $participantsCount }}</strong> inscrit{{ $participantsCount === 1 ? '' : 's' }}</p>@endif

                    <details>
                        <summary>Modifier les informations</summary>
                        <form class="dg-event-form dg-event-form--compact" method="POST" action="{{ route('community-events.update', $event) }}">
                            @csrf @method('PATCH')
                            <label><span>Titre</span><input name="title" minlength="3" maxlength="160" required value="{{ old('title', $event->title) }}"></label>
                            <label><span>Description</span><textarea name="description" rows="5" minlength="10" maxlength="4000" required>{{ old('description', $event->description) }}</textarea></label>
                            <label><span>Date et heure</span><input type="datetime-local" name="scheduled_at" required value="{{ old('scheduled_at', $event->scheduled_at->format('Y-m-d\TH:i')) }}"></label>
                            <label><span>Lieu</span><input name="location" maxlength="160" value="{{ old('location', $event->location) }}"></label>
                            <label><span>Visibilité</span><select name="visibility"><option value="INTERNAL" {{ $event->visibility === 'INTERNAL' ? 'selected' : '' }}>Membres</option><option value="PUBLIC" {{ $event->visibility === 'PUBLIC' ? 'selected' : '' }}>Public</option></select></label>
                            <button class="dg-event-button dg-event-button--primary" type="submit">Enregistrer</button>
                        </form>
                    </details>

                    <form method="POST" action="{{ route('community-events.complete', $event) }}">@csrf<button class="dg-event-button dg-event-button--primary" type="submit">Marquer comme tenu</button></form>
                    <form method="POST" action="{{ route('community-events.cancel', $event) }}">@csrf<label><span>Motif éventuel</span><textarea name="note" rows="2" maxlength="800"></textarea></label><button class="dg-event-button" type="submit">Annuler l'événement</button></form>
                </aside>
            @endif
        </div>
    </div>
</x-layouts.member>
