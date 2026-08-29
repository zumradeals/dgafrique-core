<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectMilestone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ProductionTruthTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_and_member_surfaces_use_honest_empty_states(): void
    {
        $this->get('/decouvrir')
            ->assertOk()
            ->assertSee('Le réseau public démarre ici.')
            ->assertDontSee('· Exemple')
            ->assertDontSee('Formation en entrepreneuriat pour jeunes femmes');

        $this->signIn('IDN-TRUTH-EMPTY');
        $this->get('/activite')
            ->assertOk()
            ->assertSee('Aucune activité réelle pour le moment')
            ->assertDontSee('Objet de démonstration')
            ->assertDontSee('RAHMAN Technology');
        $this->get('/besoins')->assertOk()->assertSee('Aucun besoin visible ici')->assertDontSee('Apprendre le forex');
        $this->get('/projets')->assertOk()->assertSee('Aucun projet visible')->assertDontSee('315 000+');
    }

    public function test_a_legacy_demo_title_never_overrides_real_project_fields(): void
    {
        $project = $this->project('Plateforme numérique pour artisans d’Abobo', 'Yamoussoukro, Côte d’Ivoire');
        $this->signIn('IDN-TRUTH-VIEWER');

        $content = $this->get('/projets')->assertOk()->getContent();

        self::assertStringContainsString($project->name, $content);
        self::assertStringContainsString('Yamoussoukro, Côte d’Ivoire', $content);
        self::assertStringContainsString('Aucun jalon défini', $content);
        self::assertStringNotContainsString('Priorité haute', $content);
        self::assertStringNotContainsString('65%', $content);
    }

    public function test_project_progress_is_derived_only_from_persisted_milestones(): void
    {
        $project = $this->project('Projet aux jalons réels', 'Abidjan');
        self::assertNull($project->milestoneProgressPercentage());

        ProjectMilestone::query()->create([
            'project_id' => $project->id,
            'title' => 'Première étape',
            'position' => 1,
            'status' => ProjectMilestone::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
        ProjectMilestone::query()->create([
            'project_id' => $project->id,
            'title' => 'Deuxième étape',
            'position' => 2,
            'status' => ProjectMilestone::STATUS_PLANNED,
        ]);

        self::assertSame(50, $project->fresh()->milestoneProgressPercentage());
    }

    public function test_demo_seeders_no_longer_exist_in_the_repository(): void
    {
        self::assertSame([], glob(database_path('seeders/*DemoSeeder.php')) ?: []);
        self::assertSame(
            ['DatabaseSeeder.php'],
            array_map('basename', glob(database_path('seeders/*.php')) ?: []),
        );
    }

    public function test_runtime_demo_fixtures_no_longer_exist(): void
    {
        foreach (['demo-content.json', 'fil-demo.json', 'landing-portal-demo.json', 'needs-demo.json', 'projets-demo.json'] as $fixture) {
            self::assertFileDoesNotExist(resource_path('design-reference/'.$fixture));
        }
    }

    private function project(string $name, string $location): Project
    {
        return Project::query()->create([
            'public_reference' => (string) Str::uuid(),
            'owner_type' => Project::OWNER_PERSON,
            'owner_reference' => 'IDN-TRUTH-OWNER',
            'initiator_core_reference' => 'IDN-TRUTH-OWNER',
            'name' => $name,
            'summary' => 'Un projet réel utilisé pour vérifier que la présentation ne remplace jamais ses données.',
            'problem' => 'Le produit ne doit jamais confondre une donnée réelle avec une ancienne maquette.',
            'proposed_solution' => 'Lire exclusivement les champs persistés et les jalons réels.',
            'beneficiaries' => 'Les membres du réseau concernés par ce projet.',
            'domain' => 'DIGITAL',
            'participation_mode' => 'HYBRID',
            'location' => $location,
            'objectives' => [],
            'required_capabilities' => [],
            'required_resources' => [],
            'risks' => [],
            'property_regime' => 'PERSONAL_SUPPORTED',
            'visibility' => Project::VISIBILITY_PUBLIC,
            'status' => Project::STATUS_PROPOSED,
            'maturity' => 'IDEA',
        ]);
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-12-01T00:00:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre DG Afrique', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-12-01T00:00:00+00:00']),
        ]);

        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
