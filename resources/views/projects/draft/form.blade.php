@php
    $steps = \App\Application\Projects\ProjectDraftService::STEPS;
    $stepLabels = \App\Application\Projects\ProjectDraftService::STEP_LABELS;
    $currentIndex = array_search($step, $steps, true);
    $stepNumber = ($currentIndex === false ? 0 : $currentIndex) + 1;
    $contextGroup = $zumraGroup ?? $groups->firstWhere('public_reference', $payload['zumra_group_reference'] ?? null);
    $headings = [
        'audience' => ['Avec quelle ZUMRA construire ?', 'Choisissez le monde dans lequel ce projet va naître et précisez qui en portera la responsabilité.'],
        'nom' => ['Donnez un nom à votre projet.', 'Un bon nom permet aux autres de comprendre rapidement ce que vous voulez faire naître.'],
        'resume' => ['Votre projet en quelques mots.', 'Expliquez simplement l’idée générale, comme si vous la présentiez à une personne qui la découvre.'],
        'probleme' => ['Quelle situation voulez-vous améliorer ?', 'Partez du réel : quel problème, manque ou obstacle justifie l’existence de ce projet ?'],
        'solution' => ['Que proposez-vous de faire ?', 'Décrivez la réponse que le projet veut construire, tester ou mettre en œuvre.'],
        'beneficiaires' => ['À qui ce projet sera-t-il utile ?', 'Identifiez les personnes, communautés ou organisations qui doivent réellement en bénéficier.'],
        'logistique' => ['Où et comment agir ?', 'Situez le projet dans un domaine, un mode de participation et, si utile, un territoire.'],
        'objectifs' => ['Quels objectifs souhaitez-vous atteindre ?', 'Transformez votre intention en résultats clairs que la ZUMRA pourra poursuivre ensemble.'],
        'besoins' => ['De quoi aurez-vous besoin ?', 'Identifiez les savoir-faire, ressources et difficultés à anticiper sans inventer ce qui n’est pas encore connu.'],
        'relire' => ['Votre projet prend forme.', 'Relisez l’essentiel avant de le faire naître dans GAMAD. Vous pourrez ensuite l’enrichir depuis sa fiche.'],
    ];
    [$stepHeading, $stepIntro] = $headings[$step];
    $backUrl = $contextGroup ? route('zumra.groups.show', $contextGroup) : route('member.space');
    $backLabel = $contextGroup ? 'Retour à '.$contextGroup->name : 'Retour à Mon espace';
@endphp

