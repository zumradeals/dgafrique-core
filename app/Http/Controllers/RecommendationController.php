<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Recommendation\PersonRecommendationEngine;
use App\Application\Recommendation\RecommendationConfiguration;
use App\Domain\Identity\CoreIdentity;
use App\Models\PersonProfile;
use App\Models\PortalAdministrator;
use App\Models\RecommendationDecision;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class RecommendationController
{
    public function index(Request $request, RecommendationConfiguration $configuration, PersonRecommendationEngine $engine): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $settings = $configuration->get();
        $result = $engine->forIdentity($identity->reference, $settings);
        $hiddenReferences = RecommendationDecision::query()
            ->where('core_identity_reference', $identity->reference)
            ->where('subject_type', RecommendationDecision::SUBJECT_PERSON)
            ->where('decision', RecommendationDecision::DECISION_HIDDEN)
            ->pluck('subject_reference');
        $hiddenProfiles = PersonProfile::query()
            ->whereIn('discovery_reference', $hiddenReferences)
            ->where('orientation_consent', true)
            ->where('discovery_consent', true)
            ->orderBy('discovery_display_name')
            ->get();

        return view('recommendations.index', [
            'identity' => $identity,
            'isAdministrator' => PortalAdministrator::query()->whereKey($identity->reference)->exists(),
            'settings' => $settings,
            'memberProfile' => $result['profile'],
            'recommendations' => $result['recommendations'],
            'hiddenProfiles' => $hiddenProfiles,
        ]);
    }

    public function hide(Request $request, string $reference): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $subject = PersonProfile::query()
            ->where('discovery_reference', $reference)
            ->where('core_identity_reference', '!=', $identity->reference)
            ->where('orientation_consent', true)
            ->where('discovery_consent', true)
            ->firstOrFail();

        RecommendationDecision::query()->updateOrCreate([
            'core_identity_reference' => $identity->reference,
            'subject_type' => RecommendationDecision::SUBJECT_PERSON,
            'subject_reference' => $subject->discovery_reference,
        ], ['decision' => RecommendationDecision::DECISION_HIDDEN]);

        return back()->with('status', 'Cette suggestion a été masquée pour vous.');
    }

    public function restore(Request $request, string $reference): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        RecommendationDecision::query()
            ->where('core_identity_reference', $identity->reference)
            ->where('subject_type', RecommendationDecision::SUBJECT_PERSON)
            ->where('subject_reference', $reference)
            ->delete();

        return back()->with('status', 'Cette suggestion peut de nouveau vous être proposée.');
    }
}
