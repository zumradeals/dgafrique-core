<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Zahab\ZahabWalletService;
use App\Application\Zumra\MembershipPaymentService;
use App\Http\Controllers\ZumraMembershipPaymentController;
use App\Models\LedgerEntry;
use App\Models\ZahabWallet;
use App\Models\ZumraCharter;
use App\Models\ZumraPayment;
use App\Models\ZumraPaymentReceipt;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * ADHESION-ZAHAB-001 — raccordement de l'adhésion ZUMRA (CAP-007B) au Wallet ZAHAB. Réutilise
 * intégralement le moteur existant (MembershipPaymentService, ZahabWalletService, LedgerService) :
 * GeniusPay reste intact et inchangé, ce sont désormais deux moyens de paiement distincts pour la
 * même adhésion. `ZumraProgramMembership` n'a aucune référence à une ZUMRA précise (adhésion au
 * Programme, pas à une communauté) : un seul mouvement est produit, le DEBIT du Wallet Personne,
 * jamais un Wallet ZUMRA/DG Afrique fabriqué.
 */
final class AdhesionZahabTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('payments.membership.enabled', true);
        config()->set('payments.membership.amount', 500);
        config()->set('payments.membership.currency', 'XOF');
    }

    public function test_a_membership_is_payable_with_a_sufficiently_funded_wallet(): void
    {
        $membership = $this->pendingMembership('IDN-MEMBER');
        $wallet = $this->creditPersonalWallet('IDN-MEMBER', 1500);

        $payment = app(MembershipPaymentService::class)->payWithZahabWallet($membership, 'IDN-MEMBER');

        self::assertSame(ZumraPayment::STATUS_COMPLETED, $payment->status);
        self::assertSame('ZAHAB', $payment->provider);
        self::assertSame(ZumraProgramMembership::STATUS_ACTIVE, $membership->refresh()->status);
        self::assertNotNull($membership->activated_at);
        self::assertSame(1000, app(ZahabWalletService::class)->balance($wallet->fresh()));
    }

    public function test_the_correct_personal_wallet_is_debited(): void
    {
        $membership = $this->pendingMembership('IDN-MEMBER');
        $wallet = $this->creditPersonalWallet('IDN-MEMBER', 1500);

        app(MembershipPaymentService::class)->payWithZahabWallet($membership, 'IDN-MEMBER');

        $debit = LedgerEntry::query()->where('wallet_id', $wallet->id)->where('direction', LedgerEntry::DIRECTION_DEBIT)->sole();
        self::assertSame(ZahabWallet::SUBJECT_PERSON, $debit->subject_type);
        self::assertSame('IDN-MEMBER', $debit->subject_reference);
    }

    public function test_the_amount_debited_matches_the_configured_membership_amount_exactly(): void
    {
        config()->set('payments.membership.amount', 750);
        $membership = $this->pendingMembership('IDN-MEMBER');
        $this->creditPersonalWallet('IDN-MEMBER', 1500);

        $payment = app(MembershipPaymentService::class)->payWithZahabWallet($membership, 'IDN-MEMBER');

        self::assertSame(750, $payment->amount);
        self::assertSame(750, app(ZahabWalletService::class)->balance($this->personalWallet('IDN-MEMBER')));
    }

    public function test_insufficient_wallet_balance_is_refused_and_leaves_no_trace(): void
    {
        $membership = $this->pendingMembership('IDN-MEMBER');
        $this->creditPersonalWallet('IDN-MEMBER', 100); // < 500 requis

        $this->assertAborts(409, fn () => app(MembershipPaymentService::class)->payWithZahabWallet($membership, 'IDN-MEMBER'));

        self::assertSame(0, ZumraPayment::query()->count(), 'Un échec pour fonds insuffisants ne doit laisser aucune ligne de paiement (rollback complet).');
        self::assertSame(ZumraProgramMembership::STATUS_PENDING_PAYMENT, $membership->refresh()->status);
        self::assertSame(100, app(ZahabWalletService::class)->balance($this->personalWallet('IDN-MEMBER')));
    }

    public function test_the_balance_never_goes_negative(): void
    {
        $membership = $this->pendingMembership('IDN-MEMBER');
        $this->creditPersonalWallet('IDN-MEMBER', 499);

        $this->assertAborts(409, fn () => app(MembershipPaymentService::class)->payWithZahabWallet($membership, 'IDN-MEMBER'));

        self::assertGreaterThanOrEqual(0, app(ZahabWalletService::class)->balance($this->personalWallet('IDN-MEMBER')));
    }

    public function test_a_ledger_entry_is_created_for_the_membership_payment(): void
    {
        $membership = $this->pendingMembership('IDN-MEMBER');
        $wallet = $this->creditPersonalWallet('IDN-MEMBER', 1500);

        app(MembershipPaymentService::class)->payWithZahabWallet($membership, 'IDN-MEMBER');

        $debit = LedgerEntry::query()->where('wallet_id', $wallet->id)->where('direction', LedgerEntry::DIRECTION_DEBIT)->sole();
        self::assertSame(500, $debit->amount);
        self::assertSame(ZahabWalletService::REASON_MEMBERSHIP_PAYMENT, $debit->purpose_code);
        self::assertNotNull($debit->zahab_operation_reference);
        self::assertStringContainsString($membership->id, (string) $debit->zahab_operation_reference);
        // Étanchéité : aucune deuxième écriture SOURCE_MEMBERSHIP_PAYMENT pour le même événement.
        self::assertSame(0, LedgerEntry::query()->where('source_type', LedgerEntry::SOURCE_MEMBERSHIP_PAYMENT)->count());
    }

    public function test_a_receipt_is_issued_for_a_zahab_funded_membership(): void
    {
        $membership = $this->pendingMembership('IDN-MEMBER');
        $this->creditPersonalWallet('IDN-MEMBER', 1500);

        $payment = app(MembershipPaymentService::class)->payWithZahabWallet($membership, 'IDN-MEMBER');

        $receipt = ZumraPaymentReceipt::query()->where('payment_id', $payment->id)->sole();
        self::assertSame('ZAHAB', $receipt->provider);
        self::assertSame(500, $receipt->amount);
        self::assertSame('XOF', $receipt->currency);
        self::assertSame('IDN-MEMBER', $receipt->core_identity_reference);
    }

    public function test_retrying_after_success_never_double_debits(): void
    {
        $membership = $this->pendingMembership('IDN-MEMBER');
        $this->creditPersonalWallet('IDN-MEMBER', 1500);
        app(MembershipPaymentService::class)->payWithZahabWallet($membership, 'IDN-MEMBER');

        $this->assertAborts(409, fn () => app(MembershipPaymentService::class)->payWithZahabWallet($membership->refresh(), 'IDN-MEMBER'));

        self::assertSame(1, ZumraPayment::query()->count());
        self::assertSame(1000, app(ZahabWalletService::class)->balance($this->personalWallet('IDN-MEMBER')));
    }

    public function test_a_stranger_cannot_pay_someone_elses_membership(): void
    {
        $membership = $this->pendingMembership('IDN-MEMBER');
        $this->creditPersonalWallet('IDN-MEMBER', 1500);

        $this->assertAborts(403, fn () => app(MembershipPaymentService::class)->payWithZahabWallet($membership, 'IDN-STRANGER'));
        self::assertSame(ZumraProgramMembership::STATUS_PENDING_PAYMENT, $membership->refresh()->status);
    }

    public function test_a_stranger_cannot_debit_their_own_wallet_to_pay_someone_elses_membership_either(): void
    {
        // Preuve supplémentaire : même en créditant SON PROPRE Wallet, un tiers ne peut pas régler
        // l'adhésion d'un autre — l'autorité est vérifiée avant toute mutation Wallet.
        $membership = $this->pendingMembership('IDN-MEMBER');
        $this->creditPersonalWallet('IDN-STRANGER', 1500);

        $this->assertAborts(403, fn () => app(MembershipPaymentService::class)->payWithZahabWallet($membership, 'IDN-STRANGER'));
        self::assertSame(1500, app(ZahabWalletService::class)->balance($this->personalWallet('IDN-STRANGER')));
    }

    public function test_a_membership_that_is_no_longer_payable_produces_no_orphan_debit(): void
    {
        $membership = $this->pendingMembership('IDN-MEMBER');
        $this->creditPersonalWallet('IDN-MEMBER', 1500);
        $membership->update(['status' => ZumraProgramMembership::STATUS_ACTIVE, 'activated_at' => now()]);

        $this->assertAborts(409, fn () => app(MembershipPaymentService::class)->payWithZahabWallet($membership->fresh(), 'IDN-MEMBER'));

        self::assertSame(0, ZumraPayment::query()->count());
        self::assertSame(1500, app(ZahabWalletService::class)->balance($this->personalWallet('IDN-MEMBER')));
    }

    public function test_membership_payment_is_refused_while_disabled(): void
    {
        config()->set('payments.membership.enabled', false);
        $membership = $this->pendingMembership('IDN-MEMBER');
        $this->creditPersonalWallet('IDN-MEMBER', 1500);

        $this->assertAborts(409, fn () => app(MembershipPaymentService::class)->payWithZahabWallet($membership, 'IDN-MEMBER'));
        self::assertSame(1500, app(ZahabWalletService::class)->balance($this->personalWallet('IDN-MEMBER')));
    }

    public function test_no_project_wallet_subject_is_ever_introduced(): void
    {
        self::assertNotContains('PROJECT', ZahabWallet::SUBJECTS);
        self::assertSame(0, ZahabWallet::query()->where('subject_type', 'PROJECT')->count());
    }

    public function test_no_generic_wallet_mutation_route_was_added(): void
    {
        $reflection = new \ReflectionClass(ZumraMembershipPaymentController::class);
        $methodNames = array_map(static fn (\ReflectionMethod $m): string => $m->getName(), $reflection->getMethods(\ReflectionMethod::IS_PUBLIC));
        foreach (['credit', 'debit', 'walletCredit', 'walletDebit', 'walletTransfer'] as $forbidden) {
            self::assertNotContains($forbidden, $methodNames);
        }
    }

    // ===== Coexistence GeniusPay =====

    public function test_the_pre_existing_geniuspay_membership_flow_still_works_untouched(): void
    {
        config()->set('payments.geniuspay.environment', 'live');
        config()->set('payments.geniuspay.api_key', 'pk_live_test');
        config()->set('payments.geniuspay.api_secret', 'sk_live_test');
        $this->pendingMembership('IDN-MEMBER');
        $this->fakeGeniusPay('IDN-MEMBER', 'pending');

        $payment = app(MembershipPaymentService::class)->start(
            ZumraProgramMembership::query()->where('core_identity_reference', 'IDN-MEMBER')->sole(),
            'https://example.test/ok', 'https://example.test/ko',
        );

        self::assertSame('GENIUSPAY', $payment->provider);
        self::assertSame(500, $payment->amount);
        self::assertSame(ZumraPayment::STATUS_PENDING, $payment->status);
    }

    // ===== HTTP =====

    public function test_the_membership_page_shows_the_zahab_balance_and_pay_button(): void
    {
        $this->pendingMembership('IDN-MEMBER');
        $this->creditPersonalWallet('IDN-MEMBER', 1500);
        $this->signIn('IDN-MEMBER');

        $this->get(route('zumra.membership.show'))
            ->assertOk()
            ->assertSee('Wallet ZAHAB', false)
            ->assertSee('avec mon Wallet', false);
    }

    public function test_the_zahab_membership_route_activates_the_membership(): void
    {
        $membership = $this->pendingMembership('IDN-MEMBER');
        $this->creditPersonalWallet('IDN-MEMBER', 1500);
        $this->signIn('IDN-MEMBER');

        $this->post(route('zumra.payment.zahab.store'))->assertRedirect(route('zumra.membership.show'));

        self::assertSame(ZumraProgramMembership::STATUS_ACTIVE, $membership->refresh()->status);
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

    private function creditPersonalWallet(string $identity, int $amount): ZahabWallet
    {
        $wallet = app(ZahabWalletService::class)->walletFor(ZahabWallet::SUBJECT_PERSON, $identity, $identity);
        app(ZahabWalletService::class)->credit($wallet, $amount, ZahabWalletService::REASON_AID, (string) Str::uuid(), 'IDN-ADMIN');

        return $wallet->fresh();
    }

    private function personalWallet(string $identity): ZahabWallet
    {
        return app(ZahabWalletService::class)->walletFor(ZahabWallet::SUBJECT_PERSON, $identity, $identity);
    }

    private function pendingMembership(string $identity): ZumraProgramMembership
    {
        $body = str_repeat('Respect, transmission et construction collective. ', 4);
        $charter = ZumraCharter::query()->firstOrCreate(
            ['version' => '2026.1'],
            [
                'title' => 'Charte du Programme ZUMRA', 'body' => $body,
                'content_hash' => hash('sha256', 'Charte du Programme ZUMRA'."\n".$body),
                'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now(),
            ]
        );

        return ZumraProgramMembership::query()->create([
            'core_identity_reference' => $identity, 'status' => ZumraProgramMembership::STATUS_PENDING_PAYMENT,
            'accepted_charter_id' => $charter->id, 'accepted_charter_version' => '2026.1',
            'accepted_charter_hash' => str_repeat('a', 64), 'charter_accepted_at' => now(), 'submitted_at' => now(),
        ]);
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

    private function fakeGeniusPay(string $reference, string $providerStatus): void
    {
        Http::fake(function (ClientRequest $request) use ($reference, $providerStatus) {
            $url = $request->url();
            if (str_ends_with($url, '/sessions/current')) {
                return Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-09-16T23:59:00+00:00']);
            }
            if (str_ends_with($url, '/sessions')) {
                return Http::response(['jeton' => 'bearer', 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-09-16T23:59:00+00:00'], 201);
            }
            if (str_contains($url, '/identites/')) {
                return Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']);
            }
            if (str_contains($url, '/payments')) {
                return Http::response(['success' => true, 'data' => [
                    'id' => 'pay-1', 'reference' => 'ADH-ZAHAB-TEST-REF', 'amount' => 500, 'status' => $providerStatus,
                    'environment' => 'live', 'checkout_url' => 'https://checkout.example/pay/ADH-ZAHAB-TEST-REF',
                    'completed_at' => $providerStatus === 'completed' ? now()->toIso8601String() : null,
                ]]);
            }

            return Http::response(['error' => 'UNEXPECTED_TEST_REQUEST'], 500);
        });
    }
}
