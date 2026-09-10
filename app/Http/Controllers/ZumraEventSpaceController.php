<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Community\CommunityEventService;
use App\Application\Zumra\ZumraGroupService;
use App\Domain\Identity\CoreIdentity;
use App\Models\CommunityEvent;
use App\Models\CommunityEventParticipant;
use App\Models\PortalAdministrator;
use App\Models\ZumraGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * EVENT-001 — surface humaine des événements d'une ZUMRA.
 *
 * Cette couche ne crée aucune autorité ni aucun moteur calendaire :
 * CommunityEventService (CAP-068) reste l'unique source métier.
 */
final class ZumraEventSpaceController
{
    public function index(
        Request $request,
        ZumraGroup $group,
        CommunityEventService $events,
        ZumraGroupService $zumraGroups,
    ): View|JsonResponse {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $actor = $identity->reference;
        $collection = $events->forZumraGroup($group, $actor);

        if ($request->expectsJson()) {
            return response()->json([
                'events' => $collection->map(fn (CommunityEvent $event): array => [
                    'public_reference' => $event->public_reference,
                    'organizer_type' => $event->organizer_type,
                    'organizer_reference' => $event->organizer_reference,
                    'title' => $event->title,
                    'description' => $event->description,
                    'location' => $event->location,
                    'visibility' => $event->visibility,
                    'status' => $event->status,
                    'scheduled_at' => $event->scheduled_at,
                    'completed_at' => $event->completed_at,
                    'cancelled_at' => $event->cancelled_at,
                ])->values(),
            ]);
        }

        $registeredEventIds = CommunityEventParticipant::query()
            ->where('core_identity_reference', $actor)
            ->where('status', CommunityEventParticipant::STATUS_REGISTERED)
            ->whereIn('community_event_id', $collection->pluck('id'))
            ->pluck('community_event_id')
            ->all();

        return view('zumra.groups.events', [
            'identity' => $identity,
            'isAdministrator' => PortalAdministrator::query()->whereKey($actor)->exists(),
            'group' => $group,
            'events' => $collection,
            'isLeader' => $zumraGroups->isLeader($group, $actor),
            'registeredEventIds' => $registeredEventIds,
        ]);
    }
}
