<x-layouts.member title="ZAHAB" active="space"><div class="dg-space"><a class="dg-space-text-link" href="{{ route('member.space') }}#mes-outils">← Mes outils</a><h1>Mon portefeuille ZAHAB.</h1>
<section class="dg-space-priority"><p>Solde disponible</p><h2>{{ number_format($balance, 0, ',', ' ') }} ZAHAB</h2></section>
<section class="dg-space-section"><h2>Historique des mouvements</h2>@forelse ($movements as $movement)<div class="dg-space-row"><span><strong>{{ $movement->direction === 'CREDIT' ? 'Crédit' : 'Débit' }}</strong><small>{{ $movement->occurred_at?->format('d/m/Y H:i') }}</small></span><strong>{{ number_format($movement->amount, 0, ',', ' ') }} ZAHAB</strong></div>@empty<p>Aucun mouvement enregistré.</p>@endforelse</section>
</div></x-layouts.member>
