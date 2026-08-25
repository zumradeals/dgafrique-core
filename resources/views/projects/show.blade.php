@php
    // PROJECT-FUNDING-002 — libellés d'affichage ; $funding/$fundingCollected/$fundingRemaining/
    // $fundingHistory/$fundingContributorProfiles/$fundingContributionToken viennent du contrôleur,
    // jamais recalculés en vue.
    $fundingStatusLabels = ['OPEN' => 'Ouvert aux contributions', 'FUNDED' => 'Cible atteinte', 'CLOSED' => 'Clôturé', 'CANCELLED' => 'Annulé'];
    $fundingIsOpen = $funding && $funding->status === \App\Models\ProjectFunding::STATUS_OPEN;
    $statusLabels = ['PROPOSED' => 'Proposé', 'ADOPTED' => 'Adopté', 'IN_PROGRESS' => 'En action', 'COMPLETED' => 'Réalisé', 'ARCHIVED' => 'Archivé'];
    $isArchived = $project->status === \App\Models\Project::STATUS_ARCHIVED;
    $modeLabel = match($project->participation_mode) { 'PHYSICAL' => 'Physique', 'DIGITAL' => 'Numérique', default => 'Hybride' };
    $nextMilestone = $project->milestones->firstWhere('status', '!=', 'COMPLETED');
    $projectInitial = mb_strtoupper(mb_substr($project->name, 0, 1));
    $groupHref = $group ? route('zumra.groups.show', $group) : route('zumra.index');
@endphp
<x-layouts.portal title="{{ $project->name }} — Projet">
<x-dg.shell current="projets" :identity="$identity" :is-administrator="$isAdministrator">
<div class="ps-page">
@if(session('status'))<div class="dg-band">{{ session('status') }}</div>@endif
@if($errors->any())<div class="dg-band ps-error">{{ $errors->first() }}</div>@endif
<div class="ps-layout">
<aside class="ps-left" aria-label="Navigation ZUMRA">
    <a class="ps-back" href="{{ $groupHref }}">← Retour à l’espace ZUMRA</a>
    <nav class="ps-znav"><p class="ps-eyebrow">Espace ZUMRA</p>
        <a href="{{ $groupHref }}">⌂ <span>Accueil</span></a><a href="{{ $groupHref }}#fil">▦ <span>Fil d’activités</span></a><a href="{{ $groupHref }}#conversation">□ <span>Discussions</span></a><a href="{{ $groupHref }}#transmissions">◈ <span>Transmissions</span></a><a class="is-active" href="{{ $groupHref }}#projets">◇ <span>Projets</span></a><a href="{{ $groupHref }}#besoins">◎ <span>Besoins</span></a><a href="{{ $groupHref }}#membres">♧ <span>Membres</span><b>{{ $group?->active_member_count ?? $teamMembers->count() }}</b></a><a href="{{ $groupHref }}#activites">▣ <span>Activités</span></a><a href="{{ $groupHref }}#evenements">◫ <span>Événements</span></a><a href="{{ $groupHref }}#ressources">▱ <span>Ressources</span></a><a href="{{ $groupHref }}#gouvernance">⬡ <span>À propos & Gouvernance</span></a>
    </nav>
    <section class="ps-side-card"><h2>Besoin d’aide ?</h2><p>Notre guide est là pour vous accompagner à chaque étape.</p><a href="{{ route('people.index') }}">Contacter un guide</a></section>
    <section class="ps-side-card"><p class="ps-eyebrow">Statut de la ZUMRA</p><strong class="ps-green">● {{ $group ? 'En constitution' : 'Projet accompagné' }}</strong><span>{{ $teamMembers->count() }} membre{{ $teamMembers->count() > 1 ? 's' : '' }} du projet</span></section>
    <section class="ps-side-card ps-links"><p class="ps-eyebrow">Liens rapides</p><a href="{{ route('comments.project', $project) }}">Voir les commentaires</a><a href="{{ route('shares.project', $project) }}">Partager le projet</a>@if($canDecide)<a href="#equipe">Gérer l’équipe</a>@endif</section>
