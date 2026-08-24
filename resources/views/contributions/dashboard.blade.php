{{--
    CONTRIBUTION-ZAHAB-001 — surface minimale rendant CAP-061 réellement atteignable (l'audit
    CORE-COMPLETION-001 avait trouvé un backend réel sans aucune vue). Pas de chantier esthétique :
    montant, finalité, Wallet ZAHAB utilisé, solde disponible, bouton, confirmation, reçu.
--}}
<x-layouts.portal title="Contributions — DG Afrique">
    <x-dg.shell current="espace" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:1000px">
            <a href="{{ route('member.space') }}" class="dg-crumb">← Mon espace</a>

            @if(session('status'))
                <div class="dg-band" style="margin-bottom:20px">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="dg-band" style="margin-bottom:20px;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>
            @endif

            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="saffron">Contributions volontaires</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">Mes contributions</h1>
                    <p>Une contribution est toujours facultative — jamais une dette, jamais un score. Réglable avec votre Wallet ZAHAB.</p>
                </div>
            </div>

            {{-- ===== Individuelle ===== --}}
            <x-dg.card style="margin-bottom:24px">
                <x-dg.label>Contribution individuelle</x-dg.label>

                @if(! $individual)
                    <p class="dg-body" style="margin-top:8px">Vous n'avez pas encore d'engagement de contribution individuelle.</p>
                    <form method="POST" action="{{ route('contributions.individual.start') }}" style="margin-top:12px">
                        @csrf
                        <button type="submit" class="dg-btn dg-btn--primary">Démarrer un engagement individuel</button>
                    </form>
                @else
                    <h2 class="dg-display" style="font-size:20px;margin-top:6px">
                        {{ match($individual->status) { 'ACTIVE' => 'Engagement actif', 'PAUSED' => 'Engagement en pause', 'STOPPED' => 'Engagement arrêté', default => $individual->status } }}
                    </h2>

                    <dl class="dg-dl" style="margin-top:16px;grid-template-columns:repeat(auto-fit,minmax(180px,1fr))">
                        <div>
                            <dt>Solde Wallet ZAHAB</dt>
                            <dd>{{ number_format($individualBalance, 0, ',', ' ') }} ZAHAB</dd>
                        </div>
                        <div>
                            <dt>Montant mensuel</dt>
                            <dd>{{ number_format((int) $settings['individual_amount'], 0, ',', ' ') }} ZAHAB</dd>
                        </div>
                        <div>
                            <dt>Période courante</dt>
                            <dd>{{ $currentPeriod }}</dd>
                        </div>
                    </dl>

                    @if($individual->status === 'ACTIVE')
                        @if($individualPaidThisPeriod)
                            <p class="dg-body" style="margin-top:16px">La contribution de {{ $currentPeriod }} est déjà réglée. Merci.</p>
                        @elseif(! $settings['individual_enabled'])
                            <p class="dg-meta" style="margin-top:16px">Les paiements de contribution individuelle ne sont pas encore ouverts.</p>
                        @elseif($purposes->isEmpty())
                            <p class="dg-meta" style="margin-top:16px">Aucune finalité active n'est configurée pour le moment.</p>
                        @else
                            <form method="POST" action="{{ route('contributions.pay.zahab', $individual) }}" style="margin-top:16px;display:flex;gap:12px;align-items:end;flex-wrap:wrap">
                                @csrf
                                <input type="hidden" name="period" value="{{ $currentPeriod }}">
                                <label style="display:flex;flex-direction:column;gap:4px">
                                    <span class="dg-meta">Finalité</span>
                                    <select name="purpose_code" class="dg-select">
                                        @foreach($purposes as $purpose)
                                            <option value="{{ $purpose->code }}">{{ $purpose->label }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <button type="submit" class="dg-btn dg-btn--primary" @if($individualBalance < (int) $settings['individual_amount']) disabled title="Solde ZAHAB insuffisant" @endif>
                                    Payer {{ number_format((int) $settings['individual_amount'], 0, ',', ' ') }} ZAHAB avec mon Wallet
                                </button>
                            </form>
                            @if($individualBalance < (int) $settings['individual_amount'])
                                <p class="dg-meta" style="margin-top:8px">Solde insuffisant pour régler cette période avec le Wallet ZAHAB.</p>
                            @endif
                        @endif

                        <form method="POST" action="{{ route('contributions.pause', $individual) }}" style="margin-top:12px;display:inline-block">
                            @csrf
                            <button type="submit" class="dg-btn">Mettre en pause</button>
                        </form>
                    @elseif($individual->status === 'PAUSED' || $individual->status === 'STOPPED')
                        <form method="POST" action="{{ route('contributions.resume', $individual) }}" style="margin-top:16px">
                            @csrf
                            <button type="submit" class="dg-btn dg-btn--primary">Reprendre</button>
                        </form>
                    @endif
                @endif
            </x-dg.card>

            {{-- ===== Collectives ===== --}}
            @if($collectives->isNotEmpty())
                <x-dg.label>Contributions collectives (ZUMRA)</x-dg.label>
                @foreach($collectives as $entry)
                    <x-dg.card style="margin-top:12px;margin-bottom:12px">
                        <h2 class="dg-display" style="font-size:18px">{{ $entry['group']->name }}</h2>

                        @if(! $entry['contribution'])
                            @if($entry['can_propose_or_approve'])
                                <form method="POST" action="{{ route('zumra.groups.contribution.propose', $entry['group']) }}" style="margin-top:12px">
                                    @csrf
                                    <button type="submit" class="dg-btn dg-btn--primary">Proposer un engagement collectif</button>
                                </form>
                            @else
                                <p class="dg-meta" style="margin-top:8px">Aucun engagement collectif pour cette ZUMRA pour le moment.</p>
                            @endif
                        @else
                            <p class="dg-meta" style="margin-top:6px">
                                {{ match($entry['contribution']->status) { 'PROPOSED' => 'Engagement proposé, en attente d’approbation', 'ACTIVE' => 'Engagement actif', 'PAUSED' => 'Engagement en pause', 'STOPPED' => 'Engagement arrêté', default => $entry['contribution']->status } }}
                            </p>

                            @if($entry['contribution']->status === 'PROPOSED' && $entry['can_propose_or_approve'])
                                <form method="POST" action="{{ route('zumra.groups.contribution.approve', $entry['group']) }}" style="margin-top:12px">
                                    @csrf
                                    <button type="submit" class="dg-btn dg-btn--primary">Approuver l’engagement</button>
                                </form>
                            @endif

                            @if($entry['contribution']->status === 'ACTIVE')
                                <dl class="dg-dl" style="margin-top:12px;grid-template-columns:repeat(auto-fit,minmax(180px,1fr))">
                                    <div>
                                        <dt>Solde Wallet ZAHAB de la ZUMRA</dt>
                                        <dd>{{ number_format($entry['balance'], 0, ',', ' ') }} ZAHAB</dd>
                                    </div>
                                    <div>
                                        <dt>Montant mensuel</dt>
                                        <dd>{{ number_format((int) $settings['collective_amount'], 0, ',', ' ') }} ZAHAB</dd>
                                    </div>
                                </dl>

                                @if($entry['paid_this_period'])
                                    <p class="dg-body" style="margin-top:12px">La contribution de {{ $currentPeriod }} est déjà réglée.</p>
                                @elseif(! $settings['collective_enabled'])
                                    <p class="dg-meta" style="margin-top:12px">Les paiements de contribution collective ne sont pas encore ouverts.</p>
                                @elseif($purposes->isEmpty())
                                    <p class="dg-meta" style="margin-top:12px">Aucune finalité active n'est configurée pour le moment.</p>
                                @elseif($entry['can_propose_or_approve'])
                                    <form method="POST" action="{{ route('contributions.pay.zahab', $entry['contribution']) }}" style="margin-top:12px;display:flex;gap:12px;align-items:end;flex-wrap:wrap">
                                        @csrf
                                        <input type="hidden" name="period" value="{{ $currentPeriod }}">
                                        <label style="display:flex;flex-direction:column;gap:4px">
                                            <span class="dg-meta">Finalité</span>
                                            <select name="purpose_code" class="dg-select">
                                                @foreach($purposes as $purpose)
                                                    <option value="{{ $purpose->code }}">{{ $purpose->label }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                        <button type="submit" class="dg-btn dg-btn--primary" @if($entry['balance'] < (int) $settings['collective_amount']) disabled title="Solde ZAHAB insuffisant" @endif>
                                            Payer {{ number_format((int) $settings['collective_amount'], 0, ',', ' ') }} ZAHAB avec le Wallet de la ZUMRA
                                        </button>
                                    </form>
                                @endif
                            @endif
                        @endif
                    </x-dg.card>
                @endforeach
            @endif
        </div>
    </x-dg.shell>
</x-layouts.portal>
