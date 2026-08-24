<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Zahab\ZahabWalletService;
use App\Models\ZahabWallet;
use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use Illuminate\Database\Seeder;

/**
 * ADHESION-ZAHAB-001 — démontre le raccordement adhésion ZUMRA ↔ Wallet ZAHAB : une Personne avec
 * 1 500 ZAHAB (crédités via `ZahabWalletService`, jamais un solde fabriqué) et un dossier
 * d'adhésion `PENDING_PAYMENT` prêt à être réglé. Le paiement lui-même n'est PAS déclenché par ce
 * seed — l'intérêt de la démonstration est de pouvoir cliquer soi-même sur « Payer 500 ZAHAB avec
 * mon Wallet » depuis /zumra/adhesion et observer le parcours réel.
 *
 * Volontairement PAS branché sur `DatabaseSeeder::run()` (qui reste vide) : opt-in, à exécuter
 * explicitement (`php artisan db:seed --class="Database\\Seeders\\AdhesionZahabDemoSeeder"`) sur un
 * environnement de démonstration/staging. Identité DEMO- pour rester reconnaissable.
 *
 * Après exécution : se connecter avec DEMO-IDN-ADHESION-ZAHAB et ouvrir /zumra/adhesion.
 */
final class AdhesionZahabDemoSeeder extends Seeder
{
    public function run(): void
    {
        config(['payments.membership.enabled' => true]);
        // `payments.membership.enabled` vient de .env (ZUMRA_PAYMENT_ENABLED) — un seed ne peut
        // pas modifier durablement l'environnement, seulement documenter que la démonstration
        // suppose cet interrupteur ouvert (sinon le bouton du parcours reste volontairement inactif,
        // conformément à la doctrine « dormant par défaut »).

        $charter = ZumraCharter::query()->firstOrCreate(
            ['version' => '2026.1'],
            [
                'title' => 'Charte du Programme ZUMRA', 'body' => str_repeat('Respect, transmission et construction collective. ', 8),
                'content_hash' => hash('sha256', 'charte-demo-adhesion-zahab'), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now(),
            ]
        );

        $identity = 'DEMO-IDN-ADHESION-ZAHAB';
        ZumraProgramMembership::query()->firstOrCreate(
            ['core_identity_reference' => $identity],
            [
                'status' => ZumraProgramMembership::STATUS_PENDING_PAYMENT,
                'accepted_charter_id' => $charter->id, 'accepted_charter_version' => $charter->version,
                'accepted_charter_hash' => $charter->content_hash, 'charter_accepted_at' => now(), 'submitted_at' => now(),
            ]
        );

        $wallets = app(ZahabWalletService::class);
        $wallet = $wallets->walletFor(ZahabWallet::SUBJECT_PERSON, $identity, 'DEMO-IDN-ADHESION-SEED');
        if ($wallets->balance($wallet) < 1500) {
            $wallets->credit($wallet, 1500, ZahabWalletService::REASON_AID, 'demo-seed-credit:'.$identity, 'DEMO-IDN-ADHESION-SEED');
        }
    }
}
