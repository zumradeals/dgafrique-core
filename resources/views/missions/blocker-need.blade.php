{{--
    Exprimer un blocage comme besoin — UX-HARMONY-MISSIONS-002. Jamais automatique : confirmation
    humaine explicite requise ; le Need passe strictement par NeedService puis se lie au blocage.
    Champs, validations et action de formulaire inchangés — seule la présentation rend la
    transition Mission → Besoin visuellement évidente.
--}}
<x-layouts.portal title="Exprimer ce manque comme besoin — DG Afrique">
    <x-dg.shell :identity="$identity" :is-administrator="$isAdministrator">
        <div class="md-page" style="max-width:760px">
            <a href="{{ route('missions.show', $mission) }}" class="md-crumb">← {{ $mission->title }}</a>

            <section class="md-hero">
                <div class="md-hero-top">
                    <div class="md-tags"><span>Blocage → Besoin</span></div>
                </div>
                <h1>Exprimer ce manque comme besoin</h1>
                <p>
                    La Mission « {{ $mission->title }} » est bloquée. Voici ce qui manque :
                    <strong>{{ $blocker->description }}</strong>
                </p>
                <div class="md-facts">
                    <span>Mission bloquée<strong>{{ $mission->title }}</strong></span>
                    <span>Type de blocage<strong>{{ \App\Models\MissionBlocker::TYPE_LABELS[$blocker->type] ?? $blocker->type }}</strong></span>
                    <span>Effet sur le blocage<strong>Aucun déblocage automatique</strong></span>
                </div>
            </section>

            @if($errors->any())
                <div class="dg-band" style="margin:16px 0;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('missions.blockers.express-need', [$mission, $blocker]) }}" style="display:flex;flex-direction:column;gap:16px;margin-top:16px">
                @csrf

                <div class="md-panel">
                    <x-dg.label>Le besoin</x-dg.label>
                    <div style="display:flex;flex-direction:column;gap:14px;margin-top:12px">
                        <div class="dg-field">
                            <label for="title">Titre</label>
                            <input type="text" name="title" id="title" class="dg-input" value="{{ old('title', $mission->title) }}" maxlength="180" required>
                        </div>
                        <div class="dg-field">
                            <label for="context">Contexte</label>
                            <textarea name="context" id="context" class="dg-textarea" rows="6" minlength="40" maxlength="3000" required>{{ old('context', $blocker->description) }}</textarea>
                        </div>
                        <div class="dg-field-grid">
                            <div class="dg-field">
                                <label for="category">Catégorie</label>
                                <select name="category" id="category" class="dg-select" required>
                                    @foreach($configuration['categories'] as $code => $label)
                                        <option value="{{ $code }}" @selected(old('category') === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="dg-field">
                                <label for="collaboration_mode">Mode de collaboration</label>
                                <select name="collaboration_mode" id="collaboration_mode" class="dg-select">
                                    <option value="ANY">Indifférent</option>
                                    <option value="LOCAL">Sur place</option>
                                    <option value="REMOTE">À distance</option>
                                    <option value="HYBRID">Hybride</option>
                                </select>
                            </div>
                            <div class="dg-field">
                                <label for="visibility">Visibilité</label>
                                <select name="visibility" id="visibility" class="dg-select">
                                    <option value="PRIVATE">Privé</option>
                                    <option value="PROGRAM">Membres ZUMRA</option>
                                    <option value="PUBLIC">Réseau DG Afrique</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <label class="dg-consent">
                    <input type="checkbox" name="confirm" value="1" required>
                    <span>
                        <strong>Je confirme vouloir publier ce besoin.</strong>
                        Un besoin réel sera créé et lié à ce blocage. Résoudre ce besoin plus tard ne débloquera pas automatiquement la Mission.
                    </span>
                </label>

                <button type="submit" class="dg-btn dg-btn--need" style="align-self:flex-start">Créer et lier ce besoin</button>
            </form>
        </div>
    </x-dg.shell>
</x-layouts.portal>
