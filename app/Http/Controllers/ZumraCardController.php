<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Zumra\ZumraCardIssuer;
use App\Domain\Identity\CoreIdentity;
use App\Models\PortalAdministrator;
use App\Models\ZumraCard;
use App\Models\ZumraProgramMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

final class ZumraCardController
{
    public function show(Request $request, ZumraCardIssuer $issuer): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $membership = ZumraProgramMembership::query()->where('core_identity_reference', $identity->reference)->first();
        $card = $membership ? ZumraCard::query()->where('membership_id', $membership->id)
            ->where('status', ZumraCard::STATUS_ACTIVE)->whereNull('revoked_at')->latest('issued_at')->first() : null;
        if ($membership?->status === ZumraProgramMembership::STATUS_ACTIVE && $card === null) {
            $card = $issuer->issue($membership, $identity->label);
        }
        $state = $this->state($card, $membership);
        $verificationUrl = $card ? URL::signedRoute('zumra.card.verify', ['card' => $card]) : null;
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        return view('zumra.card', compact('identity', 'membership', 'card', 'state', 'verificationUrl', 'isAdministrator'));
    }

    public function verify(ZumraCard $card): View
    {
        $membership = ZumraProgramMembership::query()->findOrFail($card->membership_id);
        $state = $this->state($card, $membership);

        return view('zumra.card-verification', compact('card', 'membership', 'state'));
    }

    private function state(?ZumraCard $card, ?ZumraProgramMembership $membership): string
    {
        if ($card?->status === ZumraCard::STATUS_REVOKED || $card?->revoked_at !== null) return 'REVOKED';
        if ($membership?->status === ZumraProgramMembership::STATUS_SUSPENDED) return 'SUSPENDED';
        if ($membership?->status === ZumraProgramMembership::STATUS_CLOSED) return 'CLOSED';
        if ($card && $membership?->status === ZumraProgramMembership::STATUS_ACTIVE) return 'ACTIVE';

        return $membership?->status ?? 'NOT_MEMBER';
    }
}
