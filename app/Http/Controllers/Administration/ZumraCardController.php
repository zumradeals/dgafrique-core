<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration;

use App\Domain\Identity\CoreIdentity;
use App\Models\ZumraCard;
use App\Models\ZumraProgramMembershipEvent;
use App\Models\ZumraProgramMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ZumraCardController
{
    public function revoke(Request $request, ZumraCard $card): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:500']]);

        DB::transaction(static function () use ($card, $data, $identity): void {
            $locked = ZumraCard::query()->whereKey($card->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === ZumraCard::STATUS_REVOKED || $locked->revoked_at !== null) abort(409, 'Cette carte est déjà révoquée.');
            $membership = ZumraProgramMembership::query()->whereKey($locked->membership_id)->lockForUpdate()->firstOrFail();
            $locked->update(['status' => ZumraCard::STATUS_REVOKED, 'revoked_at' => now(), 'revocation_reason' => trim($data['reason'])]);
            ZumraProgramMembershipEvent::query()->create([
                'membership_id' => $locked->membership_id, 'event' => 'CARD_REVOKED',
                'from_status' => $membership->status, 'to_status' => $membership->status,
                'actor_core_reference' => $identity->reference,
                'context' => ['card_reference' => $locked->public_reference, 'reason' => trim($data['reason'])],
                'occurred_at' => now(),
            ]);
        });

        return back()->with('status', 'Carte révoquée. Son ancienne vérification reste consultable et indique désormais cet état.');
    }
}