</aside>
<main class="ps-main" id="vue-ensemble">
    <section class="ps-hero">
        <div class="ps-cover">@if($project->image_path)<img src="{{ Illuminate\Support\Facades\Storage::disk('public')->url($project->image_path) }}" alt="Illustration du projet">@else<img src="{{ asset('images/project-community.svg') }}" alt="">@endif</div>
        <div class="ps-hero-copy"><div class="ps-tags"><span>Projet {{ $isArchived ? 'archivé' : 'actif' }}</span><span class="priority">{{ $project->maturity === 'IDEA' ? 'Idée en construction' : 'Priorité du collectif' }}</span></div><h1>{{ $project->name }}</h1><p>{{ $project->summary }}</p><div class="ps-meta"><span>◇ Porté par {{ $group?->name ?? 'un membre DG Afrique' }}</span>@if($project->location)<span>⌖ {{ $project->location }}</span>@endif<span>♙ {{ $teamMembers->count() }} membre{{ $teamMembers->count() > 1 ? 's' : '' }} impliqué{{ $teamMembers->count() > 1 ? 's' : '' }}</span></div></div>
        <div class="ps-progress"><strong>{{ $progressSeed }}%</strong><span>Progression globale</span><small>Repère visuel, sans effet sur la maturité</small></div>
        <div class="ps-dates"><p><i>▣</i><span>Date de démarrage<strong>{{ ($project->started_at ?? $project->adopted_at ?? $project->created_at)->translatedFormat('d F Y') }}</strong></span></p><p><i>⚑</i><span>Jalon prochain<strong>{{ $nextMilestone?->title ?? 'À définir par l’équipe' }}</strong></span></p></div>
    </section>
    <nav class="ps-tabs" aria-label="Sections du projet"><a class="active" href="#vue-ensemble">◉ Vue d’ensemble</a><a href="#taches">☑ Actions & tâches</a><a href="#jalons">⚑ Jalons</a><a href="#equipe">♧ Équipe</a><a href="#besoins">◎ Besoins</a><a href="#ressources">▱ Ressources</a><a href="#financement">✚ Financement</a><a href="#documents">□ Documents</a><a href="#parametres">⚙ Paramètres</a></nav>
    <div class="ps-center-grid">
        <div class="ps-stack"><section class="ps-panel"><div class="ps-panel-head"><h2>Résumé du projet</h2></div><p>{{ $project->summary }}</p><div class="ps-facts"><span>Catégorie<strong>{{ $configuration['domains'][$project->domain] ?? $project->domain }}</strong></span><span>Impact attendu<strong>{{ $project->beneficiaries ?: 'À préciser' }}</strong></span><span>Mode<strong>{{ $modeLabel }}</strong></span></div></section>
        <section class="ps-panel"><div class="ps-panel-head"><h2>Actions récentes</h2><a href="{{ route('comments.project', $project) }}">Voir tout →</a></div>@forelse($recentEvents->take(4) as $event) @php($profile=$eventActorProfiles->get($event->actor_core_reference)) <article class="ps-activity"><i>{{ mb_strtoupper(mb_substr($profile?->discovery_display_name ?? 'P',0,1)) }}</i><div><strong>{{ $profile?->discovery_display_name ?? 'Membre du projet' }}</strong><p>{{ $event->label() }}</p><small>{{ $event->occurred_at?->diffForHumans() }}</small></div></article>@empty<div class="ps-empty"><strong>L’activité commence ici.</strong><p>Aucune action récente n’est encore enregistrée.</p><a href="{{ route('comments.project', $project) }}">Publier une première contribution →</a></div>@endforelse</section></div>
        <div class="ps-stack"><section class="ps-panel" id="jalons"><div class="ps-panel-head"><h2>Avancement par jalons</h2></div>@forelse($project->milestones->take(4) as $milestone)<div class="ps-milestone"><span>{{ $loop->iteration }}. {{ $milestone->title }}</span><b>{{ $milestone->status === 'COMPLETED' ? 'Terminé' : 'À venir' }}</b><i><em style="width:{{ $milestone->status === 'COMPLETED' ? 100 : 0 }}%"></em></i></div>@empty<p class="ps-muted">Aucun jalon défini. L’équipe peut structurer son chemin sans qu’aucune étape soit inventée.</p>@endforelse</section>
        <section class="ps-panel" id="taches"><div class="ps-panel-head"><h2>Tâches en cours</h2></div><p class="ps-muted">Le suivi détaillé des tâches arrive progressivement. Aucun responsable n’est affecté en silence.</p>@if($canProposeMission)<a class="ps-action-link" href="{{ route('projects.missions.create', $project) }}">Proposer une Mission →</a>@endif</section></div>
    </div>
