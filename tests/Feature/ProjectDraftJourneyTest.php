<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Projects\ProjectDraftService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\Project;
use App\Models\ProjectBrainIntent;
use App\Models\ProjectDraft;
use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * UIUX-009B — parcours progressif de naissance d'un Projet, indépendant du Cerveau. Chaque test
 * vérifie une garantie explicitement demandée par le mandat de Phase B : sauvegarde/reprise,
 * isolation du propriétaire, création finale, idempotence, gates Programme/quota expliqués tôt,
 * porteur PERSON/ZUMRA, champs différables, absence de toute dépendance au Cerveau, et
 * non-régression du parcours Cerveau existant.
 */
final class ProjectDraftJourneyTest extends TestCase
{
    use RefreshDatabase;

    private array $ideaAnswers = [
        'nom' => ['name' => 'Bibliothèque solidaire'],
        'resume' => ['summary' => 'Ouvrir un petit espace de lecture pour les enfants du quartier, avec des livres donnés par les habitants.'],
        'probleme' => ['problem' => 'Les enfants du quartier n’ont nulle part où lire ou emprunter un livre après l’école aujourd’hui.'],
        'solution' => ['proposed_solution' => 'Installer une petite bibliothèque dans un local prêté, animée par des bénévoles du quartier.'],
        'beneficiaires' => ['beneficiaries' => 'Les enfants de 6 à 12 ans du quartier et leurs familles.'],
        'logistique' => ['domain' => 'EDUCATION', 'participation_mode' => 'PHYSICAL', 'location' => 'Bouaké'],
    ];

    // ===== Sauvegarde / reprise =====

    public function test_a_started_draft_is_resumed_at_its_current_step(): void
    {
        $this->activateProgram('IDN-PD-RESUME');
        $this->signIn('IDN-PD-RESUME');

        $this->get(route('projects.create'))->assertRedirect();
        $draft = $this->soleDraft('IDN-PD-RESUME');

        $this->post(route('projects.draft.update', [$draft, 'audience']), ['owner_type' => 'PERSON', '_intent' => 'continue'])->assertRedirect();
        $this->post(route('projects.draft.update', [$draft, 'nom']), $this->ideaAnswers['nom'] + ['_intent' => 'continue'])->assertRedirect();

        // Un nouveau passage par l'entrée reprend le même brouillon, à l'étape où il en était.
        $this->get(route('projects.create'))->assertRedirect(route('projects.draft.show', [$draft, 'resume']));

        $draft->refresh();
        self::assertSame('resume', $draft->current_step);
        self::assertSame('Bibliothèque solidaire', $draft->payload['name']);
    }

    public function test_enregistrer_et_continuer_plus_tard_saves_without_advancing_or_validating(): void
    {
        $this->activateProgram('IDN-PD-LATER');
        $this->signIn('IDN-PD-LATER');
        $this->get(route('projects.create'))->assertRedirect();
        $draft = $this->soleDraft('IDN-PD-LATER');
        $this->post(route('projects.draft.update', [$draft, 'audience']), ['owner_type' => 'PERSON', '_intent' => 'continue']);
        $this->post(route('projects.draft.update', [$draft, 'nom']), $this->ideaAnswers['nom'] + ['_intent' => 'continue']);

        // "summary" est trop court pour la validation normale, mais save_later ne valide jamais.
        $response = $this->post(route('projects.draft.update', [$draft, 'resume']), ['summary' => 'trop court', '_intent' => 'save_later']);
        $response->assertRedirect(route('member.space'));

        $draft->refresh();
        self::assertSame('resume', $draft->current_step, 'save_later ne doit jamais faire avancer le parcours.');
        self::assertSame('trop court', $draft->payload['summary']);
    }

    public function test_the_project_created_view_shows_a_resumable_card_with_no_percentage(): void
    {
        $this->activateProgram('IDN-PD-CARD');
        $this->signIn('IDN-PD-CARD');
        $this->get(route('projects.create'))->assertRedirect();
        $draft = $this->soleDraft('IDN-PD-CARD');
        $this->post(route('projects.draft.update', [$draft, 'audience']), ['owner_type' => 'PERSON', '_intent' => 'continue']);
        $this->post(route('projects.draft.update', [$draft, 'nom']), $this->ideaAnswers['nom'] + ['_intent' => 'save_later']);

        $content = $this->get(route('member.space'))->assertOk()->getContent();

        self::assertStringContainsString('Projet en préparation', $content);
        self::assertStringContainsString('Bibliothèque solidaire', $content);
        self::assertStringContainsString('Vous avez commencé cette idée.', $content);
        self::assertStringContainsString(route('projects.draft.show', [$draft, 'nom']), $content);

        // Le pourcentage de complétion de profil, ailleurs sur la page, est légitime — seule la
        // carte de reprise du brouillon ne doit jamais en afficher un.
        $cardStart = strpos($content, 'Projet en préparation');
        $cardEnd = strpos($content, '</section>', $cardStart);
        $card = substr($content, $cardStart, $cardEnd - $cardStart);
        self::assertDoesNotMatchRegularExpression('/\d+\s?%/', $card, 'Aucun pourcentage de complétion ne doit jamais être affiché.');
    }

