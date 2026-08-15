<?php

declare(strict_types=1);

namespace App\Application\Zumra;

use App\Models\PersonProfile;
use App\Models\ZumraCard;
use App\Models\ZumraProgramMembership;
use App\Models\ZumraProgramMembershipEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class ZumraCardIssuer
{
    public function issue(ZumraProgramMembership $membership, string $holderLabel): ZumraCard
    {
        if ($membership->status !== ZumraProgramMembership::STATUS_ACTIVE || $membership->activated_at === null) {
            throw new RuntimeException('ZUMRA_CARD_REQUIRES_ACTIVE_MEMBERSHIP');
        }

        return DB::transaction(function () use ($membership, $holderLabel): ZumraCard {
            $locked = ZumraProgramMembership::query()->whereKey($membership->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== ZumraProgramMembership::STATUS_ACTIVE || $locked->activated_at === null) {
                throw new RuntimeException('ZUMRA_CARD_REQUIRES_ACTIVE_MEMBERSHIP');
            }
            $existing = ZumraCard::query()->where('membership_id', $locked->id)
                ->where('status', ZumraCard::STATUS_ACTIVE)->whereNull('revoked_at')->latest('issued_at')->first();
            if ($existing) return $existing;

            $profile = PersonProfile::query()->find($locked->core_identity_reference);
            $card = ZumraCard::query()->create([
                'membership_id' => $locked->id,
                'public_reference' => $this->newReference(),
                'core_identity_reference' => $locked->core_identity_reference,
                'holder_label' => Str::squish($holderLabel),
                'country_code' => $profile?->country_code,
                'status' => ZumraCard::STATUS_ACTIVE,
                'issued_at' => now(),
            ]);
            ZumraProgramMembershipEvent::query()->create([
                'membership_id' => $locked->id, 'event' => 'CARD_ISSUED',
                'from_status' => $locked->status, 'to_status' => $locked->status,
                'actor_core_reference' => 'SYSTEM:CARD_ISSUER',
                'context' => ['card_reference' => $card->public_reference], 'occurred_at' => now(),
            ]);

            return $card;
        }, 3);
    }

    private function newReference(): string
    {
        do {
            $reference = 'ZUM-'.now()->format('Y').'-'.strtoupper(Str::random(12));
        } while (ZumraCard::query()->where('public_reference', $reference)->exists());

        return $reference;
    }
}
