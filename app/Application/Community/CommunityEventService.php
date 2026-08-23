<?php

declare(strict_types=1);

namespace App\Application\Community;

use App\Application\Organizations\OrganizationService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\CommunityEvent;
use App\Models\CommunityEventParticipant;
use App\Models\Organization;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * CAP-068 — Événement. Organisateur V1 strictement limité à ZUMRA_GROUP/ORGANIZATION, réutilisant
 * ZumraGroupService::isLeader() et OrganizationService::isManager()/canView() — aucune matrice
 * d'autorisation nouvelle. Cycle de vie volontairement réduit : SCHEDULED → COMPLETED|CANCELLED,
 * tous deux terminaux. Aucune récurrence, aucun calendrier complexe, aucun matching, aucune finance,
 * aucun score, aucune émargement de présence (inscription/désinscription seulement).
 */
final class CommunityEventService
{
    public const ORGANIZER_TYPES = [CommunityEvent::ORGANIZER_ZUMRA_GROUP, CommunityEvent::ORGANIZER_ORGANIZATION];

    public function __construct(
        private readonly ZumraGroupService $zumraGroups,
        private readonly OrganizationService $organizations,
    ) {}

    public function createForZumraGroup(ZumraGroup $group, string $actor, array $data): CommunityEvent
    {
        abort_unless($this->zumraGroups->isLeader($group, $actor), 403);

        return $this->store(CommunityEvent::ORGANIZER_ZUMRA_GROUP, $group->id, $actor, $data);
    }

    public function createForOrganization(Organization $organization, string $actor, array $data): CommunityEvent
    {
        abort_unless($this->organizations->isManager($organization, $actor), 403);

        return $this->store(CommunityEvent::ORGANIZER_ORGANIZATION, $organization->id, $actor, $data);
    }

    public function update(CommunityEvent $event, string $actor, array $data): CommunityEvent
    {
        $this->assertOrganizerAuthority($event, $actor);
        abort_unless($event->status === CommunityEvent::STATUS_SCHEDULED, 409, 'Seul un événement programmé peut être modifié.');

        $event->fill([
            'title' => $data['title'],
            'description' => $data['description'],
            'location' => $data['location'] ?? null,
            'visibility' => $this->normalizeVisibility($data),
            'scheduled_at' => $data['scheduled_at'],
        ])->save();

        return $event->refresh();
    }

    public function cancel(CommunityEvent $event, string $actor, ?string $note): CommunityEvent
    {
        $this->assertOrganizerAuthority($event, $actor);
        abort_unless($event->status === CommunityEvent::STATUS_SCHEDULED, 409, 'Seul un événement programmé peut être annulé.');

        $event->update([
            'status' => CommunityEvent::STATUS_CANCELLED,
            'decided_by_core_reference' => $actor, 'decision_note' => $note, 'cancelled_at' => now(),
        ]);

        return $event->refresh();
    }

    public function markCompleted(CommunityEvent $event, string $actor, ?string $note): CommunityEvent
    {
        $this->assertOrganizerAuthority($event, $actor);
        abort_unless($event->status === CommunityEvent::STATUS_SCHEDULED, 409, 'Seul un événement programmé peut être marqué tenu.');

        $event->update([
            'status' => CommunityEvent::STATUS_COMPLETED,
            'decided_by_core_reference' => $actor, 'decision_note' => $note, 'completed_at' => now(),
        ]);

        return $event->refresh();
    }

