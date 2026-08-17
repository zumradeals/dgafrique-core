{{--
    Mon espace — mon centre d'action. Reproduction fidèle du handoff Claude
    (docs/design/reference/claude-2026-08-16/) : une seule priorité dominante, puis des rangs
    nommés dans le temps (Ensuite, Cette semaine), puis une colonne d'ancrage. Jamais de grille
    de compteurs comme langage principal.
--}}
<x-layouts.portal title="Mon espace — DG Afrique">
    <x-dg.shell current="espace" :identity="$identity" :is-administrator="$isAdministrator">
        <div style="max-width:1600px;margin:0 auto;padding:28px 20px 48px" class="lg:!px-11 lg:!py-11">

            <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:32px;flex-wrap:wrap;margin-bottom:24px">
                <div style="display:flex;flex-direction:column;gap:10px">
                    <x-dg.label tone="copper">{{ now()->locale('fr')->translatedFormat('l j F') }}</x-dg.label>
                    <h1 class="dg-display" style="font-size:clamp(38px, 6vw, 56px)">Bonjour {{ $greetingName }}.</h1>
                    <p style="margin:0;font-size:17px;line-height:1.6;color:var(--dg-text);max-width:56ch">
                        @if($priority)
                            {{ $priority['body'] }}
                        @else
                            Rien ne réclame une décision aujourd’hui. Le reste de votre espace reste disponible ci-dessous.
                        @endif
                    </p>
                </div>
                <div style="display:flex;align-items:center;gap:12px;padding:14px 18px;border-radius:16px;background:var(--dg-card);border:1px solid var(--dg-line)">
                    <span class="dg-avatar" style="width:40px;height:40px;border-radius:13px;font-size:18px">{{ mb_strtoupper(mb_substr($identity->label, 0, 2)) }}</span>
                    <div style="display:flex;flex-direction:column;gap:2px">
                        <strong style="font-size:14px;color:var(--dg-forest)">Identité reconnue</strong>
                        <span style="font-size:12px;color:var(--dg-muted)">Compte vérifié · profil {{ $profileCompletion }} %</span>
                    </div>
                </div>
            </div>

            <div style="display:grid;gap:24px" class="lg:grid-cols-[minmax(0,1.55fr)_minmax(0,.95fr)]">
                <div style="display:flex;flex-direction:column;gap:24px;min-width:0">

                    @if($priority)
                        <x-dg.deep>
                            <div style="display:flex;flex-direction:column;gap:12px">
                                <x-dg.label tone="saffron">{{ $priority['label'] }}</x-dg.label>
                                <h2 class="dg-display dg-display--lead" style="max-width:24ch">{{ $priority['heading'] }}</h2>
                                <p style="margin:0;font-size:15px;line-height:1.65;color:var(--dg-on-deep-text);max-width:56ch">{{ $priority['body'] }}</p>
                                <div style="display:flex;flex-direction:column;gap:10px;margin-top:8px" class="sm:flex-row">
                                    <x-dg.btn variant="saffron" :href="$priority['primary']['href']" style="padding:14px 22px">{{ $priority['primary']['label'] }}</x-dg.btn>
                                </div>
                            </div>
                        </x-dg.deep>
                    @else
                        <x-dg.deep>
                            <div style="display:flex;flex-direction:column;gap:10px">
                                <x-dg.label tone="saffron">Aujourd’hui</x-dg.label>
                                <h2 class="dg-display dg-display--lead" style="max-width:24ch">Rien ne réclame une décision maintenant.</h2>
                                <p style="margin:0;font-size:15px;line-height:1.65;color:var(--dg-on-deep-text);max-width:56ch">DG Afrique ne fabrique pas de priorité artificielle. Revenez dès qu’un besoin, un projet ou une ZUMRA produira un mouvement réel qui vous concerne.</p>
                            </div>
                        </x-dg.deep>
                    @endif

                    @if($nextItems->isNotEmpty())
                        <div style="display:flex;flex-direction:column;gap:14px">
                            <div style="display:flex;align-items:flex-end;justify-content:space-between;padding-top:8px;border-top:1px solid var(--dg-line-section)">
                                <div style="display:flex;flex-direction:column;gap:2px;padding-top:12px">
                                    <x-dg.label>Ensuite</x-dg.label>
                                    <h3 class="dg-display dg-display--rank">Vos capacités peuvent servir ici</h3>
                                </div>
                                <a href="{{ route('needs.index') }}" style="font-size:14px;font-weight:600">Tous les besoins →</a>
                            </div>

                            @foreach($nextItems as $item)
                                <x-dg.card>
                                    <div style="display:flex;flex-direction:column;gap:12px">
                                        <div style="display:flex;align-items:center;gap:10px">
                                            <x-dg.badge tone="need">{{ $item['event_label'] }}</x-dg.badge>
                                            @if($item['location'])<span class="dg-meta">{{ $item['location'] }}</span>@endif
                                        </div>
                                        <strong style="font-size:19px;color:var(--dg-forest);line-height:1.3">
                                            <a href="{{ $item['action_url'] }}" style="color:inherit">{{ $item['title'] }}</a>
                                        </strong>
                                        @if($item['context'])
                                            <x-dg.note>{{ $item['context'] }}</x-dg.note>
                                        @endif
                                        <div style="display:flex;gap:10px;flex-wrap:wrap">
                                            @if(! $item['can_decide'])
                                                <x-dg.btn variant="need" :href="$item['contact_url']" method="POST">Je peux aider</x-dg.btn>
                                            @endif
                                            <x-dg.btn variant="quiet" :href="$item['comment_url']">Commenter</x-dg.btn>
                                        </div>
                                    </div>
                                </x-dg.card>
                            @endforeach
                        </div>
                    @endif

                    @if($weekItems->isNotEmpty())
                        <div style="display:flex;flex-direction:column;gap:14px">
                            <div style="display:flex;align-items:flex-end;justify-content:space-between;padding-top:8px;border-top:1px solid var(--dg-line-section)">
                                <div style="display:flex;flex-direction:column;gap:2px;padding-top:12px">
                                    <x-dg.label>Cette semaine</x-dg.label>
                                    <h3 class="dg-display dg-display--rank">Ce qui avance</h3>
                                </div>
                                <a href="{{ route('projects.index') }}" style="font-size:14px;font-weight:600">Mes projets →</a>
                            </div>

                            @foreach($weekItems as $item)
                                <x-dg.card>
                                    <div style="display:flex;flex-direction:column;gap:14px">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <strong style="font-size:19px;color:var(--dg-forest)">
                                                <a href="{{ $item['action_url'] }}" style="color:inherit">{{ $item['title'] }}</a>
                                            </strong>
                                            <x-dg.badge :tone="$item['kind'] === 'PROJECTS' ? 'project' : 'on-deep'">{{ $item['event_label'] }}</x-dg.badge>
                                        </div>
                                        @if($item['kind'] === 'PROJECTS' && $item['maturity'])
                                            <x-dg.maturity :stages="array_slice(\App\Application\Projects\ProjectMaturityService::STAGES, 0, 6, true)" :current="$item['maturity']" />
                                        @endif
                                        <span class="dg-meta">{{ $item['summary'] }}</span>
                                    </div>
                                </x-dg.card>
                            @endforeach
                        </div>
                    @endif
                </div>

                <aside class="dg-stack" style="align-self:flex-start">
                    @if($myGroups->isNotEmpty())
                        <div>
                            <x-dg.label>Mes ZUMRA</x-dg.label>
                            @foreach($myGroups as $group)
                                <div class="dg-person">
                                    <x-dg.avatar :initials="mb_strtoupper(mb_substr($group->name, 0, 2))" size="md" />
                                    <div>
                                        <div class="dg-person__name">{{ $group->name }}</div>
                                        <div class="dg-person__role">Membre actif</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($recommendedPeople)
                        <div>
                            <x-dg.label>Personnes pertinentes</x-dg.label>
                            @foreach($recommendedPeople as $recommendation)
                                <x-dg.person
                                    :name="$recommendation['profile']->discovery_display_name"
                                    :role="$recommendation['reasons'][0] ?? null"
                                    size="md"
                                    tone="copper"
                                />
                            @endforeach
                        </div>
                    @endif

                    @if($receivedShares)
                        <div>
                            <x-dg.label>Reçus pour vous</x-dg.label>
                            @foreach($receivedShares as $share)
                                <div>
                                    <strong style="font-size:15px;color:var(--dg-forest)"><a href="{{ $share['source_url'] }}" style="color:inherit">Un partage avec contexte</a></strong>
                                    <div class="dg-meta" style="line-height:1.55">« {{ $share['context_note'] }} » — {{ $share['sharer_label'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="dg-stack--tool">
                        <x-dg.label style="color:#7C8B9C">Mes outils</x-dg.label>
                        <form method="POST" action="{{ route('federation.continue.gamadrive') }}">
                            @csrf
                            <button type="submit" style="width:100%;display:flex;align-items:flex-start;gap:12px;border:0;background:transparent;text-align:left;font:inherit;cursor:pointer">
                                <span class="dg-tool__mark">GD</span>
                                <span>
                                    <strong style="display:block;font-size:15px;color:var(--dg-night)">GamaDrive</strong>
                                    <span class="dg-meta" style="line-height:1.55">Ouvrir votre espace documentaire.</span>
                                </span>
                            </button>
                        </form>
                        <span class="dg-meta" style="line-height:1.55;padding-top:10px;border-top:1px solid var(--dg-tint-tool-line)">Un outil n’existe ici que rattaché à une action réelle.</span>
                    </div>
                </aside>
            </div>

            @if(! $priority && $nextItems->isEmpty() && $weekItems->isEmpty())
                <div style="margin-top:24px;display:flex;flex-direction:column;gap:14px;max-width:640px">
                    <x-dg.empty title="Aucun besoin ne correspond encore">
                        <span>DG Afrique ne fabrique pas de contenu pour remplir l’écran. Dès qu’un besoin réel touchera vos capacités, il apparaîtra ici.</span>
                    </x-dg.empty>
                    @if(! $myGroups->count() && (! $zumraMembership))
                        <x-dg.empty title="Vous n’êtes membre d’aucune ZUMRA">
                            <span>Découvrez les ZUMRA existantes, ou proposez la vôtre une fois adhérent au Programme.</span>
                        </x-dg.empty>
                    @endif
                </div>
            @endif
        </div>
    </x-dg.shell>
</x-layouts.portal>
