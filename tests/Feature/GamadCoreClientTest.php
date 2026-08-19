<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\GamadCore\Exceptions\CoreIdentityNotFoundException;
use App\Infrastructure\GamadCore\Exceptions\CoreProtocolException;
use App\Infrastructure\GamadCore\Exceptions\CoreSessionRejectedException;
use App\Infrastructure\GamadCore\Exceptions\CoreUnavailableException;
use App\Infrastructure\GamadCore\GamadCoreClient;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class GamadCoreClientTest extends TestCase
{
    private GamadCoreClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new GamadCoreClient('https://core.test/api/v1');
    }

    public function test_it_resolves_the_canonical_identity_with_the_member_session(): void
    {
        Http::fake(['core.test/api/v1/identites/*' => Http::response([
            'reference' => 'PER-GAMAD-000000001',
            'type' => 'PERSONNE',
            'libelle' => 'Membre DG Afrique',
            'etat' => 'ACTIF',
            'date_effet' => '2026-08-13',
            'source' => 'SRC-GAMAD-001',
            'regime' => 'INSCRIT',
        ])]);

        $identity = $this->client->resolveIdentity('PER-GAMAD-000000001', 'secret-bearer');

        self::assertSame('PER-GAMAD-000000001', $identity->reference);
        Http::assertSent(fn ($request): bool =>
            $request->hasHeader('Authorization', 'Bearer secret-bearer')
            && $request->hasHeader('X-Correlation-ID')
        );
    }

    public function test_it_reads_the_core_attested_session_expiry(): void
    {
        Http::fake(['core.test/api/v1/sessions/current' => Http::response([
            'entite' => 'PER-GAMAD-000000001',
            'assurance' => 'A2',
            'expire_le' => '2026-08-14T02:00:00+00:00',
        ])]);

        $session = $this->client->currentSession('secret-bearer');

        self::assertSame('PER-GAMAD-000000001', $session->entity);
        self::assertSame('2026-08-14T02:00:00+00:00', $session->expiresAt->format(DATE_ATOM));
    }

    public function test_it_proves_an_identity_and_always_revokes_the_test_session(): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response([
                'jeton' => 'temporary-test-token',
            ], 201),
            'core.test/api/v1/sessions/current' => Http::response([
                'entite' => 'PER-GAMAD-000000001',
                'assurance' => 'A2',
                'expire_le' => '2026-08-14T02:00:00+00:00',
            ]),
            'core.test/api/v1/identites/*' => Http::response([
                'reference' => 'PER-GAMAD-000000001',
                'type' => 'PERSONNE',
                'libelle' => 'Membre DG Afrique',
                'etat' => 'ACTIF',
                'source' => 'SRC-GAMAD-001',
                'regime' => 'INSCRIT',
            ]),
        ]);

        $proof = $this->client->proveIdentity('PER-GAMAD-000000001', 'member-secret');

        self::assertSame('PER-GAMAD-000000001', $proof->identity->reference);
        self::assertSame('A2', $proof->session->assurance);
        Http::assertSent(fn ($request): bool =>
            $request->method() === 'DELETE'
            && $request->url() === 'https://core.test/api/v1/sessions/current'
            && $request->hasHeader('Authorization', 'Bearer temporary-test-token')
        );
    }

    public function test_it_revokes_the_test_session_when_identity_resolution_fails(): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'temporary-test-token'], 201),
            'core.test/api/v1/sessions/current' => Http::response([
                'entite' => 'PER-GAMAD-000000001',
                'assurance' => 'A2',
                'expire_le' => '2026-08-14T02:00:00+00:00',
            ]),
            'core.test/api/v1/identites/*' => Http::response([], 404),
        ]);

        try {
            $this->client->proveIdentity('PER-GAMAD-000000001', 'member-secret');
            self::fail('La résolution devait échouer.');
        } catch (CoreIdentityNotFoundException) {
            self::assertTrue(true);
        }

        Http::assertSent(fn ($request): bool => $request->method() === 'DELETE');
    }

    #[DataProvider('failureProvider')]
    public function test_it_preserves_core_failure_semantics(int $status, string $exception): void
    {
        Http::fake(['core.test/*' => Http::response([], $status)]);
        $this->expectException($exception);

        $this->client->resolveIdentity('PER-GAMAD-000000001', 'secret-bearer');
    }

    /** @return array<string, array{int, class-string<\Throwable>}> */
    public static function failureProvider(): array
    {
        return [
            'session rejetée' => [401, CoreSessionRejectedException::class],
            'identité absente' => [404, CoreIdentityNotFoundException::class],
            'limitation temporaire' => [429, CoreUnavailableException::class],
            'panne Core' => [503, CoreUnavailableException::class],
            'réponse inattendue' => [403, CoreProtocolException::class],
        ];
    }

    public function test_it_treats_a_transport_level_request_exception_as_core_unavailable(): void
    {
        Http::fake(function () {
            throw new RequestException(new Response(new Psr7Response(403)));
        });

        $this->expectException(CoreUnavailableException::class);

        $this->client->resolveIdentity('PER-GAMAD-000000001', 'secret-bearer');
    }

    public function test_it_never_sends_a_request_without_a_session(): void
    {
        Http::fake();
        $this->expectException(CoreSessionRejectedException::class);

        try {
            $this->client->resolveIdentity('PER-GAMAD-000000001', '');
        } finally {
            Http::assertNothingSent();
        }
    }
}
