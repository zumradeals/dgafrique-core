<x-layouts.member title="Proposer une mission" active="space"><div class="dg-space"><a class="dg-space-text-link" href="{{ $backUrl }}">← Revenir au contexte</a><p class="dg-space-eyebrow">UNE ACTION CONCRÈTE</p><h1>Que proposez-vous de réaliser ?</h1><p>{{ $contextLabel }}</p>
<form method="POST" action="{{ $storeUrl }}">@csrf
<div class="dg-profile-field"><label for="title">Nom de la mission</label><x-dg.input id="title" :value="old('title')" required minlength="5" maxlength="180" /></div>
<div class="dg-profile-field"><label for="description">Expliquez l’action proposée</label><textarea class="dg-input" id="description" name="description" rows="5" minlength="20" maxlength="4000" required>{{ old('description') }}</textarea></div>
<div class="dg-profile-field"><label for="expected_result">Quel résultat attendez-vous ?</label><textarea class="dg-input" id="expected_result" name="expected_result" rows="3" maxlength="2000">{{ old('expected_result') }}</textarea></div>
<label for="visibility">Visibilité</label><select class="dg-input" id="visibility" name="visibility" required>@foreach (\App\Models\Mission::VISIBILITY_LABELS as $value => $label)<option value="{{ $value }}" @selected(old('visibility', 'PRIVATE') === $value)>{{ $label }}</option>@endforeach</select>
<p class="dg-space-note">Vous enregistrez un brouillon. Sa proposition puis son ouverture sont des étapes distinctes.</p><x-dg.button type="submit">Enregistrer le brouillon de mission</x-dg.button>
</form></div></x-layouts.member>
