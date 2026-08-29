<?php

declare(strict_types=1);

namespace Tests\ExternalContracts;

use App\Application\Zahab\ZahabWalletService;
use App\Models\LedgerEntry;
use App\Models\ZahabAcquisition;
use App\Models\ZahabWallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class PaymentRecoveryExternalContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config()->set('payments.geniuspay.environment', 'live');
        config()->set('payments.geniuspay.api_key', 'stage-c-not-a-real-key');
        config()->set('payments.geniuspay.api_secret', 'stage-c-not-a-real-secret');
        config()->set('payments.geniuspay.base_url', 'https://geniuspay.test/api/v1/merchant');
        config()->set('payments.geniuspay.reconciliation.stale_after_minutes', 5);
        config()->set('payments.geniuspay.reconciliation.batch_size', 10);
    }

    public function test_scheduler_recovers_a_late_completion_without_a_browser_return_and_only_once(): void
    {
        $acquisition = $this->pendingAcquisition('ACQ-LATE-001');
        Http::fake([
            'geniuspay.test/api/v1/merchant/payments/*' => Http::response([
                'data' => $this->providerPayload('ACQ-LATE-001', 'completed'),
            ]),
        ]);

        $this->artisan('payments:reconcile-pending-external')->assertSuccessful();

        self::assertSame(ZahabAcquisition::STATUS_COMPLETED, $acquisition->refresh()->status);
        self::assertNotNull($acquisition->credited_at);
        self::assertSame(1, LedgerEntry::query()->count());
        self::assertSame(5000, app(ZahabWalletService::class)->balance($this->wallet()));

        $this->artisan('payments:reconcile-pending-external')->assertSuccessful();
        self::assertSame(1, LedgerEntry::query()->count(), 'Une tentative finale ne doit plus être reprise ni recréditée.');
    }

    public function test_one_provider_failure_does_not_block_other_pending_attempts_and_is_observable(): void
    {
        $healthy = $this->pendingAcquisition('ACQ-HEALTHY-001', 'IDN-HEALTHY');
        $unavailable = $this->pendingAcquisition('ACQ-UNAVAILABLE-001', 'IDN-UNAVAILABLE');

        Http::fake(function (Request $request) {
            if (str_ends_with($request->url(), '/ACQ-HEALTHY-001')) {
                return Http::response(['data' => $this->providerPayload('ACQ-HEALTHY-001', 'failed')]);
            }

            return Http::response([], 503);
        });

        $this->artisan('payments:reconcile-pending-external')
            ->expectsOutputToContain('1 tentative(s) réconciliée(s), 1 échec(s)')
            ->assertFailed();

        self::assertSame(ZahabAcquisition::STATUS_FAILED, $healthy->refresh()->status);
        self::assertSame(ZahabAcquisition::STATUS_PENDING, $unavailable->refresh()->status);
        self::assertSame(0, LedgerEntry::query()->count());
        Http::assertSentCount(2);
    }

    public function test_recent_and_non_geniuspay_records_are_never_polled(): void
    {
        $recent = $this->pendingAcquisition('ACQ-RECENT-001');
        $recent->touch();
        $local = $this->pendingAcquisition('ACQ-ZAHAB-001');
        $local->update(['provider' => 'ZAHAB', 'environment' => 'zahab']);
        Http::fake();

        $this->artisan('payments:reconcile-pending-external')->assertSuccessful();

        Http::assertNothingSent();
        self::assertSame(ZahabAcquisition::STATUS_PENDING, $recent->refresh()->status);
        self::assertSame(ZahabAcquisition::STATUS_PENDING, $local->refresh()->status);
    }

    private function pendingAcquisition(string $reference, string $actor = 'IDN-STAGE-C'): ZahabAcquisition
    {
        $acquisition = ZahabAcquisition::query()->create([
            'person_core_reference' => $actor,
            'provider' => 'GENIUSPAY',
            'reference' => $reference,
            'provider_id' => 'pay-'.$reference,
            'amount' => 5000,
            'currency' => 'XOF',
            'environment' => 'live',
            'status' => ZahabAcquisition::STATUS_PENDING,
            'checkout_url' => 'https://checkout.example/'.$reference,
        ]);

        ZahabAcquisition::query()->whereKey($acquisition->id)->update([
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);

        return $acquisition->refresh();
    }

    /** @return array<string, mixed> */
    private function providerPayload(string $reference, string $status): array
    {
        return [
            'id' => 'pay-'.$reference,
            'reference' => $reference,
            'amount' => 5000,
            'status' => $status,
            'environment' => 'live',
            'fees' => 0,
            'net_amount' => 5000,
            'completed_at' => $status === 'completed' ? now()->toIso8601String() : null,
        ];
    }

    private function wallet(): ZahabWallet
    {
        return app(ZahabWalletService::class)->walletFor(
            ZahabWallet::SUBJECT_PERSON,
            'IDN-STAGE-C',
            'IDN-STAGE-C',
        );
    }
}
