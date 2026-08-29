<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Zumra\MembershipPaymentService;
use App\Models\ZumraPayment;
use App\Models\ZumraPaymentReceipt;
use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use App\Models\ZumraProgramMembershipEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

final class ZumraMembershipPaymentTest extends TestCase
{
    use RefreshDatabase;

    private string $providerStatus = 'pending';
    private string $providerEnvironment = 'live';
    private string $coreReference = 'IDN-PER-000000008';
    private bool $httpIsFaked = false;
    private bool $providerStatusMissing = false;
    private string $paymentReturnUrl;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config()->set('payments.membership.enabled', true);
        config()->set('payments.membership.amount', 500);
        config()->set('payments.geniuspay.environment', 'live');
        config()->set('payments.geniuspay.api_key', 'pk_live_test');
        config()->set('payments.geniuspay.api_secret', 'sk_live_test');
    }

    public function test_a_live_payment_attempt_is_registered_before_checkout(): void
    {
        $membership = $this->pendingMembership();
        $this->signIn(providerStatus: 'pending');

        $this->post('/zumra/adhesion/paiement')->assertRedirect('https://checkout.example/pay/REF-001');
        $payment = ZumraPayment::query()->sole();
        self::assertSame($membership->id, $payment->membership_id);
        self::assertSame(500, $payment->amount);
        self::assertSame('XOF', $payment->currency);
        self::assertSame('live', $payment->environment);
        self::assertSame(ZumraPayment::STATUS_PENDING, $payment->status);
        self::assertSame(ZumraProgramMembership::STATUS_PENDING_PAYMENT, $membership->refresh()->status);
    }

    public function test_only_server_verified_completion_activates_once_and_issues_one_receipt(): void
    {
        $membership = $this->pendingMembership();
        $this->signIn(providerStatus: 'pending');
        $this->post('/zumra/adhesion/paiement')->assertRedirect();
        $this->fakeRequests(providerStatus: 'completed');

        $this->get($this->paymentReturnUrl)->assertOk()->assertSee('Votre adhésion est active');
        self::assertSame(ZumraProgramMembership::STATUS_ACTIVE, $membership->refresh()->status);
        self::assertNotNull($membership->activated_at);
        self::assertSame(1, ZumraPaymentReceipt::query()->count());
        self::assertSame(1, ZumraProgramMembershipEvent::query()->where('event', 'PAYMENT_CONFIRMED')->count());

        $this->get($this->paymentReturnUrl)->assertOk();
        self::assertSame(1, ZumraPaymentReceipt::query()->count());
        self::assertSame(1, ZumraProgramMembershipEvent::query()->where('event', 'PAYMENT_CONFIRMED')->count());
    }

    public function test_a_success_query_parameter_never_activates_a_pending_provider_payment(): void
    {
        $membership = $this->pendingMembership();
        $this->signIn(providerStatus: 'pending');
        $this->post('/zumra/adhesion/paiement')->assertRedirect();

        $this->get($this->paymentReturnUrl.'&status=completed')->assertForbidden();
        self::assertSame(ZumraProgramMembership::STATUS_PENDING_PAYMENT, $membership->refresh()->status);
        self::assertSame(0, ZumraPaymentReceipt::query()->count());
    }

    public function test_a_receipt_is_private_to_its_canonical_identity(): void
    {
        $membership = $this->pendingMembership();
        $this->signIn(providerStatus: 'pending');
        $this->post('/zumra/adhesion/paiement');
        $this->fakeRequests(providerStatus: 'completed');
        $this->get($this->paymentReturnUrl);
        $receipt = ZumraPaymentReceipt::query()->sole();

        $this->get(route('zumra.payment.receipt', $receipt))->assertOk()->assertSee($receipt->number);
        session()->flush();
        $this->signIn(reference: 'IDN-PER-000000099', providerStatus: 'completed');
        $this->get(route('zumra.payment.receipt', $receipt))->assertNotFound();
    }

    public function test_a_sandbox_payment_is_created_with_sandbox_environment(): void
    {
        config()->set('payments.geniuspay.environment', 'sandbox');
        config()->set('payments.geniuspay.api_key', 'pk_sandbox_test');
        config()->set('payments.geniuspay.api_secret', 'sk_sandbox_test');
        $this->providerEnvironment = 'sandbox';
        $this->pendingMembership();
        $this->signIn(providerStatus: 'pending');

        $this->post('/zumra/adhesion/paiement')->assertRedirect();
        self::assertSame('sandbox', ZumraPayment::query()->sole()->environment);
    }

    public function test_sandbox_completion_never_activates_membership_when_the_switch_is_off(): void
    {
        config()->set('payments.geniuspay.environment', 'sandbox');
        config()->set('payments.geniuspay.api_key', 'pk_sandbox_test');
        config()->set('payments.geniuspay.api_secret', 'sk_sandbox_test');
        config()->set('payments.geniuspay.sandbox_activation_allowed', false);
        $this->providerEnvironment = 'sandbox';
        $membership = $this->pendingMembership();
        $this->signIn(providerStatus: 'pending');
        $this->post('/zumra/adhesion/paiement');
        $this->fakeRequests(providerStatus: 'completed');

        $this->get($this->paymentReturnUrl)->assertOk();
        self::assertSame(ZumraProgramMembership::STATUS_PENDING_PAYMENT, $membership->refresh()->status, 'Off par défaut : un paiement sandbox ne doit jamais activer une adhésion.');
        self::assertSame(0, ZumraPaymentReceipt::query()->count());
    }

    public function test_sandbox_completion_activates_membership_only_when_the_switch_is_explicitly_on(): void
    {
        config()->set('payments.geniuspay.environment', 'sandbox');
        config()->set('payments.geniuspay.api_key', 'pk_sandbox_test');
        config()->set('payments.geniuspay.api_secret', 'sk_sandbox_test');
        config()->set('payments.geniuspay.sandbox_activation_allowed', true);
        $this->providerEnvironment = 'sandbox';
        $membership = $this->pendingMembership();
        $this->signIn(providerStatus: 'pending');
        $this->post('/zumra/adhesion/paiement');
        $this->fakeRequests(providerStatus: 'completed');

        $this->get($this->paymentReturnUrl)->assertOk()->assertSee('Votre adhésion est active');
        self::assertSame(ZumraProgramMembership::STATUS_ACTIVE, $membership->refresh()->status);
        self::assertSame('sandbox', ZumraPayment::query()->sole()->environment);
        self::assertSame(1, ZumraPaymentReceipt::query()->count());
    }

    public function test_reconciliation_rejects_a_payment_that_changes_environment_mid_flight(): void
    {
        $membership = $this->pendingMembership();
        $this->fakeRequests(providerStatus: 'pending');
        $payment = app(MembershipPaymentService::class)->start(
            $membership,
            'https://example.test/succes',
            'https://example.test/echec',
        );
        self::assertSame('live', $payment->environment);

        // Le prestataire renvoie soudainement un environnement différent de celui de la
        // tentative d'origine : rejeté, jamais réconcilié silencieusement.
        $this->providerEnvironment = 'sandbox';
        $this->fakeRequests(providerStatus: 'completed');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PAYMENT_RECONCILIATION_MISMATCH');
        app(MembershipPaymentService::class)->reconcile($payment);
    }

    public function test_sandbox_creation_with_a_null_status_is_normalized_to_pending(): void
    {
        config()->set('payments.geniuspay.environment', 'sandbox');
        config()->set('payments.geniuspay.api_key', 'pk_sandbox_test');
        config()->set('payments.geniuspay.api_secret', 'sk_sandbox_test');
        $this->providerEnvironment = 'sandbox';
        $this->providerStatusMissing = true;
        $membership = $this->pendingMembership();
        $this->fakeRequests(providerStatus: 'pending');

        $payment = app(MembershipPaymentService::class)->start(
            $membership,
            'https://example.test/succes',
            'https://example.test/echec',
        );

        self::assertSame(ZumraPayment::STATUS_PENDING, $payment->status, 'Un statut absent sur une création par ailleurs valide (référence, montant, environnement, checkout HTTPS) devient PENDING, jamais une erreur.');
        self::assertSame('sandbox', $payment->environment);
        self::assertSame(500, $payment->amount);
    }

    public function test_a_normal_pending_creation_still_works_unchanged(): void
    {
        $membership = $this->pendingMembership();
        $this->fakeRequests(providerStatus: 'pending');

        $payment = app(MembershipPaymentService::class)->start(
            $membership,
            'https://example.test/succes',
            'https://example.test/echec',
        );

        self::assertSame(ZumraPayment::STATUS_PENDING, $payment->status);
    }

    public function test_reconciliation_with_a_null_status_is_never_tolerated(): void
    {
        $membership = $this->pendingMembership();
        $this->fakeRequests(providerStatus: 'pending');
        $payment = app(MembershipPaymentService::class)->start(
            $membership,
            'https://example.test/succes',
            'https://example.test/echec',
        );

        // Le fallback de création ne s'applique jamais à la lecture/réconciliation : un statut
        // manquant y reste toujours une erreur explicite, jamais une supposition silencieuse.
        $this->providerStatusMissing = true;
        $this->fakeRequests(providerStatus: 'pending');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PAYMENT_PROVIDER_RESPONSE_INVALID');
        app(MembershipPaymentService::class)->reconcile($payment);
    }

    public function test_a_genuinely_invalid_status_is_always_rejected(): void
    {
        $membership = $this->pendingMembership();
        $this->fakeRequests(providerStatus: 'banana');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PAYMENT_PROVIDER_RESPONSE_INVALID');
        app(MembershipPaymentService::class)->start(
            $membership,
            'https://example.test/succes',
            'https://example.test/echec',
        );
    }

    public function test_a_genuinely_invalid_status_is_always_rejected_on_reconciliation(): void
    {
        $membership = $this->pendingMembership();
        $this->fakeRequests(providerStatus: 'pending');
        $payment = app(MembershipPaymentService::class)->start(
            $membership,
            'https://example.test/succes',
            'https://example.test/echec',
        );

        $this->fakeRequests(providerStatus: 'banana');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PAYMENT_PROVIDER_RESPONSE_INVALID');
        app(MembershipPaymentService::class)->reconcile($payment);
    }

    private function pendingMembership(): ZumraProgramMembership
    {
        $body = str_repeat('Respect, transmission et construction collective. ', 4);
        $charter = ZumraCharter::query()->create([
            'version' => '2026.1', 'title' => 'Charte du Programme ZUMRA', 'body' => $body,
            'content_hash' => hash('sha256', 'Charte du Programme ZUMRA'."\n".$body),
            'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now(),
        ]);

        return ZumraProgramMembership::query()->create([
            'core_identity_reference' => 'IDN-PER-000000008', 'status' => ZumraProgramMembership::STATUS_PENDING_PAYMENT,
            'accepted_charter_id' => $charter->id,
            'accepted_charter_version' => '2026.1', 'accepted_charter_hash' => str_repeat('a', 64),
            'charter_accepted_at' => now(), 'submitted_at' => now(),
        ]);
    }

    private function signIn(string $reference = 'IDN-PER-000000008', string $providerStatus = 'pending'): void
    {
        $this->fakeRequests($reference, $providerStatus);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }

    private function fakeRequests(string $reference = 'IDN-PER-000000008', string $providerStatus = 'pending'): void
    {
        $this->coreReference = $reference;
        $this->providerStatus = $providerStatus;
        if ($this->httpIsFaked) return;

        $this->httpIsFaked = true;
        Http::fake(function (ClientRequest $request) {
            $url = $request->url();
            if (str_ends_with($url, '/sessions/current')) {
                return Http::response(['entite' => $this->coreReference, 'assurance' => 'AS1', 'expire_le' => '2026-08-15T23:59:00+00:00']);
            }
            if (str_ends_with($url, '/sessions')) {
                return Http::response(['jeton' => 'bearer', 'entite' => $this->coreReference, 'assurance' => 'AS1', 'expire_le' => '2026-08-15T23:59:00+00:00'], 201);
            }
            if (str_contains($url, '/identites/')) {
                return Http::response(['reference' => $this->coreReference, 'type' => 'personne', 'libelle' => 'Membre ZUMRA', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']);
            }
            if ($request->method() === 'POST' && str_ends_with(rtrim($url, '/'), '/payments')) {
                $this->paymentReturnUrl = (string) $request['success_url'];
            }
            if (str_contains($url, '/payments')) {
                return Http::response(['success' => true, 'data' => $this->providerPayload($this->providerStatus)]);
            }

            return Http::response(['error' => 'UNEXPECTED_TEST_REQUEST'], 500);
        });
    }

    private function providerPayload(string $status): array
    {
        return ['id' => 'pay-1', 'reference' => 'REF-001', 'amount' => 500,
            'status' => $this->providerStatusMissing ? null : $status,
            'environment' => $this->providerEnvironment, 'checkout_url' => 'https://checkout.example/pay/REF-001',
            'completed_at' => $status === 'completed' ? now()->toIso8601String() : null];
    }
}
