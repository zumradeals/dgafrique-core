<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Contributions\ContributionConfiguration;
use App\Application\Contributions\ContributionService;
use App\Application\Zahab\ZahabWalletService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\PortalAdministrator;
use App\Models\PortalSetting;
use App\Models\ZahabWallet;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraProgramMembership;
use Illuminate\Database\Seeder;

/**
 * CONTRIBUTION-ZAHAB-001 — démontre le raccordement CAP-061 ↔ Wallet ZAHAB avec un scénario
 * individuel et un scénario collectif, réels de bout en bout (aucun solde ni aucune ligne
 * `dg_ledger_entries` écrite directement — tout passe par `ContributionService`/`ZahabWalletService`,
 * exactement comme un membre réel le vivrait).
 *
 * Volontairement PAS branché sur `DatabaseSeeder::run()` (qui reste vide) : opt-in, à exécuter
 * explicitement (`php artisan db:seed --class="Database\\Seeders\\ContributionZahabDemoSeeder"`) sur
 * un environnement de démonstration/staging. Toutes les identités seedées portent le préfixe
 * DEMO- pour rester reconnaissables comme démonstration.
 *
 * Après exécution :
 * - se connecter avec DEMO-IDN-ZAHAB-CONTRIBUTOR pour voir /contributions/tableau avec un
 *   engagement individuel actif et un Wallet ZAHAB crédité, prêt à régler la période courante ;
 * - se connecter avec DEMO-IDN-ZAHAB-ZUMRA-LEAD pour voir l'engagement collectif de sa ZUMRA,
 *   déjà approuvé et actif, Wallet de la ZUMRA crédité.
 */
final class ContributionZahabDemoSeeder extends Seeder
{
    public function run(): void
    {
        $configuration = app(ContributionConfiguration::class);
        PortalSetting::query()->updateOrCreate(
            ['key' => ContributionConfiguration::KEY],
            [
                'value' => array_merge($configuration->defaults(), ['individual_enabled' => true, 'collective_enabled' => true]),
                'updated_by_core_reference' => 'DEMO-IDN-ZAHAB-SEED',
            ]
        );

        $charter = ZumraCharter::query()->firstOrCreate(
            ['version' => '2026.1'],
            [
                'title' => 'Charte du Programme ZUMRA', 'body' => str_repeat('Respect, transmission et construction collective. ', 8),
                'content_hash' => hash('sha256', 'charte-demo-contribution-zahab'), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now(),
            ]
        );

        $contributions = app(ContributionService::class);
        $wallets = app(ZahabWalletService::class);

        // ===== Scénario individuel =====
        $contributor = 'DEMO-IDN-ZAHAB-CONTRIBUTOR';
        ZumraProgramMembership::query()->firstOrCreate(
            ['core_identity_reference' => $contributor],
            [
                'status' => ZumraProgramMembership::STATUS_ACTIVE, 'accepted_charter_id' => $charter->id,
                'accepted_charter_version' => $charter->version, 'accepted_charter_hash' => $charter->content_hash,
                'charter_accepted_at' => now(), 'submitted_at' => now(), 'activated_at' => now(),
            ]
        );
        try {
            $contributions->startIndividual($contributor);
        } catch (\Throwable) {
            // Déjà démarré lors d'une exécution précédente du seed — idempotent par construction.
        }
        $personalWallet = $wallets->walletFor(ZahabWallet::SUBJECT_PERSON, $contributor, 'DEMO-IDN-ZAHAB-SEED');
        if ($wallets->balance($personalWallet) < 5000) {
            $wallets->credit($personalWallet, 5000, ZahabWalletService::REASON_AID, 'demo-seed-credit:'.$contributor, 'DEMO-IDN-ZAHAB-SEED');
        }

        // ===== Scénario collectif =====
        $primaryLead = 'DEMO-IDN-ZAHAB-ZUMRA-LEAD';
        $financeLead = 'DEMO-IDN-ZAHAB-ZUMRA-FINANCE';
        $admin = 'DEMO-IDN-ZAHAB-ADMIN';
        PortalAdministrator::query()->firstOrCreate(['core_identity_reference' => $admin]);
        foreach ([$primaryLead, $financeLead] as $identity) {
            ZumraProgramMembership::query()->firstOrCreate(
                ['core_identity_reference' => $identity],
                [
                    'status' => ZumraProgramMembership::STATUS_ACTIVE, 'accepted_charter_id' => $charter->id,
                    'accepted_charter_version' => $charter->version, 'accepted_charter_hash' => $charter->content_hash,
                    'charter_accepted_at' => now(), 'submitted_at' => now(), 'activated_at' => now(),
                ]
            );
        }

        $groups = app(ZumraGroupService::class);
        $group = ZumraGroup::query()->where('proposer_core_reference', $primaryLead)->first();
        if ($group === null) {
            $group = $groups->create($primaryLead, [
                'name' => 'ZUMRA Démonstration ZAHAB',
                'domain' => 'Numérique',
                'founding_objective' => 'Démontrer le raccordement CAP-061 au Wallet ZAHAB sur un environnement de démonstration.',
                'participation_mode' => 'HYBRID',
                'internal_charter' => 'Chaque membre respecte la dignité, la hiérarchie, la transmission et les décisions responsables.',
                'assume_primary_lead' => true,
            ], 3);

            foreach (['FIRST_DEPUTY' => 'DEMO-IDN-ZAHAB-DEPUTY1', 'SECOND_DEPUTY' => 'DEMO-IDN-ZAHAB-DEPUTY2', 'FINANCE_LEAD' => $financeLead, 'SOCIAL_RELATIONS_LEAD' => 'DEMO-IDN-ZAHAB-SOCIAL'] as $role => $identity) {
                $groups->proposeRole($group, $primaryLead, $role, $identity);
                $groups->acceptRole($group, $identity, $role, 3, $role === 'SOCIAL_RELATIONS_LEAD');
            }
            $group = $groups->validate($group->refresh(), $admin);
            $group = $groups->activate($group, $admin);
        }

        try {
            $contribution = $contributions->proposeCollective($group->refresh(), $primaryLead);
            $contributions->approveCollective($contribution, $financeLead);
        } catch (\Throwable) {
            // Déjà proposé/approuvé lors d'une exécution précédente — idempotent par construction.
        }

        $zumraWallet = $wallets->walletFor(ZahabWallet::SUBJECT_ZUMRA_GROUP, $group->id, $admin);
        if ($wallets->balance($zumraWallet) < 10000) {
            $wallets->credit($zumraWallet, 10000, ZahabWalletService::REASON_AID, 'demo-seed-credit:'.$group->id, $admin);
        }
    }
}
