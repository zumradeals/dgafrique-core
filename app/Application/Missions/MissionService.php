<?php

declare(strict_types=1);

namespace App\Application\Missions;

use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\MissionCapabilityRequirement;
use App\Models\MissionChecklistItem;
use App\Models\MissionResourceRequirement;
use App\Models\MissionSubmission;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Couche de lecture et de petites mutations non sensibles (checklist, capacités et
 * ressources requises). Les transitions de statut restent exclusivement dans
 * MissionWorkflow / MissionAssignmentService / MissionBlockerService / MissionSubmissionService.
 */
final class MissionService
{
    private const AUTHORITY_POOL_LIMIT = 200;

    public function __construct(
        private readonly MissionContextRegistry $registry,
        private readonly MissionVisibilityService $visibility,
    ) {}

    public function find(string $publicReference, string $actor): Mission
    {
        $mission = Mission::query()->where('public_reference', $publicReference)->firstOrFail();
        abort_unless($this->visibility->canViewMission($mission, $actor), 404);

        return $mission;
    }

    /** Missions visibles d'un contexte donné (fiches Projet/ZUMRA/Besoin). */
    public function forContext(string $contextType, string $contextReference, string $actor): Collection
    {
        $adapter = $this->registry->for($contextType);
        $context = $adapter->resolve($contextReference);
        abort_unless($adapter->canView($context, $actor), 404);

        return Mission::query()
            ->where('context_type', $contextType)
            ->where('context_reference', $contextReference)
            ->whereNull('parent_mission_id')
            ->latest('created_at')
            ->get()
            ->filter(fn (Mission $mission): bool => $this->visibility->canViewMission($mission, $actor))
            ->values();
    }

    public function children(Mission $mission, string $actor): Collection
    {
        return $mission->children()
            ->latest('created_at')
            ->get()
            ->filter(fn (Mission $child): bool => $this->visibility->canViewMission($child, $actor))
            ->values();
    }

    public function addChecklistItem(Mission $mission, string $actor, string $label, bool $isRequired): MissionChecklistItem
    {
        abort_if(trim($label) === '', 422, 'Le libellé est requis.');
        $this->assertOfficializer($mission, $actor);

        $position = (int) ($mission->checklistItems()->max('position') ?? 0) + 1;

        return $mission->checklistItems()->create([
            'label' => trim($label),
            'position' => $position,
            'is_required' => $isRequired,
        ]);
    }

    /** 100 % de checklist complétée ne clôture jamais automatiquement la Mission. */
    public function setChecklistItemCompletion(Mission $mission, string $actor, MissionChecklistItem $item, bool $completed): MissionChecklistItem
    {
        abort_unless($item->mission_id === $mission->id, 404);
        $this->assertOfficializerOrAcceptedExecutor($mission, $actor);

        $item->update($completed
            ? ['completed_by_core_reference' => $actor, 'completed_at' => now()]
            : ['completed_by_core_reference' => null, 'completed_at' => null]);

        return $item;
    }

    public function addCapabilityRequirement(Mission $mission, string $actor, string $label, string $requirementLevel, ?int $quantity, ?string $context): MissionCapabilityRequirement
    {
        abort_if(trim($label) === '', 422, 'Le libellé de la capacité est requis.');
        $this->assertOfficializer($mission, $actor);

        return $mission->capabilityRequirements()->create([
            'label' => trim($label),
            'normalized_label' => mb_substr(Str::of($label)->ascii()->lower()->squish()->toString(), 0, 200),
            'requirement_level' => $requirementLevel,
            'quantity' => $quantity,
            'context' => $context,
        ]);
    }

    public function addResourceRequirement(Mission $mission, string $actor, string $type, string $label, ?float $quantity, ?string $unit, bool $isRequired, ?string $context): MissionResourceRequirement
    {
        abort_if(trim($label) === '', 422, 'Le libellé de la ressource est requis.');
        $this->assertOfficializer($mission, $actor);

        return $mission->resourceRequirements()->create([
            'type' => $type,
            'label' => trim($label),
            'quantity' => $quantity,
            'unit' => $unit,
            'is_required' => $isRequired,
            'context' => $context,
        ]);
    }

