<?php

declare(strict_types=1);

namespace App\Application\ProjectBrain;

interface ProjectBrainAiProvider
{
    /**
     * @param array<int, array{role:string,content:string}> $messages
     * @param array<string, mixed> $currentContext
     * @return array{reply:string,project_state:array<string,mixed>,suggested_next_action:?string,confidence:?float}
     */
    public function respond(array $messages, array $currentContext = []): array;
}
