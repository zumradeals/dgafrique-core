<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Operations\OperationalReadinessService;
use Illuminate\Console\Command;

final class CheckOperationalReadiness extends Command
{
    protected $signature = 'ops:readiness {--json : Émettre uniquement un document JSON}';

    protected $description = 'Vérifier PostgreSQL, Redis et la fraîcheur du scheduler';

    public function handle(OperationalReadinessService $readiness): int
    {
        $result = $readiness->inspect();

        if ((bool) $this->option('json')) {
            $this->line((string) json_encode([
                'status' => $result['ready'] ? 'ready' : 'not_ready',
                'checks' => $result['checks'],
                'checked_at' => $result['checked_at'],
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(['Composant', 'État'], array_map(
                static fn (string $name, bool $ready): array => [$name, $ready ? 'READY' : 'NOT READY'],
                array_keys($result['checks']),
                array_values($result['checks']),
            ));
        }

        return $result['ready'] ? self::SUCCESS : self::FAILURE;
    }
}
