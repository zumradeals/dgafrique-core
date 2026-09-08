<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Le nom historique du fichier est conservé pour les scripts d’exploitation existants.
 * La page `/decouvrir` n’est plus une surface publique autonome : elle redirige vers l’accueil.
 */
final class LandingPublicDiscoveryTest extends TestCase
{
    public function test_home_is_the_canonical_public_entry(): void
    {
        $content = $this->get('/')
            ->assertOk()
            ->getContent();

        self::assertStringContainsString('De vos idées.', $content);
        self::assertStringContainsString('À nos actions.', $content);
        self::assertStringContainsString('Voir comment ça marche', $content);
        self::assertStringContainsString('Vous n’avez pas besoin', $content);
        self::assertStringContainsString('la valeur ne se mesure pas en likes', $content);
        self::assertStringContainsString('Vous pouvez utiliser GAMAD sans appartenir à une ZUMRA', $content);
    }

    public function test_legacy_discovery_url_redirects_permanently_to_home(): void
    {
        $this->get('/decouvrir')
            ->assertStatus(301)
            ->assertRedirect(route('gateway'));
    }

    public function test_public_entry_does_not_offer_a_second_discovery_page(): void
    {
        $content = $this->get('/')
            ->assertOk()
            ->getContent();

        self::assertStringNotContainsString('Découvrir le réseau', $content);
        self::assertStringNotContainsString('Que puis-je découvrir aujourd’hui sans compte', $content);
        self::assertStringNotContainsString('Besoins et projets publics', $content);
    }

    public function test_public_entry_keeps_account_and_zumra_distinct(): void
    {
        $content = $this->get('/')
            ->assertOk()
            ->getContent();

        self::assertStringContainsString('Compte gratuit', $content);
        self::assertStringContainsString('adhésion', mb_strtolower($content));
        self::assertStringContainsString('ZUMRA', $content);
    }
}
