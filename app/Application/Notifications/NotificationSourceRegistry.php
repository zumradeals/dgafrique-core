<?php

declare(strict_types=1);

namespace App\Application\Notifications;

use App\Application\Missions\MissionService;
use App\Application\Missions\MissionVisibilityService;
use App\Application\Needs\NeedService;
use App\Application\Projects\ProjectService;
use App\Application\Proof\ProofService;
use App\Application\Proof\ProofVisibilityService;
use App\Application\Transmission\TransmissionService;
use App\Application\Transmission\TransmissionVisibilityService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\MissionEvent;
use App\Models\Need;
use App\Models\Project;
use App\Models\Proof;
use App\Models\ProofEvent;
use App\Models\Transmission;
use App\Models\TransmissionEvent;
use App\Models\TransmissionParticipant;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraGroupRole;
use Illuminate\Support\Collection;

/**
 * Un adaptateur léger par domaine (fiche §6/§16.2), même patron qu'ActivityFeedService : jamais
 * un subscriber générique unique. ACTIONNABLE enveloppe les sections déjà calculées par chaque
 * module (`myMissionsSections()`/`myTransmissionsSections()`/`mySections()`) plutôt que de les
 * recalculer. FYI reste un sous-ensemble curé d'événements à destinataire clairement
 * déterminable — jamais un événement sans destinataire humain identifiable avec certitude.
 */
final class NotificationSourceRegistry
{
    public function __construct(
        private readonly MissionService $missions,
        private readonly MissionVisibilityService $missionVisibility,
        private readonly TransmissionService $transmissions,
        private readonly TransmissionVisibilityService $transmissionVisibility,
        private readonly ProofService $proofs,
        private readonly ProofVisibilityService $proofVisibility,
        private readonly NeedService $needs,
        private readonly ProjectService $projects,
        private readonly ZumraGroupService $zumraGroups,
    ) {}

    public function actionable(string $actor): Collection
    {
        return collect()
            ->concat($this->missionActionable($actor))
            ->concat($this->transmissionActionable($actor))
            ->concat($this->proofActionable($actor))
            ->concat($this->needActionable($actor))
            ->concat($this->projectActionable($actor))
            ->concat($this->zumraActionable($actor));
    }

    public function fyi(string $actor, array $config): Collection
    {
        $since = now()->subDays((int) $config['lookback_days']);
        $limit = (int) $config['scan_limit'];

        $items = collect();
        if ($config['mission_fyi_enabled']) {
            $items = $items->concat($this->missionFyi($actor, $since, $limit));
        }
        if ($config['transmission_fyi_enabled']) {
            $items = $items->concat($this->transmissionFyi($actor, $since, $limit));
        }
        if ($config['proof_fyi_enabled']) {
            $items = $items->concat($this->proofFyi($actor, $since, $limit));
        }

        return $items;
    }

    // ===== ACTIONNABLE =====

    private function missionActionable(string $actor): Collection
    {
        $sections = $this->missions->myMissionsSections($actor);
        $items = collect();

        foreach ($sections['invitations'] as $assignment) {
            /** @var MissionAssignment $assignment */
            $items->push($this->item(
                'MISSION',
                $assignment->id.':'.$assignment->status,
                'Invitation — Mission « '.$assignment->mission->title.' »',
                'Accepter ou décliner reste entièrement votre choix.',
                'missions.show',
                $assignment->mission,
                $assignment->invited_at ?? $assignment->updated_at,
            ));
        }

        foreach ($sections['a_decider'] as $mission) {
            /** @var Mission $mission */
            $items->push($this->item(
                'MISSION',
                $mission->id.':'.$mission->status,
                'À décider — Mission « '.$mission->title.' »',
                'Elle a été proposée dans un contexte que vous pouvez officialiser.',
                'missions.show',
                $mission,
                $mission->proposed_at ?? $mission->updated_at,
            ));
        }

        foreach ($sections['bloquees'] as $mission) {
            /** @var Mission $mission */
            $items->push($this->item(
                'MISSION',
                $mission->id.':'.$mission->status,
                'Bloquée — Mission « '.$mission->title.' »',
                'Un blocage actif empêche sa poursuite.',
                'missions.show',
                $mission,
                $mission->updated_at,
            ));
        }

        foreach ($sections['a_valider'] as $mission) {
            /** @var Mission $mission */
            $items->push($this->item(
                'MISSION',
                $mission->id.':'.$mission->status,
                'À valider — résultat soumis pour « '.$mission->title.' »',
                'Soumis ne veut pas dire validé : votre décision explicite reste nécessaire.',
                'missions.show',
                $mission,
                $mission->submitted_at ?? $mission->updated_at,
            ));
        }

        return $items;
    }

