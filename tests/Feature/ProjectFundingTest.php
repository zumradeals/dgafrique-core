<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Projects\ProjectFundingService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\Contribution;
use App\Models\ContributionPayment;
use App\Models\LedgerEntry;
use App\Models\Organization;
use App\Models\Partnership;
use App\Models\Project;
use App\Models\ProjectFunding;
use App\Models\ProjectTeamMember;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraGroupRole;
use App\Models\ZumraProgramMembership;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * CAP-063 — Financement de projet, V1 strictement DÉCLARATIVE (art. 15.3). ProjectFunding décrit un
 * besoin financier, jamais un mouvement d'argent : aucun paiement, aucune collecte, aucun
 * décaissement. Réutilise ProjectAuthority::canDecide, aucune autorité parallèle.
 */
final class ProjectFundingTest extends TestCase
{
    use RefreshDatabase;

    // ===== Création — autorisation =====

    public function test_the_owner_can_declare_a_financial_need_for_an_adopted_project(): void
    {
        $project = $this->project(Project::STATUS_ADOPTED);

        $funding = app(ProjectFundingService::class)->create($project, 'IDN-OWNER', $this->payload());

        self::assertSame(ProjectFunding::STATUS_OPEN, $funding->status);
        self::assertSame(500000, $funding->target_amount);
        self::assertSame('XOF', $funding->currency);
        self::assertSame('IDN-OWNER', $funding->author_core_reference);
        self::assertNotNull($funding->opened_at);
    }

    public function test_a_stranger_cannot_declare_a_financial_need(): void
    {
        $project = $this->project(Project::STATUS_ADOPTED);

        $this->assertAborts(403, fn () => app(ProjectFundingService::class)->create($project, 'IDN-STRANGER', $this->payload()));
    }

    public function test_a_zumra_leader_can_declare_a_financial_need_for_the_groups_adopted_project(): void
    {
        $group = $this->group('IDN-LEADER');
        $project = $this->groupProject($group, Project::STATUS_ADOPTED);

        $funding = app(ProjectFundingService::class)->create($project, 'IDN-LEADER', $this->payload());

        self::assertSame(ProjectFunding::STATUS_OPEN, $funding->status);
    }

