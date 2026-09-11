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
            'availability' => ['nullable', Rule::in(array_keys(PersonProfile::AVAILABILITY_LABELS))],
            'recent' => ['nullable', Rule::in(['1'])],
        ]);
        $term = CapabilityStatementSynchronizer::normalize((string) ($data['q'] ?? ''));

        // La présence personnelle est distincte de la découverte publique : l'utilisateur
        // se voit toujours dans son Carrefour, sans rendre ses données visibles aux autres.
        $selfProfile = PersonProfile::query()
            ->whereKey($identity->reference)
            ->with(['capabilityStatements' => static fn ($query) => $query
                ->whereNull('archived_at')
                ->orderBy('kind')->orderBy('label')])
            ->first();

        $discoverable = PersonProfile::query()
            ->where('core_identity_reference', '!=', $identity->reference)
            ->where('orientation_consent', true)
            ->where('discovery_consent', true)
            ->whereNotNull('discovery_reference')
            ->whereNotNull('discovery_display_name');

        $metrics = [
            'people' => (clone $discoverable)->count() + ($selfProfile ? 1 : 0),
            'available' => (clone $discoverable)->where('availability_status', PersonProfile::AVAILABILITY_OPEN)->count()
                + (($selfProfile?->availability_status === PersonProfile::AVAILABILITY_OPEN) ? 1 : 0),
            'capabilities' => CapabilityStatement::query()->whereIn('core_identity_reference', (clone $discoverable)->select('core_identity_reference'))->whereNull('archived_at')->where('matching_consent', true)->where('visibility', CapabilityStatement::VISIBILITY_DISCOVERABLE)->distinct('normalized_label')->count('normalized_label'),
            'recent' => (clone $discoverable)->where('discovery_consented_at', '>=', now()->subMonth())->count(),
        ];

        $popularCapabilities = CapabilityStatement::query()
            ->selectRaw('label, normalized_label, COUNT(*) as people_count')
            ->whereIn('core_identity_reference', (clone $discoverable)->select('core_identity_reference'))
            ->whereNull('archived_at')->where('matching_consent', true)
            ->where('visibility', CapabilityStatement::VISIBILITY_DISCOVERABLE)
            ->groupBy('label', 'normalized_label')->orderByDesc('people_count')->orderBy('label')->limit(8)->get();

        $territoryCounts = (clone $discoverable)
            ->whereNotNull('city')->where('city', '!=', '')
            ->selectRaw('city, country_code, COUNT(*) as people_count')
            ->groupBy('city', 'country_code')
            ->orderByDesc('people_count')->orderBy('city')->limit(10)->get();

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
        if (isset($data['availability'])) {
            $query->where('availability_status', $data['availability']);
        }
        if (($data['recent'] ?? null) === '1') {
            $query->where('discovery_consented_at', '>=', now()->subMonth());
        }

        $profiles = $query->orderByDesc('discovery_consented_at')
            ->paginate((int) $settings['page_size'])->withQueryString();
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        return view('discovery.index', compact('identity', 'settings', 'profiles', 'modes', 'term', 'isAdministrator', 'metrics', 'popularCapabilities', 'recentProfiles', 'recommendations', 'selfProfile', 'territoryCounts'));
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
