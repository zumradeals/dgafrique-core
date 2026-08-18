<?php

declare(strict_types=1);

namespace App\Application\Notifications;

use App\Models\NotificationRead;
use Illuminate\Support\Collection;

/**
 * Notification : « voici ce qui vous concerne ». Module source : « voici ce qui est vrai et ce
 * que vous pouvez faire » (fiche §16.2/§13). Ce service ne décide jamais rien à la place d'un
 * module — il n'annonce que des items déjà légitimement calculés ailleurs.
 */
final class NotificationService
{
    public function __construct(
        private readonly NotificationSourceRegistry $registry,
        private readonly NotificationConfiguration $configuration,
    ) {}

    /** @return array{a_traiter: Collection, recentes: Collection} */
    public function sections(string $actor): array
    {
        $config = $this->configuration->get();

        $actionable = $this->registry->actionable($actor)->sortByDesc(fn (array $i) => $i['occurred_at'])->values();
        $fyi = $this->registry->fyi($actor, $config)->sortByDesc(fn (array $i) => $i['occurred_at'])->values();

        $reads = $this->readState($actor, $actionable->concat($fyi));

        $actionable = $actionable
            ->map(fn (array $item): array => $this->withReadState($item, $reads))
            ->take((int) $config['max_actionable'])
            ->values();

        $fyi = $fyi
            ->map(fn (array $item): array => $this->withReadState($item, $reads))
            ->take((int) $config['max_recent'])
            ->values();

        return ['a_traiter' => $actionable, 'recentes' => $fyi];
    }

    /**
     * Marque les items FYI actuellement affichés comme lus (même mécanique que
     * `MessageParticipant.last_read_at` : la consultation de la page vaut lecture, aucun
     * bouton dédié). Ne modifie jamais l'objet métier source (fiche §13).
     */
    public function markFyiSeen(string $actor, Collection $fyiItems): void
    {
        foreach ($fyiItems as $item) {
            if ($item['read']) {
                continue;
            }

            NotificationRead::query()->updateOrCreate(
                ['core_identity_reference' => $actor, 'source_type' => $item['source_type'], 'source_reference' => $item['source_reference']],
                ['read_at' => now()],
            );
        }
    }

    private function readState(string $actor, Collection $items): Collection
    {
        if ($items->isEmpty()) {
            return collect();
        }

        return NotificationRead::query()
            ->where('core_identity_reference', $actor)
            ->whereIn('source_type', $items->pluck('source_type')->unique())
            ->whereNotNull('read_at')
            ->get()
            ->keyBy(fn (NotificationRead $r): string => $r->source_type.'|'.$r->source_reference);
    }

    private function withReadState(array $item, Collection $reads): array
    {
        $item['read'] = $reads->has($item['source_type'].'|'.$item['source_reference']);

        return $item;
    }
}
