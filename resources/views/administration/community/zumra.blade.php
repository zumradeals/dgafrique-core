@php
    $stateLabels = ['CONSTITUTING' => 'Constitution', 'READY' => 'Prête', 'VALIDATED' => 'Validée', 'ACTIVE' => 'Active', 'WARNED' => 'Avertie', 'SUSPENDED' => 'Suspendue', 'REHABILITATING' => 'Réhabilitation'];
    $stateTones = ['CONSTITUTING' => 'neutral', 'READY' => 'decision', 'VALIDATED' => 'project', 'ACTIVE' => 'success', 'WARNED' => 'need', 'SUSPENDED' => 'danger', 'REHABILITATING' => 'need'];
    $stateTimestampField = ['CONSTITUTING' => 'created_at', 'READY' => 'ready_at', 'VALIDATED' => 'validated_at', 'ACTIVE' => 'activated_at', 'WARNED' => 'warned_at', 'SUSPENDED' => 'suspended_at', 'REHABILITATING' => 'rehabilitating_at'];
    // Machine d'états strictement linéaire (ZumraGroupService) : une seule action légale par état,
    // jamais recalculée ici — ce tableau ne fait que retrouver la route déjà validée côté serveur.
    $nextAction = [
        'CONSTITUTING' => ['route' => 'administration.zumra.groups.ready', 'label' => 'Constater prête'],
        'READY' => ['route' => 'administration.zumra.groups.validate', 'label' => 'Valider'],
        'VALIDATED' => ['route' => 'administration.zumra.groups.activate', 'label' => 'Activer'],
        'ACTIVE' => ['route' => 'administration.zumra.groups.warn', 'label' => 'Avertir'],
        'WARNED' => ['route' => 'administration.zumra.groups.suspend', 'label' => 'Suspendre'],
        'SUSPENDED' => ['route' => 'administration.zumra.groups.rehabilitate', 'label' => 'Mettre en réhabilitation'],
        'REHABILITATING' => ['route' => 'administration.zumra.groups.reactivate', 'label' => 'Réactiver'],
    ];
@endphp
<x-layouts.admin title="ZUMRA — Administration" current="zumra">
    <div class="ac-page-head">
        <div>
            <p class="dg-label dg-label--saffron">Communauté</p>
            <h1>ZUMRA</h1>
            <p>Liste, recherche et actions de cycle de vie. Chaque action réutilise strictement ZumraGroupService — aucune autorité recréée ici.</p>
        </div>
        <div class="ac-page-head__actions">
            <a href="{{ route('administration.zumra.groups.edit') }}" class="dg-btn dg-btn--quiet">Seuils de configuration →</a>
            <a href="{{ route('administration.zumra.edit') }}" class="dg-btn dg-btn--quiet">Programme & charte →</a>
        </div>
    </div>

    <div class="ac-badge-row" style="margin-bottom:20px">
        @foreach($byState as $state => $count)
            <x-dg.badge :tone="$stateTones[$state] ?? 'neutral'">{{ $stateLabels[$state] ?? $state }} · {{ $count }}</x-dg.badge>
        @endforeach
        @if($rolesProposed > 0)
            <x-dg.badge tone="decision">{{ $rolesProposed }} rôle(s) fondateur proposé(s) non accepté(s)</x-dg.badge>
        @endif
    </div>

    <form method="GET" class="ac-filters">
        <div class="dg-field">
            <label for="q">Recherche par nom</label>
            <input type="text" name="q" id="q" class="dg-input" value="{{ $search }}" placeholder="Nom de la ZUMRA">
        </div>
        <div class="dg-field">
            <label for="state">État</label>
            <select name="state" id="state" class="dg-select">
                <option value="">Tous</option>
                @foreach($stateLabels as $state => $label)
                    <option value="{{ $state }}" @selected($stateFilter === $state)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="dg-btn dg-btn--quiet">Filtrer</button>
    </form>

    <div class="ac-table-wrap">
        <table class="ac-table">
            <thead><tr><th>Nom</th><th>Domaine</th><th>État</th><th>Membres</th><th class="ac-wrap">Depuis</th><th>Action</th></tr></thead>
            <tbody>
                @forelse($groups as $group)
                    @php($since = $group->{$stateTimestampField[$group->state] ?? 'created_at'})
                    <tr>
                        <td><a href="{{ route('zumra.groups.show', $group) }}">{{ $group->name }}</a></td>
                        <td>{{ $group->domain }}</td>
                        <td><x-dg.badge :tone="$stateTones[$group->state] ?? 'neutral'">{{ $stateLabels[$group->state] ?? $group->state }}</x-dg.badge></td>
                        <td>{{ $group->active_member_count }}</td>
                        <td class="ac-wrap">{{ $since?->diffForHumans() }}</td>
                        <td>
                            @if(isset($nextAction[$group->state]))
                                <form method="POST" action="{{ route($nextAction[$group->state]['route'], $group) }}" onsubmit="return confirm('Confirmer : {{ $nextAction[$group->state]['label'] }} pour {{ $group->name }} ?')">
                                    @csrf
                                    <button type="submit" class="dg-btn dg-btn--quiet" style="padding:6px 12px;font-size:11px">{{ $nextAction[$group->state]['label'] }}</button>
                                </form>
                            @else
                                <span class="dg-meta">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="ac-empty">Aucune ZUMRA ne correspond à ces filtres.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="ac-pagination">{{ $groups->links() }}</div>
</x-layouts.admin>