    private function transmissionActionable(string $actor): Collection
    {
        $sections = $this->transmissions->myTransmissionsSections($actor);
        $items = collect();

        foreach ($sections['propositions_recues'] as $participant) {
            /** @var TransmissionParticipant $participant */
            $items->push($this->item(
                'TRANSMISSION',
                $participant->id.':'.$participant->status,
                'Invitation — Transmission « '.$participant->transmission->capability_label.' »',
                'Accepter ou décliner reste entièrement votre choix.',
                'transmissions.show',
                $participant->transmission,
                $participant->updated_at,
            ));
        }

        return $items;
    }

    private function proofActionable(string $actor): Collection
    {
        $sections = $this->proofs->mySections($actor);
        $items = collect();

        foreach ($sections['temoignages_demandes'] as $witness) {
            $items->push($this->item(
                'PROOF',
                $witness->id.':'.$witness->status,
                'Témoignage demandé — « '.$witness->proof->title.' »',
                'Confirmer un témoignage reste entièrement votre choix — cela ne certifie rien.',
                'proofs.show',
                $witness->proof,
                $witness->updated_at,
            ));
        }

        foreach ($sections['a_reconnaitre'] as $proof) {
            /** @var Proof $proof */
            $items->push($this->item(
                'PROOF',
                $proof->id.':'.$proof->status,
                'À reconnaître — « '.$proof->title.' »',
                'Reconnaître ne garantit jamais la véracité d’une preuve, mais confirme son inscription dans ce contexte.',
                'proofs.show',
                $proof,
                $proof->updated_at,
            ));
        }

        return $items;
    }

    /** Besoins portés par une ZUMRA que l'acteur dirige, en attente de publication. */
    private function needActionable(string $actor): Collection
    {
        $ledGroupIds = $this->ledGroupIds($actor);
        if ($ledGroupIds->isEmpty()) {
            return collect();
        }

        return Need::query()
            ->where('owner_type', Need::OWNER_GROUP)
            ->whereIn('owner_reference', $ledGroupIds)
            ->where('status', Need::STATUS_PROPOSED)
            ->get()
            ->filter(fn (Need $need): bool => $this->needs->canDecide($need, $actor))
            ->map(fn (Need $need) => $this->item(
                'NEED',
                $need->id.':'.$need->status,
                'À décider — Besoin « '.$need->title.' » proposé à votre ZUMRA',
                'Ce besoin attend une décision de publication.',
                'needs.show',
                $need,
                $need->created_at,
            ));
    }

    /** Projets portés par une ZUMRA que l'acteur dirige, en attente d'adoption. */
    private function projectActionable(string $actor): Collection
    {
        $ledGroupIds = $this->ledGroupIds($actor);
        if ($ledGroupIds->isEmpty()) {
            return collect();
        }

        return Project::query()
            ->where('owner_type', Project::OWNER_GROUP)
            ->whereIn('owner_reference', $ledGroupIds)
            ->where('status', Project::STATUS_PROPOSED)
            ->get()
            ->filter(fn (Project $project): bool => $this->projects->canDecide($project, $actor))
            ->map(fn (Project $project) => $this->item(
                'PROJECT',
                $project->id.':'.$project->status,
                'À décider — Projet « '.$project->name.' » proposé à votre ZUMRA',
                'Ce projet attend une décision d’adoption.',
                'projects.show',
                $project,
                $project->created_at,
            ));
    }

