<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Contributions\ContributionConfiguration;
use App\Application\Contributions\ContributionService;
use App\Application\Missions\MissionAssignmentService;
use App\Application\Missions\MissionWorkflow;
use App\Application\Needs\NeedConfiguration;
use App\Application\Needs\NeedService;
use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectFundingContributionService;
use App\Application\Projects\ProjectFundingService;
use App\Application\Projects\ProjectService;
use App\Application\Projects\ProjectTeamService;
use App\Application\Proof\ProofWorkflow;
use App\Application\Zahab\ZahabWalletService;
use App\Models\Contribution;
use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\Need;
use App\Models\PortalSetting;
use App\Models\Project;
use App\Models\ProjectFunding;
use App\Models\ProjectTeamMember;
use App\Models\Proof;
use App\Models\ZahabWallet;
use App\Models\ZumraGroup;
use Illuminate\Database\Seeder;

/**
 * BETA-READY-003 (LOT 2) — orchestrateur de démonstration : compose les seeders opt-in déjà
 * existants (ZumraWorldDemoSeeder), sans jamais recopier leur logique, pour donner à UNE seule
 * identité — DEMO-IDN-VIEWER, déjà décorée par ce seeder — un parcours démontrable de bout en
 * bout : identité → ZUMRA (Agri'Demain) → besoin → Projet rattaché → équipe → Mission → Wallet
 * ZAHAB → contribution → financement du Projet → preuve.
 *
 * Chaque mouvement d'argent ou de statut passe par le service métier réel qui le gouvernerait en
 * production (ProjectService, ProjectTeamService, NeedService, MissionWorkflow/
 * MissionAssignmentService, ZahabWalletService, ContributionService,
 * ProjectFundingService/ProjectFundingContributionService, ProofWorkflow) — jamais un solde ou un
 * statut écrit directement. DEMO-IDN-F004 (fondateur/leader d'Agri'Demain) porte les décisions
 * réservées à l'autorité du Projet ; DEMO-IDN-VIEWER reste un membre actif qui rejoint, propose et
 * contribue comme le ferait une personne réelle — jamais le porteur initial du Projet, pour
 * pouvoir réellement traverser ProjectTeamService::requestToJoin().
 *
 * Idempotent par construction : chaque étape est gardée par une vérification d'existence AVANT
 * d'appeler le service métier. ZumraWorldDemoSeeder lui-même n'est PAS idempotent (il recrée un
 * réseau ZUMRA à chaque exécution) : il n'est donc appelé ici qu'une seule fois, protégé par la
 * garde sur l'existence de la ZUMRA « Agri'Demain », plutôt que rendu idempotent lui-même (hors
 * périmètre du mandat).
 *
 * Limite assumée (documentée, non contournée) : DEMO-IDN-VIEWER a déjà une ZumraProgramMembership
 * ACTIVE (posée par ZumraWorldDemoSeeder) — un paiement d'adhésion (statut PENDING_PAYMENT →
 * ACTIVE) ne peut donc pas être démontré pour cette identité sans forcer un état incohérent avec
 * l'existant. La démonstration ZAHAB porte ici sur la contribution mensuelle (CAP-061) et le
 * financement de Projet (CAP-063), tous deux réellement accessibles pour DEMO-IDN-VIEWER.
 *
 * Volontairement PAS branché sur DatabaseSeeder::run() : opt-in, à exécuter explicitement —
 * php artisan db:seed --class="Database\\Seeders\\BetaDemoSeeder"
 * (nécessite ZumraWorldDemoSeeder, appelé automatiquement ci-dessous si son décor est absent).
 *
 * Après exécution : se connecter avec DEMO-IDN-VIEWER et ouvrir Mon espace, sa ZUMRA
 * (Agri'Demain), le Projet « Maraîchage solidaire du quartier (démonstration bêta) », la Mission
 * liée, son Wallet ZAHAB et le panneau Financement du Projet.
 */
final class BetaDemoSeeder extends Seeder
{
    private const VIEWER = 'DEMO-IDN-VIEWER';

    private const ZUMRA_NAME = "Agri'Demain";

    private const FOUNDER = 'DEMO-IDN-F004';

    private const PROJECT_NAME = 'Maraîchage solidaire du quartier (démonstration bêta)';

    private const CONTRIBUTION_PURPOSE_CODE = 'VALIDATED_PROJECTS';

    public function run(): void
    {
        $this->command?->warn('BetaDemoSeeder : orchestration du décor de démonstration bêta (identités DEMO-*), strictement opt-in.');

        if (ZumraGroup::query()->where('name', self::ZUMRA_NAME)->doesntExist()) {
            $this->call(ZumraWorldDemoSeeder::class);
        }

        $group = ZumraGroup::query()->where('name', self::ZUMRA_NAME)->firstOrFail();

        $project = $this->project($group);
        $this->joinTeam($project);
        $this->need($project);
        $this->mission($project);
        $this->creditViewerWallet();
        $this->contribution();
        $funding = $this->funding($project);
        $this->fundingContribution($funding, $project);
        $this->proof($project);

        $this->command?->info('BetaDemoSeeder : parcours démonstratif de DEMO-IDN-VIEWER prêt sur le Projet « '.self::PROJECT_NAME.' ».');
    }

