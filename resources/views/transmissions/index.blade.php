<x-layouts.member title="Mes transmissions" active="space"><div class="dg-space">
<a class="dg-space-text-link" href="{{ route('member.space') }}#mes-outils">← Mes outils</a><h1>Mes transmissions</h1>
@php($labels = ['propositions_recues' => 'Propositions reçues', 'mes_demandes' => 'Mes demandes', 'je_me_suis_propose' => 'Mes propositions', 'je_transmets' => 'Je transmets', 'j_apprends' => 'J’apprends', 'terminees' => 'Terminées'])
@foreach ($labels as $key => $label)
<section class="dg-space-section"><h2>{{ $label }}</h2>
@forelse ($sections[$key] as $entry)
@php($item = $entry instanceof \App\Models\Transmission ? $entry : $entry->transmission)
@if ($item)<a class="dg-space-row" href="{{ route('transmissions.show', $item) }}"><span><strong>{{ $item->capability_label }}</strong><small>{{ $item::STATUS_LABELS[$item->status] ?? 'État non précisé' }}</small></span><span aria-hidden="true">→</span></a>@endif
@empty<p>Aucun élément dans cette rubrique pour le moment.</p>@endforelse
</section>@endforeach
</div></x-layouts.member>
