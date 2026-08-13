<?php

declare(strict_types=1);

return [
    'base_url' => rtrim((string) env('GAMAD_CORE_BASE_URL', ''), '/'),
    'product_reference' => env('GAMAD_CORE_PRODUCT_REF'),
    'authenticator_reference' => env('GAMAD_CORE_AUTHN_REF'),
    'connect_secret' => env('GAMAD_CORE_CONNECT_SECRET'),
    'timeout_seconds' => (int) env('GAMAD_CORE_TIMEOUT', 8),
    'connect_timeout_seconds' => (int) env('GAMAD_CORE_CONNECT_TIMEOUT', 3),
];