    // ===== Isolation du propriétaire =====

    public function test_another_member_cannot_view_or_update_someone_elses_draft(): void
    {
        $this->activateProgram('IDN-PD-OWNER');
        $this->activateProgram('IDN-PD-INTRUDER');

        // Le brouillon de la victime est semé directement (service), sans passer par signIn() pour
        // elle : dans ce harnais, un second appel à signIn() dans le même test ne change jamais
        // l'identité effectivement authentifiée — seule l'intruse doit être réellement connectée.
        $draft = app(ProjectDraftService::class)->findOrStart('IDN-PD-OWNER');

        $this->signIn('IDN-PD-INTRUDER');

        $this->get(route('projects.draft.show', [$draft, 'audience']))->assertNotFound();
        $this->post(route('projects.draft.update', [$draft, 'audience']), ['owner_type' => 'PERSON'])->assertNotFound();
        $this->post(route('projects.draft.confirm', $draft))->assertNotFound();
        $this->post(route('projects.draft.abandon', $draft))->assertNotFound();
    }

    // ===== Création finale =====

    public function test_a_complete_walkthrough_creates_a_real_project_only_at_final_confirmation(): void
    {
        $this->activateProgram('IDN-PD-FULL');
        $this->signIn('IDN-PD-FULL');
        $draft = $this->walkThroughIdea('IDN-PD-FULL', 'PERSON');

        $this->post(route('projects.draft.update', [$draft, 'objectifs']), ['objectives' => ['Ouvrir deux après-midis par semaine'], '_intent' => 'continue']);
        $this->post(route('projects.draft.update', [$draft, 'besoins']), ['_intent' => 'continue']);

        self::assertDatabaseMissing('dg_projects', ['name' => 'Bibliothèque solidaire']);

        $response = $this->post(route('projects.draft.confirm', $draft));
        $project = Project::query()->where('name', 'Bibliothèque solidaire')->firstOrFail();
        $response->assertRedirect(route('projects.show', $project));

        self::assertSame(Project::STATUS_PROPOSED, $project->status);
        self::assertSame('IDEA', $project->maturity);
        self::assertSame(Project::OWNER_PERSON, $project->owner_type);
        self::assertSame('IDN-PD-FULL', $project->owner_reference);
        self::assertSame(Project::VISIBILITY_PRIVATE, $project->visibility);
        self::assertSame('PERSONAL_SUPPORTED', $project->property_regime);
        self::assertSame(['Ouvrir deux après-midis par semaine'], $project->objectives);

        $draft->refresh();
        self::assertSame(ProjectDraft::STATUS_CONFIRMED, $draft->status);
        self::assertSame($project->id, $draft->project_id);
        self::assertNotNull($draft->confirmed_at);
    }

    public function test_milestones_are_never_required_and_none_are_created_at_birth(): void
    {
        $this->activateProgram('IDN-PD-MILESTONES');
        $this->signIn('IDN-PD-MILESTONES');
        $draft = $this->walkThroughIdea('IDN-PD-MILESTONES', 'PERSON');
        $this->post(route('projects.draft.update', [$draft, 'objectifs']), ['objectives' => ['Un objectif'], '_intent' => 'continue']);
        $this->post(route('projects.draft.update', [$draft, 'besoins']), ['_intent' => 'continue']);
        $this->post(route('projects.draft.confirm', $draft));

        $project = Project::query()->where('name', 'Bibliothèque solidaire')->firstOrFail();
        self::assertSame(0, $project->milestones()->count(), 'Les jalons restent hors de la naissance du Projet — voir audit Phase A §8.');
    }

    // ===== Double confirmation / idempotence =====

