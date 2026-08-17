<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Missions\MissionRecurrenceService;
use Illuminate\Console\Command;

final class GenerateMissionRecurringOccurrences extends Command
{
    protected $signature = 'missions:generate-recurring-occurrences';
    protected $description = 'Générer les occurrences de Missions récurrentes dues (idempotent, CAP-069 §8)';

    public function handle(MissionRecurrenceService $recurrences): int
    {
        $generated = $recurrences->generateDueOccurrences();
        $this->info("{$generated} occurrence(s) de Mission générée(s).");

        return self::SUCCESS;
    }
}
