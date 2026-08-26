{{--
    Fiche Événement — CAP-068/UIUX-003/UIUX-004. Première interface humaine de l'Événement, puis
    parcours organisateur complet : elle doit répondre clairement à où suis-je ? qui organise ?
    quand ? puis-je participer ? et, pour l'organisateur réel, que puis-je gérer ? Organisateur,
    inscription, visibilité et transitions restent entièrement décidés par CommunityEventService —
    cette page ne fait qu'afficher ce que le service autorise déjà.
--}}
@php
    $statusLabels = [
        \App\Models\CommunityEvent::STATUS_SCHEDULED => 'À venir',
        \App\Models\CommunityEvent::STATUS_COMPLETED => 'Tenu',
        \App\Models\CommunityEvent::STATUS_CANCELLED => 'Annulé',
    ];
    $statusTones = [
        \App\Models\CommunityEvent::STATUS_SCHEDULED => 'saffron',
        \App\Models\CommunityEvent::STATUS_COMPLETED => 'forest',
        \App\Models\CommunityEvent::STATUS_CANCELLED => 'neutral',
    ];
@endphp
<x-layouts.portal title="{{ $event->title }} — DG Afrique">
    <x-dg.shell :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page zd-page zd-event-page" style="max-width:1360px">
            @if($organizerUrl)
                <a href="{{ $organizerUrl }}" class="dg-crumb">← {{ $organizerLabel }}</a>
            @else
                <a href="{{ route('member.space') }}" class="dg-crumb">← Mon espace</a>
            @endif

            @if(session('status'))
                <div class="dg-band" style="margin-bottom:20px">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="dg-band" style="margin-bottom:20px;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>
            @endif

            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="saffron">Événement</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">{{ $event->title }}</h1>
                    <p>
                        Organisé par
                        @if($organizerUrl)
                            <a href="{{ $organizerUrl }}" style="color:var(--dg-copper);font-weight:600">{{ $organizerLabel }}</a>
                        @else
                            {{ $organizerLabel ?? 'un organisateur inconnu' }}
                        @endif
                    </p>
                </div>
                <x-dg.badge :tone="$statusTones[$event->status] ?? 'neutral'">{{ $statusLabels[$event->status] ?? $event->status }}</x-dg.badge>
            </div>

            <div style="display:flex;flex-direction:column;gap:16px">
                <x-dg.card>
                    <x-dg.label>Description</x-dg.label>
                    <p class="dg-body" style="margin-top:12px;white-space:pre-line">{{ $event->description }}</p>
                </x-dg.card>

                <x-dg.card tight>
                    <dl class="dg-dl">
                        <div><dt>Date</dt><dd>{{ $event->scheduled_at->translatedFormat('d F Y à H:i') }}</dd></div>
                        @if($event->location)<div><dt>Lieu</dt><dd>{{ $event->location }}</dd></div>@endif
                        <div><dt>Visibilité</dt><dd>{{ $event->visibility === \App\Models\CommunityEvent::VISIBILITY_PUBLIC ? 'Publique' : 'Interne' }}</dd></div>
                        @if($event->status === \App\Models\CommunityEvent::STATUS_CANCELLED && $event->cancelled_at)
                            <div><dt>Annulé le</dt><dd>{{ $event->cancelled_at->translatedFormat('d F Y') }}</dd></div>
                        @endif
                        @if($event->status === \App\Models\CommunityEvent::STATUS_COMPLETED && $event->completed_at)
                            <div><dt>Tenu le</dt><dd>{{ $event->completed_at->translatedFormat('d F Y') }}</dd></div>
                        @endif
                        @if($isOrganizerAuthority)
                            <div><dt>Inscriptions</dt><dd>{{ $participantsCount }} inscrit{{ $participantsCount > 1 ? 's' : '' }}</dd></div>
                        @endif
                    </dl>
                </x-dg.card>

                <x-dg.card>
                    <x-dg.label>Participation</x-dg.label>
                    @if($canParticipate)
                        @if($isRegistered)
                            <p class="dg-body" style="margin-top:10px">Vous êtes inscrit·e à cet événement.</p>
                            <form method="POST" action="{{ route('community-events.unregister', $event) }}" style="margin-top:10px">
                                @csrf
                                <button type="submit" class="dg-btn dg-btn--quiet">Me désinscrire</button>
                            </form>
                        @else
                            <p class="dg-body" style="margin-top:10px">Cet événement est ouvert à l'inscription.</p>
                            <form method="POST" action="{{ route('community-events.register', $event) }}" style="margin-top:10px">
                                @csrf
                                <button type="submit" class="dg-btn dg-btn--primary">M'inscrire</button>
                            </form>
                        @endif
                    @else
                        <x-dg.empty><span>{{ $event->status === \App\Models\CommunityEvent::STATUS_CANCELLED ? 'Cet événement est annulé.' : 'Cet événement est terminé.' }}</span></x-dg.empty>
                    @endif
                </x-dg.card>

                @if($isOrganizerAuthority && $event->status === \App\Models\CommunityEvent::STATUS_SCHEDULED)
                    {{-- UIUX-004 — parcours organisateur : les trois transitions déjà existantes
                         (modifier/annuler/marquer tenu), rien de plus. Visible uniquement pour
                         l'autorité réelle de l'organisateur (isLeader()/isManager()), jamais
                         recalculée ici. --}}
                    <x-dg.card>
                        <x-dg.label>Vous organisez cet événement</x-dg.label>
                        <p class="dg-hint" style="margin-top:8px">Ces actions ne sont visibles que par vous, en tant qu'organisateur.</p>

                        <form method="POST" action="{{ route('community-events.complete', $event) }}" style="margin-top:14px" onsubmit="return confirm('Marquer cet événement comme tenu ? Cette action est définitive.');">
                            @csrf
                            <button type="submit" class="dg-btn dg-btn--primary">Marquer comme tenu</button>
                        </form>

                        <details style="margin-top:14px" @if($errors->any()) open @endif>
                            <summary style="cursor:pointer;font-size:13px;font-weight:600;color:var(--dg-copper)">Modifier les informations</summary>
                            <form method="POST" action="{{ route('community-events.update', $event) }}" style="margin-top:12px;display:flex;flex-direction:column;gap:10px">
                                @csrf
                                @method('PATCH')
                                <div class="dg-field">
                                    <label for="event-edit-title">Titre</label>
                                    <input type="text" id="event-edit-title" name="title" class="dg-input" minlength="3" maxlength="160" required value="{{ old('title', $event->title) }}">
                                </div>
                                <div class="dg-field">
                                    <label for="event-edit-description">Description</label>
                                    <textarea id="event-edit-description" name="description" class="dg-textarea" rows="4" minlength="10" maxlength="4000" required>{{ old('description', $event->description) }}</textarea>
                                </div>
                                <div class="dg-field">
                                    <label for="event-edit-location">Lieu (facultatif)</label>
                                    <input type="text" id="event-edit-location" name="location" class="dg-input" maxlength="160" value="{{ old('location', $event->location) }}">
                                </div>
                                <div class="dg-field">
                                    <label for="event-edit-visibility">Visibilité</label>
                                    <select id="event-edit-visibility" name="visibility" class="dg-select" required>
                                        <option value="INTERNAL" @if(old('visibility', $event->visibility) === 'INTERNAL') selected @endif>Interne</option>
                                        <option value="PUBLIC" @if(old('visibility', $event->visibility) === 'PUBLIC') selected @endif>Publique</option>
                                    </select>
                                </div>
                                <div class="dg-field">
                                    <label for="event-edit-scheduled-at">Date et heure</label>
                                    <input type="datetime-local" id="event-edit-scheduled-at" name="scheduled_at" class="dg-input" required value="{{ old('scheduled_at', $event->scheduled_at->format('Y-m-d\TH:i')) }}">
                                </div>
                                <button type="submit" class="dg-btn dg-btn--quiet" style="align-self:flex-start">Enregistrer les modifications</button>
                            </form>
                        </details>

                        <details style="margin-top:14px">
                            <summary style="cursor:pointer;font-size:13px;font-weight:600;color:var(--dg-muted)">Annuler l'événement</summary>
                            <form method="POST" action="{{ route('community-events.cancel', $event) }}" style="margin-top:12px;display:flex;flex-direction:column;gap:10px" onsubmit="return confirm('Annuler cet événement ? Les personnes inscrites ne pourront plus y participer.');">
                                @csrf
                                <textarea name="note" class="dg-textarea" rows="3" placeholder="Raison de l'annulation (facultatif)" maxlength="800"></textarea>
                                <button type="submit" class="dg-btn dg-btn--quiet" style="align-self:flex-start">Annuler l'événement</button>
                            </form>
                        </details>
                    </x-dg.card>
                @endif
            </div>
        </div>
    </x-dg.shell>
</x-layouts.portal>
