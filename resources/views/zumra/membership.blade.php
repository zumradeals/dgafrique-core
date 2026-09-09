<x-layouts.member
    :title="$configuration['title'] ?? 'Rejoindre le Programme ZUMRA'"
    active="zumra"
    :wide="true"
>
    @php
        $status = $membership?->status;
        $amountLabel = number_format($membershipAmount, 0, ',', ' ').' F CFA';
        $hasEnoughZahab = $zahabBalance >= $membershipAmount;
    @endphp

    <div class="dg-zumra-membership">
        <a class="dg-zumra-membership__back" href="{{ route('zumra.index') }}">← Retour au Carrefour ZUMRA</a>

        <header class="dg-zumra-membership__hero">
            <div>
                <p class="dg-zumra-eyebrow">PROGRAMME ZUMRA · ENGAGEMENT · COMMUNAUTÉ</p>
                <h1>{{ $configuration['title'] ?? 'Rejoindre le Programme ZUMRA' }}</h1>
                <p>{{ $configuration['introduction'] ?? 'Un engagement unique pour apprendre, transmettre et construire avec d’autres.' }}</p>
            </div>
            <div class="dg-zumra-membership__principle">
                <strong>L’adhésion au Programme ZUMRA est distincte de votre compte GAMAD.</strong>
                <span>Elle vous ouvre la possibilité de créer une ZUMRA ou de participer à sa gouvernance. Vous pouvez continuer à utiliser GAMAD sans cette adhésion.</span>
            </div>
        </header>

        <div class="dg-zumra-membership__layout">
            <main class="dg-zumra-membership__main">
                @if ($status === \App\Models\ZumraProgramMembership::STATUS_ACTIVE)
                    <section class="dg-zumra-membership__card dg-zumra-membership__card--success">
                        <div class="dg-zumra-membership__status-icon" aria-hidden="true">✓</div>
                        <div>
                            <p class="dg-zumra-eyebrow">ADHÉSION ACTIVE</p>
                            <h2>Vous pouvez maintenant faire naître une ZUMRA.</h2>
                            <p>Votre adhésion au Programme ZUMRA est active. Vous pouvez créer une communauté ou poursuivre vos engagements existants.</p>
                            <div class="dg-zumra-membership__actions">
                                <x-dg.button href="{{ route('zumra.groups.create') }}" variant="solar">Créer une ZUMRA →</x-dg.button>
                                <x-dg.button href="{{ route('zumra.index') }}" variant="secondary">Explorer les ZUMRA</x-dg.button>
                            </div>
                        </div>
                    </section>

                    @if ($receipt)
                        <section class="dg-zumra-membership__card">
                            <p class="dg-zumra-eyebrow">REÇU D’ADHÉSION</p>
                            <h2>Votre preuve de paiement est disponible.</h2>
                            <p>Reçu {{ $receipt->number }} · {{ number_format((int) $receipt->amount, 0, ',', ' ') }} {{ $receipt->currency }}</p>
                            <x-dg.button href="{{ route('zumra.payment.receipt', $receipt) }}" variant="secondary">Voir mon reçu</x-dg.button>
                        </section>
                    @endif
                @elseif ($status === \App\Models\ZumraProgramMembership::STATUS_PENDING_PAYMENT)
                    <section class="dg-zumra-membership__card">
                        <p class="dg-zumra-eyebrow">DOSSIER PRÊT</p>
                        <h2>{{ $configuration['pending_payment_title'] ?? 'Votre dossier est prêt' }}</h2>
                        <p>{{ $configuration['pending_payment_help'] ?? 'Finalisez le paiement unique pour activer votre adhésion.' }}</p>

                        <div class="dg-zumra-membership__amount">
                            <span>Adhésion unique</span>
                            <strong>{{ $amountLabel }}</strong>
                        </div>

                        @if ($configuration['payment_enabled'] ?? false)
                            <div class="dg-zumra-membership__payment-grid">
                                <section class="dg-zumra-membership__payment-option">
                                    <p class="dg-zumra-eyebrow">WALLET ZAHAB</p>
                                    <h3>Payer avec mon Wallet ZAHAB</h3>
                                    <p>Solde disponible : <strong>{{ number_format($zahabBalance, 0, ',', ' ') }} XOF</strong></p>
                                    @if ($hasEnoughZahab)
                                        <form method="POST" action="{{ route('zumra.payment.zahab.store') }}">
                                            @csrf
                                            <x-dg.button type="submit" variant="solar">Payer {{ $amountLabel }} avec mon Wallet</x-dg.button>
                                        </form>
                                    @else
                                        <p class="dg-zumra-membership__warning">Votre solde ZAHAB est insuffisant pour ce paiement.</p>
                                        <x-dg.button href="{{ route('zahab.wallet.person') }}" variant="secondary">Voir mon Wallet</x-dg.button>
                                    @endif
                                </section>

                                <section class="dg-zumra-membership__payment-option">
                                    <p class="dg-zumra-eyebrow">PAIEMENT EXTERNE</p>
                                    <h3>Payer par le prestataire sécurisé</h3>
                                    <p>Vous serez redirigé vers la page de paiement, puis ramené dans GAMAD après vérification.</p>
                                    <form method="POST" action="{{ route('zumra.payment.store') }}">
                                        @csrf
                                        <x-dg.button type="submit" variant="secondary">Continuer vers le paiement</x-dg.button>
                                    </form>
                                </section>
                            </div>
                        @else
                            <div class="dg-zumra-membership__unavailable">
                                <strong>Paiement momentanément indisponible.</strong>
                                <p>{{ $configuration['payment_unavailable_notice'] ?? 'Le paiement est temporairement indisponible. Aucun débit ne sera effectué.' }}</p>
                            </div>
                        @endif
                    </section>
                @elseif ($status === \App\Models\ZumraProgramMembership::STATUS_SUSPENDED)
                    <section class="dg-zumra-membership__card">
                        <p class="dg-zumra-eyebrow">ADHÉSION SUSPENDUE</p>
                        <h2>Votre adhésion ZUMRA est actuellement suspendue.</h2>
                        <p>La création d’une nouvelle ZUMRA reste indisponible tant que cette situation n’est pas régularisée.</p>
                        <x-dg.button href="{{ route('zumra.index') }}" variant="secondary">Retour au Carrefour</x-dg.button>
                    </section>
                @elseif ($status === \App\Models\ZumraProgramMembership::STATUS_CLOSED)
                    <section class="dg-zumra-membership__card">
                        <p class="dg-zumra-eyebrow">ADHÉSION CLOSE</p>
                        <h2>Cette adhésion ZUMRA est clôturée.</h2>
                        <p>Elle ne peut pas être recréée automatiquement depuis cette page.</p>
                        <x-dg.button href="{{ route('zumra.index') }}" variant="secondary">Retour au Carrefour</x-dg.button>
                    </section>
                @else
                    <section class="dg-zumra-membership__card">
                        <p class="dg-zumra-eyebrow">AVANT DE CRÉER UNE ZUMRA</p>
                        <h2>Adhérez au Programme ZUMRA.</h2>
                        <p>{{ $configuration['commitment_notice'] ?? 'L’adhésion est acquise une seule fois. Les contributions mensuelles restent entièrement facultatives.' }}</p>

                        @if ($charter)
                            <article class="dg-zumra-membership__charter">
                                <div class="dg-zumra-membership__charter-heading">
                                    <div>
                                        <span>Charte ZUMRA</span>
                                        <h3>{{ $charter->title }}</h3>
                                    </div>
                                    <small>Version {{ $charter->version }}</small>
                                </div>
                                <div class="dg-zumra-membership__charter-body">{{ $charter->body }}</div>
                            </article>

                            <form class="dg-zumra-membership__join-form" method="POST" action="{{ route('zumra.membership.store') }}">
                                @csrf
                                <input type="hidden" name="charter_id" value="{{ $charter->id }}">
                                <label class="dg-zumra-membership__accept" for="accept_charter">
                                    <input id="accept_charter" name="accept_charter" type="checkbox" value="1" required>
                                    <span>
                                        <strong>{{ $configuration['accept_label'] ?? 'J’ai lu et j’accepte cette charte.' }}</strong>
                                        <small>Cette acceptation est enregistrée avec la version de la charte que vous venez de lire.</small>
                                    </span>
                                </label>
                                <x-dg.button type="submit" variant="solar">{{ $configuration['submit_label'] ?? 'Préparer mon adhésion' }} →</x-dg.button>
                            </form>
                        @else
                            <div class="dg-zumra-membership__unavailable">
                                <strong>L’adhésion n’est pas encore ouverte.</strong>
                                <p>Aucune charte ZUMRA publiée n’est disponible pour le moment. Aucun dossier ne peut être créé sans charte.</p>
                            </div>
                        @endif
                    </section>
                @endif
            </main>

            <aside class="dg-zumra-membership__aside">
                <section>
                    <p class="dg-zumra-eyebrow">CE QUE CELA OUVRE</p>
                    <h2>De membre GAMAD à bâtisseur d’une ZUMRA.</h2>
                    <ol>
                        <li><span>1</span><div><strong>Adhérer au Programme</strong><small>Vous acceptez la charte ZUMRA et activez votre adhésion selon les règles en vigueur.</small></div></li>
                        <li><span>2</span><div><strong>Créer ou rejoindre</strong><small>Une adhésion active vous permet de faire naître une ZUMRA ou d’assumer des responsabilités.</small></div></li>
                        <li><span>3</span><div><strong>Construire dans son monde</strong><small>Projet principal, besoins, actions et contributions prennent ensuite forme dans l’espace ZUMRA.</small></div></li>
                    </ol>
                </section>

                <section class="dg-zumra-membership__aside-note">
                    <strong>Votre compte GAMAD reste indépendant.</strong>
                    <p>Ne pas adhérer au Programme ZUMRA ne vous retire pas votre compte ni les autres fonctions générales de GAMAD.</p>
                </section>

                @if ($isAdministrator)
                    <section class="dg-zumra-membership__admin-note">
                        <strong>Compte administrateur</strong>
                        <p>Votre statut administrateur n’active pas silencieusement une adhésion personnelle : les mêmes états restent visibles et traçables.</p>
                    </section>
                @endif
            </aside>
        </div>
    </div>
</x-layouts.member>
