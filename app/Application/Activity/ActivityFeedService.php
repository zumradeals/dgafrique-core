<?php

declare(strict_types=1);

namespace App\Application\Activity;

use App\Application\Missions\MissionContextRegistry;
use App\Application\Missions\MissionVisibilityService;
use App\Application\Needs\NeedService;
use App\Application\Projects\ProjectMaturityService;
use App\Application\Projects\ProjectService;
use App\Application\Proof\ProofVisibilityService;
use App\Application\Transmission\TransmissionVisibilityService;
use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\MissionEvent;
use App\Models\Need;
use App\Models\NeedEvent;
use App\Models\Project;
use App\Models\ProjectEvent;
use App\Models\Proof;
use App\Models\ProofEvent;
use App\Models\Transmission;
use App\Models\TransmissionEvent;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupEvent;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraGroupRole;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class ActivityFeedService
{
    public const FILTERS = [
        'ALL' => 'Tout',
        'NEEDS' => 'Besoins',
        'PROJECTS' => 'Projets',
        'ZUMRA' => 'ZUMRA',
    ];

    private const PER_PAGE = 12;
    private const SOURCE_LIMIT = 80;

    private const NEED_EVENTS = [
        'NEED_PUBLISHED' => ['priority' => 300, 'label' => 'Besoin ouvert'],
        'NEED_STARTED' => ['priority' => 260, 'label' => 'Besoin en action'],
        'NEED_RESOLVED' => ['priority' => 120, 'label' => 'Besoin résolu'],
    ];

    private const PROJECT_EVENTS = [
        'PROJECT_PROPOSED' => ['priority' => 250, 'label' => 'Nouveau projet'],
        'PROJECT_ADOPTED' => ['priority' => 240, 'label' => 'Projet adopté'],
        'PROJECT_IN_PROGRESS' => ['priority' => 230, 'label' => 'Projet en action'],
        'ACCOMPANIMENT_ACTION_RECORDED' => ['priority' => 220, 'label' => 'Accompagnement réalisé'],
        'PROJECT_MATURITY_CHANGED' => ['priority' => 210, 'label' => 'Repère de maturité'],
        'AUTONOMY_PATHWAY_OPENED' => ['priority' => 200, 'label' => 'Préparation à l’autonomie'],
        'ACCOMPANIMENT_ACTIVATED' => ['priority' => 180, 'label' => 'Accompagnement ouvert'],
        'PROJECT_COMPLETED' => ['priority' => 130, 'label' => 'Projet terminé'],
    ];

    private const ZUMRA_EVENTS = [
        'GROUP_PROPOSED' => ['priority' => 190, 'label' => 'Nouvelle ZUMRA'],
        'INVITATION_ACCEPTED' => ['priority' => 160, 'label' => 'ZUMRA en mouvement'],
        'MEMBERSHIP_APPROVED' => ['priority' => 160, 'label' => 'ZUMRA en mouvement'],
    ];

    // CAP-069 : événements Mission éligibles au Fil (v0.4 §15). Pas de bruit pour chaque
    // checklist/édition mineure — seuls ces trois événements de la machine d'états sont projetés.
    private const MISSION_EVENTS = ['MISSION_OFFICIALIZED', 'MISSION_BLOCKED', 'MISSION_COMPLETED'];

    private const MISSION_CONTEXT_FILTER = ['PROJECT' => 'PROJECTS', 'ZUMRA' => 'ZUMRA', 'NEED' => 'NEEDS'];

    // Transmission (CAP-006) : mêmes trois événements-charnière que Missions (fiche §19),
    // repliés dans le filtre de leur contexte porteur. Une Transmission sans contexte
    // rattaché (context_type = null) reste réelle mais n'a pas de bucket de Fil à rejoindre
    // — elle est visible via Mon espace/le lien direct, pas dans le Fil partagé.
    private const TRANSMISSION_EVENTS = ['TRANSMISSION_PROPOSED', 'TRANSMISSION_ACCEPTED', 'TRANSMISSION_COMPLETED'];

    private const TRANSMISSION_CONTEXT_FILTER = ['PROJECT' => 'PROJECTS', 'ZUMRA' => 'ZUMRA', 'NEED' => 'NEEDS'];

    // Carnet de preuves (CAP-036) : mêmes deux événements-charnière, repliés dans le filtre
    // de leur origine porteuse. Une preuve sans origine à contexte (NONE/INTERACTION) reste
    // réelle mais n'a pas de bucket de Fil à rejoindre.
    private const PROOF_EVENTS = ['PROOF_SUBMITTED', 'PROOF_ACKNOWLEDGED'];

    private const PROOF_CONTEXT_FILTER = ['PROJECT' => 'PROJECTS', 'ZUMRA' => 'ZUMRA', 'NEED' => 'NEEDS'];

    public function __construct(
        private readonly NeedService $needs,
        private readonly ProjectService $projects,
        private readonly MissionContextRegistry $missionContexts,
        private readonly MissionVisibilityService $missionVisibility,
        private readonly TransmissionVisibilityService $transmissionVisibility,
        private readonly ProofVisibilityService $proofVisibility,
    ) {
    }

    public function paginate(string $actor, string $filter = 'ALL', int $page = 1): LengthAwarePaginator
    {
        $filter = isset(self::FILTERS[$filter]) ? $filter : 'ALL';
        $page = max(1, $page);
        $items = $this->items($actor, $filter);

        return new LengthAwarePaginator(
            $items->slice(($page - 1) * self::PER_PAGE, self::PER_PAGE)->values(),
            $items->count(),
            self::PER_PAGE,
            $page,
            ['path' => route('activity.index'), 'pageName' => 'page'],
        );
    }

    public function preview(string $actor, int $limit = 3): Collection
    {
        return $this->items($actor, 'ALL', 20)->take(max(1, min($limit, 6)))->values();
    }

    private function items(string $actor, string $filter, int $sourceLimit = self::SOURCE_LIMIT): Collection
    {
        $items = collect();

        if (in_array($filter, ['ALL', 'NEEDS'], true)) {
            $items = $items->concat($this->needItems($actor, $sourceLimit));
        }
        if (in_array($filter, ['ALL', 'PROJECTS'], true)) {
            $items = $items->concat($this->projectItems($actor, $sourceLimit));
        }
        if (in_array($filter, ['ALL', 'ZUMRA'], true)) {
            $items = $items->concat($this->zumraItems($actor, $sourceLimit));
        }
        $items = $items->concat($this->missionItems($actor, $sourceLimit, $filter));
        $items = $items->concat($this->transmissionItems($actor, $sourceLimit, $filter));
        $items = $items->concat($this->proofItems($actor, $sourceLimit, $filter));

        return $items->sort(static function (array $left, array $right): int {
            $priority = $right['priority'] <=> $left['priority'];

            return $priority !== 0
                ? $priority
                : $right['occurred_at']->getTimestamp() <=> $left['occurred_at']->getTimestamp();
        })->values();
    }

    private function needItems(string $actor, int $limit): Collection
    {
        $events = NeedEvent::query()
            ->whereIn('event', array_keys(self::NEED_EVENTS))
            ->latest('occurred_at')
            ->limit($limit)
            ->get();

        $needs = Need::query()
            ->whereIn('id', $events->pluck('need_id')->unique())
            ->get()
            ->keyBy('id');

        $seen = [];
        $items = collect();

        foreach ($events as $event) {
            if (isset($seen[$event->need_id])) {
                continue;
            }
            $seen[$event->need_id] = true;

            /** @var Need|null $need */
            $need = $needs->get($event->need_id);
            if (! $need || $need->status === Need::STATUS_ARCHIVED || ! $this->needs->canView($need, $actor)) {
                continue;
            }

            $meta = self::NEED_EVENTS[$event->event];
            $items->push([
                'key' => 'need:'.$need->id,
                'kind' => 'NEEDS',
                'kind_label' => 'Besoin',
                'event' => $event->event,
                'event_label' => $meta['label'],
                'priority' => $meta['priority'],
                'title' => $need->title,
                'summary' => Str::limit(trim($need->context), 180),
                'context' => $need->capability_label ? 'Capacité recherchée : '.$need->capability_label : null,
                'location' => $need->location,
                'image_url' => $need->image_path ? Storage::disk('public')->url($need->image_path) : null,
                'action_label' => 'Voir le besoin',
                'action_url' => route('needs.show', $need),
                'comment_url' => route('comments.need', $need),
                'share_url' => route('shares.need', $need),
                'contact_url' => route('messages.need', $need),
                'can_decide' => $this->needs->canDecide($need, $actor),
                'resolution_note' => $need->resolution_note,
                'occurred_at' => $event->occurred_at,
            ]);
        }

        return $items;
    }

    private function projectItems(string $actor, int $limit): Collection
    {
        $events = ProjectEvent::query()
            ->whereIn('event', array_keys(self::PROJECT_EVENTS))
            ->latest('occurred_at')
            ->limit($limit)
            ->get();

        $projects = Project::query()
            ->whereIn('id', $events->pluck('project_id')->unique())
            ->get()
            ->keyBy('id');

        $seen = [];
        $items = collect();

        foreach ($events as $event) {
            if (isset($seen[$event->project_id])) {
                continue;
            }
            $seen[$event->project_id] = true;

            /** @var Project|null $project */
            $project = $projects->get($event->project_id);
            if (! $project || $project->status === Project::STATUS_ARCHIVED || ! $this->projects->canView($project, $actor)) {
                continue;
            }

            $meta = self::PROJECT_EVENTS[$event->event];
            $context = null;

            if ($event->event === 'PROJECT_MATURITY_CHANGED') {
                $stage = (string) ($event->context['to'] ?? '');
                $context = isset(ProjectMaturityService::STAGES[$stage])
                    ? 'Repère actuel : '.ProjectMaturityService::STAGES[$stage]
                    : null;
            } elseif ($event->event === 'AUTONOMY_PATHWAY_OPENED') {
                $context = 'Une structuration autonome est explorée, sans création automatique de satellite.';
            }

            $items->push([
                'key' => 'project:'.$project->id,
                'kind' => 'PROJECTS',
                'kind_label' => 'Projet',
                'event' => $event->event,
                'event_label' => $meta['label'],
                'priority' => $meta['priority'],
                'title' => $project->name,
                'summary' => Str::limit(trim($project->summary), 180),
                'context' => $context,
                'maturity' => $project->maturity,
                'image_url' => $project->image_path ? Storage::disk('public')->url($project->image_path) : null,
                'action_label' => 'Voir le projet',
                'action_url' => route('projects.show', $project),
                'comment_url' => route('comments.project', $project),
                'share_url' => route('shares.project', $project),
                'can_decide' => $this->projects->canDecide($project, $actor),
                'occurred_at' => $event->occurred_at,
            ]);
        }

        return $items;
    }

    private function zumraItems(string $actor, int $limit): Collection
    {
        $events = ZumraGroupEvent::query()
            ->whereIn('event', array_keys(self::ZUMRA_EVENTS))
            ->latest('occurred_at')
            ->limit($limit)
            ->get();

        $groups = ZumraGroup::query()
            ->whereIn('id', $events->pluck('zumra_group_id')->unique())
            ->where('state', '!=', ZumraGroup::STATE_SUSPENDED)
            ->get()
            ->keyBy('id');

        $memberships = ZumraGroupMembership::query()
            ->whereIn('zumra_group_id', $groups->keys())
            ->where('core_identity_reference', $actor)
            ->pluck('status', 'zumra_group_id');

        $seen = [];
        $items = collect();

        foreach ($events as $event) {
            if (isset($seen[$event->zumra_group_id])) {
                continue;
            }
            $seen[$event->zumra_group_id] = true;

            /** @var ZumraGroup|null $group */
            $group = $groups->get($event->zumra_group_id);
            if (! $group) {
                continue;
            }

            $meta = self::ZUMRA_EVENTS[$event->event];
            $items->push([
                'key' => 'zumra:'.$group->id,
                'kind' => 'ZUMRA',
                'kind_label' => 'ZUMRA',
                'event' => $event->event,
                'event_label' => $meta['label'],
                'priority' => $meta['priority'],
                'title' => $group->name,
                'summary' => Str::limit(trim($group->founding_objective), 180),
                'context' => $group->active_member_count > 0 ? $group->active_member_count.' membre(s) actif(s)' : null,
                'state' => $group->state,
                'action_label' => 'Voir la ZUMRA',
                'action_url' => route('zumra.groups.show', $group),
                'comment_url' => route('comments.zumra-activity', $group),
                'request_url' => route('zumra.groups.request', $group),
                'contact_url' => route('messages.zumra', $group),
                'is_active_member' => $memberships->get($group->id) === ZumraGroupMembership::STATUS_ACTIVE,
                'is_pending' => in_array($memberships->get($group->id), [ZumraGroupMembership::STATUS_REQUESTED, ZumraGroupMembership::STATUS_INVITED], true),
                'seats' => $this->seats($group),
                'occurred_at' => $event->occurred_at,
            ]);
        }

        return $items;
    }

    /**
     * MISSIONS s'intègre au Fil unique : jamais un Fil Missions séparé. Chaque Mission se
     * range dans le filtre de son contexte d'origine (Projet/ZUMRA/Besoin) ; la visibilité
     * est revalidée à la projection, jamais héritée d'un état mis en cache.
     */
    private function missionItems(string $actor, int $limit, string $filter): Collection
    {
        $events = MissionEvent::query()
            ->whereIn('event', self::MISSION_EVENTS)
            ->latest('occurred_at')
            ->limit($limit)
            ->get();

        $missions = Mission::query()
            ->whereIn('id', $events->pluck('mission_id')->unique())
            ->get()
            ->keyBy('id');

        $seen = [];
        $items = collect();

        foreach ($events as $event) {
            if (isset($seen[$event->mission_id])) {
                continue;
            }
            $seen[$event->mission_id] = true;

            /** @var Mission|null $mission */
            $mission = $missions->get($event->mission_id);
            if (! $mission || ! $this->missionContexts->has($mission->context_type)) {
                continue;
            }

            $kind = self::MISSION_CONTEXT_FILTER[$mission->context_type] ?? null;
            if ($kind === null || ($filter !== 'ALL' && $filter !== $kind)) {
                continue;
            }
            if (! $this->missionVisibility->canViewMission($mission, $actor)) {
                continue;
            }

            $acceptedExecutors = MissionAssignment::query()
                ->where('mission_id', $mission->id)
                ->where('status', MissionAssignment::STATUS_ACCEPTED)
                ->whereIn('role', MissionAssignment::EXECUTION_ROLES)
                ->count();

            [$label, $priority] = match (true) {
                $event->event === 'MISSION_BLOCKED' => ['Mission bloquée', 270],
                $event->event === 'MISSION_COMPLETED' => ['Mission terminée', 130],
                $mission->status === Mission::STATUS_OPEN && $acceptedExecutors < $mission->min_executors => ['Mission cherche des exécutants', 250],
                default => ['Mission officialisée', 210],
            };

            $items->push([
                'key' => 'mission:'.$mission->id,
                'kind' => $kind,
                'kind_label' => 'Mission',
                'card' => 'mission',
                'event' => $event->event,
                'event_label' => $label,
                'badge_tone' => Mission::STATUS_BADGE_TONES[$mission->status] ?? 'neutral',
                'priority' => $priority,
                'title' => $mission->title,
                'summary' => Str::limit(trim($mission->description), 180),
                'context' => null,
                'action_label' => 'Voir la Mission',
                'action_url' => route('missions.show', $mission),
                'comment_url' => route('comments.mission', $mission),
                'share_url' => route('shares.mission', $mission),
                'can_decide' => false,
                'occurred_at' => $event->occurred_at,
            ]);
        }

        return $items;
    }

    /**
     * TRANSMISSION s'intègre au Fil unique : jamais un Fil Transmission séparé (fiche §19).
     * Chaque Transmission rattachée à un contexte se range dans le filtre de ce contexte ;
     * la visibilité est revalidée à la projection, jamais héritée d'un état mis en cache.
     */
    private function transmissionItems(string $actor, int $limit, string $filter): Collection
    {
        $events = TransmissionEvent::query()
            ->whereIn('event', self::TRANSMISSION_EVENTS)
            ->latest('occurred_at')
            ->limit($limit)
            ->get();

        $transmissions = Transmission::query()
            ->whereIn('id', $events->pluck('transmission_id')->unique())
            ->get()
            ->keyBy('id');

        $seen = [];
        $items = collect();

        foreach ($events as $event) {
            if (isset($seen[$event->transmission_id])) {
                continue;
            }
            $seen[$event->transmission_id] = true;

            /** @var Transmission|null $transmission */
            $transmission = $transmissions->get($event->transmission_id);
            if (! $transmission || $transmission->context_type === null) {
                continue;
            }

            $kind = self::TRANSMISSION_CONTEXT_FILTER[$transmission->context_type] ?? null;
            if ($kind === null || ($filter !== 'ALL' && $filter !== $kind)) {
                continue;
            }
            if (! $this->transmissionVisibility->canView($transmission, $actor)) {
                continue;
            }

            [$label, $priority] = match ($event->event) {
                'TRANSMISSION_ACCEPTED' => ['Transmission acceptée', 220],
                'TRANSMISSION_COMPLETED' => ['Transmission terminée', 130],
                default => ['Transmission proposée', 240],
            };

            $items->push([
                'key' => 'transmission:'.$transmission->id,
                'kind' => $kind,
                'kind_label' => 'Transmission',
                'card' => 'transmission',
                'event' => $event->event,
                'event_label' => $label,
                'badge_tone' => Transmission::STATUS_BADGE_TONES[$transmission->status] ?? 'neutral',
                'priority' => $priority,
                'title' => $transmission->capability_label,
                'summary' => Str::limit(trim($transmission->learning_objective), 180),
                'context' => null,
                'action_label' => 'Voir la Transmission',
                'action_url' => route('transmissions.show', $transmission),
                'comment_url' => route('comments.transmission', $transmission),
                'share_url' => route('shares.transmission', $transmission),
                'can_decide' => false,
                'occurred_at' => $event->occurred_at,
            ]);
        }

        return $items;
    }

    /**
     * CARNET DE PREUVES s'intègre au Fil unique : jamais un Fil Preuves séparé (fiche §15).
     * Chaque preuve rattachée à une origine se range dans le filtre de cette origine ; la
     * visibilité est revalidée à la projection.
     */
    private function proofItems(string $actor, int $limit, string $filter): Collection
    {
        $events = ProofEvent::query()
            ->whereIn('event', self::PROOF_EVENTS)
            ->latest('occurred_at')
            ->limit($limit)
            ->get();

        $proofs = Proof::query()
            ->whereIn('id', $events->pluck('proof_id')->unique())
            ->get()
            ->keyBy('id');

        $seen = [];
        $items = collect();

        foreach ($events as $event) {
            if (isset($seen[$event->proof_id])) {
                continue;
            }
            $seen[$event->proof_id] = true;

            /** @var Proof|null $proof */
            $proof = $proofs->get($event->proof_id);
            if (! $proof || $proof->archived_at !== null) {
                continue;
            }

            $kind = self::PROOF_CONTEXT_FILTER[$proof->origin_type] ?? null;
            if ($kind === null || ($filter !== 'ALL' && $filter !== $kind)) {
                continue;
            }
            if (! $this->proofVisibility->canView($proof, $actor)) {
                continue;
            }

            [$label, $priority] = match ($event->event) {
                'PROOF_ACKNOWLEDGED' => ['Preuve reconnue', 150],
                default => ['Preuve enregistrée', 170],
            };

            $items->push([
                'key' => 'proof:'.$proof->id,
                'kind' => $kind,
                'kind_label' => 'Preuve',
                'card' => 'proof',
                'event' => $event->event,
                'event_label' => $label,
                'badge_tone' => Proof::STATUS_BADGE_TONES[$proof->status] ?? 'neutral',
                'priority' => $priority,
                'title' => $proof->title,
                'summary' => Str::limit(trim($proof->description), 180),
                'context' => null,
                'action_label' => 'Voir la preuve',
                'action_url' => route('proofs.show', $proof),
                'comment_url' => route('comments.proof', $proof),
                'share_url' => route('shares.proof', $proof),
                'can_decide' => false,
                'occurred_at' => $event->occurred_at,
            ]);
        }

        return $items;
    }

    /** @return list<array{label: string, filled: bool}> */
    private function seats(ZumraGroup $group): array
    {
        return $group->roles()
            ->orderByRaw("case role when 'PRIMARY_LEAD' then 1 when 'FIRST_DEPUTY' then 2 when 'SECOND_DEPUTY' then 3 when 'FINANCE_LEAD' then 4 else 5 end")
            ->get()
            ->map(static fn (ZumraGroupRole $role): array => [
                'label' => ZumraGroupRole::LABELS[$role->role] ?? $role->role,
                'filled' => $role->status === ZumraGroupRole::STATUS_ACCEPTED,
            ])
            ->all();
    }
}
