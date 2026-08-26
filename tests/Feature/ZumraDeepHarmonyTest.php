<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class ZumraDeepHarmonyTest extends TestCase
{
    public function test_every_active_zumra_get_surface_is_classified_and_preserves_json_contracts(): void
    {
        $inventory = file_get_contents(base_path('docs/design/UX-HARMONY-ZUMRA-DEEP-001-INVENTORY.md'));

        foreach ([
            'zumra.index', 'zumra.groups.index', 'zumra.groups.create', 'zumra.groups.show',
            'zumra.membership.show', 'zumra.payment.return', 'zumra.payment.receipt',
            'zumra.card.show', 'zumra.card.verify', 'zumra.groups.missions.create',
            'community-events.zumra.index', 'community-events.zumra.create',
            'comments.zumra-activity', 'shares.group', 'impact-metrics.zumra',
            'zumra.groups.moderation.index', 'zahab.wallet.zumra-group',
        ] as $name) {
            self::assertTrue(Route::has($name), $name.' doit rester routée.');
            self::assertStringContainsString('`'.$name.'`', $inventory, $name.' doit être inventoriée.');
        }

        self::assertStringContainsString('`JSON_ONLY`', $inventory);
        self::assertStringContainsString('`ADMIN`', $inventory);
        self::assertStringContainsString('`REDONDANTE`', $inventory);
    }

    public function test_deep_styles_are_scoped_and_do_not_create_financial_actions(): void
    {
        $layout = file_get_contents(resource_path('views/components/layouts/portal.blade.php'));
        $styles = file_get_contents(resource_path('css/zumra-deep.css'));
        $vite = file_get_contents(base_path('vite.config.js'));

        self::assertStringContainsString("request()->routeIs('zumra.membership.*')", $layout);
        self::assertStringContainsString("request()->routeIs('comments.zumra-activity')", $layout);
        self::assertStringContainsString('resources/css/zumra-deep.css', $vite);
        self::assertStringContainsString('@media(max-width:760px)', $styles);
        self::assertStringNotContainsString('credit(', $styles);
        self::assertStringNotContainsString('debit(', $styles);
        self::assertStringNotContainsString('Ledger', $styles);
    }

    public function test_governance_remains_visible_and_ctas_stay_conditioned_by_real_authorities(): void
    {
        $view = file_get_contents(resource_path('views/zumra/groups/show.blade.php'));

        self::assertStringContainsString('id="gouvernance"', $view);
        self::assertStringContainsString('@if($isLeader)', $view);
        self::assertStringContainsString("\$membership?->status === 'ACTIVE'", $view);
        self::assertStringContainsString("route('zumra.groups.roles.propose'", $view);
        self::assertStringContainsString("route('zumra.groups.requests.approve'", $view);
    }
}
