<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class MemberAccountRegistrationTest extends TestCase
{
    public function test_a_new_account_enters_a_bounded_verification_flow_without_password_in_session(): void
    {
        $this->fakeCoreAccountCreation();

        $this->post('/creer-un-compte', [
            'name' => 'Membre Nouveau',
            'email' => 'nouveau@example.test',
            'password' => 'Secret2026',
            'password_confirmation' => 'Secret2026',
            'terms' => '1',
        ])->assertRedirect('/verifier-le-compte')
            ->assertSessionHas('dg_pending_account.identity', 'IDN-PER-000000099')
            ->assertSessionMissing('dg_pending_account.password')
            ->assertSessionMissing('dg_pending_account.code')
            ->assertSessionMissing('_old_input.password');

        Http::assertSent(fn ($request): bool =>
            $request->url() === 'https://core.test/api/v1/comptes'
            && $request->hasHeader('Authorization', 'Bearer product-session')
            && $request['mot_de_passe'] === 'Secret2026'
        );
    }

    public function test_a_valid_code_completes_verification_and_returns_to_login(): void
    {
        $this->fakeCoreAccountCreation(verificationStatus: 200);
        $this->createPendingAccount();

        $this->post('/verifier-le-compte', ['code' => '123456'])
            ->assertRedirect('/connexion')
            ->assertSessionMissing('dg_pending_account')
            ->assertSessionHasInput('identifier', 'nouveau@example.test')
            ->assertSessionMissing('_old_input.code');
    }

    public function test_a_refused_code_preserves_the_pending_account_and_never_flashes_the_code(): void
    {
        $this->fakeCoreAccountCreation(verificationStatus: 422);
        $this->createPendingAccount();

        $this->from('/verifier-le-compte')->post('/verifier-le-compte', ['code' => '000000'])
            ->assertRedirect('/verifier-le-compte')
            ->assertSessionHas('dg_pending_account.identity', 'IDN-PER-000000099')
            ->assertSessionMissing('_old_input.code');
    }

    public function test_a_delivery_failure_keeps_references_for_a_governed_resend(): void
    {
        $this->fakeCoreAccountCreation(delivered: false);

        $this->post('/creer-un-compte', [
            'name' => 'Membre Nouveau',
            'email' => 'nouveau@example.test',
            'password' => 'Secret2026',
            'password_confirmation' => 'Secret2026',
            'terms' => '1',
        ])->assertRedirect('/verifier-le-compte')
            ->assertSessionHas('dg_pending_account.delivered', false)
            ->assertSessionHas('dg_pending_account.identifier_reference', 'IDF-GAMAD-099');
    }

    public function test_a_duplicate_email_conflict_never_names_gamad_in_the_response(): void
    {
        // UIUX-001 : le message de conflit affichait littéralement « Compte GAMAD » à l'écran —
        // une fuite de l'institution invisible (docs/canon/ZUMRA-DOCTRINE-INVARIANTE.md §3.2),
        // que IdentityAuthorityGuardTest ne pouvait pas détecter (chaîne PHP dynamique, pas Blade).
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'product-session'], 201),
            'core.test/api/v1/comptes' => Http::response(['erreur' => 'CONFLIT', 'message' => 'Déjà existant'], 409),
        ]);

        $this->from('/creer-un-compte')->post('/creer-un-compte', [
            'name' => 'Membre Nouveau',
            'email' => 'deja@example.test',
            'password' => 'Secret2026',
            'password_confirmation' => 'Secret2026',
            'terms' => '1',
        ])->assertRedirect('/creer-un-compte');

        // Les erreurs flashées ne sont visibles qu'au prochain rendu de la page réelle : c'est
        // exactement ce qu'un visiteur verrait à l'écran après le rechargement du formulaire.
        $redirected = $this->get('/creer-un-compte')->assertOk()->getContent();
        self::assertStringContainsString('possède déjà un compte', $redirected);
        self::assertStringNotContainsStringIgnoringCase('GAMAD', $redirected);
    }

    public function test_the_successful_registration_status_message_never_names_gamad(): void
    {
        $this->fakeCoreAccountCreation();

        $this->post('/creer-un-compte', [
            'name' => 'Membre Nouveau',
            'email' => 'nouveau@example.test',
            'password' => 'Secret2026',
            'password_confirmation' => 'Secret2026',
            'terms' => '1',
        ])->assertRedirect('/verifier-le-compte');

        $content = $this->get('/verifier-le-compte')->assertOk()->getContent();
        self::assertStringContainsString('code à 6 chiffres', $content);
        self::assertStringNotContainsStringIgnoringCase('GAMAD', $content);
    }

    public function test_the_resend_status_message_never_names_gamad(): void
    {
        $this->fakeCoreAccountCreation();
        $this->createPendingAccount();

        $this->from('/verifier-le-compte')->post('/verifier-le-compte/renvoi')->assertRedirect('/verifier-le-compte');

        $content = $this->get('/verifier-le-compte')->assertOk()->getContent();
        self::assertStringContainsString('Un nouveau code a été envoyé.', $content);
        self::assertStringNotContainsStringIgnoringCase('GAMAD', $content);
    }

    private function createPendingAccount(): void
    {
        $this->post('/creer-un-compte', [
            'name' => 'Membre Nouveau',
            'email' => 'nouveau@example.test',
            'password' => 'Secret2026',
            'password_confirmation' => 'Secret2026',
            'terms' => '1',
        ])->assertRedirect('/verifier-le-compte');
    }

    private function fakeCoreAccountCreation(bool $delivered = true, ?int $verificationStatus = null): void
    {
        $payload = [
            'compte' => [
                'identite' => 'IDN-PER-000000099',
                'type_identifiant' => 'EMAIL',
                'identifiant_reference' => 'IDF-GAMAD-099',
                'verification_requise' => true,
            ],
            'verification' => [
                'reference' => 'VRF-GAMAD-099',
                'expire_le' => '2026-08-14T23:59:00+00:00',
                'livraison' => ['canal' => 'EMAIL', 'livree' => $delivered],
            ],
        ];

        if (!$delivered) {
            $payload['erreur'] = 'VERIFICATION_NON_LIVREE';
            $payload['message'] = 'Non livrée';
        }

        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'product-session'], 201),
            'core.test/api/v1/comptes' => Http::response($payload, $delivered ? 201 : 503),
            'core.test/api/v1/comptes/verifications' => Http::response(
                $verificationStatus === 422
                    ? ['erreur' => 'CODE_INCORRECT', 'message' => 'Refusé']
                    : ['identifiant' => ['etat' => 'VERIFIE']],
                $verificationStatus ?? 200,
            ),
            'core.test/api/v1/comptes/verifications/renvoi' => Http::response([
                'verification' => [
                    'reference' => 'VRF-GAMAD-099-2',
                    'expire_le' => '2026-08-14T23:59:00+00:00',
                    'livraison' => ['canal' => 'EMAIL', 'livree' => true],
                ],
            ], 200),
            'core.test/api/v1/sessions/current' => Http::response([], 204),
        ]);
    }
}