    private function zumraActionable(string $actor): Collection
    {
        $items = collect();
        $ledGroupIds = $this->ledGroupIds($actor);

        if ($ledGroupIds->isNotEmpty()) {
            $requests = ZumraGroupMembership::query()
                ->whereIn('zumra_group_id', $ledGroupIds)
                ->where('status', ZumraGroupMembership::STATUS_REQUESTED)
                ->get();

            $groups = ZumraGroup::query()->whereIn('id', $requests->pluck('zumra_group_id')->unique())->get()->keyBy('id');

            foreach ($requests as $membership) {
                $group = $groups->get($membership->zumra_group_id);
                if (! $group) {
                    continue;
                }
                $items->push($this->item(
                    'ZUMRA',
                    $membership->id.':'.$membership->status,
                    'À décider — demande d’adhésion à « '.$group->name.' »',
                    'Une personne souhaite rejoindre cette ZUMRA que vous dirigez.',
                    'zumra.groups.show',
                    $group,
                    $membership->requested_at ?? $membership->updated_at,
                ));
            }
        }

        $invitations = ZumraGroupMembership::query()
            ->where('core_identity_reference', $actor)
            ->where('status', ZumraGroupMembership::STATUS_INVITED)
            ->get();

        if ($invitations->isNotEmpty()) {
            $groups = ZumraGroup::query()->whereIn('id', $invitations->pluck('zumra_group_id')->unique())->get()->keyBy('id');

            foreach ($invitations as $membership) {
                $group = $groups->get($membership->zumra_group_id);
                if (! $group) {
                    continue;
                }
                $items->push($this->item(
                    'ZUMRA',
                    $membership->id.':'.$membership->status,
                    'Invitation — ZUMRA « '.$group->name.' »',
                    'Rejoindre reste entièrement votre choix.',
                    'zumra.groups.show',
                    $group,
                    $membership->invited_at ?? $membership->updated_at,
                ));
            }
        }

        return $items;
    }

    // ===== FYI =====

    private const MISSION_LEVEL_FYI = [
        'MISSION_COMPLETED' => 'La Mission « %s » est terminée.',
        'MISSION_CANCELLED' => 'La Mission « %s » a été annulée.',
        'MISSION_BLOCKED' => 'La Mission « %s » est bloquée.',
        'MISSION_CORRECTION_REQUESTED' => 'Une correction est demandée sur le résultat soumis pour « %s ».',
    ];

    private const MISSION_CREATOR_ONLY_FYI = [
        'MISSION_REJECTED' => 'Votre proposition de Mission « %s » a été rejetée.',
        'MISSION_CHANGES_REQUESTED' => 'Une correction est demandée sur votre proposition de Mission « %s ».',
        'ASSIGNMENT_ACCEPTED' => 'Une personne a accepté sa participation à la Mission « %s ».',
        'ASSIGNMENT_DECLINED' => 'Une personne a décliné sa participation à la Mission « %s ».',
    ];

    private function missionFyi(string $actor, \DateTimeInterface $since, int $limit): Collection
    {
        $eventNames = array_merge(array_keys(self::MISSION_LEVEL_FYI), array_keys(self::MISSION_CREATOR_ONLY_FYI));

        $events = MissionEvent::query()
            ->whereIn('event', $eventNames)
            ->where('occurred_at', '>=', $since)
            ->latest('occurred_at')
            ->limit($limit)
            ->get();

        if ($events->isEmpty()) {
            return collect();
        }

        $missions = Mission::query()->whereIn('id', $events->pluck('mission_id')->unique())->get()->keyBy('id');
        $items = collect();

        foreach ($events as $event) {
            /** @var Mission|null $mission */
            $mission = $missions->get($event->mission_id);
            if (! $mission || hash_equals($event->actor_core_reference, $actor)) {
                continue;
            }

            $recipients = array_key_exists($event->event, self::MISSION_CREATOR_ONLY_FYI)
                ? [$mission->created_by_core_reference]
                : $this->missionRecipients($mission);

            if (! in_array($actor, $recipients, true)) {
                continue;
            }
            if (! $this->missionVisibility->canViewMission($mission, $actor)) {
                continue;
            }

            $template = self::MISSION_LEVEL_FYI[$event->event] ?? self::MISSION_CREATOR_ONLY_FYI[$event->event] ?? null;
            if ($template === null) {
                continue;
            }

            $items->push($this->item(
                'MISSION',
                $event->id,
                sprintf($template, $mission->title),
                null,
                'missions.show',
                $mission,
                $event->occurred_at,
            ));
        }

        return $items;
    }

    /** @return list<string> */
    private function missionRecipients(Mission $mission): array
    {
        $executors = MissionAssignment::query()
            ->where('mission_id', $mission->id)
            ->where('status', MissionAssignment::STATUS_ACCEPTED)
            ->whereIn('role', MissionAssignment::EXECUTION_ROLES)
            ->pluck('core_identity_reference')
            ->all();

        return array_unique(array_merge([$mission->created_by_core_reference], $executors));
    }