    public function register(CommunityEvent $event, string $actor): CommunityEventParticipant
    {
        abort_unless($this->canView($event, $actor), 404);
        abort_unless($event->status === CommunityEvent::STATUS_SCHEDULED, 409, 'Cet événement ne reçoit plus d’inscription.');

        try {
            return DB::transaction(function () use ($event, $actor): CommunityEventParticipant {
                $participant = CommunityEventParticipant::query()
                    ->where('community_event_id', $event->id)->where('core_identity_reference', $actor)
                    ->lockForUpdate()->first();
                abort_if($participant?->status === CommunityEventParticipant::STATUS_REGISTERED, 409, 'Vous êtes déjà inscrit à cet événement.');
                $participant ??= new CommunityEventParticipant(['community_event_id' => $event->id, 'core_identity_reference' => $actor]);
                $participant->fill(['status' => CommunityEventParticipant::STATUS_REGISTERED, 'registered_at' => now(), 'withdrawn_at' => null])->save();

                return $participant;
            });
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23505') {
                abort(409, 'Vous êtes déjà inscrit à cet événement.');
            }
            throw $exception;
        }
    }

    public function unregister(CommunityEvent $event, string $actor): void
    {
        $participant = CommunityEventParticipant::query()
            ->where('community_event_id', $event->id)->where('core_identity_reference', $actor)
            ->where('status', CommunityEventParticipant::STATUS_REGISTERED)
            ->firstOrFail();
        $participant->update(['status' => CommunityEventParticipant::STATUS_WITHDRAWN, 'withdrawn_at' => now()]);
    }

    public function canView(CommunityEvent $event, string $actor): bool
    {
        if ($event->visibility === CommunityEvent::VISIBILITY_PUBLIC) {
            return true;
        }

        return match ($event->organizer_type) {
            CommunityEvent::ORGANIZER_ZUMRA_GROUP => $this->isActiveZumraMember($event->organizer_reference, $actor),
            CommunityEvent::ORGANIZER_ORGANIZATION => $this->organizations->canView(Organization::query()->findOrFail($event->organizer_reference), $actor),
            default => false,
        };
    }

    /** Liste des inscrits — réservée à l'organisateur, jamais exposée à un participant ordinaire. */
    public function participants(CommunityEvent $event, string $actor): Collection
    {
        $this->assertOrganizerAuthority($event, $actor);

        return CommunityEventParticipant::query()
            ->where('community_event_id', $event->id)->where('status', CommunityEventParticipant::STATUS_REGISTERED)
            ->get();
    }

    public function forZumraGroup(ZumraGroup $group, string $actor): Collection
    {
        abort_unless($this->isActiveZumraMember($group->id, $actor) || $this->zumraGroups->isLeader($group, $actor), 404);

        return CommunityEvent::query()
            ->where('organizer_type', CommunityEvent::ORGANIZER_ZUMRA_GROUP)->where('organizer_reference', $group->id)
            ->latest('scheduled_at')->get();
    }

    public function forOrganization(Organization $organization, string $actor): Collection
    {
        abort_unless($this->organizations->canView($organization, $actor), 404);

        return CommunityEvent::query()
            ->where('organizer_type', CommunityEvent::ORGANIZER_ORGANIZATION)->where('organizer_reference', $organization->id)
            ->latest('scheduled_at')->get();
    }

    private function store(string $organizerType, string $organizerReference, string $actor, array $data): CommunityEvent
    {
        return CommunityEvent::query()->create([
            'public_reference' => (string) Str::uuid(),
            'organizer_type' => $organizerType,
            'organizer_reference' => $organizerReference,
            'organizer_core_reference' => $actor,
            'title' => $data['title'],
            'description' => $data['description'],
            'location' => $data['location'] ?? null,
            'visibility' => $this->normalizeVisibility($data),
            'status' => CommunityEvent::STATUS_SCHEDULED,
            'scheduled_at' => $data['scheduled_at'],
        ]);
    }

    private function normalizeVisibility(array $data): string
    {
        return ($data['visibility'] ?? null) === CommunityEvent::VISIBILITY_PUBLIC
            ? CommunityEvent::VISIBILITY_PUBLIC
            : CommunityEvent::VISIBILITY_INTERNAL;
    }

    private function assertOrganizerAuthority(CommunityEvent $event, string $actor): void
    {
        $authorized = match ($event->organizer_type) {
            CommunityEvent::ORGANIZER_ZUMRA_GROUP => $this->zumraGroups->isLeader(ZumraGroup::query()->findOrFail($event->organizer_reference), $actor),
            CommunityEvent::ORGANIZER_ORGANIZATION => $this->organizations->isManager(Organization::query()->findOrFail($event->organizer_reference), $actor),
            default => false,
        };
        abort_unless($authorized, 403);
    }

    private function isActiveZumraMember(string $groupId, string $actor): bool
    {
        return ZumraGroupMembership::query()
            ->where('zumra_group_id', $groupId)->where('core_identity_reference', $actor)
            ->where('status', ZumraGroupMembership::STATUS_ACTIVE)->exists();
    }
}
