{{-- DG Afrique Landing V2 — réseau des capacités en action. Les cartes réseau ci-dessous restent explicitement des exemples illustratifs. --}}
<x-layouts.portal title="DG Afrique — le réseau des capacités en action">
<div class="dg-landing-v2">
    <header class="dg-land-header">
        <div class="dg-land-container dg-land-header__inner">
            <a href="{{ route('landing') }}" class="dg-land-brand" aria-label="DG Afrique — Accueil">
                <span class="dg-land-mark" aria-hidden="true">DG</span>
                <strong>DG Afrique</strong>
            </a>

            <nav class="dg-land-nav" aria-label="Navigation principale">
                <a href="{{ route('landing') }}">Accueil</a>
                <a href="{{ route('login', ['next' => '/activite']) }}">Fil</a>
                <a href="{{ route('login', ['next' => '/besoins']) }}">Besoins</a>
                <a href="{{ route('login', ['next' => '/projets']) }}">Projets</a>
                <a href="{{ route('login', ['next' => '/zumra']) }}">ZUMRA</a>
                <a href="#outils">Ressources</a>
                <a href="#manifeste">À propos</a>
            </nav>

            <div class="dg-land-header__actions">
                <a class="dg-land-icon-btn" href="{{ route('login', ['next' => '/personnes']) }}" aria-label="Rechercher dans le réseau">
                    <svg viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="6.5" stroke-width="1.8"/><path d="m16 16 4 4" stroke-width="1.8" stroke-linecap="round"/></svg>
                </a>
                <a class="dg-land-icon-btn" href="{{ route('login', ['next' => '/notifications']) }}" aria-label="Notifications">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M6.5 9.5a5.5 5.5 0 0 1 11 0v4.1l1.4 2.4H5.1l1.4-2.4V9.5Z" stroke-width="1.7" stroke-linejoin="round"/><path d="M10 19h4" stroke-width="1.7" stroke-linecap="round"/></svg>
                </a>
                <a class="dg-land-login" href="{{ route('login') }}">Connexion</a>
                <a class="dg-land-btn dg-land-btn--orange" href="{{ route('register') }}">+&nbsp; AGIR</a>
                <a class="dg-land-mobile-menu" href="#plateforme" aria-label="Découvrir la plateforme">☰</a>
            </div>
        </div>
    </header>

    <main>
        <section class="dg-land-hero">
            <div class="dg-land-container dg-land-hero__grid">
                <div>
                    <div class="dg-land-kicker">Le réseau des capacités en action</div>
                    <h1>Ensemble,<br>déployons nos capacités<br>pour <em>changer nos réalités.</em></h1>
                    <p class="dg-land-hero__lead">DG Afrique connecte les capacités, les besoins, les projets et les collectifs pour transformer une intention en action utile, visible et durable.</p>
                    <div class="dg-land-hero__actions">
                        <a class="dg-land-btn dg-land-btn--orange" href="{{ route('register') }}">AGIR MAINTENANT <span aria-hidden="true">→</span></a>
                        <a class="dg-land-btn dg-land-btn--outline" href="#plateforme">DÉCOUVRIR LE RÉSEAU <span aria-hidden="true">●</span></a>
                    </div>
                </div>

                <div class="dg-land-africa" aria-label="Illustration d’un réseau de capacités à travers l’Afrique">
                    <span class="dg-land-africa__halo" aria-hidden="true"></span>
                    <svg viewBox="0 0 440 430" role="img" aria-hidden="true">
                        <path class="map-fill" d="M83 62 141 34 212 40 271 63 332 98 358 137 342 168 305 183 294 213 316 257 297 300 266 322 250 370 221 407 198 372 189 332 164 299 151 257 127 226 106 202 75 174 56 137 64 96Z"/>
                        <path class="map-fill" d="M350 270 368 286 359 322 345 304Z"/>
                        <g class="map-line">
                            <path d="M89 104 143 80 190 104 242 84 302 118 333 151" fill="none"/>
                            <path d="M89 104 122 161 171 144 216 169 270 146 333 151" fill="none"/>
                            <path d="M122 161 142 214 198 201 242 232 292 210" fill="none"/>
                            <path d="M142 214 170 270 221 252 267 284 289 329" fill="none"/>
                            <path d="M170 270 201 323 225 370" fill="none"/>
                            <path d="M143 80 171 144 198 201 221 252 201 323" fill="none"/>
                            <path d="M190 104 216 169 242 232 267 284 225 370" fill="none"/>
                            <path d="M242 84 270 146 292 210 267 284" fill="none"/>
                            <path d="M302 118 270 146 216 169 171 144 122 161" fill="none"/>
                        </g>
                        <g>
                            <circle class="map-node orange" cx="89" cy="104" r="7"/><circle class="map-node" cx="143" cy="80" r="7"/>
                            <circle class="map-node orange" cx="190" cy="104" r="7"/><circle class="map-node" cx="242" cy="84" r="7"/>
                            <circle class="map-node orange" cx="302" cy="118" r="7"/><circle class="map-node" cx="333" cy="151" r="6"/>
                            <circle class="map-node" cx="122" cy="161" r="7"/><circle class="map-node orange" cx="171" cy="144" r="7"/>
                            <circle class="map-node" cx="216" cy="169" r="7"/><circle class="map-node orange" cx="270" cy="146" r="7"/>
                            <circle class="map-node orange" cx="142" cy="214" r="7"/><circle class="map-node" cx="198" cy="201" r="7"/>
                            <circle class="map-node orange" cx="242" cy="232" r="7"/><circle class="map-node" cx="292" cy="210" r="7"/>
                            <circle class="map-node" cx="170" cy="270" r="7"/><circle class="map-node orange" cx="221" cy="252" r="7"/>
                            <circle class="map-node" cx="267" cy="284" r="7"/><circle class="map-node orange" cx="201" cy="323" r="7"/>
                            <circle class="map-node" cx="289" cy="329" r="7"/><circle class="map-node orange" cx="225" cy="370" r="7"/>
                        </g>
                    </svg>
                </div>
            </div>

            <div id="plateforme" class="dg-land-container dg-land-pillars">
                <a class="dg-land-pillar" href="{{ route('login', ['next' => '/activite']) }}">
                    <span class="dg-land-pillar__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M6 6h12M6 11h12M6 16h8" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                    <strong>Fil</strong>
                    <p>Suivez l’activité utile du réseau et retrouvez ce qui mérite votre attention.</p>
                    <span>Voir le Fil →</span>
                </a>
                <a class="dg-land-pillar" href="{{ route('login', ['next' => '/besoins']) }}">
                    <span class="dg-land-pillar__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M12 20s-7-3.8-7-9.1A3.9 3.9 0 0 1 12 8a3.9 3.9 0 0 1 7 2.9C19 16.2 12 20 12 20Z" stroke-width="1.7"/><path d="M12 5v6M9 8h6" stroke-width="1.7" stroke-linecap="round"/></svg></span>
                    <strong>Besoins</strong>
                    <p>Exprimez clairement ce qui manque et mobilisez les capacités adéquates.</p>
                    <span>Explorer les besoins →</span>
                </a>
                <a class="dg-land-pillar" href="{{ route('login', ['next' => '/projets']) }}">
                    <span class="dg-land-pillar__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><circle cx="8" cy="9" r="3" stroke-width="1.7"/><circle cx="16.5" cy="10" r="2.5" stroke-width="1.7"/><path d="M3.5 19c.5-3.1 2.2-4.8 4.8-4.8 2.7 0 4.5 1.7 5 4.8M13 18.8c.4-2.2 1.6-3.5 3.6-3.5 2 0 3.3 1.3 3.7 3.5" stroke-width="1.7" stroke-linecap="round"/></svg></span>
                    <strong>Projets</strong>
                    <p>Structurez une intention, rassemblez une équipe et faites progresser une action réelle.</p>
                    <span>Découvrir les projets →</span>
                </a>
                <a class="dg-land-pillar" href="{{ route('login', ['next' => '/zumra']) }}">
                    <span class="dg-land-pillar__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8" stroke-width="1.7"/><path d="M8.5 8.5h7l-7 7h7" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <strong>ZUMRA</strong>
                    <p>Rejoignez des collectifs d’action organisés autour d’un objectif et de responsabilités explicites.</p>
                    <span>Accéder à ZUMRA →</span>
                </a>
            </div>
        </section>

        <section class="dg-land-band">
            <div class="dg-land-container dg-land-band__grid">
                <div>
                    <div class="dg-land-section-head">
                        <div class="dg-land-kicker">De la capacité à l’action</div>
                        <h2>Un parcours simple, humain et utile.</h2>
                        <p>On ne vous demande pas d’être populaire. On vous aide à devenir utile là où quelque chose doit avancer.</p>
                    </div>
                    <div class="dg-land-steps">
                        @foreach([
                            ['1. Rejoindre', 'Déclarez ce que vous savez faire.', 'person'],
                            ['2. Identifier', 'Repérez un besoin ou une opportunité.', 'search'],
                            ['3. Collaborer', 'Rencontrez les bonnes personnes.', 'link'],
                            ['4. Agir', 'Transformez l’accord en action.', 'bolt'],
                            ['5. Transmettre', 'Laissez une capacité plus forte derrière vous.', 'rise'],
                        ] as [$title, $body, $icon])
                            <div class="dg-land-step">
                                <span class="dg-land-step__icon" aria-hidden="true">
                                    @if($icon === 'person')<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.2" stroke-width="1.7"/><path d="M5.5 19c.7-3.8 2.9-5.7 6.5-5.7s5.8 1.9 6.5 5.7" stroke-width="1.7" stroke-linecap="round"/></svg>@endif
                                    @if($icon === 'search')<svg viewBox="0 0 24 24" fill="none"><circle cx="10.5" cy="10.5" r="5.5" stroke-width="1.8"/><path d="m15 15 4.5 4.5" stroke-width="1.8" stroke-linecap="round"/></svg>@endif
                                    @if($icon === 'link')<svg viewBox="0 0 24 24" fill="none"><path d="m9 15 6-6M8.5 7H7a4 4 0 0 0 0 8h2M15.5 17H17a4 4 0 1 0 0-8h-2" stroke-width="1.7" stroke-linecap="round"/></svg>@endif
                                    @if($icon === 'bolt')<svg viewBox="0 0 24 24" fill="none"><path d="m13.5 3-7 10h5l-1 8 7-11h-5l1-7Z" stroke-width="1.7" stroke-linejoin="round"/></svg>@endif
                                    @if($icon === 'rise')<svg viewBox="0 0 24 24" fill="none"><path d="M5 18V12M10 18V8M15 18v-5M20 18V5M4 18h17" stroke-width="1.7" stroke-linecap="round"/></svg>@endif
                                </span>
                                <strong>{{ $title }}</strong>
                                <span>{{ $body }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="dg-land-live">
                    <div class="dg-land-live__head">
                        <div>
                            <div class="dg-land-kicker">Dans le réseau</div>
                            <h2>Voyez ce qui peut se mettre en mouvement.</h2>
                        </div>
                        <div class="dg-land-filters" aria-hidden="true">
                            <span class="dg-land-filter is-active">Tout</span><span class="dg-land-filter">Besoins</span><span class="dg-land-filter">Projets</span><span class="dg-land-filter">ZUMRA</span>
                        </div>
                    </div>

                    <div class="dg-land-live__cards">
                        <article class="dg-land-live-card">
                            <span class="dg-land-live-card__type">Besoin · Exemple</span>
                            <h3>{{ $exampleNeed['titre'] }}</h3>
                            <p>{{ $exampleNeed['lieu'] }} · collaboration {{ $exampleNeed['collaboration'] }}@if(!empty($exampleNeed['capaciteRecherchee'])) · {{ $exampleNeed['capaciteRecherchee'] }}@endif</p>
                            <div class="dg-land-live-card__footer">Exemple illustratif — connectez-vous pour voir les besoins réels.</div>
                        </article>
                        <article class="dg-land-live-card">
                            <span class="dg-land-live-card__type">Projet · Exemple</span>
                            <h3>{{ $exampleProject['titre'] }}</h3>
                            <p>Projet illustratif · {{ count($exampleProject['competencesRecherchees']) }} compétences présentées dans la maquette d’exemple.</p>
                            <div class="dg-land-live-card__footer">Exemple illustratif — connectez-vous pour voir les projets réels.</div>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section id="outils" class="dg-land-lower">
            <div class="dg-land-container dg-land-lower__grid">
                <div class="dg-land-tools">
                    <div class="dg-land-kicker" style="color:#ffad7e">Des outils conçus pour agir ensemble</div>
                    <h2>Le réseau reste simple. Les outils apparaissent quand l’action les appelle.</h2>
                    <p>DG Afrique oriente vers des outils spécialisés sans transformer le réseau en tableau de bord technique.</p>
                    <div class="dg-land-tool">
                        <span class="dg-land-tool__mark">GD</span>
                        <div><strong>GamaDrive</strong><span>Documents, partage et collaboration autour d’une action réelle.</span></div>
                    </div>
                </div>

                <div class="dg-land-principles">
                    <h3>Ce que le réseau protège</h3>
                    <div class="dg-land-principle"><span class="dg-land-principle__icon">✓</span><div><strong>L’humain décide</strong><span>Aucune recommandation ne choisit à votre place.</span></div></div>
                    <div class="dg-land-principle"><span class="dg-land-principle__icon">↔</span><div><strong>Les capacités se relient</strong><span>La mise en relation explique pourquoi, sans noter les personnes.</span></div></div>
                    <div class="dg-land-principle"><span class="dg-land-principle__icon">◎</span><div><strong>L’action reste traçable</strong><span>Les engagements réels comptent plus que l’attention.</span></div></div>
                </div>

                <div id="manifeste" class="dg-land-manifesto">
                    <div class="dg-land-kicker">Notre parti pris</div>
                    <h2>Pas de likes. Pas de classement. Place à l’action utile.</h2>
                    <p>DG Afrique ne transforme pas la dignité humaine en score. Ce qui compte, c’est qu’une capacité rencontre un besoin, qu’un accord devienne une action et qu’un collectif puisse avancer.</p>
                    <div class="dg-land-manifesto__line"></div>
                    <div class="dg-land-manifesto__photo" aria-hidden="true"><img src="{{ asset('images/landing-hero.jpg') }}" alt=""></div>
                </div>
            </div>
        </section>

        <section class="dg-land-container dg-land-cta">
            <div>
                <h2>Vous avez une capacité.<br>Quelqu’un en a peut-être besoin.</h2>
                <p>Entrez dans le réseau, rendez vos capacités visibles et choisissez ce que vous voulez mettre en mouvement.</p>
            </div>
            <a class="dg-land-btn dg-land-btn--orange" href="{{ route('register') }}">ENTRER DANS DG AFRIQUE →</a>
        </section>
    </main>

    <footer class="dg-land-footer">
        <div class="dg-land-container dg-land-footer__inner">
            <div class="dg-land-footer__brand">
                <a href="{{ route('landing') }}" class="dg-land-brand"><span class="dg-land-mark">DG</span><strong>DG Afrique</strong></a>
                <p class="dg-land-footer__tagline">Le réseau des capacités en action. Révéler, relier et mettre en mouvement ce qui existe déjà.</p>
            </div>
            <div><h4>Plateforme</h4><a href="{{ route('login', ['next' => '/activite']) }}">Fil</a><a href="{{ route('login', ['next' => '/besoins']) }}">Besoins</a><a href="{{ route('login', ['next' => '/projets']) }}">Projets</a><a href="{{ route('login', ['next' => '/zumra']) }}">ZUMRA</a></div>
            <div><h4>Ressources</h4><a href="#plateforme">Découvrir le réseau</a><a href="#outils">Outils spécialisés</a><a href="#manifeste">Notre approche</a></div>
            <div><h4>Commencer</h4><a href="{{ route('register') }}">Créer mon compte</a><a href="{{ route('login') }}">Se connecter</a><a href="{{ route('login', ['next' => '/personnes']) }}">Découvrir des personnes</a></div>
        </div>
    </footer>
</div>
</x-layouts.portal>