    public function test_confirming_twice_never_creates_a_second_project(): void
    {
        $this->activateProgram('IDN-PD-IDEMPOTENT');
        $this->signIn('IDN-PD-IDEMPOTENT');
        $draft = $this->walkThroughIdea('IDN-PD-IDEMPOTENT', 'PERSON');
        $this->post(route('projects.draft.update', [$draft, 'objectifs']), ['objectives' => ['Un objectif'], '_intent' => 'continue']);
        $this->post(route('projects.draft.update', [$draft, 'besoins']), ['_intent' => 'continue']);

        $first = $this->post(route('projects.draft.confirm', $draft));
        $second = $this->post(route('projects.draft.confirm', $draft));

        self::assertSame(1, Project::query()->where('name', 'Bibliothèque solidaire')->count());
        self::assertSame($first->headers->get('Location'), $second->headers->get('Location'));
    }

    // ===== Gates Programme / quota, expliqués immédiatement =====

    public function test_entry_explains_missing_program_membership_immediately_without_starting_a_draft(): void
    {
        $this->signIn('IDN-PD-NOMEMBER');

        $content = $this->get(route('projects.create'))->assertOk()->getContent();

        self::assertStringContainsString('adhésion active est nécessaire', $content);
        self::assertStringContainsString(route('zumra.membership.show'), $content);
        self::assertSame(0, ProjectDraft::query()->where('actor_core_reference', 'IDN-PD-NOMEMBER')->count());
    }

    public function test_active_project_quota_is_explained_at_the_audience_step_not_after_the_whole_walkthrough(): void
    {
        $this->activateProgram('IDN-PD-QUOTA');
        $this->signIn('IDN-PD-QUOTA');

        // Sature le quota personnel (8 projets actifs par défaut) directement en base, sans passer
        // par le parcours — seul le comportement du gate nous intéresse ici.
        for ($i = 0; $i < 8; $i++) {
            Project::query()->create($this->rawProjectAttributes('IDN-PD-QUOTA', 'Projet existant '.$i));
        }

        $this->get(route('projects.create'))->assertRedirect();
        $draft = $this->soleDraft('IDN-PD-QUOTA');

        $response = $this->post(route('projects.draft.update', [$draft, 'audience']), ['owner_type' => 'PERSON', '_intent' => 'continue']);
        $response->assertSessionHasErrors('owner_type');

        $draft->refresh();
        self::assertSame('audience', $draft->current_step, 'Le quota doit bloquer avant même de commencer l’idée, jamais après.');
    }

    public function test_draft_creation_never_consumes_the_active_project_quota(): void
    {
        $this->activateProgram('IDN-PD-NOQUOTA');
        $this->signIn('IDN-PD-NOQUOTA');

        // Sept projets réels existent déjà (un de moins que le quota de 8) : un huitième doit
        // rester possible, et le simple fait de démarrer/abandonner des brouillons ne doit jamais
        // avoir réduit la marge disponible.
        for ($i = 0; $i < 7; $i++) {
            Project::query()->create($this->rawProjectAttributes('IDN-PD-NOQUOTA', 'Projet existant '.$i));
        }

        for ($i = 0; $i < 5; $i++) {
            $this->get(route('projects.create'))->assertRedirect();
            $draft = $this->soleDraft('IDN-PD-NOQUOTA');
            app(ProjectDraftService::class)->abandon($draft);
        }

        $this->get(route('projects.create'))->assertRedirect();
        $draft = $this->soleDraft('IDN-PD-NOQUOTA');
        $this->post(route('projects.draft.update', [$draft, 'audience']), ['owner_type' => 'PERSON', '_intent' => 'continue'])
            ->assertSessionDoesntHaveErrors('owner_type');
    }

    // ===== Porteur PERSON / ZUMRA =====

    public function test_a_project_can_be_owned_by_an_active_zumra_group(): void
    {
        $this->activateProgram('IDN-PD-ZLEADER');
        $this->signIn('IDN-PD-ZLEADER');
        $group = app(ZumraGroupService::class)->create('IDN-PD-ZLEADER', [
            'name' => 'ZUMRA Bibliothèques', 'domain' => 'Éducation',
            'founding_objective' => 'Réunir des personnes pour apprendre et transmettre.',
            'participation_mode' => 'HYBRID', 'internal_charter' => str_repeat('Respect, transmission. ', 5),
            'assume_primary_lead' => true,
        ]);

        $draft = $this->walkThroughIdea('IDN-PD-ZLEADER', 'GROUP', $group->public_reference);
        $this->post(route('projects.draft.update', [$draft, 'objectifs']), ['objectives' => ['Un objectif'], '_intent' => 'continue']);
        $this->post(route('projects.draft.update', [$draft, 'besoins']), ['_intent' => 'continue']);
        $this->post(route('projects.draft.confirm', $draft));

        $project = Project::query()->where('name', 'Bibliothèque solidaire')->firstOrFail();
        self::assertSame(Project::OWNER_GROUP, $project->owner_type);
        self::assertSame($group->id, $project->owner_reference);
        self::assertSame('ZUMRA_COLLECTIVE', $project->property_regime);
    }

