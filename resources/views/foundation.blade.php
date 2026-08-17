{{--
    Landing page — la promesse. Reproduction fidèle du handoff Claude
    (docs/design/reference/claude-2026-08-16/) : DG Afrique comme réseau d'action, plus comme
    portail/lanceur d'applications. Les deux cartes « en ce moment » sont des fixtures
    d'exemple (resources/design-reference/), toujours annoncées par le mot « Exemple ».
--}}
<x-layouts.portal title="DG Afrique — le réseau où les capacités deviennent des actions">
<div class="dg">
    <header class="dg-topbar" style="border-radius:0">
        <a href="{{ route('landing') }}" class="flex items-center gap-2.5" style="color:inherit">
            <span class="dg-topbar__mark">D</span>
            <strong style="font-size:16px">DG Afrique</strong>
        </a>
        <nav aria-label="Navigation" class="hidden md:flex" style="margin-left:8px">
            <a href="#reseau">Le réseau</a>
            <a href="#zumra">ZUMRA</a>
            <a href="#besoins-projets">Besoins et projets</a>
            <a href="#outils">Outils</a>
        </nav>
        <div class="ml-auto flex items-center gap-3.5">
            <a href="{{ route('login') }}" style="color:#EFE6D6;font-size:14px;font-weight:600">Se connecter</a>
            <x-dg.btn variant="saffron" :href="route('register')">Créer mon compte</x-dg.btn>
        </div>
    </header>

    {{-- Hero --}}
    <section style="position:relative;background:var(--dg-forest);color:var(--dg-on-deep-text);overflow:hidden">
        <div style="position:absolute;inset:0;background:var(--dg-motif-deep)"></div>
        <div class="grid gap-10 lg:gap-14 lg:!grid-cols-[1.05fr_.95fr] lg:!items-center lg:!py-24 lg:!px-10" style="position:relative;padding:44px 20px 56px">
            <div class="flex flex-col gap-5 lg:gap-6.5">
                <x-dg.label tone="saffron">Développement Global Afrique</x-dg.label>
                <h1 class="dg-display dg-display--hero" style="color:var(--dg-on-deep-title);max-width:14ch">Vos capacités deviennent des actions.</h1>
                <p style="margin:0;font-size:19px;line-height:1.65;color:var(--dg-on-deep-text);max-width:50ch">Déclarez ce que vous savez faire. Rencontrez les personnes qu’il vous faut. Répondez à un besoin réel, faites avancer un projet, faites vivre une ZUMRA. Ici, rien ne se mesure en likes — tout se mesure en actions engagées.</p>
                <div class="flex flex-wrap items-center gap-3">
                    <x-dg.btn variant="saffron" :href="route('register')" style="padding:15px 26px">Créer mon compte — gratuit</x-dg.btn>
                    <x-dg.btn variant="on-deep" :href="route('login', ['next' => route('activity.index', absolute: false)])" style="padding:15px 26px">Voir ce qui se passe en ce moment</x-dg.btn>
                </div>
                <span style="font-size:13px;color:var(--dg-on-deep-muted)">Le compte DG Afrique est gratuit et distinct de l’adhésion au Programme ZUMRA.</span>
            </div>
            <figure class="dg-photo lg:!h-[520px]" style="height:300px">
                <figcaption class="dg-photo__caption">photo — un atelier réel, mains au travail, regard direct, format portrait</figcaption>
            </figure>
        </div>
    </section>

    {{-- Comment une capacité devient une action --}}
    <section id="reseau" style="padding:56px 20px 20px" class="lg:!py-20 lg:!px-10">
        <div class="flex flex-col lg:flex-row lg:items-baseline lg:justify-between gap-4" style="margin-bottom:32px">
            <h2 class="dg-display dg-display--section" style="max-width:20ch">Comment une capacité devient une action</h2>
            <span style="font-size:15px;color:var(--dg-muted);max-width:40ch" class="lg:text-right">Un seul mouvement, six moments. Chacun existe déjà comme objet réel du produit.</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6" style="border-top:1px solid var(--dg-line-section);border-bottom:1px solid var(--dg-line-section)">
            @foreach([
                ['01', 'Je sais faire', 'Un profil de capacités, pas une fiche biographique.'],
                ['02', 'Je rencontre', 'Des personnes pertinentes, avec la raison de la mise en relation.'],
                ['03', 'J’apprends, je transmets', 'Ce que je veux apprendre vaut autant que ce que je maîtrise.'],
                ['04', 'Un besoin apparaît', 'Ce qui manque vraiment, dit clairement, sans promesse d’aide.'],
                ['05', 'Un projet se construit', 'Le besoin dit ce qui manque, le projet organise ce qui se construit.'],
                ['06', 'Une équipe agit', 'Une ZUMRA prend la suite : gouvernance, charte, responsabilités réelles.'],
            ] as [$n, $title, $body])
                <div style="padding:24px 20px;border-bottom:1px solid var(--dg-line-inner)" class="lg:!border-b-0 lg:[&:not(:last-child)]:!border-r lg:!border-r-[var(--dg-line-inner)]">
                    <span class="dg-label" style="color:var(--dg-copper)">{{ $n }}</span>
                    <strong style="display:block;margin-top:10px;font-size:17px;color:var(--dg-forest)">{{ $title }}</strong>
                    <span style="display:block;margin-top:8px;font-size:14px;line-height:1.6;color:var(--dg-muted)">{{ $body }}</span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Exemples --}}
    <section id="besoins-projets" style="padding:40px 20px" class="lg:!py-16 lg:!px-10">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <x-dg.card>
                <div style="display:flex;flex-direction:column;gap:14px">
                    <x-dg.label tone="copper">Un besoin en ce moment · Exemple</x-dg.label>
                    <h3 class="dg-display" style="font-size:30px;line-height:1.15">{{ $exampleNeed['titre'] }}</h3>
                    <p style="margin:0;font-size:15px;line-height:1.6;color:var(--dg-text)">{{ $exampleNeed['lieu'] }} · collaboration {{ $exampleNeed['collaboration'] }}@if(!empty($exampleNeed['capaciteRecherchee'])) · capacité recherchée : {{ $exampleNeed['capaciteRecherchee'] }}@endif</p>
                    <div class="flex gap-2.5">
                        <x-dg.btn variant="need" disabled title="Ceci est un exemple illustratif — créez votre compte pour voir les besoins réels.">Je peux aider</x-dg.btn>
                        <x-dg.btn variant="learn" disabled title="Ceci est un exemple illustratif — créez votre compte pour voir les besoins réels.">Je veux apprendre</x-dg.btn>
                    </div>
                </div>
            </x-dg.card>
            <x-dg.card>
                <div style="display:flex;flex-direction:column;gap:14px">
                    <x-dg.label style="color:var(--dg-night)">Un projet qui avance · Exemple</x-dg.label>
                    <h3 class="dg-display" style="font-size:30px;line-height:1.15">{{ $exampleProject['titre'] }} — passage à l’expérimentation</h3>
                    <p style="margin:0;font-size:15px;line-height:1.6;color:var(--dg-text)">Porté par une ZUMRA · {{ count($exampleProject['competencesRecherchees']) }} compétences recherchées</p>
                    <div class="flex gap-2.5">
                        <x-dg.btn variant="project" disabled title="Ceci est un exemple illustratif — créez votre compte pour voir les projets réels.">Voir le projet</x-dg.btn>
                        <x-dg.btn variant="quiet" disabled title="Ceci est un exemple illustratif — créez votre compte pour voir les projets réels.">Participer</x-dg.btn>
                    </div>
                </div>
            </x-dg.card>
        </div>
    </section>

    {{-- ZUMRA --}}
    <section id="zumra" style="position:relative;background:var(--dg-forest);color:var(--dg-on-deep-text);overflow:hidden">
        <div style="position:absolute;inset:0;background:var(--dg-motif-deep)"></div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16 lg:!py-24 lg:!px-10" style="position:relative;padding:56px 20px">
            <div class="flex flex-col gap-5">
                <x-dg.label tone="saffron">Le moteur humain</x-dg.label>
                <h2 class="dg-display dg-display--section" style="color:var(--dg-on-deep-title);max-width:16ch">Une ZUMRA, c’est une équipe qui s’engage vraiment.</h2>
                <p style="margin:0;font-size:17px;line-height:1.7;color:var(--dg-on-deep-text);max-width:52ch">Un domaine, un objectif fondateur, une charte, cinq responsabilités nommées et acceptées explicitement. Aucune adhésion automatique, aucun rôle attribué en silence, aucun siège rempli par un profil fictif.</p>
                <span style="font-size:14px;color:var(--dg-on-deep-muted);max-width:52ch">Le compte DG Afrique reste gratuit. L’adhésion au Programme ZUMRA est un engagement distinct, avec sa contribution propre.</span>
            </div>
            <div class="flex flex-col gap-3">
                @foreach([
                    ['01', 'Premier responsable', 'Rôle accepté explicitement, jamais attribué'],
                    ['02', 'Deux adjoints distincts', 'Deux personnes différentes, toujours'],
                    ['03', 'Responsable financier', 'La contribution n’achète aucun pouvoir'],
                    ['04', 'Relations et affaires sociales', 'Un siège vacant reste visible comme vacant'],
                ] as [$n, $title, $body])
                    <div style="display:flex;align-items:center;gap:16px;padding:18px 20px;border-radius:16px;background:rgba(239,230,214,.07);border:1px solid rgba(239,230,214,.12)">
                        <span class="dg-label" style="color:var(--dg-saffron);width:24px">{{ $n }}</span>
                        <div>
                            <strong style="display:block;font-size:15px;color:var(--dg-on-deep-title)">{{ $title }}</strong>
                            <span style="font-size:13px;color:var(--dg-on-deep-muted)">{{ $body }}</span>
                        </div>
                    </div>
                @endforeach
                <div class="flex items-center gap-4" style="margin-top:8px">
                    <x-dg.btn variant="saffron" :href="route('login', ['next' => route('zumra.groups.index', absolute: false)])" style="padding:13px 22px">Découvrir les ZUMRA</x-dg.btn>
                    <a href="{{ route('login', ['next' => route('zumra.membership.show', absolute: false)]) }}" style="color:var(--dg-on-deep-title);font-size:14px;font-weight:600">Comprendre l’adhésion →</a>
                </div>
            </div>
        </div>
    </section>

    {{-- Outils --}}
    <section id="outils" style="padding:56px 20px;background:var(--dg-sand)" class="lg:!py-16 lg:!px-10">
        <div class="flex flex-col lg:flex-row lg:items-baseline lg:justify-between gap-3" style="margin-bottom:24px">
            <h2 class="dg-display lg:!text-[38px]" style="font-size:32px">Des outils, quand l’action en a besoin</h2>
            <span style="font-size:14px;color:var(--dg-muted);max-width:44ch" class="lg:text-right">Les outils spécialisés ne sont pas le produit. Ils s’ouvrent depuis un projet, au moment où il en a réellement besoin.</span>
        </div>
        <div class="flex flex-col sm:flex-row gap-4">
            <div style="flex:1;min-width:260px;background:var(--dg-card);border:1px solid var(--dg-line);border-radius:18px;padding:22px;display:flex;align-items:center;gap:16px">
                <span class="dg-tool__mark" style="width:44px;height:44px;border-radius:13px;font-size:14px">GD</span>
                <div>
                    <strong style="display:block;font-size:15px;color:var(--dg-forest)">GamaDrive</strong>
                    <span class="dg-meta">Les documents d’un projet, à leur place</span>
                </div>
            </div>
            <div style="flex:1;min-width:260px;border:1px dashed var(--dg-line-dashed);border-radius:18px;padding:22px;display:flex;align-items:center;gap:16px;color:var(--dg-faint)">
                <span style="width:44px;height:44px;border-radius:13px;border:1px dashed var(--dg-line-dashed)"></span>
                <div>
                    <strong style="display:block;font-size:15px">Futurs outils</strong>
                    <span style="font-size:13px">Ajoutés lorsqu’un besoin réel les appelle</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Ce que nous ne ferons pas --}}
    <section style="padding:56px 20px 64px;display:flex;flex-direction:column;align-items:center;gap:22px;text-align:center" class="lg:!py-24">
        <x-dg.label tone="copper">Ce que nous ne ferons pas</x-dg.label>
        <h2 class="dg-display lg:!text-[52px]" style="font-size:34px;line-height:1.1;max-width:20ch">Pas de likes. Pas de classement. Pas de course à l’attention.</h2>
        <p style="margin:0;font-size:16px;line-height:1.7;color:var(--dg-text);max-width:64ch">Personne n’est noté ici. Le fil s’arrête quand il n’a plus rien d’utile à dire. Ce qui compte, c’est qu’une capacité rencontre un besoin et qu’il en sorte quelque chose de réel.</p>
        <x-dg.btn variant="primary" :href="route('register')" style="padding:15px 28px">Créer mon compte — gratuit</x-dg.btn>
    </section>

    {{-- Footer --}}
    <footer style="padding:44px 20px 32px;background:var(--dg-forest);color:var(--dg-on-deep-muted)" class="lg:!py-14 lg:!px-10">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
            <div style="grid-column:1 / -1" class="lg:!col-span-1">
                <div class="flex items-center gap-2.5" style="margin-bottom:10px">
                    <span class="dg-topbar__mark" style="width:28px;height:28px;font-size:15px">D</span>
                    <strong style="font-size:15px;color:var(--dg-on-deep-title)">DG Afrique</strong>
                </div>
                <span style="font-size:13px;line-height:1.6;max-width:30ch;display:block">Le réseau où les capacités deviennent des actions.</span>
            </div>
            <div class="flex flex-col gap-2" style="font-size:13px">
                <strong style="color:var(--dg-on-deep-title)">Le réseau</strong>
                <a href="{{ route('login', ['next' => route('people.index', absolute: false)]) }}" style="color:var(--dg-on-deep-muted)">Personnes</a>
                <a href="{{ route('login', ['next' => route('needs.index', absolute: false)]) }}" style="color:var(--dg-on-deep-muted)">Besoins</a>
                <a href="{{ route('login', ['next' => route('projects.index', absolute: false)]) }}" style="color:var(--dg-on-deep-muted)">Projets</a>
            </div>
            <div class="flex flex-col gap-2" style="font-size:13px">
                <strong style="color:var(--dg-on-deep-title)">ZUMRA</strong>
                <a href="{{ route('login', ['next' => route('zumra.index', absolute: false)]) }}" style="color:var(--dg-on-deep-muted)">Le programme</a>
                <a href="{{ route('login', ['next' => route('zumra.groups.index', absolute: false)]) }}" style="color:var(--dg-on-deep-muted)">Les ZUMRA</a>
            </div>
            <div class="flex flex-col gap-2" style="font-size:13px">
                <strong style="color:var(--dg-on-deep-title)">Compte</strong>
                <a href="{{ route('register') }}" style="color:var(--dg-on-deep-muted)">Créer mon compte</a>
                <a href="{{ route('login') }}" style="color:var(--dg-on-deep-muted)">Se connecter</a>
            </div>
        </div>
        <div style="max-width:1600px;margin:28px auto 0;padding-top:16px;border-top:1px solid rgba(239,230,214,.14);font-size:12px;text-align:center">DG Afrique — le réseau où les capacités deviennent des actions.</div>
    </footer>
</div>
</x-layouts.portal>
