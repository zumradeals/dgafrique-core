<x-layouts.member title="Opportunités" active="space"><div class="dg-space">
<a class="dg-space-text-link" href="{{ route('member.space') }}#mes-outils">← Mes outils</a><h1>Des possibilités pour vous.</h1>
<p>Chaque proposition explique son lien avec votre situation.</p>
@forelse ($opportunities as $opportunity)
<article class="dg-space-section"><h2>{{ $opportunity['title'] }}</h2><ul>@foreach ($opportunity['reasons'] as $reason)<li>{{ $reason }}</li>@endforeach</ul><x-dg.button :href="route('missions.show', $opportunity['reference'])">Découvrir la mission</x-dg.button></article>
@empty<section class="dg-space-section"><h2>Aucune opportunité proposée pour le moment.</h2><p>Les possibilités dépendent des missions disponibles, de vos capacités et de vos choix d’orientation.</p><a class="dg-space-text-link" href="{{ route('member.profile.edit') }}">Retrouver mes informations et mes choix →</a></section>@endforelse
</div></x-layouts.member>
