<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Projects\ProjectService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * BETA-READY-004 (LOT 3) — clôture d'un jalon Projet. Réutilise strictement
 * ProjectService::canDecide() (donc ProjectAuthority::canDecide()) : aucune autorité créée pour
 * ce geste, aucun nouveau workflow.
 */
final class ProjectMilestoneCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_project_authority_can_complete_a_milestone(): void
    {
        $project = $this->personProject('IDN-OWNER');
        $milestone = $project->milestones()->create(['title' => 'Étude de faisabilité', 'position' => 1]);

        app(ProjectService::class)->completeMilestone($project, $milestone, 'IDN-OWNER');

        self::assertSame(ProjectMilestone::STATUS_COMPLETED, $milestone->fresh()->status);
    }

    public function test_completing_a_milestone_records_completed_at(): void
    {
        $project = $this->personProject('IDN-OWNER');
        $milestone = $project->milestones()->create(['title' => 'Étude de faisabilité', 'position' => 1]);
        self::assertNull($milestone->completed_at);

        app(ProjectService::class)->completeMilestone($project, $milestone, 'IDN-OWNER');

        $fresh = $milestone->fresh();
        self::assertNotNull($fresh->completed_at);
        self::assertTrue($fresh->completed_at->diffInSeconds(now()) < 5);
    }

    public function test_a_non_authorized_member_cannot_complete_the_milestone(): void
    {
        $project = $this->personProject('IDN-OWNER');
        $milestone = $project->milestones()->create(['title' => 'Étude de faisabilité', 'position' => 1]);

        try {
            app(ProjectService::class)->completeMilestone($project, $milestone, 'IDN-OUTSIDER');
            self::fail('Une identité sans autorité sur le Projet ne doit jamais pouvoir clôturer un jalon.');
        } catch (HttpException $exception) {
            self::assertSame(403, $exception->getStatusCode());
        }

        self::assertSame(ProjectMilestone::STATUS_PLANNED, $milestone->fresh()->status);
    }

    public function test_completing_an_already_completed_milestone_is_rejected(): void
    {
        $project = $this->personProject('IDN-OWNER');
        $milestone = $project->milestones()->create(['title' => 'Étude de faisabilité', 'position' => 1, 'status' => ProjectMilestone::STATUS_COMPLETED, 'completed_at' => now()->subDay()]);

        try {
            app(ProjectService::class)->completeMilestone($project, $milestone, 'IDN-OWNER');
            self::fail('Un jalon déjà accompli ne doit pas être re-clôturé.');
        } catch (HttpException $exception) {
            self::assertSame(409, $exception->getStatusCode());
        }
    }

    public function test_the_project_page_exposes_the_completion_action_to_the_authority(): void
    {
        $project = $this->personProject('IDN-OWNER');
        $project->milestones()->create(['title' => 'Étude de faisabilité', 'position' => 1]);
        $this->programMember('IDN-OWNER');

        $this->signIn('IDN-OWNER');
        $response = $this->get(route('projects.show', $project))->assertOk();
        $response->assertSee('Marquer accompli');
    }

    public function test_the_project_page_hides_the_completion_action_from_an_outsider(): void
    {
        $project = $this->personProject('IDN-OWNER');
        $project->milestones()->create(['title' => 'Étude de faisabilité', 'position' => 1]);
        $this->programMember('IDN-OUTSIDER');

        $this->signIn('IDN-OUTSIDER');
        $response = $this->get(route('projects.show', $project))->assertOk();
        $response->assertDontSee('Marquer accompli');
    }

    public function test_the_http_route_rejects_an_outsider(): void
    {
        $project = $this->personProject('IDN-OWNER');
        $milestone = $project->milestones()->create(['title' => 'Étude de faisabilité', 'position' => 1]);
        $this->programMember('IDN-OUTSIDER');

        $this->signIn('IDN-OUTSIDER');
        $this->put(route('projects.milestones.complete', [$project, $milestone]))->assertForbidden();
        self::assertSame(ProjectMilestone::STATUS_PLANNED, $milestone->fresh()->status);
    }

    public function test_the_http_route_completes_the_milestone_for_the_authority(): void
    {
        $project = $this->personProject('IDN-OWNER');
        $milestone = $project->milestones()->create(['title' => 'Étude de faisabilité', 'position' => 1]);
        $this->programMember('IDN-OWNER');

        $this->signIn('IDN-OWNER');
        $this->put(route('projects.milestones.complete', [$project, $milestone]))->assertRedirect();
        self::assertSame(ProjectMilestone::STATUS_COMPLETED, $milestone->fresh()->status);
    }

    private function personProject(string $owner): Project
    {
        $group = $this->group($owner);

        return Project::query()->create([
            'public_reference' => (string) Str::uuid(), 'owner_type' => Project::OWNER_PERSON,
            'owner_reference' => $owner, 'zumra_group_id' => $group->id, 'initiator_core_reference' => $owner,
            'name' => 'Atelier numérique communautaire', 'summary' => 'Un projet concret destiné à construire des capacités utiles.',
            'problem' => 'Des personnes motivées manquent de cadre pratique.', 'proposed_solution' => 'Créer un atelier progressif.',
            'beneficiaries' => 'Jeunes débutants.', 'domain' => 'DIGITAL', 'participation_mode' => 'HYBRID',
            'objectives' => ['Former une équipe'], 'required_capabilities' => ['Formation numérique'],
            'required_resources' => ['Ordinateurs'], 'risks' => [], 'property_regime' => 'PERSONAL_SUPPORTED',
            'visibility' => Project::VISIBILITY_PUBLIC, 'status' => Project::STATUS_ADOPTED, 'maturity' => 'IDEA',
            'decided_by_core_reference' => $owner, 'adopted_at' => now(),
        ]);
    }

    private function group(string $founder): ZumraGroup
    {
        $this->programMember($founder);

        return app(ZumraGroupService::class)->create($founder, [
            'name' => 'ZUMRA jalons '.Str::random(6), 'domain' => 'Numérique',
            'founding_objective' => 'Former une équipe qui transmet les outils numériques et réalise des solutions utiles.',
            'participation_mode' => 'HYBRID', 'internal_charter' => 'Chaque membre respecte la dignité et la hiérarchie.',
            'assume_primary_lead' => true,
        ], 3);
    }

    private function programMember(string $reference): void
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
