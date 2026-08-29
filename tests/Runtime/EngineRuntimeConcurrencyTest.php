<?php

declare(strict_types=1);

namespace Tests\Runtime;

use App\Application\Projects\ProjectFundingContributionService;
use App\Application\Zahab\ZahabWalletService;
use App\Models\Contribution;
use App\Models\ContributionPayment;
use App\Models\ContributionPurpose;
use App\Models\LedgerEntry;
use App\Models\Mission;
use App\Models\MissionRecurrence;
use App\Models\Project;
use App\Models\ProjectFunding;
use App\Models\ZahabWallet;
use App\Models\ZumraGroup;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * ENGINE-RUNTIME-001 / Stage B.
 *
 * Ces tests n'utilisent volontairement ni RefreshDatabase ni DatabaseTransactions : les workers
 * sont des processus PHP indépendants et doivent observer uniquement des données committées.
 * Le runner remet la base PostgreSQL isolée à zéro avant cette suite.
 */
final class EngineRuntimeConcurrencyTest extends TestCase
{
    public function test_wallet_creation_is_unique_under_two_real_processes(): void
    {
        $reference = 'STAGE-B-WALLET-'.Str::uuid();
        $payload = [
            'subject_type' => ZahabWallet::SUBJECT_PERSON,
            'subject_reference' => $reference,
            'actor' => 'STAGE-B-ACTOR',
        ];

        $results = $this->behindDatabaseLock(
            fn () => DB::statement('LOCK TABLE dg_zahab_wallets IN ACCESS EXCLUSIVE MODE'),
            fn (Closure $release) => $this->runConcurrently('wallet_for', [$payload, $payload], $release),
        );

        $this->assertSame([true, true], array_column($results, 'ok'));
        $this->assertSame(
            $results[0]['result']['wallet_id'],
            $results[1]['result']['wallet_id'],
            'Les deux processus doivent retrouver exactement le même Wallet.',
        );
        $this->assertSame(1, ZahabWallet::query()
            ->where('subject_type', ZahabWallet::SUBJECT_PERSON)
            ->where('subject_reference', $reference)
            ->count());
    }

    public function test_two_concurrent_debits_cannot_overdraw_one_wallet(): void
    {
        $wallet = $this->fundedWallet('STAGE-B-DEBIT-'.Str::uuid(), 100);
        $payloads = [
            $this->movementPayload($wallet, 80, 'stage-b:debit:a'),
            $this->movementPayload($wallet, 80, 'stage-b:debit:b'),
        ];

        $results = $this->behindDatabaseLock(
            fn () => ZahabWallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail(),
            fn (Closure $release) => $this->runConcurrently('wallet_debit', $payloads, $release),
        );

        $this->assertSame(1, $this->successCount($results));
        $this->assertSame([409], $this->failureStatuses($results));
        $this->assertSame(20, app(ZahabWalletService::class)->balance($wallet->fresh()));
        $this->assertSame(1, LedgerEntry::query()
            ->where('wallet_id', $wallet->id)
            ->where('direction', LedgerEntry::DIRECTION_DEBIT)
            ->count());
    }

    public function test_same_concurrent_credit_key_is_posted_once_and_returns_same_entry(): void
    {
        $wallet = app(ZahabWalletService::class)->walletFor(
            ZahabWallet::SUBJECT_PERSON,
            'STAGE-B-CREDIT-'.Str::uuid(),
            'STAGE-B-ACTOR',
        );
        $payload = $this->movementPayload($wallet, 75, 'stage-b:credit:same-key');

        $results = $this->behindDatabaseLock(
            fn () => ZahabWallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail(),
            fn (Closure $release) => $this->runConcurrently('wallet_credit', [$payload, $payload], $release),
        );

        $this->assertSame([true, true], array_column($results, 'ok'));
        $this->assertSame($results[0]['result']['entry_id'], $results[1]['result']['entry_id']);
        $this->assertSame(75, app(ZahabWalletService::class)->balance($wallet->fresh()));
        $this->assertSame(1, LedgerEntry::query()->where('wallet_id', $wallet->id)->count());
    }

