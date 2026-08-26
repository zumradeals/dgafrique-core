{{--
    Mon espace V2 — centre d'action personnel.
    Une priorité réelle domine ; les gestes rapides et les contextes existants viennent ensuite.
    Aucun compteur de popularité, aucune donnée fictive, aucune décision automatique.
--}}
<x-layouts.portal title="Mon espace — DG Afrique">
    <x-dg.shell current="espace" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-space-v2">

            <section class="dg-space-hero" aria-labelledby="dg-space-title">
                <div class="dg-space-hero__copy">
                    <div class="dg-space-eyebrow">{{ now()->locale('fr')->translatedFormat('l j F') }}</div>
                    <h1 id="dg-space-title">Bonjour {{ $greetingName }}.</h1>
                    <p class="dg-space-hero__lead">Que souhaitez-vous mettre en mouvement aujourd’hui ?</p>
                </div>

                <div class="dg-space-identity" aria-label="Votre profil DG Afrique">
                    <span class="dg-space-identity__avatar">{{ mb_strtoupper(mb_substr($identity->label, 0, 2)) }}</span>
                    <div>
                        <div class="dg-space-identity__title">
                            <strong>Votre présence dans le réseau</strong>
                        </div>
                        <div class="dg-space-identity__meta">Identité reconnue · profil {{ $profileCompletion }} %</div>
                    </div>
                    <div class="dg-space-progress">
                        <div class="dg-space-progress__track" aria-hidden="true">
                            <div class="dg-space-progress__fill" style="width:{{ $profileCompletion }}%"></div>
                        </div>
                        <a href="{{ route('member.profile.edit') }}">Mon profil →</a>
                    </div>
                </div>
            </section>

            @if($isNewMember)
                {{--
                    UIUX-001 — routeur de « première intention ». Un membre encore sans relation
                    réelle avec le réseau (docs/product/EXPERIENCE-PRODUIT-CANONIQUE.md §15) voit
                    d'abord ces quatre intentions plutôt que les actions rapides habituelles — et
                    n'y rencontre plus « Ouvrir ZUMRA » en premier niveau. Un routeur UX uniquement :
                    aucun nouveau modèle métier, chaque intention mène à une capacité existante.
                --}}
                <nav class="dg-space-actions dg-space-intent" aria-label="Qu’est-ce que vous voulez mettre en mouvement ?">
                    <details class="dg-space-action">
                        <summary>
                            <span class="dg-space-action__icon" aria-hidden="true">+</span>
                            <span class="dg-space-action__text">
                                <strong>Je peux apporter quelque chose</strong>
                                <span>Déclarer une première capacité, en une phrase.</span>
                            </span>
                        </summary>
                        <div class="dg-space-intent-reveal">
                            <form method="POST" action="{{ route('member.capability.quick') }}">
                                @csrf
                                <input type="text" name="capability" minlength="3" maxlength="200" required
                                       placeholder="Ex. : Je sais réparer des vélos.">
                                <button type="submit" class="dg-btn dg-btn--primary">Déclarer</button>
                            </form>
                            {{-- UIUX-007 — référence légère vers le profil complet, sans dupliquer
                                 cette déclaration rapide : les deux écrivent déjà le même champ
                                 (existing_skills / CapabilityStatement), une seule voie d'approfondissement. --}}
                            <p class="dg-hint" style="margin-top:8px">Pour préciser transmission, apprentissage ou preuves d’expérience, complétez <a href="{{ route('member.profile.edit') }}">votre profil complet</a>.</p>
                        </div>
                    </details>

                    <a class="dg-space-action dg-space-action--primary" href="{{ route('needs.create') }}">
                        <span class="dg-space-action__icon" aria-hidden="true">!</span>
                        <span class="dg-space-action__text">
                            <strong>J’ai un besoin</strong>
                            <span>Exprimer ce qui vous manque pour avancer.</span>
                        </span>
                    </a>

                    <a class="dg-space-action" href="{{ route('activity.index') }}">
                        <span class="dg-space-action__icon" aria-hidden="true">↗</span>
                        <span class="dg-space-action__text">
                            <strong>Je veux découvrir</strong>
                            <span>Voir ce qui bouge réellement dans le réseau.</span>
                        </span>
                    </a>

                    <details class="dg-space-action">
                        <summary>
                            <span class="dg-space-action__icon" aria-hidden="true">→</span>
                            <span class="dg-space-action__text">
                                <strong>Je veux participer</strong>
                                <span>Rejoindre une action collective déjà en cours.</span>
                            </span>
                        </summary>
                        <div class="dg-space-intent-reveal dg-space-intent-links">
                            <a href="{{ route('zumra.index') }}">Rejoindre un collectif actif →</a>
                            <a href="{{ route('organizations.index') }}">Rejoindre une organisation →</a>
                            {{-- UIUX-007 — doctrine humaine §6 : une personne seule peut déjà créer
                                 une ZUMRA réelle et opérationnelle, jamais après avoir dû réunir
                                 cinq responsables — la seule condition réelle et préexistante
                                 (art. 5 ZUMRA-DOCTRINE-INVARIANTE.md) reste l'adhésion active au
                                 Programme ZUMRA, jamais touchée par cette mission. Ce routeur n'est
                                 visible que pour un nouveau membre ($isNewMember), qui par
                                 construction n'a encore aucune adhésion ($zumraMembership === null,
                                 cf. isNewMember()) : le lien mène donc toujours à l'adhésion
                                 d'abord. Le lien de création directe équivalent existe déjà, pour
                                 un membre du Programme actif sans groupe, dans le rail « Mes
                                 structures » ci-dessous. --}}
                            <a href="{{ route('zumra.membership.show') }}">Adhérer au Programme ZUMRA pour créer ma ZUMRA →</a>
                        </div>
                    </details>
                </nav>
            @else
                <nav class="dg-space-actions" aria-label="Actions rapides">
                    <a class="dg-space-action dg-space-action--primary" href="{{ route('needs.create') }}">
                        <span class="dg-space-action__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke-width="2" stroke-linecap="round"/></svg>
                        </span>
                        <span class="dg-space-action__text">
                            <strong>Exprimer un besoin</strong>
                            <span>Faire apparaître ce qui vous manque pour avancer.</span>
                        </span>
                    </a>

                    <a class="dg-space-action" href="{{ route('projects.create') }}">
                        <span class="dg-space-action__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M5 18.5V9.5l7-4 7 4v9M8.5 13h7M12 9.5v7" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <span class="dg-space-action__text">
                            <strong>Lancer un projet</strong>
                            <span>Transformer une intention en projet structuré.</span>
                        </span>
                    </a>

                    <a class="dg-space-action" href="{{ route('people.index') }}">
                        <span class="dg-space-action__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><circle cx="10" cy="9" r="3" stroke-width="1.7"/><path d="M4.5 18c.5-3 2.4-4.7 5.5-4.7s5 1.7 5.5 4.7M17 6.5v5M14.5 9h5" stroke-width="1.7" stroke-linecap="round"/></svg>
                        </span>
                        <span class="dg-space-action__text">
                            <strong>Trouver une personne</strong>
                            <span>Découvrir des capacités rendues volontairement visibles.</span>
                        </span>
                    </a>

                    <a class="dg-space-action" href="{{ route('zumra.index') }}">
                        <span class="dg-space-action__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="7.5" stroke-width="1.7"/><path d="M8.5 8.5h7l-7 7h7" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <span class="dg-space-action__text">
                            <strong>Ouvrir ZUMRA</strong>
                            <span>Rejoindre vos collectifs et leurs actions.</span>
                        </span>
                    </a>
                </nav>
            @endif

            <div class="dg-space-layout">
                <main class="dg-space-main">
                    @if($priority)
                        <section class="dg-space-priority" aria-labelledby="dg-space-priority-title">
                            <div class="dg-space-priority__label">{{ $priority['label'] }}</div>
                            <h2 id="dg-space-priority-title">{{ $priority['heading'] }}</h2>
                            <p>{{ $priority['body'] }}</p>
                            <div class="dg-space-priority__actions">
                                <a class="dg-space-priority__cta" href="{{ $priority['primary']['href'] }}">{{ $priority['primary']['label'] }} →</a>
                            </div>
                        </section>
                    @else
                        <section class="dg-space-priority dg-space-priority--quiet" aria-labelledby="dg-space-priority-title">
                            <div class="dg-space-priority__label">Aujourd’hui</div>
                            <h2 id="dg-space-priority-title">Rien ne réclame une décision maintenant.</h2>
                            <p>Votre espace est calme. DG Afrique ne fabrique pas de priorité artificielle : utilisez les actions ci-dessus ou revenez lorsqu’un besoin, un projet, une mission ou une ZUMRA produira un mouvement réel qui vous concerne.</p>
                        </section>
                    @endif

                    @if($hasOtherAttention)
                        {{-- UIUX-002 — décision #2 : un signal texte, jamais un badge numérique, vers
                             /notifications — la source complète du reste de l'attention. --}}
                        <p class="dg-space-attention-note">
                            {{ $priority ? 'D’autres éléments attendent votre attention.' : 'Des éléments attendent votre attention ailleurs.' }}
                            <a href="{{ route('notifications.index') }}">Voir mes notifications →</a>
                        </p>
                    @endif

                    @if($nextItems->isNotEmpty())
                        <section class="dg-space-section" aria-labelledby="dg-space-needs-title">
                            <div class="dg-space-section__head">
                                <div>
                                    <div class="dg-space-section__kicker">Pour vous maintenant</div>
                                    <h3 id="dg-space-needs-title">Vos capacités peuvent servir ici</h3>
                                </div>
                                <a href="{{ route('needs.index') }}">Tous les besoins →</a>
                            </div>

                            <div class="dg-space-list">
                                @foreach($nextItems as $item)
                                    <article class="dg-space-item">
                                        <div class="dg-space-item__top">
                                            <span class="dg-space-item__badge">{{ $item['event_label'] }}</span>
                                            @if($item['location'])<span class="dg-space-item__meta">{{ $item['location'] }}</span>@endif
                                        </div>
                                        <h4><a href="{{ $item['action_url'] }}">{{ $item['title'] }}</a></h4>
                                        @if($item['context'])
                                            <div class="dg-space-item__context">{{ $item['context'] }}</div>
                                        @endif
                                        <div class="dg-space-item__actions">
                                            @if(! $item['can_decide'])
                                                <form method="POST" action="{{ $item['contact_url'] }}">
                                                    @csrf
                                                    <button type="submit" class="dg-space-mini-btn dg-space-mini-btn--orange">Je peux aider</button>
                                                </form>
                                            @endif
                                            <a class="dg-space-mini-btn" href="{{ $item['comment_url'] }}">Commenter</a>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @elseif(! $priority)
                        <div class="dg-space-empty">
                            <strong>Aucun besoin ne correspond encore</strong>
                            <span>DG Afrique ne fabrique pas de contenu pour remplir l’écran. Dès qu’un besoin réel touchera vos capacités, il apparaîtra ici.</span>
                        </div>
                    @endif

                    @if($weekItems->isNotEmpty())
                        <section class="dg-space-section" aria-labelledby="dg-space-week-title">
                            <div class="dg-space-section__head">
                                <div>
                                    <div class="dg-space-section__kicker">Cette semaine</div>
                                    <h3 id="dg-space-week-title">Ce qui avance autour de vous</h3>
                                </div>
                                <a href="{{ route('projects.index') }}">Mes projets →</a>
                            </div>

                            <div class="dg-space-list">
                                @foreach($weekItems as $item)
                                    <article class="dg-space-item">
                                        <div class="dg-space-item__top">
                                            <h4><a href="{{ $item['action_url'] }}">{{ $item['title'] }}</a></h4>
                                            <span class="dg-space-item__badge">{{ $item['event_label'] }}</span>
                                        </div>
                                        {{-- BETA-READY-003 — un item « Cette semaine » peut être un Projet réel (clé 'maturity'
                                             toujours présente) ou une Mission dont le contexte est un Projet (kind coercé à
                                             'PROJECTS' par ActivityFeedService::MISSION_CONTEXT_FILTER, sans jamais porter de
                                             maturité de Projet) : accès protégé, jamais un plantage sur ce second cas réel. --}}
                                        @if($item['kind'] === 'PROJECTS' && ($item['maturity'] ?? null))
                                            <x-dg.maturity :stages="array_slice(\App\Application\Projects\ProjectMaturityService::STAGES, 0, 6, true)" :current="$item['maturity']" />
                                        @endif
                                        <span class="dg-space-item__meta">{{ $item['summary'] }}</span>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </main>

                <aside class="dg-space-rail" aria-label="Votre contexte">
                    {{-- UIUX-009B — brouillon de Projet en préparation, reprenable ici sans jamais
                         afficher de pourcentage de complétion artificiel (voir audit UIUX-009B
                         Phase A : la complétion réelle d'un brouillon n'est pas un fait mesurable
                         de façon honnête). Card discrète, jamais la priorité dominante. --}}
                    @if($projectDraft)
                        <section class="dg-space-rail-card">
                            <div class="dg-space-rail-card__head">
                                <div class="dg-space-rail-card__kicker">Projet en préparation</div>
                                <h3>{{ $projectDraft->payload['name'] ?? 'Votre idée de projet' }}</h3>
                            </div>
                            <div class="dg-space-rail-row">
                                <span class="dg-space-rail-row__mark">{{ mb_strtoupper(mb_substr($projectDraft->payload['name'] ?? 'Projet', 0, 2)) }}</span>
                                <div>
                                    <strong>Vous avez commencé cette idée.</strong>
                                </div>
                            </div>
                            <div class="dg-space-rail-row"><div><strong><a href="{{ route('projects.draft.show', [$projectDraft, $projectDraft->current_step]) }}">Continuer →</a></strong></div></div>
                        </section>
                    @endif

                    {{-- UIUX-007 — regroupement visuel léger de « Mes ZUMRA » et « Mes
                         Organisations » sous une même notion (« Mes structures »), jamais une
                         fusion de modèle : ZUMRA ≠ Organisation reste deux cartes distinctes. --}}
                    @if($myGroups->isNotEmpty())
                        <section class="dg-space-rail-card">
                            <div class="dg-space-rail-card__head">
                                <div class="dg-space-rail-card__kicker">Mes structures</div>
                                <h3>Mes ZUMRA</h3>
                            </div>
                            @foreach($myGroups as $group)
                                <div class="dg-space-rail-row">
                                    <span class="dg-space-rail-row__mark">{{ mb_strtoupper(mb_substr($group->name, 0, 2)) }}</span>
                                    <div>
                                        <strong>{{ $group->name }}</strong>
                                        <span>Membre actif</span>
                                    </div>
                                </div>
                            @endforeach
                            <div class="dg-space-rail-row">
                                <div><strong><a href="{{ route('zumra.index') }}">Voir mon espace ZUMRA →</a></strong></div>
                            </div>
                        </section>
                    @elseif($zumraMembership?->status === \App\Models\ZumraProgramMembership::STATUS_ACTIVE)
                        {{-- UIUX-007 — doctrine humaine §6.6 : un membre du Programme ZUMRA actif
                             sans groupe n'a, avant cette mission, aucune porte réelle vers la
                             création depuis Mon espace (le routeur de première intention ne le
                             concerne plus une fois cette adhésion active). Jamais présenté comme
                             devant réunir un collectif au préalable : la personne seule peut déjà
                             créer une ZUMRA réelle et opérationnelle. --}}
                        <section class="dg-space-rail-card">
                            <div class="dg-space-rail-card__head">
                                <div class="dg-space-rail-card__kicker">Mes structures</div>
                                <h3>ZUMRA</h3>
                            </div>
                            <div class="dg-space-rail-row">
                                <span class="dg-space-rail-row__mark">Z</span>
                                <div>
                                    <strong>Vous pouvez créer votre ZUMRA dès maintenant</strong>
                                    <span>Seul·e, sans attendre de réunir un collectif au préalable.</span>
                                </div>
                            </div>
                            <div class="dg-space-rail-row"><div><strong><a href="{{ route('zumra.groups.create') }}">Créer ma ZUMRA →</a></strong></div></div>
                        </section>
                    @elseif(! $zumraMembership)
                        <section class="dg-space-rail-card">
                            <div class="dg-space-rail-card__head">
                                <div class="dg-space-rail-card__kicker">Collectifs</div>
                                <h3>ZUMRA</h3>
                            </div>
                            <div class="dg-space-rail-row">
                                <span class="dg-space-rail-row__mark">Z</span>
                                <div>
                                    <strong>Vous n’êtes membre d’aucune ZUMRA</strong>
                                    <span>Découvrez les collectifs existants ou le Programme ZUMRA.</span>
                                </div>
                            </div>
                            <div class="dg-space-rail-row"><div><strong><a href="{{ route('zumra.index') }}">Découvrir ZUMRA →</a></strong></div></div>
                        </section>
                    @endif

                    @if($myOrganizations->isNotEmpty())
                        {{--
                            UIUX-006 — « Mes Organisations » : accès persistant, jamais réservé au
                            nouveau membre (ce bloc vit hors du routeur de première intention
                            ci-dessus). Une seule Organisation = un accès direct ; plusieurs =
                            liste compacte, chacune ouvrant sa propre fiche. Aucun état vide massif
                            si l'acteur ne représente aucune structure — la carte ne s'affiche
                            simplement pas.
                        --}}
                        <section class="dg-space-rail-card">
                            <div class="dg-space-rail-card__head">
                                <div class="dg-space-rail-card__kicker">Mes structures</div>
                                <h3>Mes Organisations</h3>
                            </div>
                            @foreach($myOrganizations as $organization)
                                <a class="dg-space-rail-row" href="{{ route('organizations.show', $organization) }}" style="text-decoration:none;color:inherit">
                                    <span class="dg-space-rail-row__mark">{{ mb_strtoupper(mb_substr($organization->name, 0, 2)) }}</span>
                                    <div>
                                        <strong>{{ $organization->name }}</strong>
                                        <span>{{ \App\Models\OrganizationMembership::ROLES[$organization->my_role] ?? 'Membre' }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </section>
                    @endif

                    @if(! empty($recommendedPeople))
                        <section class="dg-space-rail-card">
                            <div class="dg-space-rail-card__head">
                                <div class="dg-space-rail-card__kicker">Connexions utiles</div>
                                <h3>Personnes pertinentes</h3>
                            </div>
                            @foreach($recommendedPeople as $recommendation)
                                <div class="dg-space-rail-row">
                                    <span class="dg-space-rail-row__mark dg-space-rail-row__mark--orange">{{ mb_strtoupper(mb_substr($recommendation['profile']->discovery_display_name, 0, 2)) }}</span>
                                    <div>
                                        <strong>{{ $recommendation['profile']->discovery_display_name }}</strong>
                                        <span>{{ $recommendation['reasons'][0] ?? 'Profil rendu visible pour la découverte.' }}</span>
                                    </div>
                                </div>
                            @endforeach
                            <div class="dg-space-rail-row"><div><strong><a href="{{ route('people.index') }}">Explorer les personnes →</a></strong></div></div>
                        </section>
                    @endif

                    @if(! empty($receivedShares))
                        <section class="dg-space-rail-card">
                            <div class="dg-space-rail-card__head">
                                <div class="dg-space-rail-card__kicker">Reçus pour vous</div>
                                <h3>Partages avec contexte</h3>
                            </div>
                            @foreach($receivedShares as $share)
                                <div class="dg-space-rail-row">
                                    <span class="dg-space-rail-row__mark dg-space-rail-row__mark--orange">↗</span>
                                    <div>
                                        <strong><a href="{{ $share['source_url'] }}">Un partage avec contexte</a></strong>
                                        <span>« {{ $share['context_note'] }} » — {{ $share['sharer_label'] }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </section>
                    @endif

                    <section class="dg-space-rail-card dg-space-tools">
                        <div class="dg-space-rail-card__head">
                            <div class="dg-space-rail-card__kicker">Continuer l’action</div>
                            <h3>Mes outils</h3>
                        </div>

                        <div class="dg-space-rail-row">
                            <span class="dg-space-rail-row__mark">M</span>
                            <div>
                                <strong><a href="{{ route('missions.index', ['scope' => 'mine']) }}">Mes Missions</a></strong>
                                <span>À décider, invitations, engagements, à valider et terminées.</span>
                            </div>
                        </div>

                        <div class="dg-space-rail-row">
                            <span class="dg-space-rail-row__mark">O</span>
                            <div>
                                <strong><a href="{{ route('opportunities.index') }}">Opportunités</a></strong>
                                <span>
                                    @if($opportunitiesCount > 0)
                                        {{ $opportunitiesCount }} Mission{{ $opportunitiesCount > 1 ? 's' : '' }} rapprochée{{ $opportunitiesCount > 1 ? 's' : '' }} de vos capacités déclarées.
                                    @else
                                        Des Missions rapprochées de vos capacités déclarées.
                                    @endif
                                </span>
                            </div>
                        </div>

                        {{-- UIUX-008 — audit Phase A : transmissions.index/proofs.index n'avaient
                             aucun point d'entrée persistant nulle part dans le produit (accessibles
                             uniquement par un lien de retour depuis une page de détail). Même
                             emplacement et même patron que Missions/Opportunités ci-dessus. --}}
                        <div class="dg-space-rail-row">
                            <span class="dg-space-rail-row__mark">T</span>
                            <div>
                                <strong><a href="{{ route('transmissions.index') }}">Mes transmissions</a></strong>
                                <span>Propositions, apprentissages et transmissions en cours.</span>
                            </div>
                        </div>

                        <div class="dg-space-rail-row">
                            <span class="dg-space-rail-row__mark">P</span>
                            <div>
                                <strong><a href="{{ route('proofs.index') }}">Mon carnet de preuves</a></strong>
                                <span>Traces d’expérience que vous avez enregistrées.</span>
                            </div>
                        </div>

                        @foreach(($satellites ?? collect()) as $satellite)
                            <form method="POST" action="{{ route('federation.continue', $satellite->slug) }}" class="dg-space-rail-row">
                                @csrf
                                <span class="dg-space-rail-row__mark">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($satellite->display_name, 0, 2)) }}</span>
                                <button type="submit" style="padding:0;border:0;background:transparent;text-align:left;font:inherit;cursor:pointer">
                                    <strong>{{ $satellite->display_name }}</strong>
                                    <span>{{ $satellite->description ?: 'Ouvrir cet outil connecté à DG Afrique.' }}</span>
                                </button>
                            </form>
                        @endforeach
                    </section>
                </aside>
            </div>
        </div>
    </x-dg.shell>
</x-layouts.portal>
