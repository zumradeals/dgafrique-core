<x-layouts.member :title="'Formation · '.$group->name" active="zumra" :wide="true">
@php
    $contextChoice = \App\Models\Transmission::CONTEXT_ZUMRA.'|'.$group->public_reference;
    $learnUrl = route('transmissions.create', ['context' => $contextChoice, 'role' => \App\Models\TransmissionParticipant::ROLE_LEARNER]);
    $transmitUrl = route('transmissions.create', ['context' => $contextChoice, 'role' => \App\Models\TransmissionParticipant::ROLE_TRANSMITTER]);
@endphp

<div class="dg-formation">
    <a class="dg-formation__back" href="{{ route('zumra.groups.show', $group) }}">← Retour à {{ $group->name }}</a>

    <section class="dg-formation__hero" aria-labelledby="formation-title">
        <div class="dg-formation__hero-copy">
            <p class="dg-formation__eyebrow">FORMATION · TRAVAIL · ADORATION</p>
            <span class="dg-formation__world">ZUMRA · {{ $group->name }}</span>
            <h1 id="formation-title">Apprendre. Pratiquer. Transmettre.</h1>
            <p>La première mission d’une ZUMRA est de faire grandir ses membres. Ici, chacun peut apprendre, développer une capacité, la mettre en pratique dans l’action puis transmettre à son tour.</p>
            @if ($isActiveMember || $isLeader)
                <div class="dg-formation__hero-actions">
                    <a class="dg-formation__button dg-formation__button--solar" href="{{ $learnUrl }}">Je veux apprendre</a>
                    <a class="dg-formation__button" href="{{ $transmitUrl }}">Je peux transmettre</a>
                </div>
            @endif
        </div>
        <div class="dg-formation__cycle" aria-label="Cycle de formation GAMAD">
            <article><span>01</span><strong>Apprendre</strong><p>Identifier ce que vous voulez comprendre ou savoir faire.</p></article>
            <article><span>02</span><strong>Pratiquer</strong><p>Mettre la capacité en mouvement dans les projets et les activités réelles.</p></article>
            <article><span>03</span><strong>Transmettre</strong><p>Partager ensuite votre expérience et faire grandir d’autres membres.</p></article>
        </div>
    </section>

    @unless ($isActiveMember || $isLeader)
        <section class="dg-formation__notice">
            <div><strong>La formation se vit dans la communauté.</strong><p>Vous pouvez comprendre la démarche de {{ $group->name }}, mais ses Transmissions restent réservées aux membres qui ont réellement accès à cette ZUMRA.</p></div>
            <a class="dg-formation__button" href="{{ route('zumra.groups.show', $group) }}">Voir la ZUMRA</a>
        </section>
    @endunless

    <div class="dg-formation__layout">
        <main class="dg-formation__main">
            <section class="dg-formation__panel dg-formation__personal">
                <div class="dg-formation__section-head">
                    <div><p class="dg-formation__eyebrow">MON APPRENTISSAGE</p><h2>Ce que je veux apprendre</h2></div>
                    <a href="{{ route('member.profile.edit') }}#learning">Modifier mon cap →</a>
                </div>
                @if ($learningGoals->isEmpty())
                    <div class="dg-formation__empty">
                        <strong>Vous n’avez pas encore indiqué ce que vous voulez apprendre.</strong>
                        <p>Déclarez vos envies d’apprentissage dans votre profil. Elles restent privées et servent à mieux vous orienter.</p>
                        <a class="dg-formation__button" href="{{ route('member.profile.edit') }}#learning">Définir ce que je veux apprendre</a>
                    </div>
                @else
                    <div class="dg-formation__chips">
                        @foreach ($learningGoals as $goal)<span>{{ $goal->label }}</span>@endforeach
                    </div>
                @endif
            </section>

            <section class="dg-formation__panel" id="transmissions">
                <div class="dg-formation__section-head">
                    <div><p class="dg-formation__eyebrow">DANS {{ mb_strtoupper($group->name) }}</p><h2>Transmissions de la ZUMRA</h2><p>Des apprentissages réels entre personnes : un savoir à travailler, un objectif clair et des participants qui acceptent explicitement leur rôle.</p></div>
                    @if ($isActiveMember || $isLeader)<a href="{{ $transmitUrl }}">Proposer une Transmission →</a>@endif
                </div>

                @if ($groupTransmissions->isEmpty())
                    <div class="dg-formation__empty dg-formation__empty--large">
                        <span>◈</span>
                        <strong>Aucune Transmission visible pour le moment.</strong>
                        <p>On ne fabrique ni cours ni progression fictive. La première Transmission apparaîtra lorsqu’un membre proposera réellement d’apprendre ou de transmettre quelque chose dans cette ZUMRA.</p>
                        @if ($isActiveMember || $isLeader)
                            <div class="dg-formation__empty-actions">
                                <a class="dg-formation__button dg-formation__button--solar" href="{{ $learnUrl }}">Demander à apprendre</a>
                                <a class="dg-formation__button" href="{{ $transmitUrl }}">Proposer de transmettre</a>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="dg-formation__transmissions">
                        @foreach ($groupTransmissions as $transmission)
                            @php($myRole = $myTransmissionParticipation[$transmission->id] ?? null)
                            <a class="dg-formation__transmission" href="{{ route('transmissions.show', $transmission) }}">
                                <div class="dg-formation__transmission-top">
                                    <span class="dg-formation__status">{{ $statusLabels[$transmission->status] ?? $transmission->status }}</span>
                                    @if ($myRole)<span>{{ $roleLabels[$myRole] ?? $myRole }}</span>@endif
                                </div>
                                <h3>{{ $transmission->capability_label }}</h3>
                                <p>{{ \Illuminate\Support\Str::limit($transmission->learning_objective, 180) }}</p>
                                <div class="dg-formation__transmission-meta">
                                    <span>{{ $transmission->participants->where('status', \App\Models\TransmissionParticipant::STATUS_ACCEPTED)->count() }} participant{{ $transmission->participants->where('status', \App\Models\TransmissionParticipant::STATUS_ACCEPTED)->count() === 1 ? '' : 's' }}</span>
                                    <span>{{ $transmission->proposed_at?->diffForHumans() }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="dg-formation__panel dg-formation__practice">
                <div class="dg-formation__section-head">
                    <div><p class="dg-formation__eyebrow">PRATIQUER</p><h2>Apprendre prend tout son sens dans l’action.</h2></div>
                </div>
                <p>Dans GAMAD, la formation n’est pas séparée du travail. Une capacité acquise peut être pratiquée dans un projet, une mission ou une contribution réelle de la ZUMRA.</p>
                <div class="dg-formation__practice-actions">
                    <a class="dg-formation__button" href="{{ route('projects.index', ['group' => $group->public_reference]) }}">Voir les projets</a>
                    <a class="dg-formation__button" href="{{ route('zumra.groups.show', $group) }}#missions">Voir les missions</a>
                </div>
            </section>
        </main>

        <aside class="dg-formation__side">
            <section class="dg-formation__panel dg-formation__situation">
                <p class="dg-formation__eyebrow">SITUATION</p>
                <h2>{{ $activeTransmissions->count() }} Transmission{{ $activeTransmissions->count() === 1 ? '' : 's' }} active{{ $activeTransmissions->count() === 1 ? '' : 's' }}</h2>
                <p>{{ $completedTransmissions->count() }} terminée{{ $completedTransmissions->count() === 1 ? '' : 's' }} et confirmée{{ $completedTransmissions->count() === 1 ? '' : 's' }} dans les éléments visibles.</p>
            </section>

            <section class="dg-formation__panel">
                <p class="dg-formation__eyebrow">CE QUE JE PEUX TRANSMETTRE</p>
                @if ($transmissionOffers->isEmpty())
                    <p>Vous n’avez encore déclaré aucun savoir que vous souhaitez transmettre.</p>
                @else
                    <div class="dg-formation__side-list">@foreach ($transmissionOffers as $offer)<span>{{ $offer->label }}</span>@endforeach</div>
                @endif
                <a class="dg-formation__text-link" href="{{ route('member.profile.edit') }}#transmission">Mettre à jour mon profil →</a>
            </section>

            <section class="dg-formation__panel dg-formation__principle">
                <p class="dg-formation__eyebrow">PRINCIPE GAMAD</p>
                <blockquote>« J’apprends aujourd’hui, je pratique avec les autres, puis je transmets à mon tour. »</blockquote>
                <p>Aucun classement, aucun score humain : la progression vient des apprentissages et des actions réellement vécus.</p>
            </section>

            @if ($isActiveMember || $isLeader)
                <section class="dg-formation__panel dg-formation__quick">
                    <p class="dg-formation__eyebrow">AGIR MAINTENANT</p>
                    <a href="{{ $learnUrl }}">◈ Demander à apprendre</a>
                    <a href="{{ $transmitUrl }}">↗ Proposer de transmettre</a>
                    <a href="{{ route('transmissions.index') }}">▤ Mes Transmissions</a>
                    <a href="{{ route('member.profile.edit') }}#learning">◎ Mes envies d’apprentissage</a>
                </section>
            @endif
        </aside>
    </div>
</div>
</x-layouts.member>
