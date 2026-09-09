<x-layouts.member title="Paiement adhésion ZUMRA" active="zumra" :wide="true">
    @php
        $isActive = $membership->status === \App\Models\ZumraProgramMembership::STATUS_ACTIVE;
        $completed = $payment->status === \App\Models\ZumraPayment::STATUS_COMPLETED;
    @endphp

    <div class="dg-zumra-membership dg-zumra-membership--status">
        <a class="dg-zumra-membership__back" href="{{ route('zumra.membership.show') }}">← Retour à mon adhésion ZUMRA</a>

        <section class="dg-zumra-membership__card {{ $isActive ? 'dg-zumra-membership__card--success' : '' }}">
            <div class="dg-zumra-membership__status-icon" aria-hidden="true">{{ $isActive ? '✓' : '…' }}</div>
            <div>
                <p class="dg-zumra-eyebrow">PAIEMENT D’ADHÉSION</p>
                @if ($isActive)
                    <h1>Votre adhésion ZUMRA est active.</h1>
                    <p>Le paiement a été confirmé et votre adhésion au Programme ZUMRA est maintenant active.</p>
                    <div class="dg-zumra-membership__actions">
                        <x-dg.button href="{{ route('zumra.groups.create') }}" variant="solar">Créer une ZUMRA →</x-dg.button>
                        @if ($receipt)
                            <x-dg.button href="{{ route('zumra.payment.receipt', $receipt) }}" variant="secondary">Voir mon reçu</x-dg.button>
                        @endif
                    </div>
                @elseif ($verificationUnavailable)
                    <h1>La vérification du paiement est momentanément indisponible.</h1>
                    <p>Aucun résultat incertain n’est transformé en adhésion active. Revenez à votre adhésion pour vérifier son état plus tard.</p>
                    <x-dg.button href="{{ route('zumra.membership.show') }}" variant="secondary">Voir mon adhésion</x-dg.button>
                @elseif ($completed)
                    <h1>Paiement reçu, activation en cours de vérification.</h1>
                    <p>Le prestataire indique que le paiement est terminé, mais l’adhésion n’est pas encore active selon les règles d’activation du système.</p>
                    <x-dg.button href="{{ route('zumra.membership.show') }}" variant="secondary">Voir mon adhésion</x-dg.button>
                @elseif ($payment->status === \App\Models\ZumraPayment::STATUS_FAILED)
                    <h1>Le paiement n’a pas abouti.</h1>
                    <p>Votre adhésion n’a pas été activée. Vous pouvez revenir à la page d’adhésion pour réessayer.</p>
                    <x-dg.button href="{{ route('zumra.membership.show') }}" variant="solar">Retour au paiement</x-dg.button>
                @elseif ($payment->status === \App\Models\ZumraPayment::STATUS_CANCELLED)
                    <h1>Le paiement a été annulé.</h1>
                    <p>Aucun paiement confirmé n’a été appliqué à votre adhésion.</p>
                    <x-dg.button href="{{ route('zumra.membership.show') }}" variant="solar">Retour à mon adhésion</x-dg.button>
                @else
                    <h1>Votre paiement est encore en cours de traitement.</h1>
                    <p>GAMAD n’active jamais une adhésion tant que la confirmation requise n’est pas établie.</p>
                    <x-dg.button href="{{ route('zumra.membership.show') }}" variant="secondary">Voir mon adhésion</x-dg.button>
                @endif
            </div>
        </section>
    </div>
</x-layouts.member>
