<x-layouts.member :title="$organization->name" active="space"><div class="dg-space"><a class="dg-space-text-link" href="{{ route('member.space') }}">← Mon espace</a><p class="dg-space-eyebrow">UNE STRUCTURE AVEC QUI AGIR</p><h1>{{ $organization->name }}</h1><p class="dg-space-description">{{ $organization->description }}</p>
@if ($isMember)<p>Vous faites partie de cette organisation.</p>@endif
</div></x-layouts.member>
