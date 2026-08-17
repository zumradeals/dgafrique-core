<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Missions\MissionContextRegistry;
use App\Application\Missions\MissionService;
use App\Application\Missions\MissionVisibilityService;
use App\Application\Missions\MissionWorkflow;
use App\Domain\Identity\CoreIdentity;
use App\Models\Mission;
use App\Models\MissionRecurrence;
use App\Models\Need;
use App\Models\PersonProfile;
use App\Models\PortalAdministrator;
use App\Models\Project;
use App\Models\ZumraGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class MissionController
{
    public function index(Request $request, MissionService $missions): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $sections = $missions->myMissionsSections($identity->reference);
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        return view('missions.index', compact('identity', 'sections', 'isAdministrator'));
    }

    public function show(Request $request, Mission $mission, MissionService $missions, MissionContextRegistry $registry, MissionVisibilityService $visibility): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $mission = $missions->find($mission->public_reference, $identity->reference);
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        $adapter = $registry->for($mission->context_type);
        $context = $adapter->resolve($mission->context_reference);

        $mission->load(['assignments', 'checklistItems', 'capabilityRequirements', 'resourceRequirements', 'blockers', 'contributions', 'submissions', 'dependencies']);
        $children = $missions->children($mission, $identity->reference);
        $recurrence = $mission->parent_mission_id === null
            ? MissionRecurrence::query()->where('source_mission_id', $mission->id)->first()
            : null;
        $myAssignment = $mission->assignments->firstWhere('core_identity_reference', $identity->reference);

        $participantLabels = [];
        foreach ($mission->assignments as $assignment) {
            $reference = $assignment->core_identity_reference;
            if (hash_equals($reference, $identity->reference)) {
                $participantLabels[$assignment->id] = 'Vous';
                continue;
            }
            $exposed = $visibility->canExposeParticipantIdentity($mission, $identity->reference, $assignment);
            $profile = $exposed ? PersonProfile::query()->where('core_identity_reference', $reference)->first() : null;
            $participantLabels[$assignment->id] = $profile?->discovery_display_name ?: 'Membre DG Afrique';
        }

        return view('missions.show', [
            'recurrence' => $recurrence,
            'myAssignment' => $myAssignment,
            'participantLabels' => $participantLabels,
            'identity' => $identity,
            'isAdministrator' => $isAdministrator,
            'mission' => $mission,
            'contextLabel' => $adapter->label($context),
            'contextUrl' => $this->contextUrl($mission->context_type, $context),
            'canOfficialize' => $adapter->canOfficialize($context, $identity->reference),
            'canManageAssignments' => $adapter->canManageAssignments($context, $identity->reference),
            'canValidate' => $adapter->canValidate($context, $identity->reference),
            'canCancel' => $adapter->canCancel($context, $identity->reference),
            'canReopen' => $adapter->canReopen($context, $identity->reference),
            'children' => $children,
        ]);
    }

    public function createForProject(Request $request, Project $project): View
    {
        return $this->createView($request, 'PROJECT', $project->public_reference, route('projects.missions.store', $project), route('projects.show', $project));
    }

    public function storeForProject(Request $request, Project $project, MissionWorkflow $workflow): RedirectResponse
    {
        return $this->store($request, 'PROJECT', $project->public_reference, $workflow, route('projects.show', $project));
    }

    public function createForZumraGroup(Request $request, ZumraGroup $group): View
    {
        return $this->createView($request, 'ZUMRA', $group->public_reference, route('zumra.groups.missions.store', $group), route('zumra.groups.show', $group));
    }

    public function storeForZumraGroup(Request $request, ZumraGroup $group, MissionWorkflow $workflow): RedirectResponse
    {
        return $this->store($request, 'ZUMRA', $group->public_reference, $workflow, route('zumra.groups.show', $group));
    }

    public function createForNeed(Request $request, Need $need): View
    {
        return $this->createView($request, 'NEED', $need->public_reference, route('needs.missions.store', $need), route('needs.show', $need));
    }

    public function storeForNeed(Request $request, Need $need, MissionWorkflow $workflow): RedirectResponse
    {
        return $this->store($request, 'NEED', $need->public_reference, $workflow, route('needs.show', $need));
    }

    public function createChild(Request $request, Mission $mission, MissionService $missions): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $mission = $missions->find($mission->public_reference, $identity->reference);
        abort_if(in_array($mission->status, Mission::TERMINAL_STATUSES, true), 409, 'Cette Mission est terminée.');

        return $this->createView(
            $request, $mission->context_type, $mission->context_reference,
            route('missions.children.store', $mission), route('missions.show', $mission), $mission
        );
    }

    public function storeChild(Request $request, Mission $mission, MissionWorkflow $workflow, MissionService $missions): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $mission = $missions->find($mission->public_reference, $identity->reference);

        return $this->store(
            $request, $mission->context_type, $mission->context_reference, $workflow,
            route('missions.show', $mission), $mission->public_reference
        );
    }

    private function createView(Request $request, string $contextType, string $contextReference, string $storeUrl, string $backUrl, ?Mission $parent = null): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        return view('missions.create', [
            'identity' => $identity,
            'isAdministrator' => $isAdministrator,
            'contextType' => $contextType,
            'contextReference' => $contextReference,
            'storeUrl' => $storeUrl,
            'backUrl' => $backUrl,
            'parent' => $parent,
        ]);
    }

    private function store(Request $request, string $contextType, string $contextReference, MissionWorkflow $workflow, string $redirectUrl, ?string $parentReference = null): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        $data = $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:180'],
            'description' => ['required', 'string', 'min:20', 'max:4000'],
            'expected_result' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', Rule::in([Mission::VISIBILITY_PRIVATE, Mission::VISIBILITY_CONTEXT, Mission::VISIBILITY_PROGRAM, Mission::VISIBILITY_PUBLIC])],
            'participation_mode' => ['nullable', 'string', 'max:20'],
            'location' => ['nullable', 'string', 'max:200'],
            'min_executors' => ['nullable', 'integer', 'min:1', 'max:200'],
            'max_executors' => ['nullable', 'integer', 'min:1', 'max:200'],
            'due_at' => ['nullable', 'date'],
        ]);

        if ($parentReference !== null) {
            $data['parent_mission_id'] = $parentReference;
        }

        $mission = $workflow->create($identity->reference, $contextType, $contextReference, $data);

        return redirect()->route('missions.show', $mission)
            ->with('status', 'Le brouillon de Mission est enregistré. Proposez-le pour lancer la décision.');
    }

    private function contextUrl(string $contextType, object $context): ?string
    {
        return match ($contextType) {
            'PROJECT' => route('projects.show', $context),
            'ZUMRA' => route('zumra.groups.show', $context),
            'NEED' => route('needs.show', $context),
            default => null,
        };
    }
}
