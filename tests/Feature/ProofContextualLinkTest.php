<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Missions\MissionWorkflow;
use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\Project;
use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * BETA-READY-004 (LOT 3) — lien contextuel « Publier une preuve » depuis une Mission ou un Projet
 * pertinents, ouvrant le formulaire de preuve EXISTANT avec origin_type/origin_reference
 * préremplis. Aucune nouvelle notion de preuve : réutilise exactement ProofContextService déjà en
 * place (mêmes valeurs ORIGIN_MISSION/ORIGIN_PROJECT que le moteur de preuve reconnaît déjà).
 */
final class ProofContextualLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_mission_page_exposes_the_proof_link_once_in_progress(): void
    {
        $this->activateProgram('IDN-OWNER');
        $project = app(ProjectService::class)->create('IDN-OWNER', $this->projectPayload(), (new ProjectConfiguration)->defaults());
        $workflow = app(MissionWorkflow::class);
        $mission = $workflow->create('IDN-OWNER', 'PROJECT', $project->public_reference, [
            'title' => 'Préparer le lancement pilote',
            'description' => 'Organiser la première session pilote avec les premiers bénéficiaires.',
            'expected_result' => 'La session pilote est organisée.',
        ]);
        $mission = $workflow->propose($mission, 'IDN-OWNER');
        $mission = $workflow->officialize($mission, 'IDN-OWNER');
        $workflow->start($mission, 'IDN-OWNER');

        $this->signIn('IDN-OWNER');
        $this->get('/missions/'.$mission->public_reference)->assertOk()
            ->assertSee('Publier une preuve')
            ->assertSee('origin_type=MISSION', false)
            ->assertSee('origin_reference='.$mission->public_reference, false);
    }

    public function test_the_mission_page_hides_the_proof_link_while_still_a_draft(): void
    {
        $this->activateProgram('IDN-OWNER');
        $project = app(ProjectService::class)->create('IDN-OWNER', $this->projectPayload(), (new ProjectConfiguration)->defaults());
        $mission = app(MissionWorkflow::class)->create('IDN-OWNER', 'PROJECT', $project->public_reference, [
            'title' => 'Préparer le lancement pilote',
            'description' => 'Organiser la première session pilote avec les premiers bénéficiaires.',
        ]);

        $this->signIn('IDN-OWNER');
        $this->get('/missions/'.$mission->public_reference)->assertOk()->assertDontSee('Publier une preuve');
    }

    public function test_the_project_page_exposes_the_proof_link_once_in_progress(): void
    {
        $this->activateProgram('IDN-OWNER');
        $project = app(ProjectService::class)->create('IDN-OWNER', $this->projectPayload(), (new ProjectConfiguration)->defaults());
        app(ProjectService::class)->transition($project, 'IDN-OWNER', Project::STATUS_IN_PROGRESS);

        $this->signIn('IDN-OWNER');
        $this->get(route('projects.show', $project))->assertOk()
            ->assertSee('Publier une preuve')
            ->assertSee('origin_type=PROJECT', false)
            ->assertSee('origin_reference='.$project->public_reference, false);
    }

    public function test_the_project_page_hides_the_proof_link_while_only_adopted(): void
    {
        $this->activateProgram('IDN-OWNER');
        $project = app(ProjectService::class)->create('IDN-OWNER', $this->projectPayload(), (new ProjectConfiguration)->defaults());

        $this->signIn('IDN-OWNER');
        $this->get(route('projects.show', $project))->assertOk()->assertDontSee('Publier une preuve');
    }

    public function test_the_proof_form_prefills_origin_from_the_mission_link(): void
    {
        $this->activateProgram('IDN-OWNER');
        $project = app(ProjectService::class)->create('IDN-OWNER', $this->projectPayload(), (new ProjectConfiguration)->defaults());
        $workflow = app(MissionWorkflow::class);
        $mission = $workflow->create('IDN-OWNER', 'PROJECT', $project->public_reference, [
            'title' => 'Préparer le lancement pilote',
            'description' => 'Organiser la première session pilote avec les premiers bénéficiaires.',
            'expected_result' => 'La session pilote est organisée.',
        ]);
        $mission = $workflow->propose($mission, 'IDN-OWNER');
        $mission = $workflow->officialize($mission, 'IDN-OWNER');
        $workflow->start($mission, 'IDN-OWNER');

        $this->signIn('IDN-OWNER');
        $this->get('/preuves/creer?origin_type=MISSION&origin_reference='.$mission->public_reference)
            ->assertOk()
            ->assertSee('Vous enregistrez cette preuve depuis la Mission')
            ->assertSee('value="'.$mission->public_reference.'"', false);
    }

    public function test_the_proof_form_prefills_origin_from_the_project_link(): void
    {
        $this->activateProgram('IDN-OWNER');
        $project = app(ProjectService::class)->create('IDN-OWNER', $this->projectPayload(), (new ProjectConfiguration)->defaults());
        app(ProjectService::class)->transition($project, 'IDN-OWNER', Project::STATUS_IN_PROGRESS);

        $this->signIn('IDN-OWNER');
        $this->get('/preuves/creer?origin_type=PROJECT&origin_reference='.$project->public_reference)
            ->assertOk()
            ->assertSee('Vous enregistrez cette preuve depuis le Projet')
            ->assertSee('value="'.$project->public_reference.'"', false);
    }

    public function test_the_proof_form_does_not_prefill_a_project_the_actor_cannot_view(): void
    {
        $this->activateProgram('IDN-OWNER');
        $project = Project::query()->create([
            'public_reference' => (string) Str::uuid(), 'owner_type' => Project::OWNER_PERSON,
            'owner_reference' => 'IDN-OWNER', 'zumra_group_id' => null, 'initiator_core_reference' => 'IDN-OWNER',
            'name' => 'Projet privé', 'summary' => 'Un projet privé.', 'problem' => 'Problème.', 'proposed_solution' => 'Solution.',
            'beneficiaries' => 'Bénéficiaires.', 'domain' => 'DIGITAL', 'participation_mode' => 'HYBRID',
            'objectives' => [], 'required_capabilities' => [], 'required_resources' => [], 'risks' => [],
            'property_regime' => 'PERSONAL_SUPPORTED', 'visibility' => Project::VISIBILITY_PRIVATE,
            'status' => Project::STATUS_IN_PROGRESS, 'maturity' => 'IDEA',
            'decided_by_core_reference' => 'IDN-OWNER', 'adopted_at' => now(), 'started_at' => now(),
        ]);

        $this->activateProgram('IDN-OUTSIDER');
        $this->signIn('IDN-OUTSIDER');
        $this->get('/preuves/creer?origin_type=PROJECT&origin_reference='.$project->public_reference)
            ->assertOk()
            ->assertDontSee('Vous enregistrez cette preuve depuis le Projet');
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

    private function projectPayload(array $overrides = []): array
    {
        return array_replace([
            'owner_type' => 'PERSON', 'group_reference' => null, 'zumra_group_reference' => $this->zumraFor('IDN-OWNER'), 'source_need_reference' => null,
            'name' => 'Atelier numérique communautaire',
            'summary' => 'Créer un espace pratique où des jeunes peuvent apprendre ensemble et produire des services numériques utiles.',
            'problem' => 'Des jeunes motivés disposent de peu de cadres pratiques pour apprendre, expérimenter et transformer leurs acquis en activités utiles.',
            'proposed_solution' => 'Mettre en place un atelier progressif avec transmission entre pairs, exercices réels et accompagnement vers des premiers services.',
            'beneficiaries' => 'Jeunes débutants et personnes en reconversion dans la commune.',
            'domain' => 'DIGITAL', 'participation_mode' => 'HYBRID', 'location' => 'Abidjan',
            'objectives' => "Former une première équipe\nProduire trois services pilotes",
            'required_capabilities' => "Formation numérique\nGestion de projet",
            'required_resources' => "Ordinateurs\nConnexion internet",
            'risks' => "Disponibilité irrégulière\nAccès au matériel",
            'milestones' => "Constituer l’équipe\nPréparer le lieu\nLancer le pilote",
            'property_regime' => 'PERSONAL_SUPPORTED', 'visibility' => 'PUBLIC',
        ], $overrides);
    }

    private function activateProgram(string $reference): void
    {
        $body = str_repeat('Respect et transmission. ', 5);
        $charter = ZumraCharter::query()->firstOrCreate(
            ['version' => '2026.1'],
            ['title' => 'Charte ZUMRA', 'body' => $body, 'content_hash' => hash('sha256', $body), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()]
        );
        ZumraProgramMembership::query()->firstOrCreate(['core_identity_reference' => $reference], [
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
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-20T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre DG Afrique', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-20T23:59:00+00:00']),
        ]);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
