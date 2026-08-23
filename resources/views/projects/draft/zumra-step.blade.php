@php
    $stageMap = [
        'audience' => 1, 'nom' => 1, 'resume' => 1,
        'probleme' => 2, 'solution' => 2, 'beneficiaires' => 2,
        'logistique' => 3, 'objectifs' => 3,
        'besoins' => 4,
        'relire' => 5,
    ];
    $stage = $stageMap[$step] ?? 1;
    $stages = [
        1 => ['L’idée du projet', 'Ce que nous voulons réaliser'],
        2 => ['Le besoin & l’impact', 'Pourquoi ce projet est important'],
        3 => ['Comment & avec qui', 'La manière et l’équipe'],
        4 => ['Ressources & cadre', 'Moyens, durée et cadre'],
        5 => ['Validation & lancement', 'Relire et créer'],
    ];
    $help = [
        1 => ['Définir clairement votre idée permet à votre équipe et aux membres de comprendre la vision du projet et de s’y engager.', 'Un projet bien défini est déjà à moitié réussi.'],
        2 => ['Partir d’un problème réel aide la ZUMRA à concentrer son énergie sur un changement utile et compréhensible.', 'L’impact commence par une situation humaine clairement observée.'],
        3 => ['Décrire la réponse et les objectifs permet de mobiliser les bonnes forces sans attribuer silencieusement de responsabilité.', 'Une équipe se construit par des engagements explicites.'],
        4 => ['Nommer les capacités, ressources, risques et conditions d’action rend le projet réaliste sans inventer de financement.', 'Un cadre honnête protège l’action collective.'],
        5 => ['Relisez ensemble ce qui sera réellement enregistré avant de faire naître le Projet dans la ZUMRA.', 'Rien n’est créé avant votre confirmation finale.'],
    ];
    $modeLabel = match($zumraGroup->participation_mode) { 'PHYSICAL' => 'Physique', 'DIGITAL' => 'Numérique', default => 'Hybride' };
    $acceptedRoles = $zumraRoles->where('status', 'ACCEPTED')->count();
    $projectModeLabels = ['PHYSICAL' => 'Sur place', 'DIGITAL' => 'En ligne', 'HYBRID' => 'Les deux'];
    $sourceNeed = $needs->firstWhere('public_reference', $payload['source_need_reference'] ?? null);
