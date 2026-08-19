{{--
    Hub ZUMRA — espace de découverte et d'action des collectifs.
    DG Afrique n'a qu'un seul Fil social (/activite) : cet écran n'en recrée jamais un second.
    Les widgets ne projettent que des faits réels déjà disponibles dans le métier ZUMRA.
--}}
<x-layouts.portal title="ZUMRA — DG Afrique">
    <x-dg.shell current="zumra" :identity="$identity" :is-administrator="$isAdministrator">
        @php
            $membershipActive = $membership && $membership->status === \App\Models\ZumraProgramMembership::STATUS_ACTIVE;
            $membershipLabel = match($membership?->status) {
                \App\Models\ZumraProgramMembership::STATUS_ACTIVE => 'Adhésion active',
                \App\Models\ZumraProgramMembership::STATUS_PENDING_PAYMENT => 'Adhésion à finaliser',
                \App\Models\ZumraProgramMembership::STATUS_SUSPENDED => 'Adhésion suspendue',
                \App\Models\ZumraProgramMembership::STATUS_CLOSED => 'Adhésion clôturée',
                default => 'Découvrir l’adhésion',
            };
            $pendingDecisionCount = (int) $pendingRequestsToDecide->sum('count');
            $firstDecision = $pendingRequestsToDecide->first();
        @endphp

        <div class="dg-page zumra-hub zumra-hub--v4">
            <section class="zumra-hub__hero" aria-labelledby="zumra-title">
                <div class="zumra-hub__hero-copy">
                    <x-dg.label tone="saffron">Programme ZUMRA</x-dg.label>
                    <h1 id="zumra-title" class="dg-display zumra-hub__title">Vos collectifs d’action</h1>
                    <p class="zumra-hub__lead">
                        ZUMRA réunit des équipes engagées autour d’un domaine, d’un objectif commun et de responsabilités explicites.
                        Découvrez, rejoignez ou proposez un collectif pour apprendre, construire et agir ensemble.
                    </p>
                </div>

                <div class="zumra-hub__hero-panel" aria-label="Actions principales ZUMRA">
                    <div class="zumra-hub__hero-actions">
                        <a href="{{ route('zumra.groups.index') }}" class="zumra-hub__btn zumra-hub__btn--primary">
                            <span aria-hidden="true">⌕</span>
                            Découvrir une ZUMRA
                        </a>

                        @if($membershipActive)
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
                    </div>

                    <a class="zumra-hub__hero-feed" href="{{ route('activity.index', ['type' => 'ZUMRA']) }}">
                        Voir les activités ZUMRA <span aria-hidden="true">→</span>
                        <small>Vue filtrée du Fil global</small>
                    </a>
                </div>
            </section>

            <div class="zumra-hub__grid">
                <aside class="zumra-hub__rail zumra-hub__rail--left" aria-label="Repères ZUMRA">
                    <section class="zumra-hub__widget zumra-hub__widget--attention" aria-labelledby="zumra-now-title">
                        <div class="zumra-hub__widget-heading">
                            <span class="zumra-hub__widget-icon zumra-hub__widget-icon--saffron" aria-hidden="true">↯</span>
                            <div>
                                <span class="zumra-hub__micro-label">Votre priorité</span>
                                <h2 id="zumra-now-title">À faire maintenant</h2>
                            </div>
                        </div>

                        @if($attentionItems->isNotEmpty())
                            <div class="zumra-hub__task-list">
                                @foreach($attentionItems as $item)
                                    <a href="{{ $item['action_href'] }}" class="zumra-hub__task">
                                        <span class="zumra-hub__task-type">{{ $item['eyebrow'] }}</span>
                                        <strong>{{ $item['heading'] }}</strong>
                                        <span>{{ $item['action_label'] }} →</span>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="zumra-hub__all-clear">
                                <span class="zumra-hub__all-clear-mark" aria-hidden="true">✓</span>
                                <div>
                                    <strong>Tout est à jour</strong>
                                    <p>Aucune action ZUMRA ne demande votre attention pour le moment.</p>
                                </div>
                            </div>
                        @endif
                    </section>

                    <section class="zumra-hub__widget zumra-hub__widget--understand" aria-labelledby="zumra-understand-title">
                        <div class="zumra-hub__widget-heading">
                            <span class="zumra-hub__widget-icon" aria-hidden="true">◎</span>
                            <div>
                                <span class="zumra-hub__micro-label">Repère</span>
                                <h2 id="zumra-understand-title">Comprendre ZUMRA</h2>
                            </div>
                        </div>

                        <p>Une ZUMRA rassemble des personnes autour d’un engagement concret, jamais autour d’un score.</p>
                        <div class="zumra-hub__principles" aria-label="Fondations d'une ZUMRA">
                            <span>Domaine</span>
                            <span>Objectif</span>
                            <span>Charte</span>
                            <span>5 responsabilités</span>
                        </div>
                        <a href="{{ route('zumra.membership.show') }}" class="zumra-hub__text-link">Voir le Programme ZUMRA →</a>
                    </section>
                </aside>

                <main class="zumra-hub__main">
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
                                    <div class="zumra-hub__group-top">
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
                                    </div>

                                    <p class="zumra-hub__objective">{{ \Illuminate\Support\Str::limit($group->founding_objective, 210) }}</p>

                                    <div class="zumra-hub__group-bottom">
                                        <div class="zumra-hub__facts">
                                            <div class="zumra-hub__fact">
                                                <span class="zumra-hub__fact-icon" aria-hidden="true">◎</span>
                                                <span>
                                                    <strong>{{ (int) $group->active_member_count }}</strong>
                                                    <small>{{ (int) $group->active_member_count > 1 ? 'membres actifs' : 'membre actif' }}</small>
                                                </span>
                                            </div>

                                            <div class="zumra-hub__fact">
                                                <span class="zumra-hub__fact-icon" aria-hidden="true">◇</span>
                                                <span>
                                                    <strong>{{ $row['role_label'] ? 'Votre rôle' : 'Participation' }}</strong>
                                                    <small>{{ $row['role_label'] ?: $statusLabel }}</small>
                                                </span>
                                            </div>
                                        </div>

                                        <a href="{{ route('zumra.groups.show', $group) }}" class="zumra-hub__btn zumra-hub__btn--primary zumra-hub__group-open">
                                            {{ $row['status'] === \App\Models\ZumraGroupMembership::STATUS_INVITED ? 'Voir l’invitation' : 'Ouvrir la ZUMRA' }}
                                            <span aria-hidden="true">→</span>
                                        </a>
                                    </div>
                                </article>
                            @empty
                                <div class="zumra-hub__empty">
                                    <span class="zumra-hub__empty-mark" aria-hidden="true">Z</span>
                                    <div>
                                        <h3>Votre première ZUMRA vous attend</h3>
                                        <p>Découvrez les collectifs existants. Si votre adhésion est active, vous pouvez aussi proposer une ZUMRA autour d’un objectif réel.</p>
                                    </div>
                                    <a href="{{ route('zumra.groups.index') }}" class="zumra-hub__btn zumra-hub__btn--primary">Découvrir les ZUMRA</a>
                                </div>
                            @endforelse
                        </div>
                    </section>

                    <section class="zumra-hub__section zumra-hub__discover" aria-labelledby="discover-domain-title">
                        <div class="zumra-hub__section-heading">
                            <div>
                                <span class="zumra-hub__section-icon zumra-hub__section-icon--green" aria-hidden="true">⌕</span>
                                <h2 id="discover-domain-title">Découvrir par domaine</h2>
                            </div>
                            <a href="{{ route('zumra.groups.index') }}">Voir tous les collectifs →</a>
                        </div>

                        @if($discoverDomains->isNotEmpty())
                            <div class="zumra-hub__domain-grid {{ $discoverDomains->count() === 1 ? 'is-single' : '' }}">
                                @foreach($discoverDomains as $domain)
                                    <a href="{{ route('zumra.groups.index') }}" class="zumra-hub__domain-card">
                                        <span class="zumra-hub__domain-mark" aria-hidden="true">{{ mb_strtoupper(mb_substr($domain['domain'], 0, 1)) }}</span>
                                        <div>
                                            <span class="zumra-hub__micro-label">Domaine</span>
                                            <h3>{{ $domain['domain'] }}</h3>
                                            <p>{{ $domain['count'] }} {{ $domain['count'] > 1 ? 'collectifs disponibles' : 'collectif disponible' }}</p>
                                        </div>
                                        <strong>Explorer →</strong>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="zumra-hub__discover-empty">
                                Les domaines apparaîtront ici à mesure que des ZUMRA seront réellement proposées.
                            </div>
                        @endif
                    </section>
                </main>

                <aside class="zumra-hub__rail zumra-hub__rail--right" aria-label="Votre Programme ZUMRA">
                    <section class="zumra-hub__widget zumra-hub__widget--program" aria-labelledby="program-zumra-title">
                        <div class="zumra-hub__widget-heading">
                            <span class="zumra-hub__widget-icon zumra-hub__widget-icon--green" aria-hidden="true">Z</span>
                            <div>
                                <span class="zumra-hub__micro-label">Votre programme</span>
                                <h2 id="program-zumra-title">Programme ZUMRA</h2>
                            </div>
                        </div>

                        <div class="zumra-hub__program-row">
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

                        <div class="zumra-hub__program-row {{ $membershipActive ? '' : 'is-muted' }}">
                            <span class="zumra-hub__program-icon" aria-hidden="true">▣</span>
                            <div>
                                <strong>Carte ZUMRA {{ $membershipActive ? 'disponible' : 'non disponible' }}</strong>
                                <p>Elle atteste votre appartenance lorsque l’adhésion est active.</p>
                                @if($membershipActive)
                                    <a href="{{ route('zumra.card.show') }}">Voir ma carte →</a>
                                @endif
                            </div>
                        </div>
                    </section>

                    <section class="zumra-hub__widget zumra-hub__widget--decisions" aria-labelledby="zumra-decisions-title">
                        <div class="zumra-hub__widget-heading">
                            <span class="zumra-hub__widget-icon" aria-hidden="true">◇</span>
                            <div>
                                <span class="zumra-hub__micro-label">Responsabilité</span>
                                <h2 id="zumra-decisions-title">Décisions</h2>
                            </div>
                        </div>

                        @if($pendingDecisionCount > 0 && $firstDecision)
                            <div class="zumra-hub__decision-count">{{ $pendingDecisionCount }}</div>
                            <strong class="zumra-hub__decision-title">{{ $pendingDecisionCount > 1 ? 'demandes à examiner' : 'demande à examiner' }}</strong>
                            <p>Ces demandes concernent uniquement les ZUMRA où vous détenez une responsabilité acceptée.</p>
                            <a href="{{ route('zumra.groups.show', $firstDecision['group']) }}#demandes" class="zumra-hub__text-link">Voir les décisions →</a>
                        @else
                            <div class="zumra-hub__decision-clear">
                                <span aria-hidden="true">✓</span>
                                <div>
                                    <strong>Aucune décision en attente</strong>
                                    <p>Vous n’avez aucune demande d’adhésion à examiner ici.</p>
                                </div>
                            </div>
                        @endif
                    </section>
                </aside>
            </div>
        </div>
    </x-dg.shell>
</x-layouts.portal>