</main>
<aside class="ps-right">
    <section class="ps-panel"><div class="ps-panel-head"><h2>État du projet</h2></div><span class="ps-state">{{ $statusLabels[$project->status] ?? $project->status }}</span><p>{{ $isArchived ? 'Ce projet est archivé.' : 'Le projet avance selon les décisions explicites de son porteur et de son équipe.' }}</p></section>
    <section class="ps-panel" id="besoins"><div class="ps-panel-head"><h2>Besoins du projet</h2><a href="{{ route('needs.index') }}">Voir tout →</a></div>@forelse($projectNeeds->take(4) as $need)<a class="ps-list-row" href="{{ route('needs.show',$need) }}"><span>✓</span><strong>{{ $need->title }}</strong></a>@empty<p class="ps-muted">Aucun besoin vivant exprimé.</p>@endforelse @if($canProposeNeed)<a class="ps-outline" href="{{ route('needs.create',['project'=>$project->public_reference]) }}">Déclarer un besoin</a>@endif</section>
    <section class="ps-panel" id="equipe"><div class="ps-panel-head"><h2>Membres impliqués</h2></div><div class="ps-avatars">@foreach($teamMembers->take(7) as $member) @php($name=$teamProfiles->get($member->core_identity_reference)?->discovery_display_name)<span title="{{ $name ?: 'Membre attesté' }}">{{ mb_strtoupper(mb_substr($name ?: 'M',0,1)) }}</span>@endforeach</div>@if($teamMembers->isEmpty())<p class="ps-muted">Aucune personne n’a encore rejoint l’équipe.</p>@endif</section>
    <section class="ps-panel" id="ressources"><div class="ps-panel-head"><h2>Ressources clés</h2></div>@forelse($project->required_resources as $resource)<div class="ps-resource">□ {{ $resource }}</div>@empty<p class="ps-muted">Aucune ressource déclarée.</p>@endforelse</section>
    <section class="ps-panel" id="financement">
        <div class="ps-panel-head"><h2>Financement</h2></div>
        @if($funding)
            <p class="ps-muted">{{ $fundingStatusLabels[$funding->status] ?? $funding->status }} · {{ $funding->purpose }}</p>
            <div class="ps-facts">
                <span>Objectif<strong>{{ number_format($funding->target_amount, 0, ',', ' ') }} ZAHAB</strong></span>
                <span>Collecté<strong>{{ number_format($fundingCollected, 0, ',', ' ') }} ZAHAB</strong></span>
                <span>Reste<strong>{{ number_format($fundingRemaining, 0, ',', ' ') }} ZAHAB</strong></span>
            </div>
            @if($fundingIsOpen)
                <form method="POST" action="{{ route('projects.funding.contribute', $project) }}" style="display:flex;gap:8px;align-items:end;margin-top:10px;flex-wrap:wrap">
                    @csrf
                    <input type="hidden" name="contribution_token" value="{{ $fundingContributionToken }}">
                    <div class="dg-field" style="margin:0">
                        <label for="funding-amount">Montant (max {{ number_format($fundingRemaining, 0, ',', ' ') }})</label>
                        <input type="number" id="funding-amount" name="amount" class="dg-input" min="1" max="{{ $fundingRemaining }}" step="1" required style="max-width:160px">
                    </div>
                    <button type="submit" class="dg-btn dg-btn--saffron">Financer avec ZAHAB</button>
                </form>
                <p class="dg-hint">Débite votre Wallet Personne, crédite le Wallet de {{ $group?->name ?? 'la ZUMRA porteuse' }}. Aucun dépassement de la cible n’est possible.</p>
            @elseif($funding->status === \App\Models\ProjectFunding::STATUS_FUNDED)
                <p class="ps-muted">Cette déclaration a atteint sa cible grâce aux contributions ci-dessous.</p>
            @endif
            @if($fundingHistory->isNotEmpty())
                <p class="ps-eyebrow" style="margin-top:12px">Contributions</p>
                @foreach($fundingHistory->take(8) as $movement)
                    @php($contributorName = $fundingContributorProfiles->get($movement->subject_reference)?->discovery_display_name ?? 'Membre DG Afrique')
                    <div class="ps-list-row"><span>◆</span><strong>{{ $contributorName }}</strong> a financé {{ number_format($movement->amount, 0, ',', ' ') }} ZAHAB · {{ $movement->occurred_at?->diffForHumans() }} · Confirmé</div>
                @endforeach
            @endif
        @else
            <p class="ps-muted">Aucun besoin financier déclaré. Une déclaration reste purement informative : aucune collecte ni décaissement automatique n’en découle.</p>
            @if($canDecide)
                <form method="POST" action="{{ route('projects.funding.store', $project) }}" style="display:flex;flex-direction:column;gap:8px;margin-top:10px">
                    @csrf
                    <div class="dg-field" style="margin:0">
                        <label for="funding-target">Montant cible (ZAHAB/XOF)</label>
                        <input type="number" id="funding-target" name="target_amount" class="dg-input" min="1" required>
                    </div>
                    <input type="hidden" name="currency" value="XOF">
                    <div class="dg-field" style="margin:0">
                        <label for="funding-purpose">Objet de ce besoin</label>
                        <textarea id="funding-purpose" name="purpose" class="dg-textarea" rows="2" minlength="10" maxlength="2000" required></textarea>
                    </div>
                    <div class="dg-field" style="margin:0">
                        <label for="funding-use">Usage prévu</label>
                        <textarea id="funding-use" name="intended_use" class="dg-textarea" rows="2" minlength="10" maxlength="2000" required></textarea>
                    </div>
                    <button type="submit" class="dg-btn dg-btn--quiet" style="align-self:flex-start">Déclarer un besoin financier</button>
                </form>
            @endif
        @endif
    </section>
    <section class="ps-panel ps-quick-actions"><h2>Actions rapides</h2><a href="{{ route('comments.project',$project) }}">Publier une mise à jour</a><a href="{{ route('shares.project',$project) }}">Partager une ressource</a>@if($canProposeMission)<a href="{{ route('projects.missions.create',$project) }}">Planifier une mission</a>@endif</section>
