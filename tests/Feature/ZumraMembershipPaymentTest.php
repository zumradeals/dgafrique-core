<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ZumraPayment;
use App\Models\ZumraPaymentReceipt;
use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use App\Models\ZumraProgramMembershipEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ZumraMembershipPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
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

        $this->get('/zumra/adhesion/paiement/retour?outcome=success')->assertOk()->assertSee('Votre adhésion est active');
        self::assertSame(ZumraProgramMembership::STATUS_ACTIVE, $membership->refresh()->status);
        self::assertNotNull($membership->activated_at);
        self::assertSame(1, ZumraPaymentReceipt::query()->count());
        self::assertSame(1, ZumraProgramMembershipEvent::query()->where('event', 'PAYMENT_CONFIRMED')->count());

        $this->get('/zumra/adhesion/paiement/retour?outcome=success')->assertOk();
        self::assertSame(1, ZumraPaymentReceipt::query()->count());
        self::assertSame(1, ZumraProgramMembershipEvent::query()->where('event', 'PAYMENT_CONFIRMED')->count());
    }

    public function test_a_success_query_parameter_never_activates_a_pending_provider_payment(): void
    {
        $membership = $this->pendingMembership();
        $this->signIn(providerStatus: 'pending');
        $this->post('/zumra/adhesion/paiement')->assertRedirect();

        $this->get('/zumra/adhesion/paiement/retour?outcome=success&status=completed')->assertOk()->assertSee('Paiement non confirmé');
        self::assertSame(ZumraProgramMembership::STATUS_PENDING_PAYMENT, $membership->refresh()->status);
        self::assertSame(0, ZumraPaymentReceipt::query()->count());
    }

    public function test_a_receipt_is_private_to_its_canonical_identity(): void
    {
        $membership = $this->pendingMembership();
        $this->signIn(providerStatus: 'pending');
        $this->post('/zumra/adhesion/paiement');
        $this->fakeRequests(providerStatus: 'completed');
        $this->get('/zumra/adhesion/paiement/retour');
        $receipt = ZumraPaymentReceipt::query()->sole();

        $this->get(route('zumra.payment.receipt', $receipt))->assertOk()->assertSee($receipt->number);
        session()->flush();
        $this->signIn(reference: 'IDN-PER-000000099', providerStatus: 'completed');
        $this->get(route('zumra.payment.receipt', $receipt))->assertNotFound();
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
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer', 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-15T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre ZUMRA', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-15T23:59:00+00:00']),
            'https://geniuspay.ci/api/v1/merchant/payments' => Http::response(['success' => true, 'data' => $this->providerPayload('pending')], 201),
            'https://geniuspay.ci/api/v1/merchant/payments/*' => Http::response(['success' => true, 'data' => $this->providerPayload($providerStatus)]),
        ]);
    }

    private function providerPayload(string $status): array
    {
        return ['id' => 'pay-1', 'reference' => 'REF-001', 'amount' => 500, 'status' => $status,
            'environment' => 'live', 'checkout_url' => 'https://checkout.example/pay/REF-001',
            'completed_at' => $status === 'completed' ? now()->toIso8601String() : null];
    }
}
