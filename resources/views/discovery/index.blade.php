<x-layouts.member title="Personnes" active="people"><div class="dg-space"><p class="dg-space-eyebrow">FAIRE CONNAISSANCE</p><h1>Des personnes avec qui agir.</h1><p>Découvrez les personnes qui ont choisi de partager leur présentation.</p>
<form method="GET" action="{{ route('people.index') }}"><label for="q">Un savoir-faire ou une activité</label><x-dg.input id="q" :value="$term" maxlength="100" /><x-dg.button type="submit">Rechercher</x-dg.button></form>
@forelse ($profiles as $person)<a class="dg-space-row" href="{{ route('people.show', $person->discovery_reference) }}"><x-dg.icon name="space" /><span><strong>{{ $person->discovery_display_name }}</strong><small>{{ $person->discovery_bio }}</small></span><span aria-hidden="true">→</span></a>@empty<section class="dg-space-section"><h2>Aucune personne à afficher pour cette recherche.</h2><p>Essayez un autre terme ou revenez découvrir le réseau plus tard.</p></section>@endforelse
{{ $profiles->links() }}
</div></x-layouts.member>
