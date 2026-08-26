<x-layouts.portal title="Reçu d’adhésion — DG Afrique">
    <div class="membership-shell zd-flow zd-receipt"><header class="membership-topbar"><a href="{{ route('zumra.membership.show') }}">← Adhésion</a><strong><i>Z</i> Reçu d’adhésion</strong><button onclick="window.print()">Imprimer</button></header>
    <main class="membership-content"><section class="charter-card"><div class="charter-heading"><div><small>REÇU OFFICIEL</small><h1>{{ $receipt->number }}</h1></div><span>PAYÉ</span></div>
        <dl><div><dt>Objet</dt><dd>Adhésion unique au Programme ZUMRA</dd></div><div><dt>Montant</dt><dd>{{ number_format($receipt->amount, 0, ',', ' ') }} {{ $receipt->currency }}</dd></div><div><dt>Date</dt><dd>{{ $receipt->issued_at->format('d/m/Y à H:i') }}</dd></div><div><dt>Référence de paiement</dt><dd>{{ $receipt->provider_reference }}</dd></div><div><dt>Référence membre</dt><dd>{{ $receipt->core_identity_reference }}</dd></div></dl>
        <p><small>Empreinte d’intégrité : {{ $receipt->integrity_hash }}</small></p>
    </section></main></div>
</x-layouts.portal>