    public function test_a_group_the_actor_is_not_an_active_member_of_is_rejected(): void
    {
        $this->activateProgram('IDN-PD-OUTSIDER');
        $this->signIn('IDN-PD-OUTSIDER');
        $group = app(ZumraGroupService::class)->create('IDN-PD-SOMEONEELSE', [
            'name' => 'ZUMRA Fermée', 'domain' => 'Éducation',
            'founding_objective' => 'Réunir des personnes pour apprendre et transmettre.',
            'participation_mode' => 'HYBRID', 'internal_charter' => str_repeat('Respect, transmission. ', 5),
            'assume_primary_lead' => true,
        ]);

        $this->get(route('projects.create'))->assertRedirect();
        $draft = $this->soleDraft('IDN-PD-OUTSIDER');

        $this->post(route('projects.draft.update', [$draft, 'audience']), [
            'owner_type' => 'GROUP', 'group_reference' => $group->public_reference, '_intent' => 'continue',
        ])->assertSessionHasErrors('group_reference');
    }

    // ===== Données différables =====

    public function test_capabilities_resources_and_risks_can_all_be_skipped(): void
    {
        $this->activateProgram('IDN-PD-SKIP');
        $this->signIn('IDN-PD-SKIP');
        $draft = $this->walkThroughIdea('IDN-PD-SKIP', 'PERSON');
        $this->post(route('projects.draft.update', [$draft, 'objectifs']), ['objectives' => ['Un objectif'], '_intent' => 'continue']);

        // « Je ne sais pas encore » = continuer sans rien renseigner, jamais bloqué.
        $this->post(route('projects.draft.update', [$draft, 'besoins']), ['_intent' => 'continue'])->assertSessionDoesntHaveErrors();
        $this->post(route('projects.draft.confirm', $draft));

        $project = Project::query()->where('name', 'Bibliothèque solidaire')->firstOrFail();
        self::assertSame([], $project->required_capabilities);
        self::assertSame([], $project->required_resources);
        self::assertSame([], $project->risks);
    }

    public function test_objectives_require_at_least_one_item(): void
    {
        $this->activateProgram('IDN-PD-EMPTYOBJ');
        $this->signIn('IDN-PD-EMPTYOBJ');
        $draft = $this->walkThroughIdea('IDN-PD-EMPTYOBJ', 'PERSON');

        $this->post(route('projects.draft.update', [$draft, 'objectifs']), ['_intent' => 'continue'])
            ->assertSessionHasErrors('objectives');

        $draft->refresh();
        self::assertSame('objectifs', $draft->current_step);
    }

    // ===== Parcours sans Cerveau =====

    public function test_the_full_walkthrough_never_touches_any_brain_route_or_model(): void
    {
        $this->activateProgram('IDN-PD-NOBRAIN');
        $this->signIn('IDN-PD-NOBRAIN');
        $draft = $this->walkThroughIdea('IDN-PD-NOBRAIN', 'PERSON');
        $this->post(route('projects.draft.update', [$draft, 'objectifs']), ['objectives' => ['Un objectif'], '_intent' => 'continue']);
        $this->post(route('projects.draft.update', [$draft, 'besoins']), ['_intent' => 'continue']);
        $this->post(route('projects.draft.confirm', $draft));

        self::assertSame(0, ProjectBrainIntent::query()->count(), 'Le parcours déterministe ne doit jamais créer d’intent Cerveau.');
    }

    // ===== Non-régression du parcours Cerveau =====

