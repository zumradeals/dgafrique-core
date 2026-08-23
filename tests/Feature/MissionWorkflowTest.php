<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Missions\MissionAssignmentService;
use App\Application\Missions\MissionBlockerService;
use App\Application\Missions\MissionSubmissionService;
use App\Application\Missions\MissionWorkflow;
use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\MissionSubmission;
use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MissionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_happy_path_on_a_project_context(): void
    {
        $this->activateProgram('IDN-OWNER');
        $project = app(ProjectService::class)->create('IDN-OWNER', $this->projectPayload(), (new ProjectConfiguration)->defaults());

        $workflow = app(MissionWorkflow::class);
        $assignments = app(MissionAssignmentService::class);
        $submissions = app(MissionSubmissionService::class);

        $mission = $workflow->create('IDN-OWNER', 'PROJECT', $project->public_reference, [
            'title' => 'Préparer le lancement pilote',
            'description' => 'Organiser la première session pilote avec les premiers bénéficiaires.',
            'visibility' => Mission::VISIBILITY_CONTEXT,
        ]);
        self::assertSame(Mission::STATUS_DRAFT, $mission->status);

        $mission = $workflow->propose($mission, 'IDN-OWNER');
        self::assertSame(Mission::STATUS_PROPOSED, $mission->status);

        $mission = $workflow->officialize($mission, 'IDN-OWNER', ['expected_result' => 'Une session pilote réalisée avec un retour documenté.']);
        self::assertSame(Mission::STATUS_OPEN, $mission->status);

        $assignment = $assignments->offer($mission, 'IDN-EXEC', MissionAssignment::ROLE_EXECUTOR);
        self::assertSame(MissionAssignment::STATUS_OFFERED, $assignment->status);

        $assignment = $assignments->acceptOffer($mission, 'IDN-OWNER', $assignment);
        self::assertSame(MissionAssignment::STATUS_ACCEPTED, $assignment->status);

        $mission = $workflow->start($mission, 'IDN-EXEC');
        self::assertSame(Mission::STATUS_IN_PROGRESS, $mission->status);

        $submissions->contribute($mission, 'IDN-EXEC', 'Session préparée, matériel réuni.');

        $submission = $submissions->submit($mission, 'IDN-EXEC', 'Session pilote réalisée le 20 août avec 8 participants.');
        self::assertSame(1, $submission->version);
        $mission = $mission->fresh();
        self::assertSame(Mission::STATUS_SUBMITTED, $mission->status);

        $mission = $submissions->accept($mission, 'IDN-OWNER', 'Bon travail.');
        self::assertSame(Mission::STATUS_COMPLETED, $mission->status);

        self::assertSame(1, MissionSubmission::query()->where('mission_id', $mission->id)->count());
        self::assertDatabaseHas('dg_mission_events', ['mission_id' => $mission->id, 'event' => 'MISSION_COMPLETED']);
    }

    public function test_block_and_resolve_cycle(): void
    {
        $this->activateProgram('IDN-OWNER');
        $project = app(ProjectService::class)->create('IDN-OWNER', $this->projectPayload(), (new ProjectConfiguration)->defaults());
        $workflow = app(MissionWorkflow::class);
        $assignments = app(MissionAssignmentService::class);
        $blockers = app(MissionBlockerService::class);

        $mission = $workflow->create('IDN-OWNER', 'PROJECT', $project->public_reference, [
            'title' => 'Réserver le lieu',
            'description' => 'Trouver et réserver un lieu pour la session pilote.',
        ]);
        $mission = $workflow->propose($mission, 'IDN-OWNER');
        $mission = $workflow->officialize($mission, 'IDN-OWNER', ['expected_result' => 'Un lieu réservé et confirmé.']);
        $a = $assignments->offer($mission, 'IDN-EXEC', MissionAssignment::ROLE_EXECUTOR);
        $assignments->acceptOffer($mission, 'IDN-OWNER', $a);
        $mission = $workflow->start($mission, 'IDN-EXEC');

        $mission = $blockers->block($mission, 'IDN-EXEC', 'MISSING_RESOURCE', 'Aucun budget disponible pour la location.');
        self::assertSame(Mission::STATUS_BLOCKED, $mission->status);

        $blocker = $mission->blockers()->first();
        $mission = $blockers->resolve($mission, 'IDN-OWNER', $blocker, 'Budget débloqué.');
        self::assertSame(Mission::STATUS_IN_PROGRESS, $mission->status);
    }

    private function zumraFor(string $actor): string
    {
        return app(ZumraGroupService::class)->create($actor, [
            'name' => 'ZUMRA '.$actor.' '.uniqid(), 'domain' => 'Général',
            'founding_objective' => str_repeat('Ancrer les projets de test dans une ZUMRA réelle. ', 2),
            'participation_mode' => 'HYBRID', 'internal_charter' => str_repeat('Respect, transmission et responsabilité partagée. ', 4),
            'assume_primary_lead' => true,
        ])->public_reference;
    }

    private function projectPayload(array $overrides = []): array
    {
        return array_replace([
            'owner_type' => 'PERSON', 'group_reference' => null, 'zumra_group_reference' => $this->zumraFor('IDN-OWNER'), 'source_need_reference' => null,
            'name' => 'Atelier numérique communautaire',
            'summary' => 'Créer un espace pratique où des jeunes peuvent apprendre ensemble et produire des services numériques utiles.',
            'problem' => 'Des jeunes motivés disposent de peu de cadres pratiques pour apprendre, expérimenter et transformer leurs acquis en activités utiles.',
            'proposed_solution' => 'Mettre en place un atelier progressif avec transmission entre pairs, exercices réels et accompagnement vers des premiers services.',
            'beneficiaries' => 'Jeunes débutants et personnes en reconversion dans la commune.',
            'domain' => 'DIGITAL', 'participation_mode' => 'HYBRID', 'location' => 'Abidjan',
            'objectives' => "Former une première équipe\nProduire trois services pilotes",
            'required_capabilities' => "Formation numérique\nGestion de projet",
            'required_resources' => "Ordinateurs\nConnexion internet",
            'risks' => "Disponibilité irrégulière\nAccès au matériel",
            'milestones' => "Constituer l’équipe\nPréparer le lieu\nLancer le pilote",
            'property_regime' => 'PERSONAL_SUPPORTED', 'visibility' => 'PUBLIC',
        ], $overrides);
    }

    private function activateProgram(string $reference): void
    {
        $body = str_repeat('Respect et transmission. ', 5);
        $charter = ZumraCharter::query()->firstOrCreate(
            ['version' => '2026.1'],
            ['title' => 'Charte ZUMRA', 'body' => $body, 'content_hash' => hash('sha256', $body), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()]
        );
        ZumraProgramMembership::query()->create([
            'core_identity_reference' => $reference,
            'status' => ZumraProgramMembership::STATUS_ACTIVE,
            'accepted_charter_id' => $charter->id,
            'accepted_charter_version' => $charter->version,
            'accepted_charter_hash' => $charter->content_hash,
            'charter_accepted_at' => now(),
            'submitted_at' => now(),
            'activated_at' => now(),
        ]);
    }
}
