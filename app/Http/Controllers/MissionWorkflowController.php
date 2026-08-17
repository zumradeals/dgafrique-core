<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Missions\MissionBlockerService;
use App\Application\Missions\MissionSubmissionService;
use App\Application\Missions\MissionWorkflow;
use App\Domain\Identity\CoreIdentity;
use App\Models\Mission;
use App\Models\MissionBlocker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class MissionWorkflowController
{
    public function propose(Request $request, Mission $mission, MissionWorkflow $workflow): RedirectResponse
    {
        $identity = $this->identity($request);
        $workflow->propose($mission, $identity->reference);

        return back()->with('status', 'La Mission est proposée. Une décision distincte reste nécessaire pour l’ouvrir.');
    }

    public function requestChanges(Request $request, Mission $mission, MissionWorkflow $workflow): RedirectResponse
    {
        $identity = $this->identity($request);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $workflow->requestChanges($mission, $identity->reference, $data['reason']);

        return back()->with('status', 'Des modifications ont été demandées avant officialisation.');
    }

    public function resubmitProposal(Request $request, Mission $mission, MissionWorkflow $workflow): RedirectResponse
    {
        $identity = $this->identity($request);
        $data = $request->validate([
            'title' => ['nullable', 'string', 'min:5', 'max:180'],
            'description' => ['nullable', 'string', 'min:20', 'max:4000'],
            'expected_result' => ['nullable', 'string', 'max:2000'],
        ]);
        $workflow->resubmitProposal($mission, $identity->reference, $data);

        return back()->with('status', 'La proposition corrigée est de nouveau soumise à décision.');
    }

    public function reject(Request $request, Mission $mission, MissionWorkflow $workflow): RedirectResponse
    {
        $identity = $this->identity($request);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $workflow->reject($mission, $identity->reference, $data['reason']);

        return back()->with('status', 'La proposition de Mission est rejetée.');
    }

    public function officialize(Request $request, Mission $mission, MissionWorkflow $workflow): RedirectResponse
    {
        $identity = $this->identity($request);
        $data = $request->validate([
            'expected_result' => ['nullable', 'string', 'max:2000'],
            'acceptance_criteria' => ['nullable', 'array'],
        ]);
        $workflow->officialize($mission, $identity->reference, $data);

        return back()->with('status', 'La Mission est officialisée et ouverte.');
    }

    public function start(Request $request, Mission $mission, MissionWorkflow $workflow): RedirectResponse
    {
        $identity = $this->identity($request);
        $workflow->start($mission, $identity->reference);

        return back()->with('status', 'La Mission est démarrée.');
    }

    public function block(Request $request, Mission $mission, MissionBlockerService $blockers): RedirectResponse
    {
        $identity = $this->identity($request);
        $data = $request->validate([
            'type' => ['required', Rule::in(MissionBlocker::TYPES)],
            'description' => ['required', 'string', 'min:5', 'max:1500'],
        ]);

        if ($mission->status === Mission::STATUS_BLOCKED) {
            $blockers->reportAdditional($mission, $identity->reference, $data['type'], $data['description']);
        } else {
            $blockers->block($mission, $identity->reference, $data['type'], $data['description']);
        }

        return back()->with('status', 'Le blocage est signalé.');
    }

    public function resolveBlocker(Request $request, Mission $mission, MissionBlocker $blocker, MissionBlockerService $blockers): RedirectResponse
    {
        $identity = $this->identity($request);
        $data = $request->validate(['resolution_note' => ['nullable', 'string', 'max:1000']]);
        $blockers->resolve($mission, $identity->reference, $blocker, $data['resolution_note'] ?? null);

        return back()->with('status', 'Le blocage est résolu.');
    }

    public function submit(Request $request, Mission $mission, MissionSubmissionService $submissions): RedirectResponse
    {
        $identity = $this->identity($request);
        $data = $request->validate(['result_summary' => ['required', 'string', 'min:10', 'max:4000']]);
        $submissions->submit($mission, $identity->reference, $data['result_summary']);

        return back()->with('status', 'Le résultat est soumis pour validation.');
    }

    public function requestCorrection(Request $request, Mission $mission, MissionSubmissionService $submissions): RedirectResponse
    {
        $identity = $this->identity($request);
        $data = $request->validate(['note' => ['required', 'string', 'min:5', 'max:1500']]);
        $submissions->requestCorrection($mission, $identity->reference, $data['note']);

        return back()->with('status', 'Une correction est demandée avant validation finale.');
    }

    public function validateSubmission(Request $request, Mission $mission, MissionSubmissionService $submissions): RedirectResponse
    {
        $identity = $this->identity($request);
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);
        $submissions->accept($mission, $identity->reference, $data['note'] ?? null);

        return back()->with('status', 'La Mission est validée et terminée.');
    }

    public function cancel(Request $request, Mission $mission, MissionWorkflow $workflow): RedirectResponse
    {
        $identity = $this->identity($request);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $workflow->cancel($mission, $identity->reference, $data['reason']);

        return back()->with('status', 'La Mission est annulée.');
    }

    public function reopen(Request $request, Mission $mission, MissionWorkflow $workflow): RedirectResponse
    {
        $identity = $this->identity($request);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $workflow->reopen($mission, $identity->reference, $data['reason']);

        return back()->with('status', 'La Mission est rouverte, motif conservé.');
    }

    private function identity(Request $request): CoreIdentity
    {
        return $request->attributes->get('dg_identity');
    }
}
