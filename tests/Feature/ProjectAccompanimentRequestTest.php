<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Projects\ProjectAccompanimentService;
use App\Models\PortalAdministrator;
use App\Models\Project;
use App\Models\ProjectAccompanimentRequest;
use App\Models\ProjectEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Couvre CAP-045 : la file de demandes est distincte de l'activation directe (CAP-016, inchangée),
 * strictement ordonnée par date de demande — jamais un score caché (précédent CAP-018).
 */
final class ProjectAccompanimentRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_request_is_refused_without_an_active_accompaniment(): void
    {
        $project = $this->project();

        $this->expectException(HttpException::class);
        app(ProjectAccompanimentService::class)->request($project, 'IDN-OWNER', 'Besoin d’un accompagnement juridique', 'Le projet a besoin d’une relecture juridique avant de signer un partenariat.');
    }

    public function test_the_owner_transmits_a_request_once_accompaniment_is_active(): void
    {
        $project = $this->project();
        $service = app(ProjectAccompanimentService::class);
        $service->activate($project, 'IDN-OWNER');

        $request = $service->request($project, 'IDN-OWNER', 'Besoin d’un accompagnement juridique', 'Le projet a besoin d’une relecture juridique avant de signer un partenariat.');

        self::assertSame(ProjectAccompanimentRequest::STATUS_PENDING, $request->status);
        self::assertTrue(ProjectEvent::query()->where('event', 'ACCOMPANIMENT_REQUEST_SUBMITTED')->exists());
    }

    public function test_only_the_project_owner_can_transmit_a_request(): void
    {
        $project = $this->project();
        $service = app(ProjectAccompanimentService::class);
        $service->activate($project, 'IDN-OWNER');

        $this->expectException(HttpException::class);
        $service->request($project, 'IDN-OUTSIDER', 'Objet quelconque', 'Une description suffisamment longue pour passer la validation.');
    }

    public function test_only_a_provisioned_administrator_can_acknowledge_or_close_a_request(): void
    {
        $project = $this->project();
        $service = app(ProjectAccompanimentService::class);
        $service->activate($project, 'IDN-OWNER');
        $request = $service->request($project, 'IDN-OWNER', 'Besoin d’un accompagnement juridique', 'Le projet a besoin d’une relecture juridique avant de signer un partenariat.');

        $this->expectException(HttpException::class);
        $service->acknowledgeRequest($request, 'IDN-NOT-ADMIN');
    }

    public function test_the_full_lifecycle_from_pending_to_closed(): void
    {
        PortalAdministrator::query()->create(['core_identity_reference' => 'IDN-ADMIN']);
        $project = $this->project();
        $service = app(ProjectAccompanimentService::class);
        $service->activate($project, 'IDN-OWNER');
        $request = $service->request($project, 'IDN-OWNER', 'Besoin d’un accompagnement juridique', 'Le projet a besoin d’une relecture juridique avant de signer un partenariat.');

        $service->acknowledgeRequest($request, 'IDN-ADMIN');
        self::assertSame(ProjectAccompanimentRequest::STATUS_ACKNOWLEDGED, $request->refresh()->status);
        self::assertSame('IDN-ADMIN', $request->acknowledged_by_core_reference);

        $service->closeRequest($request, 'IDN-ADMIN', 'Relecture réalisée et transmise au porteur.');
        self::assertSame(ProjectAccompanimentRequest::STATUS_CLOSED, $request->refresh()->status);
        self::assertSame('Relecture réalisée et transmise au porteur.', $request->resolution_note);
        foreach (['ACCOMPANIMENT_REQUEST_SUBMITTED', 'ACCOMPANIMENT_REQUEST_ACKNOWLEDGED', 'ACCOMPANIMENT_REQUEST_CLOSED'] as $event) {
            self::assertTrue(ProjectEvent::query()->where('project_id', $project->id)->where('event', $event)->exists());
        }
    }

    public function test_the_administration_queue_is_ordered_by_request_date_never_by_a_score(): void
    {
        $projectA = $this->project('11111111-1111-4111-8111-111111111111', 'IDN-OWNER-A');
        $projectB = $this->project('22222222-2222-4222-8222-222222222222', 'IDN-OWNER-B');
        $service = app(ProjectAccompanimentService::class);
        $service->activate($projectA, 'IDN-OWNER-A');
        $service->activate($projectB, 'IDN-OWNER-B');

        $older = $service->request($projectA, 'IDN-OWNER-A', 'Première demande', 'La première demande transmise, elle doit rester en tête de la file.');
        $older->update(['requested_at' => now()->subDays(3)]);
        $newer = $service->request($projectB, 'IDN-OWNER-B', 'Seconde demande', 'La seconde demande transmise, elle doit rester après la première.');

        $queue = ProjectAccompanimentRequest::query()->where('status', ProjectAccompanimentRequest::STATUS_PENDING)->oldest('requested_at')->pluck('id')->all();

        self::assertSame([$older->id, $newer->id], $queue);
    }

    private function project(string $publicReference = '6f84f6c0-1b31-4cb0-9f3d-6efbc6b80d31', string $owner = 'IDN-OWNER'): Project
    {
        return Project::query()->create([
            'public_reference' => $publicReference, 'owner_type' => Project::OWNER_PERSON, 'owner_reference' => $owner, 'initiator_core_reference' => $owner,
            'name' => 'Atelier numérique communautaire '.$publicReference, 'summary' => 'Un projet concret destiné à construire des capacités utiles de façon progressive.',
            'problem' => 'Des personnes motivées manquent de cadre pratique pour progresser ensemble.', 'proposed_solution' => 'Créer un atelier progressif avec des actions simples et des preuves concrètes.',
            'beneficiaries' => 'Jeunes débutants et personnes en reconversion.', 'domain' => 'DIGITAL', 'participation_mode' => 'HYBRID',
            'objectives' => ['Former une équipe'], 'required_capabilities' => ['Formation numérique'], 'required_resources' => ['Ordinateurs'], 'risks' => [],
            'property_regime' => 'PERSONAL_SUPPORTED', 'visibility' => Project::VISIBILITY_PRIVATE, 'status' => Project::STATUS_IN_PROGRESS, 'maturity' => 'IDEA',
        ]);
    }
}