    public function test_one_movement_cannot_be_reversed_twice_concurrently(): void
    {
        $wallet = app(ZahabWalletService::class)->walletFor(
            ZahabWallet::SUBJECT_PERSON,
            'STAGE-B-REVERSAL-'.Str::uuid(),
            'STAGE-B-ACTOR',
        );
        $movement = app(ZahabWalletService::class)->credit(
            $wallet,
            100,
            ZahabWalletService::REASON_AID,
            'stage-b:reversal:origin:'.Str::uuid(),
            'STAGE-B-ACTOR',
        );
        $payloads = [
            ['movement_id' => $movement->id, 'key' => 'stage-b:reversal:a:'.Str::uuid(), 'actor' => 'STAGE-B-ACTOR'],
            ['movement_id' => $movement->id, 'key' => 'stage-b:reversal:b:'.Str::uuid(), 'actor' => 'STAGE-B-ACTOR'],
        ];

        $results = $this->behindDatabaseLock(
            fn () => ZahabWallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail(),
            fn (Closure $release) => $this->runConcurrently('wallet_reverse', $payloads, $release),
        );

        $this->assertSame(1, $this->successCount($results));
        $this->assertSame([409], $this->failureStatuses($results));
        $this->assertSame(1, LedgerEntry::query()->where('reverses_entry_id', $movement->id)->count());
        $this->assertSame(0, app(ZahabWalletService::class)->balance($wallet->fresh()));
    }

    public function test_completed_payment_projection_is_unique_under_concurrent_posting(): void
    {
        $payment = $this->completedContributionPayment();
        $payload = ['payment_id' => $payment->id];

        // Le verrou de table force les deux workers à terminer leur bootstrap avant de pouvoir
        // lire le Ledger vide. À sa libération, ils exécutent réellement le même posting absent.
        $results = $this->behindDatabaseLock(
            fn () => DB::statement('LOCK TABLE dg_ledger_entries IN ACCESS EXCLUSIVE MODE'),
            fn (Closure $release) => $this->runConcurrently('ledger_contribution_payment', [$payload, $payload], $release),
        );

        $this->assertSame([true, true], array_column($results, 'ok'));
        $this->assertSame($results[0]['result']['entry_id'], $results[1]['result']['entry_id']);
        $this->assertSame(1, LedgerEntry::query()
            ->where('source_type', LedgerEntry::SOURCE_CONTRIBUTION_PAYMENT)
            ->where('source_id', $payment->id)
            ->count());
        $entry = LedgerEntry::query()->where('source_id', $payment->id)->sole();
        $this->assertSame(500, $entry->amount);
        $this->assertSame('XOF', $entry->currency);
        $this->assertSame('TRAINING', $entry->purpose_code);
    }

    public function test_concurrent_project_contributions_never_exceed_the_target(): void
    {
        [$project, $funding] = $this->openProjectFunding(100);
        $actorA = 'STAGE-B-FUND-A-'.Str::uuid();
        $actorB = 'STAGE-B-FUND-B-'.Str::uuid();
        $this->fundedWallet($actorA, 100);
        $this->fundedWallet($actorB, 100);

        $payloads = [
            [
                'funding_id' => $funding->id, 'project_id' => $project->id,
                'actor' => $actorA, 'amount' => 80, 'token' => 'stage-b-token-a',
            ],
            [
                'funding_id' => $funding->id, 'project_id' => $project->id,
                'actor' => $actorB, 'amount' => 80, 'token' => 'stage-b-token-b',
            ],
        ];

        $results = $this->behindDatabaseLock(
            fn () => ProjectFunding::query()->whereKey($funding->id)->lockForUpdate()->firstOrFail(),
            fn (Closure $release) => $this->runConcurrently('funding_contribute', $payloads, $release),
        );

        $this->assertSame(1, $this->successCount($results));
        $this->assertSame([422], $this->failureStatuses($results));
        $this->assertSame(80, app(ProjectFundingContributionService::class)->collectedAmount($funding->fresh(), $project->fresh()));
        $this->assertSame(ProjectFunding::STATUS_OPEN, $funding->fresh()->status);
        $this->assertSame(2, LedgerEntry::query()
            ->where('zahab_operation_reference', 'like', 'project-funding:'.$funding->id.':%')
            ->count(), 'Une contribution réussie doit produire exactement ses deux jambes Ledger.');
    }

