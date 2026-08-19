{{--
    Fil ZUMRA / Fil d'action (CAP-019), habillage Fil V2 (voir docs/design/handoffs/
    DG-AFRIQUE-FIL-V2-HANDOFF.md et l'addendum daté du 19 août 2026 dans
    docs/design/DESIGN-INVARIANTS.md §17). Chaque carte répond à « pourquoi cela m'est montré »
    puis « que puis-je faire ». Pas de likes, pas de score, le fil s'arrête et le dit.
--}}
<x-layouts.portal title="Fil — DG Afrique">
    <x-dg.shell current="fil" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-fil-grid">
            <aside class="hidden lg:flex dg-fil-rail">
                <div class="dg-fil-filters">
                    <x-dg.label class="mb-2">Filtrer le fil</x-dg.label>
                    @foreach($filters as $code => $label)
                        <a href="{{ route('activity.index', $code === 'ALL' ? [] : ['type' => $code]) }}"
                           @if($filter === $code) aria-current="page" @endif>{{ $label }}</a>
                    @endforeach
                </div>

                @if($myGroups->isNotEmpty())
                    <div style="display:flex;flex-direction:column;gap:4px;padding-top:16px;border-top:1px solid var(--dg-line)">
                        <x-dg.label class="mb-2">Mes ZUMRA</x-dg.label>
                        @foreach($myGroups as $group)
                            <a href="{{ route('zumra.groups.show', $group) }}" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:10px;color:var(--dg-ink)">
                                <x-dg.avatar :initials="mb_strtoupper(mb_substr($group->name, 0, 2))" size="sm" />
                                <span style="font-size:14px">{{ $group->name }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif

                <div class="dg-fil-contribute">
                    <x-dg.label class="mb-1">Envie de contribuer ?</x-dg.label>
                    <a href="{{ route('needs.index') }}" class="dg-fil-contribute__item">Je peux aider <span aria-hidden="true">→</span></a>
                    <a href="{{ route('needs.create') }}" class="dg-fil-contribute__item">J’ai un besoin <span aria-hidden="true">→</span></a>
                    <a href="{{ route('transmissions.create') }}" class="dg-fil-contribute__item">Je veux apprendre <span aria-hidden="true">→</span></a>
                </div>

                <div class="dg-band">
                    <strong style="display:block;font-size:14px;color:var(--dg-forest);margin-bottom:4px">Le fil s’arrête</strong>
                    Pas de défilement infini, pas de score, pas de classement. Vous ne voyez que ce que vos droits autorisent.
                </div>
            </aside>

            <main style="display:flex;flex-direction:column;gap:18px;min-width:0">
                <h1 class="sr-only">Ce qui bouge dans le réseau</h1>

                <div class="dg-fil-composer" id="dg-fil-composer">
                    <div class="dg-fil-composer__head">
                        <x-dg.avatar :initials="$identity ? mb_strtoupper(mb_substr($identity->label, 0, 1)) : '?'" size="sm" tone="saffron" />
                        <span class="dg-fil-composer__prompt">Quoi de neuf dans le réseau ?</span>
                    </div>

                    <div class="dg-fil-composer__types">
                        <a href="#dg-fil-composer-need" class="dg-fil-composer__type dg-fil-composer__type--need">Besoin</a>
                        <a href="{{ route('projects.create') }}" class="dg-fil-composer__type dg-fil-composer__type--project">Projet</a>
                        <a href="{{ route('zumra.groups.create') }}" class="dg-fil-composer__type dg-fil-composer__type--zumra">ZUMRA</a>
                        <span class="dg-fil-composer__type" aria-disabled="true" title="Une Mission se crée depuis un Projet, une ZUMRA ou un Besoin existant.">Mission</span>
                        <span class="dg-fil-composer__type" aria-disabled="true" title="Aucun objet Événement n’existe encore dans le produit.">Événement</span>
                    </div>

                    <form id="dg-fil-composer-need" method="POST" action="{{ route('needs.store') }}" enctype="multipart/form-data" class="dg-fil-composer__form">
                        @csrf
                        <input type="hidden" name="owner_type" value="PERSON">
                        <input type="hidden" name="collaboration_mode" value="ANY">
                        <input type="hidden" name="visibility" value="PUBLIC">
                        <input type="text" name="title" class="dg-composer__title" placeholder="Quel besoin réel voulez-vous exprimer ?" minlength="5" maxlength="180" required>
                        <textarea name="context" class="dg-composer__context" rows="2" minlength="40" maxlength="3000" required placeholder="Le contexte : pourquoi ce besoin, pour qui, dans quelles conditions… (40 caractères minimum)"></textarea>
                        <div class="dg-composer__foot">
                            <select name="category" class="dg-select dg-composer__category" required>
                                <option value="">Catégorie</option>
                                @foreach($composerCategories as $code => $label)
                                    <option value="{{ $code }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <label class="dg-composer__image">
                                <input type="file" name="image" accept="image/png,image/jpeg,image/webp">
                            </label>
                            <button type="submit" class="dg-btn dg-btn--need dg-composer__submit">Publier ce besoin</button>
                        </div>
                    </form>
                </div>

                <div class="dg-fil-toolbar">
                    <form method="GET" class="flex gap-2 overflow-x-auto lg:hidden" aria-label="Filtrer le fil" style="padding-bottom:2px">
                        @foreach($filters as $code => $label)
                            <a href="{{ route('activity.index', $code === 'ALL' ? [] : ['type' => $code]) }}"
                               style="padding:9px 14px;border-radius:999px;font-size:13px;white-space:nowrap;{{ $filter === $code ? 'background:var(--dg-forest);color:var(--dg-ivory);font-weight:600' : 'background:var(--dg-card);border:1px solid var(--dg-line);color:var(--dg-text)' }}">{{ $label }}</a>
                        @endforeach
                    </form>
                    <div class="dg-fil-toolbar__sort hidden lg:flex">
                        <span>Les plus récents</span>
                        <span class="dg-fil-follow-toggle" title="Le suivi de contextes précis arrivera avec les préférences de notification.">Voir uniquement mes suivis</span>
                    </div>
                </div>

                @if($feed->isEmpty() && empty($demoCards))
                    <x-dg.deep>
                        <div style="display:flex;flex-direction:column;gap:8px">
                            <x-dg.label tone="saffron">Bienvenue dans le réseau</x-dg.label>
                            <h2 class="dg-display" style="font-size:30px;line-height:1.08">Rien ne bouge encore près de vous.</h2>
                            <p style="margin:0;font-size:15px;line-height:1.65;color:var(--dg-on-deep-text)">Le fil ne fabrique rien. Il se remplira dès qu’un besoin, un projet ou une ZUMRA que vous avez le droit de voir produira un mouvement réel.</p>
                        </div>
                    </x-dg.deep>
                    <x-dg.empty title="Trois façons de faire bouger le réseau">
                        <span>Exprimer un besoin · rejoindre une ZUMRA · déclarer ce que vous savez transmettre.</span>
                    </x-dg.empty>
                    <div style="display:flex;gap:10px;flex-wrap:wrap">
                        <x-dg.btn variant="primary" :href="route('needs.create')">Exprimer un besoin</x-dg.btn>
                        <x-dg.btn variant="quiet" :href="route('zumra.groups.index')">Rejoindre une ZUMRA</x-dg.btn>
                    </div>
                    <div class="dg-band">Vous ne voyez que ce que votre identité est autorisée à consulter. Un objet privé reste privé, une ZUMRA suspendue n’apparaît pas.</div>
                @else
                    <div class="dg-feed" style="display:flex;flex-direction:column;gap:18px">
                        @foreach($demoCards as $card)
                            <x-dg.feed.demo :card="$card" />
                        @endforeach

                        @foreach($feed as $item)
                            @if(($item['card'] ?? null) === 'mission')
                                <x-dg.feed.mission :item="$item" />
                            @elseif(($item['card'] ?? null) === 'transmission')
                                <x-dg.feed.transmission :item="$item" />
                            @elseif(($item['card'] ?? null) === 'proof')
                                <x-dg.feed.proof :item="$item" />
                            @elseif($item['kind'] === 'NEEDS' && $item['event'] === 'NEED_RESOLVED')
                                <x-dg.feed.resolved :item="$item" />
                            @elseif($item['kind'] === 'NEEDS')
                                <x-dg.feed.need :item="$item" />
                            @elseif($item['kind'] === 'PROJECTS')
                                <x-dg.feed.project :item="$item" />
                            @else
                                <x-dg.feed.zumra :item="$item" />
                            @endif
                        @endforeach
                    </div>

                    @if($feed->isNotEmpty())
                        <div>{{ $feed->links('pagination.dg') }}</div>

                        @unless($feed->hasMorePages())
                            <p style="text-align:center;padding:20px 0 4px;font-size:14px;color:var(--dg-faint)">Le fil s’arrête ici. Revenez quand une action réelle aura avancé.</p>
                        @endunless
                    @endif
                @endif
            </main>

            <aside class="hidden lg:flex dg-fil-rail">
                <div class="dg-fil-matters">
                    <x-dg.label>Ce qui compte</x-dg.label>
                    <div class="dg-fil-matters__item">
                        <span class="dg-fil-matters__mark"><x-dg.icon name="handshake" size="18" /></span>
                        <div><strong>Agir utilement</strong><span>Chaque action compte, même petite.</span></div>
                    </div>
                    <div class="dg-fil-matters__item">
                        <span class="dg-fil-matters__mark"><x-dg.icon name="people" size="18" /></span>
                        <div><strong>Collaborer simplement</strong><span>Travaillons ensemble, sans classement.</span></div>
                    </div>
                    <div class="dg-fil-matters__item">
                        <span class="dg-fil-matters__mark"><x-dg.icon name="rocket" size="18" /></span>
                        <div><strong>Transmettre</strong><span>Partageons pour multiplier l’impact.</span></div>
                    </div>
                    <div class="dg-fil-matters__item">
                        <span class="dg-fil-matters__mark"><x-dg.icon name="heart" size="18" /></span>
                        <div><strong>Respect &amp; dignité</strong><span>Un réseau humain avant tout.</span></div>
                    </div>
                </div>

                @if($recommendedPeople)
                    <x-dg.card tight>
                        <x-dg.label class="mb-3.5">Personnes à rencontrer</x-dg.label>
                        <div style="display:flex;flex-direction:column;gap:11px;margin-top:14px">
                            @foreach($recommendedPeople as $recommendation)
                                <x-dg.person
                                    :name="$recommendation['profile']->discovery_display_name"
                                    :role="$recommendation['reasons'][0] ?? null"
                                    size="sm"
                                    tone="copper"
                                />
                            @endforeach
                        </div>
                    </x-dg.card>
                @endif

                <div class="dg-band">
                    <strong style="display:block;font-size:14px;color:var(--dg-forest);margin-bottom:4px">Apprentissages et transmissions</strong>
                    Cette source rejoindra le fil lorsqu’un objet métier canonique la produira (CAP-005 / CAP-006). Rien n’est simulé en attendant.
                </div>
            </aside>
        </div>
    </x-dg.shell>
</x-layouts.portal>