    private const TRANSMISSION_FYI = [
        'TRANSMISSION_COMPLETED' => 'La Transmission « %s » est terminée.',
        'TRANSMISSION_CANCELLED' => 'La Transmission « %s » a été annulée.',
        'TRANSMISSION_PARTICIPANT_ACCEPTED' => 'Une personne a accepté de participer à « %s ».',
        'TRANSMISSION_PARTICIPANT_DECLINED' => 'Une personne a décliné sa participation à « %s ».',
    ];

    private function transmissionFyi(string $actor, \DateTimeInterface $since, int $limit): Collection
    {
        $events = TransmissionEvent::query()
            ->whereIn('event', array_keys(self::TRANSMISSION_FYI))
            ->where('occurred_at', '>=', $since)
            ->latest('occurred_at')
            ->limit($limit)
            ->get();

        if ($events->isEmpty()) {
            return collect();
        }

        $transmissions = Transmission::query()->whereIn('id', $events->pluck('transmission_id')->unique())->get()->keyBy('id');
        $items = collect();

        foreach ($events as $event) {
            /** @var Transmission|null $transmission */
            $transmission = $transmissions->get($event->transmission_id);
            if (! $transmission || hash_equals($event->actor_core_reference, $actor)) {
                continue;
            }

            $participants = TransmissionParticipant::query()
                ->where('transmission_id', $transmission->id)
                ->where('status', TransmissionParticipant::STATUS_ACCEPTED)
                ->pluck('core_identity_reference')
                ->all();

            if (! in_array($actor, $participants, true)) {
                continue;
            }
            if (! $this->transmissionVisibility->canView($transmission, $actor)) {
                continue;
            }

            $items->push($this->item(
                'TRANSMISSION',
                $event->id,
                sprintf(self::TRANSMISSION_FYI[$event->event], $transmission->capability_label),
                null,
                'transmissions.show',
                $transmission,
                $event->occurred_at,
            ));
        }

        return $items;
    }

    private const PROOF_FYI = [
        'PROOF_ACKNOWLEDGED' => 'Votre preuve « %s » a été reconnue par le contexte.',
        'PROOF_DISPUTED' => 'Votre preuve « %s » a été contestée.',
    ];

    private function proofFyi(string $actor, \DateTimeInterface $since, int $limit): Collection
    {
        $events = ProofEvent::query()
            ->whereIn('event', array_keys(self::PROOF_FYI))
            ->where('occurred_at', '>=', $since)
            ->latest('occurred_at')
            ->limit($limit)
            ->get();

        if ($events->isEmpty()) {
            return collect();
        }

        $proofs = Proof::query()->whereIn('id', $events->pluck('proof_id')->unique())->get()->keyBy('id');
        $items = collect();

        foreach ($events as $event) {
            /** @var Proof|null $proof */
            $proof = $proofs->get($event->proof_id);
            if (! $proof || ! hash_equals($proof->submitted_by_core_reference, $actor) || hash_equals($event->actor_core_reference, $actor)) {
                continue;
            }
            if (! $this->proofVisibility->canView($proof, $actor)) {
                continue;
            }

            $items->push($this->item(
                'PROOF',
                $event->id,
                sprintf(self::PROOF_FYI[$event->event], $proof->title),
                null,
                'proofs.show',
                $proof,
                $event->occurred_at,
            ));
        }

        return $items;
    }

    // ===== Commun =====

    private function ledGroupIds(string $actor): Collection
    {
        return ZumraGroupRole::query()
            ->where('core_identity_reference', $actor)
            ->where('status', ZumraGroupRole::STATUS_ACCEPTED)
            ->pluck('zumra_group_id');
    }

    /** @param Mission|Transmission|Proof|Need|Project|ZumraGroup $subject */
    private function item(
        string $sourceType,
        string $sourceReference,
        string $title,
        ?string $body,
        string $routeName,
        $subject,
        \DateTimeInterface $occurredAt,
    ): array {
        return [
            'source_type' => $sourceType,
            'source_reference' => $sourceReference,
            'title' => $title,
            'body' => $body,
            'action_url' => route($routeName, $subject),
            'occurred_at' => $occurredAt,
        ];
    }
}
