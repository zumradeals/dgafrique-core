{{--
    Hub ZUMRA — espace de découverte et d'action des collectifs.
    DG Afrique n'a qu'un seul Fil social (/activite) : cet écran n'en recrée jamais un second.
    Les activités ZUMRA sont une vue filtrée du Fil global.
--}}
<x-layouts.portal title="ZUMRA — DG Afrique">
    <x-dg.shell current="zumra" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page zumra-hub">
            <section class="zumra-hub__hero" aria-labelledby="zumra-title">
                <div class="zumra-hub__hero-copy">
                    <x-dg.label tone="saffron">Programme ZUMRA</x-dg.label>
                    <h1 id="zumra-title" class="dg-display zumra-hub__title">Vos collectifs d’action</h1>
                    <p class="zumra-hub__lead">
                        ZUMRA réunit des équipes engagées autour d’un domaine et d’un objectif commun.
                        Découvrez, rejoignez ou proposez un collectif pour apprendre, construire et agir ensemble.
                    </p>
                </div>

                <div class="zumra-hub__hero-actions" aria-label="Actions principales ZUMRA">
                    <a href="{{ route('zumra.groups.index') }}" class="zumra-hub__btn zumra-hub__btn--primary">
                        <span aria-hidden="true">⌕</span>
                        Découvrir une ZUMRA
                    </a>

                    @if($membership && $membership->status === \App\Models\ZumraProgramMembership::STATUS_ACTIVE)
                        <a href="{{ route('zumra.groups.create') }}" class="zumra-hub__btn zumra-hub__btn--secondary">
                            <span aria-hidden="true">＋</span>
                            Proposer une ZUMRA
                        </a>
                    @else
                        <span class="zumra-hub__btn zumra-hub__btn--secondary zumra-hub__btn--disabled" title="Réservé aux membres dont l’adhésion au Programme ZUMRA est active.">
                            <span aria-hidden="true">＋</span>
                            Proposer une ZUMRA
                        </span>
                    @endif

                    <div class="zumra-hub__feed-link">
                        <a href="{{ route('activity.index', ['type' => 'ZUMRA']) }}">Voir les activités ZUMRA →</a>
                        <small>Vue filtrée du Fil global</small>
                    </div>
                </div>
            </section>

            @if($attentionItems->isNotEmpty())
                <section class="zumra-hub__section zumra-hub__attention" aria-labelledby="zumra-attention-title">
                    <div class="zumra-hub__section-heading">
                        <div>
                            <span class="zumra-hub__section-icon" aria-hidden="true">↯</span>
                            <h2 id="zumra-attention-title">À faire maintenant</h2>
                        </div>
                    </div>

                    <div class="zumra-hub__attention-list">
                        @foreach($attentionItems as $item)
                            <article class="zumra-hub__attention-card">
                                <div class="zumra-hub__attention-mark" aria-hidden="true">{{ $item['kind'] === 'invitation' ? '✦' : '!' }}</div>
                                <div class="zumra-hub__attention-copy">
                                    <span class="zumra-hub__micro-label">{{ $item['eyebrow'] }}</span>
                                    <h3>{{ $item['heading'] }}</h3>
                                    <p>{{ $item['body'] }}</p>
                                </div>
                                <div class="zumra-hub__attention-actions">
                                    <a href="{{ $item['action_href'] }}" class="zumra-hub__btn zumra-hub__btn--saffron">{{ $item['action_label'] }}</a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            <div class="zumra-hub__workspace">
                <section class="zumra-hub__section" aria-labelledby="my-zumra-title">
                    <div class="zumra-hub__section-heading">
                        <div>
                            <span class="zumra-hub__section-icon" aria-hidden="true">≡</span>
                            <h2 id="my-zumra-title">Mes ZUMRA</h2>
                        </div>
                        <a href="{{ route('zumra.groups.index') }}">Voir toutes les ZUMRA →</a>
                    </div>

                    <div class="zumra-hub__groups">
                        @forelse($myGroups as $row)
                            @php
                                $group = $row['group'];
                                $statusLabel = match($row['status']) {
                                    \App\Models\ZumraGroupMembership::STATUS_ACTIVE => 'Membre actif',
                                    \App\Models\ZumraGroupMembership::STATUS_INVITED => 'Invitation reçue',
                                    \App\Models\ZumraGroupMembership::STATUS_REQUESTED => 'Demande en attente',
                                    default => $row['status'],
                                };
                                $statusTone = match($row['status']) {
                                    \App\Models\ZumraGroupMembership::STATUS_ACTIVE => 'active',
                                    \App\Models\ZumraGroupMembership::STATUS_INVITED => 'invited',
                                    default => 'pending',
                                };
                                $initials = collect(preg_split('/\s+/u', trim($group->name)))
                                    ->filter()
                                    ->take(2)
                                    ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
                                    ->implode('');
                            @endphp

                            <article class="zumra-hub__group-card">
                                <div class="zumra-hub__group-main">
                                    <div class="zumra-hub__group-identity">
                                        <span class="zumra-hub__avatar">{{ $initials ?: 'Z' }}</span>
                                        <div>
                                            <div class="zumra-hub__group-title-line">
                                                <h3>{{ $group->name }}</h3>
                                                <span class="zumra-hub__status zumra-hub__status--{{ $statusTone }}">{{ $statusLabel }}</span>
                                            </div>
                                            @if($group->domain)
                                                <span class="zumra-hub__domain">{{ $group->domain }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <p class="zumra-hub__objective">{{ \Illuminate\Support\Str::limit($group->founding_objective, 190) }}</p>

                                    <div class="zumra-hub__facts">
                                        <div class="zumra-hub__fact">
                                            <span class="zumra-hub__fact-icon" aria-hidden="true">◎</span>
                                            <span>
                                                <strong>{{ (int) $group->active_member_count }}</strong>
                                                <small>membres actifs</small>
                                            </span>
                                        </div>

                                        @if($row['role_label'])
                                            <div class="zumra-hub__fact">
                                                <span class="zumra-hub__fact-icon" aria-hidden="true">◇</span>
                                                <span>
                                                    <strong>Mon rôle</strong>
                                                    <small>{{ $row['role_label'] }}</small>
                                                </span>
                                            </div>
                                        @else
                                            <div class="zumra-hub__fact">
                                                <span class="zumra-hub__fact-icon" aria-hidden="true">◇</span>
                                                <span>
                                                    <strong>Participation</strong>
                                                    <small>{{ $statusLabel }}</small>
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="zumra-hub__group-side">
                                    <div class="zumra-hub__group-context">
                                        <span>Votre collectif</span>
                                        @if($row['status'] === \App\Models\ZumraGroupMembership::STATUS_ACTIVE)
                                            <strong>Prêt à agir avec votre équipe</strong>
                                        @elseif($row['status'] === \App\Models\ZumraGroupMembership::STATUS_INVITED)
                                            <strong>Une invitation attend votre réponse</strong>
                                        @else
                                            <strong>Votre demande est en cours d’examen</strong>
                                        @endif
                                    </div>
                                    <a href="{{ route('zumra.groups.show', $group) }}" class="zumra-hub__btn zumra-hub__btn--primary">
                                        @if($row['status'] === \App\Models\ZumraGroupMembership::STATUS_INVITED)
                                            Voir l’invitation
                                        @else
                                            Ouvrir la ZUMRA
                                        @endif
                                    </a>
                                </div>
                            </article>
                        @empty
                            <div class="zumra-hub__empty">
                                <span class="zumra-hub__empty-mark" aria-hidden="true">Z</span>
                                <div>
                                    <h3>Votre première ZUMRA vous attend</h3>
                                    <p>Découvrez les collectifs existants. Si votre adhésion est active, vous pouvez aussi proposer une nouvelle ZUMRA autour d’un objectif réel.</p>
                                </div>
                                <a href="{{ route('zumra.groups.index') }}" class="zumra-hub__btn zumra-hub__btn--primary">Découvrir les ZUMRA</a>
                            </div>
                        @endforelse
                    </div>
                </section>

                <aside class="zumra-hub__program" aria-labelledby="program-zumra-title">
                    <span id="program-zumra-title" class="zumra-hub__micro-label">Votre programme ZUMRA</span>

                    @php
                        $membershipActive = $membership && $membership->status === \App\Models\ZumraProgramMembership::STATUS_ACTIVE;
                        $membershipLabel = match($membership?->status) {
                            \App\Models\ZumraProgramMembership::STATUS_ACTIVE => 'Adhésion active',
                            \App\Models\ZumraProgramMembership::STATUS_PENDING_PAYMENT => 'Adhésion à finaliser',
                            \App\Models\ZumraProgramMembership::STATUS_SUSPENDED => 'Adhésion suspendue',
                            \App\Models\ZumraProgramMembership::STATUS_CLOSED => 'Adhésion clôturée',
                            default => 'Découvrir l’adhésion',
                        };
                    @endphp

                    <div class="zumra-hub__program-item">
                        <span class="zumra-hub__program-icon {{ $membershipActive ? 'is-active' : '' }}" aria-hidden="true">{{ $membershipActive ? '✓' : '○' }}</span>
                        <div>
                            <strong>{{ $membershipLabel }}</strong>
                            <p>
                                @if($membershipActive)
                                    Votre appartenance au Programme ZUMRA est active.
                                @elseif($membership?->status === \App\Models\ZumraProgramMembership::STATUS_PENDING_PAYMENT)
                                    Votre dossier est prêt ; la contribution reste à finaliser.
                                @else
                                    Le compte DG Afrique reste distinct de l’adhésion au Programme ZUMRA.
                                @endif
                            </p>
                            <a href="{{ route('zumra.membership.show') }}">Voir mon adhésion →</a>
                        </div>
                    </div>

                    <div class="zumra-hub__program-item {{ $membershipActive ? '' : 'is-muted' }}">
                        <span class="zumra-hub__program-icon" aria-hidden="true">▣</span>
                        <div>
                            <strong>Carte ZUMRA {{ $membershipActive ? 'disponible' : 'non disponible' }}</strong>
                            <p>Elle atteste votre appartenance au Programme lorsque l’adhésion est active.</p>
                            @if($membershipActive)
                                <a href="{{ route('zumra.card.show') }}">Voir ma carte →</a>
                            @endif
                        </div>
                    </div>
                </aside>
            </div>

            <section class="zumra-hub__section zumra-hub__discover" aria-labelledby="discover-domain-title">
                <div class="zumra-hub__section-heading">
                    <div>
                        <h2 id="discover-domain-title">Découvrir par domaine</h2>
                    </div>
                    <a href="{{ route('zumra.groups.index') }}">Voir tous les collectifs →</a>
                </div>

                @if($discoverDomains->isNotEmpty())
                    <div class="zumra-hub__domain-grid">
                        @foreach($discoverDomains as $domain)
                            <article class="zumra-hub__domain-card">
                                <span class="zumra-hub__domain-mark" aria-hidden="true">{{ mb_strtoupper(mb_substr($domain['domain'], 0, 1)) }}</span>
                                <h3>{{ $domain['domain'] }}</h3>
                                <p>{{ $domain['count'] }} {{ $domain['count'] > 1 ? 'collectifs disponibles' : 'collectif disponible' }}</p>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="zumra-hub__discover-empty">
                        Les domaines apparaîtront ici à mesure que des ZUMRA seront réellement proposées.
                    </div>
                @endif
            </section>

            <footer class="zumra-hub__context-note">
                <span class="zumra-hub__context-icon" aria-hidden="true">◎</span>
                <div>
                    <strong>ZUMRA fait partie de DG Afrique.</strong>
                    <p>Les collectifs s’articulent avec le Fil, les besoins, les projets, les capacités et les outils spécialisés — sans créer un second réseau parallèle.</p>
                </div>
                <a href="{{ route('activity.index', ['type' => 'ZUMRA']) }}">Voir l’action dans le Fil →</a>
            </footer>
        </div>
    </x-dg.shell>
</x-layouts.portal>
