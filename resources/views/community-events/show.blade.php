{{--
    Fiche Événement — CAP-068/UIUX-003. Première interface humaine de l'Événement : elle doit
    répondre clairement à où suis-je ? qui organise ? quand ? puis-je participer ? Organisateur,
    inscription et visibilité restent entièrement décidés par CommunityEventService — cette page
    ne fait qu'afficher ce que le service autorise déjà.
--}}
@php
    $statusLabels = [
        \App\Models\CommunityEvent::STATUS_SCHEDULED => 'Programmé',
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
        <div class="dg-page" style="max-width:820px">
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
            </div>
        </div>
    </x-dg.shell>
</x-layouts.portal>
