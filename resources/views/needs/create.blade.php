<x-layouts.member title="Exprimer un besoin" active="needs">
<div class="dg-space">
    <a class="dg-space-text-link" href="{{ route('member.space') }}">← Mon espace</a>
    <p class="dg-space-eyebrow">UN PREMIER PAS CONCRET</p><h1>De quoi avez-vous besoin ?</h1>
    <p>Expliquez ce qui vous aiderait à avancer. Vous choisissez qui pourra le voir.</p>
    <form method="POST" action="{{ route('needs.store') }}">
        @csrf
        <section class="dg-space-section">
            <label for="title">Donnez un titre à votre besoin</label>
            <x-dg.input id="title" :value="old('title')" required minlength="5" maxlength="180" placeholder="Par exemple : apprendre à entretenir notre jardin" />
            <div class="dg-profile-field"><label for="context">Expliquez la situation et ce qui manque</label><textarea class="dg-input" id="context" name="context" rows="5" required minlength="40" maxlength="3000" aria-describedby="context-help">{{ old('context') }}</textarea><p id="context-help" class="dg-space-note">Au moins 40 caractères pour aider les autres à comprendre.</p></div>
            <label for="category">Quel domaine concerne ce besoin ?</label><select class="dg-input" id="category" name="category" required><option value="">Choisir un domaine</option>@foreach ($configuration['categories'] as $value => $label)<option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>@endforeach</select>
            <div class="dg-profile-field"><label for="capability_label">Un savoir-faire recherché (facultatif)</label><x-dg.input id="capability_label" :value="old('capability_label')" maxlength="200" /></div>
        </section>
        <section class="dg-space-section"><h2>Qui porte ce besoin ?</h2>
            <label for="owner_type">Porteur du besoin</label><select class="dg-input" id="owner_type" name="owner_type" required>@foreach (['PERSON' => 'Moi-même', 'GROUP' => 'Une ZUMRA dont je fais partie', 'PROJECT' => 'Un projet auquel je participe'] as $value => $label)<option value="{{ $value }}" @selected(old('owner_type', $preselectedProject ? 'PROJECT' : ($preselectedGroup ? 'GROUP' : 'PERSON')) === $value)>{{ $label }}</option>@endforeach</select>
            @if ($groups->isNotEmpty())<div class="dg-profile-field"><label for="group_reference">ZUMRA concernée, si vous avez choisi une ZUMRA</label><select class="dg-input" id="group_reference" name="group_reference"><option value="">Choisir une ZUMRA</option>@foreach ($groups as $group)<option value="{{ $group->public_reference }}" @selected(old('group_reference', $preselectedGroup?->public_reference) === $group->public_reference)>{{ $group->name }}</option>@endforeach</select></div>@else<p class="dg-space-note">Vous n’appartenez pas encore à une ZUMRA active.</p>@endif
            @if ($projects->isNotEmpty())<div class="dg-profile-field"><label for="project_reference">Projet concerné, si vous avez choisi un projet</label><select class="dg-input" id="project_reference" name="project_reference"><option value="">Choisir un projet</option>@foreach ($projects as $project)<option value="{{ $project->public_reference }}" @selected(old('project_reference', $preselectedProject?->public_reference) === $project->public_reference)>{{ $project->name }}</option>@endforeach</select></div>@endif
        </section>
        <section class="dg-space-section"><h2>Comment et avec qui avancer ?</h2>
            <label for="collaboration_mode">Mode de collaboration</label><select class="dg-input" id="collaboration_mode" name="collaboration_mode" required>@foreach (['ANY' => 'Je suis ouvert aux possibilités', 'LOCAL' => 'Sur place', 'REMOTE' => 'À distance', 'HYBRID' => 'Sur place et à distance'] as $value => $label)<option value="{{ $value }}" @selected(old('collaboration_mode', 'ANY') === $value)>{{ $label }}</option>@endforeach</select>
            <div class="dg-profile-field"><label for="location">Lieu (facultatif)</label><x-dg.input id="location" :value="old('location')" maxlength="160" /></div>
            <label for="visibility">Qui pourra découvrir ce besoin ?</label><select class="dg-input" id="visibility" name="visibility" required>@foreach (['PRIVATE' => 'Accès privé', 'GROUP' => 'La ZUMRA concernée', 'PROGRAM' => 'Les membres du Programme ZUMRA', 'PUBLIC' => 'Tout le monde, même sans compte'] as $value => $label)<option value="{{ $value }}" @selected(old('visibility', 'PRIVATE') === $value)>{{ $label }}</option>@endforeach</select>
            <p class="dg-space-note">Un besoin porté par une ZUMRA peut nécessiter la décision de ses responsables avant publication.</p>
        </section>
        <x-dg.button type="submit">Enregistrer mon besoin</x-dg.button>
    </form>
</div></x-layouts.member>
