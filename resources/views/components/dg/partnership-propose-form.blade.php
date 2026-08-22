{{--
    Formulaire « Notre organisation peut apporter… » — UIUX-005. N'apparaît que depuis un
    Besoin/Projet/ZUMRA déjà consultable, jamais dans le vide depuis la fiche Organisation.
    Réutilise strictement PartnershipService::propose() (provider_type=ORGANIZATION) : aucune
    autorité nouvelle, la vérification que l'acteur gère bien l'organisation choisie reste dans
    le service au moment de la soumission.
--}}
@props(['organizations', 'contextType', 'contextReference'])
@if($organizations->isNotEmpty())
    <x-dg.fieldset>
        <legend><x-dg.label>Notre organisation peut apporter…</x-dg.label></legend>
        <form method="POST" action="{{ route('partnerships.store') }}" style="display:flex;flex-direction:column;gap:10px">
            @csrf
            <input type="hidden" name="provider_type" value="ORGANIZATION">
            <input type="hidden" name="context_type" value="{{ $contextType }}">
            <input type="hidden" name="context_reference" value="{{ $contextReference }}">
            @if($organizations->count() > 1)
                <div class="dg-field">
                    <label for="partnership-organization">Organisation</label>
                    <select id="partnership-organization" name="organization_reference" class="dg-select" required>
                        @foreach($organizations as $organization)
                            <option value="{{ $organization->public_reference }}">{{ $organization->name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" name="organization_reference" value="{{ $organizations->first()->public_reference }}">
                <p class="dg-hint">Au nom de {{ $organizations->first()->name }}</p>
            @endif
            <div class="dg-field">
                <label for="partnership-capability">Ce que nous apportons</label>
                <input type="text" id="partnership-capability" name="capability_label" class="dg-input" maxlength="200" required placeholder="Ex. : Formation en gestion de projet">
            </div>
            <div class="dg-field">
                <label for="partnership-description">Précisions (facultatif)</label>
                <textarea id="partnership-description" name="description" class="dg-textarea" rows="3" maxlength="2000"></textarea>
            </div>
            <div class="dg-field">
                <label for="partnership-visibility">Visibilité</label>
                <select id="partnership-visibility" name="visibility" class="dg-select" required>
                    <option value="PRIVATE">Interne</option>
                    <option value="PUBLIC">Publique</option>
                </select>
            </div>
            <button type="submit" class="dg-btn dg-btn--saffron" style="align-self:flex-start">Proposer notre capacité</button>
        </form>
    </x-dg.fieldset>
@endif
