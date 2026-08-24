<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Projects\ProjectFundingContributionService;
use App\Application\Projects\ProjectFundingService;
use App\Application\Zahab\ZahabWalletService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\LedgerEntry;
use App\Models\Project;
use App\Models\ProjectFunding;
use App\Models\ZahabWallet;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraProgramMembership;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * PROJECT-FUNDING-002 — relie la déclaration CAP-063 (ProjectFunding, inchangée) aux primitives
 * ZAHAB déjà prouvées (ZAHAB-001/002) : Personne → Wallet ZUMRA porteur du Projet, jamais un
 * Wallet Projet, jamais une deuxième vérité financière (le Ledger reste seul juge).
 */
final class ProjectFundingZahabTest extends TestCase
{
    use RefreshDatabase;

    // ===== 1-6 : flux de base, deux jambes, soldes exacts =====

    public function test_a_contribution_debits_the_contributor_and_credits_the_carrier_zumra(): void
    {
        $group = $this->group('IDN-LEADER');
        $project = $this->groupProject($group, Project::STATUS_ADOPTED);
        $funding = $this->openFunding($project, 'IDN-LEADER');
        $this->creditPersonalWallet('IDN-CONTRIBUTOR', 5000);

        app(ProjectFundingContributionService::class)->contribute($funding, $project, 'IDN-CONTRIBUTOR', 3000, 'token-basic-001');

        self::assertSame(2000, app(ZahabWalletService::class)->balance($this->personalWallet('IDN-CONTRIBUTOR')));
        self::assertSame(3000, app(ZahabWalletService::class)->balance($this->zumraWallet($group)));
    }

    public function test_the_operation_reference_is_shared_by_both_legs(): void
    {
        $group = $this->group('IDN-LEADER');
        $project = $this->groupProject($group, Project::STATUS_ADOPTED);
        $funding = $this->openFunding($project, 'IDN-LEADER');
        $this->creditPersonalWallet('IDN-CONTRIBUTOR', 5000);

        app(ProjectFundingContributionService::class)->contribute($funding, $project, 'IDN-CONTRIBUTOR', 3000, 'token-opref-001');

        $legs = LedgerEntry::query()->where('zahab_operation_reference', 'project-funding:'.$funding->id.':token-opref-001')->get();
        self::assertSame(2, $legs->count());
        self::assertSame(1, $legs->where('direction', LedgerEntry::DIRECTION_DEBIT)->count());
        self::assertSame(1, $legs->where('direction', LedgerEntry::DIRECTION_CREDIT)->count());
    }

    public function test_the_collected_amount_is_derived_from_the_ledger(): void
    {
        $group = $this->group('IDN-LEADER');
        $project = $this->groupProject($group, Project::STATUS_ADOPTED);
        $funding = $this->openFunding($project, 'IDN-LEADER', 10000);
        $this->creditPersonalWallet('IDN-CONTRIBUTOR', 8000);

        $service = app(ProjectFundingContributionService::class);
        self::assertSame(0, $service->collectedAmount($funding, $project));

        $service->contribute($funding, $project, 'IDN-CONTRIBUTOR', 3000, 'token-collected-001');

        self::assertSame(3000, $service->collectedAmount($funding->fresh(), $project));
        self::assertSame(7000, $service->remainingAmount($funding->fresh(), $project));
    }

    // ===== 7-8 : fonds insuffisants =====

    public function test_insufficient_funds_produce_no_movement_at_all(): void
    {
        $group = $this->group('IDN-LEADER');
        $project = $this->groupProject($group, Project::STATUS_ADOPTED);
        $funding = $this->openFunding($project, 'IDN-LEADER', 10000);
        $this->creditPersonalWallet('IDN-CONTRIBUTOR', 100);

        $this->assertAborts(409, fn () => app(ProjectFundingContributionService::class)->contribute($funding, $project, 'IDN-CONTRIBUTOR', 500, 'token-insufficient-001'));

        self::assertSame(100, app(ZahabWalletService::class)->balance($this->personalWallet('IDN-CONTRIBUTOR')));
        self::assertSame(0, app(ProjectFundingContributionService::class)->collectedAmount($funding, $project));
        self::assertSame(0, LedgerEntry::query()->where('zahab_operation_reference', 'like', 'project-funding:%')->count());
    }

