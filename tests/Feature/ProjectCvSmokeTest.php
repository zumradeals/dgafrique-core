<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ZumraGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ProjectCvSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_owner_can_open_the_living_project_cv_without_fake_metrics(): void
    {
        $identity = 'IDN-PROJECT-CV-OWNER';
        $this->signIn($identity);

        $group = ZumraGroup::query()->create([
            'public_reference' => (string) Str::uuid(),
            'name' => 'GAMAD Technology',
            'slug' => 'gamad-technology-test',
            'domain' => 'Technologie',
            'founding_objective' => 'Former des talents et construire des solutions numériques utiles à partir de projets ancrés dans la communauté.',
            'participation_mode' => 'HYBRID',
            'welcome_capacity' => ZumraGroup::WELCOME_PROGRESSIVELY,
            'location' => 'Abidjan, Côte d’Ivoire',
            'state' => ZumraGroup::STATE_CONSTITUTING,
            'maturity' => ZumraGroup::MATURITY_EMERGING,
            'proposer_core_reference' => $identity,
            'active_member_count' => 1,
        ]);

        $project = Project::query()->create([
            'public_reference' => (string) Str::uuid(),
            'owner_type' => Project::OWNER_PERSON,
            'owner_reference' => $identity,
            'zumra_group_id' => $group->id,
            'initiator_core_reference' => $identity,
            'name' => 'Créer une startup technologique',
            'summary' => 'Construire progressivement une organisation capable de concevoir et développer des solutions numériques utiles.',
            'problem' => 'De nombreux besoins numériques locaux restent sans réponse accessible, adaptée et durable pour leurs utilisateurs.',
            'proposed_solution' => 'Réunir des talents, expérimenter des solutions puis structurer une activité technologique utile à partir de la ZUMRA.',
            'beneficiaries' => 'Particuliers, professionnels, institutions et membres de la communauté qui ont besoin de solutions numériques adaptées.',
            'domain' => 'TECHNOLOGY',
            'participation_mode' => 'HYBRID',
            'location' => 'Abidjan, Côte d’Ivoire',
            'objectives' => ['Valider un premier besoin réel', 'Construire puis tester une première solution'],
            'required_capabilities' => ['Développement logiciel', 'Design produit'],
            'required_resources' => ['Hébergement', 'Équipements de test'],
            'risks' => ['Manque de disponibilité des compétences au démarrage'],
            'property_regime' => 'PERSONAL_SUPPORTED',
            'visibility' => Project::VISIBILITY_PUBLIC,
            'status' => Project::STATUS_IN_PROGRESS,
            'maturity' => 'EXPERIMENT',
            'started_at' => now()->subDay(),
        ]);

        $project->milestones()->create([
            'title' => 'Comprendre le besoin',
            'position' => 1,
            'status' => ProjectMilestone::STATUS_COMPLETED,
            'completed_at' => now()->subHour(),
        ]);
        $project->milestones()->create([
            'title' => 'Tester une première solution',
            'position' => 2,
            'status' => ProjectMilestone::STATUS_PLANNED,
        ]);

        $response = $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Créer une startup technologique')
            ->assertSee('GAMAD Technology')
            ->assertSee('ZUMRA MÈRE')
            ->assertSee('Comprendre en quelques secondes.')
            ->assertSee('Jalons du projet')
            ->assertSee('50%')
            ->assertSee('Missions du projet')
            ->assertSee('Ce qu’il faut réunir pour avancer.')
            ->assertSee('BESOINS DU PROJET')
            ->assertSee('ÉTAT DU PROJET')
            ->assertSee('Notre chemin vers la réussite.')
            ->assertSee('Vers une organisation')
            ->assertSee('Financement')
            ->assertSee('Non déclaré')
            ->assertSee('Équipe à constituer')
            ->assertSee('Projet GAMAD')
            ->assertSee('L’équipe se constituera au fil des participations.')
            ->assertDontSee('0 impliqué')
            ->assertDontSee('65%')
            ->assertDontSee('Priorité haute')
            ->assertDontSee('Tâches en cours')
            ->assertDontSee('Échéance cible');

        if (getenv('ASTRA_VISUAL_EXPORT') === '1') {
            $directory = storage_path('app/astra-visual');
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            file_put_contents($directory.'/project-cv.html', $response->getContent());
        }
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-09-20T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre GAMAD', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-09-20T23:59:00+00:00']),
        ]);

        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
