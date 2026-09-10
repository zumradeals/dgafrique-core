<x-layouts.member title="Proposer une Transmission" active="zumra" :wide="true">
@php
    $requestedContext = (string) request()->query('context', '');
    $validatedContext = collect($contextOptions)->first(function (array $option) use ($requestedContext): bool {
        return $requestedContext !== '' && ($option['type'].'|'.$option['reference']) === $requestedContext;
    });
    $selectedContext = old('context_choice', $validatedContext ? $validatedContext['type'].'|'.$validatedContext['reference'] : '');
    $requestedRole = (string) request()->query('role', \App\Models\TransmissionParticipant::ROLE_LEARNER);
    $selectedRole = old('initiator_role', in_array($requestedRole, \App\Models\TransmissionParticipant::ROLES, true) ? $requestedRole : \App\Models\TransmissionParticipant::ROLE_LEARNER);
    $originType = $validatedContext && in_array($validatedContext['type'], \App\Models\Transmission::ORIGIN_TYPES, true)
        ? $validatedContext['type']
        : \App\Models\Transmission::ORIGIN_NONE;
@endphp

<div class="dg-transmission-create">
    @if ($validatedContext && $validatedContext['type'] === \App\Models\Transmission::CONTEXT_ZUMRA)
        <a class="dg-transmission-create__back" href="{{ route('zumra.groups.show', $validatedContext['reference']) }}">← Retour à la ZUMRA</a>
    @else
        <a class="dg-transmission-create__back" href="{{ route('transmissions.index') }}">← Mes Transmissions</a>
    @endif

    <section class="dg-transmission-create__hero">
        <div>
            <p class="dg-formation__eyebrow">APPRENDRE · PRATIQUER · TRANSMETTRE</p>
            <h1>Faire circuler un savoir.</h1>
            <p>Une Transmission relie des personnes autour d’une capacité à apprendre ou à transmettre. Elle repose sur un objectif réel et sur l’acceptation explicite de chaque participant.</p>
        </div>
        <div class="dg-transmission-create__context">
            <small>CONTEXTE</small>
            <strong>{{ $validatedContext['label'] ?? 'Transmission personnelle' }}</strong>
            <span>{{ $validatedContext ? 'Cette Transmission restera rattachée à ce contexte.' : 'Vous pouvez choisir un contexte auquel vous avez réellement accès.' }}</span>
        </div>
    </section>

    <div class="dg-transmission-create__layout">
        <main class="dg-transmission-create__card">
            <form class="dg-transmission-create__form" method="POST" action="{{ route('transmissions.store') }}">
                @csrf
                <input type="hidden" name="origin_type" value="{{ $originType }}">

                @if ($validatedContext)
                    <input type="hidden" name="context_choice" value="{{ $selectedContext }}">
                @else
                    <div class="dg-transmission-create__field">
                        <label for="context_choice">Où cette Transmission se déroule-t-elle ?</label>
                        <select id="context_choice" name="context_choice">
                            <option value="">Sans contexte particulier</option>
                            @foreach ($contextOptions as $option)
                                @php($value = $option['type'].'|'.$option['reference'])
                                <option value="{{ $value }}" @selected($selectedContext === $value)>{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <fieldset class="dg-transmission-create__field">
                    <legend>Quel rôle prenez-vous au départ ?</legend>
                    <div class="dg-transmission-create__roles">
                        <label class="dg-transmission-create__role">
                            <input type="radio" name="initiator_role" value="{{ \App\Models\TransmissionParticipant::ROLE_LEARNER }}" @checked($selectedRole === \App\Models\TransmissionParticipant::ROLE_LEARNER)>
                            <strong>Je veux apprendre</strong>
                            <small>Vous exprimez une capacité que vous souhaitez acquérir ou approfondir.</small>
                        </label>
                        <label class="dg-transmission-create__role">
                            <input type="radio" name="initiator_role" value="{{ \App\Models\TransmissionParticipant::ROLE_TRANSMITTER }}" @checked($selectedRole === \App\Models\TransmissionParticipant::ROLE_TRANSMITTER)>
                            <strong>Je peux transmettre</strong>
                            <small>Vous proposez d’accompagner d’autres personnes autour d’un savoir ou savoir-faire réel.</small>
                        </label>
                    </div>
                </fieldset>

                <div class="dg-transmission-create__field">
                    <label for="capability_label">Quel savoir ou savoir-faire ?</label>
                    <input id="capability_label" name="capability_label" value="{{ old('capability_label') }}" minlength="2" maxlength="200" required placeholder="Ex. Développement web, gestion de projet, comptabilité…">
                    @error('capability_label')<small>{{ $message }}</small>@enderror
                </div>

                <div class="dg-transmission-create__field">
                    <label for="learning_objective">Quel est l’objectif concret de cet apprentissage ?</label>
                    <textarea id="learning_objective" name="learning_objective" rows="5" minlength="10" maxlength="2000" required placeholder="Expliquez ce que l’apprenant devrait comprendre, savoir faire ou pouvoir mettre en pratique.">{{ old('learning_objective') }}</textarea>
                    @error('learning_objective')<small>{{ $message }}</small>@enderror
                </div>

                <div class="dg-transmission-create__field">
                    <label for="availability_note">Disponibilité ou précision utile</label>
                    <input id="availability_note" name="availability_note" value="{{ old('availability_note') }}" maxlength="300" placeholder="Facultatif : jours, rythme, modalité…">
                </div>

                <div class="dg-transmission-create__field">
                    <label for="visibility">Qui peut voir cette Transmission ?</label>
                    <select id="visibility" name="visibility" required>
                        @foreach (\App\Models\Transmission::VISIBILITY_LABELS as $value => $label)
                            @php($defaultVisibility = $validatedContext ? \App\Models\Transmission::VISIBILITY_CONTEXT : \App\Models\Transmission::VISIBILITY_PRIVATE)
                            <option value="{{ $value }}" @selected(old('visibility', $defaultVisibility) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="dg-transmission-create__note">Une Transmission n’est jamais publique sur tout le réseau dans cette version. Les règles de visibilité et du contexte restent appliquées par le moteur.</p>
                </div>

                @if ($prefillInvite)
                    <input type="hidden" name="invite_discovery_reference" value="{{ $prefillInvite->discovery_reference }}">
                    <div class="dg-transmission-create__field"><strong>Invitation prévue : {{ $prefillInvite->discovery_display_name }}</strong><p class="dg-transmission-create__note">Cette personne devra accepter explicitement l’invitation avant de participer.</p></div>
                @endif

                <div><button class="dg-formation__button dg-formation__button--solar" type="submit">Proposer la Transmission</button></div>
            </form>
        </main>

        <aside class="dg-transmission-create__aside">
            <section class="dg-transmission-create__card">
                <strong>Une Transmission n’est pas un cours fictif.</strong>
                <p>Elle devient réelle quand des personnes acceptent d’apprendre et de transmettre ensemble autour d’un objectif clair.</p>
            </section>
            <section class="dg-transmission-create__card">
                <strong>Le cycle GAMAD</strong>
                <ol><li>Apprendre</li><li>Pratiquer dans l’action</li><li>Transmettre à son tour</li></ol>
            </section>
            <section class="dg-transmission-create__card">
                <strong>Traçabilité</strong>
                <p class="dg-transmission-create__note">Jalons, contributions et clôture restent gérés par le moteur Transmission existant. Aucune réussite n’est déclarée automatiquement.</p>
            </section>
        </aside>
    </div>
</div>
</x-layouts.member>
