<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Projects\ProjectAccompanimentConfiguration;
use App\Application\Projects\ProjectAccompanimentService;
use App\Models\PortalAdministrator;
use App\Models\Project;
use App\Models\ProjectAccompanimentAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Couvre CAP-046 : la synthèse ne compte que des faits réels (jamais un score), et les filtres
 * isolent la même chronologie sous-jacente sans introduire d'état caché.
 */
final class ProjectAccompanimentDossierTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_synthesis_reflects_real_counts_by_type_and_source(): void
    {
        PortalAdministrator::query()->create(['core_identity_reference' => 'IDN-ADMIN']);
        $project = $this->project();
        $service = app(ProjectAccompanimentService::class);
        $settings = (new ProjectAccompanimentConfiguration())->defaults();
        $service->activate($project, 'IDN-OWNER');
        $service->recordAction($project, 'IDN-ADMIN', $this->actionPayload('ORIENTATION', ProjectAccompanimentAction::SOURCE_INTERNAL, ''), $settings);
        $service->recordAction($project, 'IDN-ADMIN', $this->actionPayload('ORIENTATION', ProjectAccompanimentAction::SOURCE_PARTNER, 'Partenaire A'), $settings);
        $service->recordAction($project, 'IDN-ADMIN', $this->actionPayload('TRAINING', ProjectAccompanimentAction::SOURCE_INTERNAL, ''), $settings);

        $this->signIn('IDN-OWNER');
        $response = $this->get(route('projects.accompaniment.show', $project));

        $response->assertOk()
            ->assertSee('Orientation — 2')
            ->assertSee('Formation — 1')
            ->assertSee('DG Afrique — 2')
            ->assertSee('Partenaire — 1');
    }

    public function test_filtering_by_type_isolates_matching_interventions(): void
    {
        PortalAdministrator::query()->create(['core_identity_reference' => 'IDN-ADMIN']);
        $project = $this->project();
        $service = app(ProjectAccompanimentService::class);
        $settings = (new ProjectAccompanimentConfiguration())->defaults();
        $service->activate($project, 'IDN-OWNER');
        $service->recordAction($project, 'IDN-ADMIN', $this->actionPayload('ORIENTATION', ProjectAccompanimentAction::SOURCE_INTERNAL, '', 'Un premier point de clarification stratégique.'), $settings);
        $service->recordAction($project, 'IDN-ADMIN', $this->actionPayload('TRAINING', ProjectAccompanimentAction::SOURCE_INTERNAL, '', 'Une session de formation pratique réalisée.'), $settings);

        $this->signIn('IDN-OWNER');
        $response = $this->get(route('projects.accompaniment.show', $project).'?type=ORIENTATION');

        $response->assertOk()
            ->assertSee('Un premier point de clarification stratégique.')
            ->assertDontSee('Une session de formation pratique réalisée.');
    }

    public function test_filtering_by_partner_isolates_matching_interventions(): void
    {
        PortalAdministrator::query()->create(['core_identity_reference' => 'IDN-ADMIN']);
        $project = $this->project();
        $service = app(ProjectAccompanimentService::class);
        $settings = (new ProjectAccompanimentConfiguration())->defaults();
        $service->activate($project, 'IDN-OWNER');
        $service->recordAction($project, 'IDN-ADMIN', $this->actionPayload('PARTNERS', ProjectAccompanimentAction::SOURCE_PARTNER, 'Partenaire A', 'Une action réalisée avec le partenaire A.'), $settings);
        $service->recordAction($project, 'IDN-ADMIN', $this->actionPayload('PARTNERS', ProjectAccompanimentAction::SOURCE_PARTNER, 'Partenaire B', 'Une action réalisée avec le partenaire B.'), $settings);

        $this->signIn('IDN-OWNER');
        $response = $this->get(route('projects.accompaniment.show', $project).'?partenaire=Partenaire+A');

        $response->assertOk()
            ->assertSee('Une action réalisée avec le partenaire A.')
            ->assertDontSee('Une action réalisée avec le partenaire B.');
    }

    public function test_an_unmatched_filter_shows_an_honest_empty_state_not_an_error(): void
    {
        PortalAdministrator::query()->create(['core_identity_reference' => 'IDN-ADMIN']);
        $project = $this->project();
        $service = app(ProjectAccompanimentService::class);
        $settings = (new ProjectAccompanimentConfiguration())->defaults();
        $service->activate($project, 'IDN-OWNER');
        $service->recordAction($project, 'IDN-ADMIN', $this->actionPayload('ORIENTATION', ProjectAccompanimentAction::SOURCE_INTERNAL, ''), $settings);

        $this->signIn('IDN-OWNER');
        $response = $this->get(route('projects.accompaniment.show', $project).'?type=TRAINING');

        $response->assertOk()->assertSee('Aucune intervention ne correspond à ce filtre.');
    }

    private function actionPayload(string $type, string $source, string $providerLabel, string $summary = 'Une intervention factuelle suffisamment détaillée pour ce dossier.'): array
    {
        return ['action_type' => $type, 'delivery_source' => $source, 'provider_label' => $providerLabel, 'summary' => $summary, 'next_step' => null];
    }

    private function project(): Project
    {
        return Project::query()->create([
            'public_reference' => '6f84f6c0-1b31-4cb0-9f3d-6efbc6b80d31', 'owner_type' => Project::OWNER_PERSON, 'owner_reference' => 'IDN-OWNER', 'initiator_core_reference' => 'IDN-OWNER',
            'name' => 'Atelier numérique communautaire', 'summary' => 'Un projet concret destiné à construire des capacités utiles de façon progressive.',
            'problem' => 'Des personnes motivées manquent de cadre pratique pour progresser ensemble.', 'proposed_solution' => 'Créer un atelier progressif avec des actions simples et des preuves concrètes.',
            'beneficiaries' => 'Jeunes débutants et personnes en reconversion.', 'domain' => 'DIGITAL', 'participation_mode' => 'HYBRID',
            'objectives' => ['Former une équipe'], 'required_capabilities' => ['Formation numérique'], 'required_resources' => ['Ordinateurs'], 'risks' => [],
            'property_regime' => 'PERSONAL_SUPPORTED', 'visibility' => Project::VISIBILITY_PRIVATE, 'status' => Project::STATUS_IN_PROGRESS, 'maturity' => 'IDEA',
        ]);
    }

    private function signIn(string $reference): void
    {
        Http::fake(['core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-16T23:59:00+00:00'], 201), 'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre DG Afrique', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']), 'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-16T23:59:00+00:00'])]);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
