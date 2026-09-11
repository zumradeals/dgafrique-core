<?php

declare(strict_types=1);

namespace App\Application\Activity;

use App\Application\Community\CommunityEventService;
use App\Application\Missions\MissionVisibilityService;
use App\Application\Proof\ProofVisibilityService;
use App\Application\Transmission\TransmissionVisibilityService;
use App\Models\CommunityEvent;
use App\Models\Mission;
use App\Models\MissionEvent;
use App\Models\Proof;
use App\Models\ProofEvent;
use App\Models\Transmission;
use App\Models\TransmissionEvent;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * ZUMRA-ACTIVITY-001 — projection locale du Fil canonique.
 *
 * Ce service ne persiste rien et ne constitue pas un second moteur social : il lit les mêmes
 * journaux métier que CAP-019/CAP-055, délègue les droits aux autorités existantes et limite
 * simplement la projection au contexte d'une ZUMRA précise.
 */
final class ZumraActivityProjection
{
    private const GROUP_EVENTS = [
        'GROUP_PROPOSED' => ['label' => 'ZUMRA créée', 'priority' => 190],
        'INVITATION_ACCEPTED' => ['label' => 'Un membre a rejoint la ZUMRA', 'priority' => 160],
        'MEMBERSHIP_APPROVED' => ['label' => 'Un membre a rejoint la ZUMRA', 'priority' => 160],
        'ROLE_ACCEPTED' => ['label' => 'Une responsabilité a été acceptée', 'priority' => 170],
        'GROUP_READY' => ['label' => 'La ZUMRA est structurellement prête', 'priority' => 185],
        'GROUP_VALIDATED' => ['label' => 'La ZUMRA a été validée', 'priority' => 195],
        'GROUP_ACTIVATED' => ['label' => 'La ZUMRA est active', 'priority' => 200],
    ];

    private const MISSION_EVENTS = [
        'MISSION_OFFICIALIZED' => ['label' => 'Mission officialisée', 'priority' => 210],
        'MISSION_BLOCKED' => ['label' => 'Mission bloquée', 'priority' => 270],
        'MISSION_COMPLETED' => ['label' => 'Mission terminée', 'priority' => 130],
    ];

    private const TRANSMISSION_EVENTS = [
        'TRANSMISSION_PROPOSED' => ['label' => 'Nouvelle transmission', 'priority' => 240],
        'TRANSMISSION_ACCEPTED' => ['label' => 'Transmission acceptée', 'priority' => 220],
        'TRANSMISSION_COMPLETED' => ['label' => 'Transmission terminée', 'priority' => 130],
    ];

    private const PROOF_EVENTS = [
        'PROOF_SUBMITTED' => ['label' => 'Preuve enregistrée', 'priority' => 170],
        'PROOF_ACKNOWLEDGED' => ['label' => 'Preuve reconnue', 'priority' => 150],
    ];

    public function __construct(
        private readonly MissionVisibilityService $missionVisibility,
        private readonly TransmissionVisibilityService $transmissionVisibility,
        private readonly ProofVisibilityService $proofVisibility,
        private readonly CommunityEventService $communityEvents,
    ) {}

    /** @return Collection<int, array<string, mixed>> */
    public function forZumra(ZumraGroup $group, string $actor): Collection
    {
        return collect()
            ->concat($this->groupItems($group))
            ->concat($this->missionItems($group, $actor))
            ->concat($this->transmissionItems($group, $actor))
            ->concat($this->proofItems($group, $actor))
            ->concat($this->communityEventItems($group, $actor))
            ->sort(static function (array $left, array $right): int {
                $priority = $right['priority'] <=> $left['priority'];

                return $priority !== 0
                    ? $priority
                    : $right['occurred_at']->getTimestamp() <=> $left['occurred_at']->getTimestamp();
            })
            ->values();
    }

    private function groupItems(ZumraGroup $group): Collection
    {
        return ZumraGroupEvent::query()
            ->where('zumra_group_id', $group->id)
            ->whereIn('event', array_keys(self::GROUP_EVENTS))
            ->latest('occurred_at')
            ->limit(40)
            ->get()
            ->map(function (ZumraGroupEvent $event) use ($group): array {
                $meta = self::GROUP_EVENTS[$event->event];

                return [
                    'key' => 'zumra-event:'.$event->id,
                    'kind_label' => 'ZUMRA',
                    'event_label' => $meta['label'],
                    'priority' => $meta['priority'],
                    'title' => $group->name,
                    'summary' => $this->groupSummary($event),
                    'action_label' => 'Voir la ZUMRA',
                    'action_url' => route('zumra.groups.show', $group),
                    'occurred_at' => $event->occurred_at,
                ];
            });
    }