<x-layouts.member title="Faire naître un projet" active="projects" :wide="true">
    <div class="dg-project-create">
        <a class="dg-project-create__back" href="{{ $backUrl }}">← {{ $backLabel }}</a>

        <header class="dg-project-create__hero">
            <div class="dg-project-create__hero-copy">
                <p class="dg-project-create__eyebrow">ZUMRA · PROJET · ACTION</p>
                <h1>Donnez vie à votre projet.</h1>
                <p>Construisez-le étape par étape. Votre brouillon reste sauvegardable jusqu’au moment où vous choisissez réellement de faire naître le Projet dans GAMAD.</p>
            </div>

            <div class="dg-project-create__world">
                <small>MONDE D’ANCRAGE</small>
                <strong>{{ $contextGroup?->name ?? 'Votre ZUMRA' }}</strong>
                <span>{{ $contextGroup?->founding_objective ?? 'Tout Projet GAMAD naît dans une ZUMRA dont vous êtes membre actif.' }}</span>
            </div>
        </header>

        <nav class="dg-project-create__progress" aria-label="Progression de création du projet">
            @foreach ($steps as $index => $draftStep)
                @php
                    $state = $index < $currentIndex ? 'is-done' : ($index === $currentIndex ? 'is-active' : '');
                    $label = $stepLabels[$draftStep] ?? $draftStep;
                @endphp
                @if ($index <= $currentIndex)
                    <a class="dg-project-create__progress-step {{ $state }}" href="{{ route('projects.draft.show', [$draft, $draftStep]) }}" @if($index === $currentIndex) aria-current="step" @endif>
                        <span>{{ $index + 1 }}. {{ $label }}</span>
                    </a>
                @else
                    <span class="dg-project-create__progress-step {{ $state }}"><span>{{ $index + 1 }}. {{ $label }}</span></span>
                @endif
            @endforeach
        </nav>

        <div class="dg-project-create__layout">
            <main class="dg-project-create__main">
                <section class="dg-project-create__card">
                    <div class="dg-project-create__step-head">
                        <span class="dg-project-create__number">{{ str_pad((string) $stepNumber, 2, '0', STR_PAD_LEFT) }}</span>
                        <div>
                            <p class="dg-project-create__eyebrow">ÉTAPE {{ $stepNumber }} SUR {{ count($steps) }}</p>
                            <h2>{{ $stepHeading }}</h2>
                            <p>{{ $stepIntro }}</p>
                        </div>
                    </div>

                    @if ($step === 'relire')
                        <div class="dg-project-create__review">
                            @foreach ([
                                'name' => 'Nom du projet',
                                'summary' => 'Résumé',
                                'problem' => 'Situation à améliorer',
                                'proposed_solution' => 'Solution proposée',
                                'beneficiaries' => 'Bénéficiaires',
                                'location' => 'Territoire',
                            ] as $key => $label)
                                <section>
                                    <h3>{{ $label }}</h3>
                                    <p>{{ filled($payload[$key] ?? null) ? $payload[$key] : 'Non précisé' }}</p>
                                </section>
                            @endforeach

                            <section>
                                <h3>Objectifs</h3>
                                <p>{{ collect($payload['objectives'] ?? [])->filter()->join(' · ') ?: 'À préciser plus tard' }}</p>
                            </section>
                            <section>
                                <h3>Besoins identifiés</h3>
                                <p>{{ collect(array_merge($payload['required_capabilities'] ?? [], $payload['required_resources'] ?? []))->filter()->join(' · ') ?: 'À préciser plus tard' }}</p>
                            </section>
                        </div>

                        <form method="POST" enctype="multipart/form-data" action="{{ route('projects.draft.confirm', $draft) }}">
                            @csrf
                            <div class="dg-project-create__fields" style="margin-top:1rem">
                                <x-dg.field label="Illustration du projet" for="image" :error="$errors->first('image')" hint="Facultatif · JPG, PNG ou WebP · 4 Mo maximum.">
                                    <input class="dg-input" id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
                                </x-dg.field>
                            </div>
                            <div class="dg-project-create__actions">
                                <a class="dg-button dg-button--secondary" href="{{ route('projects.draft.show', [$draft, 'audience']) }}">Revoir mes réponses</a>
                                <button class="dg-button dg-button--solar" type="submit">Faire naître le projet →</button>
                            </div>
                        </form>
                    @else
                        <form method="POST" action="{{ route('projects.draft.update', [$draft, $step]) }}">
                            @csrf

                            @if ($step === 'audience')
                                <div class="dg-project-create__fields">
                                    <x-dg.field label="ZUMRA d’ancrage" for="zumra_group_reference" :required="true" :error="$errors->first('zumra_group_reference')" hint="Le Projet restera rattaché à cette ZUMRA, quel que soit son porteur.">
                                        <select class="dg-input" id="zumra_group_reference" name="zumra_group_reference" required>
                                            <option value="">Choisir une ZUMRA</option>
                                            @foreach ($groups as $group)
                                                <option value="{{ $group->public_reference }}" @selected(old('zumra_group_reference', $payload['zumra_group_reference'] ?? '') === $group->public_reference)>{{ $group->name }}</option>
                                            @endforeach
                                        </select>
                                    </x-dg.field>

                                    <div>
                                        <p class="dg-project-create__eyebrow" style="margin-bottom:.55rem">QUI PORTE LE PROJET ?</p>
                                        <div class="dg-project-create__choice-grid">
                                            <label class="dg-project-create__choice">
                                                <input type="radio" name="owner_type" value="GROUP" @checked(old('owner_type', $payload['owner_type'] ?? 'GROUP') === 'GROUP')>
                                                <strong>La ZUMRA porte le projet</strong>
                                                <small>Le projet est gouverné collectivement par la ZUMRA selon ses responsabilités réelles.</small>
                                            </label>
                                            <label class="dg-project-create__choice">
                                                <input type="radio" name="owner_type" value="PERSON" @checked(old('owner_type', $payload['owner_type'] ?? 'GROUP') === 'PERSON')>
                                                <strong>Je porte le projet</strong>
                                                <small>Vous gardez la décision sur le projet, tout en restant ancré dans la ZUMRA choisie.</small>
                                            </label>
                                        </div>
                                        @error('owner_type')<p class="dg-field__error">{{ $message }}</p>@enderror
                                    </div>

                                    @if ($groups->isEmpty())
                                        <div class="dg-project-create__draft-note">Vous n’êtes membre actif d’aucune ZUMRA pour le moment. <a href="{{ route('zumra.index') }}">Découvrir les ZUMRA →</a></div>
                                    @endif
                                </div>
                            @elseif (in_array($step, ['nom', 'resume', 'probleme', 'solution', 'beneficiaires'], true))
                                @php
                                    [$key, $minimum, $maximum, $label, $placeholder] = [
                                        'nom' => ['name', 5, 180, 'Nom du projet', 'Ex. Plateforme numérique pour artisans d’Abobo'],
                                        'resume' => ['summary', 40, 1200, 'Résumé du projet', 'En quelques phrases, que voulez-vous construire ?'],
                                        'probleme' => ['problem', 40, 2400, 'Situation à améliorer', 'Décrivez le problème réel auquel vous voulez répondre.'],
                                        'solution' => ['proposed_solution', 40, 2400, 'Solution proposée', 'Expliquez ce que le projet veut mettre en œuvre.'],
                                        'beneficiaires' => ['beneficiaries', 20, 1200, 'Bénéficiaires', 'À qui le projet doit-il être utile ?'],
                                    ][$step];
                                @endphp
                                <div class="dg-project-create__fields">
                                    <x-dg.field :label="$label" :for="$key" :required="true" :error="$errors->first($key)" :hint="'Minimum '.$minimum.' caractères.'">
                                        <textarea class="dg-input" id="{{ $key }}" name="{{ $key }}" minlength="{{ $minimum }}" maxlength="{{ $maximum }}" rows="6" placeholder="{{ $placeholder }}" required>{{ old($key, $payload[$key] ?? '') }}</textarea>
                                    </x-dg.field>
                                    @if ($step === 'nom')
                                        <input type="hidden" name="source_need_reference" value="{{ old('source_need_reference', $payload['source_need_reference'] ?? '') }}">
                                    @endif
                                </div>
                            @elseif ($step === 'logistique')
                                <div class="dg-project-create__fields dg-project-create__fields--two">
                                    <x-dg.field label="Domaine" for="domain" :required="true" :error="$errors->first('domain')">
                                        <select class="dg-input" id="domain" name="domain" required>
                                            <option value="">Choisir un domaine</option>
                                            @foreach ($config['domains'] as $value => $label)
                                                <option value="{{ $value }}" @selected(old('domain', $payload['domain'] ?? '') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </x-dg.field>
                                    <x-dg.field label="Mode de participation" for="participation_mode" :required="true" :error="$errors->first('participation_mode')">
                                        <select class="dg-input" id="participation_mode" name="participation_mode" required>
                                            @foreach (['PHYSICAL' => 'Sur place', 'DIGITAL' => 'À distance', 'HYBRID' => 'Hybride'] as $value => $label)
                                                <option value="{{ $value }}" @selected(old('participation_mode', $payload['participation_mode'] ?? 'HYBRID') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </x-dg.field>
                                </div>
                                <div class="dg-project-create__fields" style="margin-top:1rem">
                                    <x-dg.field label="Territoire" for="location" :error="$errors->first('location')" hint="Facultatif si le projet est entièrement numérique.">
                                        <input class="dg-input" id="location" name="location" value="{{ old('location', $payload['location'] ?? '') }}" maxlength="160" placeholder="Ex. Abidjan, Côte d’Ivoire">
                                    </x-dg.field>
                                </div>
                            @else
                                @php
                                    $collections = $step === 'objectifs'
                                        ? ['objectives' => ['Objectifs', 'Ex. Mettre un premier prototype entre les mains de vrais utilisateurs']]
                                        : [
                                            'required_capabilities' => ['Savoir-faire nécessaires', 'Ex. Développement web, UX, commercial…'],
                                            'required_resources' => ['Ressources nécessaires', 'Ex. Hébergement, équipement, accès terrain…'],
                                            'risks' => ['Difficultés à anticiper', 'Ex. Disponibilité des utilisateurs pilotes…'],
                                        ];
                                @endphp
                                <div class="dg-project-create__fields">
                                    @foreach ($collections as $key => [$label, $placeholder])
                                        <section class="dg-project-create__collection">
                                            <h3>{{ $label }}</h3>
                                            @php($rows = array_slice([...old($key, $payload[$key] ?? []), ''], 0, 20))
                                            @foreach ($rows as $index => $value)
                                                <div class="dg-project-create__collection-row">
                                                    <span>{{ $index + 1 }}</span>
                                                    <input class="dg-input" id="{{ $key }}-{{ $index }}" name="{{ $key }}[]" value="{{ $value }}" placeholder="{{ $placeholder }}">
                                                </div>
                                            @endforeach
                                        </section>
                                    @endforeach
                                    <button class="dg-button dg-button--secondary" type="submit" name="_intent" value="add">Enregistrer et ajouter une ligne</button>
                                </div>
                            @endif

                            <div class="dg-project-create__actions">
                                <div class="dg-project-create__actions-group">
                                    @if ($previousStep)
                                        <button class="dg-button dg-button--secondary" type="submit" name="_intent" value="back" formnovalidate>← Étape précédente</button>
                                    @endif
                                    <button class="dg-button dg-button--secondary" type="submit" name="_intent" value="save_later" formnovalidate>Enregistrer et reprendre plus tard</button>
                                </div>
                                <button class="dg-button dg-button--primary" type="submit" name="_intent" value="continue">Continuer →</button>
                            </div>
                        </form>
                    @endif
                </section>
            </main>

            <aside class="dg-project-create__aside" aria-label="Repères pour créer le projet">
                <section>
                    <p class="dg-project-create__eyebrow">VOTRE CHEMIN</p>
                    <h2>De l’idée à l’action.</h2>
                    <div class="dg-project-create__aside-list">
                        <div class="dg-project-create__aside-item"><span>1</span><div><strong>Ancrer</strong><small>Choisir la ZUMRA dans laquelle le Projet prend naissance.</small></div></div>
                        <div class="dg-project-create__aside-item"><span>2</span><div><strong>Comprendre</strong><small>Nommer le problème, la solution et les personnes concernées.</small></div></div>
                        <div class="dg-project-create__aside-item"><span>3</span><div><strong>Structurer</strong><small>Préciser objectifs, savoir-faire et ressources nécessaires.</small></div></div>
                        <div class="dg-project-create__aside-item"><span>4</span><div><strong>Faire naître</strong><small>Relire puis créer le Projet. Sa progression commencera ensuite dans le réel.</small></div></div>
                    </div>
                </section>

                <section class="dg-project-create__aside-note">
                    <strong>Votre brouillon vous appartient.</strong>
                    <p>Vous pouvez quitter et reprendre plus tard. Aucun Projet n’est créé avant votre confirmation finale.</p>
                </section>

                @if ($contextGroup)
                    <section>
                        <p class="dg-project-create__eyebrow">DANS {{ mb_strtoupper($contextGroup->name) }}</p>
                        <h2>Un projet pour faire avancer la communauté.</h2>
                        <p>{{ $contextGroup->founding_objective }}</p>
                    </section>
                @endif
            </aside>
        </div>
    </div>
</x-layouts.member>
