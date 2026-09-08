<x-layouts.member title="Mes missions" active="space"><div class="dg-space">
<a class="dg-space-text-link" href="{{ route('member.space') }}#mes-outils">← Mes outils</a><h1>Mes missions</h1>
@php($labels = ['a_decider' => 'À décider', 'mes_propositions' => 'Mes propositions', 'invitations' => 'Invitations reçues', 'je_me_suis_propose' => 'Mes candidatures', 'mes_engagements' => 'Mes engagements', 'bloquees' => 'À débloquer', 'a_soumettre' => 'À soumettre', 'a_valider' => 'À valider', 'terminees' => 'Terminées'])
@foreach ($labels as $key => $label)
<section class="dg-space-section"><h2>{{ $label }}</h2>
@forelse ($sections[$key] as $entry)
@php($item = $entry instanceof \App\Models\Mission ? $entry : $entry->mission)
@if ($item)<a class="dg-space-row" href="{{ route('missions.show', $item) }}"><span><strong>{{ $item->title }}</strong><small>{{ $item::STATUS_LABELS[$item->status] ?? 'État non précisé' }}</small></span><span aria-hidden="true">→</span></a>@endif
@empty<p>Aucun élément dans cette rubrique pour le moment.</p>@endforelse
</section>@endforeach
</div></x-layouts.member>
