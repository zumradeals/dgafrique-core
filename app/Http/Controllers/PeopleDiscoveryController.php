<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Discovery\PeopleDiscoveryConfiguration;
use App\Application\Profile\CapabilityStatementSynchronizer;
use App\Application\Profile\ProfileConfiguration;
use App\Application\Recommendation\PersonRecommendationEngine;
use App\Application\Recommendation\RecommendationConfiguration;
use App\Domain\Identity\CoreIdentity;
use App\Models\CapabilityStatement;
use App\Models\PersonProfile;
use App\Models\PortalAdministrator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class PeopleDiscoveryController
{
    public function index(Request $request, PeopleDiscoveryConfiguration $configuration, ProfileConfiguration $profileConfiguration, PersonRecommendationEngine $recommendationEngine, RecommendationConfiguration $recommendationConfiguration): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $settings = $configuration->get();
        $modes = $profileConfiguration->get()['participation_modes'] ?? [];
        $modeValues = array_column($modes, 'value');
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
            'mode' => ['nullable', Rule::in($modeValues)],
        ]);
        $term = CapabilityStatementSynchronizer::normalize((string) ($data['q'] ?? ''));

        $discoverable = PersonProfile::query()
            ->where('core_identity_reference', '!=', $identity->reference)
            ->where('orientation_consent', true)
            ->where('discovery_consent', true)
            ->whereNotNull('discovery_reference')
            ->whereNotNull('discovery_display_name');
        $metrics = [
            'people' => (clone $discoverable)->count(),
            'available' => (clone $discoverable)->where('availability_status', PersonProfile::AVAILABILITY_OPEN)->count(),
            'capabilities' => CapabilityStatement::query()->whereIn('core_identity_reference', (clone $discoverable)->select('core_identity_reference'))->whereNull('archived_at')->where('matching_consent', true)->where('visibility', CapabilityStatement::VISIBILITY_DISCOVERABLE)->distinct('normalized_label')->count('normalized_label'),
            'recent' => (clone $discoverable)->where('discovery_consented_at', '>=', now()->subMonth())->count(),
        ];
        $popularCapabilities = CapabilityStatement::query()
            ->selectRaw('label, normalized_label, COUNT(*) as people_count')
            ->whereIn('core_identity_reference', (clone $discoverable)->select('core_identity_reference'))
            ->whereNull('archived_at')->where('matching_consent', true)
            ->where('visibility', CapabilityStatement::VISIBILITY_DISCOVERABLE)
            ->groupBy('label', 'normalized_label')->orderByDesc('people_count')->orderBy('label')->limit(5)->get();
        $recentProfiles = (clone $discoverable)->orderByDesc('discovery_consented_at')->limit(5)->get();
        $recommendations = $recommendationEngine->forIdentity($identity->reference, $recommendationConfiguration->get())['recommendations'];
        $query = (clone $discoverable)
            ->with(['capabilityStatements' => static fn ($query) => $query
                ->whereNull('archived_at')
                ->where('matching_consent', true)
                ->where('visibility', CapabilityStatement::VISIBILITY_DISCOVERABLE)
                ->orderBy('kind')->orderBy('label')]);

        if ($term !== '') {
            $query->where(function (Builder $query) use ($term): void {
                $query->whereRaw('LOWER(current_activity) LIKE ?', ['%'.$term.'%'])
                    ->orWhereHas('capabilityStatements', static fn (Builder $statements) => $statements
                        ->whereNull('archived_at')
                        ->where('matching_consent', true)
                        ->where('visibility', CapabilityStatement::VISIBILITY_DISCOVERABLE)
                        ->where('normalized_label', 'like', '%'.$term.'%'));
            });
        }
        if (($settings['country_filter'] ?? true) && isset($data['country'])) {
            $query->where('country_code', strtoupper($data['country']));
        }
        if (($settings['mode_filter'] ?? true) && isset($data['mode'])) {
            $query->where('participation_mode', $data['mode']);
        }

        $profiles = $query->orderByDesc('discovery_consented_at')
            ->paginate((int) $settings['page_size'])->withQueryString();
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        return view('discovery.index', compact('identity', 'settings', 'profiles', 'modes', 'term', 'isAdministrator', 'metrics', 'popularCapabilities', 'recentProfiles', 'recommendations'));
    }

    public function show(Request $request, string $reference, PeopleDiscoveryConfiguration $configuration): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $profile = PersonProfile::query()
            ->where('discovery_reference', $reference)
            ->where('core_identity_reference', '!=', $identity->reference)
            ->where('orientation_consent', true)
            ->where('discovery_consent', true)
            ->with(['capabilityStatements' => static fn ($query) => $query
                ->whereNull('archived_at')
                ->where('matching_consent', true)
                ->where('visibility', CapabilityStatement::VISIBILITY_DISCOVERABLE)
                ->orderBy('kind')->orderBy('label')])
            ->firstOrFail();
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        return view('discovery.show', ['identity' => $identity, 'profile' => $profile, 'settings' => $configuration->get(), 'isAdministrator' => $isAdministrator]);
    }
}
