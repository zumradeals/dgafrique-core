{{--
    UIUX-009B — étape finale : résumé humain avant confirmation. Aucune création n'a lieu avant ce
    clic (même invariant que le Cerveau). Chaque section renvoie vers son étape pour modification.
--}}
@php
    $modeLabels = ['PHYSICAL' => 'Sur place', 'DIGITAL' => 'En ligne', 'HYBRID' => 'Les deux'];
    $zumraGroup = $groups->firstWhere('public_reference', $payload['zumra_group_reference'] ?? null);
    $sourceNeed = $needs->firstWhere('public_reference', $payload['source_need_reference'] ?? null);
@endphp
<x-layouts.portal title="Relire mon projet — DG Afrique">
    <x-dg.shell current="projets" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:680px">
            @include('projects.draft._header', ['step' => 'relire'])

            <h1 class="dg-display dg-display--lead">Relire mon projet</h1>
            <p class="dg-body" style="margin-top:8px">Vérifiez ce que vous avez raconté. Rien n’est encore créé — vous pouvez modifier n’importe quelle section avant de confirmer.</p>

            <div style="margin-top:26px;display:flex;flex-direction:column;gap:14px">
                <x-dg.card tight>
                    <div style="display:flex;justify-content:space-between;align-items:baseline;gap:10px">
                        <x-dg.label>Votre ZUMRA</x-dg.label>
                        <a href="{{ route('projects.draft.show', [$draft, 'audience']) }}" class="dg-meta" style="color:var(--dg-copper)">Modifier →</a>
                    </div>
                    <p class="dg-body" style="margin-top:6px">
                        {{ $zumraGroup ? '« '.$zumraGroup->name.' »' : '—' }}
                        · {{ ($payload['owner_type'] ?? null) === 'GROUP' ? 'décidé collectivement' : 'décidé par vous, comme initiateur·rice' }}
                    </p>
                </x-dg.card>

                <x-dg.card tight>
                    <div style="display:flex;justify-content:space-between;align-items:baseline;gap:10px">
                        <x-dg.label>Votre idée</x-dg.label>
                        <a href="{{ route('projects.draft.show', [$draft, 'nom']) }}" class="dg-meta" style="color:var(--dg-copper)">Modifier →</a>
                    </div>
                    <p class="dg-display" style="font-size:22px;margin-top:6px">{{ $payload['name'] ?? '' }}</p>
                    @if($sourceNeed)
                        <p class="dg-meta" style="margin-top:4px">Issu du besoin « {{ $sourceNeed->title }} »</p>
                    @endif
                    <p class="dg-body" style="margin-top:8px">{{ $payload['summary'] ?? '' }}</p>
                    <p class="dg-body" style="margin-top:10px"><strong>Problème :</strong> {{ $payload['problem'] ?? '' }}</p>
                    <p class="dg-body" style="margin-top:6px"><strong>Solution envisagée :</strong> {{ $payload['proposed_solution'] ?? '' }}</p>
                </x-dg.card>

                <x-dg.card tight>
                    <div style="display:flex;justify-content:space-between;align-items:baseline;gap:10px">
                        <x-dg.label>À qui cela doit être utile</x-dg.label>
                        <a href="{{ route('projects.draft.show', [$draft, 'beneficiaires']) }}" class="dg-meta" style="color:var(--dg-copper)">Modifier →</a>
                    </div>
                    <p class="dg-body" style="margin-top:6px">{{ $payload['beneficiaries'] ?? '' }}</p>
                </x-dg.card>

                <x-dg.card tight>
                    <div style="display:flex;justify-content:space-between;align-items:baseline;gap:10px">
                        <x-dg.label>Où et comment</x-dg.label>
                        <a href="{{ route('projects.draft.show', [$draft, 'logistique']) }}" class="dg-meta" style="color:var(--dg-copper)">Modifier →</a>
                    </div>
                    <p class="dg-body" style="margin-top:6px">
                        {{ $config['domains'][$payload['domain'] ?? ''] ?? '—' }} · {{ $modeLabels[$payload['participation_mode'] ?? ''] ?? '—' }}
                        @if(!empty($payload['location'])) · {{ $payload['location'] }} @endif
                    </p>
                </x-dg.card>

                <x-dg.card tight>
                    <div style="display:flex;justify-content:space-between;align-items:baseline;gap:10px">
                        <x-dg.label>Objectifs</x-dg.label>
                        <a href="{{ route('projects.draft.show', [$draft, 'objectifs']) }}" class="dg-meta" style="color:var(--dg-copper)">Modifier →</a>
                    </div>
                    <ul style="margin:8px 0 0;padding-left:18px">
                        @forelse($payload['objectives'] ?? [] as $item)
                            <li class="dg-body">{{ $item }}</li>
                        @empty
                            <li class="dg-meta">Aucun objectif renseigné.</li>
                        @endforelse
                    </ul>
                </x-dg.card>

                <x-dg.card tight>
                    <div style="display:flex;justify-content:space-between;align-items:baseline;gap:10px">
                        <x-dg.label>Ce qui pourrait manquer</x-dg.label>
                        <a href="{{ route('projects.draft.show', [$draft, 'besoins']) }}" class="dg-meta" style="color:var(--dg-copper)">Modifier →</a>
                    </div>
                    @foreach([
                        'required_capabilities' => 'Capacités',
                        'required_resources' => 'Ressources',
                        'risks' => 'Risques',
                    ] as $key => $label)
                        <p class="dg-body" style="margin-top:8px">
                            <strong>{{ $label }} :</strong>
                            {{ !empty($payload[$key]) ? implode(', ', $payload[$key]) : 'Pas encore précisé' }}
                        </p>
                    @endforeach
                </x-dg.card>
            </div>

            <form method="POST" action="{{ route('projects.draft.confirm', $draft) }}" enctype="multipart/form-data" style="margin-top:26px">
                @csrf
                <label class="dg-field" style="margin-bottom:16px">
                    <span class="dg-label">Une image pour illustrer votre projet (facultatif)</span>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
                </label>
                <p class="dg-hint" style="margin-bottom:10px">La maturité de votre projet reste « Idée » à la création — elle évoluera avec des signes observables réels. Aucun financement n’est ouvert ici.</p>
                <button type="submit" class="dg-btn dg-btn--project">Confirmer et créer mon projet →</button>
            </form>

            @include('projects.draft._abandon')
        </div>
    </x-dg.shell>
</x-layouts.portal>
