<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Community\CommunityEventService;
use App\Domain\Identity\CoreIdentity;
use App\Models\CommunityEvent;
use App\Models\CommunityEventParticipant;
use App\Models\Organization;
use App\Models\ZumraGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * CAP-068 — surface minimale, JSON pour la lecture / redirection `back()` pour l'écriture (même
 * patron que ProjectFundingController/ModerationReportController, précédents les plus récents).
 */
final class CommunityEventController
{
    public function storeForZumraGroup(Request $request, ZumraGroup $group, CommunityEventService $events): RedirectResponse
    {
        $event = $events->createForZumraGroup($group, $this->actor($request), $this->validated($request));

        return redirect()->route('community-events.show', $event)->with('status', 'Événement créé.');
    }

    public function indexForZumraGroup(Request $request, ZumraGroup $group, CommunityEventService $events): JsonResponse
    {
        return response()->json(['events' => $events->forZumraGroup($group, $this->actor($request))->map(fn (CommunityEvent $e): array => $this->present($e))->values()]);
    }

    public function storeForOrganization(Request $request, Organization $organization, CommunityEventService $events): RedirectResponse
    {
        $event = $events->createForOrganization($organization, $this->actor($request), $this->validated($request));

        return redirect()->route('community-events.show', $event)->with('status', 'Événement créé.');
    }

    public function indexForOrganization(Request $request, Organization $organization, CommunityEventService $events): JsonResponse
    {
        return response()->json(['events' => $events->forOrganization($organization, $this->actor($request))->map(fn (CommunityEvent $e): array => $this->present($e))->values()]);
    }

    public function show(Request $request, CommunityEvent $event, CommunityEventService $events): JsonResponse
    {
        abort_unless($events->canView($event, $this->actor($request)), 404);

        return response()->json(['event' => $this->present($event)]);
    }

    public function update(Request $request, CommunityEvent $event, CommunityEventService $events): RedirectResponse
    {
        $events->update($event, $this->actor($request), $this->validated($request));

        return back()->with('status', 'Événement mis à jour.');
    }

    public function cancel(Request $request, CommunityEvent $event, CommunityEventService $events): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:800']]);
        $events->cancel($event, $this->actor($request), $data['note'] ?? null);

        return back()->with('status', 'Événement annulé.');
    }

    public function markCompleted(Request $request, CommunityEvent $event, CommunityEventService $events): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:800']]);
        $events->markCompleted($event, $this->actor($request), $data['note'] ?? null);

        return back()->with('status', 'Événement marqué tenu.');
    }

    public function register(Request $request, CommunityEvent $event, CommunityEventService $events): RedirectResponse
    {
        $events->register($event, $this->actor($request));

        return back()->with('status', 'Inscription enregistrée.');
    }

    public function unregister(Request $request, CommunityEvent $event, CommunityEventService $events): RedirectResponse
    {
        $events->unregister($event, $this->actor($request));

        return back()->with('status', 'Inscription retirée.');
    }

    public function participants(Request $request, CommunityEvent $event, CommunityEventService $events): JsonResponse
    {
        $participants = $events->participants($event, $this->actor($request))
            ->map(fn (CommunityEventParticipant $p): array => ['core_identity_reference' => $p->core_identity_reference, 'registered_at' => $p->registered_at])
            ->values();

        return response()->json(['participants' => $participants]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:160'],
            'description' => ['required', 'string', 'min:10', 'max:4000'],
            'location' => ['nullable', 'string', 'max:160'],
            'visibility' => ['required', 'in:'.CommunityEvent::VISIBILITY_INTERNAL.','.CommunityEvent::VISIBILITY_PUBLIC],
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);
    }

    /** @return array<string, mixed> */
    private function present(CommunityEvent $event): array
    {
        return [
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
        ];
    }

    private function actor(Request $request): string
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        return $identity->reference;
    }
}
