<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Transmission;
use Database\Seeders\TransmissionsProofsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * UX-HARMONY-TRANSMISSIONS-PROOFS-001 — harmonisation de la famille Transmission (tableau de
 * bord personnel, fiche, création, matching). Ne re-teste jamais la machine d'états déjà
 * couverte par TransmissionWorkflowTest/TransmissionHttpSmokeTest : vérifie seulement que la
 * nouvelle présentation reste honnête (stepper fondé sur des horodatages réels, jamais de
 * trajectoire fabriquée pour les statuts de branche), que le contexte est lisible dès la
 * création, que le matching ne montre toujours aucun score, et que chaque route réelle continue
 * de rendre 200 sur les 7 statuts du seeder.
 */
final class TransmissionsDetailHarmonyTest extends TestCase
{
    use RefreshDatabase;

    public function test_fiche_renders_for_every_real_status_seeded(): void
    {
        $this->seed(TransmissionsProofsDemoSeeder::class);
        $this->signIn('DEMO-TP-LEADER-01');

        foreach (['PROPOSED', 'ACCEPTED', 'IN_PROGRESS', 'COMPLETED_CONFIRMED', 'COMPLETED_BY_CONTEXT', 'ENDED', 'CANCELLED'] as $status) {
            $transmission = Transmission::query()->where('status', $status)->first();
            self::assertNotNull($transmission, "Aucune Transmission de démonstration au statut {$status}.");

            $this->get(route('transmissions.show', $transmission))->assertOk();
        }
    }

    public function test_progress_stepper_reflects_real_timestamps_and_hides_for_branch_statuses(): void
    {
        $this->seed(TransmissionsProofsDemoSeeder::class);
        $this->signIn('DEMO-TP-LEADER-01');

        $inProgress = Transmission::query()->where('capability_label', 'Teinture indigo traditionnelle')->firstOrFail();
        self::assertNotNull($inProgress->started_at);
        self::assertNull($inProgress->completed_at);

        $content = $this->get(route('transmissions.show', $inProgress))->assertOk()->getContent();
        self::assertMatchesRegularExpression('/is-current"[\s\S]{0,200}?En cours/', $content);

        // ENDED est un statut de branche : le stepper linéaire ne doit jamais fabriquer une
        // position pour lui — il est simplement absent, le badge réel « Arrêtée » reste seul juge.
        $ended = Transmission::query()->where('status', 'ENDED')->firstOrFail();
        $endedContent = $this->get(route('transmissions.show', $ended))->assertOk()->getContent();
        self::assertStringContainsString('Arrêtée', $endedContent);
        self::assertStringNotContainsString('tr-stepper', $endedContent);
    }

    public function test_proposed_transmission_never_shows_a_start_action_before_acceptance(): void
    {
        $this->seed(TransmissionsProofsDemoSeeder::class);
        $this->signIn('DEMO-TP-LEADER-01');

        $proposed = Transmission::query()->where('capability_label', 'Point de croix wax traditionnel')->firstOrFail();
        self::assertSame('PROPOSED', $proposed->status);
        $content = $this->get(route('transmissions.show', $proposed))->assertOk()->getContent();

        self::assertStringNotContainsString('Démarrer la Transmission', $content, 'Une Transmission PROPOSED ne peut jamais démarrer directement : aucun bouton décoratif simulant cette action.');
    }

    public function test_create_form_shows_the_real_next_steps_and_keeps_the_form_fields_unchanged(): void
    {
        $this->seed(TransmissionsProofsDemoSeeder::class);
        $this->signIn('DEMO-TP-LEADER-01');

        $content = $this->get(route('transmissions.create'))->assertOk()->getContent();

        self::assertStringContainsString('Proposée → en attente d’acceptation', $content);
        self::assertStringContainsString('name="capability_label"', $content);
        self::assertStringContainsString('name="learning_objective"', $content);
    }

    public function test_matching_page_never_shows_a_human_value_score(): void
    {
        $this->seed(TransmissionsProofsDemoSeeder::class);
        $this->signIn('DEMO-TP-LEADER-01');

        $transmission = Transmission::query()->where('capability_label', 'Point de croix wax traditionnel')->firstOrFail();
        $content = $this->get(route('transmissions.matching', $transmission))->assertOk()->getContent();

        self::assertStringContainsString('Aucun — jamais produit', $content);
        foreach (['note sur 10', 'classement', 'top match'] as $forbidden) {
            self::assertStringNotContainsStringIgnoringCase($forbidden, $content);
        }
    }

    public function test_completed_transmission_shows_the_honest_bridge_to_the_proof_book(): void
    {
        $this->seed(TransmissionsProofsDemoSeeder::class);
        $this->signIn('DEMO-TP-LEADER-01');

        $completed = Transmission::query()->where('status', 'COMPLETED_CONFIRMED')->firstOrFail();
        $content = $this->get(route('transmissions.show', $completed))->assertOk()->getContent();

        self::assertStringContainsString('Enregistrer une preuve', $content);
        self::assertStringContainsString(e(route('proofs.create', ['origin_type' => 'TRANSMISSION', 'origin_reference' => $completed->public_reference])), $content);
    }

    public function test_personal_dashboard_renders_real_sections_never_a_public_discovery_wording(): void
    {
        $this->seed(TransmissionsProofsDemoSeeder::class);
        $this->signIn('DEMO-TP-LEADER-01');

        $content = $this->get(route('transmissions.index'))->assertOk()->getContent();

        self::assertStringContainsString('Mes Transmissions', $content);
        self::assertStringContainsString('Proposer une Transmission', $content);
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
