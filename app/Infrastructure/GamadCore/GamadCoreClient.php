<?php

declare(strict_types=1);

namespace App\Infrastructure\GamadCore;

use App\Domain\Identity\CoreIdentity;
use App\Domain\Identity\CoreIdentityProof;
use App\Domain\Identity\CoreAuthenticatedIdentity;
use App\Domain\Identity\CoreSession;
use App\Domain\Identity\PendingAccountVerification;
use App\Infrastructure\GamadCore\Exceptions\CoreAccountException;
use App\Infrastructure\GamadCore\Exceptions\CoreIdentityNotFoundException;
use App\Infrastructure\GamadCore\Exceptions\CoreProtocolException;
use App\Infrastructure\GamadCore\Exceptions\CoreSessionRejectedException;
use App\Infrastructure\GamadCore\Exceptions\CoreUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final readonly class GamadCoreClient
{
    public function __construct(
        private string $baseUrl,
        private ?string $productReference = null,
        #[\SensitiveParameter]
        private ?string $connectSecret = null,
        private int $timeoutSeconds = 8,
        private int $connectTimeoutSeconds = 3,
    ) {}

    public function createAccount(string $name, string $channel, string $identifier, #[\SensitiveParameter] string $password): PendingAccountVerification
    {
        return $this->withProductSession(function (string $bearer) use ($name, $channel, $identifier, $password): PendingAccountVerification {
            $response = $this->post('/comptes', [
                'nom' => $name,
                'type_identifiant' => $channel,
                'identifiant' => $identifier,
                'mot_de_passe' => $password,
            ], $bearer);

            return $this->pendingVerificationFromResponse($response, $identifier, $channel);
        });
    }

    public function verifyAccount(PendingAccountVerification $pending, #[\SensitiveParameter] string $code): void
    {
        $this->withProductSession(function (string $bearer) use ($pending, $code): null {
            $response = $this->post('/comptes/verifications', [
                'identite' => $pending->identity,
                'identifiant_reference' => $pending->identifierReference,
                'verification_reference' => $pending->verificationReference,
                'code' => $code,
            ], $bearer);
            $this->assertAccountUsable($response);

            return null;
        });
    }

    public function resendAccountVerification(PendingAccountVerification $pending): PendingAccountVerification
    {
        return $this->withProductSession(function (string $bearer) use ($pending): PendingAccountVerification {
            $response = $this->post('/comptes/verifications/renvoi', [
                'identifiant_reference' => $pending->identifierReference,
                'destination' => $pending->destination,
            ], $bearer);
            $this->assertAccountUsable($response);
            $payload = $this->jsonObject($response);
            $verification = $payload['verification'] ?? null;
            if (!is_array($verification)) {
                throw new CoreProtocolException('Réponse de renvoi Core incomplète.');
            }

            return $this->buildPending($pending->identity, $pending->identifierReference, $verification, $pending->destination, $pending->channel, true);
        });
    }

    /**
     * CAP-067 — inscrit une identité canonique de type `organisation`
     * (CAP-CORE-001, canal `PRODUIT_RECONNU`) via la délégation
     * CORE-ORG-DELEGATION-001. Assurance A1, jamais davantage : ce canal ne
     * produit jamais un niveau supérieur au sien.
     *
     * @return array{reference: string, etat: string, assurance: string}
     */
    public function provisionOrganizationIdentity(string $label): array
    {
        return $this->withProductSession(function (string $bearer) use ($label): array {
            $response = $this->post('/identites', [
                'canal' => 'PRODUIT_RECONNU',
                'type' => 'organisation',
                'libelle' => $label,
            ], $bearer);
            $this->assertUsable($response);
            $payload = $this->jsonObject($response);
            $identite = $payload['identite'] ?? null;
            if (!is_array($identite) || !is_string($identite['reference'] ?? null) || trim($identite['reference']) === '') {
                throw new CoreProtocolException('Core n’a pas remis de référence d’identité organisationnelle.');
            }

            return [
                'reference' => $identite['reference'],
                'etat' => is_string($identite['etat'] ?? null) ? $identite['etat'] : '',
                'assurance' => is_string($identite['assurance'] ?? null) ? $identite['assurance'] : '',
            ];
        });
    }

    /**
     * CAP-067 — inscrit la fiche organisationnelle canonique (CAP-CORE-002)
     * pour une identité déjà provisionnée par
     * {@see self::provisionOrganizationIdentity()}. N'attache jamais
     * d'autorité Core (activation, représentant, dirigeant) : seule la
     * création (CREATE) est déléguée à ce produit.
     *
     * @param  array{type_organisation_reference: string, proprietaire_reference: string, denomination_officielle: string, classification_reference: string, description?: string}  $payload
     * @return array{reference: string, identite_reference: string, etat: string, type_organisation_reference: string}
     */
    public function createOrganization(string $identityReference, array $payload): array
    {
        return $this->withProductSession(function (string $bearer) use ($identityReference, $payload): array {
            $response = $this->post('/organisations', array_merge(
                ['identite_reference' => $identityReference],
                $payload,
            ), $bearer);
            $this->assertUsable($response);
            $body = $this->jsonObject($response);
            $resultat = $body['resultat'] ?? null;
            if (!is_array($resultat) || !is_string($resultat['reference'] ?? null) || trim($resultat['reference']) === '') {
                throw new CoreProtocolException('Core n’a pas remis de référence d’organisation canonique.');
            }

            return [
                'reference' => $resultat['reference'],
                'identite_reference' => $identityReference,
                'etat' => is_string($resultat['etat'] ?? null) ? $resultat['etat'] : '',
                'type_organisation_reference' => is_string($resultat['type_organisation_reference'] ?? null) ? $resultat['type_organisation_reference'] : '',
            ];
        });
    }

    /**
     * CAP-067 — ATTACH V1 : résout, en lecture seule, la fiche
     * organisationnelle déjà canonisée pour une identité précise
     * (`GET /organisations/resolution/{identite}`). Ne mute jamais la
     * propriété, un représentant, un dirigeant ni aucune affiliation — une
     * pure lecture. Retourne `null` si aucune fiche n'existe encore pour
     * cette identité (`404 ORGANISATION_INTROUVABLE`).
     *
     * @return array{reference: string, identite_reference: string, etat: string}|null
     */
    public function resolveOrganizationByIdentity(string $identityReference): ?array
    {
        return $this->withProductSession(function (string $bearer) use ($identityReference): ?array {
            $response = $this->get('/organisations/resolution/'.rawurlencode($identityReference), $bearer);
            if ($response->status() === 404) {
                return null;
            }
            $this->assertUsable($response);
            $body = $this->jsonObject($response);
            $organisation = $body['organisation'] ?? null;
            if (!is_array($organisation) || !is_string($organisation['reference'] ?? null) || trim($organisation['reference']) === '') {
                throw new CoreProtocolException('Core n’a pas remis de fiche organisationnelle exploitable.');
            }

            return [
                'reference' => $organisation['reference'],
                'identite_reference' => $identityReference,
                'etat' => is_string($organisation['etat'] ?? null) ? $organisation['etat'] : '',
            ];
        });
    }

    public function currentSession(string $bearerToken): CoreSession
    {
        $response = $this->get('/sessions/current', $bearerToken);
        $this->assertUsable($response);

        return CoreSession::fromCurrentPayload($this->jsonObject($response));
    }

    public function authenticate(string $identifier, #[\SensitiveParameter] string $secret): CoreAuthenticatedIdentity
    {
        $identifier = trim($identifier);
        if ($identifier === '' || $secret === '') {
            throw new CoreSessionRejectedException('Identifiant ou moyen d’accès absent.');
        }

        $credentials = str_contains($identifier, '-GAMAD-')
            ? ['entite' => $identifier, 'secret' => $secret]
            : ['identifiant' => $identifier, 'secret' => $secret];

        $response = $this->post('/sessions', $credentials);
        $this->assertUsable($response);
        $payload = $this->jsonObject($response);

        foreach (['jeton', 'entite', 'assurance', 'expire_le'] as $field) {
            if (!isset($payload[$field]) || !is_string($payload[$field]) || trim($payload[$field]) === '') {
                throw new CoreProtocolException("Champ d’authentification Core manquant : {$field}.");
            }
        }

        $token = $payload['jeton'];
        try {
            $session = CoreSession::fromCurrentPayload([
                'entite' => $payload['entite'],
                'assurance' => $payload['assurance'],
                'expire_le' => $payload['expire_le'],
            ]);
            $identity = $this->resolveIdentity($session->entity, $token);
        } catch (\Throwable $exception) {
            $this->revokeSession($token);
            throw $exception;
        }

        return new CoreAuthenticatedIdentity($identity, $session, $token);
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
        } catch (ConnectionException|RequestException $exception) {
            throw new CoreUnavailableException('GAMAD Core est temporairement injoignable.', previous: $exception);
        }
    }

    /** @param array<string, string> $payload */
    private function post(string $path, array $payload, ?string $bearerToken = null): Response
    {
        if (trim($this->baseUrl) === '') {
            throw new CoreUnavailableException('GAMAD Core n’est pas configuré.');
        }

        try {
            return $this->request($bearerToken)->post($path, $payload);
        } catch (ConnectionException|RequestException $exception) {
            throw new CoreUnavailableException('GAMAD Core est temporairement injoignable.', previous: $exception);
        }
    }

    private function withProductSession(callable $operation): mixed
    {
        $product = trim((string) $this->productReference);
        $secret = (string) $this->connectSecret;
        if ($product === '' || $secret === '') {
            throw new CoreUnavailableException('Le raccordement produit GAMAD Core est incomplet.');
        }

        $response = $this->post('/sessions', ['entite' => $product, 'secret' => $secret]);
        $this->assertUsable($response);
        $token = $this->jsonObject($response)['jeton'] ?? null;
        if (!is_string($token) || $token === '') {
            throw new CoreProtocolException('Core n’a pas remis de session produit.');
        }

        try {
            return $operation($token);
        } finally {
            try {
                $this->revokeSession($token);
            } catch (CoreUnavailableException) {
                // La session produit est courte et n'est jamais conservée par le portail.
            }
        }
    }

    private function pendingVerificationFromResponse(Response $response, string $destination, string $channel): PendingAccountVerification
    {
        $payload = $this->jsonObject($response);
        $delivered = $response->successful();
        if (!$delivered && !($response->status() === 503 && ($payload['erreur'] ?? null) === 'VERIFICATION_NON_LIVREE')) {
            $this->throwAccountException($response, $payload);
        }

        $account = $payload['compte'] ?? null;
        $verification = $payload['verification'] ?? null;
        if (!is_array($account) || !is_array($verification)) {
            throw new CoreProtocolException('Réponse de création Core incomplète.');
        }

        return $this->buildPending(
            (string) ($account['identite'] ?? ''),
            (string) ($account['identifiant_reference'] ?? ''),
            $verification,
            $destination,
            $channel,
            $delivered && (($verification['livraison']['livree'] ?? false) === true),
        );
    }

    /** @param array<string, mixed> $verification */
    private function buildPending(string $identity, string $identifierReference, array $verification, string $destination, string $channel, bool $delivered): PendingAccountVerification
    {
        $reference = $verification['reference'] ?? null;
        $expires = $verification['expire_le'] ?? null;
        if ($identity === '' || $identifierReference === '' || !is_string($reference) || $reference === '' || !is_string($expires)) {
            throw new CoreProtocolException('Références de vérification Core absentes.');
        }

        try {
            $expiresAt = new \DateTimeImmutable($expires);
        } catch (\Throwable $exception) {
            throw new CoreProtocolException('Échéance de vérification Core invalide.', previous: $exception);
        }

        return new PendingAccountVerification($identity, $identifierReference, $reference, $destination, $channel, $expiresAt, $delivered);
    }

    private function assertAccountUsable(Response $response): void
    {
        if (!$response->successful()) {
            $this->throwAccountException($response, $this->jsonObject($response));
        }
    }

    /** @param array<string, mixed> $payload */
    private function throwAccountException(Response $response, array $payload): never
    {
        $reason = is_string($payload['erreur'] ?? null) ? $payload['erreur'] : 'ERREUR_COMPTE_CORE';
        $message = is_string($payload['message'] ?? null) ? $payload['message'] : 'L’opération sur le compte a échoué.';
        $retry = is_numeric($payload['reessayer_dans'] ?? null) ? (int) $payload['reessayer_dans'] : null;
        throw new CoreAccountException($reason, $response->status(), $message, $retry);
    }

    public function revokeSession(string $bearerToken): void
    {
        try {
            $response = $this->request($bearerToken)->delete('/sessions/current');
        } catch (ConnectionException|RequestException $exception) {
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
