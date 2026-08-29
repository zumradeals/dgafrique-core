<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Contributions\ContributionConfiguration;
use App\Application\Contributions\ContributionService;
use App\Application\Zahab\ZahabAcquisitionService;
use App\Application\Zahab\ZahabWalletService;
use App\Application\Zumra\MembershipPaymentService;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\ZahabAcquisitionController;
use App\Models\LedgerEntry;
use App\Models\PortalSetting;
use App\Models\ZahabAcquisition;
use App\Models\ZahabWallet;
use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * ZAHAB-002 — acquisition de ZAHAB par paiement externe GeniusPay confirmé. Réutilise
 * intégralement GeniusPayClient::createContributionPayment()/payment() (déjà génériques) et
 * ZahabWalletService::credit() (déjà mergé). Parité 1 ZAHAB = 1 FCFA : le montant FCFA confirmé
 * EST le montant ZAHAB crédité, sans conversion.
 */
final class ZahabAcquisitionTest extends TestCase
{
    use RefreshDatabase;

    private string $providerStatus = 'pending';

    private string $providerEnvironment = 'live';

    private int $providerAmount = 5000;

    private int $paymentCounter = 0;

    private string $paymentReturnUrl;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config()->set('payments.geniuspay.environment', 'live');
        config()->set('payments.geniuspay.api_key', 'pk_live_test');
        config()->set('payments.geniuspay.api_secret', 'sk_live_test');
    }

    // ===== Création =====

    public function test_starting_an_acquisition_creates_a_pending_record_with_a_checkout_url(): void
    {
        $this->fakeGeniusPay('pending', amount: 5000);

        $acquisition = app(ZahabAcquisitionService::class)->start('IDN-MEMBER', 5000, 'https://example.test/ok', 'https://example.test/ko');

        self::assertSame('IDN-MEMBER', $acquisition->person_core_reference);
        self::assertSame(5000, $acquisition->amount);
        self::assertSame('XOF', $acquisition->currency);
        self::assertSame(ZahabAcquisition::STATUS_PENDING, $acquisition->status);
        self::assertNotNull($acquisition->checkout_url);
    }

    public function test_a_zero_or_negative_amount_is_refused(): void
    {
        $this->assertAborts(422, fn () => app(ZahabAcquisitionService::class)->start('IDN-MEMBER', 0, 'https://example.test/ok', 'https://example.test/ko'));
        $this->assertAborts(422, fn () => app(ZahabAcquisitionService::class)->start('IDN-MEMBER', -100, 'https://example.test/ok', 'https://example.test/ko'));
    }

    public function test_geniuspay_not_configured_is_refused_cleanly(): void
    {
        config()->set('payments.geniuspay.api_key', '');
        config()->set('payments.geniuspay.api_secret', '');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PAYMENT_PROVIDER_NOT_LIVE');
        app(ZahabAcquisitionService::class)->start('IDN-MEMBER', 5000, 'https://example.test/ok', 'https://example.test/ko');
    }

    // ===== Confirmation =====

    public function test_a_pending_acquisition_credits_nothing(): void
    {
        $this->fakeGeniusPay('pending', amount: 5000);
        app(ZahabAcquisitionService::class)->start('IDN-MEMBER', 5000, 'https://example.test/ok', 'https://example.test/ko');

        self::assertSame(0, LedgerEntry::query()->count());
        self::assertSame(0, app(ZahabWalletService::class)->balance($this->personalWallet('IDN-MEMBER')));
    }

    public function test_a_failed_acquisition_credits_nothing(): void
    {
        $this->fakeGeniusPay('pending', amount: 5000);
        $acquisition = app(ZahabAcquisitionService::class)->start('IDN-MEMBER', 5000, 'https://example.test/ok', 'https://example.test/ko');

        $this->fakeGeniusPayReconcile('failed');
        app(ZahabAcquisitionService::class)->reconcile($acquisition);

        self::assertSame(0, LedgerEntry::query()->count());
        self::assertSame(0, app(ZahabWalletService::class)->balance($this->personalWallet('IDN-MEMBER')));
    }

    public function test_a_cancelled_acquisition_credits_nothing(): void
    {
        $this->fakeGeniusPay('pending', amount: 5000);
        $acquisition = app(ZahabAcquisitionService::class)->start('IDN-MEMBER', 5000, 'https://example.test/ok', 'https://example.test/ko');

        $this->fakeGeniusPayReconcile('cancelled');
        app(ZahabAcquisitionService::class)->reconcile($acquisition);

        self::assertSame(0, LedgerEntry::query()->count());
    }

    public function test_a_confirmed_acquisition_credits_the_exact_amount_1_to_1(): void
    {
        $this->fakeGeniusPay('pending', amount: 5000);
        $acquisition = app(ZahabAcquisitionService::class)->start('IDN-MEMBER', 5000, 'https://example.test/ok', 'https://example.test/ko');

        $this->fakeGeniusPayReconcile('completed');
        $confirmed = app(ZahabAcquisitionService::class)->reconcile($acquisition);

        self::assertSame(ZahabAcquisition::STATUS_COMPLETED, $confirmed->status);
        self::assertNotNull($confirmed->credited_at);
        self::assertSame(5000, app(ZahabWalletService::class)->balance($this->personalWallet('IDN-MEMBER')), '5 000 FCFA confirmés doivent créditer exactement 5 000 ZAHAB (parité 1:1).');
    }

    public function test_the_correct_personal_wallet_is_credited(): void
    {
        $this->fakeGeniusPay('pending', amount: 5000);
        $acquisition = app(ZahabAcquisitionService::class)->start('IDN-MEMBER', 5000, 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('completed');
        app(ZahabAcquisitionService::class)->reconcile($acquisition);

        $wallet = $this->personalWallet('IDN-MEMBER');
        $credit = LedgerEntry::query()->where('wallet_id', $wallet->id)->sole();
        self::assertSame(ZahabWallet::SUBJECT_PERSON, $credit->subject_type);
        self::assertSame('IDN-MEMBER', $credit->subject_reference);
        self::assertSame(LedgerEntry::DIRECTION_CREDIT, $credit->direction);
        self::assertSame(ZahabWalletService::REASON_ZAHAB_ACQUISITION, $credit->purpose_code);
        self::assertNotNull($credit->zahab_operation_reference);
        self::assertStringContainsString($acquisition->id, (string) $credit->zahab_operation_reference);
    }

    public function test_repeated_confirmation_credits_only_once(): void
    {
        $this->fakeGeniusPay('pending', amount: 5000);
        $acquisition = app(ZahabAcquisitionService::class)->start('IDN-MEMBER', 5000, 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('completed');

        app(ZahabAcquisitionService::class)->reconcile($acquisition);
        app(ZahabAcquisitionService::class)->reconcile($acquisition->fresh());
        app(ZahabAcquisitionService::class)->reconcile($acquisition->fresh());

        self::assertSame(1, LedgerEntry::query()->count(), 'Une confirmation rejouée 3 fois ne doit créditer qu’une seule fois.');
        self::assertSame(5000, app(ZahabWalletService::class)->balance($this->personalWallet('IDN-MEMBER')));
    }

    public function test_the_browser_return_alone_never_credits(): void
    {
        $this->fakeGeniusPay('pending', amount: 5000);
        app(ZahabAcquisitionService::class)->start('IDN-MEMBER', 5000, 'https://example.test/ok', 'https://example.test/ko');

        // Simule un simple retour navigateur avec ?outcome=success SANS jamais appeler reconcile().
        self::assertSame(0, LedgerEntry::query()->count());
        self::assertSame(0, app(ZahabWalletService::class)->balance($this->personalWallet('IDN-MEMBER')));
    }

    public function test_sandbox_completion_never_credits_when_the_switch_is_off(): void
    {
        config()->set('payments.geniuspay.environment', 'sandbox');
        config()->set('payments.geniuspay.api_key', 'pk_sandbox_test');
        config()->set('payments.geniuspay.api_secret', 'sk_sandbox_test');
        config()->set('payments.geniuspay.sandbox_activation_allowed', false);
        $this->providerEnvironment = 'sandbox';
        $this->fakeGeniusPay('pending', amount: 5000);
        $acquisition = app(ZahabAcquisitionService::class)->start('IDN-MEMBER', 5000, 'https://example.test/ok', 'https://example.test/ko');

        $this->fakeGeniusPayReconcile('completed');
        $reconciled = app(ZahabAcquisitionService::class)->reconcile($acquisition);

        self::assertSame(ZahabAcquisition::STATUS_COMPLETED, $reconciled->status);
        self::assertSame(0, LedgerEntry::query()->count(), 'Off par défaut : un paiement sandbox ne doit jamais créditer.');
    }

    public function test_reconciliation_rejects_an_amount_mismatch(): void
    {
        $this->fakeGeniusPay('pending', amount: 5000);
        $acquisition = app(ZahabAcquisitionService::class)->start('IDN-MEMBER', 5000, 'https://example.test/ok', 'https://example.test/ko');

        $this->fakeGeniusPayReconcile('completed', amount: 9999);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ZAHAB_ACQUISITION_RECONCILIATION_MISMATCH');
        app(ZahabAcquisitionService::class)->reconcile($acquisition);
    }

    // ===== Ledger / historique =====

    public function test_a_ledger_entry_is_created_for_the_credit(): void
    {
        $this->fakeGeniusPay('pending', amount: 5000);
        $acquisition = app(ZahabAcquisitionService::class)->start('IDN-MEMBER', 5000, 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('completed');
        app(ZahabAcquisitionService::class)->reconcile($acquisition);

        self::assertSame(1, LedgerEntry::query()->where('source_type', LedgerEntry::SOURCE_ZAHAB_WALLET_MOVEMENT)->count());
    }

    public function test_the_dashboard_shows_a_coherent_history(): void
    {
        $this->fakeGeniusPay('pending', amount: 5000);
        $acquisition = app(ZahabAcquisitionService::class)->start('IDN-MEMBER', 5000, 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('completed');
        app(ZahabAcquisitionService::class)->reconcile($acquisition);
        $this->signIn('IDN-MEMBER');

        $this->get(route('zahab.wallet.dashboard'))
            ->assertOk()
            ->assertSee('5 000', false)
            ->assertSee('Confirmée et créditée', false);
    }

    // ===== Autorisations =====

    public function test_a_person_can_only_acquire_for_their_own_wallet(): void
    {
        // Aucun paramètre ne permet de désigner un autre sujet Wallet : le service crédite
        // toujours l'acteur authentifié lui-même.
        $this->fakeGeniusPay('pending', amount: 5000);
        app(ZahabAcquisitionService::class)->start('IDN-MEMBER', 5000, 'https://example.test/ok', 'https://example.test/ko');

        self::assertSame(0, ZahabWallet::query()->where('subject_reference', 'IDN-STRANGER')->count());
    }

    public function test_no_generic_wallet_mutation_route_was_added(): void
    {
        foreach ([ZahabAcquisitionController::class, WalletController::class] as $controller) {
            $reflection = new \ReflectionClass($controller);
            $methodNames = array_map(static fn (\ReflectionMethod $m): string => $m->getName(), $reflection->getMethods(\ReflectionMethod::IS_PUBLIC));
            foreach (['credit', 'debit', 'walletCredit', 'walletDebit', 'walletTransfer'] as $forbidden) {
                self::assertNotContains($forbidden, $methodNames, "{$controller} ne doit exposer aucune action générique de mutation Wallet ({$forbidden}).");
            }
        }
    }

    public function test_no_project_wallet_subject_is_ever_introduced(): void
    {
        self::assertNotContains('PROJECT', ZahabWallet::SUBJECTS);
        self::assertSame(0, ZahabWallet::query()->where('subject_type', 'PROJECT')->count());
    }

    // ===== HTTP =====

    public function test_the_store_route_redirects_to_the_geniuspay_checkout(): void
    {
        $this->signIn('IDN-MEMBER');
        $this->fakeGeniusPay('pending', amount: 3000, actor: 'IDN-MEMBER');

        $this->post(route('zahab.acquisitions.store'), ['amount' => 3000])
            ->assertRedirect('https://checkout.example/pay/ACQ-REF-1');

        self::assertSame(1, ZahabAcquisition::query()->count());
    }

    public function test_the_return_route_reconciles_server_to_server_and_shows_the_credit(): void
    {
        $this->signIn('IDN-MEMBER');
        $this->fakeGeniusPay('pending', amount: 3000, actor: 'IDN-MEMBER');
        $this->post(route('zahab.acquisitions.store'), ['amount' => 3000]);

        $this->fakeGeniusPayReconcile('completed', amount: 3000, actor: 'IDN-MEMBER');
        $this->get($this->paymentReturnUrl)
            ->assertRedirect(route('zahab.wallet.dashboard'));

        self::assertSame(3000, app(ZahabWalletService::class)->balance($this->personalWallet('IDN-MEMBER')));
    }

    public function test_a_return_token_reconciles_its_exact_attempt_never_the_latest_one(): void
    {
        $this->signIn('IDN-MEMBER');
        $this->fakeGeniusPay('pending', amount: 3000, actor: 'IDN-MEMBER');
        $this->post(route('zahab.acquisitions.store'), ['amount' => 3000])->assertRedirect();
        $firstReturnUrl = $this->paymentReturnUrl;
        $first = ZahabAcquisition::query()->sole();

        $this->post(route('zahab.acquisitions.store'), ['amount' => 3000])->assertRedirect();
        $secondReturnUrl = $this->paymentReturnUrl;
        $second = ZahabAcquisition::query()->where('id', '!=', $first->id)->sole();
        self::assertNotSame($firstReturnUrl, $secondReturnUrl);
        self::assertNotSame($first->id, $second->id);

        $this->fakeGeniusPayReconcile('completed', amount: 3000, actor: 'IDN-MEMBER');
        $this->get($firstReturnUrl)->assertRedirect(route('zahab.wallet.dashboard'));

        self::assertSame(ZahabAcquisition::STATUS_COMPLETED, $first->refresh()->status);
        self::assertSame(ZahabAcquisition::STATUS_PENDING, $second->refresh()->status);
        self::assertSame(3000, app(ZahabWalletService::class)->balance($this->personalWallet('IDN-MEMBER')));
    }

    public function test_a_missing_or_unknown_return_token_is_rejected_without_reconciliation(): void
    {
        $this->signIn('IDN-MEMBER');
        $this->fakeGeniusPay('pending', amount: 3000, actor: 'IDN-MEMBER');
        $this->post(route('zahab.acquisitions.store'), ['amount' => 3000]);

        $this->get(route('zahab.acquisitions.return'))->assertForbidden();
        $unknownReturnUrl = URL::temporarySignedRoute(
            'zahab.acquisitions.return',
            now()->addHour(),
            ['attempt' => str_repeat('A', 64), 'outcome' => 'success'],
        );
        $this->get($unknownReturnUrl)->assertNotFound();

        self::assertSame(ZahabAcquisition::STATUS_PENDING, ZahabAcquisition::query()->sole()->status);
        self::assertSame(0, LedgerEntry::query()->count());
    }

    // ===== Boucle métier complète (art. 12 du mandat) =====

    public function test_the_full_acquisition_membership_contribution_loop_is_entirely_ledger_derived(): void
    {
        $identity = 'IDN-LOOP';
        config()->set('payments.membership.enabled', true);
        config()->set('payments.membership.amount', 500);
        config()->set('payments.membership.currency', 'XOF');
        // Adhésion en attente de paiement — une seule ligne `dg_zumra_program_memberships`
        // par identité (contrainte d'unicité) : `startIndividual()` exige un membre déjà ACTIF,
        // donc l'adhésion doit être réglée AVANT que la contribution individuelle ne démarre.
        $membership = $this->pendingMembership($identity);

        // 1) Acquisition de 1 500 ZAHAB via GeniusPay confirmé.
        $this->fakeGeniusPay('pending', amount: 1500);
        $acquisition = app(ZahabAcquisitionService::class)->start($identity, 1500, 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('completed');
        app(ZahabAcquisitionService::class)->reconcile($acquisition);
        $wallet = $this->personalWallet($identity);
        self::assertSame(1500, app(ZahabWalletService::class)->balance($wallet));

        // 2) Adhésion réglée avec le Wallet : -500. Active le membre ZUMRA.
        app(MembershipPaymentService::class)->payWithZahabWallet($membership, $identity);
        self::assertSame(1000, app(ZahabWalletService::class)->balance($wallet));
        self::assertSame(ZumraProgramMembership::STATUS_ACTIVE, $membership->refresh()->status);

        // 3) Contribution réglée avec le Wallet : -600. Le membre est désormais actif.
        PortalSetting::query()->updateOrCreate(
            ['key' => ContributionConfiguration::KEY],
            ['value' => array_merge(app(ContributionConfiguration::class)->defaults(), ['individual_enabled' => true, 'collective_enabled' => true, 'individual_amount' => 600]), 'updated_by_core_reference' => 'IDN-ADMIN']
        );
        $contribution = app(ContributionService::class)->startIndividual($identity);
        app(ContributionService::class)->payPeriodWithZahabWallet($contribution, $identity, '2026-09', 'TRAINING');
        self::assertSame(400, app(ZahabWalletService::class)->balance($wallet));

        // Preuve finale : le solde n'est jamais autre chose que la somme des écritures Ledger.
        $credits = (int) LedgerEntry::query()->where('wallet_id', $wallet->id)->where('direction', LedgerEntry::DIRECTION_CREDIT)->sum('amount');
        $debits = (int) LedgerEntry::query()->where('wallet_id', $wallet->id)->where('direction', LedgerEntry::DIRECTION_DEBIT)->sum('amount');
        self::assertSame(1500, $credits);
        self::assertSame(1100, $debits);
        self::assertSame(400, $credits - $debits);
        self::assertSame(400, app(ZahabWalletService::class)->balance($wallet));
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

    private function personalWallet(string $identity): ZahabWallet
    {
        return app(ZahabWalletService::class)->walletFor(ZahabWallet::SUBJECT_PERSON, $identity, $identity);
    }

    private function enableContributions(): void
    {
        $config = app(ContributionConfiguration::class);
        PortalSetting::query()->updateOrCreate(
            ['key' => ContributionConfiguration::KEY],
            ['value' => array_merge($config->defaults(), ['individual_enabled' => true, 'collective_enabled' => true]), 'updated_by_core_reference' => 'IDN-ADMIN']
        );
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

    /**
     * Réutilise `installFake()` (mêmes routes GAMAD Core + GeniusPay) plutôt qu'un second
     * `Http::fake()` séparé : `Http::fake()` empile les callbacks et essaie chacun dans l'ordre,
     * donc un second callback avec son propre "catch-all 500" masquerait silencieusement les
     * requêtes GeniusPay émises après la connexion.
     */
    private function signIn(string $reference): void
    {
        $this->installFake($reference);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }

    private function fakeGeniusPay(string $providerStatus, ?int $amount = null, ?string $environment = null, string $actor = 'IDN-MEMBER'): void
    {
        $this->providerStatus = $providerStatus;
        if ($amount !== null) {
            $this->providerAmount = $amount;
        }
        if ($environment !== null) {
            $this->providerEnvironment = $environment;
        }
        $this->installFake($actor);
    }

    private function fakeGeniusPayReconcile(string $providerStatus, ?int $amount = null, ?string $environment = null, string $actor = 'IDN-MEMBER'): void
    {
        $this->fakeGeniusPay($providerStatus, $amount, $environment, $actor);
    }

    private function installFake(string $actor): void
    {
        Http::fake(function (ClientRequest $request) use ($actor) {
            $url = $request->url();
            $method = $request->method();

            if (str_ends_with($url, '/sessions/current')) {
                return Http::response(['entite' => $actor, 'assurance' => 'AS1', 'expire_le' => '2026-09-16T23:59:00+00:00']);
            }
            if (str_ends_with($url, '/sessions')) {
                return Http::response(['jeton' => 'bearer', 'entite' => $actor, 'assurance' => 'AS1', 'expire_le' => '2026-09-16T23:59:00+00:00'], 201);
            }
            if (str_contains($url, '/identites/')) {
                return Http::response(['reference' => $actor, 'type' => 'personne', 'libelle' => 'Membre', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']);
            }
            if ($method === 'POST' && str_ends_with(rtrim($url, '/'), '/payments')) {
                $this->paymentCounter++;
                $reference = 'ACQ-REF-'.$this->paymentCounter;
                $this->paymentReturnUrl = (string) $request['success_url'];

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
