<?php

declare(strict_types=1);

namespace App\Infrastructure\Payments;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class GeniusPayClient
{
    public function createMembershipPayment(string $identityReference, string $successUrl, string $errorUrl): array
    {
        $amount = (int) config('payments.membership.amount');
        if ($amount !== 500 || config('payments.membership.currency') !== 'XOF') {
            throw new RuntimeException('PAYMENT_CANONICAL_PRICE_INVALID');
        }

        $response = $this->request()->post('/payments', [
            'amount' => $amount,
            'currency' => 'XOF',
            'description' => 'Adhésion au Programme ZUMRA',
            'success_url' => $successUrl,
            'error_url' => $errorUrl,
            'metadata' => ['purpose' => 'zumra_membership', 'identity_reference' => $identityReference],
        ]);
        $data = $response->throw()->json('data');

        return $this->normalize(is_array($data) ? $data : []);
    }

    public function payment(string $reference): array
    {
        $data = $this->request()->get('/payments/'.rawurlencode($reference))->throw()->json('data');
        $payment = $this->normalize(is_array($data) ? $data : []);
        if (! hash_equals($reference, $payment['reference'])) {
            throw new RuntimeException('PAYMENT_REFERENCE_MISMATCH');
        }

        return $payment;
    }

    private function request(): PendingRequest
    {
        $key = trim((string) config('payments.geniuspay.api_key'));
        $secret = trim((string) config('payments.geniuspay.api_secret'));
        $environment = (string) config('payments.geniuspay.environment');
        if (! config('payments.membership.enabled') || $environment !== 'live' || $key === '' || $secret === '') {
            throw new RuntimeException('PAYMENT_PROVIDER_NOT_LIVE');
        }

        return Http::baseUrl((string) config('payments.geniuspay.base_url'))
            ->acceptJson()->asJson()->timeout((int) config('payments.geniuspay.timeout'))
            ->withHeaders(['X-API-Key' => $key, 'X-API-Secret' => $secret]);
    }

    private function normalize(array $raw): array
    {
        $status = strtoupper((string) ($raw['status'] ?? ''));
        $aliases = ['SUCCESS' => 'COMPLETED', 'SUCCESSFUL' => 'COMPLETED'];
        $status = $aliases[$status] ?? $status;
        $reference = (string) ($raw['reference'] ?? '');
        $amount = filter_var($raw['amount'] ?? null, FILTER_VALIDATE_INT);
        $checkout = $raw['checkout_url'] ?? $raw['payment_url'] ?? null;
        if ($reference === '' || $amount === false || ! in_array($status, ['PENDING', 'PROCESSING', 'COMPLETED', 'FAILED', 'CANCELLED', 'REFUNDED'], true)) {
            throw new RuntimeException('PAYMENT_PROVIDER_RESPONSE_INVALID');
        }

        return [
            'reference' => $reference, 'provider_id' => isset($raw['id']) ? (string) $raw['id'] : null,
            'amount' => $amount, 'status' => $status, 'checkout_url' => is_string($checkout) ? $checkout : null,
            'environment' => strtolower((string) ($raw['environment'] ?? 'live')),
            'fees' => isset($raw['fees']) ? (int) $raw['fees'] : null,
            'net_amount' => isset($raw['net_amount']) ? (int) $raw['net_amount'] : null,
            'completed_at' => is_string($raw['completed_at'] ?? null) ? $raw['completed_at'] : null,
            'snapshot' => array_intersect_key($raw, array_flip(['id', 'reference', 'amount', 'status', 'environment', 'fees', 'net_amount', 'completed_at', 'payment_method'])),
        ];
    }
}
