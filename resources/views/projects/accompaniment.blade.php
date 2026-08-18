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

            <details class="dg-disclosure" style="margin-bottom:24px">
                <summary><span class="dg-disclosure__mark">i</span> Le projet reste autonome.</summary>
                <div class="dg-disclosure__body">Activer cet accompagnement n’accorde à DG Afrique ni propriété, ni contrôle, ni pouvoir de décision sur le projet. Aucun financement n’est créé par cette page.</div>
            </details>

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

            @if($accompaniment && $accompaniment->status === \App\Models\ProjectAccompaniment::STATUS_ACTIVE)
                <x-dg.card style="margin-bottom:24px">
                    <x-dg.label>Transmettre une demande</x-dg.label>
                    <p class="dg-hint" style="margin-top:6px">Chaque demande entre dans une file traitée par DG Afrique dans l’ordre d’arrivée, jamais selon un score.</p>
                    <form method="POST" action="{{ route('projects.accompaniment.requests.store', $project) }}" style="margin-top:14px;display:flex;flex-direction:column;gap:10px">
                        @csrf
                        <div class="dg-field">
                            <label for="subject">Objet</label>
                            <input type="text" id="subject" name="subject" class="dg-input" minlength="5" maxlength="180" required>
                        </div>
                        <div class="dg-field">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="dg-textarea" rows="3" minlength="20" maxlength="2400" required></textarea>
                        </div>
                        <button type="submit" class="dg-btn dg-btn--primary" style="align-self:flex-start">Transmettre la demande</button>
                    </form>
                </x-dg.card>

                <x-dg.card style="margin-bottom:24px">
                    <x-dg.label>Vos demandes</x-dg.label>
                    <div style="margin-top:14px;display:flex;flex-direction:column;gap:10px">
                        @forelse($requests as $accompanimentRequest)
                            <div class="dg-note">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <strong style="color:var(--dg-ink)">{{ $accompanimentRequest->subject }}</strong>
                                    <x-dg.label>{{ ['PENDING' => 'En attente', 'ACKNOWLEDGED' => 'Prise en charge', 'CLOSED' => 'Close'][$accompanimentRequest->status] ?? $accompanimentRequest->status }}</x-dg.label>
                                </div>
                                <p style="margin:8px 0 0">{{ $accompanimentRequest->description }}</p>
                                @if($accompanimentRequest->resolution_note)
                                    <p style="margin:8px 0 0;font-weight:600;color:var(--dg-forest-action)">Réponse : {{ $accompanimentRequest->resolution_note }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="dg-meta">Aucune demande transmise pour l’instant.</p>
                        @endforelse
                    </div>
                </x-dg.card>
            @endif

            <x-dg.card style="margin-bottom:24px">
                <x-dg.label>Appuis disponibles</x-dg.label>
                <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px">
                    @foreach($configuration['action_types'] as $label)
                        <x-dg.badge tone="neutral">{{ $label }}</x-dg.badge>
                    @endforeach
                </div>
            </x-dg.card>

            @if($accompaniment && $accompaniment->actions->isNotEmpty())
                <x-dg.card style="margin-bottom:24px">
                    <x-dg.label>Synthèse</x-dg.label>
                    <p class="dg-hint" style="margin-top:6px">Des comptes réels, jamais un score ni un classement.</p>
                    <div style="margin-top:12px;display:grid;gap:16px" class="lg:grid-cols-2">
                        <div>
                            <span class="dg-meta">Par type d’appui</span>
                            <ul style="margin-top:8px;display:flex;flex-direction:column;gap:4px;font-size:14px;color:var(--dg-text)">
                                @foreach($typeCounts as $type => $count)
                                    <li>· {{ $configuration['action_types'][$type] ?? str_replace('_', ' ', $type) }} — {{ $count }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <div>
                            <span class="dg-meta">Par source</span>
                            <ul style="margin-top:8px;display:flex;flex-direction:column;gap:4px;font-size:14px;color:var(--dg-text)">
                                @foreach($sourceCounts as $source => $count)
                                    <li>· {{ $source === \App\Models\ProjectAccompanimentAction::SOURCE_PARTNER ? 'Partenaire' : 'DG Afrique' }} — {{ $count }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </x-dg.card>
            @endif

            <x-dg.card>
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <x-dg.label>Traçabilité</x-dg.label>
                        <h2 class="dg-display" style="font-size:20px;margin-top:6px">Interventions enregistrées</h2>
                    </div>
                    @if($accompaniment)
                        <span class="dg-meta">{{ $actions->count() }} / {{ $accompaniment->actions->count() }} action(s)</span>
                    @endif
                </div>

                @if($accompaniment && $accompaniment->actions->isNotEmpty())
                    <form method="GET" action="{{ route('projects.accompaniment.show', $project) }}" style="margin-top:14px;display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
                        <div class="dg-field" style="max-width:220px">
                            <label for="type">Type d’appui</label>
                            <select name="type" id="type" class="dg-select">
                                <option value="">Tous</option>
                                @foreach($typeCounts->keys() as $type)
                                    <option value="{{ $type }}" @selected($selectedType === $type)>{{ $configuration['action_types'][$type] ?? str_replace('_', ' ', $type) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="dg-field" style="max-width:220px">
                            <label for="partenaire">Intervenant / partenaire</label>
                            <select name="partenaire" id="partenaire" class="dg-select">
                                <option value="">Tous</option>
                                @foreach($availablePartners as $partner)
                                    <option value="{{ $partner }}" @selected($selectedPartner === $partner)>{{ $partner }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="dg-btn dg-btn--quiet">Filtrer</button>
                        @if($selectedType || $selectedPartner)
                            <a href="{{ route('projects.accompaniment.show', $project) }}" class="dg-btn dg-btn--quiet">Réinitialiser</a>
                        @endif
                    </form>
                @endif

                <div style="margin-top:16px;display:flex;flex-direction:column;gap:12px">
                    @forelse($actions as $action)
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
                        @if($selectedType || $selectedPartner)
                            <x-dg.empty title="Aucune intervention ne correspond à ce filtre.">
                                <span>Réinitialisez le filtre pour revoir la chronologie complète.</span>
                            </x-dg.empty>
                        @else
                            <x-dg.empty title="Aucune intervention enregistrée.">
                                <span>L’accompagnement peut commencer sans dossier lourd ; la chronologie s’enrichira au fur et à mesure des actions réelles.</span>
                            </x-dg.empty>
                        @endif
                    @endforelse
                </div>
            </x-dg.card>
        </div>
    </x-dg.shell>
</x-layouts.portal>
