<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Models\Satellite;
use Illuminate\View\View;

/** Read-only presentation of the registered tools. Access remains owned by federation.continue. */
final class MemberToolsComposer
{
    public function compose(View $view): void
    {
        $view->with('connectedTools', Satellite::query()
            ->where('is_active', true)
            ->orderBy('display_name')
            ->get(['slug', 'display_name', 'description']));
    }
}