    // ===== 9 : financement fermé =====

    public function test_a_closed_declaration_refuses_further_contributions(): void
    {
        $group = $this->group('IDN-LEADER');
        $project = $this->groupProject($group, Project::STATUS_ADOPTED);
        $funding = $this->openFunding($project, 'IDN-LEADER');
        app(ProjectFundingService::class)->close($funding, $project, 'IDN-LEADER', null);
        $this->creditPersonalWallet('IDN-CONTRIBUTOR', 5000);

        $this->assertAborts(409, fn () => app(ProjectFundingContributionService::class)->contribute($funding->fresh(), $project, 'IDN-CONTRIBUTOR', 1000, 'token-closed-001'));
        self::assertSame(0, app(ZahabWalletService::class)->balance($this->zumraWallet($group)));
    }

    public function test_a_cancelled_declaration_refuses_further_contributions(): void
    {
        $group = $this->group('IDN-LEADER');
        $project = $this->groupProject($group, Project::STATUS_ADOPTED);
        $funding = $this->openFunding($project, 'IDN-LEADER');
        app(ProjectFundingService::class)->cancel($funding, $project, 'IDN-LEADER', null);
        $this->creditPersonalWallet('IDN-CONTRIBUTOR', 5000);

        $this->assertAborts(409, fn () => app(ProjectFundingContributionService::class)->contribute($funding->fresh(), $project, 'IDN-CONTRIBUTOR', 1000, 'token-cancelled-001'));
    }

    // ===== 10 : dépassement de cible refusé =====

    public function test_a_contribution_exceeding_the_target_is_refused(): void
    {
        $group = $this->group('IDN-LEADER');
        $project = $this->groupProject($group, Project::STATUS_ADOPTED);
        $funding = $this->openFunding($project, 'IDN-LEADER', 100000);
        app(ProjectFundingContributionService::class)->contribute($funding, $project, $this->fundedContributor('IDN-FIRST', 95000), 95000, 'token-overshoot-setup-001');
        $this->creditPersonalWallet('IDN-SECOND', 20000);

        $this->assertAborts(422, fn () => app(ProjectFundingContributionService::class)->contribute($funding->fresh(), $project, 'IDN-SECOND', 10000, 'token-overshoot-001'));

        self::assertSame(20000, app(ZahabWalletService::class)->balance($this->personalWallet('IDN-SECOND')));
        self::assertSame(95000, app(ProjectFundingContributionService::class)->collectedAmount($funding->fresh(), $project));
    }

    // ===== 11 : atteinte exacte de la cible =====

    public function test_reaching_the_target_exactly_marks_the_declaration_funded(): void
    {
        $group = $this->group('IDN-LEADER');
        $project = $this->groupProject($group, Project::STATUS_ADOPTED);
        $funding = $this->openFunding($project, 'IDN-LEADER', 5000);
        $this->creditPersonalWallet('IDN-CONTRIBUTOR', 5000);

        app(ProjectFundingContributionService::class)->contribute($funding, $project, 'IDN-CONTRIBUTOR', 5000, 'token-exact-001');

        self::assertSame(ProjectFunding::STATUS_FUNDED, $funding->fresh()->status);
        self::assertNotNull($funding->fresh()->closed_at);
    }

    public function test_a_partial_contribution_leaves_the_declaration_open(): void
    {
        $group = $this->group('IDN-LEADER');
        $project = $this->groupProject($group, Project::STATUS_ADOPTED);
        $funding = $this->openFunding($project, 'IDN-LEADER', 5000);
        $this->creditPersonalWallet('IDN-CONTRIBUTOR', 5000);

        app(ProjectFundingContributionService::class)->contribute($funding, $project, 'IDN-CONTRIBUTOR', 2000, 'token-partial-001');

        self::assertSame(ProjectFunding::STATUS_OPEN, $funding->fresh()->status);
    }

    // ===== 12-13 : idempotence, double clic =====

