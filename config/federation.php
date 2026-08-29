<?php

declare(strict_types=1);

$gamadriveProductReference = (string) env('GAMADRIVE_FEDERATION_PRODUCT_REF', 'PRD-GAMAD-002');
$gamadriveCallbackUrl = (string) env(
    'GAMADRIVE_FEDERATION_CALLBACK_URL',
    'https://gamadrive.dgafrique.com/federation/callback',
);
$additionalTrustedProducts = json_decode(
    (string) env('FEDERATION_TRUSTED_PRODUCTS_JSON', '{}'),
    true,
);

return [
    'gamadrive' => [
        'product_reference' => $gamadriveProductReference,
        'display_name' => 'GamaDrive',
        'callback_url' => $gamadriveCallbackUrl,
    ],

    // Autorité de routage fédéré : le registre administrable décrit l'affichage, mais ne peut
    // jamais choisir seul où remettre une preuve Core. Tout nouveau satellite doit être lié ici
    // par configuration de déploiement sous la forme {"PRD-GAMAD-xxx":"https://.../callback"}.
    'trusted_products' => array_replace(
        [$gamadriveProductReference => $gamadriveCallbackUrl],
        is_array($additionalTrustedProducts) && ! array_is_list($additionalTrustedProducts)
            ? $additionalTrustedProducts
            : [],
    ),
];
