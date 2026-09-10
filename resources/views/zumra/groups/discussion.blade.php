<x-layouts.member :title="'Discussion · '.$group->name" active="zumra" :wide="true">
    <div class="dg-zumra-discussion">
        <a class="dg-zumra-discussion__back" href="{{ route('zumra.groups.show', $group) }}">← Retour à {{ $group->name }}</a>

        <section class="dg-zumra-discussion__hero" aria-labelledby="zumra-discussion-title">
            <div>
                <p class="dg-zumra-discussion__doctrine">FORMATION · TRAVAIL · ADORATION</p>
                <p class="dg-zumra-discussion__eyebrow">DISCUSSION</p>
                <h1 id="zumra-discussion-title">La conversation de {{ $group->name }}</h1>
                <p>Un espace réservé aux membres actifs pour poser une question, apporter une précision, partager une ressource ou coordonner une action.</p>
            </div>
            <div class="dg-zumra-discussion__world">
                <span>MONDE ZUMRA</span>
                <strong>{{ $group->name }}</strong>
                <p>{{ $context['summary'] }}</p>
            </div>
        </section>

        <nav class="dg-zumra-discussion__tabs" aria-label="Navigation dans la ZUMRA">
            <a href="{{ route('zumra.groups.show', $group) }}">⌂ Accueil</a>
            <a href="{{ route('zumra.groups.formation', $group) }}">◈ Formation</a>
            <a href="{{ route('zumra.groups.show', $group) }}#projets">▣ Projets</a>
            <a href="{{ route('zumra.groups.show', $group) }}#membres">♙ Membres</a>
            <a class="is-active" href="{{ route('zumra.groups.discussion', $group) }}" aria-current="page">▢ Discussion</a>
            <a href="{{ route('zumra.groups.show', $group) }}#evenements">▣ Événements</a>
            <a href="{{ route('zumra.groups.show', $group) }}#besoins">♡ Besoins</a>
            <a href="{{ route('zumra.groups.show', $group) }}#missions">◉ Missions</a>
        </nav>

        @if (session('status'))
            <div class="dg-zumra-discussion__notice" role="status">{{ session('status') }}</div>
        @endif

        <div class="dg-zumra-discussion__layout">
            <main class="dg-zumra-discussion__main">
                <section class="dg-zumra-discussion__panel">
                    <div class="dg-zumra-discussion__panel-head">
                        <div>
                            <p class="dg-zumra-discussion__eyebrow">FIL DE DISCUSSION</p>
                            <h2>Parler pour mieux agir.</h2>
                        </div>
                        <span class="dg-zumra-discussion__privacy">Membres actifs uniquement</span>
                    </div>

                    <div class="dg-zumra-discussion__thread" aria-live="polite">
                        @forelse ($comments as $comment)
                            <article class="dg-zumra-discussion__message">
                                <div class="dg-zumra-discussion__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($comment['author_label'], 0, 1)) }}</div>
                                <div class="dg-zumra-discussion__message-body">
                                    <div class="dg-zumra-discussion__message-meta">
                                        <strong>{{ $comment['author_label'] }}</strong>
                                        <span>{{ $comment['purpose_label'] }}</span>
                                        <time datetime="{{ $comment['posted_at']?->toIso8601String() }}">{{ $comment['posted_at']?->diffForHumans() }}</time>
                                    </div>
                                    <p>{{ $comment['body'] }}</p>
                                </div>
                            </article>
                        @empty
                            <div class="dg-zumra-discussion__empty">
                                <span aria-hidden="true">▢</span>
                                <h3>Aucun message pour le moment.</h3>
                                <p>Ouvrez la discussion avec une question, une précision, une ressource ou un point de coordination utile à la ZUMRA.</p>
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="dg-zumra-discussion__panel dg-zumra-discussion__composer" aria-labelledby="discussion-compose-title">
                    <div>
                        <p class="dg-zumra-discussion__eyebrow">CONTRIBUER</p>
                        <h2 id="discussion-compose-title">Ajouter quelque chose d’utile</h2>
                        <p>Choisissez l’intention de votre message pour garder la conversation claire et orientée vers l’action.</p>
                    </div>

                    <form method="POST" action="{{ route('zumra.groups.discussion.store', $group) }}">
                        @csrf
                        <label>
                            <span>Type de contribution</span>
                            <select name="purpose" required>
                                @foreach ($purposeLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('purpose') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label>
                            <span>Votre message</span>
                            <textarea name="body" rows="5" minlength="2" maxlength="1200" required placeholder="Écrivez un message concret, compréhensible et utile à la prochaine action…">{{ old('body') }}</textarea>
                        </label>

                        @if ($errors->any())
                            <div class="dg-zumra-discussion__errors" role="alert">
                                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                            </div>
                        @endif

                        <div class="dg-zumra-discussion__composer-foot">
                            <p>Visible uniquement dans cette ZUMRA aux membres actifs. Votre identité vient de votre session GAMAD.</p>
                            <button type="submit">Publier dans la discussion</button>
                        </div>
                    </form>
                </section>
            </main>

            <aside class="dg-zumra-discussion__aside">
                <section class="dg-zumra-discussion__panel">
                    <p class="dg-zumra-discussion__eyebrow">DANS CETTE DISCUSSION</p>
                    <h2>Un canal de travail collectif.</h2>
                    <div class="dg-zumra-discussion__purpose-list">
                        @foreach ($purposeLabels as $label)
                            <span>{{ $label }}</span>
                        @endforeach
                    </div>
                </section>

                <section class="dg-zumra-discussion__panel dg-zumra-discussion__principles">
                    <h2>Rester relié à l’action</h2>
                    <p>Précisez le besoin, partagez ce qui aide réellement, coordonnez la prochaine étape et gardez les échanges dans le contexte de {{ $group->name }}.</p>
                    <p>Aucun score de popularité, aucun classement des personnes : la discussion sert la progression collective.</p>
                </section>
            </aside>
        </div>
    </div>
</x-layouts.member>
