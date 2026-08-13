<?php

declare(strict_types=1);

namespace App\Infrastructure\GamadCore;

use App\Domain\Identity\CoreIdentity;
use App\Domain\Identity\CoreSession;
use App\Infrastructure\GamadCore\Exceptions\CoreIdentityNotFoundException;
use App\Infrastructure\GamadCore\Exceptions\CoreProtocolException;
use App\Infrastructure\GamadCore\Exceptions\CoreSessionRejectedException;
use App\Infrastructure\GamadCore\Exceptions\CoreUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final readonly class GamadCoreClient
{
    public function __construct(
        private string $baseUrl,
        private int $timeoutSeconds = 8,
        private int $connectTimeoutSeconds = 3,
    ) {}

    public function currentSession(string $bearerToken): CoreSession
    {
        $response = $this->get('/sessions/current', $bearerToken);
        $this->assertUsable($response);

        return CoreSession::fromCurrentPayload($this->jsonObject($response));
    }

    public function resolveIdentity(string $reference, string $bearerToken): CoreIdentity
    {
        $reference = trim($reference);
        if ($reference === '') {
            throw new CoreProtocolException('La référence Core ne peut pas être vide.');
        }

        $response = $this->get('/identites/'.rawurlencode($reference), $bearerToken);
        if ($response->status() === 404) {
            throw new CoreIdentityNotFoundException('Identité Core introuvable.');
        }
        $this->assertUsable($response);

        return CoreIdentity::fromCorePayload($this->jsonObject($response), $reference);
    }

    private function get(string $path, string $bearerToken): Response
    {
        if (trim($this->baseUrl) === '') {
            throw new CoreUnavailableException('GAMAD Core n’est pas configuré.');
        }
        if (trim($bearerToken) === '') {
            throw new CoreSessionRejectedException('Session Core absente.');
        }

        try {
            return $this->request($bearerToken)->get($path);
        } catch (ConnectionException $exception) {
            throw new CoreUnavailableException('GAMAD Core est temporairement injoignable.', previous: $exception);
        }
    }

    private function request(string $bearerToken): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->withToken($bearerToken)
            ->withHeaders(['X-Correlation-ID' => (string) Str::uuid()])
            ->connectTimeout($this->connectTimeoutSeconds)
            ->timeout($this->timeoutSeconds);
    }

    private function assertUsable(Response $response): void
    {
        if ($response->status() === 401) {
            throw new CoreSessionRejectedException('Session Core refusée, expirée ou révoquée.');
        }
        if ($response->status() === 429 || $response->serverError()) {
            throw new CoreUnavailableException('GAMAD Core est temporairement indisponible.');
        }
        if (!$response->successful()) {
            throw new CoreProtocolException("Réponse Core inattendue ({$response->status()}).");
        }
    }

    /** @return array<string, mixed> */
    private function jsonObject(Response $response): array
    {
        $payload = $response->json();
        if (!is_array($payload) || array_is_list($payload)) {
            throw new CoreProtocolException('GAMAD Core a retourné un document invalide.');
        }

        return $payload;
    }
}
