<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Project;
use Database\Seeders\ProjectHubDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ProjectHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_hub_is_honest_without_the_opt_in_seed(): void
    {
        $this->signIn('IDN-PH-EMPTY');
        $this->get(route('projects.index'))->assertOk()
            ->assertSee('Les projets qui construisent')
            ->assertSee('Aucun projet visible');
        self::assertSame(0, Project::query()->count());
    }

    public function test_the_eight_seeded_projects_and_their_carrier_groups_are_navigable(): void
    {
        $this->seed(ProjectHubDemoSeeder::class);
        $this->signIn('IDN-PH-VIEWER');
        $content = $this->get(route('projects.index'))->assertOk()->getContent();

        foreach (['Plateforme numérique pour artisans d’Abobo', 'Installation solaire pour écoles rurales', 'Centre de formation couture et design', 'Reboisement communautaire de Tambacounda', 'Bibliothèque numérique mobile', 'Marché en ligne des producteurs', 'Accès à l’eau potable', 'Incubateur de talents numériques'] as $name) {
            self::assertStringContainsString($name, $content);
            self::assertStringContainsString(route('projects.show', Project::query()->where('name', $name)->sole()), $content);
        }
        foreach (['RAHMAN Technology', 'ZUMRA Bamtaré', 'Excellence ZUMRA', 'ZUMRA Vert Demain', 'Savoir pour Tous', 'AgriZUMRA', 'ZUMRA Eau Vie', 'Code & Impact'] as $group) {
            self::assertStringContainsString($group, $content);
        }
        self::assertSame(8, Project::query()->count());
    }

    public function test_search_and_supported_filters_use_real_project_fields(): void
    {
        $this->seed(ProjectHubDemoSeeder::class);
        $this->signIn('IDN-PH-FILTER');
        $this->get(route('projects.index', ['q' => 'solaire']))->assertOk()->assertSee('Installation solaire')->assertDontSee('Accès à l’eau potable');
        $this->get(route('projects.index', ['domain' => 'AGRICULTURE']))->assertOk()->assertSee('Marché en ligne des producteurs')->assertDontSee('Incubateur de talents numériques');
        $this->get(route('projects.index', ['status' => Project::STATUS_PROPOSED]))->assertOk()->assertSee('Reboisement communautaire')->assertSee('Accès à l’eau potable')->assertDontSee('Marché en ligne des producteurs');
        $this->get(route('projects.index', ['country' => 'Mali']))->assertOk()->assertSee('Installation solaire')->assertDontSee('Centre de formation couture');
    }

    public function test_harmony_hub_keeps_readable_cards_across_project_breakpoints(): void
    {
        $css = file_get_contents(resource_path('css/projects-directory.css'));
        self::assertNotFalse($css);

        self::assertStringContainsString('.ph-grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}', $css);
        self::assertStringContainsString('@media(max-width:1050px)', $css);
        self::assertStringContainsString('.ph-grid{grid-template-columns:repeat(2,minmax(0,1fr))}', $css);
        self::assertStringContainsString('@media(max-width:640px)', $css);
        self::assertStringContainsString('.ph-grid{grid-template-columns:1fr}', $css);
        self::assertStringContainsString('.ph-card h2{margin-bottom:12px;font-size:20px', $css);
    }

    public function test_harmony_project_detail_exposes_existing_actions_in_the_primary_hierarchy(): void
    {
        $this->seed(ProjectHubDemoSeeder::class);
        $this->signIn('IDN-PH-ACTIONS');
        $project = Project::query()->firstOrFail();

        $content = $this->get(route('projects.show', $project))->assertOk()->getContent();

        self::assertStringContainsString('class="ps-hero-actions"', $content);
        self::assertStringContainsString(route('comments.project', $project), $content);
    }

    public function test_harmony_project_detail_switches_to_a_real_mobile_composition(): void
    {
        $css = file_get_contents(resource_path('css/project-space.css'));
        self::assertNotFalse($css);

        self::assertStringContainsString('@media(max-width:760px)', $css);
        self::assertStringContainsString('.ps-hero{display:flex;flex-direction:column;align-items:stretch', $css);
        self::assertStringContainsString('.ps-cover{width:100%;height:190px}', $css);
        self::assertStringContainsString('.ps-hero-actions a{min-height:48px', $css);
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-16T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre DG Afrique', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-16T23:59:00+00:00']),
        ]);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
