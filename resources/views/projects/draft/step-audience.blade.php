{{--
    PROJET-ZUMRA-INVARIANT-001 — tout Projet appartient toujours à une ZUMRA. Cette étape ne
    propose jamais « pour moi-même » comme alternative à une ZUMRA : l'ancrage est mandatoire au
    niveau serveur (ProjectService::create()) ; ce que la personne choisit ici, c'est la ZUMRA et,
    séparément, qui décide en son sein — jamais l'un à la place de l'autre.
--}}
<x-layouts.portal title="Dans quelle ZUMRA ? — DG Afrique">
    <x-dg.shell current="projets" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:640px">
            @include('projects.draft._header', ['step' => 'audience'])

            <h1 class="dg-display dg-display--lead">Dans quelle ZUMRA ce projet va-t-il grandir ?</h1>
            <p class="dg-body" style="margin-top:8px">Un projet appartient toujours à une ZUMRA — même porté par vous seul·e. Une ZUMRA peut commencer avec une seule personne.</p>

            <form method="POST" action="{{ route('projects.draft.update', [$draft, 'audience']) }}" style="margin-top:26px;display:flex;flex-direction:column;gap:18px">
                @csrf

                @if($groups->isNotEmpty())
                    <div class="dg-field">
                        <label>Votre ZUMRA</label>
                        @foreach($groups as $group)
                            <label class="dg-radio" style="padding:16px">
                                <input type="radio" name="zumra_group_reference" value="{{ $group->public_reference }}" required @checked(old('zumra_group_reference', $payload['zumra_group_reference'] ?? null) === $group->public_reference)>
                                <span><strong>{{ $group->name }}</strong></span>
                            </label>
                        @endforeach
                    </div>

                    <div class="dg-field">
                        <label>Qui décide pour ce projet ?</label>
                        <label class="dg-radio" style="padding:16px">
                            <input type="radio" name="owner_type" value="PERSON" required @checked(old('owner_type', $payload['owner_type'] ?? 'PERSON') === 'PERSON')>
                            <span><strong>Vous seul·e</strong><br><span class="dg-meta">Comme initiateur·rice, au sein de cette ZUMRA.</span></span>
                        </label>
                        <label class="dg-radio" style="padding:16px">
                            <input type="radio" name="owner_type" value="GROUP" required @checked(old('owner_type', $payload['owner_type'] ?? null) === 'GROUP')>
                            <span><strong>La ZUMRA collectivement</strong><br><span class="dg-meta">Un·e responsable devra l’adopter avant son démarrage.</span></span>
                        </label>
                    </div>

                    <a href="{{ route('projects.draft.zumra.create', $draft) }}" class="dg-meta" style="color:var(--dg-copper)">+ Commencer une autre ZUMRA</a>

                    @include('projects.draft._footer', ['continueLabel' => 'Continuer →'])
                @else
                    <div class="dg-band">
                        <strong style="display:block;color:var(--dg-forest);margin-bottom:4px">Vous n’êtes pas encore membre d’une ZUMRA</strong>
                        Ce n’est pas un obstacle : une ZUMRA peut commencer avec vous seul·e, sans attendre de réunir un collectif au préalable.
                    </div>

                    <x-dg.btn variant="project" :href="route('projects.draft.zumra.create', $draft)">Commencer ma ZUMRA maintenant →</x-dg.btn>

                    <div class="dg-actions" style="margin-top:10px">
                        <button type="submit" name="_intent" value="save_later" formnovalidate class="dg-btn dg-btn--quiet">Enregistrer et continuer plus tard</button>
                    </div>
                @endif
            </form>

            @include('projects.draft._abandon')
        </div>
    </x-dg.shell>
</x-layouts.portal>
