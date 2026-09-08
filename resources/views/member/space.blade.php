<x-layouts.member title="Mon espace" active="space" :wide="true">
    @php
        // La priorité reste entièrement fournie par le moteur existant. Le cockpit ne fait que la projeter.
        $showFirstSteps = $isNewMember && ($priority === null || $priority['primary']['href'] === route('member.profile.edit'));
        $profileReady = $profileCompletion >= 100;
    @endphp

    <div class="dg-space-cockpit">
        <section class="dg-cockpit-hero" aria-labelledby="cockpit-title">
            <div class="dg-cockpit-hero-main">
                <div class="dg-cockpit-hero-copy">
                    <p class="dg-cockpit-kicker">DES TALENTS AUJOURD’HUI, UN IMPACT PLUS FORT DEMAIN</p>
                    <h1 id="cockpit-title">{{ $isNewMember ? 'Bienvenue chez vous.' : 'Bonjour, '.$greetingName.'.' }}</h1>
                    <p class="dg-cockpit-lead">{{ $isNewMember ? 'Faisons avancer ce qui compte pour vous.' : 'Voici ce qui compte pour vous aujourd’hui.' }}</p>
                    <p class="dg-cockpit-intro">Ici, vous trouvez des personnes, des opportunités et des projets pour transformer ce que vous savez faire, ce dont vous avez besoin et vos idées en actions concrètes.</p>

                    <div class="dg-cockpit-values" aria-label="Ce que GAMAD rend possible">
                        <div class="dg-cockpit-value"><span aria-hidden="true">●</span><span>Une communauté engagée</span></div>
                        <div class="dg-cockpit-value"><span aria-hidden="true">↗</span><span>Des actions concrètes</span></div>
                        <div class="dg-cockpit-value"><span aria-hidden="true">▥</span><span>Des réalisations visibles</span></div>
                    </div>

                    <div class="dg-cockpit-mobile-account">
                        <a class="dg-cockpit-alert-link" href="{{ route('member.profile.edit') }}">Mon profil →</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dg-member-logout" type="submit">Déconnexion</button>
                        </form>
                    </div>
                </div>

                <div class="dg-cockpit-hero-art" aria-hidden="true">
                    <img
                        src="{{ asset('images/entry/ensemble-640.webp') }}"
                        srcset="{{ asset('images/entry/ensemble-640.webp') }} 640w, {{ asset('images/entry/ensemble-1280.webp') }} 1280w"
                        sizes="(min-width: 1200px) 45vw, 100vw"
                        alt=""
                        width="1280"
                        height="853"
                    >
                </div>
            </div>

            <aside class="dg-cockpit-hero-aside" aria-label="Votre prochain pas et votre situation">
                @if ($showFirstSteps)
                    <section class="dg-cockpit-card" aria-labelledby="premier-pas">
                        <p class="dg-cockpit-kicker">COMMENCER SIMPLEMENT</p>
                        <h2 id="premier-pas">Quel sera votre premier pas ?</h2>
                        <p>Choisissez une action simple pour commencer.</p>
                        <div class="dg-cockpit-intentions">
                            <a class="dg-cockpit-intention" href="#ajouter-savoir-faire">
                                <span class="dg-cockpit-intention__icon"><x-dg.icon name="transmission" /></span>
                                <span><strong>Partager un savoir-faire</strong><small>Je peux apporter quelque chose.</small></span>
                                <span aria-hidden="true">→</span>
                            </a>
                            <a class="dg-cockpit-intention" href="{{ route('needs.create') }}">
                                <span class="dg-cockpit-intention__icon"><x-dg.icon name="need" /></span>
                                <span><strong>Exprimer un besoin</strong><small>J’ai un besoin à faire avancer.</small></span>
                                <span aria-hidden="true">→</span>
                            </a>
                            <a class="dg-cockpit-intention" href="{{ route('projects.index') }}">
                                <span class="dg-cockpit-intention__icon"><x-dg.icon name="people" /></span>
                                <span><strong>Participer à une action</strong><small>Je veux participer à une action ou un projet.</small></span>
                                <span aria-hidden="true">→</span>
                            </a>
                            <a class="dg-cockpit-intention" href="{{ route('people.index') }}">
                                <span class="dg-cockpit-intention__icon"><x-dg.icon name="discover" /></span>
                                <span><strong>Explorer le réseau</strong><small>Je veux découvrir les personnes, besoins et projets.</small></span>
                                <span aria-hidden="true">→</span>
                            </a>
                        </div>
                        <p class="dg-cockpit-note">Un premier pas suffit. Vous pourrez compléter votre profil ensuite.</p>
                    </section>
                @else
                    <section class="dg-cockpit-card" aria-labelledby="dg-space-priority-title">
                        <p class="dg-cockpit-kicker">{{ $priority['label'] ?? 'CE QUI COMPTE MAINTENANT' }}</p>
                        <h2 id="dg-space-priority-title">{{ $priority['heading'] ?? 'Tout est à jour.' }}</h2>
                        <p>{{ $priority['body'] ?? 'Rien ne réclame une décision maintenant.' }}</p>
                        <div class="dg-cockpit-situation-action">
                            <x-dg.button :href="$priority['primary']['href'] ?? route('opportunities.index')" variant="solar">
                                {{ $priority['primary']['label'] ?? 'Explorer les possibilités' }}
                            </x-dg.button>
                        </div>
                    </section>
                @endif

                <section class="dg-cockpit-card" aria-labelledby="situation-title">
                    <div class="dg-cockpit-section-heading">
                        <div>
                            <p class="dg-cockpit-kicker">VOTRE SITUATION</p>
                            <h2 id="situation-title">Votre situation</h2>
                        </div>
                        <a class="dg-cockpit-alert-link" href="{{ route('member.profile.edit') }}">Voir mon profil →</a>
                    </div>
                    <ul class="dg-cockpit-situation-list">
                        <li class="dg-cockpit-situation-item">
                            <span class="dg-cockpit-status-icon" aria-hidden="true">✓</span>
                            <span><strong>Compte vérifié</strong><small>Votre identité est confirmée.</small></span>
                        </li>
                        <li class="dg-cockpit-situation-item">
                            <span class="dg-cockpit-status-icon {{ $profileReady ? '' : 'dg-cockpit-status-icon--progress' }}" aria-hidden="true">{{ $profileReady ? '✓' : '◷' }}</span>
                            <span><strong>{{ $profileReady ? 'Profil prêt' : 'Profil en cours' }}</strong><small>{{ $profileReady ? 'Vos informations essentielles sont renseignées.' : 'Quelques informations peuvent encore être complétées.' }}</small></span>
                        </li>
                        <li class="dg-cockpit-situation-item">
                            @if ($showFirstSteps)
                                <span class="dg-cockpit-status-icon dg-cockpit-status-icon--progress" aria-hidden="true">→</span>
                                <span><strong>Premier pas à choisir</strong><small>Choisissez ce qui compte pour vous aujourd’hui.</small></span>
                            @elseif ($priority)
                                <span class="dg-cockpit-status-icon dg-cockpit-status-icon--info" aria-hidden="true">!</span>
                                <span><strong>Une priorité active</strong><small>Votre prochaine action est indiquée juste au-dessus.</small></span>
                            @else
                                <span class="dg-cockpit-status-icon dg-cockpit-status-icon--calm" aria-hidden="true">–</span>
                                <span><strong>Aucune action urgente</strong><small>Vous êtes à jour.</small></span>
                            @endif
                        </li>
                        <li class="dg-cockpit-situation-item">
                            <span class="dg-cockpit-status-icon dg-cockpit-status-icon--info" aria-hidden="true">▦</span>
                            <span><strong>{{ $opportunitiesCount > 0 ? $opportunitiesCount.' opportunité'.($opportunitiesCount > 1 ? 's' : '') : 'Réseau disponible' }}</strong><small>{{ $opportunitiesCount > 0 ? 'Des possibilités correspondent à votre situation.' : 'Explorez à votre rythme les personnes, besoins et projets.' }}</small></span>
                        </li>
                    </ul>
                    @if (!$profileReady)
                        <div class="dg-cockpit-situation-action"><x-dg.button :href="route('member.profile.edit')" variant="solar">Compléter mon profil</x-dg.button></div>
                    @endif
                </section>
            </aside>
        </section>

        @if ($hasOtherAttention)
            <a class="dg-cockpit-alert-link" href="{{ route('notifications.index') }}"><span>D’autres éléments attendent votre attention.</span><span aria-hidden="true">→</span></a>
        @endif

        <section class="dg-cockpit-main-grid" aria-label="Actions et outils">
            <section class="dg-cockpit-actions" aria-labelledby="actions-maintenant-title">
                <div class="dg-cockpit-section-heading">
                    <div>
                        <p class="dg-cockpit-kicker">PETITES ACTIONS, IMPACT CONCRET</p>
                        <h2 id="actions-maintenant-title">Ce que vous pouvez faire maintenant</h2>
                        <p>Des actions simples pour avancer dès aujourd’hui.</p>
                    </div>
                </div>

                <div class="dg-cockpit-action-stack">
                    <article id="ajouter-savoir-faire" class="dg-cockpit-action-card dg-cockpit-action-card--skill">
                        <span class="dg-cockpit-action-icon"><x-dg.icon name="transmission" /></span>
                        <div>
                            <h3>Ajouter un savoir-faire</h3>
                            <p>Indiquez un domaine dans lequel vous pouvez aider d’autres membres.</p>
                            <form class="dg-cockpit-inline-form" method="POST" action="{{ route('member.capability.quick') }}">
                                @csrf
                                <x-dg.input id="capability" :value="old('capability')" required minlength="3" maxlength="200" :invalid="$errors->has('capability')" aria-describedby="capability-error" placeholder="Ex. réparation solaire" />
                                <x-dg.button type="submit">Enregistrer</x-dg.button>
                            </form>
                            <p id="capability-error" class="dg-space-error">@error('capability'){{ $message }}@enderror</p>
                        </div>
                    </article>

                    <article class="dg-cockpit-action-card dg-cockpit-action-card--need">
                        <span class="dg-cockpit-action-icon"><x-dg.icon name="need" /></span>
                        <div>
                            <h3>Publier un besoin</h3>
                            <p>Faites part d’un besoin pour le faire avancer avec le réseau.</p>
                            <x-dg.button :href="route('needs.create')" variant="solar">Exprimer mon besoin</x-dg.button>
                        </div>
                    </article>

                    <article class="dg-cockpit-action-card dg-cockpit-action-card--opportunity">
                        <span class="dg-cockpit-action-icon"><x-dg.icon name="discover" /></span>
                        <div>
                            <h3>Découvrir des opportunités</h3>
                            <p>{{ $opportunitiesCount > 0 ? $opportunitiesCount.' possibilité'.($opportunitiesCount > 1 ? 's correspondent' : ' correspond').' actuellement à votre situation.' : 'Explorez les besoins, projets et possibilités qui peuvent vous correspondre.' }}</p>
                            <x-dg.button :href="route('opportunities.index')" variant="progress">Voir les opportunités</x-dg.button>
                        </div>
                    </article>
                </div>

                @if ($projectDraft || $nextItems->isNotEmpty() || $weekItems->isNotEmpty() || count($receivedShares))
                    <div class="dg-cockpit-context" aria-label="À suivre">
                        <p class="dg-cockpit-kicker">À SUIVRE</p>
                        @if ($projectDraft)
                            <a class="dg-cockpit-context-row" href="{{ route('projects.draft.show', ['draft' => $projectDraft, 'step' => $projectDraft->current_step]) }}">
                                <x-dg.icon name="project" /><span><strong>Votre projet en préparation</strong><small>Votre brouillon vous attend.</small></span><span aria-hidden="true">→</span>
                            </a>
                        @endif
                        @foreach ($nextItems->concat($weekItems) as $item)
                            <a class="dg-cockpit-context-row" href="{{ $item['action_url'] }}"><x-dg.icon name="activity" /><span><strong>{{ $item['title'] }}</strong><small>{{ $item['summary'] }}</small></span><span aria-hidden="true">→</span></a>
                        @endforeach
                        @foreach ($receivedShares as $share)
                            <a class="dg-cockpit-context-row" href="{{ $share['source_url'] }}"><x-dg.icon name="people" /><span><strong>{{ $share['source_title'] }}</strong><small>{{ $share['sharer_label'] }} · {{ $share['context_note'] }}</small></span><span aria-hidden="true">→</span></a>
                        @endforeach
                    </div>
                @endif
            </section>

            <aside>
                @include('member.tools')
            </aside>
        </section>

        @if (!$isNewMember || $myGroups->isNotEmpty() || $myOrganizations->isNotEmpty())
            <section class="dg-cockpit-engagements" aria-labelledby="engagements-title">
                <div class="dg-cockpit-section-heading">
                    <div><p class="dg-cockpit-kicker">VOS LIENS ACTIFS</p><h2 id="engagements-title">Mes engagements</h2></div>
                    <a class="dg-cockpit-alert-link" href="{{ route('zumra.index') }}">Ouvrir ZUMRA →</a>
                </div>
                @if ($myGroups->isNotEmpty() || $myOrganizations->isNotEmpty())
                    @if ($myOrganizations->isNotEmpty())
                        <h3>Mes Organisations</h3>
                    @endif
                    <div class="dg-cockpit-engagement-list">
                        @foreach ($myGroups as $group)
                            <a class="dg-cockpit-quick-link" href="{{ route('zumra.groups.show', $group) }}"><span class="dg-cockpit-quick-icon"><x-dg.icon name="zumra" /></span><span><strong>{{ $group->name }}</strong><small>ZUMRA</small></span><span aria-hidden="true">→</span></a>
                        @endforeach
                        @foreach ($myOrganizations as $organization)
                            <a class="dg-cockpit-quick-link" href="{{ route('organizations.show', $organization) }}"><span class="dg-cockpit-quick-icon"><x-dg.icon name="project" /></span><span><strong>{{ $organization->name }}</strong><small>Mon organisation</small></span><span aria-hidden="true">→</span></a>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        <section id="vos-acces-rapides" class="dg-cockpit-quick-access" aria-labelledby="quick-title">
            <div class="dg-cockpit-quick-access__header">
                <div><p class="dg-cockpit-kicker">TOUT RESTE À PORTÉE DE MAIN</p><h2 id="quick-title">Vos accès rapides</h2></div>
            </div>
            <div class="dg-cockpit-quick-grid">
                <a class="dg-cockpit-quick-link" href="{{ route('member.profile.edit') }}"><span class="dg-cockpit-quick-icon"><x-dg.icon name="space" /></span><span><strong>Mon profil</strong><small>Gérez votre présentation et votre visibilité.</small></span><span aria-hidden="true">→</span></a>
                <a class="dg-cockpit-quick-link" href="{{ route('people.index') }}"><span class="dg-cockpit-quick-icon"><x-dg.icon name="people" /></span><span><strong>Personnes</strong><small>Découvrez les personnes avec qui agir.</small></span><span aria-hidden="true">→</span></a>
                <a class="dg-cockpit-quick-link" href="{{ route('projects.index') }}"><span class="dg-cockpit-quick-icon"><x-dg.icon name="project" /></span><span><strong>Projets</strong><small>Découvrez les projets et collaborations du réseau.</small></span><span aria-hidden="true">→</span></a>
            </div>
        </section>

        <footer class="dg-cockpit-footer">
            <p class="dg-cockpit-footer__manifesto">Ici, la valeur ne se mesure pas en likes.<br>Elle se construit par les contributions et les réalisations.</p>
            <div class="dg-cockpit-footer__bar">
                <div class="dg-cockpit-footer__brand"><strong>GAMAD</strong><small>Des personnes. Des actions. Un impact réel.</small></div>
                <nav class="dg-cockpit-footer__links" aria-label="Liens de fin de page"><a href="{{ route('gateway') }}">À propos</a><a href="{{ route('member.profile.edit') }}">Aide</a><a href="mailto:contact@dgafrique.com">Contact</a></nav>
            </div>
        </footer>
    </div>
</x-layouts.member>
