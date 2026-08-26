<x-layouts.portal title="Confirmation du paiement — DG Afrique">
    <div class="membership-shell zd-flow"><header class="membership-topbar"><a href="{{ route('zumra.membership.show') }}">← Adhésion</a><strong><i>Z</i> Programme ZUMRA</strong><a href="{{ route('member.space') }}">Mon espace</a></header>
    <main class="membership-content"><section class="membership-state {{ strtolower($membership->status) }}"><span class="state-mark"></span><div><small>CONFIRMATION SÉCURISÉE</small>
        @if ($membership->status === 'ACTIVE' && $receipt)
            <h1>Votre adhésion est active</h1><p>Le paiement de 500 FCFA a été confirmé directement auprès du prestataire. Votre reçu est disponible.</p>
            <a class="primary-button" href="{{ route('zumra.payment.receipt', $receipt) }}">Consulter mon reçu</a>
        @elseif ($verificationUnavailable)
            <h1>Vérification en cours</h1><p>Nous ne pouvons pas encore confirmer le paiement. Votre adhésion reste protégée et aucun retour navigateur ne peut l’activer.</p>
            <a class="primary-button" href="{{ route('zumra.payment.return') }}">Vérifier à nouveau</a>
        @else
            <h1>Paiement non confirmé</h1><p>État actuel : {{ $payment?->status ?? 'aucune tentative enregistrée' }}. Votre adhésion n’est pas encore active.</p>
            <a class="primary-button" href="{{ route('zumra.membership.show') }}">Retour au dossier</a>
        @endif
    </div></section></main></div>
</x-layouts.portal>
