<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Projects\ProjectAutonomyPathwayService;
use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectMaturityService;
use App\Application\Projects\ProjectService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\Organization;
use App\Models\PortalAdministrator;
use App\Models\Project;
use App\Models\ProjectAccompaniment;
use App\Models\ProjectAutonomyPathway;
use App\Models\ProjectEvent;
use App\Models\Satellite;
use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * REF-001B — le service canonique est ProjectAutonomyPathwayService (renommé depuis
 * ProjectSatelliteLauncherService). Doctrine invariante : PROJET ≠ SATELLITE. Ouvrir un
 * parcours d'autonomie ne crée jamais de satellite logiciel (CAP-048, registre distinct) ni
 * d'Organisation (CAP-066, geste humain distinct) — seulement un marqueur de statut/intention
 * attaché au projet.
 */
final class ProjectAutonomyPathwayTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_potential_structure_or_autonomy_ready_projects_are_eligible(): void
    {
        $this->member('IDN-OWNER');
        $project = $this->project('IDN-OWNER');
        $pathways = app(ProjectAutonomyPathwayService::class);
        $maturity = app(ProjectMaturityService::class);

        self::assertFalse($pathways->isEligible($project));

        $maturity->change($project, 'IDN-OWNER', 'ACTIVITY');
        self::assertFalse($pathways->isEligible($project->refresh()));

        $maturity->change($project->refresh(), 'IDN-OWNER', 'POTENTIAL_STRUCTURE');
        self::assertTrue($pathways->isEligible($project->refresh()));

        $maturity->change($project->refresh(), 'IDN-OWNER', 'AUTONOMY_READY');
        self::assertTrue($pathways->isEligible($project->refresh()));
    }

    public function test_owner_opens_autonomy_pathway_without_creating_satellite_organization_or_changing_control(): void
    {
        $this->member('IDN-OWNER');
        $project = $this->matureProject('IDN-OWNER');

        $pathway = app(ProjectAutonomyPathwayService::class)->open($project, 'IDN-OWNER', [
            'target_form' => 'STARTUP',
        ]);

        self::assertSame(ProjectAutonomyPathway::STATUS_ACTIVE, $pathway->status);
        self::assertSame('STARTUP', $pathway->target_form);
        self::assertSame('IDN-OWNER', $project->refresh()->owner_reference);
        self::assertSame(Project::STATUS_PROPOSED, $project->status);
        self::assertSame('POTENTIAL_STRUCTURE', $project->maturity);
        self::assertSame(0, ProjectAccompaniment::query()->count());
        self::assertSame(1, Satellite::query()->count(), 'ouvrir un parcours d’autonomie ne doit jamais créer de satellite (CAP-048, registre distinct et administratif)');
        self::assertSame(0, Organization::query()->count(), 'ouvrir un parcours d’autonomie ne doit jamais créer d’Organisation (CAP-066, geste humain distinct)');
    }

    public function test_outsider_cannot_open_autonomy_pathway(): void
    {
        $this->member('IDN-OWNER');
        $project = $this->matureProject('IDN-OWNER');

        $this->expectException(HttpException::class);
        app(ProjectAutonomyPathwayService::class)->open($project, 'IDN-OUTSIDER', [
            'target_form' => 'ASSOCIATION',
        ]);
    }

    public function test_immature_project_cannot_open_autonomy_pathway(): void
    {
        $this->member('IDN-OWNER');
        $project = $this->project('IDN-OWNER');

        $this->expectException(HttpException::class);
        app(ProjectAutonomyPathwayService::class)->open($project, 'IDN-OWNER', [
            'target_form' => 'COOPERATIVE',
        ]);
    }

    public function test_other_form_requires_an_explicit_label(): void
    {
        $this->member('IDN-OWNER');
        $project = $this->matureProject('IDN-OWNER');

        $this->expectException(HttpException::class);
        app(ProjectAutonomyPathwayService::class)->open($project, 'IDN-OWNER', [
            'target_form' => 'OTHER',
            'other_form_label' => '',
        ]);
    }

    public function test_pathway_can_close_and_reopen_with_append_only_project_events(): void
    {
        $this->member('IDN-OWNER');
        $project = $this->matureProject('IDN-OWNER');
        $pathways = app(ProjectAutonomyPathwayService::class);

        $pathways->open($project, 'IDN-OWNER', ['target_form' => 'PLATFORM']);
        $pathways->close($project, 'IDN-OWNER');
        $pathways->open($project, 'IDN-OWNER', ['target_form' => 'COMPANY']);

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
        self::assertSame(0, Organization::query()->count());
        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), '/organisations'));

        PortalAdministrator::query()->create(['core_identity_reference' => 'IDN-MATURE']);

        $this->get('/administration/parcours-autonomie')
            ->assertOk()
            ->assertSee('Projet mûr pour autonomie')
            ->assertDontSee('Projet encore en exploration');
    }

    public function test_no_runtime_reference_to_the_deleted_launcher_service_remains(): void
    {
        self::assertFalse(class_exists('App\\Application\\Projects\\ProjectSatelliteLauncherService'), 'REF-001B : ce service historique ne doit plus exister.');
        self::assertTrue(class_exists(ProjectAutonomyPathwayService::class), 'Le service canonique doit être résolu par le container.');
        self::assertInstanceOf(ProjectAutonomyPathwayService::class, app(ProjectAutonomyPathwayService::class));
    }

    private function matureProject(string $identity, array $overrides = []): Project
    {
        $project = $this->project($identity, $overrides);
        app(ProjectMaturityService::class)->change($project, $identity, 'POTENTIAL_STRUCTURE');

        return $project->refresh();
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

    private function project(string $identity, array $overrides = []): Project
    {
        return app(ProjectService::class)->create(
            $identity,
            array_replace([
                'owner_type' => 'PERSON',
                'group_reference' => null,
                'zumra_group_reference' => $this->zumraFor($identity),
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
            (new ProjectConfiguration)->defaults()
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
