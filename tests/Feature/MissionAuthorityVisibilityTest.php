<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Missions\MissionContextRegistry;
use App\Application\Missions\MissionVisibilityService;
use App\Application\Missions\MissionWorkflow;
use App\Application\Needs\NeedConfiguration;
use App\Application\Needs\NeedService;
use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\Mission;
use App\Models\Need;
use App\Models\Project;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Couvre CAP-069 §21 : autorité par contexte (réutilisée, jamais réinventée), registre
 * fail-closed, plafonds de visibilité, contextes suspendus/archivés.
 */
final class MissionAuthorityVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_context_type_fails_closed(): void
    {
        $this->assertAborts(422, fn () => app(MissionContextRegistry::class)->for('OPPORTUNITY'));
        $this->assertAborts(422, fn () => app(MissionContextRegistry::class)->for('EVENT'));
        self::assertSame(['PROJECT', 'ZUMRA', 'NEED'], app(MissionContextRegistry::class)->types());
    }

    public function test_zumra_proposal_requires_active_membership_and_operational_group(): void
    {
        $this->activateProgram('IDN-MEMBER');
        $group = $this->group('IDN-LEADER');
        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id, 'core_identity_reference' => 'IDN-MEMBER',
            'status' => ZumraGroupMembership::STATUS_ACTIVE, 'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => 'IDN-MEMBER', 'joined_at' => now(),
        ]);

        $workflow = app(MissionWorkflow::class);
        $mission = $workflow->create('IDN-MEMBER', 'ZUMRA', $group->public_reference, [
            'title' => 'Organiser la session mensuelle',
            'description' => 'Préparer et animer la session mensuelle de la ZUMRA.',
        ]);
        self::assertSame(Mission::STATUS_DRAFT, $mission->status);

        // Un simple visiteur (jamais membre actif) ne peut pas proposer.
        $this->assertAborts(403, fn () => $workflow->create('IDN-OUTSIDER', 'ZUMRA', $group->public_reference, [
            'title' => 'Autre mission', 'description' => 'Description suffisamment longue pour être valide.',
        ]));

        $group->update(['state' => ZumraGroup::STATE_SUSPENDED]);
        $this->assertAborts(403, fn () => $workflow->create('IDN-MEMBER', 'ZUMRA', $group->public_reference, [
            'title' => 'Pendant suspension', 'description' => 'Une ZUMRA suspendue ne peut pas recevoir de nouvelle proposition.',
        ]));
    }

    public function test_zumra_officialization_requires_leader_not_a_simple_member(): void
    {
        $this->activateProgram('IDN-MEMBER');
        $group = $this->group('IDN-LEADER');
        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id, 'core_identity_reference' => 'IDN-MEMBER',
            'status' => ZumraGroupMembership::STATUS_ACTIVE, 'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => 'IDN-MEMBER', 'joined_at' => now(),
        ]);
        $workflow = app(MissionWorkflow::class);
        $mission = $workflow->create('IDN-MEMBER', 'ZUMRA', $group->public_reference, [
            'title' => 'Organiser la session mensuelle',
            'description' => 'Préparer et animer la session mensuelle de la ZUMRA.',
        ]);
        $mission = $workflow->propose($mission, 'IDN-MEMBER');

        $this->assertAborts(403, fn () => $workflow->officialize($mission, 'IDN-MEMBER', ['expected_result' => 'Une session tenue.']));

        $mission = $workflow->officialize($mission, 'IDN-LEADER', ['expected_result' => 'Une session tenue et documentée.']);
        self::assertSame(Mission::STATUS_OPEN, $mission->status);
    }

    public function test_need_context_reuses_need_authority_and_respects_archival(): void
    {
        $this->activateProgram('IDN-OWNER');
        $need = app(NeedService::class)->create('IDN-OWNER', $this->needPayload(), (new NeedConfiguration)->defaults());

        $workflow = app(MissionWorkflow::class);
        $mission = $workflow->create('IDN-OWNER', 'NEED', $need->public_reference, [
            'title' => 'Trouver la salle', 'description' => 'Identifier une salle disponible pour la formation.',
        ]);
        $mission = $workflow->propose($mission, 'IDN-OWNER');
        $mission = $workflow->officialize($mission, 'IDN-OWNER', ['expected_result' => 'Une salle réservée.']);
        self::assertSame(Mission::STATUS_OPEN, $mission->status);

        $need->update(['status' => Need::STATUS_ARCHIVED]);
        $this->assertAborts(403, fn () => $workflow->create('IDN-OWNER', 'NEED', $need->public_reference, [
            'title' => 'Après archivage', 'description' => 'Une deuxième Mission après archivage du besoin.',
        ]));
    }

    public function test_archived_project_stops_new_mission_proposals(): void
    {
        $this->activateProgram('IDN-OWNER');
        $project = app(ProjectService::class)->create('IDN-OWNER', $this->projectPayload(), (new ProjectConfiguration)->defaults());

        $workflow = app(MissionWorkflow::class);
        $mission = $workflow->create('IDN-OWNER', 'PROJECT', $project->public_reference, [
            'title' => 'Avant archivage', 'description' => 'Une Mission créée avant que le projet ne soit archivé.',
        ]);
        self::assertSame(Mission::STATUS_DRAFT, $mission->status);

        $project->update(['status' => Project::STATUS_ARCHIVED]);
        $this->assertAborts(403, fn () => $workflow->create('IDN-OWNER', 'PROJECT', $project->public_reference, [
            'title' => 'Après archivage', 'description' => 'Une deuxième Mission après archivage du projet.',
        ]));
    }

    public function test_visibility_never_exceeds_context_ceiling(): void
    {
        $this->activateProgram('IDN-OWNER');
        $project = app(ProjectService::class)->create('IDN-OWNER', $this->projectPayload(['visibility' => 'PRIVATE']), (new ProjectConfiguration)->defaults());

        $workflow = app(MissionWorkflow::class);
        $this->assertAborts(422, fn () => $workflow->create('IDN-OWNER', 'PROJECT', $project->public_reference, [
            'title' => 'Trop visible', 'description' => 'Cette Mission demande une visibilité publique dans un projet privé.',
            'visibility' => Mission::VISIBILITY_PUBLIC,
        ]));

        $mission = $workflow->create('IDN-OWNER', 'PROJECT', $project->public_reference, [
            'title' => 'Visibilité correcte', 'description' => 'Cette Mission reste au plafond du projet.',
            'visibility' => Mission::VISIBILITY_PRIVATE,
        ]);
        self::assertSame(Mission::VISIBILITY_PRIVATE, $mission->visibility);
    }

    public function test_private_mission_is_invisible_to_a_non_participant(): void
    {
        $this->activateProgram('IDN-OWNER');
        $project = app(ProjectService::class)->create('IDN-OWNER', $this->projectPayload(['visibility' => 'PUBLIC']), (new ProjectConfiguration)->defaults());

        $workflow = app(MissionWorkflow::class);
        $mission = $workflow->create('IDN-OWNER', 'PROJECT', $project->public_reference, [
            'title' => 'Mission privée', 'description' => 'Une Mission volontairement privée dans un projet public.',
            'visibility' => Mission::VISIBILITY_PRIVATE,
        ]);

        $visibility = app(MissionVisibilityService::class);
        self::assertTrue($visibility->canViewMission($mission, 'IDN-OWNER'));
        self::assertFalse($visibility->canViewMission($mission, 'IDN-STRANGER'));
    }

    private function assertAborts(int $status, callable $fn): void
    {
        try {
            $fn();
            self::fail("Expected an HttpException with status {$status} but none was thrown.");
        } catch (HttpException $e) {
            self::assertSame($status, $e->getStatusCode());
        }
    }

    private function needPayload(array $overrides = []): array
    {
        return array_replace([
            'owner_type' => Need::OWNER_PERSON, 'group_reference' => null,
            'title' => 'Former notre équipe à la comptabilité',
            'context' => 'Notre équipe tient ses activités mais ne maîtrise pas encore le suivi simple des recettes et dépenses mensuelles.',
            'category' => 'TRAINING', 'capability_label' => 'Comptabilité', 'collaboration_mode' => 'HYBRID',
            'location' => 'Abidjan', 'visibility' => Need::VISIBILITY_PUBLIC,
        ], $overrides);
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

    private function group(string $leader): ZumraGroup
    {
        return app(ZumraGroupService::class)->create($leader, [
            'name' => 'ZUMRA CAP 069', 'domain' => 'Formation',
            'founding_objective' => 'Réunir des personnes pour apprendre et transmettre des capacités utiles au développement.',
            'participation_mode' => 'HYBRID',
            'internal_charter' => 'Respect, dignité, transmission, hiérarchie responsable et décisions conformes à la charte commune.',
            'assume_primary_lead' => true,
        ]);
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
