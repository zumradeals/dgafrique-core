<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Proof;
use App\Models\Transmission;
use Database\Seeders\TransmissionsProofsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * UX-HARMONY-TRANSMISSIONS-PROOFS-001 — harmonisation de la famille Preuve (Carnet, Mémoire,
 * Fiche, enregistrement). Ne re-teste jamais la machine d'états déjà couverte par
 * ProofWorkflowTest/ProofHttpSmokeTest/ProofContextualLinkTest : vérifie seulement que la
 * nouvelle présentation reste honnête (signaux réels de témoignage/reconnaissance, jamais un
 * score ni une trajectoire linéaire fabriquée), que le contexte est lisible dès la création, et
 * que chaque route réelle continue de rendre 200 sur les statuts du seeder.
 */
final class ProofsDetailHarmonyTest extends TestCase
{
    use RefreshDatabase;

    public function test_fiche_renders_for_every_real_status_seeded(): void
    {
        $this->seed(TransmissionsProofsDemoSeeder::class);
        $this->signIn('DEMO-TP-PEER-01');

        foreach (['SUBMITTED', 'WITNESSED', 'ACKNOWLEDGED', 'DISPUTED'] as $status) {
            $proof = Proof::query()->where('status', $status)->first();
            self::assertNotNull($proof, "Aucune Preuve de démonstration au statut {$status}.");

            $this->get(route('proofs.show', $proof))->assertOk();
        }
    }

    public function test_signals_reflect_real_witness_and_acknowledgement_state_never_a_score(): void
    {
        $this->seed(TransmissionsProofsDemoSeeder::class);
        $this->signIn('DEMO-TP-PEER-01');

        $witnessed = Proof::query()->where('status', 'WITNESSED')->firstOrFail();
        $content = $this->get(route('proofs.show', $witnessed))->assertOk()->getContent();
        self::assertStringContainsString('Témoin confirmé', $content);
        self::assertStringContainsString('Non reconnue par un contexte', $content);

        $submittedNoWitness = Proof::query()->where('title', 'Réparation autonome d’une machine à coudre')->firstOrFail();
        self::assertTrue($submittedNoWitness->witnesses()->count() === 0);
        $content = $this->get(route('proofs.show', $submittedNoWitness))->assertOk()->getContent();
        self::assertStringContainsString('Témoin aucun', $content);

        foreach ([$content] as $c) {
            self::assertStringNotContainsStringIgnoringCase('score', $c);
            self::assertStringNotContainsStringIgnoringCase('classement', $c);
        }
    }

    public function test_archived_proof_shows_the_real_band_and_restore_action_never_dispute(): void
    {
        $this->seed(TransmissionsProofsDemoSeeder::class);
        $this->signIn('DEMO-TP-PEER-01');

        $archived = Proof::query()->where('title', 'Essai de teinture non concluant')->firstOrFail();
        self::assertNotNull($archived->archived_at);

        $content = $this->get(route('proofs.show', $archived))->assertOk()->getContent();
        self::assertStringContainsString('archivée depuis le', $content);
        self::assertStringContainsString('Restaurer', $content);
    }

    public function test_create_form_shows_the_real_origin_context_and_the_honest_next_steps(): void
    {
        $this->seed(TransmissionsProofsDemoSeeder::class);
        $this->signIn('DEMO-TP-LEADER-01');

        $completedTransmission = Transmission::query()->where('status', 'COMPLETED_CONFIRMED')->firstOrFail();
        $content = $this->get(route('proofs.create', ['origin_type' => 'TRANSMISSION', 'origin_reference' => $completedTransmission->public_reference]))->assertOk()->getContent();

        self::assertStringContainsString('Soumise → témoin/reconnaissance facultatifs', $content);
        self::assertStringContainsString($completedTransmission->capability_label, $content);
    }

    public function test_carnet_and_memory_render_real_sections(): void
    {
        $this->seed(TransmissionsProofsDemoSeeder::class);
        $this->signIn('DEMO-TP-PEER-01');

        $carnet = $this->get(route('proofs.index'))->assertOk()->getContent();
        self::assertStringContainsString('Mon Carnet de preuves', $carnet);
        self::assertStringContainsString('Enregistrer une preuve', $carnet);

        $memory = $this->get(route('proofs.memory.self'))->assertOk()->getContent();
        self::assertStringContainsString('Ma mémoire d’expérience', $memory);
        self::assertStringContainsString('Ce n’est jamais un score ni un classement.', $memory);
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
