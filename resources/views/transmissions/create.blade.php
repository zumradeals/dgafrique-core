{{--
    Proposer une Transmission — fiche TRANSMISSION §5/§6. Aucune déclaration CAP-005/006
    préalable n'est exigée (§5.C) : la Transmission porte son propre libellé de capacité.
--}}
<x-layouts.portal title="Proposer une Transmission — DG Afrique">
    <x-dg.shell :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:820px">
            <a href="{{ route('transmissions.index') }}" class="dg-crumb">← Mes Transmissions</a>

            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="saffron">CAP-006 · Transmission</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">Proposer une Transmission</h1>
                    <p>Une Transmission reste une proposition tant que les autres participants ne l’ont pas explicitement acceptée.</p>
                </div>
            </div>

            @if($errors->any())
                <div class="dg-band" style="margin-bottom:20px;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>
            @endif

            @if($prefillInvite)
                {{-- UIUX-007 — contexte conservé depuis un profil découvert. Le consentement et
                     l'acceptation explicite restent entièrement ceux du moteur Transmission :
                     cette invitation n'est qu'une proposition de plus, jamais garantie. --}}
                <div class="dg-band" style="margin-bottom:20px">Vous proposez cette Transmission en pensant à <strong>{{ $prefillInvite->discovery_display_name }}</strong>, qui recevra une invitation à y participer une fois la Transmission créée.</div>
            @endif

            <form method="POST" action="{{ route('transmissions.store') }}" style="display:flex;flex-direction:column;gap:20px">
                @csrf
                @if($prefillInvite)
                    <input type="hidden" name="invite_discovery_reference" value="{{ $prefillInvite->discovery_reference }}">
                @endif

                <x-dg.fieldset>
                    <legend><x-dg.label>La capacité</x-dg.label></legend>
                    <div class="dg-field">
                        <label for="capability_label">Capacité concernée</label>
                        <input type="text" name="capability_label" id="capability_label" class="dg-input" value="{{ old('capability_label') }}" maxlength="200" required>
                    </div>
                    <div class="dg-field">
                        <label for="learning_objective">Objectif d’apprentissage</label>
                        <textarea name="learning_objective" id="learning_objective" class="dg-textarea" rows="4" minlength="10" maxlength="2000" required>{{ old('learning_objective') }}</textarea>
                        <span class="dg-hint">Ce que la personne apprenante doit pouvoir faire à la fin.</span>
                    </div>
                    <div class="dg-field">
                        <label for="initiator_role">Votre rôle dans cette Transmission</label>
                        <select name="initiator_role" id="initiator_role" class="dg-select" required>
                            <option value="TRANSMITTER" {{ old('initiator_role') === 'TRANSMITTER' ? 'selected' : '' }}>Je transmets — je propose de transmettre cette capacité</option>
                            <option value="LEARNER" {{ old('initiator_role') === 'LEARNER' ? 'selected' : '' }}>J’apprends — je souhaite apprendre cette capacité</option>
                        </select>
                    </div>
                </x-dg.fieldset>

                <x-dg.fieldset>
                    <legend><x-dg.label>Origine et contexte</x-dg.label></legend>
                    <div class="dg-field-grid">
                        <div class="dg-field">
                            <label for="origin_type">Origine de cette Transmission</label>
                            <select name="origin_type" id="origin_type" class="dg-select" required>
                                <option value="INTERACTION" selected>Une interaction humaine ponctuelle</option>
                                <option value="PROFILE">Une intention déclarée dans mon profil</option>
                                <option value="RECOMMENDATION">Une recommandation reçue</option>
                                <option value="NEED">Un besoin de compétence</option>
                                <option value="MISSION">Une Mission</option>
                                <option value="PROJECT">Un Projet</option>
                                <option value="ZUMRA">Une ZUMRA</option>
                                <option value="NONE">Aucune — proposition autonome</option>
                            </select>
                        </div>
                        <div class="dg-field">
                            <label for="context_choice">Rattacher à un contexte (facultatif)</label>
                            <select name="context_choice" id="context_choice" class="dg-select">
                                <option value="">Aucun rattachement — Transmission privée entre personnes</option>
                                @foreach($contextOptions as $option)
                                    <option value="{{ $option['type'] }}|{{ $option['reference'] }}" {{ old('context_choice') === $option['type'].'|'.$option['reference'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                            <span class="dg-hint">Un rattachement ne fabrique jamais d’accès : il ajoute une visibilité contextuelle possible.</span>
                        </div>
                        <div class="dg-field">
                            <label for="visibility">Visibilité</label>
                            <select name="visibility" id="visibility" class="dg-select" required>
                                <option value="PRIVATE" selected>Privée (par défaut)</option>
                                <option value="CONTEXT">Contexte rattaché</option>
                                <option value="PROGRAM">Membres du Programme ZUMRA</option>
                            </select>
                        </div>
                        <div class="dg-field">
                            <label for="availability_note">Disponibilité (facultatif)</label>
                            <input type="text" name="availability_note" id="availability_note" class="dg-input" value="{{ old('availability_note') }}" maxlength="300" placeholder="Ex. mardis soir, selon accord mutuel">
                        </div>
                    </div>
                </x-dg.fieldset>

                <button type="submit" class="dg-btn dg-btn--saffron" style="align-self:flex-start">Proposer la Transmission</button>
            </form>
        </div>
    </x-dg.shell>
</x-layouts.portal>
