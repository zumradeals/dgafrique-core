<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class DesignInvariantTest extends TestCase
{
    public function test_design_reference_is_declared_canonical_and_versioned(): void
    {
        $invariants = file_get_contents(base_path('docs/design/DESIGN-INVARIANTS.md'));
        $handoff = file_get_contents(base_path('docs/AI-HANDOFF.md'));

        self::assertIsString($invariants);
        self::assertStringContainsString('CANONIQUE DESIGN — ADOPTÉ', $invariants);
        self::assertStringContainsString('claude-2026-08-16', $invariants);
        self::assertIsString($handoff);
        self::assertStringContainsString('docs/design/DESIGN-INVARIANTS.md', $handoff);
    }

    public function test_action_network_styles_keep_the_adopted_palette(): void
    {
        $css = file_get_contents(public_path('design/dg-identity.css'));

        self::assertIsString($css);

        foreach ([
            '#F8F3EA',
            '#EDE4D4',
            '#FFFDF7',
            '#103028',
            '#1B4A3B',
            '#A9552B',
            '#14314D',
            '#D9A02B',
        ] as $canonicalColor) {
            self::assertStringContainsString($canonicalColor, $css);
        }
    }

    public function test_action_navigation_only_targets_existing_named_routes(): void
    {
        foreach ([
            'activity.index',
            'member.space',
            'people.index',
            'needs.index',
            'projects.index',
            'zumra.index',
            'messages.index',
            'shares.index',
            'federation.continue.gamadrive',
        ] as $routeName) {
            self::assertTrue(Route::has($routeName), 'Missing route: '.$routeName);
        }
    }

    public function test_activity_view_uses_action_network_layer_without_popularity_widgets(): void
    {
        $view = file_get_contents(resource_path('views/activity/index.blade.php'));

        self::assertIsString($view);
        self::assertStringContainsString('dg-feed-hero', $view);
        self::assertStringContainsString('Ce qui peut vous faire avancer', $view);
        self::assertStringContainsString('Le fil s’arrête ici', $view);
        self::assertStringNotContainsString("J’aime", $view);
        self::assertStringNotContainsString('followers', strtolower($view));
        self::assertStringNotContainsString('nombre de likes', strtolower($view));
    }
}