    public function test_replaying_the_same_contribution_token_never_doubles_the_transfer(): void
    {
        $group = $this->group('IDN-LEADER');
        $project = $this->groupProject($group, Project::STATUS_ADOPTED);
        $funding = $this->openFunding($project, 'IDN-LEADER', 10000);
        $this->creditPersonalWallet('IDN-CONTRIBUTOR', 5000);

        $contributions = app(ProjectFundingContributionService::class);
        $contributions->contribute($funding, $project, 'IDN-CONTRIBUTOR', 3000, 'token-replay-001');
        $contributions->contribute($funding->fresh(), $project, 'IDN-CONTRIBUTOR', 3000, 'token-replay-001');
        $contributions->contribute($funding->fresh(), $project, 'IDN-CONTRIBUTOR', 3000, 'token-replay-001');

        self::assertSame(2000, app(ZahabWalletService::class)->balance($this->personalWallet('IDN-CONTRIBUTOR')));
        self::assertSame(3000, app(ZahabWalletService::class)->balance($this->zumraWallet($group)));
        self::assertSame(2, LedgerEntry::query()->where('zahab_operation_reference', 'like', 'project-funding:'.$funding->id.':%')->count());
    }

    public function test_a_double_submitted_http_request_credits_only_once(): void
    {
        $group = $this->group('IDN-LEADER');
        $project = $this->groupProject($group, Project::STATUS_ADOPTED);
        $funding = $this->openFunding($project, 'IDN-LEADER', 10000);
        $this->creditPersonalWallet('IDN-CONTRIBUTOR', 5000);
        $this->signIn('IDN-CONTRIBUTOR');

        $payload = ['amount' => 3000, 'contribution_token' => 'token-http-double-click-001'];
        $this->post(route('projects.funding.contribute', $project), $payload)->assertRedirect();
        $this->post(route('projects.funding.contribute', $project), $payload)->assertRedirect();

        self::assertSame(2000, app(ZahabWalletService::class)->balance($this->personalWallet('IDN-CONTRIBUTOR')));
        self::assertSame(3000, app(ZahabWalletService::class)->balance($this->zumraWallet($group)));
    }

    // ===== 14 : non authentifié =====

    public function test_an_unauthenticated_visitor_cannot_contribute(): void
    {
        $group = $this->group('IDN-LEADER');
        $project = $this->groupProject($group, Project::STATUS_ADOPTED);
        $this->openFunding($project, 'IDN-LEADER');

        $this->post(route('projects.funding.contribute', $project), ['amount' => 1000, 'contribution_token' => 'token-anon-001'])
            ->assertRedirect(route('login', ['next' => '/projets/'.$project->public_reference.'/financement/contribuer']));

        self::assertSame(0, LedgerEntry::query()->where('zahab_operation_reference', 'like', 'project-funding:%')->count());
    }

    // ===== 15 : aucun Wallet Projet =====

    public function test_no_project_wallet_subject_is_ever_created(): void
    {
        $group = $this->group('IDN-LEADER');
        $project = $this->groupProject($group, Project::STATUS_ADOPTED);
        $funding = $this->openFunding($project, 'IDN-LEADER');
        $this->creditPersonalWallet('IDN-CONTRIBUTOR', 5000);

        app(ProjectFundingContributionService::class)->contribute($funding, $project, 'IDN-CONTRIBUTOR', 1000, 'token-noprojectwallet-001');

        self::assertNotContains('PROJECT', ZahabWallet::SUBJECTS);
        self::assertSame(0, ZahabWallet::query()->where('subject_type', 'PROJECT')->count());
        self::assertSame(0, ZahabWallet::query()->where('subject_reference', $project->id)->count());
    }

    // ===== 16 : aucune table financière parallèle =====

    public function test_no_parallel_financial_movement_table_was_introduced(): void
    {
        self::assertFalse(Schema::hasTable('dg_project_funding_contributions'));
        self::assertFalse(Schema::hasTable('dg_project_funding_movements'));
        self::assertFalse(Schema::hasTable('dg_project_wallets'));
    }

    // ===== 17 : historique correct =====

