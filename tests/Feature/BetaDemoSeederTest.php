<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Zahab\ZahabWalletService;
use App\Models\Contribution;
use App\Models\ContributionPayment;
use App\Models\LedgerEntry;
use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\Need;
use App\Models\Project;
use App\Models\ProjectFunding;
use App\Models\ProjectTeamMember;
use App\Models\Proof;
use App\Models\ZahabWallet;
use App\Models\ZumraGroup;
use Database\Seeders\BetaDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * BETA-READY-003 (LOT 2) — l'orchestrateur de démonstration doit produire, pour
 * DEMO-IDN-VIEWER, un parcours réellement traversable (ZUMRA → besoin → Projet → équipe →
 * Mission → Wallet ZAHAB → contribution → financement du Projet → preuve), entièrement via les
 * services métier réels, et rester sans effet la seconde fois qu'il est exécuté (idempotence
 * requise par le mandat : aucun doublon de membre, de mouvement Ledger, de financement,
 * d'adhésion d'équipe ou de Mission).
 */
final class BetaDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    private const VIEWER = 'DEMO-IDN-VIEWER';

    private const PROJECT_NAME = 'Maraîchage solidaire du quartier (démonstration bêta)';

    public function test_the_seeder_produces_a_complete_and_believable_journey_for_the_viewer(): void
    {
        (new BetaDemoSeeder)->run();

        $group = ZumraGroup::query()->where('name', "Agri'Demain")->sole();
        $project = Project::query()->where('zumra_group_id', $group->id)->where('name', self::PROJECT_NAME)->sole();
        self::assertSame(Project::STATUS_ADOPTED, $project->status);

        self::assertSame(
            ProjectTeamMember::STATUS_ACTIVE,
            ProjectTeamMember::query()->where('project_id', $project->id)->where('core_identity_reference', self::VIEWER)->sole()->status,
        );

        $need = Need::query()->where('owner_type', Need::OWNER_PROJECT)->where('owner_reference', $project->id)
            ->where('author_core_reference', self::VIEWER)->sole();
        self::assertSame(Need::STATUS_OPEN, $need->status);

        $mission = Mission::query()->where('context_type', 'PROJECT')->where('context_reference', $project->public_reference)
            ->where('created_by_core_reference', self::VIEWER)->sole();
        self::assertSame(Mission::STATUS_IN_PROGRESS, $mission->status);
        self::assertSame(
            MissionAssignment::STATUS_ACCEPTED,
            MissionAssignment::query()->where('mission_id', $mission->id)->where('core_identity_reference', self::VIEWER)->sole()->status,
        );

        $wallet = app(ZahabWalletService::class)->walletFor(ZahabWallet::SUBJECT_PERSON, self::VIEWER, self::VIEWER);
        self::assertGreaterThan(0, app(ZahabWalletService::class)->balance($wallet));
        self::assertSame(
            Contribution::STATUS_ACTIVE,
            Contribution::query()->where('type', Contribution::TYPE_INDIVIDUAL)->where('subject_reference', self::VIEWER)->sole()->status,
        );
        self::assertSame(1, ContributionPayment::query()->count());

        $funding = ProjectFunding::query()->where('project_id', $project->id)->sole();
        self::assertSame(ProjectFunding::STATUS_OPEN, $funding->status);
        self::assertTrue(
            LedgerEntry::query()->where('zahab_operation_reference', 'like', 'project-funding:'.$funding->id.':%')
                ->where('direction', LedgerEntry::DIRECTION_DEBIT)->exists(),
        );

        self::assertTrue(
            Proof::query()->where('owner_type', Proof::OWNER_PERSON)->where('owner_reference', self::VIEWER)
                ->where('origin_type', Proof::ORIGIN_PROJECT)->where('origin_reference', $project->public_reference)->exists(),
        );
    }

    public function test_running_the_seeder_twice_never_duplicates_anything(): void
    {
        (new BetaDemoSeeder)->run();
        (new BetaDemoSeeder)->run();

        $project = Project::query()->where('name', self::PROJECT_NAME)->sole();

        self::assertSame(1, Project::query()->where('name', self::PROJECT_NAME)->count());
        self::assertSame(1, ProjectTeamMember::query()->where('project_id', $project->id)->where('core_identity_reference', self::VIEWER)->count());
        self::assertSame(1, Need::query()->where('owner_type', Need::OWNER_PROJECT)->where('owner_reference', $project->id)->where('author_core_reference', self::VIEWER)->count());
        self::assertSame(1, Mission::query()->where('context_type', 'PROJECT')->where('context_reference', $project->public_reference)->where('created_by_core_reference', self::VIEWER)->count());
        self::assertSame(1, MissionAssignment::query()->where('core_identity_reference', self::VIEWER)->count());
        self::assertSame(1, Contribution::query()->where('type', Contribution::TYPE_INDIVIDUAL)->where('subject_reference', self::VIEWER)->count());
        self::assertSame(1, ContributionPayment::query()->count());
        self::assertSame(1, ProjectFunding::query()->where('project_id', $project->id)->count());
        self::assertSame(1, Proof::query()->where('owner_reference', self::VIEWER)->where('origin_reference', $project->public_reference)->count());

        $wallet = app(ZahabWalletService::class)->walletFor(ZahabWallet::SUBJECT_PERSON, self::VIEWER, self::VIEWER);
        $firstBalance = app(ZahabWalletService::class)->balance($wallet);
        $ledgerCountAfterTwoRuns = LedgerEntry::query()->count();

        (new BetaDemoSeeder)->run();

        self::assertSame($firstBalance, app(ZahabWalletService::class)->balance($wallet));
        self::assertSame($ledgerCountAfterTwoRuns, LedgerEntry::query()->count());
    }

    /**
     * Régression découverte pendant la vérification manuelle du parcours (BETA-READY-003) :
     * la Mission créée par le seed apparaît dans les « Cette semaine » de Mon espace avec
     * kind='PROJECTS' (coercion de ActivityFeedService::MISSION_CONTEXT_FILTER pour un contexte
     * PROJECT), mais sans la clé 'maturity' — absente uniquement pour les items Mission,
     * toujours présente pour un vrai Projet. La vue y accédait sans garde et plantait
     * (`Undefined array key "maturity"`), jamais exercé avant que ce seed ne place une Mission
     * en contexte Projet dans le fil hebdomadaire d'un compte réel.
     */
    public function test_the_viewer_can_open_mon_espace_without_the_page_crashing(): void
    {
        (new BetaDemoSeeder)->run();

        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.self::VIEWER, 'entite' => self::VIEWER, 'assurance' => 'AS1', 'expire_le' => '2026-08-20T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => self::VIEWER, 'type' => 'personne', 'libelle' => 'Membre DG Afrique', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => self::VIEWER, 'assurance' => 'AS1', 'expire_le' => '2026-08-20T23:59:00+00:00']),
        ]);
        $this->post('/connexion', ['identifier' => self::VIEWER, 'secret' => 'secret'])->assertRedirect('/espace');

        $this->get(route('member.space'))->assertOk();
    }
}
