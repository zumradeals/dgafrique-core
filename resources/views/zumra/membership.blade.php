<x-layouts.portal title="Adhésion ZUMRA — DG Afrique">
    <div class="membership-shell">
        <header class="membership-topbar"><a href="{{ route('zumra.index') }}">← ZUMRA</a><strong><i>Z</i> Programme ZUMRA</strong><a href="{{ route('member.space') }}">Mon espace</a></header>
        <main class="membership-content">
            @if (session('status'))<div class="alert success">{{ session('status') }}</div>@endif
            @if ($errors->any())<div class="alert danger">{{ $errors->first() }}</div>@endif

            <section class="membership-hero"><div><p class="eyebrow dark">ADHÉSION AU PROGRAMME</p><h1>{{ $configuration['title'] }}</h1><p>{{ $configuration['introduction'] }}</p></div><div class="membership-price"><strong>500 <small>FCFA</small></strong><span>ou 1 USD · une seule fois</span></div></section>

            @if ($membership)
                <section class="membership-state {{ strtolower($membership->status) }}"><span class="state-mark"></span><div><small>ÉTAT DU DOSSIER</small><h2>{{ $membership->status === 'PENDING_PAYMENT' ? $configuration['pending_payment_title'] : match($membership->status) {'ACTIVE' => 'Adhésion active', 'SUSPENDED' => 'Adhésion suspendue', 'CLOSED' => 'Adhésion fermée', default => $membership->status} }}</h2><p>{{ $membership->status === 'PENDING_PAYMENT' ? $configuration['pending_payment_help'] : 'Votre historique d’adhésion reste conservé.' }}</p><dl><div><dt>Charte acceptée</dt><dd>{{ $membership->accepted_charter_version }}</dd></div><div><dt>Dossier créé</dt><dd>{{ $membership->submitted_at->format('d/m/Y à H:i') }}</dd></div><div><dt>Paiement</dt><dd>{{ $membership->status === 'PENDING_PAYMENT' ? 'Non effectué' : 'Voir preuve' }}</dd></div></dl></div></section>
                @if ($membership->status === 'PENDING_PAYMENT')
                    <div class="payment-honesty">
                        @if ($configuration['payment_enabled'])
                            <strong>Paiement sécurisé · 500 FCFA</strong>
                            <p>L’activation intervient uniquement après vérification directe du paiement par DG Afrique. Un retour navigateur ne vaut jamais confirmation.</p>
                            <form method="POST" action="{{ route('zumra.payment.store') }}">@csrf<button type="submit">Payer mon adhésion</button></form>
                        @else
                            <strong>Aucun débit en attente</strong><p>{{ $configuration['payment_unavailable_notice'] }}</p><button disabled>Paiement temporairement indisponible</button>
                        @endif
                    </div>
                @endif
            @elseif ($charter)
                <div class="membership-grid">
                    <section class="charter-card"><div class="charter-heading"><div><small>CHARTE EN VIGUEUR</small><h2>{{ $charter->title }}</h2></div><span>Version {{ $charter->version }}</span></div><div class="charter-body">{!! nl2br(e($charter->body)) !!}</div></section>
                    <aside class="membership-commitment"><span class="card-kicker">VOTRE ENGAGEMENT</span><h2>Un seul paiement, une adhésion acquise.</h2><p>{{ $configuration['commitment_notice'] }}</p><ul><li>Demander à rejoindre une ZUMRA</li><li>Recevoir des invitations</li><li>Proposer une nouvelle ZUMRA</li><li>Accéder progressivement au matching</li></ul><form method="POST" action="{{ route('zumra.membership.store') }}">@csrf<input type="hidden" name="charter_id" value="{{ $charter->id }}"><label class="membership-check"><input type="checkbox" name="accept_charter" value="1" required><span>{{ $configuration['accept_label'] }}</span></label><button class="primary-button" type="submit">{{ $configuration['submit_label'] }}</button><small>Aucun paiement ne sera déclenché à cette étape.</small></form></aside>
                </div>
            @else
                <section class="membership-unavailable"><h1>Adhésion en préparation</h1><p>Aucune charte n’est actuellement publiée. Aucun dossier ni paiement ne peut être créé.</p></section>
            @endif
        </main>
    </div>
</x-layouts.portal>