    /** Sections « Mes Missions » (CAP-069 §16/17) — jamais un mur de statistiques. */
    public function myMissionsSections(string $actor): array
    {
        $myAssignments = MissionAssignment::query()
            ->where('core_identity_reference', $actor)
            ->with('mission')
            ->latest('updated_at')
            ->limit(self::AUTHORITY_POOL_LIMIT)
            ->get();

        $myProposals = Mission::query()
            ->where('created_by_core_reference', $actor)
            ->whereIn('status', [Mission::STATUS_DRAFT, Mission::STATUS_PROPOSED, Mission::STATUS_CHANGES_REQUESTED, Mission::STATUS_REJECTED])
            ->latest('created_at')
            ->limit(self::AUTHORITY_POOL_LIMIT)
            ->get();

        $authorityCandidates = Mission::query()
            ->whereIn('status', [Mission::STATUS_PROPOSED, Mission::STATUS_SUBMITTED, Mission::STATUS_BLOCKED])
            ->latest('created_at')
            ->limit(self::AUTHORITY_POOL_LIMIT)
            ->get()
            ->filter(fn (Mission $mission): bool => $this->isOfficializer($mission, $actor))
            ->values();

        $invitations = $myAssignments->filter(fn (MissionAssignment $a): bool => $a->status === MissionAssignment::STATUS_INVITED);
        $offered = $myAssignments->filter(fn (MissionAssignment $a): bool => $a->status === MissionAssignment::STATUS_OFFERED);
        $accepted = $myAssignments->filter(fn (MissionAssignment $a): bool => $a->status === MissionAssignment::STATUS_ACCEPTED);

        $acceptedActive = $accepted->filter(fn (MissionAssignment $a): bool => $a->mission && in_array($a->mission->status, [Mission::STATUS_OPEN, Mission::STATUS_IN_PROGRESS, Mission::STATUS_SUBMITTED], true));
        $acceptedBlocked = $accepted->filter(fn (MissionAssignment $a): bool => $a->mission?->status === Mission::STATUS_BLOCKED);

        $blockedAuthority = $authorityCandidates->filter(fn (Mission $m): bool => $m->status === Mission::STATUS_BLOCKED);
        $blocked = $acceptedBlocked->map(fn (MissionAssignment $a): Mission => $a->mission)
            ->concat($blockedAuthority)
            ->unique('id')
            ->values();

        $toDecide = $authorityCandidates->filter(fn (Mission $m): bool => $m->status === Mission::STATUS_PROPOSED)->values();
        $toValidate = $authorityCandidates->filter(fn (Mission $m): bool => $m->status === Mission::STATUS_SUBMITTED)->values();

        $toSubmit = $accepted
            ->filter(fn (MissionAssignment $a): bool => $a->mission?->status === Mission::STATUS_IN_PROGRESS
                && in_array($a->role, MissionAssignment::EXECUTION_ROLES, true))
            ->map(fn (MissionAssignment $a): Mission => $a->mission)
            ->unique('id')
            ->values();

        $completed = $myAssignments
            ->filter(fn (MissionAssignment $a): bool => $a->mission && in_array($a->mission->status, Mission::TERMINAL_STATUSES, true))
            ->map(fn (MissionAssignment $a): Mission => $a->mission)
            ->concat(Mission::query()->where('created_by_core_reference', $actor)->whereIn('status', Mission::TERMINAL_STATUSES)->latest('created_at')->limit(30)->get())
            ->unique('id')
            ->sortByDesc(fn (Mission $m) => $m->updated_at)
            ->values();

        return [
            'a_decider' => $toDecide,
            'mes_propositions' => $myProposals,
            'invitations' => $invitations->values(),
            'je_me_suis_propose' => $offered->values(),
            'mes_engagements' => $acceptedActive->map(fn (MissionAssignment $a): Mission => $a->mission)->unique('id')->values(),
            'bloquees' => $blocked,
            'a_soumettre' => $toSubmit,
            'a_valider' => $toValidate,
            'terminees' => $completed,
        ];
    }

