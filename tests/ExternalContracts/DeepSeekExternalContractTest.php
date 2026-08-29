<?php

declare(strict_types=1);

namespace Tests\ExternalContracts;

use App\Infrastructure\AI\DeepSeekProjectBrainProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

final class DeepSeekExternalContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config()->set('services.deepseek.enabled', false);
        config()->set('services.deepseek.data_policy_version', null);
        config()->set('services.deepseek.api_key', 'stage-c-not-a-real-key');
        config()->set('services.deepseek.base_url', 'https://deepseek.test');
        config()->set('services.deepseek.connect_timeout', 1);
        config()->set('services.deepseek.timeout', 2);
    }

    public function test_no_data_leaves_dg_afrique_when_deepseek_is_not_explicitly_enabled(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DEEPSEEK_DISABLED');

        try {
            app(DeepSeekProjectBrainProvider::class)->respond([
                ['role' => 'user', 'content' => 'Information sensible'],
            ]);
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_no_data_leaves_without_an_explicit_data_policy_version(): void
    {
        config()->set('services.deepseek.enabled', true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DEEPSEEK_DATA_POLICY_NOT_CONFIGURED');

        try {
            app(DeepSeekProjectBrainProvider::class)->respond([
                ['role' => 'user', 'content' => 'Information sensible'],
            ]);
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_configured_provider_returns_only_a_normalized_proposal(): void
    {
        $this->enableProvider();
        Http::fake(['deepseek.test/*' => Http::response([
            'choices' => [[
                'finish_reason' => 'stop',
                'message' => ['content' => json_encode([
                    'reply' => 'Clarifions ce besoin.',
                    'project_state' => ['phase' => 'discovery'],
                    'suggested_next_action' => 'Décrire le lieu',
                    'confidence' => 4,
                    'proposed_actions' => [[
                        'type' => 'NEED_CREATE',
                        'title' => 'Trouver une salle',
                    ]],
                ], JSON_THROW_ON_ERROR)],
            ]],
        ])]);

        $result = app(DeepSeekProjectBrainProvider::class)->respond(
            [['role' => 'user', 'content' => 'Il nous manque une salle.']],
            ['project' => ['public_reference' => 'PRJ-STAGE-C']],
        );

        self::assertSame('Clarifions ce besoin.', $result['reply']);
        self::assertSame(1.0, $result['confidence']);
        self::assertSame('NEED_CREATE', $result['proposed_actions'][0]['type']);
        Http::assertSent(fn (Request $request): bool =>
            $request->method() === 'POST'
            && $request->url() === 'https://deepseek.test/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer stage-c-not-a-real-key')
            && $request->hasHeader('X-DG-Afrique-Data-Policy', 'DG-AFRIQUE-AI-2026-01')
            && $request['stream'] === false
        );
    }

    public function test_malformed_provider_payload_is_rejected(): void
    {
        $this->enableProvider();
        Http::fake(['deepseek.test/*' => Http::response([
            'choices' => [[
                'finish_reason' => 'stop',
                'message' => ['content' => '{not-json'],
            ]],
        ])]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DeepSeek returned an invalid Project Brain payload.');

        app(DeepSeekProjectBrainProvider::class)->respond([['role' => 'user', 'content' => 'Bonjour']]);
    }

    public function test_provider_unavailability_is_never_converted_into_a_success(): void
    {
        $this->enableProvider();
        Http::fake(['deepseek.test/*' => Http::response([], 503)]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DeepSeek request failed with HTTP 503.');

        app(DeepSeekProjectBrainProvider::class)->respond([['role' => 'user', 'content' => 'Bonjour']]);
    }

    public function test_transport_failure_or_timeout_is_normalized_and_never_returns_content(): void
    {
        $this->enableProvider();
        Http::fake(['deepseek.test/*' => Http::failedConnection('simulated timeout')]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DeepSeek request failed at transport level.');

        app(DeepSeekProjectBrainProvider::class)->respond([['role' => 'user', 'content' => 'Bonjour']]);
    }

    private function enableProvider(): void
    {
        config()->set('services.deepseek.enabled', true);
        config()->set('services.deepseek.data_policy_version', 'DG-AFRIQUE-AI-2026-01');
    }
}