@endphp
<x-layouts.portal title="Créer un projet dans {{ $zumraGroup->name }} — DG Afrique">
<x-dg.shell current="zumra" :identity="$identity" :is-administrator="$isAdministrator">
<div class="dg-page zpj" data-zumra-project-step="{{ $stage }}">
    <div class="zpj-layout">
        <aside class="zpj-left" aria-label="Navigation de l’espace ZUMRA">
            <a class="zpj-back" href="{{ route('zumra.groups.show', $zumraGroup) }}">← Retour à l’espace ZUMRA</a>
            <nav class="zpj-nav">
                <p>Espace ZUMRA</p>
                <a href="{{ route('zumra.groups.show', $zumraGroup) }}">⌂ <span>Accueil</span></a>
                <a href="{{ route('zumra.groups.show', $zumraGroup) }}#fil">▥ <span>Fil d’activités</span></a>
                <a href="{{ route('zumra.groups.show', $zumraGroup) }}#conversation">▢ <span>Discussions</span></a>
                <a href="{{ route('zumra.groups.show', $zumraGroup) }}#transmissions">◇ <span>Transmissions</span></a>
                <a class="is-active" href="{{ route('zumra.groups.show', $zumraGroup) }}#projets">□ <span>Projets</span></a>
                <a href="{{ route('zumra.groups.show', $zumraGroup) }}#besoins">◎ <span>Besoins</span></a>
                <a href="{{ route('zumra.groups.show', $zumraGroup) }}#membres">♧ <span>Membres</span><b>{{ $zumraGroup->active_member_count }}</b></a>
                <a href="{{ route('zumra.groups.show', $zumraGroup) }}#activites">▣ <span>Activités</span><b>{{ $zumraActivities->count() + 1 }}</b></a>
                <a href="{{ route('zumra.groups.show', $zumraGroup) }}#evenements">◫ <span>Événements</span></a>
                <a href="{{ route('zumra.groups.show', $zumraGroup) }}#ressources">▱ <span>Ressources</span></a>
                <a href="{{ route('zumra.groups.show', $zumraGroup) }}#gouvernance">⬡ <span>À propos & Gouvernance</span></a>
            </nav>
            <section class="zpj-side-card"><h2>Besoin d’aide ?</h2><p>Notre guide est là pour vous accompagner à chaque étape.</p><a href="{{ route('people.index') }}">Contacter un guide</a></section>
            <section class="zpj-side-card"><p class="zpj-eyebrow">Statut de la ZUMRA</p><strong class="zpj-state"><i></i>{{ $zumraGroup->state === 'CONSTITUTING' ? 'En constitution' : ucfirst(mb_strtolower($zumraGroup->state)) }}</strong><small>{{ $acceptedRoles }}/5 responsabilités acceptées</small></section>
        </aside>

        <main class="zpj-main">
            <section class="zpj-hero">
                <div class="zpj-mark">{{ mb_strtoupper(mb_substr($zumraGroup->name, 0, 1)) }}<i></i></div>
                <div><div class="zpj-tags"><span>{{ $zumraGroup->domain }}</span><span>{{ $zumraGroup->maturity === 'ESTABLISHED' ? 'Établie' : 'Émergente' }}</span></div><h1>{{ $zumraGroup->name }}</h1><p>{{ $zumraGroup->founding_objective }}</p><div class="zpj-meta"><span>♧ {{ $modeLabel }}</span><span>⌖ {{ $zumraGroup->location ?: 'Lieu à préciser' }}</span><span>♙ {{ $zumraGroup->active_member_count }} membre{{ $zumraGroup->active_member_count > 1 ? 's' : '' }}</span></div></div>
                <aside><strong>{{ $zumraGroup->active_member_count }}</strong><span>membre{{ $zumraGroup->active_member_count > 1 ? 's' : '' }} actif{{ $zumraGroup->active_member_count > 1 ? 's' : '' }}</span><small>{{ $acceptedRoles }}/5 responsabilités<br>acceptées</small></aside>
            </section>

            <section class="zpj-workshop">
                <header><div><h2>Créer un projet dans votre ZUMRA ✨</h2><p>Transformons une intention en action concrète. Suivez les étapes pour structurer votre projet.</p></div>@if($step !== 'relire')<button type="submit" form="project-step-form" name="_intent" value="save_later" formnovalidate class="zpj-draft">▱ Enregistrer comme brouillon</button>@endif</header>
                <ol class="zpj-progress" aria-label="Progression du projet">@foreach($stages as $number => [$title, $subtitle])<li class="{{ $number === $stage ? 'is-active' : ($number < $stage ? 'is-done' : '') }}"><i>{{ $number }}</i><strong>{{ $title }}</strong><small>{{ $subtitle }}</small></li>@endforeach</ol>

                @if($errors->any())<div class="zpj-error">{{ $errors->first() }}</div>@endif

                @if($step === 'relire')
                    <form id="project-step-form" method="POST" action="{{ route('projects.draft.confirm', $draft) }}" enctype="multipart/form-data" class="zpj-form">@csrf
                        <div class="zpj-step-heading"><h3>5. Validation & lancement</h3><p>Relisez les informations qui seront réellement enregistrées.</p></div>
                        <div class="zpj-review">
                            <article><span>Identité</span><h4>{{ $payload['name'] ?? '—' }}</h4><p>{{ $payload['summary'] ?? '—' }}</p></article>
                            <article><span>Besoin & impact</span><p><strong>Situation :</strong> {{ $payload['problem'] ?? '—' }}</p><p><strong>Personnes concernées :</strong> {{ $payload['beneficiaries'] ?? '—' }}</p></article>
                            <article><span>Réponse proposée</span><p>{{ $payload['proposed_solution'] ?? '—' }}</p><p><strong>Objectifs :</strong> {{ !empty($payload['objectives']) ? implode(' · ', $payload['objectives']) : '—' }}</p></article>
                            <article><span>Cadre réel</span><p>{{ $config['domains'][$payload['domain'] ?? ''] ?? '—' }} · {{ $projectModeLabels[$payload['participation_mode'] ?? ''] ?? '—' }} @if(!empty($payload['location']))· {{ $payload['location'] }}@endif</p><p><strong>ZUMRA porteuse :</strong> {{ $zumraGroup->name }}</p><p><strong>Participants engagés :</strong> aucun participant attribué automatiquement.</p></article>
                            @if($sourceNeed)<article><span>Besoin DG Afrique relié</span><p>{{ $sourceNeed->title }}</p></article>@endif
                        </div>
                        <label class="zpj-upload"><span>Image de couverture (facultatif)</span><input type="file" name="image" accept="image/jpeg,image/png,image/webp"><small>JPEG, PNG ou WebP — 4 Mo maximum.</small></label>
                        <p class="zpj-honest">Le Projet sera créé au statut « Idée ». Aucun financement, membre ou responsabilité n’est créé ici.</p>
                        <footer class="zpj-actions"><a href="{{ route('zumra.groups.show', $zumraGroup) }}">Annuler et quitter</a><a class="zpj-prev" href="{{ route('projects.draft.show', [$draft, 'logistique']) }}">← Précédent</a><button type="submit">Créer le projet ✨</button></footer>
                    </form>
                @else
                    <form id="project-step-form" method="POST" action="{{ route('projects.draft.update', [$draft, $step]) }}" class="zpj-form">@csrf
                        @switch($step)
                            @case('audience')
                                <div class="zpj-step-heading"><h3>Ce projet naît dans {{ $zumraGroup->name }}</h3><p>Confirmez explicitement la ZUMRA qui portera et gouvernera ce Projet.</p></div>
                                <input type="hidden" name="owner_type" value="GROUP">
                                <input type="hidden" name="zumra_group_reference" value="{{ $zumraGroup->public_reference }}">
                                <div class="zpj-anchor"><i>{{ mb_strtoupper(mb_substr($zumraGroup->name, 0, 1)) }}</i><span><strong>{{ $zumraGroup->name }}</strong><small>{{ $zumraGroup->domain }} · {{ $modeLabel }} · {{ $zumraGroup->active_member_count }} membre{{ $zumraGroup->active_member_count > 1 ? 's' : '' }}</small></span></div>
                                <p class="zpj-honest">Cette confirmation vérifie vos droits et le quota réel de la ZUMRA. Elle n’ajoute aucun membre et ne vous attribue aucune responsabilité de Projet.</p>
                                @break
                            @case('nom')
                                <div class="zpj-step-heading"><h3>1. L’idée du projet</h3><p>Donnons un nom clair à ce que nous voulons réaliser.</p></div>
                                <div class="zpj-two"><label><b>Titre du projet <em>*</em></b><input name="name" value="{{ old('name', $payload['name'] ?? '') }}" minlength="5" maxlength="180" placeholder="Donnez un titre court et clair à votre projet…" required autofocus><small>Un bon titre donne déjà une direction à votre équipe.</small></label>@if($needs->isNotEmpty())<label><b>Besoin DG Afrique d’origine (facultatif)</b><select name="source_need_reference"><option value="">Aucun besoin existant</option>@foreach($needs as $need)<option value="{{ $need->public_reference }}" @selected(old('source_need_reference', $payload['source_need_reference'] ?? null) === $need->public_reference)>{{ $need->title }}</option>@endforeach</select><small>Cette liaison n’ajoute ni ne crée aucun Besoin.</small></label>@endif</div>
                                @break
                            @case('resume')
                                <div class="zpj-step-heading"><h3>1. L’idée du projet</h3><p>Expliquez simplement ce que la ZUMRA veut réaliser.</p></div><label><b>Description courte du projet <em>*</em></b><textarea name="summary" rows="5" minlength="40" maxlength="1200" required placeholder="En une ou deux phrases, de quoi s’agit-il ?">{{ old('summary', $payload['summary'] ?? '') }}</textarea><small>Comme si vous le racontiez à une personne qui découvre votre idée.</small></label>
                                @break
                            @case('probleme')
                                <div class="zpj-step-heading"><h3>2. Le besoin & l’impact</h3><p>Partons d’une situation réelle, sans créer silencieusement un objet Besoin.</p></div><label><b>Quel problème ou quel manque avez-vous observé ? <em>*</em></b><textarea name="problem" rows="6" minlength="40" maxlength="2400" required>{{ old('problem', $payload['problem'] ?? '') }}</textarea></label>
                                @break
                            @case('beneficiaires')
                                <div class="zpj-step-heading"><h3>2. Le besoin & l’impact</h3><p>Précisez les personnes ou communautés concernées.</p></div><label><b>À qui ce projet doit-il être utile ? <em>*</em></b><textarea name="beneficiaries" rows="5" minlength="20" maxlength="1200" required>{{ old('beneficiaries', $payload['beneficiaries'] ?? '') }}</textarea><small>Décrivez les bénéficiaires sans créer de profil ou d’adhésion.</small></label>
                                @break
                            @case('solution')
                                <div class="zpj-step-heading"><h3>2. Le besoin & l’impact</h3><p>Décrivez la réponse envisagée à la situation observée.</p></div><label><b>Comment pensez-vous répondre à cette situation ? <em>*</em></b><textarea name="proposed_solution" rows="6" minlength="40" maxlength="2400" required>{{ old('proposed_solution', $payload['proposed_solution'] ?? '') }}</textarea></label>
                                @break
                            @case('objectifs')
                                <div class="zpj-step-heading"><h3>3. Comment & avec qui</h3><p>Formulez les résultats concrets que la ZUMRA souhaite atteindre.</p></div>@include('projects.draft._dynamic-list', ['key' => 'objectives', 'items' => $payload['objectives'] ?? [], 'placeholder' => 'Ex. Produire trois services pilotes'])
                                @break
                            @case('besoins')
                                <div class="zpj-step-heading"><h3>4. Ressources & cadre</h3><p>Renseignez seulement ce que vous savez déjà. Aucun financement n’est ouvert.</p></div><div class="zpj-three"><fieldset><legend>Capacités nécessaires</legend>@include('projects.draft._dynamic-list', ['key' => 'required_capabilities', 'items' => $payload['required_capabilities'] ?? [], 'placeholder' => 'Ex. Design de service'])</fieldset><fieldset><legend>Ressources nécessaires</legend>@include('projects.draft._dynamic-list', ['key' => 'required_resources', 'items' => $payload['required_resources'] ?? [], 'placeholder' => 'Ex. Un espace de travail'])</fieldset><fieldset><legend>Risques identifiés</legend>@include('projects.draft._dynamic-list', ['key' => 'risks', 'items' => $payload['risks'] ?? [], 'placeholder' => 'Ex. Disponibilité limitée'])</fieldset></div>
                                @break
                            @case('logistique')
                                <div class="zpj-step-heading"><h3>3. Comment & avec qui</h3><p>Précisez dans quel domaine, de quelle manière et où la ZUMRA agira.</p></div><div class="zpj-two"><label><b>Domaine du projet <em>*</em></b><select name="domain" required><option value="">Sélectionnez un domaine…</option>@foreach($config['domains'] as $code => $label)<option value="{{ $code }}" @selected(old('domain', $payload['domain'] ?? null) === $code)>{{ $label }}</option>@endforeach</select></label><fieldset><legend>Mode <em>*</em></legend><div class="zpj-radios">@foreach(['PHYSICAL'=>'Sur place','DIGITAL'=>'En ligne','HYBRID'=>'Hybride'] as $value=>$label)<label><input type="radio" name="participation_mode" value="{{ $value }}" @checked(old('participation_mode', $payload['participation_mode'] ?? 'PHYSICAL') === $value) required><span>{{ $label }}</span></label>@endforeach</div></fieldset></div><label data-project-location><b>Lieu</b><input name="location" value="{{ old('location', $payload['location'] ?? '') }}" maxlength="160" placeholder="Ville, quartier ou zone…"></label><p class="zpj-honest">Les membres et responsabilités du Projet seront engagés séparément et explicitement après sa création.</p>
                                @break
                        @endswitch
                        <footer class="zpj-actions"><a href="{{ route('zumra.groups.show', $zumraGroup) }}">Annuler et quitter</a>@if($previousStep)<button class="zpj-prev" type="submit" name="_intent" value="back" formnovalidate>← Précédent</button>@endif<button type="submit" name="_intent" value="continue">{{ $step === 'besoins' ? 'Suivant : Validation & lancement →' : 'Continuer →' }}</button></footer>
                    </form>
                @endif
            </section>
        </main>

        <aside class="zpj-right">
            <section class="zpj-context"><h2>🎯 À quoi sert cette étape ?</h2><p>{{ $help[$stage][0] }}</p><strong>{{ $help[$stage][1] }}</strong></section>
            <section class="zpj-preview"><h2>Aperçu de votre projet</h2><div class="zpj-project-mark">🌱</div><strong data-project-name>{{ $payload['name'] ?? 'Nom de votre projet' }}</strong><small>{{ isset($payload['name']) ? 'Projet porté par '.$zumraGroup->name : '(à définir à l’étape 1)' }}</small><dl><div><dt>⌘ Domaine</dt><dd data-project-domain>{{ $config['domains'][$payload['domain'] ?? ''] ?? '—' }}</dd></div><div><dt>♧ Porteur</dt><dd>{{ $zumraGroup->name }}</dd></div><div><dt>♧ Équipe</dt><dd>Aucune attribution</dd></div><div><dt>▧ Impact attendu</dt><dd data-project-impact>{{ !empty($payload['beneficiaries']) ? \Illuminate\Support\Str::limit($payload['beneficiaries'], 42) : '—' }}</dd></div><div><dt>◎ Statut</dt><dd>Idée</dd></div></dl></section>
            <section class="zpj-question"><h2>Une question ?</h2><p>Consultez notre guide ou échangez avec un autre créateur de projet.</p><a href="{{ route('people.index') }}">Voir le guide</a></section>
        </aside>
    </div>
    <section class="zpj-before"><h2>Avant de commencer…</h2><p>Un projet réussi répond à un vrai besoin, mobilise les bonnes forces et produit un impact durable.</p><ol><li><i>♧</i><span><strong>Comprendre le besoin</strong>Partons d’un besoin réel de notre communauté.</span></li><li><i>◎</i><span><strong>Définir l’impact</strong>Clarifions la valeur et les résultats attendus.</span></li><li><i>♧</i><span><strong>Mobiliser les forces</strong>Impliquons explicitement les personnes et capacités.</span></li><li><i>▣</i><span><strong>Agir avec constance</strong>Passons à l’action et mesurons progressivement.</span></li><li><i>↗</i><span><strong>Grandir ensemble</strong>Capitalisons nos réussites et apprentissages.</span></li></ol></section>
</div>
<script>(()=>{const r=document.querySelector('[data-zumra-project-step]');if(!r)return;const bind=(field,target,fallback,format=v=>v)=>{const input=r.querySelector(`[name="${field}"]`),output=r.querySelector(target);if(input&&output)input.addEventListener('input',()=>output.textContent=input.value.trim()?format(input.value.trim()):fallback)};bind('name','[data-project-name]','Nom de votre projet');bind('beneficiaries','[data-project-impact]','—',v=>v.length>42?v.slice(0,41)+'…':v);const domain=r.querySelector('[name="domain"]'),domainOut=r.querySelector('[data-project-domain]');if(domain&&domainOut)domain.addEventListener('change',()=>domainOut.textContent=domain.selectedOptions[0]?.textContent||'—');const radios=r.querySelectorAll('[name="participation_mode"]'),loc=r.querySelector('[data-project-location]');const sync=()=>{const c=r.querySelector('[name="participation_mode"]:checked');if(loc)loc.hidden=c?.value==='DIGITAL'};radios.forEach(x=>x.addEventListener('change',sync));sync()})();</script>
</x-dg.shell>
</x-layouts.portal>
