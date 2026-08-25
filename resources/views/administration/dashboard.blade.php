@php
    $statusLabels = ['PROPOSED' => 'Proposé', 'OPEN' => 'Ouvert', 'IN_PROGRESS' => 'En cours', 'RESOLVED' => 'Résolu', 'ARCHIVED' => 'Archivé', 'ADOPTED' => 'Adopté', 'COMPLETED' => 'Terminé'];
    $stateLabels = ['CONSTITUTING' => 'Constitution', 'READY' => 'Prête', 'VALIDATED' => 'Validée', 'ACTIVE' => 'Active', 'WARNED' => 'Avertie', 'SUSPENDED' => 'Suspendue', 'REHABILITATING' => 'Réhabilitation'];
    $attention = collect([
        ['count' => $moderationPending, 'label' => 'signalement(s) de modération en attente', 'route' => route('administration.moderation.index'), 'tone' => 'danger'],
        ['count' => $moderationAppealsPending, 'label' => 'recours de modération en attente', 'route' => route('administration.moderation.index'), 'tone' => 'danger'],
        ['count' => $accompanimentPending, 'label' => 'demande(s) d’accompagnement en attente', 'route' => route('administration.project-accompaniment.edit'), 'tone' => 'warn'],
        ['count' => $rolesProposedPending, 'label' => 'rôle(s) fondateur ZUMRA proposé(s) non accepté(s)', 'route' => route('administration.community.zumra'), 'tone' => 'warn'],
        ['count' => $missionsBlocked, 'label' => 'Mission(s) bloquée(s)', 'route' => route('administration.projects.missions'), 'tone' => 'warn'],
        ['count' => $acquisitionsFailed24h, 'label' => 'acquisition(s) ZAHAB échouée(s) (24h)', 'route' => route('administration.finance.acquisitions'), 'tone' => 'danger'],
        ['count' => $contributionFailures7d, 'label' => 'paiement(s) de contribution échoué(s) (7j)', 'route' => route('administration.finance.contributions'), 'tone' => 'warn'],
    ])->filter(fn ($a) => $a['count'] > 0)->values();