    public function test_concurrent_scheduler_workers_generate_one_occurrence_and_advance_once(): void
    {
        $project = $this->project();
        $source = Mission::query()->create([
            'public_reference' => (string) Str::uuid(),
            'context_type' => 'PROJECT',
            'context_reference' => $project->public_reference,
            'created_by_core_reference' => 'STAGE-B-ACTOR',
            'title' => 'Mission récurrente de certification',
            'description' => 'Vérifier la sérialisation de la génération récurrente.',
            'expected_result' => 'Une occurrence unique doit être produite.',
            'acceptance_criteria' => ['Une seule occurrence'],
            'participation_mode' => 'HYBRID',
            'visibility' => Mission::VISIBILITY_PUBLIC,
            'status' => Mission::STATUS_OPEN,
            'min_executors' => 1,
            'proposed_at' => now()->subDay(),
            'officialized_at' => now()->subDay(),
        ]);
        $initialDueAt = now()->subMinute();
        $recurrence = MissionRecurrence::query()->create([
            'source_mission_id' => $source->id,
            'rrule' => 'FREQ=DAILY;INTERVAL=1',
            'timezone' => 'UTC',
            'due_offset_minutes' => null,
            'monthly_anchor_day' => null,
            'is_active' => true,
            'status' => MissionRecurrence::STATUS_ACTIVE,
            'next_occurrence_at' => $initialDueAt,
            'created_by_core_reference' => 'STAGE-B-ACTOR',
        ]);
        // La sérialisation Eloquent/PostgreSQL constitue la vérité temporelle : selon le format
        // de date du modèle, elle peut normaliser les microsecondes de l'objet Carbon initial.
        // L'intervalle doit donc être mesuré depuis la valeur réellement persistée, jamais depuis
        // l'objet PHP antérieur à l'INSERT.
        $persistedInitialDueAt = $recurrence->fresh()->next_occurrence_at;

        $results = $this->behindDatabaseLock(
            fn () => MissionRecurrence::query()->whereKey($recurrence->id)->lockForUpdate()->firstOrFail(),
            fn (Closure $release) => $this->runConcurrently('mission_generate_due', [[], []], $release),
        );

        $this->assertSame([true, true], array_column($results, 'ok'));
        $generatedCounts = array_column(array_column($results, 'result'), 'generated');
        sort($generatedCounts);
        $this->assertSame([0, 1], $generatedCounts);
        $this->assertSame(1, Mission::query()->where('recurrence_id', $recurrence->id)->count());
        $this->assertTrue(
            $recurrence->fresh()->next_occurrence_at->equalTo($persistedInitialDueAt->addDay()),
            'La récurrence doit avancer d’un seul intervalle quotidien, jamais de deux.',
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function runConcurrently(string $scenario, array $payloads, ?Closure $afterGate = null): array
    {
        $runDirectory = storage_path('framework/testing/engine-runtime-stage-b/'.Str::uuid());
        if (! mkdir($runDirectory, 0770, true) && ! is_dir($runDirectory)) {
            $this->fail('Impossible de créer le répertoire de synchronisation Stage B.');
        }

        $gate = $runDirectory.'/go';
        $processes = [];
        foreach (array_values($payloads) as $index => $payload) {
            $process = new Process([
                PHP_BINARY,
                base_path('scripts/engine-runtime/concurrency-worker.php'),
                $scenario,
                base64_encode(json_encode($payload, JSON_THROW_ON_ERROR)),
                $gate,
                $runDirectory."/{$index}.ready",
            ], base_path());
            $process->setTimeout(45);
            $process->start();
            $processes[] = $process;
        }

        $deadline = microtime(true) + 15;
        while (count(glob($runDirectory.'/*.ready') ?: []) !== count($processes)) {
            foreach ($processes as $process) {
                if ($process->isTerminated()) {
                    $this->fail('Un worker a quitté avant la barrière : '.$process->getErrorOutput().$process->getOutput());
                }
            }
            if (microtime(true) >= $deadline) {
                $this->fail('Les workers n’ont pas tous atteint la barrière Stage B.');
            }
            usleep(10_000);
        }

        touch($gate);
        if ($afterGate !== null) {
            $afterGate();
        }

        $results = [];
        foreach ($processes as $process) {
            $process->wait();
            $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput().$process->getOutput());
            $lines = array_values(array_filter(array_map('trim', explode("\n", $process->getOutput()))));
            $this->assertNotEmpty($lines, 'Le worker doit toujours retourner un résultat JSON.');
            $decoded = json_decode((string) end($lines), true, 512, JSON_THROW_ON_ERROR);
            $this->assertIsArray($decoded);
            $results[] = $decoded;
        }

        foreach (glob($runDirectory.'/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($runDirectory);

        return $results;
    }

    private function behindDatabaseLock(Closure $lock, Closure $race): mixed
    {
        DB::beginTransaction();
        try {
            $lock();

            return $race(function (): void {
                usleep(250_000);
                DB::commit();
            });
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }
    }

    private function fundedWallet(string $reference, int $amount): ZahabWallet
    {
        $wallet = app(ZahabWalletService::class)->walletFor(
            ZahabWallet::SUBJECT_PERSON,
            $reference,
            'STAGE-B-ACTOR',
        );
        app(ZahabWalletService::class)->credit(
            $wallet,
            $amount,
            ZahabWalletService::REASON_AID,
            'stage-b:seed:'.$reference.':'.Str::uuid(),
            'STAGE-B-ACTOR',
        );

        return $wallet;
    }

    /** @return array{wallet_id: string, amount: int, reason: string, key: string, actor: string} */
    private function movementPayload(ZahabWallet $wallet, int $amount, string $key): array
    {
        return [
            'wallet_id' => $wallet->id,
            'amount' => $amount,
            'reason' => ZahabWalletService::REASON_AID,
            'key' => $key.':'.Str::uuid(),
            'actor' => 'STAGE-B-ACTOR',
        ];
    }

    /** @return array{Project, ProjectFunding} */
    private function openProjectFunding(int $target): array
    {
        $project = $this->project();
        $funding = ProjectFunding::query()->create([
            'project_id' => $project->id,
            'status' => ProjectFunding::STATUS_OPEN,
            'target_amount' => $target,
            'currency' => ZahabWalletService::CURRENCY,
            'purpose' => 'Certifier le plafond transactionnel sous concurrence.',
            'intended_use' => 'Banc d’essai isolé ENGINE-RUNTIME-001.',
            'author_core_reference' => 'STAGE-B-ACTOR',
            'opened_at' => now(),
        ]);

        return [$project, $funding];
    }

    private function completedContributionPayment(): ContributionPayment
    {
        $actor = 'STAGE-B-PAYER-'.Str::random(10);
        $contribution = Contribution::query()->create([
            'public_reference' => (string) Str::uuid(),
            'type' => Contribution::TYPE_INDIVIDUAL,
            'subject_type' => Contribution::SUBJECT_PERSON,
            'subject_reference' => $actor,
            'status' => Contribution::STATUS_ACTIVE,
            'initiated_by_core_reference' => $actor,
        ]);
        $purpose = ContributionPurpose::query()->where('code', 'TRAINING')->sole();

        return ContributionPayment::query()->create([
            'contribution_id' => $contribution->id,
            'period' => '2026-08',
            'purpose_id' => $purpose->id,
            'initiated_by_core_reference' => $actor,
            'provider' => 'GENIUSPAY',
            'reference' => 'stage-b-payment-'.Str::uuid(),
            'provider_id' => 'stage-b-provider-'.Str::uuid(),
            'amount' => 500,
            'currency' => 'XOF',
            'environment' => 'sandbox',
            'status' => ContributionPayment::STATUS_COMPLETED,
            'completed_at' => now(),
            'last_verified_at' => now(),
        ]);
    }

    private function project(): Project
    {
        $group = ZumraGroup::query()->create([
            'public_reference' => (string) Str::uuid(),
            'name' => 'ZUMRA Stage B '.Str::random(8),
            'slug' => 'zumra-stage-b-'.Str::lower(Str::random(12)),
            'domain' => 'Numérique',
            'founding_objective' => 'Certifier les invariants transactionnels du moteur.',
            'participation_mode' => 'HYBRID',
            'internal_charter' => 'Chaque opération respecte les invariants du moteur.',
            'state' => ZumraGroup::STATE_ACTIVE,
            'maturity' => ZumraGroup::MATURITY_ESTABLISHED,
            'proposer_core_reference' => 'STAGE-B-ACTOR',
            'active_member_count' => 1,
            'activated_at' => now(),
        ]);

        return Project::query()->create([
            'public_reference' => (string) Str::uuid(),
            'owner_type' => Project::OWNER_GROUP,
            'owner_reference' => $group->id,
            'zumra_group_id' => $group->id,
            'initiator_core_reference' => 'STAGE-B-ACTOR',
            'name' => 'Projet Stage B '.Str::random(8),
            'summary' => 'Projet isolé pour la certification concurrente.',
            'problem' => 'Les invariants doivent résister à plusieurs processus.',
            'proposed_solution' => 'Les vérifier sur PostgreSQL avec de vraies connexions.',
            'beneficiaries' => 'La communauté DG Afrique.',
            'domain' => 'DIGITAL',
            'participation_mode' => 'HYBRID',
            'objectives' => ['Certifier le moteur'],
            'required_capabilities' => [],
            'required_resources' => [],
            'risks' => [],
            'property_regime' => 'ZUMRA_COLLECTIVE',
            'visibility' => Project::VISIBILITY_PUBLIC,
            'status' => Project::STATUS_ADOPTED,
            'maturity' => 'IDEA',
            'decided_by_core_reference' => 'STAGE-B-ACTOR',
            'adopted_at' => now(),
        ]);
    }

    /** @param array<int, array<string, mixed>> $results */
    private function successCount(array $results): int
    {
        return count(array_filter($results, fn (array $result): bool => $result['ok'] === true));
    }

    /** @param array<int, array<string, mixed>> $results
     *  @return array<int, int|null>
     */
    private function failureStatuses(array $results): array
    {
        return array_values(array_map(
            fn (array $result): ?int => $result['error']['status'],
            array_filter($results, fn (array $result): bool => $result['ok'] === false),
        ));
    }
}
