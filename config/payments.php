<?php

declare(strict_types=1);

return [
    'membership' => [
        'enabled' => (bool) env('ZUMRA_PAYMENT_ENABLED', false),
        'amount' => (int) env('ZUMRA_MEMBERSHIP_FEE_XOF', 500),
        'currency' => 'XOF',
        'provider' => 'geniuspay',
    ],
    'geniuspay' => [
        'base_url' => rtrim((string) env('GENIUSPAY_BASE_URL', 'https://geniuspay.ci/api/v1/merchant'), '/'),
        'environment' => (string) env('GENIUSPAY_ENVIRONMENT', 'live'),
        'api_key' => env('GENIUSPAY_API_KEY'),
        'api_secret' => env('GENIUSPAY_API_SECRET'),
        'timeout' => (int) env('GENIUSPAY_TIMEOUT', 15),
    ],
];
