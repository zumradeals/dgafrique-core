<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration;

use App\Application\Projects\ProjectAccompanimentConfiguration;
use App\Application\Projects\ProjectAccompanimentService;
use App\Domain\Identity\CoreIdentity;
use App\Models\PortalSetting;
use App\Models\Project;
use App\Models\ProjectAccompaniment;
use App\Models\ProjectAccompanimentAction;
use App\Models\ProjectAccompanimentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class ProjectAccompanimentController
{
    public function edit(ProjectAccompanimentConfiguration $configuration): View
    {
        $active = ProjectAccompaniment::query()
            ->where('status', ProjectAccompaniment::STATUS_ACTIVE)
            ->with(['project', 'actions'])
            ->latest('activated_at')
            ->limit(100)
            ->get();

        // CAP-045 : file déterministe, triée par date de demande croissante — jamais un score caché.
        $pendingRequests = ProjectAccompanimentRequest::query()
            ->where('status', ProjectAccompanimentRequest::STATUS_PENDING)
            ->with(['accompaniment.project'])
            ->oldest('requested_at')
            ->limit(100)
            ->get();

        return view('administration.project-accompaniment', [
            'configuration' => $configuration->get(),
            'activeAccompaniments' => $active,
            'pendingRequests' => $pendingRequests,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        $data = $request->validate([
            'page_title' => ['required', 'string', 'max:180'],
            'page_intro' => ['required', 'string', 'max:700'],
            'action_type_codes' => ['required', 'array', 'min:1', 'max:24'],
            'action_type_codes.*' => ['required', 'regex:/^[A-Z][A-Z0-9_]{1,49}$/', 'distinct'],
            'action_type_labels' => ['required', 'array'],
            'action_type_labels.*' => ['required', 'string', 'max:120'],
        ]);

        abort_unless(count($data['action_type_codes']) === count($data['action_type_labels']), 422);

        $types = [];
        foreach ($data['action_type_codes'] as $index => $code) {
            $types[$code] = $data['action_type_labels'][$index];
        }

        PortalSetting::query()->updateOrCreate(
            ['key' => ProjectAccompanimentConfiguration::KEY],
            [
                'value' => [
                    'page_title' => $data['page_title'],
                    'page_intro' => $data['page_intro'],
                    'action_types' => $types,
                ],
                'updated_by_core_reference' => $identity->reference,
            ]
        );

        return back()->with('status', 'Configuration CAP‑016 enregistrée.');
    }

    public function storeAction(
        Request $request,
        Project $project,
        ProjectAccompanimentConfiguration $configuration,
        ProjectAccompanimentService $service
    ): RedirectResponse {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        $settings = $configuration->get();

        $data = $request->validate([
            'action_type' => ['required', Rule::in(array_keys($settings['action_types']))],
            'delivery_source' => [
                'required',
                Rule::in([ProjectAccompanimentAction::SOURCE_INTERNAL, ProjectAccompanimentAction::SOURCE_PARTNER]),
            ],
            'provider_label' => ['nullable', 'string', 'max:180'],
            'summary' => ['required', 'string', 'min:20', 'max:2400'],
            'next_step' => ['nullable', 'string', 'max:1200'],
            'occurred_at' => ['nullable', 'date', 'before_or_equal:now'],
        ]);

        $service->recordAction($project, $identity->reference, $data, $settings);

        return back()->with('status', 'Intervention enregistrée dans la chronologie du projet.');
    }

    public function acknowledgeRequest(
        Request $request,
        ProjectAccompanimentRequest $accompanimentRequest,
        ProjectAccompanimentService $service
    ): RedirectResponse {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $service->acknowledgeRequest($accompanimentRequest, $identity->reference);

        return back()->with('status', 'Demande prise en charge.');
    }

    public function closeRequest(
        Request $request,
        ProjectAccompanimentRequest $accompanimentRequest,
        ProjectAccompanimentService $service
    ): RedirectResponse {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $request->validate(['resolution_note' => ['nullable', 'string', 'max:1500']]);
        $service->closeRequest($accompanimentRequest, $identity->reference, $data['resolution_note'] ?? null);

        return back()->with('status', 'Demande close.');
    }
}