    private function missionItems(ZumraGroup $group, string $actor): Collection
    {
        $missions = Mission::query()
            ->where('context_type', 'ZUMRA')
            ->where('context_reference', $group->id)
            ->get()
            ->keyBy('id');

        if ($missions->isEmpty()) {
            return collect();
        }

        return MissionEvent::query()
            ->whereIn('mission_id', $missions->keys())
            ->whereIn('event', array_keys(self::MISSION_EVENTS))
            ->latest('occurred_at')
            ->limit(40)
            ->get()
            ->filter(fn (MissionEvent $event): bool => ($mission = $missions->get($event->mission_id)) !== null
                && $this->missionVisibility->canViewMission($mission, $actor))
            ->map(function (MissionEvent $event) use ($missions): array {
                /** @var Mission $mission */
                $mission = $missions->get($event->mission_id);
                $meta = self::MISSION_EVENTS[$event->event];

                return [
                    'key' => 'mission:'.$mission->id.':'.$event->id,
                    'kind_label' => 'Mission',
                    'event_label' => $meta['label'],
                    'priority' => $meta['priority'],
                    'title' => $mission->title,
                    'summary' => Str::limit(trim((string) $mission->description), 180),
                    'action_label' => 'Voir la Mission',
                    'action_url' => route('missions.show', $mission),
                    'occurred_at' => $event->occurred_at,
                ];
            });
    }

    private function transmissionItems(ZumraGroup $group, string $actor): Collection
    {
        $transmissions = Transmission::query()
            ->where('context_type', Transmission::CONTEXT_ZUMRA)
            ->where('context_reference', $group->id)
            ->get()
            ->keyBy('id');

        if ($transmissions->isEmpty()) {
            return collect();
        }

        return TransmissionEvent::query()
            ->whereIn('transmission_id', $transmissions->keys())
            ->whereIn('event', array_keys(self::TRANSMISSION_EVENTS))
            ->latest('occurred_at')
            ->limit(40)
            ->get()
            ->filter(fn (TransmissionEvent $event): bool => ($transmission = $transmissions->get($event->transmission_id)) !== null
                && $this->transmissionVisibility->canView($transmission, $actor))
            ->map(function (TransmissionEvent $event) use ($transmissions): array {
                /** @var Transmission $transmission */
                $transmission = $transmissions->get($event->transmission_id);
                $meta = self::TRANSMISSION_EVENTS[$event->event];

                return [
                    'key' => 'transmission:'.$transmission->id.':'.$event->id,
                    'kind_label' => 'Formation',
                    'event_label' => $meta['label'],
                    'priority' => $meta['priority'],
                    'title' => $transmission->capability_label,
                    'summary' => Str::limit(trim((string) $transmission->learning_objective), 180),
                    'action_label' => 'Voir la Transmission',
                    'action_url' => route('transmissions.show', $transmission),
                    'occurred_at' => $event->occurred_at,
                ];
            });
    }

    private function proofItems(ZumraGroup $group, string $actor): Collection
    {
        $proofs = Proof::query()
            ->where('origin_type', Proof::ORIGIN_ZUMRA)
            ->where('origin_reference', $group->id)
            ->whereNull('archived_at')
            ->get()
            ->keyBy('id');

        if ($proofs->isEmpty()) {
            return collect();
        }

        return ProofEvent::query()
            ->whereIn('proof_id', $proofs->keys())
            ->whereIn('event', array_keys(self::PROOF_EVENTS))
            ->latest('occurred_at')
            ->limit(40)
            ->get()
            ->filter(fn (ProofEvent $event): bool => ($proof = $proofs->get($event->proof_id)) !== null
                && $this->proofVisibility->canView($proof, $actor))
            ->map(function (ProofEvent $event) use ($proofs): array {
                /** @var Proof $proof */
                $proof = $proofs->get($event->proof_id);
                $meta = self::PROOF_EVENTS[$event->event];

                return [
                    'key' => 'proof:'.$proof->id.':'.$event->id,
                    'kind_label' => 'Preuve',
                    'event_label' => $meta['label'],
                    'priority' => $meta['priority'],
                    'title' => $proof->title,
                    'summary' => Str::limit(trim((string) $proof->description), 180),
                    'action_label' => 'Voir la preuve',
                    'action_url' => route('proofs.show', $proof),
                    'occurred_at' => $event->occurred_at,
                ];
            });
    }

    private function communityEventItems(ZumraGroup $group, string $actor): Collection
    {
        return $this->communityEvents->forZumraGroup($group, $actor)
            ->map(function (CommunityEvent $event): array {
                [$label, $priority, $occurredAt] = match ($event->status) {
                    CommunityEvent::STATUS_COMPLETED => ['Événement tenu', 150, $event->completed_at ?? $event->updated_at],
                    CommunityEvent::STATUS_CANCELLED => ['Événement annulé', 100, $event->cancelled_at ?? $event->updated_at],
                    default => ['Événement programmé', 200, $event->created_at],
                };

                return [
                    'key' => 'community-event:'.$event->id,
                    'kind_label' => 'Événement',
                    'event_label' => $label,
                    'priority' => $priority,
                    'title' => $event->title,
                    'summary' => Str::limit(trim((string) $event->description), 180),
                    'context' => $event->scheduled_at?->translatedFormat('j M Y · H:i'),
                    'action_label' => 'Voir l’événement',
                    'action_url' => route('community-events.show', $event),
                    'occurred_at' => $occurredAt,
                ];
            });
    }

    private function groupSummary(ZumraGroupEvent $event): ?string
    {
        $role = (string) ($event->context['role'] ?? '');

        return $role !== '' ? 'Responsabilité : '.str_replace('_', ' ', mb_strtolower($role)) : null;
    }
}
