<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectFundingContributionService;
use App\Application\Projects\ProjectFundingService;
use App\Application\Projects\ProjectService;
use App\Application\Zahab\ZahabWalletService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\PortalAdministrator;
use App\Models\Project;
use App\Models\ProjectFunding;
use App\Models\ZahabWallet;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraProgramMembership;
use Illuminate\Database\Seeder;

/**
 * PROJECT-FUNDING-002 — démontre la boucle complète : une Personne contribue en ZAHAB à un
 * besoin financier déclaré (`ProjectFunding`, CAP-063, inchangé) d'un Projet porté par une ZUMRA,
 * via `ProjectFundingContributionService::contribute()` — jamais un solde/mouvement fabriqué
 * directement. Aucun Wallet Projet créé (interdiction du mandat) : le bénéficiaire réel est le
 * Wallet de la ZUMRA porteuse.
 *
 * Volontairement PAS branché sur `DatabaseSeeder::run()` : opt-in, à exécuter explicitement
 * (`php artisan db:seed --class="Database\\Seeders\\ProjectFundingZahabDemoSeeder"`) sur un
 * environnement de démonstration/staging. Identités DEMO- pour rester reconnaissables.
 * Idempotent : une seconde exécution ne recrée ni ne recontribue rien.
 *
 * Après exécution : se connecter avec DEMO-IDN-FUNDING-CONTRIBUTOR ou DEMO-IDN-FUNDING-LEAD et
 * ouvrir la fiche du Projet « Puits solaire communautaire (démonstration) », onglet Financement.
 */
final class ProjectFundingZahabDemoSeeder extends Seeder
{
    public function run(): void
    {
        $primaryLead = 'DEMO-IDN-FUNDING-LEAD';
        $financeLead = 'DEMO-IDN-FUNDING-FINANCE';
        $admin = 'DEMO-IDN-FUNDING-ADMIN';
        $contributor = 'DEMO-IDN-FUNDING-CONTRIBUTOR';

        PortalAdministrator::query()->firstOrCreate(['core_identity_reference' => $admin]);

        $charter = ZumraCharter::query()->firstOrCreate(
            ['version' => '2026.1'],
            [
                'title' => 'Charte du Programme ZUMRA', 'body' => str_repeat('Respect, transmission et construction collective. ', 8),
                'content_hash' => hash('sha256', 'charter'), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now(),
            ]
        );
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
                'name' => 'ZUMRA Financement Démonstration',
                'domain' => 'Eau et assainissement',
                'founding_objective' => 'Démontrer PROJECT-FUNDING-002 : un Projet réellement financé en ZAHAB par le réseau.',
                'participation_mode' => 'HYBRID',
                'internal_charter' => 'Chaque membre respecte la dignité, la hiérarchie, la transmission et les décisions responsables.',
                'assume_primary_lead' => true,
            ], 3);

            foreach (['FIRST_DEPUTY' => 'DEMO-IDN-FUNDING-DEPUTY1', 'SECOND_DEPUTY' => 'DEMO-IDN-FUNDING-DEPUTY2', 'FINANCE_LEAD' => $financeLead, 'SOCIAL_RELATIONS_LEAD' => 'DEMO-IDN-FUNDING-SOCIAL'] as $role => $identity) {
                $groups->proposeRole($group, $primaryLead, $role, $identity);
                $groups->acceptRole($group, $identity, $role, 3, $role === 'SOCIAL_RELATIONS_LEAD');
            }
            $group = $groups->validate($group->refresh(), $admin);
            $group = $groups->activate($group, $admin);
        }
        $group = $group->refresh();

        $projects = app(ProjectService::class);
        $project = Project::query()->where('zumra_group_id', $group->id)->where('name', 'Puits solaire communautaire (démonstration)')->first();
        if ($project === null) {
            $project = $projects->create($primaryLead, [
                'owner_type' => Project::OWNER_GROUP,
                'group_reference' => $group->public_reference,
                'name' => 'Puits solaire communautaire (démonstration)',
                'summary' => 'Installer un puits à pompage solaire pour le quartier, démonstration PROJECT-FUNDING-002.',
                'problem' => 'Le quartier dépend d’un point d’eau distant, coûteux en temps pour les familles.',
                'proposed_solution' => 'Un puits équipé d’une pompe solaire, entretenu par la ZUMRA.',
                'beneficiaries' => 'Les familles du quartier',
                'domain' => 'HEALTH',
                'participation_mode' => 'PHYSICAL',
                'property_regime' => 'ZUMRA_COLLECTIVE',
                'visibility' => Project::VISIBILITY_PUBLIC,
                'objectives' => "Forer le puits\nInstaller la pompe solaire\nFormer un comité d’entretien",
                'required_capabilities' => "Forage\nÉlectricité solaire",
                'required_resources' => "Pompe solaire\nMatériel de forage",
                'risks' => 'Disponibilité du matériel localement.',
                'milestones' => "Étude de faisabilité\nForage\nInstallation solaire",
            ], app(ProjectConfiguration::class)->get());
            $projects->transition($project, $primaryLead, Project::STATUS_ADOPTED);
        }
        $project = $project->fresh();

        $fundingService = app(ProjectFundingService::class);
        $funding = ProjectFunding::query()->where('project_id', $project->id)->latest('created_at')->first();
        if ($funding === null) {
            $funding = $fundingService->create($project, $primaryLead, [
                'target_amount' => 10000,
                'currency' => 'XOF',
                'purpose' => 'Financer le forage et la pompe solaire du puits communautaire.',
                'intended_use' => 'Achat de la pompe solaire et rémunération de l’équipe de forage locale.',
                'conditions' => null,
            ]);
        }

        $wallets = app(ZahabWalletService::class);
        $contributorWallet = $wallets->walletFor(ZahabWallet::SUBJECT_PERSON, $contributor, $contributor);
        if ($wallets->balance($contributorWallet) < 8000) {
            $wallets->credit($contributorWallet, 8000, ZahabWalletService::REASON_AID, 'demo-seed-credit:'.$contributor, $contributor);
        }

        $contributions = app(ProjectFundingContributionService::class);
        if ($funding->status === ProjectFunding::STATUS_OPEN && $contributions->collectedAmount($funding, $project) === 0) {
            $contributions->contribute($funding, $project, $contributor, 4000, 'demo-seed-contribution-token-001');
        }
    }
}