    public function test_the_history_lists_contributor_amount_and_date(): void
    {
        $group = $this->group('IDN-LEADER');
        $project = $this->groupProject($group, Project::STATUS_ADOPTED);
        $funding = $this->openFunding($project, 'IDN-LEADER', 10000);
        $this->creditPersonalWallet('IDN-CONTRIBUTOR', 5000);

        app(ProjectFundingContributionService::class)->contribute($funding, $project, 'IDN-CONTRIBUTOR', 3000, 'token-history-001');

        $history = app(ProjectFundingContributionService::class)->history($funding->fresh());
        self::assertCount(1, $history);
        self::assertSame('IDN-CONTRIBUTOR', $history->first()->subject_reference);
        self::assertSame(3000, $history->first()->amount);
        self::assertNotNull($history->first()->occurred_at);
    }

    // ===== 18 : CAP-063 déclaratif inchangé =====

    public function test_the_existing_declarative_funding_lifecycle_still_works_unchanged(): void
    {
        $project = $this->personProject(Project::STATUS_ADOPTED);
        $service = app(ProjectFundingService::class);

        $funding = $service->create($project, 'IDN-OWNER', [
            'target_amount' => 500000, 'currency' => 'XOF',
            'purpose' => 'Financer l’achat de matériel nécessaire à la poursuite du projet.',
            'intended_use' => 'Acquisition de matériel informatique partagé par l’équipe du projet.',
            'conditions' => null,
        ]);
        self::assertSame(ProjectFunding::STATUS_OPEN, $funding->status);

        $updated = $service->update($funding, $project, 'IDN-OWNER', [
            'target_amount' => 600000, 'currency' => 'XOF',
            'purpose' => 'Financer l’achat de matériel nécessaire à la poursuite du projet, révisé.',
            'intended_use' => 'Acquisition de matériel informatique partagé par l’équipe du projet.',
            'conditions' => null,
        ]);
        self::assertSame(600000, $updated->target_amount);

        $closed = $service->close($updated, $project, 'IDN-OWNER', 'Clôturé sans financement ZAHAB.');
        self::assertSame(ProjectFunding::STATUS_CLOSED, $closed->status);
    }

    // ===== Compléments : autorisation / ancrage / devise / ZUMRA suspendue =====

    public function test_a_project_without_a_zumra_anchor_cannot_be_funded_in_zahab(): void
    {
        // Anchor volontairement absent (ancien Projet, avant PROJET-ZUMRA-INVARIANT-001).
        $project = Project::query()->create([
            'public_reference' => (string) Str::uuid(), 'owner_type' => Project::OWNER_PERSON,
            'owner_reference' => 'IDN-OWNER', 'initiator_core_reference' => 'IDN-OWNER', 'zumra_group_id' => null,
            'name' => 'Projet historique', 'summary' => 'Un projet antérieur à l’ancrage ZUMRA obligatoire.',
            'problem' => 'Problème réel.', 'proposed_solution' => 'Solution progressive.', 'beneficiaries' => 'Communauté.',
            'domain' => 'DIGITAL', 'participation_mode' => 'HYBRID', 'objectives' => [], 'required_capabilities' => [],
            'required_resources' => [], 'risks' => [], 'property_regime' => 'PERSONAL_SUPPORTED',
            'visibility' => Project::VISIBILITY_PUBLIC, 'status' => Project::STATUS_ADOPTED, 'maturity' => 'IDEA',
            'decided_by_core_reference' => 'IDN-OWNER', 'adopted_at' => now(),
        ]);
        $funding = $this->openFunding($project, 'IDN-OWNER');
        $this->creditPersonalWallet('IDN-CONTRIBUTOR', 5000);

        $this->assertAborts(409, fn () => app(ProjectFundingContributionService::class)->contribute($funding, $project, 'IDN-CONTRIBUTOR', 1000, 'token-noanchor-001'));
    }

    public function test_a_declaration_not_expressed_in_xof_cannot_be_funded_in_zahab(): void
    {
        $group = $this->group('IDN-LEADER');
        $project = $this->groupProject($group, Project::STATUS_ADOPTED);
        $funding = app(ProjectFundingService::class)->create($project, 'IDN-LEADER', [
            'target_amount' => 1000, 'currency' => 'EUR', 'purpose' => 'Financer un besoin exprimé hors ZAHAB pour ce test.',
            'intended_use' => 'Test de la barrière de devise.', 'conditions' => null,
        ]);
        $this->creditPersonalWallet('IDN-CONTRIBUTOR', 5000);

        $this->assertAborts(422, fn () => app(ProjectFundingContributionService::class)->contribute($funding, $project, 'IDN-CONTRIBUTOR', 1000, 'token-currency-001'));
    }

