<x-layouts.member :title="$profile->discovery_display_name" active="people"><div class="dg-space"><a class="dg-space-text-link" href="{{ route('people.index') }}">← Les personnes</a><h1>{{ $profile->discovery_display_name }}</h1><p>{{ $profile->discovery_bio }}</p>
<section class="dg-space-section"><h2>Savoir-faire et envies partagés</h2>@forelse ($profile->capabilityStatements as $statement)<div class="dg-space-row"><strong>{{ $statement->label }}</strong></div>@empty<p>Aucune capacité partagée pour le moment.</p>@endforelse</section>
</div></x-layouts.member>