    private function project(ZumraGroup $group): Project
    {
        $project = Project::query()->where('zumra_group_id', $group->id)->where('name', self::PROJECT_NAME)->first();
        if ($project !== null) {
            return $project->fresh();
        }

        $projects = app(ProjectService::class);
        $project = $projects->create(self::FOUNDER, [
            'owner_type' => Project::OWNER_GROUP,
            'group_reference' => $group->public_reference,
            'name' => self::PROJECT_NAME,
            'summary' => 'Installer un carré maraîcher collectif pour nourrir les familles du quartier et transmettre des savoir-faire agricoles.',
            'problem' => 'Les familles du quartier manquent d’un accès régulier à des légumes frais et abordables.',
            'proposed_solution' => 'Un carré maraîcher entretenu collectivement par les membres de la ZUMRA, avec une rotation de cultures vivrières.',
            'beneficiaries' => 'Les familles du quartier et les membres de la ZUMRA',
            'domain' => 'AGRICULTURE',
            'participation_mode' => 'PHYSICAL',
            'property_regime' => 'ZUMRA_COLLECTIVE',
            'visibility' => Project::VISIBILITY_PUBLIC,
            'objectives' => "Aménager le terrain\nPlanter les premières cultures\nOrganiser la distribution",
            'required_capabilities' => "Maraîchage\nLogistique",
            'required_resources' => "Semences\nOutils de jardinage",
            'risks' => 'Disponibilité de l’eau en saison sèche.',
            'milestones' => "Aménagement du terrain\nPremière récolte\nDistribution aux familles",
        ], app(ProjectConfiguration::class)->get());
        $projects->transition($project, self::FOUNDER, Project::STATUS_ADOPTED);

        return $project->fresh();
    }

    private function joinTeam(Project $project): void
    {
        $member = ProjectTeamMember::query()->where('project_id', $project->id)->where('core_identity_reference', self::VIEWER)->first();
        if ($member?->status === ProjectTeamMember::STATUS_ACTIVE) {
            return;
        }

        $teams = app(ProjectTeamService::class);
        if ($member === null) {
            $teams->requestToJoin($project, self::VIEWER, 'Je souhaite participer à ce projet porté par ma ZUMRA.');
            $member = ProjectTeamMember::query()->where('project_id', $project->id)->where('core_identity_reference', self::VIEWER)->firstOrFail();
        }
        if ($member->status === ProjectTeamMember::STATUS_REQUESTED) {
            $teams->approveRequest($project, self::FOUNDER, $member->id);
        }
    }

    private function need(Project $project): void
    {
        $need = Need::query()->where('owner_type', Need::OWNER_PROJECT)->where('owner_reference', $project->id)
            ->where('author_core_reference', self::VIEWER)->first();

        if ($need === null) {
            $need = app(NeedService::class)->create(self::VIEWER, [
                'owner_type' => Need::OWNER_PROJECT,
                'project_reference' => $project->public_reference,
                'title' => 'Bras supplémentaires pour aménager le carré maraîcher',
                'context' => 'Nous avons besoin de personnes disponibles quelques heures pour préparer le terrain avant les premières plantations du carré maraîcher collectif.',
                'category' => 'LOGISTICS',
                'collaboration_mode' => 'LOCAL',
                'visibility' => Need::VISIBILITY_PROGRAM,
            ], app(NeedConfiguration::class)->get());
        }

        if ($need->status === Need::STATUS_PROPOSED) {
            app(NeedService::class)->transition($need, self::FOUNDER, Need::STATUS_OPEN);
        }
    }

