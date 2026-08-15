<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectMaturityService;
use App\Application\Projects\ProjectSatelliteLauncherService;
use App\Application\Projects\ProjectService;
use App\Models\PortalAdministrator;
use App\Models\Project;
use App\Models\ProjectAccompaniment;
use App\Models\ProjectAutonomyPathway;
use App\Models\ProjectEvent;
use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class ProjectSatelliteLauncherTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_potential_structure_or_satellite_projects_are_eligible(): void
    {
        $this->member('IDN-OWNER');
        $project = $this->project('IDN-OWNER');
        $launcher = app(ProjectSatelliteLauncherService::class);
        $maturity = app(ProjectMaturityService::class);

        self::assertFalse($launcher->isEligible($project));

        $maturity->change($project, 'IDN-OWNER', 'ACTIVITY');
        self::assertFalse($launcher->isEligible($project->refresh()));

        $maturity->change($project->refresh(), 'IDN-OWNER', 'POTENTIAL_STRUCTURE');
        self::assertTrue($launcher->isEligible($project->refresh()));

        $maturity->change($project->refresh(), 'IDN-OWNER', 'POTENTIAL_SATELLITE');
        self::assertTrue($launcher->isEligible($project->refresh()));
    }

    public function test_owner_opens_autonomy_pathway_without_creating_satellite_or_changing_control(): void
    {
        $this->member('IDN-OWNER');
        $project = $this->matureProject('IDN-OWNER');

        $pathway = app(ProjectSatelliteLauncherService::class)->open($project, 'IDN-OWNER', [
            'target_form' => 'STARTUP',
        ]);

        self::assertSame(ProjectAutonomyPathway::STATUS_ACTIVE, $pathway->status);
        self::assertSame('STARTUP', $pathway->target_form);
        self::assertSame('IDN-OWNER', $project->refresh()->owner_reference);
        self::assertSame(Project::STATUS_PROPOSED, $project->status);
        self::assertSame('POTENTIAL_STRUCTURE', $project->maturity);
        self::assertSame(0, ProjectAccompaniment::query()->count());
        self::assertFalse(Schema::hasTable('dg_satellites'));
        self::assertFalse(Schema::hasTable('dg_organizations'));
    }

    public function test_outsider_cannot_open_autonomy_pathway(): void
    {
        $this->member('IDN-OWNER');
        $project = $this->matureProject('IDN-OWNER');

        $this->expectException(HttpException::class);
        app(ProjectSatelliteLauncherService::class)->open($project, 'IDN-OUTSIDER', [
            'target_form' => 'ASSOCIATION',
        ]);
    }

    public function test_immature_project_cannot_open_autonomy_pathway(): void
    {
        $this->member('IDN-OWNER');
        $project = $this->project('IDN-OWNER');

        $this->expectException(HttpException::class);
        app(ProjectSatelliteLauncherService::class)->open($project, 'IDN-OWNER', [
            'target_form' => 'COOPERATIVE',
        ]);
    }

    public function test_other_form_requires_an_explicit_label(): void
    {
        $this->member('IDN-OWNER');
        $project = $this->matureProject('IDN-OWNER');

        $this->expectException(HttpException::class);
        app(ProjectSatelliteLauncherService::class)->open($project, 'IDN-OWNER', [
            'target_form' => 'OTHER',
            'other_form_label' => '',
        ]);
    }

    public function test_pathway_can_close_and_reopen_with_append_only_project_events(): void
    {
        $this->member('IDN-OWNER');
        $project = $this->matureProject('IDN-OWNER');
        $launcher = app(ProjectSatelliteLauncherService::class);

        $launcher->open($project, 'IDN-OWNER', ['target_form' => 'PLATFORM']);
        $launcher->close($project, 'IDN-OWNER');
        $launcher->open($project, 'IDN-OWNER', ['target_form' => 'COMPANY']);

        self::assertSame(ProjectAutonomyPathway::STATUS_ACTIVE, $project->autonomyPathway()->sole()->status);
        self::assertSame('COMPANY', $project->autonomyPathway()->sole()->target_form);
        self::assertSame(2, ProjectEvent::query()->where('event', 'AUTONOMY_PATHWAY_OPENED')->count());
        self::assertSame(1, ProjectEvent::query()->where('event', 'AUTONOMY_PATHWAY_CLOSED')->count());
    }

    public function test_member_route_and_admin_queue_expose_preparation_without_core_organization_creation(): void
    {
        $this->member('IDN-MATURE');
        $this->member('IDN-IMMATURE');
        $mature = $this->matureProject('IDN-MATURE', ['name' => 'Projet mûr pour autonomie']);
        $this->project('IDN-IMMATURE', ['name' => 'Projet encore en exploration']);

        $this->signIn('IDN-MATURE');
        $this->post('/projets/'.$mature->public_reference.'/autonomie', [
            'target_form' => 'COOPERATIVE',
        ])->assertRedirect();

        self::assertSame(1, ProjectAutonomyPathway::query()->count());
        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), '/organisations'));

        PortalAdministrator::query()->create(['core_identity_reference' => 'IDN-ADMIN']);
        $this->signIn('IDN-ADMIN');

        $this->get('/administration/lanceur-satellites')
            ->assertOk()
            ->assertSee('Projet mûr pour autonomie')
            ->assertDontSee('Projet encore en exploration');
    }

    private function matureProject(string $identity, array $overrides = []): Project
    {
        $project = $this->project($identity, $overrides);
        app(ProjectMaturityService::class)->change($project, $identity, 'POTENTIAL_STRUCTURE');

        return $project->refresh();
    }

    private function project(string $identity, array $overrides = []): Project
    {
        return app(ProjectService::class)->create(
            $identity,
            array_replace([
                'owner_type' => 'PERSON',
                'group_reference' => null,
                'source_need_reference' => null,
                'name' => 'Atelier numérique communautaire',
                'summary' => 'Créer un espace pratique où des jeunes peuvent apprendre ensemble et produire des services numériques utiles.',
                'problem' => 'Des jeunes motivés disposent de peu de cadres pratiques pour apprendre, expérimenter et transformer leurs acquis en activités utiles.',
                'proposed_solution' => 'Mettre en place un atelier progressif avec transmission entre pairs, exercices réels et accompagnement vers des premiers services.',
                'beneficiaries' => 'Jeunes débutants et personnes en reconversion dans la commune.',
                'domain' => 'DIGITAL',
                'participation_mode' => 'HYBRID',
                'location' => 'Abidjan',
                'objectives' => "Former une première équipe\nProduire trois services pilotes",
                'required_capabilities' => "Formation numérique\nGestion de projet",
                'required_resources' => "Ordinateurs\nConnexion internet",
                'risks' => "Disponibilité irrégulière\nAccès au matériel",
                'milestones' => "Constituer l’équipe\nPréparer le lieu\nLancer le pilote",
                'property_regime' => 'PERSONAL_SUPPORTED',
                'visibility' => 'PUBLIC',
            ], $overrides),
            (new ProjectConfiguration())->defaults()
        );
    }

    private function member(string $identity): void
    {
        $body = str_repeat('Respect et transmission. ', 5);
        $charter = ZumraCharter::query()->firstOrCreate(
            ['version' => '2026.1'],
            [
                'title' => 'Charte ZUMRA',
                'body' => $body,
                'content_hash' => hash('sha256', $body),
                'status' => ZumraCharter::STATUS_PUBLISHED,
                'published_at' => now(),
            ]
        );

        ZumraProgramMembership::query()->create([
            'core_identity_reference' => $identity,
            'status' => ZumraProgramMembership::STATUS_ACTIVE,
            'accepted_charter_id' => $charter->id,
            'accepted_charter_version' => $charter->version,
            'accepted_charter_hash' => $charter->content_hash,
            'charter_accepted_at' => now(),
            'submitted_at' => now(),
            'activated_at' => now(),
        ]);
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response([
                'jeton' => 'bearer-'.$reference,
                'entite' => $reference,
                'assurance' => 'AS1',
                'expire_le' => '2026-08-16T23:59:00+00:00',
            ], 201),
            'core.test/api/v1/identites/*' => Http::response([
                'reference' => $reference,
                'type' => 'personne',
                'libelle' => 'Membre DG Afrique',
                'etat' => 'ACTIF',
                'source' => 'CORE',
                'regime' => 'INSCRIT_AU_REGISTRE',
            ]),
            'core.test/api/v1/sessions/current' => Http::response([
                'entite' => $reference,
                'assurance' => 'AS1',
                'expire_le' => '2026-08-16T23:59:00+00:00',
            ]),
        ]);

        $this->post('/connexion', [
            'identifier' => $reference,
            'secret' => 'secret',
        ])->assertRedirect('/espace');
    }
}
