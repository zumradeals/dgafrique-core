<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Needs\NeedConfiguration;
use App\Application\Needs\NeedService;
use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\Need;
use App\Models\Project;
use App\Models\ProjectTeamMember;
use App\Models\ZumraCharter;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Couvre CAP-042 (besoin projet) : Need::OWNER_PROJECT réutilise strictement ProjectAuthority
 * (extraite de ProjectService sans changement de comportement), jamais une nouvelle autorité.
 */
final class ProjectNeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_project_owner_publishes_a_need_directly(): void
    {
        $this->member('IDN-OWNER');
        $project = $this->project('IDN-OWNER');
        $need = app(NeedService::class)->create('IDN-OWNER', $this->payload(['owner_type' => Need::OWNER_PROJECT, 'project_reference' => $project->public_reference]), (new NeedConfiguration())->defaults());

        self::assertSame(Need::STATUS_OPEN, $need->status);
        self::assertSame(Need::OWNER_PROJECT, $need->owner_type);
        self::assertSame($project->id, $need->owner_reference);
    }

    public function test_an_active_team_member_proposes_but_only_the_owner_publishes(): void
    {
        $this->member('IDN-OWNER');
        $this->member('IDN-MEMBER');
        $project = $this->project('IDN-OWNER');
        ProjectTeamMember::query()->create(['project_id' => $project->id, 'core_identity_reference' => 'IDN-MEMBER', 'status' => ProjectTeamMember::STATUS_ACTIVE, 'entry_mode' => ProjectTeamMember::ENTRY_MODE_REQUEST, 'initiated_by_core_reference' => 'IDN-MEMBER', 'joined_at' => now()]);
        $needs = app(NeedService::class);
        $need = $needs->create('IDN-MEMBER', $this->payload(['owner_type' => Need::OWNER_PROJECT, 'project_reference' => $project->public_reference, 'visibility' => Need::VISIBILITY_PUBLIC]), (new NeedConfiguration())->defaults());

        self::assertSame(Need::STATUS_PROPOSED, $need->status);
        self::assertFalse($needs->canView($need, 'IDN-OUTSIDER'));
        $needs->transition($need, 'IDN-OWNER', Need::STATUS_OPEN);
        self::assertSame(Need::STATUS_OPEN, $need->refresh()->status);
        self::assertTrue($needs->canView($need, 'IDN-OUTSIDER'));
    }

    public function test_someone_outside_the_project_cannot_propose_a_need_for_it(): void
    {
        $this->member('IDN-OWNER');
        $this->member('IDN-OUTSIDER');
        $project = $this->project('IDN-OWNER');

        $this->expectException(HttpException::class);
        app(NeedService::class)->create('IDN-OUTSIDER', $this->payload(['owner_type' => Need::OWNER_PROJECT, 'project_reference' => $project->public_reference]), (new NeedConfiguration())->defaults());
    }

    public function test_a_group_owned_project_need_is_decided_by_the_group_leader(): void
    {
        $this->member('IDN-LEADER');
        $this->member('IDN-MEMBER');
        $group = app(ZumraGroupService::class)->create('IDN-LEADER', ['name' => 'ZUMRA CAP-042', 'domain' => 'Formation', 'founding_objective' => 'Réunir des personnes pour apprendre et transmettre des capacités utiles au développement.', 'participation_mode' => 'HYBRID', 'internal_charter' => 'Respect, dignité, transmission, hiérarchie responsable et décisions conformes à la charte commune.', 'assume_primary_lead' => true]);
        ZumraGroupMembership::query()->create(['zumra_group_id' => $group->id, 'core_identity_reference' => 'IDN-MEMBER', 'status' => ZumraGroupMembership::STATUS_ACTIVE, 'entry_mode' => 'REQUEST', 'initiated_by_core_reference' => 'IDN-MEMBER', 'joined_at' => now()]);
        $project = app(ProjectService::class)->create('IDN-MEMBER', $this->projectPayload(['owner_type' => 'GROUP', 'group_reference' => $group->public_reference, 'property_regime' => 'ZUMRA_COLLECTIVE']), (new ProjectConfiguration())->defaults());
        $needs = app(NeedService::class);

        $need = $needs->create('IDN-MEMBER', $this->payload(['owner_type' => Need::OWNER_PROJECT, 'project_reference' => $project->public_reference]), (new NeedConfiguration())->defaults());
        self::assertSame(Need::STATUS_PROPOSED, $need->status, 'un membre actif du groupe propriétaire n’est pas lui-même décideur du projet');
        self::assertFalse($needs->canDecide($need, 'IDN-MEMBER'));
        self::assertTrue($needs->canDecide($need, 'IDN-LEADER'));
        $needs->transition($need, 'IDN-LEADER', Need::STATUS_OPEN);
        self::assertSame(Need::STATUS_OPEN, $need->refresh()->status);
    }

    public function test_group_visibility_is_refused_for_a_project_need_and_folded_to_private(): void
    {
        $this->member('IDN-OWNER');
        $project = $this->project('IDN-OWNER');
        $need = app(NeedService::class)->create('IDN-OWNER', $this->payload(['owner_type' => Need::OWNER_PROJECT, 'project_reference' => $project->public_reference, 'visibility' => Need::VISIBILITY_GROUP]), (new NeedConfiguration())->defaults());

        self::assertSame(Need::VISIBILITY_PRIVATE, $need->visibility);
    }

    private function project(string $owner, array $overrides = []): Project
    {
        return app(ProjectService::class)->create($owner, $this->projectPayload($overrides), (new ProjectConfiguration())->defaults());
    }

    private function projectPayload(array $overrides = []): array
    {
        return array_replace([
            'owner_type' => 'PERSON', 'group_reference' => null, 'source_need_reference' => null,
            'name' => 'Atelier numérique communautaire', 'summary' => 'Créer un espace pratique où des jeunes peuvent apprendre ensemble et produire des services numériques utiles.',
            'problem' => 'Des jeunes motivés disposent de peu de cadres pratiques pour apprendre, expérimenter et transformer leurs acquis en activités utiles.',
            'proposed_solution' => 'Mettre en place un atelier progressif avec transmission entre pairs, exercices réels et accompagnement vers des premiers services.',
            'beneficiaries' => 'Jeunes débutants et personnes en reconversion dans la commune.', 'domain' => 'DIGITAL', 'participation_mode' => 'HYBRID', 'location' => 'Abidjan',
            'objectives' => "Former une première équipe\nProduire trois services pilotes", 'required_capabilities' => "Formation numérique\nGestion de projet",
            'required_resources' => "Ordinateurs\nConnexion internet", 'risks' => "Disponibilité irrégulière\nAccès au matériel",
            'milestones' => "Constituer l’équipe\nPréparer le lieu\nLancer le pilote", 'property_regime' => 'PERSONAL_SUPPORTED', 'visibility' => 'PUBLIC',
        ], $overrides);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace(['owner_type' => Need::OWNER_PERSON, 'group_reference' => null, 'project_reference' => null, 'title' => 'Former notre équipe à la comptabilité', 'context' => 'Notre équipe tient ses activités mais ne maîtrise pas encore le suivi simple des recettes et dépenses mensuelles.', 'category' => 'TRAINING', 'capability_label' => 'Comptabilité', 'collaboration_mode' => 'HYBRID', 'location' => 'Abidjan', 'visibility' => Need::VISIBILITY_PUBLIC], $overrides);
    }

    private function member(string $id): void
    {
        $body = str_repeat('Respect et transmission. ', 5);
        $charter = ZumraCharter::query()->firstOrCreate(['version' => '2026.1'], ['title' => 'Charte ZUMRA', 'body' => $body, 'content_hash' => hash('sha256', $body), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()]);
        ZumraProgramMembership::query()->create(['core_identity_reference' => $id, 'status' => ZumraProgramMembership::STATUS_ACTIVE, 'accepted_charter_id' => $charter->id, 'accepted_charter_version' => $charter->version, 'accepted_charter_hash' => $charter->content_hash, 'charter_accepted_at' => now(), 'submitted_at' => now(), 'activated_at' => now()]);
    }
}
