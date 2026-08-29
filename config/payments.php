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
        'connect_timeout' => (int) env('GENIUSPAY_CONNECT_TIMEOUT', 3),
        'timeout' => (int) env('GENIUSPAY_TIMEOUT', 15),
        'reconciliation' => [
            'stale_after_minutes' => max(1, (int) env('GENIUSPAY_RECONCILIATION_STALE_AFTER_MINUTES', 5)),
            'batch_size' => max(1, min(500, (int) env('GENIUSPAY_RECONCILIATION_BATCH_SIZE', 100))),
        ],
        'return_url_ttl_minutes' => max(5, (int) env('GENIUSPAY_RETURN_URL_TTL_MINUTES', 1440)),
        // CAP-007B : un paiement sandbox ne peut activer une adhésion que si cet interrupteur
        // dédié est explicitement activé — jamais déduit de APP_ENV, qui peut légitimement
        // valoir "production" sur le domaine réel pendant une phase de test. À désactiver
        // explicitement dès que de vrais membres commencent à payer pour de vrai.
        'sandbox_activation_allowed' => (bool) env('GENIUSPAY_SANDBOX_ACTIVATION_ALLOWED', false),
    ],
];
