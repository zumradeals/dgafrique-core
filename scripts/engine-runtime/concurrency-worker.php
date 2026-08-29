<?php

declare(strict_types=1);

use App\Application\Missions\MissionRecurrenceService;
use App\Application\Ledger\LedgerService;
use App\Application\Projects\ProjectFundingContributionService;
use App\Application\Zahab\ZahabWalletService;
use App\Models\LedgerEntry;
use App\Models\ContributionPayment;
use App\Models\MissionRecurrence;
use App\Models\Project;
use App\Models\ProjectFunding;
use App\Models\ZahabWallet;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$application = require dirname(__DIR__, 2).'/bootstrap/app.php';
$application->make(Kernel::class)->bootstrap();

/**
 * Worker volontairement séparé du processus PHPUnit : chaque instance possède sa propre
 * connexion PostgreSQL. Une barrière fichier aligne les workers avant l'opération métier.
 */
try {
    [$script, $scenario, $encodedPayload, $gatePath, $readyPath] = $argv + [null, null, null, null, null];
    if (! is_string($scenario) || ! is_string($encodedPayload) || ! is_string($gatePath) || ! is_string($readyPath)) {
        throw new InvalidArgumentException('Arguments du worker incomplets.');
    }

    $payload = json_decode(base64_decode($encodedPayload, true) ?: '', true, 512, JSON_THROW_ON_ERROR);
    if (! is_array($payload)) {
        throw new InvalidArgumentException('Payload du worker invalide.');
    }

    DB::purge('pgsql');
    DB::reconnect('pgsql');
    $timezone = DB::selectOne("select current_setting('TIMEZONE') as timezone")->timezone ?? null;
    if ($timezone !== 'UTC') {
        throw new RuntimeException('La connexion PostgreSQL du worker doit être en UTC.');
    }

    if (file_put_contents($readyPath, (string) getmypid(), LOCK_EX) === false) {
        throw new RuntimeException('Impossible de signaler le worker prêt.');
    }

    $deadline = microtime(true) + 15;
    while (! is_file($gatePath)) {
        if (microtime(true) >= $deadline) {
            throw new RuntimeException('Délai dépassé avant ouverture de la barrière.');
        }
        usleep(10_000);
    }

    $result = match ($scenario) {
        'wallet_for' => (static function () use ($payload): array {
            $wallet = app(ZahabWalletService::class)->walletFor(
                (string) $payload['subject_type'],
                (string) $payload['subject_reference'],
                (string) $payload['actor'],
            );

            return ['wallet_id' => $wallet->id];
        })(),
        'wallet_credit' => (static function () use ($payload): array {
            $entry = app(ZahabWalletService::class)->credit(
                ZahabWallet::query()->findOrFail((string) $payload['wallet_id']),
                (int) $payload['amount'],
                (string) $payload['reason'],
                (string) $payload['key'],
                (string) $payload['actor'],
            );

            return ['entry_id' => $entry->id];
        })(),
        'wallet_debit' => (static function () use ($payload): array {
            $entry = app(ZahabWalletService::class)->debit(
                ZahabWallet::query()->findOrFail((string) $payload['wallet_id']),
                (int) $payload['amount'],
                (string) $payload['reason'],
                (string) $payload['key'],
                (string) $payload['actor'],
            );

            return ['entry_id' => $entry->id];
        })(),
        'wallet_reverse' => (static function () use ($payload): array {
            $entry = app(ZahabWalletService::class)->reverse(
                LedgerEntry::query()->findOrFail((string) $payload['movement_id']),
                (string) $payload['key'],
                (string) $payload['actor'],
            );

            return ['entry_id' => $entry->id];
        })(),
        'ledger_contribution_payment' => (static function () use ($payload): array {
            $entry = app(LedgerService::class)->postContributionPayment(
                ContributionPayment::query()->findOrFail((string) $payload['payment_id']),
            );

            return ['entry_id' => $entry?->id];
        })(),
        'funding_contribute' => (static function () use ($payload): array {
            app(ProjectFundingContributionService::class)->contribute(
                ProjectFunding::query()->findOrFail((string) $payload['funding_id']),
                Project::query()->findOrFail((string) $payload['project_id']),
                (string) $payload['actor'],
                (int) $payload['amount'],
                (string) $payload['token'],
            );

            return ['contributed' => true];
        })(),
        'mission_generate_due' => [
            'generated' => app(MissionRecurrenceService::class)->generateDueOccurrences(),
        ],
        default => throw new InvalidArgumentException("Scénario inconnu : {$scenario}"),
    };

    echo json_encode(['ok' => true, 'result' => $result], JSON_THROW_ON_ERROR).PHP_EOL;
} catch (Throwable $exception) {
    $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : null;
    echo json_encode([
        'ok' => false,
        'error' => [
            'class' => $exception::class,
            'status' => $status,
            'message' => $exception->getMessage(),
        ],
    ], JSON_THROW_ON_ERROR).PHP_EOL;
}
