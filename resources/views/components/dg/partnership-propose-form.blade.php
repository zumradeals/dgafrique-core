{{--
    Formulaire « Notre organisation peut apporter… » — UIUX-005, convergé avec CAP-067. N'apparaît
    que depuis un Besoin/Projet/ZUMRA déjà consultable, jamais dans le vide depuis la fiche
    Organisation. Réutilise strictement PartnershipService::propose() (provider_type=ORGANIZATION) :
    aucune autorité nouvelle, la vérification que l'acteur gère bien l'organisation choisie et que
    la capacité choisie lui appartient reste dans le service au moment de la soumission.

    La capacité proposée est toujours une CapabilityStatement réellement déclarée par
    l'Organisation (CAP-067) — jamais un champ libre : le convertir en texte fabriqué depuis ce
    formulaire romprait la preuve métier que CAP-065/CAP-067 viennent précisément d'établir.
--}}
@props(['organizations', 'capabilities', 'contextType', 'contextReference'])
@if($organizations->isNotEmpty())
    <x-dg.fieldset>
        <legend><x-dg.label>Notre organisation peut apporter…</x-dg.label></legend>
        @if($capabilities->isEmpty())
            <x-dg.empty>
                <span>
                    Aucune capacité active à proposer pour le moment.
                    @foreach($organizations as $organization)
                        <a href="{{ route('organizations.show', $organization) }}">Déclarer une capacité pour {{ $organization->name }}</a>{{ ! $loop->last ? ' · ' : '' }}
                    @endforeach
                </span>
            </x-dg.empty>
        @else
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
                    <select id="partnership-capability" name="capability_statement_id" class="dg-select" required>
                        @foreach($capabilities->groupBy('organization_id') as $organizationCapabilities)
                            <optgroup label="{{ $organizationCapabilities->first()->organization->name }}">
                                @foreach($organizationCapabilities as $capability)
                                    <option value="{{ $capability->id }}">{{ $capability->label }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <p class="dg-hint">Une capacité déjà déclarée sur la fiche de l'organisation choisie ci-dessus.</p>
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
        @endif
    </x-dg.fieldset>
@endif