</aside></div>
<section class="ps-path"><div><h2>Notre chemin vers la réussite</h2><p>Ce projet est une aventure collective. Chaque étape nous rapproche de notre impact.</p></div><ol><li><i>◎</i><span><strong>1. Comprendre le besoin</strong>Partons d’un besoin réel.</span></li><li><i>♧</i><span><strong>2. Construire ensemble</strong>Combinons talents et compétences.</span></li><li><i>◇</i><span><strong>3. Tester & ajuster</strong>Validons avec les utilisateurs.</span></li><li><i>◉</i><span><strong>4. Lancer & impacter</strong>Créons un impact réel.</span></li><li><i>▽</i><span><strong>5. Apprendre & grandir</strong>Capitalisons nos réussites.</span></li></ol></section>
<details class="ps-deep" id="documents"><summary>Cadre complet, collaborations et gouvernance du projet <b>Ouvrir +</b></summary><div class="ps-deep-body"><section><h2>Collaborations</h2>@forelse($projectPartnerships as $row)<x-dg.partnership-row :row="$row" />@empty<p class="ps-muted">Aucune collaboration déclarée.</p>@endforelse<x-dg.partnership-propose-form :organizations="$manageableOrganizations" :capabilities="$manageableOrganizationCapabilities" context-type="PROJECT" :context-reference="$project->public_reference" /></section><section id="parametres"><h2>Repères et garanties</h2><p>Le financement de ce projet (le cas échéant) est géré dans l’onglet <a href="#financement">Financement</a>, uniquement en ZAHAB, jamais un décaissement. Cette présentation ne constitue ni une collecte externe, ni une promesse d’accompagnement. Aucun membre, rôle ou responsable n’est créé silencieusement.</p>@if($canDecide)<a href="{{ route('projects.brain.show',$project) }}">Ouvrir le Cerveau du projet →</a>@endif</section></div></details>
</div></x-dg.shell></x-layouts.portal>
