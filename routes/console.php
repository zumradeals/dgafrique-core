<?php

declare(strict_types=1);

use App\Console\Commands\GenerateMissionRecurringOccurrences;
use App\Infrastructure\GamadCore\Exceptions\CoreException;
use App\Infrastructure\GamadCore\GamadCoreClient;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Symfony\Component\Console\Command\Command;

// CAP-069 §8 : génération idempotente des occurrences de Missions récurrentes dues.
Schedule::command(GenerateMissionRecurringOccurrences::class)->hourly();

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
