<?php

declare(strict_types=1);

namespace App\Infrastructure\GamadCore;

use App\Infrastructure\GamadCore\Exceptions\CoreProductAccessDeniedException;
use App\Infrastructure\GamadCore\Exceptions\CoreProtocolException;
use App\Infrastructure\GamadCore\Exceptions\CoreSessionRejectedException;
use App\Infrastructure\GamadCore\Exceptions\CoreUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final readonly class FederatedProductGateway
{
    public function __construct(
        private string $baseUrl,
        private int $timeoutSeconds = 8,
        private int $connectTimeoutSeconds = 3,
    ) {}

    /** @return array{token: string, audience: string} */
    public function open(string $productReference, #[\SensitiveParameter] string $bearerToken): array
    {
        $productReference = trim($productReference);
        if ($productReference === '') {
            throw new CoreProtocolException('La référence du produit fédéré est absente.');
        }
        if (trim($this->baseUrl) === '') {
            throw new CoreUnavailableException('Le service d’ouverture fédérée n’est pas configuré.');
        }
        if (trim($bearerToken) === '') {
            throw new CoreSessionRejectedException('Session centrale absente.');
        }

        try {
            $response = Http::baseUrl($this->baseUrl)
                ->acceptJson()
                ->withToken($bearerToken)
                ->withHeaders(['X-Correlation-ID' => (string) Str::uuid()])
                ->connectTimeout($this->connectTimeoutSeconds)
                ->timeout($this->timeoutSeconds)
                ->post('/produits/'.rawurlencode($productReference).'/ouverture', []);
        } catch (ConnectionException $exception) {
            throw new CoreUnavailableException('Le service d’ouverture est temporairement injoignable.', previous: $exception);
        }

        $this->assertUsable($response);
        $payload = $response->json();
        if (!is_array($payload) || array_is_list($payload)) {
            throw new CoreProtocolException('Le service d’ouverture a retourné un document invalide.');
        }

        $access = $payload['acces'] ?? null;
        if (!is_array($access)) {
            throw new CoreProtocolException('La preuve d’accès fédéré est absente.');
        }

        $token = $access['jeton'] ?? null;
        $audience = $access['audience'] ?? null;
        if (!is_string($token) || trim($token) === '' || !is_string($audience) || trim($audience) === '') {
            throw new CoreProtocolException('La preuve d’accès fédéré est incomplète.');
        }
        if (!hash_equals($productReference, $audience)) {
            throw new CoreProtocolException('La preuve d’accès fédéré vise un autre produit.');
        }

        return ['token' => $token, 'audience' => $audience];
    }

    private function assertUsable(Response $response): void
    {
        if ($response->status() === 401) {
            throw new CoreSessionRejectedException('La session centrale a été refusée.');
        }
        if (in_array($response->status(), [403, 422], true)) {
            throw new CoreProductAccessDeniedException('L’ouverture de ce produit a été refusée.');
        }
        if ($response->status() === 429 || $response->serverError()) {
            throw new CoreUnavailableException('Le service d’ouverture est temporairement indisponible.');
        }
        if (!$response->successful()) {
            throw new CoreProtocolException("Réponse d’ouverture inattendue ({$response->status()}).");
        }
    }
}
