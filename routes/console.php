<?php

declare(strict_types=1);

use App\Console\Commands\GenerateMissionRecurringOccurrences;
use App\Console\Commands\RecordSchedulerHeartbeat;
use App\Console\Commands\ReconcilePendingExternalPayments;
use App\Infrastructure\GamadCore\Exceptions\CoreException;
use App\Infrastructure\GamadCore\GamadCoreClient;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Symfony\Component\Console\Command\Command;

// Signal de vie durable : /ready refuse le trafic si le déclencheur système n'appelle plus
// Laravel. Il est placé avant les traitements métier pour mesurer la chaîne de déclenchement.
Schedule::command(RecordSchedulerHeartbeat::class)->everyMinute();

// CAP-069 §8 : génération idempotente des occurrences de Missions récurrentes dues.
Schedule::command(GenerateMissionRecurringOccurrences::class)->hourly();

// Les retours navigateur ne sont pas une source d'autorité de paiement. Le serveur reprend
// périodiquement les tentatives GeniusPay anciennes, avec un verrou anti-chevauchement.
Schedule::command(ReconcilePendingExternalPayments::class)
    ->everyFiveMinutes()
    ->withoutOverlapping(10);

Artisan::command('dg:core:prouver-identite {reference?}', function (GamadCoreClient $core): int {
    $reference = trim((string) ($this->argument('reference') ?: $this->ask('Référence de l’identité Core')));
    $secret = (string) $this->secret('Moyen d’accès Core (saisie invisible)');

    try {
        $proof = $core->proveIdentity($reference, $secret);
    } catch (CoreException $exception) {
        $this->error($exception->getMessage());

        return Command::FAILURE;
    } finally {
        $secret = str_repeat("\0", strlen($secret));
        unset($secret);
    }

    $this->info('Preuve CAP-001 obtenue ; la session de test a été révoquée.');
    $this->table(['Champ', 'Valeur'], [
        ['Référence', $proof->identity->reference],
        ['Type', $proof->identity->type],
        ['Libellé', $proof->identity->label],
        ['État', $proof->identity->state ?? '—'],
        ['Régime', $proof->identity->regime],
        ['Assurance', $proof->session->assurance],
        ['Expire le', $proof->session->expiresAt->format(DATE_ATOM)],
    ]);

    return Command::SUCCESS;
})->purpose('Prouver CAP-001 sans afficher ni conserver le secret ou le bearer Core');
