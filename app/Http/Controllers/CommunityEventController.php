<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Community\CommunityEventService;
use App\Application\Organizations\OrganizationService;
use App\Application\Zumra\ZumraGroupService;
use App\Domain\Identity\CoreIdentity;
use App\Models\CommunityEvent;
use App\Models\CommunityEventParticipant;
use App\Models\Organization;
use App\Models\PortalAdministrator;
use App\Models\ZumraGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CAP-068 — listes en JSON, redirection `back()` pour l'écriture (même patron que
 * ProjectFundingController/ModerationReportController) ; `show()` seul est une vraie fiche HTML
 * (UIUX-003 — première interface humaine de l'Événement ; UIUX-004 — parcours organisateur
 * complet : modifier/annuler/marquer tenu exposés depuis la même fiche, sans nouvelle autorité).
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

    /**
     * UIUX-007 — gabarit minimal manquant identifié par l'audit Phase A : `storeForZumraGroup()`
     * existait déjà, aucune vue GET ne l'exposait. Même autorité que le service
     * (`ZumraGroupService::isLeader()`), jamais recalculée.
     */
    public function createForZumraGroup(Request $request, ZumraGroup $group, ZumraGroupService $zumraGroups): View
    {
        abort_unless($zumraGroups->isLeader($group, $this->actor($request)), 403);

        return view('community-events.create', [
            'identity' => $request->attributes->get('dg_identity'),
            'isAdministrator' => PortalAdministrator::query()->whereKey($this->actor($request))->exists(),
            'organizerLabel' => $group->name,
            'storeUrl' => route('community-events.zumra.store', $group),
            'backUrl' => route('zumra.groups.show', $group),
        ]);
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

    /**
     * UIUX-007 — même gabarit partagé que `createForZumraGroup()`, même autorité que le service
     * (`OrganizationService::isManager()`).
     */
    public function createForOrganization(Request $request, Organization $organization, OrganizationService $organizations): View
    {
        abort_unless($organizations->isManager($organization, $this->actor($request)), 403);

        return view('community-events.create', [
            'identity' => $request->attributes->get('dg_identity'),
            'isAdministrator' => PortalAdministrator::query()->whereKey($this->actor($request))->exists(),
            'organizerLabel' => $organization->name,
            'storeUrl' => route('community-events.organization.store', $organization),
            'backUrl' => route('organizations.show', $organization),
        ]);
    }

    /**
     * CAP-068/UIUX-003/UIUX-004 — fiche humaine complète de l'Événement : organisateur réel (avec
     * retour vers lui), date, état, visibilité, action de participation, et pour l'organisateur
     * réel les transitions déjà existantes (modifier/annuler/marquer tenu). Aucune autorité
     * nouvelle : canView()/register()/unregister()/update()/cancel()/markCompleted() et
     * ZumraGroupService::isLeader()/OrganizationService::isManager() restent la seule décision
     * réelle — cette méthode ne fait que décider quoi afficher à partir de ces réponses.
     */
    public function show(Request $request, CommunityEvent $event, CommunityEventService $events, ZumraGroupService $zumraGroups, OrganizationService $organizations): View
    {
        $actor = $this->actor($request);
        abort_unless($events->canView($event, $actor), 404);

        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        $organizer = match ($event->organizer_type) {
            CommunityEvent::ORGANIZER_ZUMRA_GROUP => ZumraGroup::query()->find($event->organizer_reference),
            CommunityEvent::ORGANIZER_ORGANIZATION => Organization::query()->find($event->organizer_reference),
            default => null,
        };

        $organizerUrl = match (true) {
            $event->organizer_type === CommunityEvent::ORGANIZER_ZUMRA_GROUP && $organizer !== null => route('zumra.groups.show', $organizer),
            $event->organizer_type === CommunityEvent::ORGANIZER_ORGANIZATION && $organizer !== null => route('organizations.show', $organizer),
            default => null,
        };

        $isRegistered = CommunityEventParticipant::query()
            ->where('community_event_id', $event->id)
            ->where('core_identity_reference', $actor)
            ->where('status', CommunityEventParticipant::STATUS_REGISTERED)
            ->exists();

        $isOrganizerAuthority = match (true) {
            $event->organizer_type === CommunityEvent::ORGANIZER_ZUMRA_GROUP && $organizer instanceof ZumraGroup => $zumraGroups->isLeader($organizer, $actor),
            $event->organizer_type === CommunityEvent::ORGANIZER_ORGANIZATION && $organizer instanceof Organization => $organizations->isManager($organizer, $actor),
            default => false,
        };

        return view('community-events.show', [
            'identity' => $identity,
            'isAdministrator' => PortalAdministrator::query()->whereKey($identity->reference)->exists(),
            'event' => $event,
            'organizer' => $organizer,
            'organizerLabel' => $organizer->name ?? null,
            'organizerUrl' => $organizerUrl,
            'isRegistered' => $isRegistered,
            'canParticipate' => $event->status === CommunityEvent::STATUS_SCHEDULED,
            'isOrganizerAuthority' => $isOrganizerAuthority,
            'participantsCount' => $isOrganizerAuthority ? $events->participants($event, $actor)->count() : null,
        ]);
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
