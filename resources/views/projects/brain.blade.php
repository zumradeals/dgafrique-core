{{--
    Cerveau du Projet — refonte visuelle V1 (PVB-I05, docs/design/DESIGN-INVARIANTS.md addendum
    §20), fidèle à la maquette du 20 août 2026. Rejoint enfin la navigation globale DG Afrique
    (x-dg.shell) et le langage visuel de /projets et /projets/{project} : le Cerveau ne doit plus
    sembler appartenir à un autre produit.

    Le moteur n'est pas touché : conversation, brouillons (ProjectBrainDraft) et leur confirmation
    obligatoire (projects.brain.needs.confirm / .drafts.cancel) restent exactement ceux de
    ProjectBrainNeedDraftService — aucune mutation Core silencieuse.
--}}
@php
    $statusLabels = ['PROPOSED' => 'Proposé', 'ADOPTED' => 'Adopté', 'IN_PROGRESS' => 'En action', 'COMPLETED' => 'Réalisé'];
@endphp
<x-layouts.portal title="Cerveau — {{ $project->name }} — DG Afrique">
    <x-dg.shell current="projets" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-brain-layout">
            <aside class="dg-brain-sidebar dg-brain-sidebar--desktop">
                @include('projects.partials.brain-sidebar')
            </aside>

            <main class="dg-brain-main">
                <details class="dg-brain-drawer dg-brain-drawer--sidebar">
                    <summary>Projets &amp; conversations <x-dg.icon name="chevron-down" size="16" /></summary>
                    <div class="dg-brain-sidebar">
                        @include('projects.partials.brain-sidebar')
                    </div>
                </details>

                <div class="dg-brain-head">
                    <div class="dg-brain-head__title">
                        <span class="dg-brain-head__icon" aria-hidden="true"><x-dg.icon name="brain" size="24" /></span>
                        <div>
                            <h1>Cerveau du projet <span class="dg-brain-beta">BETA</span></h1>
                            <p>Votre assistant stratégique pour réaliser {{ $project->name }}.</p>
                        </div>
                    </div>
                    <div class="dg-brain-head__actions">
                        <span class="dg-brain-mode" title="D’autres modes arriveront avec les futurs rôles d’équipe.">Mode · Collaboratif</span>
                        <x-dg.btn variant="quiet" :href="route('projects.show', $project)">Voir le projet →</x-dg.btn>
                    </div>
                </div>

                @if(session('status'))
                    <div class="dg-brain-status-flash">{{ session('status') }}</div>
                @endif

                <section class="dg-brain-thread" aria-label="Conversation avec le Cerveau">
                    @if($messages->isEmpty())
                        <div class="dg-brain-msg dg-brain-msg--assistant">Bonjour. Parlons du projet naturellement. Je peux réfléchir avec vous et proposer des actions, mais vous gardez toujours la décision finale.</div>

                        <div class="dg-brain-draft">
                            <div class="dg-brain-draft__head"><x-dg.icon name="check-circle" size="14" /> Exemple d’action en attente de validation <span>· Exemple, pas encore de conversation</span></div>
                            <div class="dg-brain-draft__body">
                                <span class="dg-brain-draft__icon" aria-hidden="true"><x-dg.icon name="team" size="20" /></span>
                                <div>
                                    <h3>Créer une équipe projet (minimum 3 personnes)</h3>
                                    <p>Rôles recommandés : Responsable, Technicien, Formateur.</p>
                                </div>
                                <div class="dg-brain-draft__actions">
                                    <x-dg.btn variant="project" disabled title="Exemple d’illustration — parlez au Cerveau ci-dessous pour obtenir de vraies propositions.">Valider</x-dg.btn>
                                    <x-dg.btn variant="quiet" disabled title="Exemple d’illustration — parlez au Cerveau ci-dessous pour obtenir de vraies propositions.">Modifier</x-dg.btn>
                                </div>
                            </div>
                        </div>
                    @endif

                    @foreach($messages as $message)
                        <div class="dg-brain-msg dg-brain-msg--{{ mb_strtolower($message->role) }}">{{ $message->content }}</div>

                        @php($draftReference = $message->meta['draft_reference'] ?? null)
                        @if($draftReference && ($draft = $drafts->get($draftReference)))
                            @php($payload = $draft->payload)
                            <div class="dg-brain-draft">
                                <div class="dg-brain-draft__head"><x-dg.icon name="check-circle" size="14" /> Action proposée <span>en attente de votre confirmation</span></div>
                                <div class="dg-brain-draft__body">
                                    <span class="dg-brain-draft__icon" aria-hidden="true"><x-dg.icon name="target" size="20" /></span>
                                    <div>
                                        <h3>{{ $payload['title'] }}</h3>
                                        <p>{{ $payload['context'] }}</p>
                                        <div class="dg-brain-draft__meta">
                                            <span>Catégorie<strong>{{ $needConfiguration['categories'][$payload['category']] ?? $payload['category'] }}</strong></span>
                                            <span>Lieu<strong>{{ $payload['location'] ?? '—' }}</strong></span>
                                            <span>Mode<strong>{{ $payload['collaboration_mode'] }}</strong></span>
                                        </div>
                                    </div>
                                    <div class="dg-brain-draft__actions">
                                        <form method="POST" action="{{ route('projects.brain.needs.confirm', [$project, $draft]) }}">
                                            @csrf
                                            <button type="submit" class="dg-btn dg-btn--project">Créer ce besoin</button>
                                        </form>
                                        <form method="POST" action="{{ route('projects.brain.drafts.cancel', [$project, $draft]) }}">
                                            @csrf
                                            <button type="submit" class="dg-btn dg-btn--quiet">Pas maintenant</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </section>

                <form class="dg-brain-compose" method="POST" action="{{ route('projects.brain.needs.prepare', $project) }}">
                    @csrf
                    <textarea name="message" required minlength="2" maxlength="3000" placeholder="Parlez au Cerveau de votre projet…">{{ old('message') }}</textarea>
                    <div class="dg-brain-compose__row">
                        <div class="dg-brain-compose__tools">
                            <button type="button" disabled title="L’envoi de pièces jointes arrivera avec l’espace documentaire du projet."><x-dg.icon name="paperclip" size="17" /></button>
                            <button type="button" disabled title="L’envoi d’images arrivera avec l’espace documentaire du projet."><x-dg.icon name="image" size="17" /></button>
                            <button type="button" disabled title="L’envoi de documents arrivera avec l’espace documentaire du projet."><x-dg.icon name="document" size="17" /></button>
                            <button type="button" disabled title="La saisie vocale n’est pas encore disponible."><x-dg.icon name="mic" size="17" /></button>
                        </div>
                        <button type="submit" class="dg-brain-send" aria-label="Envoyer"><x-dg.icon name="send" size="17" /></button>
                    </div>
                </form>
                <p class="dg-brain-mantra">Le Cerveau propose, vous décidez, le projet avance.</p>
            </main>

            <aside class="dg-brain-aside dg-brain-aside--desktop">
                @include('projects.partials.brain-aside')
            </aside>
        </div>

        <details class="dg-brain-drawer dg-brain-drawer--aside" style="margin:0 16px 16px">
            <summary>Projet vivant <x-dg.icon name="chevron-down" size="16" /></summary>
            <div class="dg-brain-aside" style="border-left:0">
                @include('projects.partials.brain-aside')
            </div>
        </details>
    </x-dg.shell>
</x-layouts.portal>
