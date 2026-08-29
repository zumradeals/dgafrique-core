<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Contributions\ContributionService;
use App\Application\Zahab\ZahabAcquisitionService;
use App\Application\Zumra\MembershipPaymentService;
use App\Models\ContributionPayment;
use App\Models\ZahabAcquisition;
use App\Models\ZumraPayment;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ReconcilePendingExternalPayments extends Command
{
    protected $signature = 'payments:reconcile-pending-external';

    protected $description = 'Réconcilier les tentatives GeniusPay en attente sans dépendre du retour navigateur';

    public function handle(
        MembershipPaymentService $memberships,
        ContributionService $contributions,
        ZahabAcquisitionService $acquisitions,
    ): int {
        $limit = (int) config('payments.geniuspay.reconciliation.batch_size', 100);
        $staleBefore = now()->subMinutes((int) config('payments.geniuspay.reconciliation.stale_after_minutes', 5));
        $processed = 0;
        $failed = 0;

        $targets = [
            [ZumraPayment::class, fn (ZumraPayment $payment) => $memberships->reconcile($payment)],
            [ContributionPayment::class, fn (ContributionPayment $payment) => $contributions->reconcile($payment)],
            [ZahabAcquisition::class, fn (ZahabAcquisition $acquisition) => $acquisitions->reconcile($acquisition)],
        ];

        foreach ($targets as [$modelClass, $reconcile]) {
            /** @var list<Model> $pending */
            $pending = $modelClass::query()
                ->where('provider', 'GENIUSPAY')
                ->whereIn('status', ['PENDING', 'PROCESSING'])
                ->where('updated_at', '<=', $staleBefore)
                ->oldest('updated_at')
                ->oldest('id')
                ->limit($limit)
                ->get()
                ->all();

            foreach ($pending as $payment) {
                try {
                    $reconcile($payment);
                    $processed++;
                } catch (Throwable $exception) {
                    $failed++;
                    Log::error('External payment reconciliation failed.', [
                        'model' => $modelClass,
                        'payment_id' => $payment->getKey(),
                        'provider_reference' => $payment->getAttribute('reference'),
                        'exception' => $exception::class,
                        'message' => $exception->getMessage(),
                    ]);
                }
            }
        }

        $this->info("{$processed} tentative(s) réconciliée(s), {$failed} échec(s), limite {$limit} par famille.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
