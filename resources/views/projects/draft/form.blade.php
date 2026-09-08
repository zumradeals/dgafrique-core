<x-layouts.member title="Mon projet en préparation" active="projects"><div class="dg-space">
<a class="dg-space-text-link" href="{{ route('member.space') }}">← Mon espace</a><p class="dg-space-eyebrow">VOTRE PROJET EN PRÉPARATION</p>
@php($headings = ['audience' => 'Avec quelle ZUMRA construire ?', 'nom' => 'Quel nom pour votre idée ?', 'resume' => 'Votre idée en quelques mots.', 'probleme' => 'Quelle situation voulez-vous améliorer ?', 'solution' => 'Que proposez-vous de faire ?', 'beneficiaires' => 'À qui ce projet sera-t-il utile ?', 'logistique' => 'Où et comment agir ?', 'objectifs' => 'Quels objectifs souhaitez-vous atteindre ?', 'besoins' => 'De quoi aurez-vous besoin ?', 'relire' => 'Votre projet prend forme.'])
<h1>{{ $headings[$step] }}</h1>
@if ($step === 'relire')
    @foreach (['name' => 'Nom', 'summary' => 'Résumé', 'problem' => 'Situation', 'proposed_solution' => 'Solution', 'beneficiaries' => 'Bénéficiaires', 'location' => 'Lieu'] as $key => $label)<section class="dg-space-section"><h2>{{ $label }}</h2><p>{{ $payload[$key] ?? 'Non précisé' }}</p></section>@endforeach
    <p>La confirmation crée votre projet. Elle n’ouvre aucun financement.</p>
    <form method="POST" action="{{ route('projects.draft.confirm', $draft) }}">@csrf<x-dg.button type="submit">Confirmer la création du projet</x-dg.button></form>
    <a class="dg-space-text-link" href="{{ route('projects.draft.show', [$draft, 'audience']) }}">Revoir mes réponses</a>
@else
<form method="POST" action="{{ route('projects.draft.update', [$draft, $step]) }}">@csrf
    @if ($step === 'audience')
        <p>Tout projet s’inscrit dans une ZUMRA. Choisissez une communauté dont vous êtes membre.</p>
        <label for="zumra_group_reference">Votre ZUMRA</label><select class="dg-input" id="zumra_group_reference" name="zumra_group_reference" required><option value="">Choisir une ZUMRA</option>@foreach ($groups as $group)<option value="{{ $group->public_reference }}" @selected(old('zumra_group_reference', $payload['zumra_group_reference'] ?? '') === $group->public_reference)>{{ $group->name }}</option>@endforeach</select>
        <label for="owner_type">Qui porte le projet ?</label><select class="dg-input" id="owner_type" name="owner_type"><option value="PERSON" @selected(old('owner_type', $payload['owner_type'] ?? '') === 'PERSON')>Je porte le projet</option><option value="GROUP" @selected(old('owner_type', $payload['owner_type'] ?? '') === 'GROUP')>La ZUMRA porte le projet</option></select>
        @if ($groups->isEmpty())<p>Vous n’êtes membre d’aucune ZUMRA pour le moment.</p><a class="dg-space-text-link" href="{{ route('zumra.index') }}">Découvrir les ZUMRA →</a>@endif
    @elseif (in_array($step, ['nom', 'resume', 'probleme', 'solution', 'beneficiaires'], true))
        @php([$key, $minimum, $maximum] = ['nom' => ['name', 5, 180], 'resume' => ['summary', 40, 1200], 'probleme' => ['problem', 40, 2400], 'solution' => ['proposed_solution', 40, 2400], 'beneficiaires' => ['beneficiaries', 20, 1200]][$step])
        <label for="{{ $key }}">Votre réponse</label><textarea class="dg-input" id="{{ $key }}" name="{{ $key }}" rows="5" minlength="{{ $minimum }}" maxlength="{{ $maximum }}" required>{{ old($key, $payload[$key] ?? '') }}</textarea><p class="dg-space-note">Au moins {{ $minimum }} caractères.</p>
        @if ($step === 'nom')<input type="hidden" name="source_need_reference" value="{{ old('source_need_reference', $payload['source_need_reference'] ?? '') }}">@endif
    @elseif ($step === 'logistique')
        <label for="domain">Domaine</label><select class="dg-input" id="domain" name="domain" required><option value="">Choisir</option>@foreach ($config['domains'] as $value => $label)<option value="{{ $value }}" @selected(old('domain', $payload['domain'] ?? '') === $value)>{{ $label }}</option>@endforeach</select>
        <label for="participation_mode">Mode de participation</label><select class="dg-input" id="participation_mode" name="participation_mode" required>@foreach (['PHYSICAL' => 'Sur place', 'DIGITAL' => 'À distance', 'HYBRID' => 'Sur place et à distance'] as $value => $label)<option value="{{ $value }}" @selected(old('participation_mode', $payload['participation_mode'] ?? '') === $value)>{{ $label }}</option>@endforeach</select>
        <label for="location">Lieu (facultatif)</label><x-dg.input id="location" :value="old('location', $payload['location'] ?? '')" maxlength="160" />
    @else
        @php($collections = $step === 'objectifs' ? ['objectives' => 'Objectifs'] : ['required_capabilities' => 'Savoir-faire nécessaires', 'required_resources' => 'Ressources nécessaires', 'risks' => 'Difficultés à anticiper'])
        @foreach ($collections as $key => $label)
            <section class="dg-space-section"><h2>{{ $label }}</h2>
            @php($rows = array_slice([...old($key, $payload[$key] ?? []), ''], 0, 20))
            @foreach ($rows as $index => $value)<label for="{{ $key }}-{{ $index }}">{{ $label }} · {{ $index + 1 }}</label><input class="dg-input" id="{{ $key }}-{{ $index }}" name="{{ $key }}[]" value="{{ $value }}">@endforeach
            </section>
        @endforeach
        <button class="dg-button dg-button--secondary" type="submit" name="_intent" value="add">Enregistrer ces lignes et en ajouter</button>
    @endif
    <div class="dg-space-section"><button class="dg-button dg-button--primary" type="submit" name="_intent" value="continue">Continuer</button> <button class="dg-button dg-button--secondary" type="submit" name="_intent" value="save_later" formnovalidate>Enregistrer et reprendre plus tard</button></div>
</form>
@endif
@if ($previousStep)<a class="dg-space-text-link" href="{{ route('projects.draft.show', [$draft, $previousStep]) }}">← Étape précédente</a>@endif
</div></x-layouts.member>
