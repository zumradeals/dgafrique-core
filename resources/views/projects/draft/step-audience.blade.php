{{-- UIUX-009B — étape 1 : pour qui est ce projet ? --}}
<x-layouts.portal title="Pour qui est ce projet ? — DG Afrique">
    <x-dg.shell current="projets" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:640px">
            @include('projects.draft._header', ['step' => 'audience'])

            <h1 class="dg-display dg-display--lead">Pour qui est ce projet ?</h1>
            <p class="dg-body" style="margin-top:8px">Cela change simplement qui pourra ensuite le décider et le faire avancer.</p>

            <form method="POST" action="{{ route('projects.draft.update', [$draft, 'audience']) }}" style="margin-top:26px;display:flex;flex-direction:column;gap:18px">
                @csrf

                <label class="dg-radio" style="padding:16px">
                    <input type="radio" name="owner_type" value="PERSON" required @checked(old('owner_type', $payload['owner_type'] ?? 'PERSON') === 'PERSON')>
                    <span><strong>Pour moi</strong><br><span class="dg-meta">Vous restez seul·e décisionnaire de ce projet.</span></span>
                </label>

                @if($groups->isNotEmpty())
                    <label class="dg-radio" style="padding:16px">
                        <input type="radio" name="owner_type" value="GROUP" required @checked(old('owner_type', $payload['owner_type'] ?? null) === 'GROUP')>
                        <span><strong>Pour ma ZUMRA</strong><br><span class="dg-meta">Les responsables de la ZUMRA choisie pourront le décider.</span></span>
                    </label>

                    <div class="dg-field">
                        <label for="group_reference">Quelle ZUMRA ?</label>
                        <select name="group_reference" id="group_reference" class="dg-select">
                            <option value="">Choisir</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->public_reference }}" @selected(old('group_reference', $payload['group_reference'] ?? null) === $group->public_reference)>{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="dg-band" style="opacity:.85">
                        <strong style="display:block;color:var(--dg-forest);margin-bottom:4px">« Pour ma ZUMRA » n’est pas proposé ici</strong>
                        Vous n’êtes membre actif d’aucune ZUMRA pour le moment. Vous pouvez proposer ce projet pour vous-même, ou <a href="{{ route('zumra.index') }}">rejoindre une ZUMRA</a> d’abord.
                    </div>
                @endif

                @include('projects.draft._footer', ['continueLabel' => 'Continuer →'])
            </form>

            @include('projects.draft._abandon')
        </div>
    </x-dg.shell>
</x-layouts.portal>
