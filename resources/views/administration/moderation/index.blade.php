@php
    $reasonLabels = ['VIOLENCE' => 'Violence', 'THREAT' => 'Menace', 'FRAUD' => 'Fraude', 'HARASSMENT' => 'Harcèlement', 'DISCRIMINATION' => 'Discrimination', 'HATE' => 'Haine', 'EXPLOITATION' => 'Exploitation', 'MISAPPROPRIATION' => 'Usurpation de contenu', 'IMPERSONATION' => 'Usurpation d’identité', 'DANGEROUS_MISINFORMATION' => 'Désinformation dangereuse', 'OTHER' => 'Autre'];
    $actionLabels = ['CONTENT_HIDDEN' => 'Masquer le contenu', 'WARNING' => 'Avertissement', 'MEMBERSHIP_SUSPENSION' => 'Suspension d’adhésion', 'MEMBERSHIP_EXCLUSION' => 'Exclusion', 'ROLE_REVOCATION' => 'Révocation de rôle'];
@endphp
<x-layouts.admin title="Modération — Administration" current="moderation">
    <div class="ac-page-head">
        <div>
            <p class="dg-label dg-label--saffron">Pilotage</p>
            <h1>Modération</h1>
            <p>Autorité niveau 3 (DG Afrique). Voit tout, y compris ce qu’une ZUMRA n’a jamais eu le droit de voir — aucune interception possible.</p>
        </div>
    </div>

    <section class="ac-section">
        <div class="ac-section__head"><h2>Signalements en attente</h2><x-dg.badge tone="danger">{{ count($reports) }}</x-dg.badge></div>
        <div class="ac-list">
            @forelse($reports as $report)
                <div class="ac-list__row" style="flex-direction:column;align-items:stretch">
                    <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap">
                        <div>
                            <strong>{{ $reasonLabels[$report['reason_code']] ?? $report['reason_code'] }}</strong>
                            <small>{{ $report['target_type'] }} · signalé par {{ $report['reporter_core_reference'] }} · {{ \Illuminate\Support\Carbon::parse($report['reported_at'])->diffForHumans() }}</small>
                        </div>
                        @if($report['escalated_at'])<x-dg.badge tone="decision">Escaladé</x-dg.badge>@endif
                    </div>
                    @if($report['target_excerpt'])<p class="dg-hint" style="margin:8px 0">« {{ $report['target_excerpt'] }} »</p>@endif
                    @if($report['reason_details'])<p class="dg-hint" style="margin:4px 0">{{ $report['reason_details'] }}</p>@endif
                    <form method="POST" action="{{ route('administration.moderation.decide', $report['id']) }}" style="display:flex;gap:8px;align-items:end;flex-wrap:wrap;margin-top:10px">
                        @csrf
                        <div class="dg-field" style="margin:0;min-width:200px">
                            <label>Décision</label>
                            <select name="action_type" class="dg-select" required>
                                @foreach($actionLabels as $type => $label)<option value="{{ $type }}">{{ $label }}</option>@endforeach
                            </select>
                        </div>
                        <div class="dg-field" style="margin:0;flex:1;min-width:220px">
                            <label>Motif (facultatif)</label>
                            <input type="text" name="reason_details" class="dg-input" maxlength="2000">
                        </div>
                        <button type="submit" class="dg-btn dg-btn--primary">Trancher</button>
                    </form>
                </div>
            @empty
                <div class="ac-empty"><strong>Aucun signalement en attente.</strong></div>
            @endforelse
        </div>
    </section>

    <section class="ac-section">
        <div class="ac-section__head"><h2>Recours en attente</h2><x-dg.badge tone="danger">{{ $pendingAppeals->count() }}</x-dg.badge></div>
        <div class="ac-list">
            @forelse($pendingAppeals as $decision)
                <div class="ac-list__row" style="flex-direction:column;align-items:stretch">
                    <div>
                        <strong>{{ $actionLabels[$decision->action_type] ?? $decision->action_type }}</strong>
                        <small>{{ $decision->target_type }} · demandé {{ $decision->appeal_requested_at?->diffForHumans() }}</small>
                    </div>
                    @if($decision->appeal_reason)<p class="dg-hint" style="margin:8px 0">{{ $decision->appeal_reason }}</p>@endif
                    <form method="POST" action="{{ route('administration.moderation.appeal-decide', $decision) }}" style="display:flex;gap:8px;align-items:end;flex-wrap:wrap;margin-top:10px">
                        @csrf
                        <div class="dg-field" style="margin:0;min-width:200px">
                            <label>Issue</label>
                            <select name="outcome" class="dg-select" required>
                                <option value="CONFIRMED">Confirmer la décision</option>
                                <option value="MODIFIED">Modifier la décision</option>
                                <option value="LIFTED">Lever la décision</option>
                            </select>
                        </div>
                        <div class="dg-field" style="margin:0;flex:1;min-width:220px">
                            <label>Explication (facultatif)</label>
                            <input type="text" name="explanation" class="dg-input" maxlength="2000">
                        </div>
                        <button type="submit" class="dg-btn dg-btn--primary">Trancher le recours</button>
                    </form>
                </div>
            @empty
                <div class="ac-empty"><strong>Aucun recours en attente.</strong></div>
            @endforelse
        </div>
    </section>
</x-layouts.admin>
