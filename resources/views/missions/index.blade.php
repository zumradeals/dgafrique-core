<x-layouts.member title="Missions" active="space"><div class="dg-space"><a class="dg-space-text-link" href="{{ route('member.space') }}#mes-outils">← Mes outils</a><h1>Des missions pour agir.</h1><a class="dg-space-text-link" href="{{ route('missions.index', ['scope' => 'mine']) }}">Retrouver mes missions →</a>
<form method="GET" action="{{ route('missions.index') }}"><label for="q">Rechercher une mission</label><x-dg.input id="q" :value="$searchTerm" /><x-dg.button type="submit">Rechercher</x-dg.button></form>
@forelse ($missionsPage as $mission)<a class="dg-space-row" href="{{ route('missions.show', $mission) }}"><span><strong>{{ $mission->title }}</strong><small>{{ $mission::STATUS_LABELS[$mission->status] ?? 'État non précisé' }}</small></span><span aria-hidden="true">→</span></a>@empty<p>Aucune mission ne correspond à cette recherche.</p>@endforelse
{{ $missionsPage->links() }}
</div></x-layouts.member>
