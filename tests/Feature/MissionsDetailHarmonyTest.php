<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Mission;
use App\Models\Project;
use Database\Seeders\MissionsDirectoryDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * UX-HARMONY-MISSIONS-002 — LOT A : harmonisation profonde (Fiche Mission, création, matching,
 * blocage → besoin). Ne re-teste jamais la machine d'états déjà couverte par MissionWorkflowTest/
 * MissionParticipationBlockerTest/MissionStructureMatchingTest : vérifie seulement que la nouvelle
 * présentation reste honnête (repère de progression fondé sur des horodatages réels, jamais de
 * bouton décoratif visible hors autorité réelle), que le contexte est lisible dès la création, que
 * le matching ne montre toujours aucun score, et que chaque route réelle continue de rendre 200
 * sur les 7 statuts du seeder.
 */
final class MissionsDetailHarmonyTest extends TestCase
{
    use RefreshDatabase;

    public function test_fiche_renders_for_every_real_status_seeded(): void
    {
        $this->seed(MissionsDirectoryDemoSeeder::class);
        $this->signIn('DEMO-MISSION-LEADER-01');

        foreach (['DRAFT', 'PROPOSED', 'OPEN', 'IN_PROGRESS', 'BLOCKED', 'SUBMITTED', 'COMPLETED'] as $status) {
            $mission = Mission::query()->where('status', $status)
                ->whereIn('created_by_core_reference', ['DEMO-MISSION-LEADER-01', 'DEMO-MISSION-LEADER-02', 'DEMO-MISSION-LEADER-03'])
                ->first();
            self::assertNotNull($mission, "Aucune Mission de démonstration au statut {$status}.");

            $this->get(route('missions.show', $mission))->assertOk();
        }
    }

    public function test_progress_stepper_reflects_real_timestamps_never_an_invented_state(): void
    {
        $this->seed(MissionsDirectoryDemoSeeder::class);
        $this->signIn('DEMO-MISSION-LEADER-01');

        $blocked = Mission::query()->where('title', 'Équiper la première tournée sanitaire mobile')->firstOrFail();
        self::assertNotNull($blocked->started_at, 'Une Mission BLOCKED est nécessairement passée par IN_PROGRESS.');
        self::assertNull($blocked->submitted_at);

        $content = $this->get(route('missions.show', $blocked))->assertOk()->getContent();

        // Le badge de statut réel (Bloquée) reste affiché ; le stepper marque "En cours" comme
        // dernier jalon réellement atteint (started_at non nul), jamais "Soumise" (submitted_at nul).
        self::assertStringContainsString('Bloquée', $content);
        self::assertMatchesRegularExpression('/is-current[^>]*>[^<]*En cours/s', $content);
    }

    public function test_draft_mission_only_shows_the_propose_action_never_officialize(): void
    {
        $this->seed(MissionsDirectoryDemoSeeder::class);
        $this->signIn('DEMO-MISSION-LEADER-01');

        $draft = Mission::query()->where('title', 'Cartographier les besoins de formation continue')->firstOrFail();
        $content = $this->get(route('missions.show', $draft))->assertOk()->getContent();

        self::assertStringContainsString('Proposer cette Mission', $content);
        self::assertStringNotContainsString('Officialiser et ouvrir', $content, 'Un brouillon ne peut jamais être officialisé directement : aucun bouton décoratif simulant cette action.');
    }

    public function test_pending_offer_shows_real_accept_decline_actions_in_participation_panel(): void
    {
        $this->seed(MissionsDirectoryDemoSeeder::class);
        $this->signIn('DEMO-MISSION-LEADER-01');

        $mission = Mission::query()->where('title', 'Recenser les écoles partenaires de Kayes')->firstOrFail();
        $pending = $mission->assignments()->where('status', 'OFFERED')->first();
        self::assertNotNull($pending, 'Le seeder doit laisser au moins une offre non acceptée pour éprouver ce panneau.');

        $content = $this->get(route('missions.show', $mission))->assertOk()->getContent();
        self::assertStringContainsString(route('missions.assignments.offer.accept', [$mission, $pending]), $content);
        self::assertStringContainsString(route('missions.assignments.offer.decline', [$mission, $pending]), $content);
    }

    public function test_create_form_shows_the_real_context_name_and_the_honest_next_steps(): void
    {
        $this->seed(MissionsDirectoryDemoSeeder::class);
        $this->signIn('DEMO-MISSION-LEADER-01');

        $project = Project::query()->where('name', 'Centre de lecture communautaire de Kayes')->firstOrFail();
        $content = $this->get(route('projects.missions.create', $project))->assertOk()->getContent();

        self::assertStringContainsString($project->name, $content);
        self::assertStringContainsString('Brouillon → à proposer', $content);
    }

    public function test_matching_page_never_shows_a_human_value_score(): void
    {
        $this->seed(MissionsDirectoryDemoSeeder::class);
        $this->signIn('DEMO-MISSION-LEADER-01');

        $mission = Mission::query()->where('title', 'Constituer le premier fonds de livres')->firstOrFail();
        $content = $this->get(route('missions.matching', $mission))->assertOk()->getContent();

        self::assertStringContainsString('Aucun — jamais produit', $content);
        self::assertStringNotContainsString('%match', $content);
        foreach (['note sur 10', 'classement', 'top match'] as $forbidden) {
            self::assertStringNotContainsStringIgnoringCase($forbidden, $content);
        }
    }

    public function test_blocked_mission_need_expression_makes_the_transition_explicit(): void
    {
        $this->seed(MissionsDirectoryDemoSeeder::class);
        $this->signIn('DEMO-MISSION-LEADER-01');

        $mission = Mission::query()->where('title', 'Équiper la première tournée sanitaire mobile')->firstOrFail();
        $blocker = $mission->blockers()->whereNull('resolved_at')->firstOrFail();

        $content = $this->get(route('missions.blockers.express-need.create', [$mission, $blocker]))->assertOk()->getContent();

        self::assertStringContainsString($mission->title, $content);
        self::assertStringContainsString('Blocage → Besoin', $content);
        self::assertStringContainsString('Aucun déblocage automatique', $content);
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-12-01T00:00:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Identité démo', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-12-01T00:00:00+00:00']),
        ]);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
