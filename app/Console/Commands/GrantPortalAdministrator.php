<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PortalAdministrator;
use Illuminate\Console\Command;

final class GrantPortalAdministrator extends Command
{
    protected $signature = 'dg:admin:grant {core_identity_reference}';
    protected $description = 'Accorder l’administration du portail à une identité canonique GAMAD Core';

    public function handle(): int
    {
        $reference = strtoupper(trim((string) $this->argument('core_identity_reference')));
        if (!preg_match('/^IDN-[A-Z]+-[A-Z0-9-]+$/', $reference)) {
            $this->error('La référence Core est invalide.');

            return self::FAILURE;
        }

        PortalAdministrator::query()->firstOrCreate(['core_identity_reference' => $reference]);
        $this->info("Accès administrateur accordé à {$reference}.");

        return self::SUCCESS;
    }
}
