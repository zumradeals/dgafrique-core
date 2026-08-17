{{--
    Fil ZUMRA / Fil d'action (CAP-019). Reproduction fidèle du handoff Claude
    (docs/design/reference/claude-2026-08-16/) : chaque carte répond à « pourquoi cela m'est
    montré » puis « que puis-je faire ». Pas de likes, pas de score, le fil s'arrête et le dit.
--}}
<x-layouts.portal title="Fil — DG Afrique">
    <x-dg.shell current="fil" :identity="$identity" :is-administrator="$isAdministrator">
        <div style="display:grid;gap:32px;max-width:1600px;margin:0 auto;padding:28px 20px 40px" class="lg:grid-cols-[248px_minmax(0,1fr)_300px] lg:!px-10 lg:!py-10">
            <aside class="hidden lg:flex" style="flex-direction:column;gap:20px">
                <div style="display:flex;flex-direction:column;gap:4px">
                    <x-dg.label class="mb-2">Filtrer le fil</x-dg.label>
                    @foreach($filters as $code => $label)
                        <a href="{{ route('activity.index', $code === 'ALL' ? [] : ['type' => $code]) }}"
                           style="padding:11px 14px;border-radius:10px;font-size:14px;{{ $filter === $code ? 'background:var(--dg-forest);color:var(--dg-ivory);font-weight:600' : 'color:var(--dg-text)' }}">{{ $label }}</a>
                    @endforeach
                </div>

                @if($myGroups->isNotEmpty())
                    <div style="display:flex;flex-direction:column;gap:4px;padding-top:18px;border-top:1px solid var(--dg-line)">
                        <x-dg.label class="mb-2">Mes ZUMRA</x-dg.label>
                        @foreach($myGroups as $group)
                            <a href="{{ route('zumra.groups.show', $group) }}" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:10px;color:var(--dg-ink)">
                                <x-dg.avatar :initials="mb_strtoupper(mb_substr($group->name, 0, 2))" size="sm" />
                                <span style="font-size:14px">{{ $group->name }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif

                <div class="dg-band">
                    <strong style="display:block;font-size:14px;color:var(--dg-forest);margin-bottom:4px">Le fil s’arrête</strong>
                    Pas de défilement infini, pas de score, pas de classement. Vous ne voyez que ce que vos droits autorisent.
                </div>
            </aside>

            <main style="display:flex;flex-direction:column;gap:18px;min-width:0">
                <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:20px">
                    <h1 class="dg-display dg-display--screen" style="max-width:18ch">Ce qui bouge dans le réseau</h1>
                </div>

                <form method="GET" class="flex gap-2 overflow-x-auto lg:hidden" aria-label="Filtrer le fil" style="padding-bottom:2px">
                    @foreach($filters as $code => $label)
                        <a href="{{ route('activity.index', $code === 'ALL' ? [] : ['type' => $code]) }}"
                           style="padding:9px 14px;border-radius:999px;font-size:13px;white-space:nowrap;{{ $filter === $code ? 'background:var(--dg-forest);color:var(--dg-ivory);font-weight:600' : 'background:var(--dg-card);border:1px solid var(--dg-line);color:var(--dg-text)' }}">{{ $label }}</a>
                    @endforeach
                </form>

                @if($feed->isEmpty())
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
                    @foreach($feed as $item)
                        @if(($item['card'] ?? null) === 'mission')
                            <x-dg.feed.mission :item="$item" />
                        @elseif(($item['card'] ?? null) === 'transmission')
                            <x-dg.feed.transmission :item="$item" />
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

                    <div>{{ $feed->links('pagination.dg') }}</div>

                    @unless($feed->hasMorePages())
                        <p style="text-align:center;padding:20px 0 4px;font-size:14px;color:var(--dg-faint)">Le fil s’arrête ici. Revenez quand une action réelle aura avancé.</p>
                    @endunless
                @endif
            </main>

            <aside class="hidden lg:flex" style="flex-direction:column;gap:16px">
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
