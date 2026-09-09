<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Zumra\ZumraProgramConfiguration;
use App\Domain\Identity\CoreIdentity;
use App\Models\PortalAdministrator;
use App\Models\ZumraCharter;
use App\Models\ZumraPaymentReceipt;
use App\Models\ZumraProgramMembership;
use App\Models\ZumraProgramMembershipEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class ZumraProgramMembershipController
{
    public function show(Request $request, ZumraProgramConfiguration $configuration): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $membership = ZumraProgramMembership::query()->where('core_identity_reference', $identity->reference)->first();
        $charter = ZumraCharter::query()->where('status', ZumraCharter::STATUS_PUBLISHED)->latest('published_at')->first();
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();
        $receipt = $membership
            ? ZumraPaymentReceipt::query()->where('membership_id', $membership->id)->latest('issued_at')->first()
            : null;

        return view('zumra.membership', compact('identity', 'membership', 'charter', 'isAdministrator', 'receipt') + ['configuration' => $configuration->get()]);
    }

    /**
     * ZUMRA-FREE-001 — l'adhésion au Programme ZUMRA est gratuite.
     *
     * La charte reste l'engagement obligatoire et traçable. Son acceptation active immédiatement
     * l'adhésion : aucun paiement, aucun Wallet et aucun prestataire financier ne conditionne le
     * droit de créer une ZUMRA. Les anciennes lignes PENDING_PAYMENT peuvent repasser par cette
     * même action pour être activées gratuitement après acceptation de la charte publiée.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $request->validate([
            'charter_id' => ['required', 'integer'],
            'accept_charter' => ['accepted'],
        ]);
        $charter = ZumraCharter::query()
            ->whereKey($data['charter_id'])
            ->where('status', ZumraCharter::STATUS_PUBLISHED)
            ->firstOrFail();

        DB::transaction(static function () use ($identity, $charter): void {
            $existing = ZumraProgramMembership::query()
                ->where('core_identity_reference', $identity->reference)
                ->lockForUpdate()
                ->first();

            if ($existing !== null && in_array($existing->status, [
                ZumraProgramMembership::STATUS_ACTIVE,
                ZumraProgramMembership::STATUS_SUSPENDED,
                ZumraProgramMembership::STATUS_CLOSED,
            ], true)) {
                abort(409, 'Cette adhésion ne peut pas être recréée.');
            }

            $now = now();
            $membership = $existing ?? new ZumraProgramMembership([
                'core_identity_reference' => $identity->reference,
            ]);
            $fromStatus = $membership->exists ? $membership->status : null;

            $membership->fill([
                'status' => ZumraProgramMembership::STATUS_ACTIVE,
                'accepted_charter_id' => $charter->id,
                'accepted_charter_version' => $charter->version,
                'accepted_charter_hash' => $charter->content_hash,
                'charter_accepted_at' => $now,
                'submitted_at' => $membership->submitted_at ?? $now,
                'activated_at' => $now,
            ])->save();

            ZumraProgramMembershipEvent::query()->create([
                'membership_id' => $membership->id,
                'event' => 'MEMBERSHIP_ACTIVATED',
                'from_status' => $fromStatus,
                'to_status' => ZumraProgramMembership::STATUS_ACTIVE,
                'actor_core_reference' => $identity->reference,
                'context' => [
                    'charter_version' => $charter->version,
                    'charter_hash' => $charter->content_hash,
                    'activation_mode' => 'FREE_CHARTER_ACCEPTANCE',
                ],
                'occurred_at' => $now,
            ]);
        });

        return redirect()
            ->route('zumra.groups.create')
            ->with('status', 'Votre adhésion gratuite au Programme ZUMRA est active. Vous pouvez maintenant faire naître votre ZUMRA.');
    }
}
