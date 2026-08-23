{{--
    Landing page — la promesse. Refonte « portail » du 19/08/2026 (voir l'addendum daté dans
    docs/design/DESIGN-INVARIANTS.md §1) : DG Afrique s'y présente comme un hub d'entrée qui met en
    avant Fil/Besoins/Projets/ZUMRA et l'activité vivante du réseau, plutôt que la version
    manifeste du 16/08/2026. Les chiffres et cartes « en ce moment » sont des fixtures d'exemple
    (resources/design-reference/landing-portal-demo.json), toujours annoncées par le mot « Exemple ».
--}}
<x-layouts.portal title="DG Afrique — le réseau où les capacités deviennent des actions">
<div class="dg">
    {{-- Topbar desktop --}}
    <header class="hidden lg:flex items-center" style="height:72px;padding:0 32px;background:var(--dg-card);border-bottom:1px solid var(--dg-line);gap:28px">
        <a href="{{ route('landing') }}" class="flex items-center gap-2.5" style="color:inherit">
            <span class="dg-topbar__mark">D</span>
            <strong style="font-size:16px;color:var(--dg-forest)">DG Afrique</strong>
        </a>
        <nav aria-label="Navigation" style="display:flex;gap:6px">
            <a href="{{ route('landing') }}" aria-current="page" class="dg-landing-nav-link">Accueil</a>
            <a href="{{ route('login', ['next' => route('activity.index', absolute: false)]) }}" class="dg-landing-nav-link">Fil</a>
            <a href="{{ route('login', ['next' => route('needs.index', absolute: false)]) }}" class="dg-landing-nav-link">Besoins</a>
            <a href="{{ route('login', ['next' => route('projects.index', absolute: false)]) }}" class="dg-landing-nav-link">Projets</a>
            <a href="{{ route('login', ['next' => route('zumra.index', absolute: false)]) }}" class="dg-landing-nav-link">ZUMRA</a>
            <a href="#outils" class="dg-landing-nav-link">Ressources</a>
            <a href="#a-propos" class="dg-landing-nav-link">À propos</a>
        </nav>
        <div class="ml-auto flex items-center gap-3">
            <button type="button" class="dg-landing-icon-btn" aria-disabled="true" title="La recherche publique sera activée avec l’index de données réelles.">
                <x-dg.icon name="search" />
            </button>
            <a href="{{ route('login') }}" class="dg-landing-icon-btn" style="position:relative" title="Connectez-vous pour voir vos notifications.">
                <x-dg.icon name="bell" />
            </a>
            <x-dg.btn variant="copper" :href="route('login', ['next' => route('member.space', absolute: false)])">AGIR</x-dg.btn>
        </div>
    </header>

    {{-- Topbar mobile --}}
    <header class="lg:hidden flex items-center justify-between" style="height:60px;padding:0 16px;background:var(--dg-card);border-bottom:1px solid var(--dg-line)">
        <a href="{{ route('landing') }}" class="flex items-center gap-2" style="color:inherit">
            <span class="dg-topbar__mark" style="width:28px;height:28px;font-size:15px">D</span>
            <strong style="font-size:14px;color:var(--dg-forest)">DG Afrique</strong>
        </a>
        <div class="flex items-center gap-2">
            <button type="button" class="dg-landing-icon-btn" aria-disabled="true" title="La recherche publique sera activée avec l’index de données réelles.">
                <x-dg.icon name="search" size="18" />
            </button>
            <a href="{{ route('login') }}" class="dg-landing-icon-btn" title="Connectez-vous pour voir vos notifications.">
                <x-dg.icon name="bell" size="18" />
            </a>
            <button type="button" class="dg-landing-icon-btn" data-dg-menu-open aria-haspopup="dialog" aria-controls="dg-landing-menu" aria-label="Ouvrir le menu">
                <x-dg.icon name="menu" size="18" />
            </button>
        </div>
    </header>

    <div id="dg-landing-menu" data-dg-menu-sheet hidden>
        <div class="dg-sheet-backdrop" data-dg-menu-close></div>
        <div class="dg-sheet" role="dialog" aria-modal="true" aria-label="Menu">
            <div class="dg-sheet__grab" aria-hidden="true"></div>
            <a href="{{ route('landing') }}" style="color:var(--dg-forest);font-weight:700">Accueil</a>
            <a href="{{ route('login', ['next' => route('activity.index', absolute: false)]) }}" style="color:var(--dg-ink)">Fil</a>
            <a href="{{ route('login', ['next' => route('needs.index', absolute: false)]) }}" style="color:var(--dg-ink)">Besoins</a>
            <a href="{{ route('login', ['next' => route('projects.index', absolute: false)]) }}" style="color:var(--dg-ink)">Projets</a>
            <a href="{{ route('login', ['next' => route('zumra.index', absolute: false)]) }}" style="color:var(--dg-ink)">ZUMRA</a>
            <a href="#outils" data-dg-menu-close style="color:var(--dg-ink)">Ressources</a>
            <a href="#a-propos" data-dg-menu-close style="color:var(--dg-ink)">À propos</a>
            <a href="{{ route('login') }}" style="color:var(--dg-muted);margin-top:6px">Se connecter</a>
            <a href="{{ route('register') }}" style="color:var(--dg-copper);font-weight:700">Créer mon compte</a>
            <button type="button" data-dg-menu-close style="margin-top:6px;color:var(--dg-muted);font-weight:500">Fermer</button>
        </div>
    </div>

    {{-- Hero --}}
    <section style="background:var(--dg-ivory)">
        <div class="grid gap-10 lg:gap-14 lg:!grid-cols-[1.05fr_.95fr] lg:!items-center lg:!py-20 lg:!px-10 dg-landing-container" style="padding:40px 20px 48px">
            <div class="flex flex-col gap-5 lg:gap-6.5">
                <x-dg.label tone="copper">Développement Global Afrique</x-dg.label>
                <h1 class="dg-display lg:!text-[54px]" style="font-size:38px;line-height:1.12;max-width:16ch">Ensemble, déployons nos capacités pour <span style="color:var(--dg-copper)">changer nos réalités.</span></h1>
                <p style="margin:0;font-size:18px;line-height:1.65;color:var(--dg-text);max-width:52ch">DG Afrique est le réseau d’action qui connecte les talents, les besoins, les projets et les communautés pour un impact social réel et durable.</p>
                <div class="flex flex-wrap items-center gap-3">
                    <x-dg.btn variant="copper" :href="route('register')" style="padding:15px 26px">AGIR MAINTENANT</x-dg.btn>
                    <x-dg.btn variant="quiet" :href="route('login', ['next' => route('activity.index', absolute: false)])" style="padding:15px 26px;border-color:var(--dg-line)">DÉCOUVRIR LE RÉSEAU</x-dg.btn>
                </div>
            </div>
            <div style="display:flex;justify-content:center">
                <x-dg.africa-network style="width:100%;max-width:380px;height:auto" />
            </div>
        </div>
    </section>

    {{-- Quatre portes d'entrée --}}
    <section style="padding:0 20px 40px" class="lg:!px-10 lg:!pb-16 dg-landing-container">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('login', ['next' => route('activity.index', absolute: false)]) }}" class="dg-landing-hub-card">
                <span class="dg-tool__mark" style="background:var(--dg-forest-action)"><x-dg.icon name="feed" size="18" /></span>
                <strong>Fil</strong>
                <span>Suivez l’activité réelle du réseau en temps réel et restez informé de ce qui compte.</span>
                <span class="dg-landing-hub-card__cta">Voir le fil →</span>
            </a>
            <a href="{{ route('login', ['next' => route('needs.index', absolute: false)]) }}" class="dg-landing-hub-card">
                <span class="dg-tool__mark" style="background:var(--dg-copper)"><x-dg.icon name="heart" size="18" /></span>
                <strong>Besoins</strong>
                <span>Identifiez les besoins prioritaires et mobilisez les compétences adéquates.</span>
                <span class="dg-landing-hub-card__cta">Explorer les besoins →</span>
            </a>
            <a href="{{ route('login', ['next' => route('projects.index', absolute: false)]) }}" class="dg-landing-hub-card">
                <span class="dg-tool__mark" style="background:var(--dg-night)"><x-dg.icon name="team" size="18" /></span>
                <strong>Projets</strong>
                <span>Collaborez sur des projets à fort impact avec des communautés engagées.</span>
                <span class="dg-landing-hub-card__cta">Découvrir les projets →</span>
            </a>
            <a href="{{ route('login', ['next' => route('zumra.index', absolute: false)]) }}" class="dg-landing-hub-card">
                <span class="dg-tool__mark" style="background:var(--dg-saffron);color:var(--dg-forest)"><x-dg.icon name="zumra" size="18" /></span>
                <strong>ZUMRA</strong>
                <span>Rejoignez des collectifs engagés et passez à l’action de manière coordonnée.</span>
                <span class="dg-landing-hub-card__cta">Accéder à ZUMRA →</span>
            </a>
        </div>
    </section>

    {{-- De la capacité à l'action --}}
    <section id="reseau" style="padding:0 20px 44px" class="lg:!px-10 lg:!pb-20 dg-landing-container">
        <div style="text-align:center;margin-bottom:8px">
            <h2 class="dg-display dg-display--section" style="font-size:30px">De la capacité à l’action</h2>
            <p style="margin:8px 0 0;font-size:15px;color:var(--dg-muted)">Un parcours simple, collectif et efficace.</p>
        </div>
        <div class="dg-landing-steps">
            <div class="dg-landing-steps__line hidden lg:block" aria-hidden="true"></div>
            @foreach([
                ['1', 'people', 'Rejoindre', 'Créez votre profil et rejoignez le réseau.'],
                ['2', 'search', 'Identifier', 'Explorez les besoins et opportunités.'],
                ['3', 'handshake', 'Collaborer', 'Apportez vos compétences et travaillez ensemble.'],
                ['4', 'rocket', 'Agir', 'Lancez des projets et passez à l’action.'],
                ['5', 'chart', 'Impacter', 'Mesurez l’impact et transformez des vies.'],
            ] as [$n, $icon, $title, $body])
                <div class="dg-landing-step">
                    <span class="dg-landing-step__mark"><x-dg.icon :name="$icon" size="20" /></span>
                    <strong>{{ $n }}. {{ $title }}</strong>
                    <span>{{ $body }}</span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Dans le réseau en ce moment --}}
    <section id="besoins-projets" style="padding:0 20px 44px" class="lg:!px-10 lg:!pb-16 dg-landing-container">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4" style="margin-bottom:20px">
            <h2 class="dg-display" style="font-size:26px">Dans le réseau en ce moment</h2>
            <div class="dg-landing-pills" role="group" aria-label="Filtrer les exemples d’activité">
                <button type="button" data-dg-moment-filter="tout" aria-pressed="true">Tout</button>
                <button type="button" data-dg-moment-filter="besoin" aria-pressed="false">Besoins</button>
                <button type="button" data-dg-moment-filter="projet" aria-pressed="false">Projets</button>
                <button type="button" data-dg-moment-filter="zumra" aria-pressed="false">ZUMRA</button>
            </div>
        </div>

        {{--
            UIUX-001 §5 « Découvrir » — DEMO-FIRST, REAL-DATA-TAKES-OVER (même règle que le Fil V2
            et les portails Projets/Besoins, DESIGN-INVARIANTS.md §17/§18/§21) : dès qu'un Besoin ou
            Projet réellement public existe, il remplace entièrement les cartes d'exemple — jamais
            de compte de participants fabriqué pour un objet réel.
        --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" data-dg-moments>
            @forelse($realMoments as $moment)
                @php([$badgeTone, $badgeLabel] = match($moment['type']) {
                    'besoin' => ['need', 'Besoin'],
                    'projet' => ['project', 'Projet'],
                })
                <div class="dg-card dg-card--tight" data-dg-moment="{{ $moment['type'] }}" style="display:flex;flex-direction:column;gap:12px">
                    <span class="dg-badge dg-badge--{{ $badgeTone }}">{{ $badgeLabel }}</span>
                    <strong style="font-size:16px;color:var(--dg-forest);line-height:1.3">{{ $moment['titre'] }}</strong>
                    @if(!empty($moment['lieu']))
                        <span class="dg-meta">{{ $moment['lieu'] }}</span>
                    @endif
                    <span class="dg-meta">{{ $moment['meta'] }}</span>
                </div>
            @empty
                @foreach($exampleMoments as $moment)
                    @php([$badgeTone, $badgeLabel] = match($moment['type']) {
                        'besoin' => ['need', 'Besoin'],
                        'projet' => ['project', 'Projet'],
                        default => ['neutral', 'ZUMRA'],
                    })
                    <div class="dg-card dg-card--tight" data-dg-moment="{{ $moment['type'] }}" style="display:flex;flex-direction:column;gap:12px">
                        <span class="dg-badge dg-badge--{{ $badgeTone }}">{{ $badgeLabel }} · Exemple</span>
                        <strong style="font-size:16px;color:var(--dg-forest);line-height:1.3">{{ $moment['titre'] }}</strong>
                        @if(!empty($moment['lieu']))
                            <span class="dg-meta">{{ $moment['lieu'] }}</span>
                        @endif
                        <span class="dg-meta">{{ $moment['meta'] }}</span>
                        <div class="flex items-center justify-between" style="margin-top:auto">
                            <div class="dg-people">
                                @for($i = 0; $i < min(3, (int) ceil($moment['participants'] / 8)) + 1; $i++)
                                    <x-dg.avatar size="sm" :tone="['copper','night','saffron'][$i % 3]" initials="?" />
                                @endfor
                                <span class="dg-meta" style="margin-left:8px">+{{ $moment['participants'] }}</span>
                            </div>
                            <span class="dg-meta">{{ $moment['tempsEcoule'] }}</span>
                        </div>
                    </div>
                @endforeach
            @endforelse
        </div>

        <div style="text-align:center;margin-top:24px">
            <a href="{{ route('login', ['next' => route('activity.index', absolute: false)]) }}" style="font-size:14px;font-weight:600;color:var(--dg-copper)">Voir toutes les activités →</a>
        </div>
    </section>

    {{-- Outils / communauté / manifeste --}}
    <section id="outils" style="padding:40px 20px;background:var(--dg-sand)" class="lg:!px-10 lg:!py-16">
        <div class="dg-landing-container grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="dg-tool" style="border-radius:20px;background:var(--dg-night);color:var(--dg-on-deep-text)">
                <x-dg.label tone="saffron">Des outils conçus pour agir ensemble</x-dg.label>
                <p style="margin:0;font-size:13px;line-height:1.6;color:var(--dg-on-deep-muted)">On ne part pas d’outils spécialisés pour connectés et avancer plus loin.</p>
                <div style="display:flex;align-items:center;gap:12px;margin-top:6px">
                    <span class="dg-tool__mark" style="background:var(--dg-saffron);color:var(--dg-forest)">GD</span>
                    <div>
                        <strong style="display:block;font-size:14px;color:var(--dg-on-deep-title)">GamaDrive</strong>
                        <span style="font-size:12px;color:var(--dg-on-deep-muted)">Stocker, partager et collaborer en toute sécurité</span>
                    </div>
                </div>
                <x-dg.btn variant="on-deep" :href="route('login', ['next' => route('member.space', absolute: false)])" style="margin-top:10px;align-self:flex-start">Découvrir GamaDrive →</x-dg.btn>
            </div>

            <div class="dg-card" style="display:flex;flex-direction:column;gap:16px;justify-content:center">
                <x-dg.label>Une communauté engagée · Exemple</x-dg.label>
                <div style="display:flex;flex-direction:column;gap:14px">
                    <div class="flex items-center gap-3">
                        <span class="dg-landing-stat__value">{{ $exampleStats['membresActifs'] }}</span>
                        <span class="dg-meta">Membres actifs</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="dg-landing-stat__value">{{ $exampleStats['projetsLances'] }}</span>
                        <span class="dg-meta">Projets lancés</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="dg-landing-stat__value">{{ $exampleStats['paysConnectes'] }}</span>
                        <span class="dg-meta">Pays connectés</span>
                    </div>
                </div>
            </div>

            <div class="dg-card" id="a-propos" style="background:var(--dg-tint-need);border-color:transparent;display:flex;flex-direction:column;gap:14px;justify-content:center">
                <span style="font-family:var(--dg-font-display);font-size:22px;line-height:1.3;color:var(--dg-forest)">« Pas de likes. Pas de classement. Place à l’action utile. »</span>
                <p style="margin:0;font-size:13px;line-height:1.6;color:var(--dg-text)">Ici, ce qui compte, c’est l’impact réel. Ensemble, transformons nos capacités en changements durables.</p>
                <figure class="dg-photo" style="height:140px;padding:0;border-radius:14px">
                    <img src="{{ asset('images/landing-hero.jpg') }}" alt="Une membre de DG Afrique, souriante." style="width:100%;height:100%;object-fit:cover;object-position:center 20%">
                </figure>
            </div>
        </div>
    </section>

    {{-- Ce que nous ne ferons pas --}}
    <section style="padding:56px 20px 64px;display:flex;flex-direction:column;align-items:center;gap:22px;text-align:center" class="lg:!py-24 dg-landing-container">
        <x-dg.label tone="copper">Ce que nous ne ferons pas</x-dg.label>
        <h2 class="dg-display lg:!text-[46px]" style="font-size:32px;line-height:1.1;max-width:20ch">Pas de course à l’attention. Pas de fausse popularité.</h2>
        <p style="margin:0;font-size:16px;line-height:1.7;color:var(--dg-text);max-width:64ch">Personne n’est noté ici. Le fil s’arrête quand il n’a plus rien d’utile à dire. Ce qui compte, c’est qu’une capacité rencontre un besoin et qu’il en sorte quelque chose de réel.</p>
        <x-dg.btn variant="copper" :href="route('register')" style="padding:15px 28px">Créer mon compte — gratuit</x-dg.btn>
    </section>

    {{-- Footer --}}
    <footer style="padding:44px 20px 32px;background:var(--dg-forest);color:var(--dg-on-deep-muted)" class="lg:!py-14 lg:!px-10">
        <div class="dg-landing-container">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
                <div style="grid-column:1 / -1" class="lg:!col-span-1">
                    <div class="flex items-center gap-2.5" style="margin-bottom:10px">
                        <span class="dg-topbar__mark" style="width:28px;height:28px;font-size:15px">D</span>
                        <strong style="font-size:15px;color:var(--dg-on-deep-title)">DG Afrique</strong>
                    </div>
                    <span style="font-size:13px;line-height:1.6;max-width:30ch;display:block">Le réseau où les capacités deviennent des actions.</span>
                    <div style="display:flex;gap:8px;margin-top:16px">
                        @foreach(['facebook', 'linkedin', 'twitter', 'youtube', 'instagram'] as $network)
                            <span class="dg-landing-social" aria-label="{{ ucfirst($network) }}" title="Réseau bientôt disponible">
                                <x-dg.icon :name="$network" size="15" />
                            </span>
                        @endforeach
                    </div>
                </div>
                <div class="flex flex-col gap-2" style="font-size:13px">
                    <strong style="color:var(--dg-on-deep-title)">Plateforme</strong>
                    <a href="{{ route('login', ['next' => route('activity.index', absolute: false)]) }}" style="color:var(--dg-on-deep-muted)">Fil</a>
                    <a href="{{ route('login', ['next' => route('needs.index', absolute: false)]) }}" style="color:var(--dg-on-deep-muted)">Besoins</a>
                    <a href="{{ route('login', ['next' => route('projects.index', absolute: false)]) }}" style="color:var(--dg-on-deep-muted)">Projets</a>
                    <a href="{{ route('login', ['next' => route('zumra.index', absolute: false)]) }}" style="color:var(--dg-on-deep-muted)">ZUMRA</a>
                </div>
                <div class="flex flex-col gap-2" style="font-size:13px">
                    <strong style="color:var(--dg-on-deep-title)">Ressources</strong>
                    <a href="#outils" style="color:var(--dg-on-deep-muted)">Ressources</a>
                    <span style="color:var(--dg-on-deep-muted);opacity:.55">Guides · bientôt</span>
                    <span style="color:var(--dg-on-deep-muted);opacity:.55">FAQ · bientôt</span>
                    <span style="color:var(--dg-on-deep-muted);opacity:.55">Blog · bientôt</span>
                </div>
                <div class="flex flex-col gap-2" style="font-size:13px">
                    <strong style="color:var(--dg-on-deep-title)">À propos</strong>
                    <a href="#a-propos" style="color:var(--dg-on-deep-muted)">Qui sommes-nous ?</a>
                    <span style="color:var(--dg-on-deep-muted);opacity:.55">Actualités · bientôt</span>
                    <span style="color:var(--dg-on-deep-muted);opacity:.55">Partenaires · bientôt</span>
                    <a href="{{ route('login') }}" style="color:var(--dg-on-deep-muted)">Contact</a>
                </div>
            </div>
            <div style="margin-top:28px;padding-top:16px;border-top:1px solid rgba(217,214,203,.14);font-size:12px;text-align:center">DG Afrique — le réseau où les capacités deviennent des actions.</div>
        </div>
    </footer>

    <div style="height:78px" class="lg:hidden" aria-hidden="true"></div>

    {{-- Barre de navigation mobile : le même composant que Fil/Mon espace/ZUMRA (un visiteur non
         connecté est redirigé vers la connexion par le middleware core.member, avec retour
         automatique sur la page demandée). --}}
    <div class="lg:hidden fixed inset-x-0 bottom-0 z-30">
        <x-dg.tabbar />
    </div>

    {{-- Feuille d'action du bouton « Agir » de la tabbar (même composant partagé que Fil/ZUMRA/
         Mon espace) : un visiteur anonyme est redirigé vers la connexion sur chaque geste. --}}
    <x-dg.agir-sheet />
</div>
</x-layouts.portal>
