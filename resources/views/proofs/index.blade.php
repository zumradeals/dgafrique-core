<x-layouts.member title="Mes preuves de réalisation" active="space"><div class="dg-space">
<a class="dg-space-text-link" href="{{ route('member.space') }}#mes-outils">← Mes outils</a><h1>Mes preuves de réalisation</h1>
@php($labels = ['temoignages_demandes' => 'Témoignages demandés', 'mes_preuves' => 'Mes preuves', 'a_reconnaitre' => 'À reconnaître'])
@foreach ($labels as $key => $label)
<section class="dg-space-section"><h2>{{ $label }}</h2>
@forelse ($sections[$key] as $entry)
@php($item = $entry instanceof \App\Models\Proof ? $entry : $entry->proof)
@if ($item)<a class="dg-space-row" href="{{ route('proofs.show', $item) }}"><span><strong>{{ $item->title }}</strong><small>{{ $item::STATUS_LABELS[$item->status] ?? 'État non précisé' }}</small></span><span aria-hidden="true">→</span></a>@endif
@empty<p>Aucun élément dans cette rubrique pour le moment.</p>@endforelse
</section>@endforeach
</div></x-layouts.member>
