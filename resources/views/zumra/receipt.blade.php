<x-layouts.member title="Reçu adhésion ZUMRA" active="zumra" :wide="true">
    <div class="dg-zumra-membership dg-zumra-membership--receipt">
        <a class="dg-zumra-membership__back" href="{{ route('zumra.membership.show') }}">← Retour à mon adhésion ZUMRA</a>

        <article class="dg-zumra-receipt">
            <header class="dg-zumra-receipt__header">
                <div>
                    <p class="dg-zumra-eyebrow">PREUVE D’ADHÉSION · GAMAD</p>
                    <h1>Reçu d’adhésion ZUMRA</h1>
                    <p>Ce reçu correspond au paiement enregistré pour votre adhésion au Programme ZUMRA.</p>
                </div>
                <div class="dg-zumra-receipt__number">
                    <span>Reçu</span>
                    <strong>{{ $receipt->number }}</strong>
                </div>
            </header>

            <dl class="dg-zumra-receipt__grid">
                <div><dt>Montant</dt><dd>{{ number_format((int) $receipt->amount, 0, ',', ' ') }} {{ $receipt->currency }}</dd></div>
                <div><dt>Moyen</dt><dd>{{ $receipt->provider }}</dd></div>
                <div><dt>Objet</dt><dd>Adhésion Programme ZUMRA</dd></div>
                <div><dt>Date d’émission</dt><dd>{{ $receipt->issued_at?->format('d/m/Y H:i') }}</dd></div>
                <div><dt>Référence paiement</dt><dd>{{ $receipt->provider_reference }}</dd></div>
                <div><dt>Identité</dt><dd>{{ $receipt->core_identity_reference }}</dd></div>
            </dl>

            <footer class="dg-zumra-receipt__footer">
                <div>
                    <strong>Intégrité enregistrée</strong>
                    <p>Une empreinte d’intégrité est conservée avec ce reçu dans GAMAD.</p>
                </div>
                <x-dg.button href="{{ route('zumra.membership.show') }}" variant="secondary">Retour à mon adhésion</x-dg.button>
            </footer>
        </article>
    </div>
</x-layouts.member>
