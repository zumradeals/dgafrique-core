<?php

declare(strict_types=1);

namespace App\Application\Missions;

use App\Application\Missions\Contexts\MissionContextAdapter;

/**
 * Registre fail-closed des contextes missionnables. Aucun context_type arbitraire n'est
 * accepté : un contexte n'entre dans le registre que par un adaptateur explicitement
 * enregistré (voir MissionServiceProvider). Le moteur ne contient jamais de if/elseif
 * dispersé par type de contexte — tout passe par ce registre et ses adaptateurs.
 */
final class MissionContextRegistry
{
    /** @var array<string, MissionContextAdapter> */
    private array $adapters = [];

    /** @param iterable<MissionContextAdapter> $adapters */
    public function __construct(iterable $adapters)
    {
        foreach ($adapters as $adapter) {
            $this->adapters[$adapter->type()] = $adapter;
        }
    }

    public function has(string $type): bool
    {
        return isset($this->adapters[$type]);
    }

    public function for(string $type): MissionContextAdapter
    {
        abort_unless($this->has($type), 422, 'Ce contexte de Mission n’est pas pris en charge.');

        return $this->adapters[$type];
    }

    /** @return list<string> */
    public function types(): array
    {
        return array_keys($this->adapters);
    }
}
