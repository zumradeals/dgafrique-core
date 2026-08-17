{{--
    Accompagnement DG Afrique (CAP-016) — le projet reste autonome. Ce dossier n'accorde ni
    propriété, ni contrôle, ni pouvoir de décision, et n'ouvre aucun financement.
--}}
<x-layouts.portal title="Accompagnement — {{ $project->name }}">
    <x-dg.shell current="projets" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:1000px">
            <a href="{{ route('projects.show', $project) }}" class="dg-crumb">← {{ $project->name }}</a>

            @if(session('status'))
                <div class="dg-band" style="margin-bottom:20px">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="dg-band" style="margin-bottom:20px;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>
            @endif

            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="night">Accompagnement DG Afrique</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">{{ $configuration['page_title'] }}</h1>
                    <p>{{ $configuration['page_intro'] }}</p>
                </div>
                <div style="text-align:right">
                    <x-dg.label>Projet</x-dg.label>
                    <div style="margin-top:6px;font-size:14px;font-weight:600;color:var(--dg-forest)">{{ $project->name }}</div>
                    <div class="dg-meta">{{ $project->status }}</div>
                </div>
            </div>

            <div class="dg-band" style="margin-bottom:24px">
                <strong style="display:block;font-size:14px;color:var(--dg-forest);margin-bottom:4px">Le projet reste autonome.</strong>
                Activer cet accompagnement n’accorde à DG Afrique ni propriété, ni contrôle, ni pouvoir de décision sur le projet. Aucun financement n’est créé par cette page.
            </div>

            @if(! $accompaniment || $accompaniment->status === \App\Models\ProjectAccompaniment::STATUS_ENDED)
                <x-dg.card style="margin-bottom:24px">
                    <x-dg.label>{{ $accompaniment ? 'Reprendre un accompagnement' : 'Activer un accompagnement' }}</x-dg.label>
                    <p class="dg-body" style="margin-top:8px">Le porteur autorisé ouvre volontairement ce parcours. Les interventions pourront ensuite être apportées par DG Afrique ou coordonnées avec un partenaire.</p>
                    <form method="POST" action="{{ route('projects.accompaniment.activate', $project) }}" style="margin-top:14px">
                        @csrf
                        <button type="submit" class="dg-btn dg-btn--primary">Activer l’accompagnement</button>
                    </form>
                </x-dg.card>
            @else
                <x-dg.deep style="margin-bottom:24px">
                    <x-dg.label tone="saffron">Accompagnement actif</x-dg.label>
                    <h2 class="dg-display" style="font-size:26px;margin-top:8px">Un appui progressif, action par action.</h2>
                    <p style="margin:8px 0 0;font-size:15px;line-height:1.65;color:var(--dg-on-deep-text)">Activé le {{ $accompaniment->activated_at?->format('d/m/Y à H:i') }}. Chaque intervention est conservée dans la chronologie.</p>
                    <form method="POST" action="{{ route('projects.accompaniment.end', $project) }}" style="margin-top:16px">
                        @csrf @method('PUT')
                        <button type="submit" class="dg-btn dg-btn--on-deep">Terminer l’accompagnement</button>
                    </form>
                </x-dg.deep>
            @endif

            <x-dg.card style="margin-bottom:24px">
                <x-dg.label>Appuis disponibles</x-dg.label>
                <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px">
                    @foreach($configuration['action_types'] as $label)
                        <x-dg.badge tone="neutral">{{ $label }}</x-dg.badge>
                    @endforeach
                </div>
            </x-dg.card>

            <x-dg.card>
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <x-dg.label>Traçabilité</x-dg.label>
                        <h2 class="dg-display" style="font-size:20px;margin-top:6px">Interventions enregistrées</h2>
                    </div>
                    @if($accompaniment)
                        <span class="dg-meta">{{ $accompaniment->actions->count() }} action(s)</span>
                    @endif
                </div>

                <div style="margin-top:16px;display:flex;flex-direction:column;gap:12px">
                    @forelse($accompaniment?->actions ?? [] as $action)
                        <div class="dg-note">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <strong style="color:var(--dg-ink)">{{ $configuration['action_types'][$action->action_type] ?? str_replace('_', ' ', $action->action_type) }}</strong>
                                <span class="dg-meta">{{ $action->delivery_source === \App\Models\ProjectAccompanimentAction::SOURCE_PARTNER ? 'Partenaire' : 'DG Afrique' }} · {{ $action->occurred_at?->format('d/m/Y H:i') }}</span>
                            </div>
                            <h3 style="margin:8px 0 2px;font-size:15px;color:var(--dg-forest)">{{ $action->provider_label }}</h3>
                            <p style="margin:0">{{ $action->summary }}</p>
                            @if($action->next_step)
                                <p style="margin:8px 0 0;font-weight:600;color:var(--dg-forest-action)">Prochaine étape : {{ $action->next_step }}</p>
                            @endif
                        </div>
                    @empty
                        <x-dg.empty title="Aucune intervention enregistrée.">
                            <span>L’accompagnement peut commencer sans dossier lourd ; la chronologie s’enrichira au fur et à mesure des actions réelles.</span>
                        </x-dg.empty>
                    @endforelse
                </div>
            </x-dg.card>
        </div>
    </x-dg.shell>
</x-layouts.portal>
