<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration;

use App\Application\Projects\ProjectAutonomyPathwayService;
use App\Models\Project;
use App\Models\ProjectAutonomyPathway;
use Illuminate\View\View;

final class ProjectAutonomyPathwayController
{
    public function index(): View
    {
        $eligibleProjects = Project::query()
            ->whereIn('maturity', ProjectAutonomyPathwayService::ELIGIBLE_MATURITIES)
            ->where('status', '!=', Project::STATUS_ARCHIVED)
            ->with('autonomyPathway')
            ->latest()
            ->limit(100)
            ->get();

        $activePathways = ProjectAutonomyPathway::query()
            ->where('status', ProjectAutonomyPathway::STATUS_ACTIVE)
            ->with('project')
            ->latest('opened_at')
            ->limit(100)
            ->get();

        return view('administration.project-autonomy-pathway', [
            'eligibleProjects' => $eligibleProjects,
            'activePathways' => $activePathways,
            'targetForms' => ProjectAutonomyPathwayService::TARGET_FORMS,
        ]);
    }
}
