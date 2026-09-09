<x-layouts.member
    :title="$configuration['title'] ?? 'Rejoindre le Programme ZUMRA'"
    active="zumra"
    :wide="true"
>
    @php
        $status = $membership?->status;
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
                <strong>L’adhésion au Programme ZUMRA est gratuite et distincte de votre compte GAMAD.</strong>
                <span>Elle repose sur l’acceptation de la charte ZUMRA. Aucun paiement n’est demandé pour devenir membre du Programme et pouvoir créer une ZUMRA.</span>
            </div>
        </header>

        <div class="dg-zumra-membership__layout">
            <main class="dg-zumra-membership__main">
                @if ($status === \App\Models\ZumraProgramMembership::STATUS_ACTIVE)
                    <section class="dg-zumra-membership__card dg-zumra-membership__card--success">
                        <div class="dg-zumra-membership__status-icon" aria-hidden="true">✓</div>
                        <div>
                            <p class="dg-zumra-eyebrow">ADHÉSION GRATUITE ACTIVE</p>
                            <h2>Vous pouvez maintenant faire naître une ZUMRA.</h2>
                            <p>Votre engagement au Programme ZUMRA est actif. Vous pouvez créer une communauté ou poursuivre vos engagements existants.</p>
                            <div class="dg-zumra-membership__actions">
                                <x-dg.button href="{{ route('zumra.groups.create') }}" variant="solar">Créer une ZUMRA →</x-dg.button>
                                <x-dg.button href="{{ route('zumra.index') }}" variant="secondary">Explorer les ZUMRA</x-dg.button>
                            </div>
                        </div>
                    </section>

                    @if ($receipt)
                        <section class="dg-zumra-membership__card">
                            <p class="dg-zumra-eyebrow">HISTORIQUE</p>
                            <h2>Un ancien reçu d’adhésion est conservé.</h2>
                            <p>Reçu {{ $receipt->number }} · {{ number_format((int) $receipt->amount, 0, ',', ' ') }} {{ $receipt->currency }}</p>
                            <x-dg.button href="{{ route('zumra.payment.receipt', $receipt) }}" variant="secondary">Voir ce reçu</x-dg.button>
                        </section>
                    @endif
                @elseif ($status === \App\Models\ZumraProgramMembership::STATUS_PENDING_PAYMENT)
                    <section class="dg-zumra-membership__card">
                        <p class="dg-zumra-eyebrow">ANCIEN DOSSIER À ACTIVER</p>
                        <h2>Votre adhésion peut maintenant être activée gratuitement.</h2>
                        <p>Le Programme ZUMRA ne demande plus de paiement d’adhésion. Il suffit d’accepter la charte actuellement publiée pour devenir membre actif.</p>
                        <p class="dg-zumra-membership__warning">L’ancienne action « Payer avec mon Wallet ZAHAB » n’est plus nécessaire : aucune somme ne sera débitée pour activer votre adhésion.</p>

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
                                <label class="dg-zumra-membership__accept" for="accept_charter_pending">
                                    <input id="accept_charter_pending" name="accept_charter" type="checkbox" value="1" required>
                                    <span>
                                        <strong>J’ai lu et j’accepte cette charte.</strong>
                                        <small>Votre acceptation sera enregistrée avec cette version et activera immédiatement votre adhésion gratuite.</small>
                                    </span>
                                </label>
                                <x-dg.button type="submit" variant="solar">Activer gratuitement mon adhésion →</x-dg.button>
                            </form>
                        @else
                            <div class="dg-zumra-membership__unavailable">
                                <strong>L’activation attend une charte publiée.</strong>
                                <p>Aucune charte ZUMRA publiée n’est disponible pour le moment.</p>
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
                        <h2>Adhérez gratuitement au Programme ZUMRA.</h2>
                        <p>Votre engagement consiste à accepter la charte commune. Aucun frais d’adhésion n’est demandé. Les contributions à des projets ou besoins restent distinctes et suivent leurs propres règles.</p>

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
                                        <small>Cette acceptation est enregistrée avec la version de la charte que vous venez de lire et active immédiatement votre adhésion.</small>
                                    </span>
                                </label>
                                <x-dg.button type="submit" variant="solar">Accepter la charte et adhérer gratuitement →</x-dg.button>
                            </form>
                        @else
                            <div class="dg-zumra-membership__unavailable">
                                <strong>L’adhésion n’est pas encore ouverte.</strong>
                                <p>Aucune charte ZUMRA publiée n’est disponible pour le moment. Aucun membre ne peut être activé sans charte.</p>
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
                        <li><span>1</span><div><strong>Accepter la charte</strong><small>L’engagement est gratuit, explicite et enregistré avec la version de la charte acceptée.</small></div></li>
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
                        <p>Votre statut administrateur n’active pas silencieusement une adhésion personnelle : l’acceptation de la charte reste nécessaire et traçable.</p>
                    </section>
                @endif
            </aside>
        </div>
    </div>
</x-layouts.member>
