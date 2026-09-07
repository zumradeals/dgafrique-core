<x-layouts.member title="Mon espace" active="space">
    <div class="dg-space">
        <header class="dg-space-heading">
            <div><p class="dg-space-eyebrow">MON ESPACE</p><h1>{{ $isNewMember ? 'Bienvenue chez vous.' : 'Bonjour, '.$greetingName.'.' }}</h1><p>Faisons avancer ce qui compte.</p></div>
            <a class="dg-space-tool-link" href="#mes-outils"><x-dg.icon name="project" /> Mes outils</a>
        </header>

        @if ($isNewMember)
            <section class="dg-space-welcome" aria-labelledby="premier-pas">
                <div class="dg-space-welcome-art"><img src="{{ asset('images/entry/ensemble-640.webp') }}" srcset="{{ asset('images/entry/ensemble-640.webp') }} 640w, {{ asset('images/entry/ensemble-1280.webp') }} 1280w" sizes="(min-width: 900px) 45vw, 100vw" alt="" width="1280" height="853"><p>Chacun peut apporter<br>quelque chose.</p></div>
                <div class="dg-space-welcome-content">
                    <h2 id="premier-pas">Quel sera votre premier pas ?</h2><p>Commencez par ce qui compte pour vous.</p>
                    <div class="dg-space-intentions">
                        <a class="dg-space-row" href="#mon-savoir-faire"><x-dg.icon name="transmission" /><span><strong>Partager un savoir-faire</strong><small>Je peux apporter quelque chose</small></span><span aria-hidden="true">→</span></a>
                        <a class="dg-space-row" href="{{ route('needs.create') }}"><x-dg.icon name="need" /><span><strong>Exprimer un besoin</strong><small>J’ai un besoin</small></span><span aria-hidden="true">→</span></a>
                        <a class="dg-space-row" href="{{ route('projects.index') }}"><x-dg.icon name="project" /><span><strong>Participer à un projet</strong><small>Je veux participer</small></span><span aria-hidden="true">→</span></a>
                        <a class="dg-space-row" href="#decouvrir-le-reseau"><x-dg.icon name="discover" /><span><strong>Découvrir le réseau</strong><small>Je veux découvrir</small></span><span aria-hidden="true">→</span></a>
                    </div>
                    <p class="dg-space-note">Un premier pas suffit. Vous pourrez compléter votre profil à votre rythme.</p>
                </div>
            </section>
        @else
            <section class="dg-space-priority" aria-labelledby="dg-space-priority-title">
                <p class="dg-space-eyebrow">{{ $priority['label'] ?? 'À VOTRE RYTHME' }}</p>
                <h2 id="dg-space-priority-title">{{ $priority['heading'] ?? 'De la place pour votre prochain pas.' }}</h2>
                <p>{{ $priority['body'] ?? 'Rien ne réclame une décision maintenant.' }}</p>
                <x-dg.button :href="$priority['primary']['href'] ?? '#decouvrir-le-reseau'" variant="solar">{{ $priority['primary']['label'] ?? 'Explorer les possibilités' }}</x-dg.button>
            </section>
        @endif

        @if ($hasOtherAttention)
            <a class="dg-space-text-link" href="{{ route('notifications.index') }}">D’autres éléments attendent votre attention.</a>
        @endif
        <div class="dg-space-columns">
            <div>
                @if ($projectDraft)
                    <section class="dg-space-section"><h2>Votre projet en préparation</h2><p>Votre brouillon vous attend.</p><a class="dg-space-text-link" href="{{ route('projects.draft.show', ['draft' => $projectDraft, 'step' => $projectDraft->current_step]) }}">Reprendre mon brouillon →</a></section>
                @endif
                @foreach (['Pour vous maintenant' => $nextItems, 'Autour de vos engagements' => $weekItems] as $heading => $items)
                    @if ($items->isNotEmpty())
                        <section class="dg-space-section"><h2>{{ $heading }}</h2>
                            @foreach ($items as $item)<a class="dg-space-row" href="{{ $item['action_url'] }}"><span><strong>{{ $item['title'] }}</strong><small>{{ $item['summary'] }}</small></span><span aria-hidden="true">→</span></a>@endforeach
                        </section>
                    @endif
                @endforeach
                @if (count($receivedShares))
                    <section class="dg-space-section"><h2>Partagé avec vous</h2>
                        @foreach ($receivedShares as $share)<a class="dg-space-row" href="{{ $share['source_url'] }}"><span><strong>{{ $share['source_title'] }}</strong><small>{{ $share['sharer_label'] }} · {{ $share['context_note'] }}</small></span><span aria-hidden="true">→</span></a>@endforeach
                    </section>
                @endif
                <section id="mon-savoir-faire" class="dg-space-section" aria-labelledby="savoir-faire-title">
                    <p class="dg-space-eyebrow">CE QUE VOUS APPORTEZ</p><h2 id="savoir-faire-title">Un savoir-faire à partager.</h2>
                    <p>Une phrase suffit pour commencer.</p>
                    <form method="POST" action="{{ route('member.capability.quick') }}">
                        @csrf
                        <label for="capability">Qu’est-ce que vous savez faire ?</label>
                        <x-dg.input id="capability" :value="old('capability')" required minlength="3" maxlength="200" :invalid="$errors->has('capability')" aria-describedby="capability-help capability-error" placeholder="Par exemple : réparer des vélos" />
                        <p id="capability-help" class="dg-space-note">Cet ajout ne change pas vos choix de visibilité.</p>
                        <p id="capability-error" class="dg-space-error">@error('capability'){{ $message }}@enderror</p>
                        <x-dg.button type="submit">Enregistrer mon savoir-faire</x-dg.button>
                    </form>
                </section>
                @if (!$isNewMember || $myGroups->isNotEmpty() || $myOrganizations->isNotEmpty())
                    <section class="dg-space-section" aria-labelledby="engagements-title"><h2 id="engagements-title">Mes engagements</h2>
                        @forelse ($myGroups as $group)
                            <a class="dg-space-row" href="{{ route('zumra.groups.show', $group) }}"><x-dg.icon name="zumra" /><span><strong>{{ $group->name }}</strong><small>ZUMRA</small></span><span aria-hidden="true">→</span></a>
                        @empty
                            <p>Vous ne faites encore partie d’aucune ZUMRA active.</p>
                        @endforelse
                        @if ($myOrganizations->isNotEmpty())<h3>Mes Organisations</h3>@endif
                        @foreach ($myOrganizations as $organization)
                            <a class="dg-space-row" href="{{ route('organizations.show', $organization) }}"><x-dg.icon name="project" /><span><strong>{{ $organization->name }}</strong><small>Mon organisation</small></span><span aria-hidden="true">→</span></a>
                        @endforeach
                        <a class="dg-space-text-link" href="{{ route('zumra.index') }}">Ouvrir ZUMRA →</a>
                    </section>
                @endif
                <section id="decouvrir-le-reseau" class="dg-space-section" aria-labelledby="discover-title"><h2 id="discover-title">Des personnes avec qui agir.</h2><p>Explorez le réseau selon ce que vous cherchez.</p>
                    @foreach ([['people.index', 'Personnes', 'people'], ['needs.index', 'Besoins', 'need'], ['projects.index', 'Projets', 'project']] as [$destination, $label, $icon])
                        <a class="dg-space-row" href="{{ route($destination) }}"><x-dg.icon :name="$icon" /><strong>{{ $label }}</strong><span aria-hidden="true">→</span></a>
                    @endforeach
                </section>
            </div>
            <aside>
                @include('member.tools')
                <section class="dg-space-section"><a class="dg-space-row" href="{{ route('member.profile.edit') }}"><x-dg.icon name="space" /><span><strong>Mon profil et mes savoir-faire</strong><small>Votre présentation, vos choix de visibilité</small></span><span aria-hidden="true">→</span></a></section>
            </aside>
        </div>
    </div>
</x-layouts.member>
