<x-layouts.member title="Créer un événement" active="zumra" :wide="true">
    <div class="dg-event-space dg-event-space--form">
        <header class="dg-event-space__hero dg-event-space__hero--compact">
            <div>
                <a class="dg-event-space__back" href="{{ $backUrl }}">← Retour</a>
                <p class="dg-event-space__eyebrow">NOUVEL ÉVÉNEMENT</p>
                <h1>Organiser une rencontre utile.</h1>
                <p>Programmez une rencontre, un atelier ou une activité réelle pour {{ $organizerLabel }}.</p>
            </div>
        </header>

        @if ($errors->any())
            <div class="dg-event-errors" role="alert">
                <strong>Quelques informations sont à corriger.</strong>
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form class="dg-event-form" method="POST" action="{{ $storeUrl }}">
            @csrf
            <label>
                <span>Titre</span>
                <input type="text" name="title" minlength="3" maxlength="160" required value="{{ old('title') }}" placeholder="Ex. Atelier de préparation du projet">
            </label>

            <label>
                <span>Description</span>
                <textarea name="description" rows="6" minlength="10" maxlength="4000" required placeholder="Quel est le but de cette rencontre ?">{{ old('description') }}</textarea>
            </label>

            <div class="dg-event-form__grid">
                <label>
                    <span>Date et heure</span>
                    <input type="datetime-local" name="scheduled_at" required value="{{ old('scheduled_at') }}">
                </label>
                <label>
                    <span>Lieu</span>
                    <input type="text" name="location" maxlength="160" value="{{ old('location') }}" placeholder="Lieu ou indication utile">
                </label>
            </div>

            <fieldset>
                <legend>Qui peut voir cet événement ?</legend>
                <label class="dg-event-choice"><input type="radio" name="visibility" value="INTERNAL" {{ old('visibility', 'INTERNAL') === 'INTERNAL' ? 'checked' : '' }}> <span><strong>Membres</strong><small>Visible uniquement dans la communauté concernée.</small></span></label>
                <label class="dg-event-choice"><input type="radio" name="visibility" value="PUBLIC" {{ old('visibility') === 'PUBLIC' ? 'checked' : '' }}> <span><strong>Public</strong><small>Visible par toute personne connectée autorisée à consulter un événement public.</small></span></label>
            </fieldset>

            <div class="dg-event-form__actions">
                <a class="dg-event-button" href="{{ $backUrl }}">Annuler</a>
                <button class="dg-event-button dg-event-button--primary" type="submit">Créer l’événement</button>
            </div>
        </form>
    </div>
</x-layouts.member>