    public function test_a_suspended_carrier_zumra_cannot_receive_funding(): void
    {
        $group = $this->group('IDN-LEADER');
        $project = $this->groupProject($group, Project::STATUS_ADOPTED);
        $funding = $this->openFunding($project, 'IDN-LEADER');
        $this->creditPersonalWallet('IDN-CONTRIBUTOR', 5000);
        $group->update(['state' => ZumraGroup::STATE_SUSPENDED]);

        $this->assertAborts(409, fn () => app(ProjectFundingContributionService::class)->contribute($funding, $project, 'IDN-CONTRIBUTOR', 1000, 'token-suspended-001'));
    }

    public function test_the_amount_must_be_a_positive_integer(): void
    {
        $group = $this->group('IDN-LEADER');
        $project = $this->groupProject($group, Project::STATUS_ADOPTED);
        $funding = $this->openFunding($project, 'IDN-LEADER');
        $this->creditPersonalWallet('IDN-CONTRIBUTOR', 5000);

        $this->assertAborts(422, fn () => app(ProjectFundingContributionService::class)->contribute($funding, $project, 'IDN-CONTRIBUTOR', 0, 'token-zero-001'));
        $this->assertAborts(422, fn () => app(ProjectFundingContributionService::class)->contribute($funding, $project, 'IDN-CONTRIBUTOR', -100, 'token-negative-001'));
    }

    public function test_the_full_http_journey_shows_the_credit_on_the_project_page(): void
    {
        $group = $this->group('IDN-LEADER');
        $project = $this->groupProject($group, Project::STATUS_ADOPTED);
        $this->openFunding($project, 'IDN-LEADER', 10000);
        $this->creditPersonalWallet('IDN-CONTRIBUTOR', 5000);
        $this->signIn('IDN-CONTRIBUTOR');

        $this->post(route('projects.funding.contribute', $project), ['amount' => 3000, 'contribution_token' => 'token-journey-001'])
            ->assertRedirect();

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('3 000', false)
            ->assertSee('7 000', false);
    }

    // ===== Helpers =====

    private function assertAborts(int $status, callable $fn): void
    {
        try {
            $fn();
            self::fail("Expected an HttpException with status {$status} but none was thrown.");
        } catch (HttpException $e) {
            self::assertSame($status, $e->getStatusCode());
        } catch (ModelNotFoundException $e) {
            self::assertSame(404, $status);
        }
    }

    private function programMember(string $identity): void
    {
        $charter = ZumraCharter::query()->firstOrCreate(['version' => '2026.1'], ['title' => 'Charte ZUMRA', 'body' => str_repeat('Respect et transmission. ', 8), 'content_hash' => hash('sha256', 'charter'), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()]);
        ZumraProgramMembership::query()->firstOrCreate(['core_identity_reference' => $identity], ['status' => ZumraProgramMembership::STATUS_ACTIVE, 'accepted_charter_id' => $charter->id, 'accepted_charter_version' => $charter->version, 'accepted_charter_hash' => $charter->content_hash, 'charter_accepted_at' => now(), 'submitted_at' => now(), 'activated_at' => now()]);
    }

    private function group(string $leader): ZumraGroup
    {
        $this->programMember($leader);

        return app(ZumraGroupService::class)->create($leader, [
            'name' => 'ZUMRA financement '.Str::random(6), 'domain' => 'Numérique',
            'founding_objective' => 'Former une équipe qui transmet les outils numériques et réalise des solutions utiles.',
            'participation_mode' => 'HYBRID', 'internal_charter' => 'Chaque membre respecte la dignité et la hiérarchie.',
            'assume_primary_lead' => true,
        ], 3);
    }

