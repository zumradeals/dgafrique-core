<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Operational readiness
    |--------------------------------------------------------------------------
    |
    | The HTTP process being alive is not enough to accept traffic. A healthy
    | engine also needs PostgreSQL, both Redis connections, and recent proof
    | that the host scheduler has invoked Laravel's scheduler.
    |
    */
    'scheduler_heartbeat_max_age_seconds' => (int) env('SCHEDULER_HEARTBEAT_MAX_AGE_SECONDS', 180),
];
