<x-layouts.member
    :title="$configuration['creation_title'] ?? 'Faire naître une ZUMRA'"
    active="zumra"
    :wide="true"
>
    <div class="dg-zumra-create">
        <a class="dg-zumra-create__back" href="{{ route('zumra.index') }}">← Retour au Carrefour ZUMRA</a>

        <header class="dg-zumra-create__hero">
            <div>
                <p class="dg-zumra-eyebrow">COMMUNAUTÉ · PROJET PRINCIPAL · ACTION</p>
                <h1>{{ $configuration['creation_title'] ?? 'Faire naître une ZUMRA' }}</h1>
                <p>Donnez une identité claire à la communauté que vous voulez faire naître. Après sa création, vous entrerez dans son espace pour structurer son projet principal, ses besoins et ses premières actions.</p>
            </div>
            <div class="dg-zumra-create__principle">
                <strong>Une ZUMRA est un monde d’action.</strong>
                <span>Elle naît autour de personnes, d’un objectif commun et d’un projet principal qui donnera ensuite naissance à des besoins, contributions et projets de la même famille.</span>
            </div>
        </header>

        <form class="dg-zumra-create__layout" method="POST" action="{{ route('zumra.groups.store') }}">
            @csrf

            <div class="dg-zumra-create__main">
                <section class="dg-zumra-create__section" aria-labelledby="zumra-create-identity">
                    <div class="dg-zumra-create__section-heading">
                        <span>01</span>
                        <div>
                            <h2 id="zumra-create-identity">Identité de la ZUMRA</h2>
                            <p>Commencez par dire simplement qui vous êtes et ce qui vous rassemble.</p>
                        </div>
                    </div>

                    <div class="dg-zumra-create__fields dg-zumra-create__fields--two">
                        <x-dg.field label="Nom de la ZUMRA" for="name" :required="true" :error="$errors->first('name')">
                            <x-dg.input id="name" name="name" :value="old('name')" maxlength="140" placeholder="Ex. ZUMRA Agriculture Korhogo" :invalid="$errors->has('name')" required />
                        </x-dg.field>

                        <x-dg.field label="Domaine principal" for="domain" :required="true" :error="$errors->first('domain')" hint="Le domaine reste libre : agriculture, éducation, numérique, artisanat…">
                            <x-dg.input id="domain" name="domain" :value="old('domain')" maxlength="140" placeholder="Ex. Agriculture" :invalid="$errors->has('domain')" required />
                        </x-dg.field>
                    </div>

                    <x-dg.field
                        label="Objectif fondateur"
                        for="founding_objective"
                        :required="true"
                        :error="$errors->first('founding_objective')"
                        hint="Décrivez ce que cette communauté veut construire ou changer. Minimum 40 caractères."
                    >
                        <x-dg.textarea id="founding_objective" name="founding_objective" rows="6" maxlength="1800" :invalid="$errors->has('founding_objective')" required>{{ old('founding_objective') }}</x-dg.textarea>
                    </x-dg.field>
                </section>

                <section class="dg-zumra-create__section" aria-labelledby="zumra-create-presence">
                    <div class="dg-zumra-create__section-heading">
                        <span>02</span>
                        <div>
                            <h2 id="zumra-create-presence">Présence et territoire</h2>
                            <p>Indiquez comment les membres pourront agir ensemble et où la ZUMRA s’ancre.</p>
                        </div>
                    </div>

                    <div class="dg-zumra-create__fields dg-zumra-create__fields--two">
                        <x-dg.field label="Mode de participation" for="participation_mode" :required="true" :error="$errors->first('participation_mode')">
                            <x-dg.select id="participation_mode" name="participation_mode" :invalid="$errors->has('participation_mode')" required>
                                <option value="">Choisir…</option>
                                <option value="PHYSICAL" @selected(old('participation_mode') === 'PHYSICAL')>Présentiel</option>
                                <option value="DIGITAL" @selected(old('participation_mode') === 'DIGITAL')>À distance</option>
                                <option value="HYBRID" @selected(old('participation_mode') === 'HYBRID')>Hybride</option>
                            </x-dg.select>
                        </x-dg.field>

                        <x-dg.field label="Territoire principal" for="location" :error="$errors->first('location')" hint="Ville, région ou territoire. Facultatif si la ZUMRA est entièrement numérique.">
                            <x-dg.input id="location" name="location" :value="old('location')" maxlength="160" placeholder="Ex. Abidjan, Côte d’Ivoire" :invalid="$errors->has('location')" />
                        </x-dg.field>
                    </div>

                    <x-dg.field label="Capacité d’accueil au démarrage" for="welcome_capacity" :error="$errors->first('welcome_capacity')">
                        <x-dg.select id="welcome_capacity" name="welcome_capacity" :invalid="$errors->has('welcome_capacity')">
                            <option value="">Je préfère le préciser plus tard</option>
                            @foreach ($welcomeCapacities as $value => $label)
                                <option value="{{ $value }}" @selected(old('welcome_capacity') === $value)>{{ $label }}</option>
                            @endforeach
                        </x-dg.select>
                    </x-dg.field>
                </section>

                <section class="dg-zumra-create__section" aria-labelledby="zumra-create-governance">
                    <div class="dg-zumra-create__section-heading">
                        <span>03</span>
                        <div>
                            <h2 id="zumra-create-governance">Responsabilité au démarrage</h2>
                            <p>Créer une ZUMRA ne vous attribue pas silencieusement tous les rôles.</p>
                        </div>
                    </div>

                    <label class="dg-zumra-create__choice" for="assume_primary_lead">
                        <input
                            id="assume_primary_lead"
                            name="assume_primary_lead"
                            type="checkbox"
                            value="1"
                            @checked(old('assume_primary_lead', '1'))
                        >
                        <span>
                            <strong>Je prends la responsabilité principale au démarrage.</strong>
                            <small>Vous devenez responsable principal de cette ZUMRA. Les autres responsabilités restent vacantes et pourront être proposées puis acceptées explicitement.</small>
                        </span>
                    </label>
                    @error('assume_primary_lead')<p class="dg-field__error">{{ $message }}</p>@enderror
                </section>

                <details class="dg-zumra-create__optional" @if($errors->has('internal_charter') || $errors->has('activity_label.*') || $errors->has('activity_relation.*')) open @endif>
                    <summary>Ajouter maintenant des éléments facultatifs</summary>
                    <div class="dg-zumra-create__optional-body">
                        <x-dg.field
                            label="Charte interne"
                            for="internal_charter"
                            :error="$errors->first('internal_charter')"
                            hint="Facultatif à la naissance. Si vous la rédigez maintenant, 80 caractères minimum."
                        >
                            <x-dg.textarea id="internal_charter" name="internal_charter" rows="7" maxlength="6000" :invalid="$errors->has('internal_charter')">{{ old('internal_charter') }}</x-dg.textarea>
                        </x-dg.field>

                        <div class="dg-zumra-create__activities">
                            <div>
                                <h3>Premières activités dérivées</h3>
                                <p>Ajoutez seulement les activités déjà évidentes. Elles doivent rester reliées à l’objectif principal de la ZUMRA.</p>
                            </div>

                            @for ($index = 0; $index < 2; $index++)
                                <div class="dg-zumra-create__activity-row">
                                    <x-dg.field label="Activité {{ $index + 1 }}" :for="'activity_label_'.$index" :error="$errors->first('activity_label.'.$index)">
                                        <x-dg.input :id="'activity_label_'.$index" :name="'activity_label['.$index.']'" :value="old('activity_label.'.$index)" maxlength="140" placeholder="Ex. Formation aux techniques d’irrigation" :invalid="$errors->has('activity_label.'.$index)" />
                                    </x-dg.field>
                                    <x-dg.field label="Lien avec l’objectif principal" :for="'activity_relation_'.$index" :error="$errors->first('activity_relation.'.$index)">
                                        <x-dg.input :id="'activity_relation_'.$index" :name="'activity_relation['.$index.']'" :value="old('activity_relation.'.$index)" maxlength="600" placeholder="Expliquez en une phrase pourquoi cette activité en découle" :invalid="$errors->has('activity_relation.'.$index)" />
                                    </x-dg.field>
                                </div>
                            @endfor
                        </div>
                    </div>
                </details>

                <div class="dg-zumra-create__submit">
                    <x-dg.button href="{{ route('zumra.index') }}" variant="secondary">Annuler</x-dg.button>
                    <x-dg.button type="submit" variant="solar">Faire naître la ZUMRA →</x-dg.button>
                </div>
            </div>

            <aside class="dg-zumra-create__aside" aria-label="Ce qui se passe après la création">
                <section>
                    <p class="dg-zumra-eyebrow">APRÈS LA CRÉATION</p>
                    <h2>Vous entrez dans son monde.</h2>
                    <ol>
                        <li><span>1</span><div><strong>La ZUMRA naît</strong><small>Elle est créée en constitution et vous en devenez membre actif.</small></div></li>
                        <li><span>2</span><div><strong>Son projet principal prend forme</strong><small>Dans l’espace ZUMRA, vous pourrez structurer le projet qui constitue son cœur opérationnel.</small></div></li>
                        <li><span>3</span><div><strong>Les besoins et actions apparaissent</strong><small>Ils naîtront dans ce contexte avant de rayonner dans les carrefours GAMAD.</small></div></li>
                    </ol>
                </section>

                <section class="dg-zumra-create__aside-note">
                    <strong>Rien n’est publié artificiellement.</strong>
                    <p>La création ouvre l’espace communautaire. Les projets, besoins, responsabilités et contributions continueront à suivre leurs propres règles.</p>
                </section>
            </aside>
        </form>
    </div>
</x-layouts.member>
