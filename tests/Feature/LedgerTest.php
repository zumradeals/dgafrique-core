<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Contributions\ContributionConfiguration;
use App\Application\Contributions\ContributionService;
use App\Application\Ledger\LedgerService;
use App\Application\Zumra\MembershipPaymentService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\Contribution;
use App\Models\ContributionPayment;
use App\Models\ContributionPurpose;
use App\Models\LedgerEntry;
use App\Models\PortalAdministrator;
use App\Models\PortalSetting;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraPayment;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * CAP-062 — Ledger / traçabilité (art. 6.5, 23.1). Journal financier simple, immuable, additif :
 * une écriture par paiement CONFIRMÉ (CAP-061 ou CAP-007B), jamais un wallet, un solde stocké ni un
 * moteur double-entry. LedgerEntry est une PROJECTION : elle ne modifie jamais sa source.
 */
final class LedgerTest extends TestCase
{
    use RefreshDatabase;

    private string $providerStatus = 'pending';

    private string $providerEnvironment = 'live';

    private int $providerAmount = 500;

    private int $paymentCounter = 0;

    private string $coreReference = 'IDN-PAYER';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('payments.membership.enabled', true);
        config()->set('payments.geniuspay.environment', 'live');
        config()->set('payments.geniuspay.api_key', 'pk_live_test');
        config()->set('payments.geniuspay.api_secret', 'sk_live_test');
        $this->enableContributions();
    }

    // ===== Posting CAP-061 =====

    public function test_a_completed_contribution_payment_creates_a_ledger_entry(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('completed');
        app(ContributionService::class)->reconcile($payment);

        self::assertSame(1, LedgerEntry::query()->where('source_type', LedgerEntry::SOURCE_CONTRIBUTION_PAYMENT)->where('source_id', $payment->id)->count());
    }

    public function test_a_pending_contribution_payment_creates_no_ledger_entry(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');

        self::assertSame(0, LedgerEntry::query()->count());
    }

    public function test_a_processing_contribution_payment_creates_no_ledger_entry(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('processing');
        app(ContributionService::class)->reconcile($payment);

        self::assertSame(0, LedgerEntry::query()->count());
    }

    public function test_a_failed_contribution_payment_creates_no_ledger_entry(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('failed');
        app(ContributionService::class)->reconcile($payment);

        self::assertSame(0, LedgerEntry::query()->count());
    }

    public function test_a_cancelled_contribution_payment_creates_no_ledger_entry(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('cancelled');
        app(ContributionService::class)->reconcile($payment);

        self::assertSame(0, LedgerEntry::query()->count());
    }

    public function test_the_browser_return_alone_never_creates_a_ledger_entry(): void
    {
        // returned() ne fait que relire l'état déjà en base — sans reconcile() serveur-à-serveur
        // explicite (simulé ici par un statut toujours PENDING côté prestataire), aucune écriture.
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');

        self::assertSame(0, LedgerEntry::query()->count());
    }

    // ===== Posting CAP-007B =====

    public function test_a_completed_membership_payment_creates_a_ledger_entry(): void
    {
        $membership = $this->pendingMembership('IDN-NEWMEMBER');
        $this->fakeGeniusPay('pending');
        $payment = app(MembershipPaymentService::class)->start($membership, 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('completed');
        app(MembershipPaymentService::class)->reconcile($payment);

        self::assertSame(1, LedgerEntry::query()->where('source_type', LedgerEntry::SOURCE_MEMBERSHIP_PAYMENT)->where('source_id', $payment->id)->count());
    }

    public function test_a_pending_membership_payment_creates_no_ledger_entry(): void
    {
        $membership = $this->pendingMembership('IDN-NEWMEMBER');
        $this->fakeGeniusPay('pending');
        app(MembershipPaymentService::class)->start($membership, 'https://example.test/ok', 'https://example.test/ko');

        self::assertSame(0, LedgerEntry::query()->count());
    }

    public function test_a_failed_membership_payment_creates_no_ledger_entry(): void
    {
        $membership = $this->pendingMembership('IDN-NEWMEMBER');
        $this->fakeGeniusPay('pending');
        $payment = app(MembershipPaymentService::class)->start($membership, 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('failed');
        app(MembershipPaymentService::class)->reconcile($payment);

        self::assertSame(0, LedgerEntry::query()->count());
    }

    public function test_a_cancelled_membership_payment_creates_no_ledger_entry(): void
    {
        $membership = $this->pendingMembership('IDN-NEWMEMBER');
        $this->fakeGeniusPay('pending');
        $payment = app(MembershipPaymentService::class)->start($membership, 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('cancelled');
        app(MembershipPaymentService::class)->reconcile($payment);

        self::assertSame(0, LedgerEntry::query()->count());
    }

    // ===== Idempotence =====

    public function test_reconcile_called_repeatedly_creates_only_one_ledger_entry(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('completed');
        $confirmed = app(ContributionService::class)->reconcile($payment);
        app(ContributionService::class)->reconcile($confirmed);
        app(ContributionService::class)->reconcile($confirmed);

        self::assertSame(1, LedgerEntry::query()->count());
    }

    public function test_posting_the_same_payment_directly_several_times_always_returns_the_same_entry(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('completed');
        $confirmed = app(ContributionService::class)->reconcile($payment);

        $ledger = app(LedgerService::class);
        $first = $ledger->postContributionPayment($confirmed);
        $second = $ledger->postContributionPayment($confirmed);
        $third = $ledger->postContributionPayment($confirmed);

        self::assertSame($first->id, $second->id);
        self::assertSame($first->id, $third->id);
        self::assertSame(1, LedgerEntry::query()->count());
    }

    public function test_backfill_run_twice_creates_no_duplicate(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('completed');
        app(ContributionService::class)->reconcile($payment);
        // L'écriture existe déjà (postée par reconcile()) : le backfill ne doit rien dupliquer.
        LedgerEntry::query()->delete();
        ContributionPayment::query()->whereKey($payment->id)->update(['status' => ContributionPayment::STATUS_COMPLETED]);

        $this->artisan('ledger:backfill')->assertSuccessful();
        $this->artisan('ledger:backfill')->assertSuccessful();

        self::assertSame(1, LedgerEntry::query()->where('source_type', LedgerEntry::SOURCE_CONTRIBUTION_PAYMENT)->where('source_id', $payment->id)->count());
    }

    public function test_backfill_after_reconcile_creates_no_duplicate(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('completed');
        app(ContributionService::class)->reconcile($payment);

        $this->artisan('ledger:backfill')->assertSuccessful();

        self::assertSame(1, LedgerEntry::query()->where('source_type', LedgerEntry::SOURCE_CONTRIBUTION_PAYMENT)->where('source_id', $payment->id)->count());
    }

    // ===== Backfill =====

    public function test_backfill_picks_up_historical_completed_contribution_and_membership_payments(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        $contribPayment = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('completed');
        app(ContributionService::class)->reconcile($contribPayment);

        $membership = $this->pendingMembership('IDN-NEWMEMBER');
        $this->fakeGeniusPay('pending');
        $membershipPayment = app(MembershipPaymentService::class)->start($membership, 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('completed');
        app(MembershipPaymentService::class)->reconcile($membershipPayment);

        // Simule un déploiement de CAP-062 après coup : les écritures déjà postées par reconcile()
        // sont effacées pour prouver que le backfill seul peut les reconstruire depuis les sources.
        LedgerEntry::query()->delete();

        $this->artisan('ledger:backfill')->assertSuccessful();

        self::assertSame(1, LedgerEntry::query()->where('source_type', LedgerEntry::SOURCE_CONTRIBUTION_PAYMENT)->where('source_id', $contribPayment->id)->count());
        self::assertSame(1, LedgerEntry::query()->where('source_type', LedgerEntry::SOURCE_MEMBERSHIP_PAYMENT)->where('source_id', $membershipPayment->id)->count());
    }

    public function test_backfill_ignores_non_completed_payments(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');

        $this->artisan('ledger:backfill')->assertSuccessful();

        self::assertSame(0, LedgerEntry::query()->count());
    }

    public function test_backfill_never_modifies_source_payments(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('completed');
        $confirmed = app(ContributionService::class)->reconcile($payment);
        $statusBefore = $confirmed->status;
        $updatedAtBefore = $confirmed->refresh()->updated_at;

        $this->artisan('ledger:backfill')->assertSuccessful();

        self::assertSame($statusBefore, $confirmed->refresh()->status);
        self::assertTrue($updatedAtBefore->equalTo($confirmed->refresh()->updated_at));
    }

    // ===== Snapshot =====

    public function test_ledger_entry_snapshots_exact_fields_for_individual_contribution(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('completed');
        $confirmed = app(ContributionService::class)->reconcile($payment);

        $entry = LedgerEntry::query()->where('source_id', $confirmed->id)->sole();
        self::assertSame(500, $entry->amount);
        self::assertSame('XOF', $entry->currency);
        self::assertSame('2026-09', $entry->period);
        self::assertSame('TRAINING', $entry->purpose_code);
        self::assertSame('IDN-MEMBER', $entry->payer_core_reference);
        self::assertSame(Contribution::SUBJECT_PERSON, $entry->subject_type);
        self::assertSame('IDN-MEMBER', $entry->subject_reference);
        self::assertSame(LedgerEntry::SOURCE_CONTRIBUTION_PAYMENT, $entry->source_type);
        self::assertSame(LedgerEntry::TYPE_PAYMENT, $entry->entry_type);
        self::assertNotNull($entry->occurred_at);
        self::assertNotNull($entry->posted_at);
    }

    public function test_ledger_entry_snapshots_zumra_group_subject_for_collective_contribution(): void
    {
        $group = $this->activeGroup('IDN-LEADER', 'IDN-FINANCE');
        $contribution = $this->approvedCollective($group, 'IDN-LEADER', 'IDN-FINANCE');
        $this->fakeGeniusPay('pending', amount: 2500);
        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-LEADER', '2026-09', 'SOLIDARITY', 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('completed', amount: 2500);
        $confirmed = app(ContributionService::class)->reconcile($payment);

        $entry = LedgerEntry::query()->where('source_id', $confirmed->id)->sole();
        self::assertSame(2500, $entry->amount);
        self::assertSame(Contribution::SUBJECT_ZUMRA_GROUP, $entry->subject_type);
        self::assertSame($group->id, $entry->subject_reference);
        self::assertSame('IDN-LEADER', $entry->payer_core_reference);
    }

    public function test_ledger_entry_has_a_null_period_for_membership_payment(): void
    {
        $membership = $this->pendingMembership('IDN-NEWMEMBER');
        $this->fakeGeniusPay('pending');
        $payment = app(MembershipPaymentService::class)->start($membership, 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('completed');
        $confirmed = app(MembershipPaymentService::class)->reconcile($payment);

        $entry = LedgerEntry::query()->where('source_id', $confirmed->id)->sole();
        self::assertNull($entry->period);
        self::assertSame(500, $entry->amount);
        self::assertSame('IDN-NEWMEMBER', $entry->payer_core_reference);
        self::assertSame(Contribution::SUBJECT_PERSON, $entry->subject_type);
        self::assertSame('IDN-NEWMEMBER', $entry->subject_reference);
        self::assertSame(ZumraPayment::PURPOSE_MEMBERSHIP, $entry->purpose_code);
    }

    // ===== Immutabilité =====

    public function test_retiring_a_purpose_after_posting_never_alters_the_ledger_entry(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'EMERGENCY', 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('completed');
        $confirmed = app(ContributionService::class)->reconcile($payment);
        $entry = LedgerEntry::query()->where('source_id', $confirmed->id)->sole();

        ContributionPurpose::query()->where('code', 'EMERGENCY')->update(['status' => ContributionPurpose::STATUS_RETIRED]);

        self::assertSame('EMERGENCY', $entry->refresh()->purpose_code);
    }

    public function test_ledger_service_exposes_no_update_or_delete_method(): void
    {
        $reflection = new \ReflectionClass(LedgerService::class);
        $publicMethodNames = array_map(static fn (\ReflectionMethod $m): string => $m->getName(), $reflection->getMethods(\ReflectionMethod::IS_PUBLIC));

        self::assertNotContains('updateEntry', $publicMethodNames);
        self::assertNotContains('deleteEntry', $publicMethodNames);
        self::assertNotContains('update', $publicMethodNames);
        self::assertNotContains('delete', $publicMethodNames);
    }

    // ===== Backfill / posting n'altèrent jamais la source =====

    public function test_posting_a_contribution_payment_never_modifies_the_contribution_or_zumra_group(): void
    {
        $group = $this->activeGroup('IDN-LEADER', 'IDN-FINANCE');
        $stateBefore = $group->state;
        $maturityBefore = $group->maturity;
        $contribution = $this->approvedCollective($group, 'IDN-LEADER', 'IDN-FINANCE');
        $statusBefore = $contribution->status;
        $this->fakeGeniusPay('pending', amount: 2500);
        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-LEADER', '2026-09', 'SOLIDARITY', 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('completed', amount: 2500);
        app(ContributionService::class)->reconcile($payment);

        self::assertSame($statusBefore, $contribution->refresh()->status);
        self::assertSame($stateBefore, $group->refresh()->state);
        self::assertSame($maturityBefore, $group->refresh()->maturity);
    }

    // ===== Invariants =====

    public function test_ledger_table_has_no_wallet_balance_or_credit_column(): void
    {
        $columns = Schema::getColumnListing('dg_ledger_entries');
        foreach (['balance', 'available_balance', 'wallet_balance', 'credit', 'wallet', 'score'] as $forbidden) {
            self::assertNotContains($forbidden, $columns);
        }
    }

    // ===== Autorisations =====

    public function test_a_person_sees_their_own_ledger_entries(): void
    {
        $entry = $this->postedIndividualEntry('IDN-MEMBER');
        $this->signIn('IDN-MEMBER');

        $response = $this->getJson('/finances/ledger');

        $response->assertOk();
        $response->assertJsonPath('entries.0.id', $entry->id);
    }

    public function test_a_stranger_does_not_see_another_persons_ledger_entry(): void
    {
        $entry = $this->postedIndividualEntry('IDN-MEMBER');
        $this->signIn('IDN-STRANGER');

        $response = $this->getJson("/finances/ledger/{$entry->id}");

        $response->assertNotFound();
    }

    public function test_a_zumra_leader_sees_the_collective_ledger_entry(): void
    {
        $group = $this->activeGroup('IDN-LEADER', 'IDN-FINANCE');
        $contribution = $this->approvedCollective($group, 'IDN-LEADER', 'IDN-FINANCE');
        $this->fakeGeniusPay('pending', amount: 2500);
        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-LEADER', '2026-09', 'SOLIDARITY', 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('completed', amount: 2500);
        $confirmed = app(ContributionService::class)->reconcile($payment);
        $entry = LedgerEntry::query()->where('source_id', $confirmed->id)->sole();
        $this->signIn('IDN-FINANCE');

        $response = $this->getJson("/finances/ledger/{$entry->id}");

        $response->assertOk();
    }

    public function test_a_non_leader_does_not_see_the_collective_ledger_entry(): void
    {
        $group = $this->activeGroup('IDN-LEADER', 'IDN-FINANCE');
        $contribution = $this->approvedCollective($group, 'IDN-LEADER', 'IDN-FINANCE');
        $this->fakeGeniusPay('pending', amount: 2500);
        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-LEADER', '2026-09', 'SOLIDARITY', 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('completed', amount: 2500);
        $confirmed = app(ContributionService::class)->reconcile($payment);
        $entry = LedgerEntry::query()->where('source_id', $confirmed->id)->sole();
        $this->signIn('IDN-OUTSIDER');

        $response = $this->getJson("/finances/ledger/{$entry->id}");

        $response->assertNotFound();
    }

    public function test_an_administrator_sees_the_global_ledger(): void
    {
        $this->postedIndividualEntry('IDN-MEMBER');
        $admin = $this->administrator();
        $this->signIn($admin);

        $response = $this->getJson('/administration/ledger');

        $response->assertOk();
    }

    public function test_a_non_administrator_cannot_reach_the_administration_ledger(): void
    {
        $this->postedIndividualEntry('IDN-MEMBER');
        $this->signIn('IDN-MEMBER');

        $response = $this->getJson('/administration/ledger');

        $response->assertForbidden();
    }

    // ===== Helpers =====

    private function signIn(string $reference): void
    {
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }

    private function postedIndividualEntry(string $identity): LedgerEntry
    {
        $this->programMember($identity);
        $contribution = app(ContributionService::class)->startIndividual($identity);
        $this->fakeGeniusPay('pending');
        $payment = app(ContributionService::class)->payPeriod($contribution, $identity, '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('completed');
        $confirmed = app(ContributionService::class)->reconcile($payment);

        return LedgerEntry::query()->where('source_id', $confirmed->id)->sole();
    }

    private function actingAsIdentity(string $reference): self
    {
        $this->withoutMiddleware();
        app()->instance('dg_identity.test_override', new CoreIdentity(reference: $reference, type: 'personne', label: $reference, state: 'ACTIF', source: 'CORE', regime: 'INSCRIT_AU_REGISTRE'));
        $this->app['router']->pushMiddlewareToGroup('web', function ($request, $next) use ($reference) {
            $request->attributes->set('dg_identity', new CoreIdentity(reference: $reference, type: 'personne', label: $reference, state: 'ACTIF', source: 'CORE', regime: 'INSCRIT_AU_REGISTRE'));

            return $next($request);
        });

        return $this;
    }

    private function enableContributions(): void
    {
        $config = app(ContributionConfiguration::class);
        PortalSetting::query()->updateOrCreate(
            ['key' => ContributionConfiguration::KEY],
            ['value' => array_merge($config->defaults(), ['individual_enabled' => true, 'collective_enabled' => true]), 'updated_by_core_reference' => 'IDN-ADMIN']
        );
    }

    private function administrator(): string
    {
        $reference = 'IDN-ADMIN-'.Str::random(6);
        PortalAdministrator::query()->create(['core_identity_reference' => $reference]);

        return $reference;
    }

    /** @return array{0: ZumraGroup} */
    private function readyGroup(string $leader, string $financeLead): array
    {
        $this->programMember($leader);
        $service = app(ZumraGroupService::class);
        $group = $service->create($leader, $this->groupPayload(), 3);

        $roles = ['FIRST_DEPUTY' => 'IDN-DEPUTY1-'.Str::random(4), 'SECOND_DEPUTY' => 'IDN-DEPUTY2-'.Str::random(4), 'FINANCE_LEAD' => $financeLead, 'SOCIAL_RELATIONS_LEAD' => 'IDN-SOCIAL-'.Str::random(4)];
        $last = array_key_last($roles);
        foreach ($roles as $role => $identity) {
            $this->programMember($identity);
            $service->proposeRole($group, $leader, $role, $identity);
            $service->acceptRole($group, $identity, $role, 3, $role === $last);
        }

        return [$group->refresh()];
    }

    private function validatedGroup(string $leader, string $financeLead): ZumraGroup
    {
        [$group] = $this->readyGroup($leader, $financeLead);
        $admin = $this->administrator();

        return app(ZumraGroupService::class)->validate($group, $admin);
    }

    private function activeGroup(string $leader, string $financeLead): ZumraGroup
    {
        $group = $this->validatedGroup($leader, $financeLead);
        $admin = $this->administrator();

        return app(ZumraGroupService::class)->activate($group, $admin);
    }

    private function approvedCollective(ZumraGroup $group, string $leader, string $financeLead): Contribution
    {
        $service = app(ContributionService::class);
        $contribution = $service->proposeCollective($group, $leader);

        return $service->approveCollective($contribution, $financeLead);
    }

    private function groupPayload(): array
    {
        return [
            'name' => 'Atelier ledger '.Str::random(8),
            'domain' => 'Numérique',
            'founding_objective' => 'Former une équipe qui transmet les outils numériques et réalise des solutions utiles aux communautés locales.',
            'participation_mode' => 'HYBRID',
            'internal_charter' => 'Chaque membre respecte la dignité, la hiérarchie, la transmission et les décisions responsables.',
            'assume_primary_lead' => true,
        ];
    }

    private function programMember(string $identity): void
    {
        $charter = ZumraCharter::query()->firstOrCreate(['version' => '2026.1'], ['title' => 'Charte ZUMRA', 'body' => str_repeat('Respect et transmission. ', 8), 'content_hash' => hash('sha256', 'charter'), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()]);
        ZumraProgramMembership::query()->firstOrCreate(['core_identity_reference' => $identity], ['status' => ZumraProgramMembership::STATUS_ACTIVE, 'accepted_charter_id' => $charter->id, 'accepted_charter_version' => $charter->version, 'accepted_charter_hash' => $charter->content_hash, 'charter_accepted_at' => now(), 'submitted_at' => now(), 'activated_at' => now()]);
    }

    private function pendingMembership(string $identity): ZumraProgramMembership
    {
        $charter = ZumraCharter::query()->firstOrCreate(['version' => '2026.1'], ['title' => 'Charte ZUMRA', 'body' => str_repeat('Respect et transmission. ', 8), 'content_hash' => hash('sha256', 'charter'), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()]);

        return ZumraProgramMembership::query()->create([
            'core_identity_reference' => $identity, 'status' => ZumraProgramMembership::STATUS_PENDING_PAYMENT,
            'accepted_charter_id' => $charter->id, 'accepted_charter_version' => $charter->version,
            'accepted_charter_hash' => $charter->content_hash, 'charter_accepted_at' => now(), 'submitted_at' => now(),
        ]);
    }

    private function fakeGeniusPay(string $providerStatus, ?int $amount = null, ?string $environment = null): void
    {
        $this->providerStatus = $providerStatus;
        if ($amount !== null) {
            $this->providerAmount = $amount;
        }
        if ($environment !== null) {
            $this->providerEnvironment = $environment;
        }
        $this->installFake();
    }

    private function fakeGeniusPayReconcile(string $providerStatus, ?int $amount = null, ?string $environment = null): void
    {
        $this->fakeGeniusPay($providerStatus, $amount, $environment);
    }

    private function installFake(): void
    {
        Http::fake(function (ClientRequest $request) {
            $url = $request->url();
            $method = $request->method();

            // Sessions/identités dynamiques (pas un seul coreReference fixe) : plusieurs tests
            // d'autorisation se connectent successivement sous des identités différentes dans le
            // même test — l'identité est portée par le corps de connexion puis par le jeton.
            if ($method === 'POST' && str_ends_with(rtrim($url, '/'), '/sessions')) {
                $identifier = (string) ($request->data()['identifiant'] ?? $this->coreReference);

                return Http::response(['jeton' => 'bearer-'.$identifier, 'entite' => $identifier, 'assurance' => 'AS1', 'expire_le' => '2026-09-16T23:59:00+00:00'], 201);
            }
            if (str_ends_with($url, '/sessions/current')) {
                $authorization = $request->header('Authorization')[0] ?? '';
                $reference = str_starts_with($authorization, 'Bearer bearer-') ? substr($authorization, strlen('Bearer bearer-')) : $this->coreReference;

                return Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-09-16T23:59:00+00:00']);
            }
            if (str_contains($url, '/identites/')) {
                $reference = rawurldecode((string) basename((string) parse_url($url, PHP_URL_PATH)));

                return Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']);
            }
            if ($method === 'POST' && str_ends_with(rtrim($url, '/'), '/payments')) {
                $this->paymentCounter++;
                $reference = 'LEDGER-REF-'.$this->paymentCounter;

                return Http::response(['success' => true, 'data' => $this->providerPayload($reference)]);
            }
            if ($method === 'GET' && str_contains($url, '/payments/')) {
                $reference = rawurldecode((string) basename((string) parse_url($url, PHP_URL_PATH)));

                return Http::response(['success' => true, 'data' => $this->providerPayload($reference)]);
            }

            return Http::response(['error' => 'UNEXPECTED_TEST_REQUEST'], 500);
        });
    }

    private function providerPayload(string $reference): array
    {
        return [
            'id' => 'pay-'.$reference,
            'reference' => $reference,
            'amount' => $this->providerAmount,
            'status' => $this->providerStatus,
            'environment' => $this->providerEnvironment,
            'checkout_url' => 'https://checkout.example/pay/'.$reference,
            'completed_at' => $this->providerStatus === 'completed' ? now()->toIso8601String() : null,
        ];
    }
}
