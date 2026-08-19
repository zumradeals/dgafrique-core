<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class MemberAccountFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_can_sign_in_and_enter_the_personal_space(): void
    {
        $this->fakeSuccessfulAuthentication();

        $this->post('/connexion', [
            'identifier' => 'AUT-GAMAD-001',
            'secret' => 'member-secret',
            'next' => '/espace',
        ])->assertRedirect('/espace')
            ->assertSessionHas('dg_core_member.reference', 'AUT-GAMAD-001')
            ->assertSessionMissing('secret');

        $this->get('/espace')
            ->assertOk()
            ->assertSee('Membre Démo')
            ->assertSee('Identité reconnue')
            ->assertDontSee('GAMAD')
            ->assertSee('Aujourd’hui — une seule chose compte')
            ->assertSee('Déclarez une chose que vous savez faire.')
            ->assertSee('Commencer mon profil');

        Http::assertSent(fn ($request): bool =>
            $request->url() === 'https://core.test/api/v1/sessions/current'
            && $request->hasHeader('Authorization', 'Bearer member-bearer')
        );
    }

    public function test_a_rejected_core_session_is_destroyed_and_returns_to_login(): void
    {
        $this->fakeSuccessfulAuthentication(currentSessionStatus: 401);
        $this->post('/connexion', [
            'identifier' => 'AUT-GAMAD-001',
            'secret' => 'member-secret',
        ])->assertRedirect('/espace');

        $this->get('/espace')
            ->assertRedirect('/connexion')
            ->assertSessionMissing('dg_core_member');
    }

    public function test_a_transient_core_failure_preserves_the_portal_session(): void
    {
        $this->fakeSuccessfulAuthentication(currentSessionStatus: 503);
        $this->post('/connexion', [
            'identifier' => 'AUT-GAMAD-001',
            'secret' => 'member-secret',
        ])->assertRedirect('/espace');

        $this->get('/espace')
            ->assertStatus(503)
            ->assertSee('Connexion préservée')
            ->assertSessionHas('dg_core_member.reference', 'AUT-GAMAD-001');
    }

    public function test_login_never_redirects_to_an_external_destination(): void
    {
        $this->fakeSuccessfulAuthentication();

        $this->post('/connexion', [
            'identifier' => 'AUT-GAMAD-001',
            'secret' => 'member-secret',
            'next' => 'https://evil.example/steal',
        ])->assertRedirect('/espace');
    }

    public function test_logout_revokes_core_and_clears_the_local_session(): void
    {
        $this->fakeSuccessfulAuthentication();
        $this->post('/connexion', [
            'identifier' => 'AUT-GAMAD-001',
            'secret' => 'member-secret',
        ]);

        $this->post('/deconnexion')
            ->assertRedirect('/connexion')
            ->assertSessionMissing('dg_core_member');

        Http::assertSent(fn ($request): bool =>
            $request->method() === 'DELETE'
            && $request->url() === 'https://core.test/api/v1/sessions/current'
        );
    }

    public function test_password_is_not_flashed_after_a_failed_login(): void
    {
        Http::fake(['core.test/api/v1/sessions' => Http::response([], 401)]);

        $this->from('/connexion')->post('/connexion', [
            'identifier' => 'AUT-GAMAD-001',
            'secret' => 'do-not-flash-me',
        ])->assertRedirect('/connexion')
            ->assertSessionHasInput('identifier', 'AUT-GAMAD-001')
            ->assertSessionMissing('_old_input.secret');
    }

    public function test_an_authenticated_member_can_open_the_honest_zumra_preview(): void
    {
        $this->fakeSuccessfulAuthentication();
        $this->post('/connexion', [
            'identifier' => 'AUT-GAMAD-001',
            'secret' => 'member-secret',
        ])->assertRedirect('/espace');

        $this->get('/zumra')
            ->assertOk()
            ->assertSee('ZUMRA')
            ->assertSee('Votre première ZUMRA vous attend')
            ->assertDontSee('Contribution à jour')
            ->assertDontSee('24 J’aime');
    }

    private function fakeSuccessfulAuthentication(int $currentSessionStatus = 200): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response([
                'jeton' => 'member-bearer',
                'entite' => 'AUT-GAMAD-001',
                'assurance' => 'AS1 — FACTEUR UNIQUE',
                'expire_le' => '2026-08-14T03:42:19+00:00',
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
                'expire_le' => '2026-08-14T03:42:19+00:00',
            ], $currentSessionStatus),
        ]);
    }
}
