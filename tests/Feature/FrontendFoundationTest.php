<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class FrontendFoundationTest extends TestCase
{
    public function test_mobile_navigation_renders_exactly_five_canonical_controls(): void
    {
        $html = view('components.dg.navigation', [
            'active' => 'fil',
            'actions' => $this->actions(),
        ])->render();

        preg_match_all('/data-mobile-primary="([^"]+)"/', $html, $matches);

        $this->assertSame(['fil', 'discover', 'act', 'zumra', 'space'], $matches[1]);
        $this->assertStringNotContainsString('>Plus<', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringContainsString('aria-label="Mon espace"', $html);
    }

    public function test_action_sheet_renders_only_actions_supplied_by_the_authorized_context(): void
    {
        $html = view('components.dg.navigation', [
            'active' => 'space',
            'actions' => [$this->actions()[0]],
        ])->render();

        $this->assertStringContainsString('Exprimer un besoin', $html);
        $this->assertStringNotContainsString('Lancer un projet', $html);
        $this->assertStringContainsString('Seules les actions réellement disponibles', $html);
    }

    public function test_action_control_is_disabled_when_the_context_has_no_real_action(): void
    {
        $html = view('components.dg.navigation', [
            'active' => 'space',
            'actions' => [],
        ])->render();

        $this->assertSame(2, substr_count($html, 'disabled'));
        preg_match('/data-actions-list>(.*?)<\/ul>/s', $html, $matches);
        $this->assertStringNotContainsString('data-autofocus', $matches[1] ?? '');
    }

    public function test_all_transversal_states_have_human_feedback(): void
    {
        foreach (['loading', 'empty', 'error', 'offline', 'unavailable', 'forbidden', 'not-found', 'conflict', 'success'] as $type) {
            $html = Blade::render('<x-dg.state :type="$type" />', ['type' => $type]);

            $this->assertStringContainsString('dg-state--'.$type, $html);
            $this->assertStringContainsString('aria-live=', $html);
        }
    }

    /** @return array<int, array{label: string, description: string, href: string, icon: string}> */
    private function actions(): array
    {
        return [
            [
                'label' => 'Exprimer un besoin',
                'description' => 'Dites ce qui vous manque pour avancer.',
                'href' => route('needs.create'),
                'icon' => 'need',
            ],
            [
                'label' => 'Lancer un projet',
                'description' => 'Transformez une intention en action suivie.',
                'href' => route('projects.create'),
                'icon' => 'project',
            ],
        ];
    }
}
