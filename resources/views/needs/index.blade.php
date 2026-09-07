<x-layouts.member title="Besoins" active="needs"><div class="dg-space">
<header class="dg-space-heading"><div><p class="dg-space-eyebrow">FAIRE AVANCER UNE SITUATION</p><h1>Tout commence par un besoin.</h1></div><x-dg.button :href="route('needs.create')">Exprimer un besoin</x-dg.button></header>
<form method="GET" action="{{ route('needs.index') }}"><label for="q">Que recherchez-vous ?</label><x-dg.input id="q" :value="$searchTerm" /><x-dg.button type="submit">Rechercher</x-dg.button></form>
<a class="dg-space-text-link" href="{{ route('needs.index', ['mine' => 1]) }}">Mes besoins</a>
@forelse ($needs as $need)<a class="dg-space-row" href="{{ route('needs.show', $need) }}"><x-dg.icon name="need" /><span><strong>{{ $need->title }}</strong><small>{{ $configuration['categories'][$need->category] ?? 'Besoin' }}{{ $need->location ? ' · '.$need->location : '' }}</small></span><span aria-hidden="true">→</span></a>@empty<section class="dg-space-section"><h2>Aucun besoin à afficher.</h2><p>Vous pouvez exprimer le vôtre ou revenir à la liste complète.</p><a class="dg-space-text-link" href="{{ route('needs.index') }}">Voir tous les besoins</a></section>@endforelse
{{ $needs->links() }}
</div></x-layouts.member>