    public function test_the_brain_start_flow_still_works_unchanged(): void
    {
        $this->activateProgram('IDN-PD-BRAINCHECK');
        $this->signIn('IDN-PD-BRAINCHECK');

        $this->get(route('projects.brain.start'))->assertOk();

        $intent = ProjectBrainIntent::query()->create([
            'actor_core_reference' => 'IDN-PD-BRAINCHECK',
            'title' => 'Idée test',
            'messages' => [],
            'context' => [
                'project_state' => [
                    'name' => 'Projet Cerveau', 'summary' => str_repeat('Résumé suffisamment long pour passer la validation minimale. ', 2),
                    'problem' => str_repeat('Problème suffisamment détaillé pour passer la validation minimale. ', 2),
                    'proposed_solution' => str_repeat('Solution suffisamment détaillée pour passer la validation minimale. ', 2),
                    'beneficiaries' => ['Des habitants du quartier'],
                    'activity' => 'informatique', 'mode' => 'hybride', 'goal' => 'Un objectif clair',
                    'milestones' => ['Première étape clé'],
                    'ready_for_confirmation' => true,
                ],
            ],
            'status' => 'DRAFT',
        ]);

        $this->get(route('projects.brain.start.show', $intent))->assertOk();
        $response = $this->post(route('projects.brain.start.confirm', $intent));
        $project = Project::query()->where('name', 'Projet Cerveau')->firstOrFail();
        $response->assertRedirect(route('projects.brain.show', $project));

        self::assertSame(0, ProjectDraft::query()->count(), 'Le Cerveau ne doit jamais créer ni dépendre d’un ProjectDraft.');
    }

    // ===== Abandon =====

    public function test_abandoning_a_draft_starts_a_fresh_one_next_time(): void
    {
        $this->activateProgram('IDN-PD-ABANDON');
        $this->signIn('IDN-PD-ABANDON');
        $this->get(route('projects.create'))->assertRedirect();
        $first = $this->soleDraft('IDN-PD-ABANDON');

        $this->post(route('projects.draft.abandon', $first))->assertRedirect(route('projects.index'));
        $first->refresh();
        self::assertSame(ProjectDraft::STATUS_ABANDONED, $first->status);

        $this->get(route('projects.create'))->assertRedirect();
        $second = ProjectDraft::query()->where('actor_core_reference', 'IDN-PD-ABANDON')->where('status', ProjectDraft::STATUS_DRAFT)->firstOrFail();
        self::assertNotSame($first->id, $second->id);
    }

    // ===== Helpers =====

    private function walkThroughIdea(string $actor, string $ownerType, ?string $groupReference = null): ProjectDraft
    {
        $this->get(route('projects.create'))->assertRedirect();
        $draft = $this->soleDraft($actor);

        $audience = ['owner_type' => $ownerType, '_intent' => 'continue'];
        if ($groupReference) {
            $audience['group_reference'] = $groupReference;
        }
        $this->post(route('projects.draft.update', [$draft, 'audience']), $audience)->assertRedirect();

        foreach (['nom', 'resume', 'probleme', 'solution', 'beneficiaires', 'logistique'] as $step) {
            $this->post(route('projects.draft.update', [$draft, $step]), $this->ideaAnswers[$step] + ['_intent' => 'continue'])->assertRedirect();
        }

        return $draft->fresh();
    }

    private function soleDraft(string $actor): ProjectDraft
    {
        return ProjectDraft::query()->where('actor_core_reference', $actor)->where('status', ProjectDraft::STATUS_DRAFT)->latest('created_at')->firstOrFail();
    }

    private function rawProjectAttributes(string $actor, string $name): array
    {
        return [
            'public_reference' => (string) Str::uuid(), 'owner_type' => Project::OWNER_PERSON, 'owner_reference' => $actor,
            'initiator_core_reference' => $actor, 'name' => $name,
            'summary' => str_repeat('Résumé suffisant pour la contrainte de longueur. ', 2),
            'problem' => str_repeat('Problème suffisant pour la contrainte de longueur. ', 2),
            'proposed_solution' => str_repeat('Solution suffisante pour la contrainte de longueur. ', 2),
            'beneficiaries' => 'Des habitants du quartier concernés.',
            'domain' => 'EDUCATION', 'participation_mode' => 'PHYSICAL',
            'property_regime' => 'PERSONAL_SUPPORTED', 'visibility' => Project::VISIBILITY_PRIVATE,
            'status' => Project::STATUS_PROPOSED, 'maturity' => 'IDEA',
        ];
    }

    private function activateProgram(string $reference): void
    {
        $body = str_repeat('Respect et transmission. ', 8);
        $charter = ZumraCharter::query()->firstOrCreate(
            ['version' => '2026.1'],
            ['title' => 'Charte ZUMRA', 'body' => $body, 'content_hash' => hash('sha256', $body), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()],
        );
        ZumraProgramMembership::query()->firstOrCreate(
            ['core_identity_reference' => $reference],
            ['status' => ZumraProgramMembership::STATUS_ACTIVE, 'accepted_charter_id' => $charter->id, 'accepted_charter_version' => $charter->version, 'accepted_charter_hash' => $charter->content_hash, 'charter_accepted_at' => now(), 'submitted_at' => now(), 'activated_at' => now()],
        );
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-20T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre DG Afrique', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-20T23:59:00+00:00']),
        ]);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
