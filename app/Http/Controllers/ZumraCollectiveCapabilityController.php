<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Identity\CoreIdentity;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupEvent;
use App\Models\ZumraGroupMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ZumraCollectiveCapabilityController
{
    public function consent(Request $request, ZumraGroup $group): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $enabled = $request->boolean('collective_capability_consent');

        DB::transaction(static function () use ($group, $identity, $enabled): void {
            $membership = ZumraGroupMembership::query()
                ->where('zumra_group_id', $group->id)
                ->where('core_identity_reference', $identity->reference)
                ->where('status', ZumraGroupMembership::STATUS_ACTIVE)
                ->lockForUpdate()
                ->firstOrFail();
            $membership->update([
                'collective_capability_consent' => $enabled,
                'collective_capability_consented_at' => $enabled ? now() : null,
            ]);
            ZumraGroupEvent::query()->create([
                'zumra_group_id' => $group->id,
                'event' => $enabled ? 'COLLECTIVE_CAPABILITY_CONSENTED' : 'COLLECTIVE_CAPABILITY_WITHDRAWN',
                'actor_core_reference' => $identity->reference,
                'context' => [],
                'occurred_at' => now(),
            ]);
        });

        return back()->with('status', $enabled ? 'Vos capacités découvrables peuvent maintenant renforcer le profil collectif.' : 'Vos capacités ne sont plus comptées dans ce profil collectif.');
    }
}
