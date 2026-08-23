{{-- UIUX-009B — étape : où et comment. Le lieu n'apparaît que si le mode n'est pas « Numérique ». --}}
<x-layouts.portal title="Où et comment cela se passera-t-il ? — DG Afrique">
    <x-dg.shell current="projets" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:640px">
            @include('projects.draft._header', ['step' => 'logistique'])

            <h1 class="dg-display dg-display--lead">Où et comment cela se passera-t-il ?</h1>

            <form method="POST" action="{{ route('projects.draft.update', [$draft, 'logistique']) }}" style="margin-top:26px;display:flex;flex-direction:column;gap:20px">
                @csrf

                <div class="dg-field">
                    <label for="domain">Quel domaine correspond le mieux ?</label>
                    <select name="domain" id="domain" class="dg-select" required>
                        <option value="">Choisir</option>
                        @foreach($config['domains'] as $code => $label)
                            <option value="{{ $code }}" @selected(old('domain', $payload['domain'] ?? null) === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="dg-field">
                    <label>Comment cela se déroule-t-il principalement ?</label>
                    <div class="dg-radio-group" id="dg-participation-mode">
                        <label class="dg-radio">
                            <input type="radio" name="participation_mode" value="PHYSICAL" required @checked(old('participation_mode', $payload['participation_mode'] ?? 'PHYSICAL') === 'PHYSICAL')>
                            Sur place
                        </label>
                        <label class="dg-radio">
                            <input type="radio" name="participation_mode" value="DIGITAL" required @checked(old('participation_mode', $payload['participation_mode'] ?? null) === 'DIGITAL')>
                            En ligne
                        </label>
                        <label class="dg-radio">
                            <input type="radio" name="participation_mode" value="HYBRID" required @checked(old('participation_mode', $payload['participation_mode'] ?? null) === 'HYBRID')>
                            Les deux
                        </label>
                    </div>
                </div>

                <div class="dg-field" id="dg-location-field">
                    <label for="location">Où, précisément ?</label>
                    <input type="text" name="location" id="location" class="dg-input" value="{{ old('location', $payload['location'] ?? '') }}" placeholder="Ex. Bouaké, Côte d’Ivoire">
                </div>

                @include('projects.draft._footer')
            </form>

            @include('projects.draft._abandon')
        </div>
    </x-dg.shell>

    <script>
        (function () {
            var radios = document.querySelectorAll('#dg-participation-mode input[name="participation_mode"]');
            var locationField = document.getElementById('dg-location-field');
            function sync() {
                var checked = document.querySelector('#dg-participation-mode input[name="participation_mode"]:checked');
                locationField.style.display = (checked && checked.value === 'DIGITAL') ? 'none' : '';
            }
            radios.forEach(function (r) { r.addEventListener('change', sync); });
            sync();
        })();
    </script>
</x-layouts.portal>
