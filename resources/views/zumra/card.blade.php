{{--
    Carte ZUMRA — attestation d'adhésion, jamais une carte bancaire ni une preuve de contribution.
    Le QR (data-zumra-qr, resources/js/app.js) pointe vers la vérification publique signée.
--}}
<x-layouts.portal title="Ma Carte ZUMRA — DG Afrique">
    @php
        $labels = ['ACTIVE' => 'Active', 'SUSPENDED' => 'Suspendue', 'CLOSED' => 'Fermée', 'REVOKED' => 'Révoquée', 'PENDING_PAYMENT' => 'Paiement à finaliser', 'NOT_MEMBER' => 'Non délivrée'];
        $initial = mb_strtoupper(mb_substr($identity->label, 0, 1));
    @endphp
    <x-dg.shell current="zumra" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:1100px">
            <a href="{{ route('zumra.index') }}" class="dg-crumb">← ZUMRA</a>

            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="saffron">Attestation d’adhésion</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">Votre engagement, vérifiable simplement.</h1>
                    <p>La Carte ZUMRA représente votre adhésion au programme. Elle n’est ni une carte bancaire, ni un portefeuille, ni une preuve de contribution.</p>
                </div>
                <x-dg.label>{{ $labels[$state] ?? $state }}</x-dg.label>
            </div>

            @if($card)
                <div class="grid gap-6 lg:grid-cols-[380px_minmax(0,1fr)]">
                    <article class="dg-card" style="background:var(--dg-forest);color:var(--dg-ivory);display:flex;flex-direction:column;gap:18px;padding:26px">
                        <div class="flex items-center justify-between">
                            <strong style="font-size:15px">Z ZUMRA</strong>
                            <span style="font-size:12px;opacity:.8">DG Afrique</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="dg-avatar dg-avatar--saffron">{{ $initial }}</span>
                            <div>
                                <div style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;opacity:.75">Membre du programme</div>
                                <div style="font-size:18px;font-weight:600">{{ $card->holder_label }}</div>
                                <div style="font-size:13px;opacity:.85">{{ $card->country_code ?: 'Pays non renseigné' }}</div>
                            </div>
                        </div>
                        <dl style="display:flex;flex-direction:column;gap:8px;font-size:13px">
                            <div class="flex justify-between"><dt style="opacity:.75">Référence membre</dt><dd>{{ $card->core_identity_reference }}</dd></div>
                            <div class="flex justify-between"><dt style="opacity:.75">Adhésion depuis</dt><dd>{{ $membership->activated_at->format('d/m/Y') }}</dd></div>
                            <div class="flex justify-between"><dt style="opacity:.75">État</dt><dd>{{ $labels[$state] ?? $state }}</dd></div>
                        </dl>
                        <div class="flex items-center justify-between" style="padding-top:10px;border-top:1px solid rgba(217,214,203,.2);font-size:12px;opacity:.85">
                            <span>{{ $card->public_reference }}</span>
                            <span>Formation · Travail · Adoration</span>
                        </div>
                    </article>

                    <x-dg.card>
                        <div class="flex flex-wrap items-start gap-6">
                            <div style="border:1px solid var(--dg-line);border-radius:var(--dg-radius-control);padding:12px">
                                <canvas width="190" height="190" data-zumra-qr="{{ $verificationUrl }}" aria-label="QR de vérification de la Carte ZUMRA"></canvas>
                                <noscript>Activez JavaScript pour afficher le QR.</noscript>
                            </div>
                            <div style="flex:1;min-width:200px">
                                <x-dg.label>Vérification publique</x-dg.label>
                                <h2 class="dg-display" style="font-size:20px;margin-top:6px">Une preuve, pas une promesse.</h2>
                                <p class="dg-body" style="margin-top:8px">Le QR confirme l’existence de la carte et son état actuel. Une suspension ou une révocation apparaît immédiatement lors de la vérification.</p>
                                <div style="margin-top:14px">
                                    <x-dg.btn variant="quiet" :href="$verificationUrl" target="_blank" rel="noopener">Ouvrir la vérification →</x-dg.btn>
                                </div>
                            </div>
                        </div>
                    </x-dg.card>
                </div>

                <div class="dg-band" style="margin-top:24px;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px">
                    <div>
                        <strong style="display:block;font-size:14px;color:var(--dg-forest);margin-bottom:4px">Données maîtrisées</strong>
                        La vérification publique ne révèle ni téléphone, ni capacités, ni besoins, ni paiements, ni contributions. Aucune appartenance à une ZUMRA particulière n’est inventée.
                    </div>
                    <button type="button" onclick="window.print()" class="dg-btn dg-btn--quiet">Imprimer ma carte</button>
                </div>
            @else
                <x-dg.empty title="Carte non délivrée">
                    <span>{{ $state === 'PENDING_PAYMENT' ? 'Finalisez votre adhésion pour recevoir votre Carte ZUMRA.' : 'Une adhésion active est nécessaire pour recevoir cette carte.' }}</span>
                    <div style="margin-top:12px">
                        <x-dg.btn variant="quiet" :href="route('zumra.membership.show')">Voir mon adhésion</x-dg.btn>
                    </div>
                </x-dg.empty>
            @endif
        </div>
    </x-dg.shell>
</x-layouts.portal>