    public function test_a_non_leader_group_member_cannot_declare_a_financial_need(): void
    {
        $group = $this->group('IDN-LEADER');
        $project = $this->groupProject($group, Project::STATUS_ADOPTED);
        $this->programMember('IDN-MEMBER');
        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id, 'core_identity_reference' => 'IDN-MEMBER',
            'status' => ZumraGroupMembership::STATUS_ACTIVE, 'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => 'IDN-MEMBER', 'joined_at' => now(),
        ]);

        $this->assertAborts(403, fn () => app(ProjectFundingService::class)->create($project, 'IDN-MEMBER', $this->payload()));
    }

    // ===== Éligibilité du Projet =====

    public function test_a_proposed_project_is_not_eligible(): void
    {
        $project = $this->project(Project::STATUS_PROPOSED);

        $this->assertAborts(409, fn () => app(ProjectFundingService::class)->create($project, 'IDN-OWNER', $this->payload()));
    }

    public function test_an_archived_project_is_not_eligible(): void
    {
        $project = $this->project(Project::STATUS_ARCHIVED);

        $this->assertAborts(409, fn () => app(ProjectFundingService::class)->create($project, 'IDN-OWNER', $this->payload()));
    }

    public function test_a_completed_project_is_not_eligible(): void
    {
        $project = $this->project(Project::STATUS_COMPLETED);

        $this->assertAborts(409, fn () => app(ProjectFundingService::class)->create($project, 'IDN-OWNER', $this->payload()));
    }

    public function test_an_in_progress_project_is_eligible(): void
    {
        $project = $this->project(Project::STATUS_IN_PROGRESS);

        $funding = app(ProjectFundingService::class)->create($project, 'IDN-OWNER', $this->payload());

        self::assertSame(ProjectFunding::STATUS_OPEN, $funding->status);
    }

    // ===== Validation des montants et de la devise (frontière HTTP réelle) =====

    public function test_target_amount_must_be_a_positive_integer(): void
    {
        $project = $this->project(Project::STATUS_ADOPTED);
        $this->signIn('IDN-OWNER');

        $this->post(route('projects.funding.store', $project), array_merge($this->payload(), ['target_amount' => 0]))
            ->assertSessionHasErrors('target_amount');
        self::assertSame(0, ProjectFunding::query()->count());
    }

    public function test_target_amount_rejects_a_non_integer_string(): void
    {
        $project = $this->project(Project::STATUS_ADOPTED);
        $this->signIn('IDN-OWNER');

        $this->post(route('projects.funding.store', $project), array_merge($this->payload(), ['target_amount' => 'beaucoup']))
            ->assertSessionHasErrors('target_amount');
        self::assertSame(0, ProjectFunding::query()->count());
    }

    public function test_currency_must_be_exactly_three_characters(): void
    {
        $project = $this->project(Project::STATUS_ADOPTED);
        $this->signIn('IDN-OWNER');

        $this->post(route('projects.funding.store', $project), array_merge($this->payload(), ['currency' => 'FRANCS']))
            ->assertSessionHasErrors('currency');
        self::assertSame(0, ProjectFunding::query()->count());
    }

    // ===== Mise à jour =====

    public function test_the_owner_can_update_an_open_declaration(): void
    {
        $project = $this->project(Project::STATUS_ADOPTED);
        $funding = app(ProjectFundingService::class)->create($project, 'IDN-OWNER', $this->payload());

        $updated = app(ProjectFundingService::class)->update($funding, $project, 'IDN-OWNER', array_merge($this->payload(), ['target_amount' => 750000]));

        self::assertSame(750000, $updated->target_amount);
    }

    public function test_a_stranger_cannot_update_a_declaration(): void
    {
        $project = $this->project(Project::STATUS_ADOPTED);
        $funding = app(ProjectFundingService::class)->create($project, 'IDN-OWNER', $this->payload());

        $this->assertAborts(403, fn () => app(ProjectFundingService::class)->update($funding, $project, 'IDN-STRANGER', $this->payload()));
    }

    public function test_a_closed_declaration_cannot_be_updated(): void
    {
        $project = $this->project(Project::STATUS_ADOPTED);
        $funding = app(ProjectFundingService::class)->create($project, 'IDN-OWNER', $this->payload());
        app(ProjectFundingService::class)->close($funding, $project, 'IDN-OWNER', null);

        $this->assertAborts(409, fn () => app(ProjectFundingService::class)->update($funding->refresh(), $project, 'IDN-OWNER', $this->payload()));
    }

    // ===== Clôture / annulation =====

    public function test_the_owner_can_close_an_open_declaration(): void
    {
        $project = $this->project(Project::STATUS_ADOPTED);
        $funding = app(ProjectFundingService::class)->create($project, 'IDN-OWNER', $this->payload());

        $closed = app(ProjectFundingService::class)->close($funding, $project, 'IDN-OWNER', 'Besoin couvert autrement.');

        self::assertSame(ProjectFunding::STATUS_CLOSED, $closed->status);
        self::assertNotNull($closed->closed_at);
        self::assertSame('IDN-OWNER', $closed->decided_by_core_reference);
    }

    public function test_the_owner_can_cancel_an_open_declaration(): void
    {
        $project = $this->project(Project::STATUS_ADOPTED);
        $funding = app(ProjectFundingService::class)->create($project, 'IDN-OWNER', $this->payload());

        $cancelled = app(ProjectFundingService::class)->cancel($funding, $project, 'IDN-OWNER', 'Déclarée par erreur.');

        self::assertSame(ProjectFunding::STATUS_CANCELLED, $cancelled->status);
        self::assertNotNull($cancelled->cancelled_at);
    }

    public function test_closing_an_already_closed_declaration_is_refused(): void
    {
        $project = $this->project(Project::STATUS_ADOPTED);
        $funding = app(ProjectFundingService::class)->create($project, 'IDN-OWNER', $this->payload());
        $closed = app(ProjectFundingService::class)->close($funding, $project, 'IDN-OWNER', null);

        $this->assertAborts(409, fn () => app(ProjectFundingService::class)->close($closed, $project, 'IDN-OWNER', null));
    }

    public function test_cancelling_an_already_cancelled_declaration_is_refused(): void
    {
        $project = $this->project(Project::STATUS_ADOPTED);
        $funding = app(ProjectFundingService::class)->create($project, 'IDN-OWNER', $this->payload());
        $cancelled = app(ProjectFundingService::class)->cancel($funding, $project, 'IDN-OWNER', null);

        $this->assertAborts(409, fn () => app(ProjectFundingService::class)->cancel($cancelled, $project, 'IDN-OWNER', null));
    }

    public function test_a_stranger_cannot_close_a_declaration(): void
    {
        $project = $this->project(Project::STATUS_ADOPTED);
        $funding = app(ProjectFundingService::class)->create($project, 'IDN-OWNER', $this->payload());

        $this->assertAborts(403, fn () => app(ProjectFundingService::class)->close($funding, $project, 'IDN-STRANGER', null));
    }

    // ===== Unicité / concurrence =====

    public function test_only_one_open_declaration_per_project_at_a_time(): void
    {
        $project = $this->project(Project::STATUS_ADOPTED);
        app(ProjectFundingService::class)->create($project, 'IDN-OWNER', $this->payload());

        $this->assertAborts(409, fn () => app(ProjectFundingService::class)->create($project, 'IDN-OWNER', $this->payload()));
    }

    public function test_a_new_declaration_is_possible_after_closing_the_previous_one(): void
    {
        $project = $this->project(Project::STATUS_ADOPTED);
        $first = app(ProjectFundingService::class)->create($project, 'IDN-OWNER', $this->payload());
        app(ProjectFundingService::class)->close($first, $project, 'IDN-OWNER', null);

        $second = app(ProjectFundingService::class)->create($project, 'IDN-OWNER', $this->payload());

        self::assertSame(ProjectFunding::STATUS_OPEN, $second->status);
        self::assertNotSame($first->id, $second->id);
    }

    public function test_the_partial_unique_index_itself_rejects_a_concurrent_duplicate_open_row(): void
    {
        $project = $this->project(Project::STATUS_ADOPTED);
        app(ProjectFundingService::class)->create($project, 'IDN-OWNER', $this->payload());

        $this->expectException(QueryException::class);
        DB::table('dg_project_fundings')->insert([
            'id' => (string) Str::uuid(), 'project_id' => $project->id, 'status' => ProjectFunding::STATUS_OPEN,
            'target_amount' => 100000, 'currency' => 'XOF', 'purpose' => 'Autre besoin réel et suffisamment détaillé.',
            'intended_use' => 'Un usage prévu suffisamment détaillé pour être compréhensible.',
            'author_core_reference' => 'IDN-OWNER', 'opened_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // ===== Visibilité =====

    public function test_a_private_project_funding_stays_invisible_to_a_stranger(): void
    {
        $project = $this->project(Project::STATUS_ADOPTED, Project::VISIBILITY_PRIVATE);
        app(ProjectFundingService::class)->create($project, 'IDN-OWNER', $this->payload());

        self::assertFalse(app(ProjectFundingService::class)->canView($project, 'IDN-STRANGER'));
        self::assertTrue(app(ProjectFundingService::class)->canView($project, 'IDN-OWNER'));
    }

    public function test_a_group_visibility_project_funding_is_visible_to_an_active_group_member_only(): void
    {
        $group = $this->group('IDN-LEADER');
        $project = $this->groupProject($group, Project::STATUS_ADOPTED, Project::VISIBILITY_GROUP);
        app(ProjectFundingService::class)->create($project, 'IDN-LEADER', $this->payload());
        $this->programMember('IDN-MEMBER');
        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id, 'core_identity_reference' => 'IDN-MEMBER',
            'status' => ZumraGroupMembership::STATUS_ACTIVE, 'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => 'IDN-MEMBER', 'joined_at' => now(),
        ]);
        $this->programMember('IDN-OUTSIDER');

        self::assertTrue(app(ProjectFundingService::class)->canView($project, 'IDN-MEMBER'));
        self::assertFalse(app(ProjectFundingService::class)->canView($project, 'IDN-OUTSIDER'));
    }

    // ===== Frontières CAP-061 / CAP-062 / GeniusPay =====

    public function test_declaring_a_financial_need_creates_no_contribution_or_payment(): void
    {
        $project = $this->project(Project::STATUS_ADOPTED);

        app(ProjectFundingService::class)->create($project, 'IDN-OWNER', $this->payload());

        self::assertSame(0, Contribution::query()->count());
        self::assertSame(0, ContributionPayment::query()->count());
    }

    public function test_declaring_a_financial_need_creates_no_ledger_entry(): void
    {
        $project = $this->project(Project::STATUS_ADOPTED);

        $funding = app(ProjectFundingService::class)->create($project, 'IDN-OWNER', $this->payload());
        app(ProjectFundingService::class)->close($funding, $project, 'IDN-OWNER', null);

        self::assertSame(0, LedgerEntry::query()->count());
    }

    public function test_no_outbound_http_call_is_made_when_declaring_a_financial_need(): void
    {
        Http::fake(fn () => Http::response(['error' => 'UNEXPECTED_OUTBOUND_CALL'], 500));
        $project = $this->project(Project::STATUS_ADOPTED);

        app(ProjectFundingService::class)->create($project, 'IDN-OWNER', $this->payload());

        Http::assertNothingSent();
    }

    // ===== Invariants de propriété =====

    public function test_declaring_a_financial_need_never_changes_project_ownership(): void
    {
        $project = $this->project(Project::STATUS_ADOPTED);
        $ownerBefore = $project->owner_reference;
        $ownerTypeBefore = $project->owner_type;

        app(ProjectFundingService::class)->create($project, 'IDN-OWNER', $this->payload());

        self::assertSame($ownerBefore, $project->refresh()->owner_reference);
        self::assertSame($ownerTypeBefore, $project->refresh()->owner_type);
    }

    public function test_declaring_a_financial_need_creates_no_team_member_partnership_or_organization(): void
    {
        $project = $this->project(Project::STATUS_ADOPTED);

        $funding = app(ProjectFundingService::class)->create($project, 'IDN-OWNER', $this->payload());
        app(ProjectFundingService::class)->cancel($funding, $project, 'IDN-OWNER', null);

        self::assertSame(0, ProjectTeamMember::query()->count());
        self::assertSame(0, Partnership::query()->count());
        self::assertSame(0, Organization::query()->count());
    }

    public function test_declaring_a_financial_need_for_a_group_project_creates_no_zumra_role(): void
    {
        $group = $this->group('IDN-LEADER');
        $project = $this->groupProject($group, Project::STATUS_ADOPTED);
        $rolesBefore = ZumraGroupRole::query()->count();

        app(ProjectFundingService::class)->create($project, 'IDN-LEADER', $this->payload());

        self::assertSame($rolesBefore, ZumraGroupRole::query()->count());
    }

    // ===== Aucune dette, aucun score =====

    public function test_the_funding_table_has_no_debt_score_or_collected_amount_column(): void
    {
        $columns = Schema::getColumnListing('dg_project_fundings');
        foreach (['collected_amount', 'paid_amount', 'balance', 'available_balance', 'wallet_balance', 'debt', 'score', 'rank'] as $forbidden) {
            self::assertNotContains($forbidden, $columns);
        }
    }

    // ===== Helpers =====

    private function assertAborts(int $status, callable $fn): void
    {
        try {
            $fn();
            self::fail("Expected an HttpException with status {$status} but none was thrown.");
        } catch (HttpException $e) {
            self::assertSame($status, $e->getStatusCode());
        }
    }

    private function payload(): array
    {
        return [
            'target_amount' => 500000,
            'currency' => 'XOF',
            'purpose' => 'Financer l’achat de matériel nécessaire à la poursuite du projet.',
            'intended_use' => 'Acquisition de matériel informatique partagé par l’équipe du projet.',
            'conditions' => null,
        ];
    }

    private function project(string $status, string $visibility = Project::VISIBILITY_PUBLIC): Project
    {
        return Project::query()->create([
            'public_reference' => (string) Str::uuid(), 'owner_type' => Project::OWNER_PERSON,
            'owner_reference' => 'IDN-OWNER', 'initiator_core_reference' => 'IDN-OWNER',
            'name' => 'Atelier numérique communautaire', 'summary' => 'Un projet concret destiné à construire des capacités utiles.',
            'problem' => 'Des personnes motivées manquent de cadre pratique.', 'proposed_solution' => 'Créer un atelier progressif.',
            'beneficiaries' => 'Jeunes débutants.', 'domain' => 'DIGITAL', 'participation_mode' => 'HYBRID',
            'objectives' => ['Former une équipe'], 'required_capabilities' => ['Formation numérique'],
            'required_resources' => ['Ordinateurs'], 'risks' => [], 'property_regime' => 'PERSONAL_SUPPORTED',
            'visibility' => $visibility, 'status' => $status, 'maturity' => 'IDEA',
            'decided_by_core_reference' => $status !== Project::STATUS_PROPOSED ? 'IDN-OWNER' : null,
            'adopted_at' => in_array($status, [Project::STATUS_ADOPTED, Project::STATUS_IN_PROGRESS, Project::STATUS_COMPLETED, Project::STATUS_ARCHIVED], true) ? now() : null,
            'archived_at' => $status === Project::STATUS_ARCHIVED ? now() : null,
        ]);
    }

    private function groupProject(ZumraGroup $group, string $status, string $visibility = Project::VISIBILITY_GROUP): Project
    {
        return Project::query()->create([
            'public_reference' => (string) Str::uuid(), 'owner_type' => Project::OWNER_GROUP,
            'owner_reference' => $group->id, 'initiator_core_reference' => 'IDN-LEADER',
            'name' => 'Projet du groupe', 'summary' => 'Un projet concret porté par la ZUMRA.',
            'problem' => 'Un problème réel à résoudre collectivement.', 'proposed_solution' => 'Une solution progressive.',
            'beneficiaries' => 'Communauté locale.', 'domain' => 'DIGITAL', 'participation_mode' => 'HYBRID',
            'objectives' => ['Agir'], 'required_capabilities' => ['Coordination'], 'required_resources' => ['Temps'], 'risks' => [],
            'property_regime' => 'ZUMRA_COLLECTIVE', 'visibility' => $visibility, 'status' => $status, 'maturity' => 'IDEA',
            'decided_by_core_reference' => $status !== Project::STATUS_PROPOSED ? 'IDN-LEADER' : null,
            'adopted_at' => in_array($status, [Project::STATUS_ADOPTED, Project::STATUS_IN_PROGRESS, Project::STATUS_COMPLETED, Project::STATUS_ARCHIVED], true) ? now() : null,
            'archived_at' => $status === Project::STATUS_ARCHIVED ? now() : null,
        ]);
    }

    private function group(string $leader): ZumraGroup
    {
        $this->programMember($leader);

        return app(ZumraGroupService::class)->create($leader, [
            'name' => 'Atelier financement '.Str::random(6), 'domain' => 'Numérique',
            'founding_objective' => 'Former une équipe qui transmet les outils numériques et réalise des solutions utiles.',
            'participation_mode' => 'HYBRID', 'internal_charter' => 'Chaque membre respecte la dignité et la hiérarchie.',
            'assume_primary_lead' => true,
        ], 3);
    }

    private function programMember(string $identity): void
    {
        $charter = ZumraCharter::query()->firstOrCreate(['version' => '2026.1'], ['title' => 'Charte ZUMRA', 'body' => str_repeat('Respect et transmission. ', 8), 'content_hash' => hash('sha256', 'charter'), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()]);
        ZumraProgramMembership::query()->firstOrCreate(['core_identity_reference' => $identity], ['status' => ZumraProgramMembership::STATUS_ACTIVE, 'accepted_charter_id' => $charter->id, 'accepted_charter_version' => $charter->version, 'accepted_charter_hash' => $charter->content_hash, 'charter_accepted_at' => now(), 'submitted_at' => now(), 'activated_at' => now()]);
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-09-16T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre DG Afrique', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-09-16T23:59:00+00:00']),
        ]);

        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
