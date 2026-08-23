{{--
    Nom du projet — et rattachement facultatif à un Besoin d'origine (restauré depuis l'ancien
    formulaire unique, jamais rendu obligatoire : le métier ne l'exige pas).
--}}
<x-layouts.portal title="Comment voulez-vous appeler ce projet ? — DG Afrique">
    <x-dg.shell current="projets" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:640px">
            @include('projects.draft._header', ['step' => 'nom'])

            <h1 class="dg-display dg-display--lead">Comment voulez-vous appeler ce projet ?</h1>
            <p class="dg-body" style="margin-top:8px">Un nom simple suffit — vous pourrez toujours l’ajuster plus tard.</p>

            <form method="POST" action="{{ route('projects.draft.update', [$draft, 'nom']) }}" style="margin-top:26px">
                @csrf

                <div class="dg-field">
                    <label for="name" class="dg-sr-only" style="position:absolute;width:1px;height:1px;overflow:hidden">Comment voulez-vous appeler ce projet ?</label>
                    <input type="text" name="name" id="name" class="dg-input" style="font-size:17px;padding:16px"
                           value="{{ old('name', $payload['name'] ?? '') }}"
                           placeholder="Ex. Bibliothèque solidaire" minlength="5" maxlength="180" required autofocus>
                </div>

                @if($needs->isNotEmpty())
                    <div class="dg-field" style="margin-top:18px">
                        <label for="source_need_reference">Ce projet vient-il d’un besoin que vous avez exprimé ? (facultatif)</label>
                        <select name="source_need_reference" id="source_need_reference" class="dg-select">
                            <option value="">Non, aucun</option>
                            @foreach($needs as $need)
                                <option value="{{ $need->public_reference }}" @selected(old('source_need_reference', $payload['source_need_reference'] ?? null) === $need->public_reference)>{{ $need->title }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @include('projects.draft._footer')
            </form>

            @include('projects.draft._abandon')
        </div>
    </x-dg.shell>
</x-layouts.portal>