    /**
     * Une seule prochaine action Mission, explicable, pour Mon espace (CAP-069 §17). Jamais un
     * mur de statistiques ni un score : uniquement ce qui réclame réellement une décision ou une
     * action de ce membre, dans un ordre de priorité fixe. Retourne null si rien ne le concerne.
     */
    public function nextAction(string $actor): ?array
    {
        $sections = $this->myMissionsSections($actor);

        if ($sections['a_decider']->isNotEmpty()) {
            $mission = $sections['a_decider']->first();

            return [
                'heading' => 'La Mission « '.$mission->title.' » attend votre décision.',
                'body' => 'Elle a été proposée dans un contexte que vous pouvez officialiser.',
                'primary' => ['label' => 'Décider', 'href' => route('missions.show', $mission)],
            ];
        }

        if ($sections['invitations']->isNotEmpty()) {
            $mission = $sections['invitations']->first()->mission;

            return [
                'heading' => 'Vous êtes invité·e sur la Mission « '.$mission->title.' ».',
                'body' => 'Accepter ou décliner reste entièrement votre choix.',
                'primary' => ['label' => 'Répondre à l’invitation', 'href' => route('missions.show', $mission)],
            ];
        }

        if ($sections['bloquees']->isNotEmpty()) {
            $mission = $sections['bloquees']->first();

            return [
                'heading' => 'La Mission « '.$mission->title.' » est bloquée.',
                'body' => 'Un ou plusieurs blocages actifs empêchent sa poursuite.',
                'primary' => ['label' => 'Voir le blocage', 'href' => route('missions.show', $mission)],
            ];
        }

        if ($sections['a_valider']->isNotEmpty()) {
            $mission = $sections['a_valider']->first();

            return [
                'heading' => 'Un résultat soumis pour « '.$mission->title.' » attend votre validation.',
                'body' => 'Soumis ne veut pas dire validé : votre décision explicite reste nécessaire.',
                'primary' => ['label' => 'Examiner le résultat', 'href' => route('missions.show', $mission)],
            ];
        }

        $correction = $sections['mes_engagements']->first(function (Mission $mission): bool {
            $latest = $mission->submissions()->latest('version')->first();

            return $latest !== null && $latest->decision === MissionSubmission::DECISION_CHANGES_REQUESTED;
        });
        if ($correction) {
            return [
                'heading' => 'Une correction est demandée sur « '.$correction->title.' ».',
                'body' => 'L’autorité de ce contexte a renvoyé le résultat soumis avec une note.',
                'primary' => ['label' => 'Voir la note de correction', 'href' => route('missions.show', $correction)],
            ];
        }

        $approaching = $sections['mes_engagements']
            ->filter(fn (Mission $mission): bool => $mission->due_at !== null && $mission->due_at->isFuture() && $mission->due_at->diffInDays(now()) <= 3)
            ->sortBy(fn (Mission $mission) => $mission->due_at)
            ->first();
        if ($approaching) {
            return [
                'heading' => 'L’échéance de « '.$approaching->title.' » approche.',
                'body' => 'Prévue pour '.$approaching->due_at->locale('fr')->isoFormat('D MMMM').'.',
                'primary' => ['label' => 'Voir la Mission', 'href' => route('missions.show', $approaching)],
            ];
        }

        return null;
    }

    private function isOfficializer(Mission $mission, string $actor): bool
    {
        if (! $this->registry->has($mission->context_type)) {
            return false;
        }
        $adapter = $this->registry->for($mission->context_type);
        $context = $adapter->resolve($mission->context_reference);

        return $adapter->canOfficialize($context, $actor);
    }

    private function assertOfficializer(Mission $mission, string $actor): void
    {
        abort_unless($this->isOfficializer($mission, $actor), 403);
    }

    private function assertOfficializerOrAcceptedExecutor(Mission $mission, string $actor): void
    {
        if ($this->isOfficializer($mission, $actor)) {
            return;
        }

        abort_unless(
            $mission->assignments()
                ->where('core_identity_reference', $actor)
                ->where('status', MissionAssignment::STATUS_ACCEPTED)
                ->exists(),
            403
        );
    }
}
