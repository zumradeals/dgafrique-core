{{--
    Adhésion au Programme ZUMRA — un engagement distinct du compte DG Afrique. L'activation
    n'intervient qu'après vérification directe du paiement, jamais depuis un retour navigateur.
--}}
<x-layouts.portal title="Adhésion ZUMRA — DG Afrique">
    <x-dg.shell current="zumra" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:1000px">
            <a href="{{ route('zumra.index') }}" class="dg-crumb">← ZUMRA</a>

            @if(session('status'))
                <div class="dg-band" style="margin-bottom:20px">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="dg-band" style="margin-bottom:20px;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>
            @endif

            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="saffron">Adhésion au programme</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">{{ $configuration['title'] }}</h1>
                    <p>{{ $configuration['introduction'] }}</p>
                </div>
                <div style="text-align:right">
                    <div class="dg-display" style="font-size:28px">500 <small style="font-size:14px">FCFA</small></div>
                    <div class="dg-meta">ou 1 USD · une seule fois</div>
                </div>
            </div>

            @if($membership)
                <x-dg.card style="margin-bottom:24px">
                    <x-dg.label>État du dossier</x-dg.label>
                    <h2 class="dg-display" style="font-size:22px;margin-top:6px">{{ $membership->status === 'PENDING_PAYMENT' ? $configuration['pending_payment_title'] : match($membership->status) { 'ACTIVE' => 'Adhésion active', 'SUSPENDED' => 'Adhésion suspendue', 'CLOSED' => 'Adhésion fermée', default => $membership->status } }}</h2>
                    <p class="dg-body" style="margin-top:8px">{{ $membership->status === 'PENDING_PAYMENT' ? $configuration['pending_payment_help'] : 'Votre historique d’adhésion reste conservé.' }}</p>
                    <dl class="dg-dl" style="margin-top:16px;grid-template-columns:repeat(auto-fit,minmax(180px,1fr))">
                        <div>
                            <dt>Charte acceptée</dt>
                            <dd>{{ $membership->accepted_charter_version }}</dd>
                        </div>
                        <div>
                            <dt>Dossier créé</dt>
                            <dd>{{ $membership->submitted_at->format('d/m/Y à H:i') }}</dd>
                        </div>
                        <div>
                            <dt>Paiement</dt>
                            <dd>{{ $membership->status === 'PENDING_PAYMENT' ? 'Non effectué' : 'Voir preuve' }}</dd>
                        </div>
                    </dl>
                </x-dg.card>

                @if($membership->status === 'PENDING_PAYMENT')
                    <x-dg.deep>
                        @if($configuration['payment_enabled'])
                            <x-dg.label tone="saffron">Paiement sécurisé · 500 FCFA</x-dg.label>
                            <p style="margin:10px 0 16px;font-size:15px;line-height:1.65;color:var(--dg-on-deep-text)">L’activation intervient uniquement après vérification directe du paiement par DG Afrique. Un retour navigateur ne vaut jamais confirmation.</p>
                            <form method="POST" action="{{ route('zumra.payment.store') }}">
                                @csrf
                                <button type="submit" class="dg-btn dg-btn--saffron">Payer mon adhésion</button>
                            </form>
                        @else
                            <x-dg.label tone="saffron">Aucun débit en attente</x-dg.label>
                            <p style="margin:10px 0 16px;font-size:15px;line-height:1.65;color:var(--dg-on-deep-text)">{{ $configuration['payment_unavailable_notice'] }}</p>
                            <span class="dg-btn dg-btn--on-deep" aria-disabled="true">Paiement temporairement indisponible</span>
                        @endif
                    </x-dg.deep>
                @endif
            @elseif($charter)
                <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
                    <x-dg.card>
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <x-dg.label>Charte en vigueur</x-dg.label>
                                <h2 class="dg-display" style="font-size:20px;margin-top:6px">{{ $charter->title }}</h2>
                            </div>
                            <span class="dg-meta">Version {{ $charter->version }}</span>
                        </div>
                        <div class="dg-body" style="margin-top:14px;white-space:pre-line">{{ $charter->body }}</div>
                    </x-dg.card>

                    <x-dg.card tight>
                        <x-dg.label tone="saffron">Votre engagement</x-dg.label>
                        <h2 class="dg-display" style="font-size:18px;margin-top:6px">Un seul paiement, une adhésion acquise.</h2>
                        <p class="dg-body" style="margin-top:8px">{{ $configuration['commitment_notice'] }}</p>
                        <ul style="margin-top:12px;display:flex;flex-direction:column;gap:6px;font-size:14px;color:var(--dg-text)">
                            <li>· Demander à rejoindre une ZUMRA</li>
                            <li>· Recevoir des invitations</li>
                            <li>· Proposer une nouvelle ZUMRA</li>
                            <li>· Accéder progressivement aux mises en relation</li>
                        </ul>
                        <form method="POST" action="{{ route('zumra.membership.store') }}" style="margin-top:16px;display:flex;flex-direction:column;gap:12px">
                            @csrf
                            <input type="hidden" name="charter_id" value="{{ $charter->id }}">
                            <label class="dg-consent">
                                <input type="checkbox" name="accept_charter" value="1" required>
                                <span>{{ $configuration['accept_label'] }}</span>
                            </label>
                            <button type="submit" class="dg-btn dg-btn--saffron">{{ $configuration['submit_label'] }}</button>
                            <span class="dg-hint">Aucun paiement ne sera déclenché à cette étape.</span>
                        </form>
                    </x-dg.card>
                </div>
            @else
                <x-dg.empty title="Adhésion en préparation">
                    <span>Aucune charte n’est actuellement publiée. Aucun dossier ni paiement ne peut être créé.</span>
                </x-dg.empty>
            @endif
        </div>
    </x-dg.shell>
</x-layouts.portal>