    /** Comme ProjectFundingTest::groupProject(), MAIS avec zumra_group_id renseigné (art. « Cas A » du mandat — ancrage canonique déjà en place pour tout nouveau Projet, PROJET-ZUMRA-INVARIANT-001). */
    private function groupProject(ZumraGroup $group, string $status): Project
    {
        return Project::query()->create([
            'public_reference' => (string) Str::uuid(), 'owner_type' => Project::OWNER_GROUP,
            'owner_reference' => $group->id, 'zumra_group_id' => $group->id, 'initiator_core_reference' => 'IDN-LEADER',
            'name' => 'Projet du groupe', 'summary' => 'Un projet concret porté par la ZUMRA.',
            'problem' => 'Un problème réel à résoudre collectivement.', 'proposed_solution' => 'Une solution progressive.',
            'beneficiaries' => 'Communauté locale.', 'domain' => 'DIGITAL', 'participation_mode' => 'HYBRID',
            'objectives' => ['Agir'], 'required_capabilities' => ['Coordination'], 'required_resources' => ['Temps'], 'risks' => [],
            'property_regime' => 'ZUMRA_COLLECTIVE', 'visibility' => Project::VISIBILITY_PUBLIC, 'status' => $status, 'maturity' => 'IDEA',
            'decided_by_core_reference' => 'IDN-LEADER', 'adopted_at' => now(),
        ]);
    }

    private function personProject(string $status): Project
    {
        $group = $this->group('IDN-OWNER');

        return Project::query()->create([
            'public_reference' => (string) Str::uuid(), 'owner_type' => Project::OWNER_PERSON,
            'owner_reference' => 'IDN-OWNER', 'zumra_group_id' => $group->id, 'initiator_core_reference' => 'IDN-OWNER',
            'name' => 'Atelier numérique communautaire', 'summary' => 'Un projet concret destiné à construire des capacités utiles.',
            'problem' => 'Des personnes motivées manquent de cadre pratique.', 'proposed_solution' => 'Créer un atelier progressif.',
            'beneficiaries' => 'Jeunes débutants.', 'domain' => 'DIGITAL', 'participation_mode' => 'HYBRID',
            'objectives' => ['Former une équipe'], 'required_capabilities' => ['Formation numérique'],
            'required_resources' => ['Ordinateurs'], 'risks' => [], 'property_regime' => 'PERSONAL_SUPPORTED',
            'visibility' => Project::VISIBILITY_PUBLIC, 'status' => $status, 'maturity' => 'IDEA',
            'decided_by_core_reference' => 'IDN-OWNER', 'adopted_at' => now(),
        ]);
    }

    private function openFunding(Project $project, string $actor, int $target = 5000): ProjectFunding
    {
        return app(ProjectFundingService::class)->create($project, $actor, [
            'target_amount' => $target, 'currency' => 'XOF',
            'purpose' => 'Financer un besoin réel du projet pour ce test.',
            'intended_use' => 'Usage précisé pour ce test.', 'conditions' => null,
        ]);
    }

    private function personalWallet(string $identity): ZahabWallet
    {
        return app(ZahabWalletService::class)->walletFor(ZahabWallet::SUBJECT_PERSON, $identity, $identity);
    }

    private function zumraWallet(ZumraGroup $group): ZahabWallet
    {
        return app(ZahabWalletService::class)->walletFor(ZahabWallet::SUBJECT_ZUMRA_GROUP, (string) $group->id, 'IDN-ADMIN');
    }

    private function creditPersonalWallet(string $identity, int $amount): void
    {
        $wallet = $this->personalWallet($identity);
        app(ZahabWalletService::class)->credit($wallet, $amount, ZahabWalletService::REASON_AID, 'test-seed-credit:'.$identity.':'.Str::random(8), 'IDN-ADMIN');
    }

    /** Crédite $identity puis retourne son identité, pour préparer une contribution qui atteint exactement une cible. */
    private function fundedContributor(string $identity, int $amount): string
    {
        $this->creditPersonalWallet($identity, $amount);

        return $identity;
    }

    private function signIn(string $reference): void
    {
        Http::fake(function (ClientRequest $request) use ($reference) {
            $url = $request->url();
            $method = $request->method();
            if ($method === 'POST' && str_ends_with(rtrim($url, '/'), '/sessions')) {
                return Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-09-16T23:59:00+00:00'], 201);
            }
            if (str_ends_with($url, '/sessions/current')) {
                return Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-09-16T23:59:00+00:00']);
            }
            if (str_contains($url, '/identites/')) {
                return Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']);
            }

            return Http::response(['error' => 'UNEXPECTED_TEST_REQUEST'], 500);
        });

        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
