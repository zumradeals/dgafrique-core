<?php

declare(strict_types=1);

namespace App\Infrastructure\GamadCore;

use App\Domain\Identity\CoreIdentity;
use App\Domain\Identity\CoreIdentityProof;
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

    public function proveIdentity(string $entity, string $secret): CoreIdentityProof
    {
        $entity = trim($entity);
        if ($entity === '' || $secret === '') {
            throw new CoreSessionRejectedException('Identité ou moyen d’accès absent.');
        }

        $response = $this->post('/sessions', [
            'entite' => $entity,
            'secret' => $secret,
        ]);
        $this->assertUsable($response);

        $payload = $this->jsonObject($response);
        $token = $payload['jeton'] ?? null;
        if (!is_string($token) || $token === '') {
            throw new CoreProtocolException('GAMAD Core n’a pas remis de jeton de session.');
        }

        try {
            $session = $this->currentSession($token);
            if (!hash_equals($entity, $session->entity)) {
                throw new CoreProtocolException('La session Core appartient à une autre entité.');
            }

            return new CoreIdentityProof(
                identity: $this->resolveIdentity($session->entity, $token),
                session: $session,
            );
        } finally {
            $this->revokeSession($token);
        }
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

    /** @param array<string, string> $payload */
    private function post(string $path, array $payload): Response
    {
        if (trim($this->baseUrl) === '') {
            throw new CoreUnavailableException('GAMAD Core n’est pas configuré.');
        }

        try {
            return $this->request()->post($path, $payload);
        } catch (ConnectionException $exception) {
            throw new CoreUnavailableException('GAMAD Core est temporairement injoignable.', previous: $exception);
        }
    }

    private function revokeSession(string $bearerToken): void
    {
        try {
            $response = $this->request($bearerToken)->delete('/sessions/current');
        } catch (ConnectionException $exception) {
            throw new CoreUnavailableException('La session de preuve n’a pas pu être révoquée.', previous: $exception);
        }

        if (!$response->successful()) {
            throw new CoreUnavailableException('Core n’a pas confirmé la révocation de la session de preuve.');
        }
    }

    private function request(?string $bearerToken = null): PendingRequest
    {
        $request = Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->withHeaders(['X-Correlation-ID' => (string) Str::uuid()])
            ->connectTimeout($this->connectTimeoutSeconds)
            ->timeout($this->timeoutSeconds);

        return $bearerToken === null ? $request : $request->withToken($bearerToken);
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
