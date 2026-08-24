<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Zahab\ZahabWalletService;
use App\Models\ZahabAcquisition;
use App\Models\ZahabWallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * ZAHAB-002 — démontre le raccordement acquisition GeniusPay ↔ Wallet ZAHAB : une Personne avec
 * une acquisition GeniusPay déjà CONFIRMÉE de 5 000 ZAHAB, visible dans l'historique du Wallet
 * (/finances/zahab/tableau). Aucun appel réseau réel vers GeniusPay n'est déclenché ici — un seed
 * ne peut pas simuler un paiement externe authentique — mais le crédit lui-même passe par le même
 * service métier réel (`ZahabWalletService::credit()`) et la même convention de clé déterministe
 * (`zahab-acquisition:{id}:wallet-credit`) que `ZahabAcquisitionService::reconcile()`, jamais un
 * simple ajustement de solde.
 *
 * Volontairement PAS branché sur `DatabaseSeeder::run()` : opt-in, à exécuter explicitement
 * (`php artisan db:seed --class="Database\\Seeders\\ZahabAcquisitionDemoSeeder"`) sur un
 * environnement de démonstration/staging. Identité DEMO- pour rester reconnaissable. Idempotent :
 * une seconde exécution ne recrée ni ne recrédite rien.
 *
 * Après exécution : se connecter avec DEMO-IDN-ACQUISITION-ZAHAB et ouvrir /finances/zahab/tableau.
 */
final class ZahabAcquisitionDemoSeeder extends Seeder
{
    public function run(): void
    {
        $identity = 'DEMO-IDN-ACQUISITION-ZAHAB';
        $reference = 'DEMO-ACQ-GENIUSPAY-'.$identity;

        $acquisition = ZahabAcquisition::query()->firstOrCreate(
            ['reference' => $reference],
            [
                'person_core_reference' => $identity,
                'provider' => 'GENIUSPAY',
                'provider_id' => 'demo-pay-'.Str::lower(Str::random(10)),
                'amount' => 5000,
                'currency' => ZahabWalletService::CURRENCY,
                'environment' => 'live',
                'status' => ZahabAcquisition::STATUS_COMPLETED,
                'checkout_url' => 'https://checkout.geniuspay.example/pay/'.$reference,
                'fees' => 0,
                'net_amount' => 5000,
                'provider_snapshot' => ['reference' => $reference, 'status' => 'completed', 'demo' => true],
                'provider_snapshot_hash' => hash('sha256', $reference.'|completed|demo'),
                'completed_at' => now(),
                'last_verified_at' => now(),
            ]
        );

        if ($acquisition->credited_at !== null) {
            return;
        }

        $wallets = app(ZahabWalletService::class);
        $wallet = $wallets->walletFor(ZahabWallet::SUBJECT_PERSON, $identity, $identity);
        $operationReference = 'zahab-acquisition:'.$acquisition->id;

        $wallets->credit(
            $wallet,
            $acquisition->amount,
            ZahabWalletService::REASON_ZAHAB_ACQUISITION,
            $operationReference.':wallet-credit',
            $identity,
            $operationReference,
        );

        $acquisition->update(['credited_at' => now()]);
    }
}
