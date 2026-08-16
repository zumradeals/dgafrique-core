<?php

declare(strict_types=1);

return [
    'gamadrive' => [
        'product_reference' => env('GAMADRIVE_FEDERATION_PRODUCT_REF', 'PRD-GAMAD-002'),
        'display_name' => 'GamaDrive',
        'callback_url' => env(
            'GAMADRIVE_FEDERATION_CALLBACK_URL',
            'https://gamadrive.dgafrique.com/federation/callback',
        ),
    ],
];
