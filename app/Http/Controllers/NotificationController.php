<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Notifications\NotificationService;
use App\Domain\Identity\CoreIdentity;
use App\Models\PortalAdministrator;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class NotificationController
{
    public function index(Request $request, NotificationService $notifications): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        $sections = $notifications->sections($identity->reference);
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        // La consultation vaut lecture pour les items « Récentes », même mécanique que
        // MessageParticipant.last_read_at — aucun bouton dédié (fiche §16.4).
        $notifications->markFyiSeen($identity->reference, $sections['recentes']);

        return view('notifications.index', compact('identity', 'sections', 'isAdministrator'));
    }
}
