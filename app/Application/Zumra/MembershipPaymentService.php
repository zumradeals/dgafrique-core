<?php

declare(strict_types=1);

namespace App\Application\Zumra;

use App\Infrastructure\Payments\GeniusPayClient;
use App\Models\ZumraPayment;
use App\Models\ZumraPaymentReceipt;
use App\Models\ZumraProgramMembership;
use App\Models\ZumraProgramMembershipEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class MembershipPaymentService
{
    public function __construct(private readonly GeniusPayClient $provider) {}

    public function start(ZumraProgramMembership $membership, string $successUrl, string $errorUrl): ZumraPayment
    {
        if ($membership->status !== ZumraProgramMembership::STATUS_PENDING_PAYMENT) {
            throw new RuntimeException('MEMBERSHIP_NOT_PAYABLE');
        }
        $configuredEnvironment = (string) config('payments.geniuspay.environment');
        $remote = $this->provider->createMembershipPayment($membership->core_identity_reference, $successUrl, $errorUrl);
        if ($remote['amount'] !== 500 || $remote['environment'] !== $configuredEnvironment || ! $remote['checkout_url']
            || parse_url($remote['checkout_url'], PHP_URL_SCHEME) !== 'https') {
            throw new RuntimeException('PAYMENT_CREATION_MISMATCH');
        }

        return ZumraPayment::query()->create([
            'membership_id' => $membership->id, 'provider' => 'GENIUSPAY', 'purpose' => ZumraPayment::PURPOSE_MEMBERSHIP,
            'reference' => $remote['reference'], 'provider_id' => $remote['provider_id'], 'amount' => 500, 'currency' => 'XOF',
            'environment' => $configuredEnvironment, 'status' => $remote['status'], 'checkout_url' => $remote['checkout_url'],
            'provider_snapshot' => $remote['snapshot'], 'provider_snapshot_hash' => $this->snapshotHash($remote['snapshot']),
        ]);
    }

    public function reconcile(ZumraPayment $payment): ZumraPayment
    {
        $remote = $this->provider->payment($payment->reference);
        // La tentative ne peut jamais changer d'environnement en cours de route : un paiement
        // amorcé en sandbox doit se réconcilier en sandbox, jamais en live et inversement.
        if ($remote['amount'] !== $payment->amount || $payment->currency !== 'XOF' || $payment->purpose !== ZumraPayment::PURPOSE_MEMBERSHIP
            || $remote['environment'] !== $payment->environment) {
            throw new RuntimeException('PAYMENT_RECONCILIATION_MISMATCH');
        }

        return DB::transaction(function () use ($payment, $remote): ZumraPayment {
            $locked = ZumraPayment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $membership = ZumraProgramMembership::query()->whereKey($locked->membership_id)->lockForUpdate()->firstOrFail();
            $locked->fill([
                'status' => $remote['status'], 'fees' => $remote['fees'], 'net_amount' => $remote['net_amount'],
                'provider_snapshot' => $remote['snapshot'], 'provider_snapshot_hash' => $this->snapshotHash($remote['snapshot']),
                'last_verified_at' => now(), 'completed_at' => $remote['status'] === ZumraPayment::STATUS_COMPLETED ? ($locked->completed_at ?? now()) : $locked->completed_at,
            ])->save();

            // CAP-007B : seul `live` active toujours. `sandbox` ne peut activer que si
            // l'interrupteur dédié `sandbox_activation_allowed` est explicitement ouvert —
            // jamais déduit de APP_ENV. Off par défaut partout, y compris en local.
            $canActivate = $locked->environment === 'live'
                || ($locked->environment === 'sandbox' && (bool) config('payments.geniuspay.sandbox_activation_allowed'));

            if ($remote['status'] === ZumraPayment::STATUS_COMPLETED && $canActivate && $membership->status === ZumraProgramMembership::STATUS_PENDING_PAYMENT) {
                $membership->update(['status' => ZumraProgramMembership::STATUS_ACTIVE, 'activated_at' => now()]);
                ZumraProgramMembershipEvent::query()->create([
                    'membership_id' => $membership->id, 'event' => 'PAYMENT_CONFIRMED',
                    'from_status' => ZumraProgramMembership::STATUS_PENDING_PAYMENT, 'to_status' => ZumraProgramMembership::STATUS_ACTIVE,
                    'actor_core_reference' => 'SYSTEM:PAYMENT_PROVIDER', 'context' => ['payment_reference' => $locked->reference, 'environment' => $locked->environment], 'occurred_at' => now(),
                ]);
                $this->issueReceipt($locked, $membership);
            }

            return $locked->refresh();
        }, 3);
    }

    private function issueReceipt(ZumraPayment $payment, ZumraProgramMembership $membership): void
    {
        if (ZumraPaymentReceipt::query()->where('payment_id', $payment->id)->exists()) return;
        $issuedAt = now();
        $number = 'DGZ-'.$issuedAt->format('Y').'-'.strtoupper(Str::random(12));
        $canonical = implode('|', [$number, $payment->reference, $membership->core_identity_reference, '500', 'XOF', $issuedAt->toIso8601String()]);
        ZumraPaymentReceipt::query()->create([
            'payment_id' => $payment->id, 'membership_id' => $membership->id, 'number' => $number,
            'core_identity_reference' => $membership->core_identity_reference, 'provider' => $payment->provider,
            'provider_reference' => $payment->reference, 'amount' => 500, 'currency' => 'XOF',
            'purpose' => ZumraPayment::PURPOSE_MEMBERSHIP, 'issued_at' => $issuedAt, 'integrity_hash' => hash('sha256', $canonical),
        ]);
    }

    private function snapshotHash(array $snapshot): string
    {
        ksort($snapshot);
        return hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }
}
