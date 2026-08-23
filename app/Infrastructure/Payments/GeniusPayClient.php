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
        if (! config('payments.membership.enabled')) {
            throw new RuntimeException('PAYMENT_PROVIDER_NOT_LIVE');
        }
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

        // GeniusPay sandbox renvoie parfois un statut absent sur la création initiale (le
        // paiement existe déjà côté prestataire) — jamais toléré à la réconciliation.
        return $this->normalize(is_array($data) ? $data : [], allowMissingInitialStatus: true);
    }

    /**
     * CAP-061 — création générique, contrairement à createMembershipPayment() qui reste
     * verrouillée à 500 XOF « adhésion » (CAP-007B, jamais modifiée). Réutilise request()/
     * normalize() à l'identique : le provider reste seul responsable du checkout et du
     * mouvement d'argent réel, DG Afrique ne construit aucune abstraction bancaire parallèle.
     *
     * @param  array<string, string>  $metadata
     */
    public function createContributionPayment(int $amount, string $currency, string $description, array $metadata, string $successUrl, string $errorUrl): array
    {
        if ($amount <= 0 || $currency === '') {
            throw new RuntimeException('PAYMENT_CANONICAL_PRICE_INVALID');
        }

        $response = $this->request()->post('/payments', [
            'amount' => $amount,
            'currency' => $currency,
            'description' => $description,
            'success_url' => $successUrl,
            'error_url' => $errorUrl,
            'metadata' => $metadata,
        ]);
        $data = $response->throw()->json('data');

        // Même tolérance qu'à la création d'un paiement d'adhésion (GeniusPay sandbox peut
        // renvoyer un statut absent sur la création initiale) — jamais tolérée à la réconciliation.
        return $this->normalize(is_array($data) ? $data : [], allowMissingInitialStatus: true);
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
        // Connexion au prestataire lui-même — générique, jamais spécifique à une finalité
        // (adhésion CAP-007B ou contribution CAP-061). L'activation métier propre à chaque
        // finalité (payments.membership.enabled, ContributionConfiguration) est vérifiée par
        // chaque appelant, jamais ici : sandbox reste un environnement de paiement légitime
        // (tests réels chez le prestataire, sans argent réel) — seule la FINALISATION d'un
        // paiement en sandbox reste gouvernée séparément par `sandbox_activation_allowed`.
        if (! in_array($environment, ['live', 'sandbox'], true) || $key === '' || $secret === '') {
            throw new RuntimeException('PAYMENT_PROVIDER_NOT_LIVE');
        }

        return Http::baseUrl((string) config('payments.geniuspay.base_url'))
            ->acceptJson()->asJson()->timeout((int) config('payments.geniuspay.timeout'))
            ->withHeaders(['X-API-Key' => $key, 'X-API-Secret' => $secret]);
    }

    /**
     * @param  bool  $allowMissingInitialStatus  true uniquement pour createMembershipPayment() —
     *                                           jamais pour payment()/reconcile(), qui doivent toujours recevoir un vrai statut du
     *                                           prestataire. Un statut absent n'est jamais transformé silencieusement en COMPLETED :
     *                                           au mieux, sur une création par ailleurs valide, en PENDING.
     */
    private function normalize(array $raw, bool $allowMissingInitialStatus = false): array
    {
        $status = strtoupper((string) ($raw['status'] ?? ''));
        $aliases = ['SUCCESS' => 'COMPLETED', 'SUCCESSFUL' => 'COMPLETED'];
        $status = $aliases[$status] ?? $status;
        $reference = (string) ($raw['reference'] ?? '');
        $amount = filter_var($raw['amount'] ?? null, FILTER_VALIDATE_INT);
        $checkout = $raw['checkout_url'] ?? $raw['payment_url'] ?? null;
        $environment = strtolower((string) ($raw['environment'] ?? ''));

        if ($allowMissingInitialStatus && $status === '' && $reference !== '' && $amount !== false
            && in_array($environment, ['live', 'sandbox'], true)
            && is_string($checkout) && parse_url($checkout, PHP_URL_SCHEME) === 'https') {
            $status = 'PENDING';
        }

        if ($reference === '' || $amount === false || ! in_array($status, ['PENDING', 'PROCESSING', 'COMPLETED', 'FAILED', 'CANCELLED', 'REFUNDED'], true)
            || ! in_array($environment, ['live', 'sandbox'], true)) {
            throw new RuntimeException('PAYMENT_PROVIDER_RESPONSE_INVALID');
        }

        return [
            'reference' => $reference, 'provider_id' => isset($raw['id']) ? (string) $raw['id'] : null,
            'amount' => $amount, 'status' => $status, 'checkout_url' => is_string($checkout) ? $checkout : null,
            'environment' => $environment,
            'fees' => isset($raw['fees']) ? (int) $raw['fees'] : null,
            'net_amount' => isset($raw['net_amount']) ? (int) $raw['net_amount'] : null,
            'completed_at' => is_string($raw['completed_at'] ?? null) ? $raw['completed_at'] : null,
            'snapshot' => array_intersect_key($raw, array_flip(['id', 'reference', 'amount', 'status', 'environment', 'fees', 'net_amount', 'completed_at', 'payment_method'])),
        ];
    }
}
