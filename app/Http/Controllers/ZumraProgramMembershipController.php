<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Zahab\ZahabWalletService;
use App\Application\Zumra\ZumraProgramConfiguration;
use App\Domain\Identity\CoreIdentity;
use App\Models\PortalAdministrator;
use App\Models\ZahabWallet;
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
    public function show(Request $request, ZumraProgramConfiguration $configuration, ZahabWalletService $wallets): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $membership = ZumraProgramMembership::query()->where('core_identity_reference', $identity->reference)->first();
        $charter = ZumraCharter::query()->where('status', ZumraCharter::STATUS_PUBLISHED)->latest('published_at')->first();
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();
        $zahabWallet = $wallets->walletFor(ZahabWallet::SUBJECT_PERSON, $identity->reference, $identity->reference);
        $zahabBalance = $wallets->balance($zahabWallet);
        $receipt = $membership && $membership->status !== ZumraProgramMembership::STATUS_PENDING_PAYMENT
            ? ZumraPaymentReceipt::query()->where('membership_id', $membership->id)->latest('issued_at')->first()
            : null;
        $membershipAmount = (int) config('payments.membership.amount');

        return view('zumra.membership', compact('identity', 'membership', 'charter', 'isAdministrator', 'zahabBalance', 'receipt', 'membershipAmount') + ['configuration' => $configuration->get()]);
    }

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
            $existing = ZumraProgramMembership::query()->where('core_identity_reference', $identity->reference)->lockForUpdate()->first();
            if ($existing !== null && in_array($existing->status, [ZumraProgramMembership::STATUS_ACTIVE, ZumraProgramMembership::STATUS_SUSPENDED, ZumraProgramMembership::STATUS_CLOSED], true)) {
                abort(409, 'Cette adhésion ne peut pas être recréée.');
            }

            $now = now();
            $membership = $existing ?? new ZumraProgramMembership(['core_identity_reference' => $identity->reference]);
            $fromStatus = $membership->exists ? $membership->status : null;
            $membership->fill([
                'status' => ZumraProgramMembership::STATUS_PENDING_PAYMENT,
                'accepted_charter_id' => $charter->id,
                'accepted_charter_version' => $charter->version,
                'accepted_charter_hash' => $charter->content_hash,
                'charter_accepted_at' => $now,
                'submitted_at' => $membership->submitted_at ?? $now,
            ])->save();

            ZumraProgramMembershipEvent::query()->create([
                'membership_id' => $membership->id,
                'event' => $existing === null ? 'DOSSIER_SUBMITTED' : 'CHARTER_REACCEPTED',
                'from_status' => $fromStatus,
                'to_status' => $membership->status,
                'actor_core_reference' => $identity->reference,
                'context' => ['charter_version' => $charter->version, 'charter_hash' => $charter->content_hash],
                'occurred_at' => $now,
            ]);
        });

        return redirect()->route('zumra.membership.show')->with('status', 'Votre dossier d’adhésion est préparé. Aucun paiement n’a été effectué.');
    }
}
