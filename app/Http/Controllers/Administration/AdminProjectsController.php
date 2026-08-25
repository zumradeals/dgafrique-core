<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration;

use App\Models\Mission;
use App\Models\Project;
use App\Models\ProjectFunding;
use App\Models\Proof;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ADMIN-CONTROL-002 — section « Projets ». Lecture seule : aucune décision de Projet/Mission/
 * Financement/Preuve n'est prise ici — chaque objet reste gouverné par son autorité réelle
 * (ProjectAuthority, MissionContextRegistry, etc.), jamais une autorité admin parallèle.
 */
final class AdminProjectsController
{
    public function index(Request $request): View
    {
        $query = Project::query();
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->string('q').'%');
        }

        return view('administration.projects.index', [
            'projects' => $query->orderByDesc('created_at')->paginate(20)->withQueryString(),
            'statusFilter' => $request->string('status')->toString(),
            'search' => $request->string('q')->toString(),
            'byStatus' => Project::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function missions(Request $request): View
    {
        $query = Mission::query();
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('administration.projects.missions', [
            'missions' => $query->orderByDesc('created_at')->paginate(20)->withQueryString(),
            'statusFilter' => $request->string('status')->toString(),
            'byStatus' => Mission::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function fundings(Request $request): View
    {
        $query = ProjectFunding::query();
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $fundings = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $projects = Project::query()->whereIn('id', $fundings->pluck('project_id'))->get()->keyBy('id');

        return view('administration.projects.fundings', [
            'fundings' => $fundings,
            'projects' => $projects,
            'statusFilter' => $request->string('status')->toString(),
            'byStatus' => ProjectFunding::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'targetOpen' => (int) ProjectFunding::query()->where('status', ProjectFunding::STATUS_OPEN)->sum('target_amount'),
        ]);
    }

    public function proofs(Request $request): View
    {
        $query = Proof::query();
        if ($request->filled('origin_type')) {
            $query->where('origin_type', $request->string('origin_type'));
        }

        return view('administration.projects.proofs', [
            'proofs' => $query->orderByDesc('created_at')->paginate(20)->withQueryString(),
            'originFilter' => $request->string('origin_type')->toString(),
            'byOrigin' => Proof::query()->selectRaw('origin_type, count(*) as total')->groupBy('origin_type')->pluck('total', 'origin_type'),
            'byStatus' => Proof::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }
}
