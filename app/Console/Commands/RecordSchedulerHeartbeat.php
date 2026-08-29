<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class RecordSchedulerHeartbeat extends Command
{
    protected $signature = 'ops:scheduler-heartbeat {--source=laravel-scheduler : Source opérationnelle du signal}';

    protected $description = 'Enregistrer la preuve durable que le scheduler Laravel a été déclenché';

    public function handle(): int
    {
        $source = trim((string) $this->option('source'));

        if ($source === '' || strlen($source) > 120 || preg_match('/^[a-z0-9][a-z0-9._:-]*$/i', $source) !== 1) {
            $this->error('La source doit contenir 1 à 120 caractères alphanumériques, point, tiret, underscore ou deux-points.');

            return self::INVALID;
        }

        $at = now();

        DB::table('dg_operational_heartbeats')->upsert(
            [[
                'name' => 'scheduler',
                'source' => $source,
                'last_succeeded_at' => $at,
                'created_at' => $at,
                'updated_at' => $at,
            ]],
            ['name'],
            ['source', 'last_succeeded_at', 'updated_at'],
        );

        Log::info('Operational scheduler heartbeat recorded.', [
            'source' => $source,
            'last_succeeded_at' => $at->toIso8601String(),
        ]);

        $this->info("Heartbeat scheduler enregistré depuis {$source}.");

        return self::SUCCESS;
    }
}