    private function mission(Project $project): void
    {
        $mission = Mission::query()->where('context_type', 'PROJECT')->where('context_reference', $project->public_reference)
            ->where('created_by_core_reference', self::VIEWER)->first();

        $workflow = app(MissionWorkflow::class);
        $expectedResult = 'Le carré maraîcher est planté et prêt pour son premier cycle de croissance.';

        if ($mission === null) {
            $mission = $workflow->create(self::VIEWER, 'PROJECT', $project->public_reference, [
                'title' => 'Préparer et planter le premier carré maraîcher',
                'description' => 'Aménager la parcelle, amender le sol et planter les premières cultures vivrières prévues par le Projet.',
                'expected_result' => $expectedResult,
                'participation_mode' => 'PHYSICAL',
            ]);
        }

        if ($mission->status === Mission::STATUS_DRAFT) {
            $mission = $workflow->propose($mission, self::VIEWER);
        }
        if ($mission->status === Mission::STATUS_PROPOSED) {
            $mission = $workflow->officialize($mission, self::FOUNDER, ['expected_result' => $expectedResult]);
        }

        $assignments = app(MissionAssignmentService::class);
        $assignment = MissionAssignment::query()->where('mission_id', $mission->id)->where('core_identity_reference', self::VIEWER)->first();
        if ($assignment === null) {
            $assignments->offer($mission->fresh(), self::VIEWER, MissionAssignment::ROLE_EXECUTOR);
            $assignment = MissionAssignment::query()->where('mission_id', $mission->id)->where('core_identity_reference', self::VIEWER)->firstOrFail();
        }
        if ($assignment->status === MissionAssignment::STATUS_OFFERED) {
            $assignments->acceptOffer($mission->fresh(), self::FOUNDER, $assignment);
        }

        $mission = $mission->fresh();
        if ($mission->status === Mission::STATUS_OPEN) {
            $workflow->start($mission, self::VIEWER);
        }
    }

    /** Crédit du Wallet ZAHAB de DEMO-IDN-VIEWER — jamais un solde écrit directement (art. mandat). */
    private function creditViewerWallet(): void
    {
        $wallets = app(ZahabWalletService::class);
        $wallet = $wallets->walletFor(ZahabWallet::SUBJECT_PERSON, self::VIEWER, self::VIEWER);
        if ($wallets->balance($wallet) < 10000) {
            $wallets->credit($wallet, 10000, ZahabWalletService::REASON_AID, 'demo-seed-credit:'.self::VIEWER, self::VIEWER);
        }
    }

    private function contribution(): void
    {
        $configuration = app(ContributionConfiguration::class);
        PortalSetting::query()->updateOrCreate(
            ['key' => ContributionConfiguration::KEY],
            [
                'value' => array_merge($configuration->defaults(), ['individual_enabled' => true, 'collective_enabled' => true]),
                'updated_by_core_reference' => 'DEMO-IDN-BETA-SEED',
            ]
        );

        $contributions = app(ContributionService::class);
        $contribution = Contribution::query()->where('type', Contribution::TYPE_INDIVIDUAL)->where('subject_reference', self::VIEWER)->first();
        if ($contribution === null) {
            $contribution = $contributions->startIndividual(self::VIEWER);
        }

        if ($contribution->status === Contribution::STATUS_ACTIVE) {
            $period = now()->format('Y-m');
            $alreadyPaid = $contribution->payments()->where('period', $period)
                ->whereIn('status', ['PENDING', 'PROCESSING', 'COMPLETED'])->exists();
            if (! $alreadyPaid) {
                $contributions->payPeriodWithZahabWallet($contribution, self::VIEWER, $period, self::CONTRIBUTION_PURPOSE_CODE);
            }
        }
    }

    private function funding(Project $project): ProjectFunding
    {
        $funding = ProjectFunding::query()->where('project_id', $project->id)->latest('created_at')->first();
        if ($funding !== null) {
            return $funding;
        }

        return app(ProjectFundingService::class)->create($project, self::FOUNDER, [
            'target_amount' => 6000,
            'currency' => 'XOF',
            'purpose' => 'Financer l’aménagement du carré maraîcher et l’achat des premières semences.',
            'intended_use' => 'Achat de semences, d’outils de jardinage et petits aménagements du terrain.',
            'conditions' => null,
        ]);
    }

    private function fundingContribution(ProjectFunding $funding, Project $project): void
    {
        if ($funding->status !== ProjectFunding::STATUS_OPEN) {
            return;
        }

        $contributions = app(ProjectFundingContributionService::class);
        if ($contributions->collectedAmount($funding, $project) === 0) {
            $contributions->contribute($funding, $project, self::VIEWER, 3000, 'demo-beta-seed-funding-token-001');
        }
    }

    private function proof(Project $project): void
    {
        $exists = Proof::query()
            ->where('owner_type', Proof::OWNER_PERSON)
            ->where('owner_reference', self::VIEWER)
            ->where('origin_type', Proof::ORIGIN_PROJECT)
            ->where('origin_reference', $project->public_reference)
            ->exists();
        if ($exists) {
            return;
        }

        app(ProofWorkflow::class)->submit(self::VIEWER, [
            'owner_type' => Proof::OWNER_PERSON,
            'origin_type' => Proof::ORIGIN_PROJECT,
            'origin_reference' => $project->public_reference,
            'title' => 'Terrain aménagé pour le carré maraîcher',
            'description' => 'La parcelle a été débroussaillée et amendée : premières semences prêtes à être plantées la semaine prochaine.',
            'visibility' => Proof::VISIBILITY_DISCOVERABLE,
        ]);
    }
}
