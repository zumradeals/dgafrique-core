<?php

declare(strict_types=1);

namespace App\Application\Administration;

use App\Models\ModerationDecision;
use App\Models\NeedEvent;
use App\Models\ProjectEvent;
use App\Models\ZumraGroupEvent;
use App\Models\ZumraProgramMembershipEvent;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * ADMIN-CONTROL-002 — Journal V1 : agrège les journaux métier DÉJÀ existants
 * (ZumraGroupEvent, ProjectEvent, NeedEvent, ZumraProgramMembershipEvent, ModerationDecision),
 * jamais une nouvelle table d'audit générique. Chaque source garde son vocabulaire d'origine —
 * ce service ne fait que normaliser la présentation (type, libellé, acteur, date) pour un affichage
 * unique et triable chronologiquement. Lecture seule, jamais un écrivain.
 */
final class AdminJournalAggregator
{
    public const TYPES = [
        'ZUMRA' => 'ZUMRA',
        'PROJECT' => 'Projet',
        'NEED' => 'Besoin',
        'MEMBERSHIP' => 'Adhésion',
        'MODERATION' => 'Modération',
    ];

    /** @return Collection<int, array<string, mixed>> */
    public function recent(int $limit = 12): Collection
    {
        return $this->merged(perSourceLimit: $limit)->take($limit)->values();
    }

    public function paginated(?string $type, int $page, int $perPage = 30): LengthAwarePaginator
    {
        $items = $type !== null && array_key_exists($type, self::TYPES)
            ? $this->fromType($type, 400)
            : $this->merged(perSourceLimit: 120);

        $items = $items->values();
        $slice = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator($slice, $items->count(), $perPage, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function merged(int $perSourceLimit): Collection
    {
        return collect(array_keys(self::TYPES))
            ->flatMap(fn (string $type): Collection => $this->fromType($type, $perSourceLimit))
            ->sortByDesc('occurred_at')
            ->values();
    }

    /** @return Collection<int, array<string, mixed>> */
    private function fromType(string $type, int $limit): Collection
    {
        return match ($type) {
            'ZUMRA' => ZumraGroupEvent::query()->latest('occurred_at')->limit($limit)->get()
                ->map(fn (ZumraGroupEvent $e): array => $this->row('ZUMRA', $e->event, $e->actor_core_reference, $e->occurred_at, $e->context)),
            'PROJECT' => ProjectEvent::query()->latest('occurred_at')->limit($limit)->get()
                ->map(fn (ProjectEvent $e): array => $this->row('PROJECT', $e->event, $e->actor_core_reference, $e->occurred_at, $e->context)),
            'NEED' => NeedEvent::query()->latest('occurred_at')->limit($limit)->get()
                ->map(fn (NeedEvent $e): array => $this->row('NEED', $e->event, $e->actor_core_reference, $e->occurred_at, $e->context)),
            'MEMBERSHIP' => ZumraProgramMembershipEvent::query()->latest('occurred_at')->limit($limit)->get()
                ->map(fn (ZumraProgramMembershipEvent $e): array => $this->row('MEMBERSHIP', $e->event, $e->actor_core_reference, $e->occurred_at, $e->context)),
            'MODERATION' => ModerationDecision::query()->latest('created_at')->limit($limit)->get()
                ->map(fn (ModerationDecision $d): array => $this->row('MODERATION', $d->action_type, $d->decided_by_core_reference, $d->created_at, ['target_type' => $d->target_type, 'status' => $d->status])),
            default => collect(),
        };
    }

    /** @param  array<string, mixed>  $context */
    private function row(string $type, string $label, string $actor, mixed $occurredAt, array $context): array
    {
        return [
            'type' => $type,
            'type_label' => self::TYPES[$type],
            'label' => $label,
            'actor' => $actor,
            'occurred_at' => $occurredAt,
            'context' => $context,
        ];
    }
}
