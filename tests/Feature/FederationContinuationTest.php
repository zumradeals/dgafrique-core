<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class FederationContinuationTest extends TestCase
{
    public function test_unauthenticated_member_returns_to_login_with_local_federation_destination(): void
    {
        $this->get('/federation/continue/gamadrive')
            ->assertRedirect('/connexion?next=%2Ffederation%2Fcontinue%2Fgamadrive');
    }

    public function test_authenticated_member_opens_gamadrive_with_post_handoff(): void
    {
        $this->fakeFederationFlow();
        $this->signIn();

        $response = $this->get('/federation/continue/gamadrive');

        $response->assertOk()
            ->assertSee('Ouverture de GamaDrive')
            ->assertSee('action="https://gamadrive.dgafrique.com/federation/callback"', false)
            ->assertSee('name="jeton" value="FED-DEMO-TOKEN"', false)
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertDontSee('GAMAD Core');

        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);

        Http::assertSent(fn ($request): bool =>
            $request->method() === 'POST'
            && $request->url() === 'https://core.test/api/v1/produits/PRD-GAMAD-002/ouverture'
            && $request->hasHeader('Authorization', 'Bearer member-bearer')
        );
    }

    public function test_foreign_audience_is_never_forwarded_to_gamadrive(): void
    {
        $this->fakeFederationFlow([
            'acces' => [
                'jeton' => 'FED-WRONG-AUDIENCE',
                'audience' => 'PRD-GAMAD-999',
            ],
        ]);
        $this->signIn();

        $this->get('/federation/continue/gamadrive')
            ->assertStatus(502)
            ->assertSee('Aucun accès n’a été transmis')
            ->assertDontSee('FED-WRONG-AUDIENCE');
    }

    public function test_governed_refusal_is_presented_without_creating_local_access(): void
    {
        $this->fakeFederationFlow([
            'resultat' => ['refus' => 'OUVERTURE_REFUSEE'],
        ], 422);
        $this->signIn();

        $this->get('/federation/continue/gamadrive')
            ->assertForbidden()
            ->assertSee('Votre compte ne peut pas ouvrir GamaDrive');
    }

    public function test_transient_opening_failure_preserves_dg_afrique_session(): void
    {
        $this->fakeFederationFlow([], 503);
        $this->signIn();

        $this->get('/federation/continue/gamadrive')
            ->assertStatus(503)
            ->assertSee('Votre connexion DG Afrique reste active')
            ->assertSessionHas('dg_core_member.reference', 'AUT-GAMAD-001');
    }

    public function test_session_rejected_during_opening_returns_to_login_and_clears_portal_session(): void
    {
        $this->fakeFederationFlow([], 401);
        $this->signIn();

        $this->get('/federation/continue/gamadrive')
            ->assertRedirect('/connexion?next=%2Ffederation%2Fcontinue%2Fgamadrive')
            ->assertSessionMissing('dg_core_member');
    }

    public function test_non_https_callback_configuration_fails_closed(): void
    {
        $this->fakeFederationFlow();
        $this->signIn();
        config(['federation.gamadrive.callback_url' => 'http://gamadrive.test/federation/callback']);

        $this->get('/federation/continue/gamadrive')
            ->assertStatus(503)
            ->assertSee('temporairement indisponible');

        Http::assertNotSent(fn ($request): bool =>
            $request->url() === 'https://core.test/api/v1/produits/PRD-GAMAD-002/ouverture'
        );
    }

    public function test_unknown_satellite_has_no_compatibility_route(): void
    {
        $this->get('/federation/continue/wasplex')->assertNotFound();
    }

    private function signIn(): void
    {
        $this->post('/connexion', [
            'identifier' => 'AUT-GAMAD-001',
            'secret' => 'member-secret',
        ])->assertRedirect('/espace');
    }

    /** @param array<string, mixed>|null $openingPayload */
    private function fakeFederationFlow(?array $openingPayload = null, int $openingStatus = 201): void
    {
        $openingPayload ??= [
            'acces' => [
                'jeton' => 'FED-DEMO-TOKEN',
                'audience' => 'PRD-GAMAD-002',
            ],
        ];

        Http::fake([
            'core.test/api/v1/sessions' => Http::response([
                'jeton' => 'member-bearer',
                'entite' => 'AUT-GAMAD-001',
                'assurance' => 'AS1 — FACTEUR UNIQUE',
                'expire_le' => '2026-08-16T23:59:59+00:00',
            ], 201),
            'core.test/api/v1/identites/*' => Http::response([
                'reference' => 'AUT-GAMAD-001',
                'type' => 'personne',
                'libelle' => 'Membre Démo',
                'etat' => null,
                'source' => 'CORPUS-GAMAD',
                'regime' => 'DÉRIVÉ_DU_CORPUS',
            ]),
            'core.test/api/v1/sessions/current' => Http::response([
                'entite' => 'AUT-GAMAD-001',
                'assurance' => 'AS1 — FACTEUR UNIQUE',
                'expire_le' => '2026-08-16T23:59:59+00:00',
            ]),
            'core.test/api/v1/produits/PRD-GAMAD-002/ouverture' => Http::response($openingPayload, $openingStatus),
        ]);
    }
}
