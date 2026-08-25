<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration;

use App\Application\Contributions\ContributionConfiguration;
use App\Domain\Identity\CoreIdentity;
use App\Models\ContributionPurpose;
use App\Models\PortalSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** CAP-061 — administration minimale : montants/activation et gestion des finalités. */
final class ContributionConfigurationController
{
    public function edit(ContributionConfiguration $configuration): View
    {
        return view('administration.contributions', [
            'configuration' => $configuration->get(),
            'purposes' => ContributionPurpose::query()->orderBy('code')->get(['id', 'code', 'label', 'status']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $request->validate([
            'individual_enabled' => ['nullable', 'boolean'],
            'individual_amount' => ['required', 'integer', 'min:1', 'max:1000000'],
            'collective_enabled' => ['nullable', 'boolean'],
            'collective_amount' => ['required', 'integer', 'min:1', 'max:10000000'],
            'currency' => ['required', 'string', 'size:3'],
        ]);
        $data['individual_enabled'] = $request->boolean('individual_enabled');
        $data['collective_enabled'] = $request->boolean('collective_enabled');
        PortalSetting::query()->updateOrCreate(['key' => ContributionConfiguration::KEY], ['value' => $data, 'updated_by_core_reference' => $identity->reference]);

        return back()->with('status', 'Configuration CAP‑061 enregistrée.');
    }

    public function retirePurpose(Request $request, ContributionPurpose $purpose): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        abort_if($purpose->status === ContributionPurpose::STATUS_RETIRED, 409, 'Cette finalité est déjà retirée.');
        $purpose->update(['status' => ContributionPurpose::STATUS_RETIRED, 'retired_by_core_reference' => $identity->reference, 'retired_at' => now()]);

        return back()->with('status', 'Finalité retirée. Les paiements déjà associés restent inchangés.');
    }

    public function reactivatePurpose(Request $request, ContributionPurpose $purpose): RedirectResponse
    {
        abort_unless($purpose->status === ContributionPurpose::STATUS_RETIRED, 409, 'Cette finalité est déjà active.');
        $purpose->update(['status' => ContributionPurpose::STATUS_ACTIVE, 'retired_by_core_reference' => null, 'retired_at' => null]);

        return back()->with('status', 'Finalité réactivée.');
    }
}
