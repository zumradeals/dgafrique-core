@props([
    'active' => null,
    'actions' => [],
])

@php
    $centres = [
        ['key' => 'fil', 'label' => 'Fil', 'route' => 'activity.index', 'icon' => 'activity'],
        ['key' => 'people', 'label' => 'Personnes', 'route' => 'people.index', 'icon' => 'people'],
        ['key' => 'needs', 'label' => 'Besoins', 'route' => 'needs.index', 'icon' => 'need'],
        ['key' => 'projects', 'label' => 'Projets', 'route' => 'projects.index', 'icon' => 'project'],
        ['key' => 'zumra', 'label' => 'ZUMRA', 'route' => 'zumra.index', 'icon' => 'zumra'],
        ['key' => 'space', 'label' => 'Mon espace', 'route' => 'member.space', 'icon' => 'space'],
    ];
    $discoverActive = in_array($active, ['people', 'needs', 'projects'], true);
    $actionCount = count($actions);
@endphp

<div x-data="dgNavigation" @keydown.escape.window="if (panel) close()" @keydown.tab="if (panel) trapFocus($event)">
    <nav class="dg-desktop-nav" aria-label="Navigation principale">
        <div class="dg-desktop-nav__inner">
            <a class="dg-brand-text" href="{{ route('member.space') }}" aria-label="DG Afrique — Mon espace">
                <span>DG Afrique</span>
            </a>

            <ul class="dg-desktop-nav__links" role="list">
                @foreach ($centres as $centre)
                    <li>
                        <a
                            class="dg-desktop-nav__link"
                            href="{{ route($centre['route']) }}"
                            @if ($active === $centre['key']) aria-current="page" @endif
                            data-desktop-centre="{{ $centre['key'] }}"
                        >
                            <x-dg.icon :name="$centre['icon']" size="19" />
                            <span>{{ $centre['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <button
                type="button"
                class="dg-button dg-button--solar"
                @click="open('actions', $event)"
                aria-haspopup="dialog"
                :aria-expanded="panel === 'actions'"
                @disabled($actionCount === 0)
            >
                <x-dg.icon name="act" size="20" />
                <span>Agir</span>
            </button>
        </div>
    </nav>

    <nav class="dg-mobile-nav" aria-label="Navigation principale mobile">
        <ul class="dg-mobile-nav__grid" role="list" data-mobile-navigation>
            <li class="dg-mobile-nav__item" data-mobile-primary="fil">
                <a
                    class="dg-mobile-nav__control"
                    href="{{ route('activity.index') }}"
                    @if ($active === 'fil') aria-current="page" @endif
                >
                    <x-dg.icon name="activity" />
                    <span>Fil</span>
                </a>
            </li>

            <li class="dg-mobile-nav__item" data-mobile-primary="discover">
                <button
                    type="button"
                    class="dg-mobile-nav__control"
                    @click="open('discover', $event)"
                    aria-haspopup="dialog"
                    :aria-expanded="panel === 'discover'"
                    @if ($discoverActive) aria-current="page" @endif
                >
                    <x-dg.icon name="discover" />
                    <span>Découvrir</span>
                </button>
            </li>

            <li class="dg-mobile-nav__item" data-mobile-primary="act">
                <button
                    type="button"
                    class="dg-mobile-nav__control dg-mobile-nav__control--act"
                    @click="open('actions', $event)"
                    aria-haspopup="dialog"
                    :aria-expanded="panel === 'actions'"
                    aria-label="Agir — ouvrir les actions disponibles"
                    @disabled($actionCount === 0)
                >
                    <x-dg.icon name="act" />
                    <span>Agir</span>
                </button>
            </li>

            <li class="dg-mobile-nav__item" data-mobile-primary="zumra">
                <a
                    class="dg-mobile-nav__control"
                    href="{{ route('zumra.index') }}"
                    @if ($active === 'zumra') aria-current="page" @endif
                >
                    <x-dg.icon name="zumra" />
                    <span>ZUMRA</span>
                </a>
            </li>

            <li class="dg-mobile-nav__item" data-mobile-primary="space">
                <a
                    class="dg-mobile-nav__control"
                    href="{{ route('member.space') }}"
                    aria-label="Mon espace"
                    @if ($active === 'space') aria-current="page" @endif
                >
                    <x-dg.icon name="space" />
                    <span>Espace</span>
                </a>
            </li>
        </ul>
    </nav>

    <template x-teleport="body">
        <div x-cloak x-show="panel === 'discover'">
            <div
                class="dg-panel-backdrop"
                aria-hidden="true"
                @click="close()"
                x-transition:enter="dg-panel-enter"
                x-transition:enter-start="dg-panel-enter-start"
                x-transition:leave="dg-panel-leave"
                x-transition:leave-end="dg-panel-leave-end"
            ></div>
            <section
                class="dg-panel"
                role="dialog"
                aria-modal="true"
                aria-labelledby="dg-discover-title"
                data-navigation-panel="discover"
                x-transition:enter="dg-sheet-enter"
                x-transition:enter-start="dg-sheet-enter-start"
                x-transition:leave="dg-sheet-leave"
                x-transition:leave-end="dg-sheet-leave-end"
            >
                <div class="dg-panel__handle" aria-hidden="true"></div>
                <div class="dg-panel__header">
                    <div>
                        <h2 class="dg-panel__title" id="dg-discover-title">Que cherchez-vous ?</h2>
                        <p class="dg-panel__description">Trouvez une personne, un besoin concret ou un projet à découvrir.</p>
                    </div>
                    <button class="dg-icon-button" type="button" aria-label="Fermer" @click="close()">
                        <x-dg.icon name="close" />
                    </button>
                </div>
                <ul class="dg-panel__list" role="list" data-discover-list>
                    @foreach (array_slice($centres, 1, 3) as $centre)
                        <li>
                            <a class="dg-action-link" href="{{ route($centre['route']) }}" @if ($loop->first) data-autofocus @endif>
                                <span class="dg-action-link__icon"><x-dg.icon :name="$centre['icon']" /></span>
                                <span>
                                    <span class="dg-action-link__label">{{ $centre['label'] }}</span>
                                    <span class="dg-action-link__description">
                                        @if ($centre['key'] === 'people') Trouver des savoir-faire et des disponibilités réelles.
                                        @elseif ($centre['key'] === 'needs') Voir ce qui manque pour faire avancer une action.
                                        @else Découvrir les initiatives et leur prochaine étape.
                                        @endif
                                    </span>
                                </span>
                                <x-dg.icon name="arrow" size="20" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    </template>

    <template x-teleport="body">
        <div x-cloak x-show="panel === 'actions'">
            <div
                class="dg-panel-backdrop"
                aria-hidden="true"
                @click="close()"
                x-transition:enter="dg-panel-enter"
                x-transition:enter-start="dg-panel-enter-start"
                x-transition:leave="dg-panel-leave"
                x-transition:leave-end="dg-panel-leave-end"
            ></div>
            <section
                class="dg-panel"
                role="dialog"
                aria-modal="true"
                aria-labelledby="dg-actions-title"
                data-navigation-panel="actions"
                x-transition:enter="dg-sheet-enter"
                x-transition:enter-start="dg-sheet-enter-start"
                x-transition:leave="dg-sheet-leave"
                x-transition:leave-end="dg-sheet-leave-end"
            >
                <div class="dg-panel__handle" aria-hidden="true"></div>
                <div class="dg-panel__header">
                    <div>
                        <h2 class="dg-panel__title" id="dg-actions-title">Que voulez-vous faire ?</h2>
                        <p class="dg-panel__description">Seules les actions réellement disponibles dans votre situation sont proposées.</p>
                    </div>
                    <button class="dg-icon-button" type="button" aria-label="Fermer" @click="close()">
                        <x-dg.icon name="close" />
                    </button>
                </div>
                <ul class="dg-panel__list" role="list" data-actions-list>
                    @foreach ($actions as $action)
                        <li>
                            <a class="dg-action-link" href="{{ $action['href'] }}" @if ($loop->first) data-autofocus @endif>
                                <span class="dg-action-link__icon"><x-dg.icon :name="$action['icon'] ?? 'act'" /></span>
                                <span>
                                    <span class="dg-action-link__label">{{ $action['label'] }}</span>
                                    <span class="dg-action-link__description">{{ $action['description'] }}</span>
                                </span>
                                <x-dg.icon name="arrow" size="20" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    </template>
</div>
