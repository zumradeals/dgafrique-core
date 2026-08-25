<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration;

use App\Models\CapabilityStatement;
use App\Models\Need;
use App\Models\Organization;
use App\Models\PersonProfile;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupRole;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ADMIN-CONTROL-002 — section « Communauté ». Uniquement de la supervision (lecture + liens vers
 * les actions déjà existantes) : aucune nouvelle règle métier, aucune mutation de Personne, de
 * Besoin ou d'Organisation n'est introduite ici.
 */
final class AdminCommunityController
{
    public function people(): View
    {
        return view('administration.community.people', [
            'profileCount' => PersonProfile::query()->count(),
            'orientationOptIn' => PersonProfile::query()->where('orientation_consent', true)->count(),
            'discoveryOptIn' => PersonProfile::query()->where('discovery_consent', true)->count(),
            'availabilityByStatus' => PersonProfile::query()->selectRaw('availability_status, count(*) as total')->groupBy('availability_status')->pluck('total', 'availability_status'),
            'capabilitiesByKind' => CapabilityStatement::query()->selectRaw('kind, count(*) as total')->groupBy('kind')->pluck('total', 'kind'),
            'capabilitiesByStatus' => CapabilityStatement::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function zumra(Request $request): View
    {
        $query = ZumraGroup::query();
        if ($request->filled('state')) {
            $query->where('state', $request->string('state'));
        }
        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->string('q').'%');
        }

        $groups = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('administration.community.zumra', [
            'groups' => $groups,
            'stateFilter' => $request->string('state')->toString(),
            'search' => $request->string('q')->toString(),
            'byState' => ZumraGroup::query()->selectRaw('state, count(*) as total')->groupBy('state')->pluck('total', 'state'),
            'rolesProposed' => ZumraGroupRole::query()->where('status', ZumraGroupRole::STATUS_PROPOSED)->count(),
        ]);
    }

    public function needs(Request $request): View
    {
        $query = Need::query();
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        $needs = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $stagnant = Need::query()->whereIn('status', [Need::STATUS_OPEN, Need::STATUS_IN_PROGRESS])
            ->where('created_at', '<=', now()->subDays(30))->count();

        return view('administration.community.needs', [
            'needs' => $needs,
            'statusFilter' => $request->string('status')->toString(),
            'categoryFilter' => $request->string('category')->toString(),
            'byStatus' => Need::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'stagnant' => $stagnant,
        ]);
    }

    public function organizations(Request $request): View
    {
        $query = Organization::query();
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $organizations = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('administration.community.organizations', [
            'organizations' => $organizations,
            'statusFilter' => $request->string('status')->toString(),
            'byStatus' => Organization::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }
}
