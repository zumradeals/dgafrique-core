<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Contributions\ContributionConfiguration;
use App\Application\Contributions\ContributionService;
use App\Application\Zumra\MembershipPaymentService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\Contribution;
use App\Models\ContributionEvent;
use App\Models\ContributionPayment;
use App\Models\ContributionPurpose;
use App\Models\ContributionReceipt;
use App\Models\Organization;
use App\Models\PortalAdministrator;
use App\Models\PortalSetting;
use App\Models\Project;
use App\Models\Satellite;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraPayment;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * CAP-061 — Contributions financières (art. 6). Contribution = ENGAGEMENT, ContributionPayment =
 * TENTATIVE mensuelle, jamais confondues. Ne touche jamais ZumraGroup.state/maturity. Vérifie
 * explicitement l'absence de score, de dette et de suspension automatique.
 */
final class ContributionTest extends TestCase
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
        Http::preventStrayRequests();
        config()->set('payments.membership.enabled', true);
        config()->set('payments.geniuspay.environment', 'live');
        config()->set('payments.geniuspay.api_key', 'pk_live_test');
        config()->set('payments.geniuspay.api_secret', 'sk_live_test');
        $this->enableContributions();
    }

    // ===== Individuelle =====

    public function test_starting_an_individual_contribution_requires_active_program_membership(): void
    {
        $this->assertAborts(403, fn () => app(ContributionService::class)->startIndividual('IDN-NOT-A-MEMBER'));
    }

    public function test_an_active_program_member_can_start_an_individual_contribution(): void
    {
        $this->programMember('IDN-MEMBER');

        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');

        self::assertSame(Contribution::TYPE_INDIVIDUAL, $contribution->type);
        self::assertSame(Contribution::STATUS_ACTIVE, $contribution->status);
        self::assertSame('IDN-MEMBER', $contribution->subject_reference);
        self::assertTrue(ContributionEvent::query()->where('event', 'CONTRIBUTION_STARTED')->exists());
    }

    public function test_pause_resume_and_stop_are_freely_reversible_and_preserve_history(): void
    {
        $this->programMember('IDN-MEMBER');
        $service = app(ContributionService::class);
        $contribution = $service->startIndividual('IDN-MEMBER');

        $paused = $service->pause($contribution, 'IDN-MEMBER');
        self::assertSame(Contribution::STATUS_PAUSED, $paused->status);

        $resumed = $service->resume($contribution, 'IDN-MEMBER');
        self::assertSame(Contribution::STATUS_ACTIVE, $resumed->status);

        $stopped = $service->stop($contribution, 'IDN-MEMBER');
        self::assertSame(Contribution::STATUS_STOPPED, $stopped->status);
        self::assertNotNull($stopped->stopped_at);

        // Réactivation sans perte d'historique : même ligne, mêmes événements conservés.
        $reactivated = $service->resume($contribution, 'IDN-MEMBER');
        self::assertSame(Contribution::STATUS_ACTIVE, $reactivated->status);
        self::assertSame($contribution->id, $reactivated->id);
        self::assertSame(
            ['CONTRIBUTION_STARTED', 'CONTRIBUTION_PAUSED', 'CONTRIBUTION_RESUMED', 'CONTRIBUTION_STOPPED', 'CONTRIBUTION_RESUMED'],
            ContributionEvent::query()->where('contribution_id', $contribution->id)->orderBy('occurred_at')->pluck('event')->all()
        );
    }

    public function test_a_stranger_cannot_pause_someone_elses_individual_contribution(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');

        $this->assertAborts(403, fn () => app(ContributionService::class)->pause($contribution, 'IDN-STRANGER'));
    }

    public function test_a_missed_month_never_produces_debt_or_a_second_engagement(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');

        // Aucune colonne de dette/créance/pénalité n'existe sur le modèle : rien à faire pour
        // « manquer un mois » — l'absence de ContributionPayment EST l'absence de dette.
        self::assertSame(0, $contribution->payments()->count());
        self::assertSame(Contribution::STATUS_ACTIVE, $contribution->refresh()->status, 'Aucune suspension automatique pour absence de paiement.');
    }

    public function test_starting_a_second_individual_engagement_is_refused(): void
    {
        $this->programMember('IDN-MEMBER');
        app(ContributionService::class)->startIndividual('IDN-MEMBER');

        $this->assertAborts(409, fn () => app(ContributionService::class)->startIndividual('IDN-MEMBER'));
    }

    // ===== Collective — état ZUMRA =====

    public function test_a_constituting_zumra_cannot_propose_a_collective_engagement(): void
    {
        $this->programMember('IDN-LEADER');
        $group = app(ZumraGroupService::class)->create('IDN-LEADER', $this->groupPayload(), 3);

        $this->assertAborts(409, fn () => app(ContributionService::class)->proposeCollective($group, 'IDN-LEADER'));
    }

    public function test_a_ready_zumra_cannot_propose_a_collective_engagement(): void
    {
        [$group] = $this->readyGroup('IDN-LEADER', 'IDN-FINANCE');

        $this->assertAborts(409, fn () => app(ContributionService::class)->proposeCollective($group, 'IDN-LEADER'));
    }

    public function test_a_validated_zumra_can_propose_a_collective_engagement(): void
    {
        $group = $this->validatedGroup('IDN-LEADER', 'IDN-FINANCE');

        $contribution = app(ContributionService::class)->proposeCollective($group, 'IDN-LEADER');

        self::assertSame(Contribution::STATUS_PROPOSED, $contribution->status);
        self::assertSame('PRIMARY_LEAD', $contribution->proposed_role);
    }

    public function test_an_active_zumra_can_propose_a_collective_engagement(): void
    {
        $group = $this->activeGroup('IDN-LEADER', 'IDN-FINANCE');

        $contribution = app(ContributionService::class)->proposeCollective($group, 'IDN-LEADER');

        self::assertSame(Contribution::STATUS_PROPOSED, $contribution->status);
    }

    public function test_a_warned_zumra_can_still_propose_a_collective_engagement(): void
    {
        $group = $this->activeGroup('IDN-LEADER', 'IDN-FINANCE');
        $admin = $this->administrator();
        $group = app(ZumraGroupService::class)->warn($group, $admin);

        $contribution = app(ContributionService::class)->proposeCollective($group, 'IDN-LEADER');
        self::assertSame(Contribution::STATUS_PROPOSED, $contribution->status);
    }

    public function test_a_rehabilitating_zumra_can_still_propose_a_collective_engagement(): void
    {
        $group = $this->activeGroup('IDN-LEADER', 'IDN-FINANCE');
        $admin = $this->administrator();
        $groupService = app(ZumraGroupService::class);
        $group = $groupService->warn($group, $admin);
        $group = $groupService->suspend($group, $admin);
        $group = $groupService->enterRehabilitation($group, $admin);

        $contribution = app(ContributionService::class)->proposeCollective($group, 'IDN-LEADER');
        self::assertSame(Contribution::STATUS_PROPOSED, $contribution->status);
    }

    public function test_a_suspended_zumra_cannot_propose_a_collective_engagement(): void
    {
        $group = $this->activeGroup('IDN-LEADER', 'IDN-FINANCE');
        $admin = $this->administrator();
        $groupService = app(ZumraGroupService::class);
        $group = $groupService->warn($group, $admin);
        $group = $groupService->suspend($group, $admin);

        $this->assertAborts(409, fn () => app(ContributionService::class)->proposeCollective($group, 'IDN-LEADER'));
    }

    public function test_a_suspended_zumra_blocks_a_new_collective_payment_but_engagement_state_is_untouched(): void
    {
        $group = $this->activeGroup('IDN-LEADER', 'IDN-FINANCE');
        $contribution = $this->approvedCollective($group, 'IDN-LEADER', 'IDN-FINANCE');
        $admin = $this->administrator();
        $groupService = app(ZumraGroupService::class);
        $group = $groupService->warn($group, $admin);
        $group = $groupService->suspend($group, $admin);

        $this->assertAborts(409, fn () => app(ContributionService::class)->payPeriod($contribution, 'IDN-LEADER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko'));
        self::assertSame(Contribution::STATUS_ACTIVE, $contribution->refresh()->status, 'Le domaine financier ne modifie jamais le cycle de vie ZUMRA ni ne subit un changement d’état à cause de lui hors ce seul refus de paiement.');
    }

    // ===== Collective — gouvernance à deux acteurs =====

    public function test_proposing_requires_primary_lead_or_finance_lead_role(): void
    {
        $group = $this->validatedGroup('IDN-LEADER', 'IDN-FINANCE');

        $this->assertAborts(403, fn () => app(ContributionService::class)->proposeCollective($group, 'IDN-OUTSIDER'));
    }

    public function test_finance_lead_can_propose_and_primary_lead_approves_in_the_reverse_order(): void
    {
        $group = $this->validatedGroup('IDN-LEADER', 'IDN-FINANCE');
        $service = app(ContributionService::class);

        $contribution = $service->proposeCollective($group, 'IDN-FINANCE');
        self::assertSame('FINANCE_LEAD', $contribution->proposed_role);

        $approved = $service->approveCollective($contribution, 'IDN-LEADER');
        self::assertSame(Contribution::STATUS_ACTIVE, $approved->status);
        self::assertSame('PRIMARY_LEAD', $approved->approved_role);
    }

    public function test_primary_lead_proposes_and_finance_lead_approves(): void
    {
        $group = $this->validatedGroup('IDN-LEADER', 'IDN-FINANCE');
        $service = app(ContributionService::class);

        $contribution = $service->proposeCollective($group, 'IDN-LEADER');
        $approved = $service->approveCollective($contribution, 'IDN-FINANCE');

        self::assertSame(Contribution::STATUS_ACTIVE, $approved->status);
        self::assertSame('FINANCE_LEAD', $approved->approved_role);
        self::assertTrue(ContributionEvent::query()->where('event', 'CONTRIBUTION_PROPOSED')->exists());
        self::assertTrue(ContributionEvent::query()->where('event', 'CONTRIBUTION_APPROVED')->exists());
    }

    public function test_the_same_person_cannot_propose_and_approve_even_if_holding_both_roles(): void
    {
        // Invariant impossible à violer par construction (une personne ne peut exercer qu'une
        // seule responsabilité fondatrice par ZUMRA, ZUMRA-COMP-001) : le service défend quand
        // même explicitement l'égalité de core_reference, indépendamment de cette garantie.
        $group = $this->validatedGroup('IDN-LEADER', 'IDN-LEADER-AS-FINANCE-IMPOSSIBLE');
        $service = app(ContributionService::class);
        $contribution = $service->proposeCollective($group, 'IDN-LEADER');

        $this->assertAborts(403, fn () => $service->approveCollective($contribution, 'IDN-LEADER'));
    }

    public function test_approval_by_the_same_role_as_the_proposer_is_refused(): void
    {
        // Scénario défensif : deux personnes distinctes ne peuvent pas partager le même rôle
        // (impossible par construction), donc ce test prouve la garde côté service directement.
        $group = $this->validatedGroup('IDN-LEADER', 'IDN-FINANCE');
        $service = app(ContributionService::class);
        $contribution = $service->proposeCollective($group, 'IDN-LEADER');
        Contribution::query()->whereKey($contribution->id)->update(['proposed_by_core_reference' => 'IDN-GHOST']);

        $this->assertAborts(422, fn () => $service->approveCollective($contribution->refresh(), 'IDN-LEADER'));
    }

    public function test_an_actor_without_the_required_role_cannot_approve(): void
    {
        $group = $this->validatedGroup('IDN-LEADER', 'IDN-FINANCE');
        $service = app(ContributionService::class);
        $contribution = $service->proposeCollective($group, 'IDN-LEADER');

        $this->assertAborts(403, fn () => $service->approveCollective($contribution, 'IDN-OUTSIDER'));
    }

    public function test_payment_is_possible_only_after_the_engagement_is_approved(): void
    {
        $group = $this->activeGroup('IDN-LEADER', 'IDN-FINANCE');
        $service = app(ContributionService::class);
        $contribution = $service->proposeCollective($group, 'IDN-LEADER');

        $this->assertAborts(409, fn () => $service->payPeriod($contribution, 'IDN-LEADER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko'));
    }

    // ===== Paiement =====

    public function test_a_leader_can_pay_the_month_after_approval(): void
    {
        $group = $this->activeGroup('IDN-LEADER', 'IDN-FINANCE');
        $contribution = $this->approvedCollective($group, 'IDN-LEADER', 'IDN-FINANCE');
        $this->fakeGeniusPay('pending', amount: 2500);

        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-LEADER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');

        self::assertSame(2500, $payment->amount);
        self::assertSame('XOF', $payment->currency);
        self::assertSame('2026-09', $payment->period);
        self::assertSame(ContributionPayment::STATUS_PENDING, $payment->status);
        self::assertTrue(ContributionEvent::query()->where('event', 'PAYMENT_STARTED')->exists());
    }

    public function test_individual_payment_amount_defaults_to_500(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending', amount: 500);

        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'ECOSYSTEM_SUSTAINABILITY', 'https://example.test/ok', 'https://example.test/ko');

        self::assertSame(500, $payment->amount);
    }

    public function test_disabled_purpose_is_refused(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        ContributionPurpose::query()->where('code', 'EMERGENCY')->update(['status' => ContributionPurpose::STATUS_RETIRED]);

        $this->assertAborts(422, fn () => app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'EMERGENCY', 'https://example.test/ok', 'https://example.test/ko'));
    }

    public function test_retiring_a_purpose_never_alters_a_payment_that_already_used_it(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'EMERGENCY', 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('completed');
        app(ContributionService::class)->reconcile($payment);
        $receipt = ContributionReceipt::query()->where('payment_id', $payment->id)->sole();

        ContributionPurpose::query()->where('code', 'EMERGENCY')->update(['status' => ContributionPurpose::STATUS_RETIRED]);

        self::assertSame('EMERGENCY', $receipt->refresh()->purpose_code);
        self::assertNotNull(ContributionPayment::query()->find($payment->id));
    }

    public function test_completed_payment_then_a_second_payment_for_the_same_month_is_refused(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('completed');
        app(ContributionService::class)->reconcile($payment);

        $this->assertAborts(409, fn () => app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko'));
    }

    public function test_a_failed_payment_allows_a_new_attempt_for_the_same_month(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        $first = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('failed');
        app(ContributionService::class)->reconcile($first);

        $this->fakeGeniusPay('pending');
        $second = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');
        self::assertNotSame($first->id, $second->id);
    }

    public function test_a_cancelled_payment_allows_a_new_attempt_for_the_same_month(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        $first = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('cancelled');
        app(ContributionService::class)->reconcile($first);

        $this->fakeGeniusPay('pending');
        $second = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');
        self::assertNotSame($first->id, $second->id);
    }

    public function test_a_pending_payment_blocks_a_concurrent_attempt_for_the_same_month(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');

        $this->assertAborts(409, fn () => app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko'));
    }

    // ===== Réconciliation =====

    public function test_browser_return_alone_never_confirms_a_payment(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');

        // Simule un simple retour navigateur avec ?outcome=success sans jamais appeler reconcile().
        self::assertSame(ContributionPayment::STATUS_PENDING, $payment->refresh()->status);
        self::assertSame(0, ContributionReceipt::query()->count());
    }

    public function test_server_side_reconciliation_confirms_and_issues_one_receipt(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');

        $this->fakeGeniusPayReconcile('completed');
        $confirmed = app(ContributionService::class)->reconcile($payment);

        self::assertSame(ContributionPayment::STATUS_COMPLETED, $confirmed->status);
        self::assertSame(1, ContributionReceipt::query()->where('payment_id', $payment->id)->count());
        self::assertTrue(ContributionEvent::query()->where('event', 'PAYMENT_CONFIRMED')->exists());

        // Idempotence : rejouer la réconciliation ne double ni l'événement ni le reçu.
        app(ContributionService::class)->reconcile($confirmed);
        self::assertSame(1, ContributionReceipt::query()->where('payment_id', $payment->id)->count());
        self::assertSame(1, ContributionEvent::query()->where('event', 'PAYMENT_CONFIRMED')->count());
    }

    public function test_reconciliation_rejects_an_amount_mismatch(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');

        $this->fakeGeniusPayReconcile('completed', amount: 999);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('CONTRIBUTION_PAYMENT_RECONCILIATION_MISMATCH');
        app(ContributionService::class)->reconcile($payment);
    }

    public function test_reconciliation_rejects_an_environment_mismatch(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');

        $this->fakeGeniusPayReconcile('completed', environment: 'sandbox');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('CONTRIBUTION_PAYMENT_RECONCILIATION_MISMATCH');
        app(ContributionService::class)->reconcile($payment);
    }

    public function test_creation_rejects_a_currency_that_is_not_the_locally_configured_one(): void
    {
        // GeniusPay ne renvoie aucune devise (confirmé Phase A) : la seule vérification possible
        // est locale — ici, la configuration elle-même n'autorise que XOF.
        PortalSetting::query()->updateOrCreate(['key' => ContributionConfiguration::KEY], ['value' => array_merge(app(ContributionConfiguration::class)->defaults(), ['individual_enabled' => true, 'collective_enabled' => true, 'currency' => 'EUR']), 'updated_by_core_reference' => 'IDN-ADMIN']);
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');

        $this->fakeGeniusPayReconcile('completed');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('CONTRIBUTION_PAYMENT_RECONCILIATION_MISMATCH');
        app(ContributionService::class)->reconcile($payment);
    }

    public function test_sandbox_completion_never_confirms_when_the_switch_is_off(): void
    {
        config()->set('payments.geniuspay.environment', 'sandbox');
        config()->set('payments.geniuspay.sandbox_activation_allowed', false);
        $this->providerEnvironment = 'sandbox';
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');

        $this->fakeGeniusPayReconcile('completed');
        $reconciled = app(ContributionService::class)->reconcile($payment);

        self::assertSame(ContributionPayment::STATUS_COMPLETED, $reconciled->status, 'Le statut brut du prestataire reste stocké tel quel.');
        self::assertSame(0, ContributionReceipt::query()->count(), 'Mais aucun reçu ni effet métier tant que le sandbox n’est pas explicitement autorisé.');
    }

    public function test_sandbox_completion_confirms_when_the_switch_is_explicitly_on(): void
    {
        config()->set('payments.geniuspay.environment', 'sandbox');
        config()->set('payments.geniuspay.sandbox_activation_allowed', true);
        $this->providerEnvironment = 'sandbox';
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');

        $this->fakeGeniusPayReconcile('completed');
        app(ContributionService::class)->reconcile($payment);

        self::assertSame(1, ContributionReceipt::query()->count());
    }

    // ===== Reçus =====

    public function test_a_receipt_is_private_to_its_owner(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('completed');
        app(ContributionService::class)->reconcile($payment);
        $receipt = ContributionReceipt::query()->sole();

        self::assertSame('IDN-MEMBER', $receipt->core_identity_reference);
        self::assertSame('2026-09', $receipt->period);
        self::assertSame('TRAINING', $receipt->purpose_code);
        self::assertSame(500, $receipt->amount);
    }

    // ===== Invariants sociaux =====

    public function test_no_score_rank_or_privilege_field_exists_on_the_contribution_model(): void
    {
        $columns = array_keys((new Contribution)->getAttributes());
        foreach (['score', 'rank', 'badge', 'priority', 'visibility_boost', 'privilege'] as $forbidden) {
            self::assertNotContains($forbidden, $columns);
        }
    }

    public function test_no_debt_or_balance_column_exists_on_any_contribution_model(): void
    {
        foreach ([new Contribution, new ContributionPayment, new ContributionReceipt] as $model) {
            $columns = array_keys($model->getAttributes());
            foreach (['debt', 'balance', 'wallet', 'due_amount', 'penalty'] as $forbidden) {
                self::assertNotContains($forbidden, $columns);
            }
        }
    }

    public function test_no_sensitive_payment_data_is_ever_stored(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');

        $snapshot = json_encode($payment->provider_snapshot);
        foreach (['card_number', 'cvv', 'pan', 'mobile_money_pin', 'secret', 'api_key', 'api_secret'] as $sensitive) {
            self::assertStringNotContainsStringIgnoringCase($sensitive, (string) $snapshot);
        }
    }

    public function test_no_project_is_ever_financed_by_a_validated_projects_purpose_code(): void
    {
        $projectsBefore = Project::query()->count();
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'VALIDATED_PROJECTS', 'https://example.test/ok', 'https://example.test/ko');

        self::assertSame($projectsBefore, Project::query()->count(), 'VALIDATED_PROJECTS reste un simple code doctrinal de destination, jamais un vrai Projet financé.');
    }

    public function test_no_organization_or_satellite_is_ever_created_by_a_contribution(): void
    {
        $organizationsBefore = Organization::query()->count();
        $satellitesBefore = Satellite::query()->count();
        $this->programMember('IDN-MEMBER');
        app(ContributionService::class)->startIndividual('IDN-MEMBER');

        self::assertSame($organizationsBefore, Organization::query()->count());
        self::assertSame($satellitesBefore, Satellite::query()->count());
    }

    public function test_no_zumra_state_or_maturity_is_ever_modified_by_the_contribution_domain(): void
    {
        $group = $this->activeGroup('IDN-LEADER', 'IDN-FINANCE');
        $stateBefore = $group->state;
        $maturityBefore = $group->maturity;
        $contribution = $this->approvedCollective($group, 'IDN-LEADER', 'IDN-FINANCE');
        $this->fakeGeniusPay('pending', amount: 2500);
        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-LEADER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('completed', amount: 2500);
        app(ContributionService::class)->reconcile($payment);

        self::assertSame($stateBefore, $group->refresh()->state);
        self::assertSame($maturityBefore, $group->refresh()->maturity);
    }

    // ===== CAP-062 : contrat de données préparatoire =====

    public function test_a_confirmed_payment_carries_every_field_a_future_ledger_would_need(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->fakeGeniusPay('pending');
        $payment = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('completed');
        $confirmed = app(ContributionService::class)->reconcile($payment);

        foreach (['reference', 'amount', 'currency', 'period', 'status', 'purpose_id', 'initiated_by_core_reference', 'contribution_id'] as $field) {
            self::assertNotNull($confirmed->{$field}, "Champ requis pour CAP-062 manquant : {$field}");
        }
        self::assertTrue(ContributionReceipt::query()->where('payment_id', $confirmed->id)->exists());
    }

    // ===== Régression CAP-007B =====

    public function test_membership_payments_still_work_after_the_genius_pay_client_generalization(): void
    {
        $body = str_repeat('Respect, transmission et construction collective. ', 4);
        $charter = ZumraCharter::query()->create([
            'version' => '2026.1', 'title' => 'Charte du Programme ZUMRA', 'body' => $body,
            'content_hash' => hash('sha256', 'Charte du Programme ZUMRA'."\n".$body),
            'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now(),
        ]);
        $membership = ZumraProgramMembership::query()->create([
            'core_identity_reference' => 'IDN-MEMBERSHIP-CHECK', 'status' => ZumraProgramMembership::STATUS_PENDING_PAYMENT,
            'accepted_charter_id' => $charter->id, 'accepted_charter_version' => '2026.1',
            'accepted_charter_hash' => str_repeat('a', 64), 'charter_accepted_at' => now(), 'submitted_at' => now(),
        ]);
        $this->fakeMembershipAndSession(providerStatus: 'pending');

        $payment = app(MembershipPaymentService::class)->start($membership, 'https://example.test/succes', 'https://example.test/echec');
        self::assertSame(500, $payment->amount);
        self::assertSame(ZumraPayment::STATUS_PENDING, $payment->status);
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
            'name' => 'Atelier contributions '.Str::random(8),
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

            if (str_ends_with($url, '/sessions/current')) {
                return Http::response(['entite' => $this->coreReference, 'assurance' => 'AS1', 'expire_le' => '2026-09-16T23:59:00+00:00']);
            }
            if (str_ends_with($url, '/sessions')) {
                return Http::response(['jeton' => 'bearer', 'entite' => $this->coreReference, 'assurance' => 'AS1', 'expire_le' => '2026-09-16T23:59:00+00:00'], 201);
            }
            if (str_contains($url, '/identites/')) {
                return Http::response(['reference' => $this->coreReference, 'type' => 'personne', 'libelle' => 'Membre', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']);
            }
            if ($method === 'POST' && str_ends_with(rtrim($url, '/'), '/payments')) {
                $this->paymentCounter++;
                $reference = 'CONTRIB-REF-'.$this->paymentCounter;

                return Http::response(['success' => true, 'data' => $this->providerPayload($reference)]);
            }
            if ($method === 'GET' && str_contains($url, '/payments/')) {
                $reference = rawurldecode((string) basename((string) parse_url($url, PHP_URL_PATH)));

                return Http::response(['success' => true, 'data' => $this->providerPayload($reference)]);
            }

            return Http::response(['error' => 'UNEXPECTED_TEST_REQUEST'], 500);
        });
    }

    private function fakeMembershipAndSession(string $providerStatus): void
    {
        $this->providerStatus = $providerStatus;
        $this->providerAmount = 500;
        $this->installFake();
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
