<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectMaturityService;
use App\Application\Projects\ProjectService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectAccompaniment;
use App\Models\ProjectEvent;
use App\Models\Satellite;
use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class ProjectMaturityTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_repositions_maturity_without_changing_status_or_control(): void
    {
        $this->member('IDN-OWNER');
        $project = $this->project('IDN-OWNER');

        app(ProjectMaturityService::class)->change($project, 'IDN-OWNER', 'EXPERIMENT', 'Un premier prototype a été testé.');

        self::assertSame('EXPERIMENT', $project->refresh()->maturity);
        self::assertSame(Project::STATUS_PROPOSED, $project->status);
        self::assertSame('IDN-OWNER', $project->owner_reference);

        $event = ProjectEvent::query()->where('event', 'PROJECT_MATURITY_CHANGED')->sole();
        self::assertSame('IDEA', $event->context['from']);
        self::assertSame('EXPERIMENT', $event->context['to']);
        self::assertSame('Un premier prototype a été testé.', $event->context['note']);
    }

    public function test_maturity_can_move_backward_and_keeps_append_only_events(): void
    {
        $this->member('IDN-OWNER');
        $project = $this->project('IDN-OWNER');
        $service = app(ProjectMaturityService::class);

        $service->change($project, 'IDN-OWNER', 'TEAM');
        $service->change($project->refresh(), 'IDN-OWNER', 'EXPLORATION', 'L’équipe doit être recomposée.');

        self::assertSame('EXPLORATION', $project->refresh()->maturity);
        self::assertSame(2, ProjectEvent::query()->where('event', 'PROJECT_MATURITY_CHANGED')->count());
        self::assertTrue(ProjectEvent::query()->where('event', 'PROJECT_MATURITY_CHANGED')->where('context->to', 'TEAM')->exists());
        self::assertTrue(ProjectEvent::query()->where('event', 'PROJECT_MATURITY_CHANGED')->where('context->to', 'EXPLORATION')->exists());
    }

    public function test_outsider_cannot_decree_project_maturity(): void
    {
        $this->member('IDN-OWNER');
        $project = $this->project('IDN-OWNER');

        $this->expectException(HttpException::class);
        app(ProjectMaturityService::class)->change($project, 'IDN-OUTSIDER', 'ACTIVITY');
    }

    public function test_project_status_transition_does_not_change_maturity(): void
    {
        $this->member('IDN-OWNER');
        $project = $this->project('IDN-OWNER');

        app(ProjectMaturityService::class)->change($project, 'IDN-OWNER', 'TEAM');
        app(ProjectService::class)->transition($project->refresh(), 'IDN-OWNER', Project::STATUS_IN_PROGRESS);

        self::assertSame(Project::STATUS_IN_PROGRESS, $project->refresh()->status);
        self::assertSame('TEAM', $project->maturity);
    }

    public function test_autonomy_ready_is_only_a_repere_without_side_effects(): void
    {
        $this->member('IDN-OWNER');
        $project = $this->project('IDN-OWNER');

        app(ProjectMaturityService::class)->change($project, 'IDN-OWNER', 'AUTONOMY_READY');

        self::assertSame('AUTONOMY_READY', $project->refresh()->maturity);
        self::assertSame(Project::STATUS_PROPOSED, $project->status);
        self::assertSame('IDN-OWNER', $project->owner_reference);
        self::assertSame(0, ProjectAccompaniment::query()->count());
        self::assertSame(1, Satellite::query()->count(), 'atteindre ce repère ne doit jamais créer de satellite (CAP-048, registre distinct et administratif)');
        self::assertSame(0, Organization::query()->count(), 'atteindre ce repère ne doit jamais créer d’Organisation (CAP-066, geste humain distinct)');
        self::assertFalse(Schema::hasTable('dg_project_funding'));
    }

    public function test_member_route_updates_only_the_maturity_repere(): void
    {
        $this->member('IDN-OWNER');
        $project = $this->project('IDN-OWNER');
        $this->signIn('IDN-OWNER');

        $this->put('/projets/'.$project->public_reference.'/maturite', [
            'maturity' => 'STRUCTURED',
            'note' => 'Le dossier, les objectifs et le chemin d’action sont désormais structurés.',
        ])->assertRedirect();

        self::assertSame('STRUCTURED', $project->refresh()->maturity);
        self::assertSame(Project::STATUS_PROPOSED, $project->status);
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

    private function project(string $identity): Project
    {
        return app(ProjectService::class)->create(
            $identity,
            [
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
            ],
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

        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
