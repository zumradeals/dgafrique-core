<x-layouts.portal title="Ma Carte ZUMRA — DG Afrique">
    @php
        $labels = ['ACTIVE' => 'Active', 'SUSPENDED' => 'Suspendue', 'CLOSED' => 'Fermée', 'REVOKED' => 'Révoquée', 'PENDING_PAYMENT' => 'Paiement à finaliser', 'NOT_MEMBER' => 'Non délivrée'];
        $initial = mb_strtoupper(mb_substr($identity->label, 0, 1));
    @endphp
    <div class="zumra-card-page">
        <header class="membership-topbar"><a href="{{ route('zumra.index') }}">← ZUMRA</a><strong><i>Z</i> Carte ZUMRA</strong><a href="{{ route('member.space') }}">Mon espace</a></header>
        <main class="zumra-card-content">
            <section class="zumra-card-intro"><div><p class="eyebrow dark">ATTESTATION D’ADHÉSION</p><h1>Votre engagement, vérifiable simplement.</h1><p>La Carte ZUMRA représente votre adhésion au programme. Elle n’est ni une carte bancaire, ni un portefeuille, ni une preuve de contribution.</p></div><span class="card-state state-{{ strtolower($state) }}">{{ $labels[$state] ?? $state }}</span></section>

            @if ($card)
                <div class="zumra-card-grid">
                    <article class="zumra-digital-card state-{{ strtolower($state) }}">
                        <div class="digital-card-head"><strong><i>Z</i> ZUMRA</strong><span>DG Afrique</span></div>
                        <div class="digital-card-person"><span class="digital-avatar">{{ $initial }}</span><div><small>MEMBRE DU PROGRAMME</small><h2>{{ $card->holder_label }}</h2><p>{{ $card->country_code ?: 'Pays non renseigné' }}</p></div></div>
                        <dl><div><dt>Référence membre</dt><dd>{{ $card->core_identity_reference }}</dd></div><div><dt>Adhésion depuis</dt><dd>{{ $membership->activated_at->format('d/m/Y') }}</dd></div><div><dt>État</dt><dd>{{ $labels[$state] ?? $state }}</dd></div></dl>
                        <div class="digital-card-foot"><span>{{ $card->public_reference }}</span><small>Formation · Travail · Adoration</small></div>
                    </article>

                    <aside class="zumra-card-proof">
                        <div class="qr-frame"><canvas width="190" height="190" data-zumra-qr="{{ $verificationUrl }}" aria-label="QR de vérification de la Carte ZUMRA"></canvas><noscript>Activez JavaScript pour afficher le QR.</noscript></div>
                        <div><span class="card-kicker">VÉRIFICATION PUBLIQUE</span><h2>Une preuve, pas une promesse.</h2><p>Le QR confirme l’existence de la carte et son état actuel. Une suspension ou une révocation apparaît immédiatement lors de la vérification.</p><a href="{{ $verificationUrl }}" target="_blank" rel="noopener">Ouvrir la vérification →</a></div>
                    </aside>
                </div>
                <section class="card-privacy-note"><strong>Données maîtrisées</strong><p>La vérification publique ne révèle ni téléphone, ni capacités, ni besoins, ni paiements, ni contributions. Aucune appartenance à une ZUMRA particulière n’est inventée.</p><button type="button" onclick="window.print()">Imprimer ma carte</button></section>
            @else
                <section class="membership-unavailable"><h1>Carte non délivrée</h1><p>{{ $state === 'PENDING_PAYMENT' ? 'Finalisez votre adhésion pour recevoir votre Carte ZUMRA.' : 'Une adhésion active est nécessaire pour recevoir cette carte.' }}</p><a class="primary-button link-button" href="{{ route('zumra.membership.show') }}">Voir mon adhésion</a></section>
            @endif
        </main>
    </div>
</x-layouts.portal>