@endphp
<x-layouts.admin title="Vue d’ensemble — Administration DG Afrique" current="dashboard">
    <div class="ac-page-head">
        <div>
            <p class="dg-label dg-label--saffron">Tour de contrôle</p>
            <h1>Vue d’ensemble</h1>
            <p>Ce qui se passe sur DG Afrique en ce moment, ce qui nécessite votre attention, et l’état des principaux moteurs.</p>
        </div>
    </div>

    <div class="ac-stat-grid">
        <div class="ac-stat"><div class="ac-stat__label">ZUMRA actives</div><div class="ac-stat__value">{{ $zumraByState['ACTIVE'] ?? 0 }}</div><div class="ac-stat__meta">{{ $zumraByState->sum() }} au total</div></div>
        <div class="ac-stat"><div class="ac-stat__label">Besoins ouverts</div><div class="ac-stat__value">{{ ($needsByStatus['OPEN'] ?? 0) + ($needsByStatus['IN_PROGRESS'] ?? 0) }}</div><div class="ac-stat__meta">{{ $needsByStatus->sum() }} au total</div></div>
        <div class="ac-stat"><div class="ac-stat__label">Projets actifs</div><div class="ac-stat__value">{{ ($projectsByStatus['ADOPTED'] ?? 0) + ($projectsByStatus['IN_PROGRESS'] ?? 0) }}</div><div class="ac-stat__meta">{{ $projectsByStatus->sum() }} au total</div></div>
        <div class="ac-stat"><div class="ac-stat__label">Missions actives</div><div class="ac-stat__value">{{ $missionsActive }}</div><div class="ac-stat__meta">{{ $missionsBlocked }} bloquée(s)</div></div>
        <div class="ac-stat"><div class="ac-stat__label">Masse ZAHAB</div><div class="ac-stat__value">{{ number_format($massZahab, 0, ',', ' ') }}</div><div class="ac-stat__meta">dérivée du Ledger · {{ $ledgerMovements7d }} mouvement(s)/7j</div></div>
        <div class="ac-stat"><div class="ac-stat__label">Financements Projet ouverts</div><div class="ac-stat__value">{{ $projectFundingOpen }}</div><div class="ac-stat__meta">{{ number_format($projectFundingTargetOpen, 0, ',', ' ') }} ZAHAB visés</div></div>
    </div>

    <div class="ac-section-grid">
        <section class="ac-section">
            <div class="ac-section__head"><h2>Nécessite votre attention</h2></div>
            @if($attention->isEmpty())
                <div class="ac-empty"><strong>Rien n’attend de décision.</strong>Aucune file d’attente connue n’est non vide en ce moment.</div>
            @else
                <div class="ac-list">
                    @foreach($attention as $item)
                        <a href="{{ $item['route'] }}" class="ac-list__row" style="text-decoration:none">
                            <span><x-dg.badge :tone="$item['tone'] === 'danger' ? 'danger' : 'need'">{{ $item['count'] }}</x-dg.badge></span>
                            <strong style="flex:1">{{ $item['label'] }}</strong>
                            <span aria-hidden="true">→</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="ac-section">
            <div class="ac-section__head"><h2>ZUMRA par état</h2><a href="{{ route('administration.community.zumra') }}">Voir la liste →</a></div>
            <div class="ac-badge-row">
                @forelse($zumraByState as $state => $count)
                    <x-dg.badge tone="neutral">{{ $stateLabels[$state] ?? $state }} · {{ $count }}</x-dg.badge>
                @empty
                    <span class="dg-meta">Aucune ZUMRA enregistrée.</span>
                @endforelse
            </div>
        </section>

        <section class="ac-section">
            <div class="ac-section__head"><h2>Acquisitions ZAHAB récentes</h2><a href="{{ route('administration.finance.acquisitions') }}">Voir tout →</a></div>
            @forelse($acquisitionsRecent as $acquisition)
                <div class="ac-list__row">
                    <div><strong>{{ number_format($acquisition->amount, 0, ',', ' ') }} {{ $acquisition->currency }}</strong><small>{{ $acquisition->person_core_reference }} · {{ $acquisition->created_at?->diffForHumans() }}</small></div>
                    <x-dg.badge :tone="$acquisition->status === 'COMPLETED' ? 'success' : ($acquisition->status === 'FAILED' ? 'danger' : 'neutral')">{{ $acquisition->status }}</x-dg.badge>
                </div>
            @empty
                <div class="ac-empty">Aucune acquisition ZAHAB enregistrée.</div>
            @endforelse
            <p class="dg-hint" style="margin-top:10px">Total complété : {{ number_format($acquisitionsCompletedTotal, 0, ',', ' ') }} ZAHAB.</p>
        </section>

        <section class="ac-section">
            <div class="ac-section__head"><h2>Contributions & adhésions</h2><a href="{{ route('administration.finance.contributions') }}">Voir tout →</a></div>
            <p class="dg-hint">{{ $contributionsActive }} engagement(s) de contribution actif(s).</p>
            @forelse($contributionPaymentsRecent as $payment)
                <div class="ac-list__row">
                    <div><strong>{{ number_format($payment->amount, 0, ',', ' ') }} {{ $payment->currency }}</strong><small>{{ $payment->period }} · {{ $payment->created_at?->diffForHumans() }}</small></div>
                    <x-dg.badge :tone="$payment->status === 'COMPLETED' ? 'success' : ($payment->status === 'FAILED' ? 'danger' : 'neutral')">{{ $payment->status }}</x-dg.badge>
                </div>
            @empty
                <div class="ac-empty">Aucun paiement de contribution enregistré.</div>
            @endforelse
        </section>

        <section class="ac-section">
            <div class="ac-section__head"><h2>Activité récente</h2><a href="{{ route('administration.journal.index') }}">Voir le journal →</a></div>
            <div class="ac-list">
                @forelse($recentJournal as $entry)
                    <div class="ac-list__row">
                        <div><strong>{{ $entry['type_label'] }}</strong><small>{{ $entry['label'] }} · {{ $entry['actor'] }}</small></div>
                        <small>{{ \Illuminate\Support\Carbon::parse($entry['occurred_at'])->diffForHumans() }}</small>
                    </div>
                @empty
                    <div class="ac-empty">Aucune activité enregistrée.</div>
                @endforelse
            </div>
        </section>

        <section class="ac-section">
            <div class="ac-section__head"><h2>Moteurs & configuration</h2><a href="{{ route('administration.engines.index') }}">Ouvrir →</a></div>
            <p class="dg-hint">Matching Projet et Recommandations sont configurés et actifs. Aucune mesure de performance fiable n’existe encore (seuls les masquages sont tracés) — voir la page Moteurs.</p>
            <a href="{{ route('administration.configuration.index') }}" class="dg-btn dg-btn--quiet" style="margin-top:8px;display:inline-block">Ouvrir la Configuration →</a>
        </section>
    </div>
</x-layouts.admin>
