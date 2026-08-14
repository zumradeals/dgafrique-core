<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class IdentityAuthorityGuardTest extends TestCase
{
    public function test_laravel_has_no_parallel_member_identity_provider(): void
    {
        self::assertSame(['web' => null], config('auth.guards'));
        self::assertSame(['users' => null], config('auth.providers'));
        self::assertSame(['users' => null], config('auth.passwords'));
        self::assertFileDoesNotExist(app_path('Models/User.php'));
        self::assertFileDoesNotExist(database_path('migrations/0001_01_01_000000_create_users_table.php'));
    }

    public function test_the_foundation_page_is_available(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Ce que vous savez faire')
            ->assertSee('Révéler les capacités')
            ->assertSee('Exprimer les besoins')
            ->assertSee('Ouvrir des opportunités')
            ->assertSee('Une ZUMRA peut bâtir.')
            ->assertDontSee('4 250')
            ->assertDontSee('1,2 million');
    }

    public function test_the_invisible_institution_is_never_named_in_user_interfaces(): void
    {
        foreach (File::allFiles(resource_path('views')) as $view) {
            self::assertDoesNotMatchRegularExpression(
                '/\\bGAMAD\\b/ui',
                $view->getContents(),
                "Le nom institutionnel réservé est exposé dans {$view->getRelativePathname()}."
            );
        }
    }
}
