<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Zumra\MembershipPaymentService;
use App\Domain\Identity\CoreIdentity;
use App\Models\ZumraPayment;
use App\Models\ZumraPaymentReceipt;
use App\Models\ZumraProgramMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

final class ZumraMembershipPaymentController
{
    public function store(Request $request, MembershipPaymentService $payments): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $membership = ZumraProgramMembership::query()->where('core_identity_reference', $identity->reference)->firstOrFail();

        try {
            $payment = $payments->start(
                $membership,
                route('zumra.payment.return', ['outcome' => 'success']),
                route('zumra.payment.return', ['outcome' => 'error']),
            );
        } catch (Throwable $exception) {
            report($exception);
            return back()->withErrors(['payment' => 'Le service de paiement est momentanément indisponible. Aucun débit n’a été déclenché.']);
        }

        return redirect()->away((string) $payment->checkout_url);
    }

    public function returned(Request $request, MembershipPaymentService $payments): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $membership = ZumraProgramMembership::query()->where('core_identity_reference', $identity->reference)->firstOrFail();
        $payment = ZumraPayment::query()->where('membership_id', $membership->id)->latest()->first();
        $verificationUnavailable = false;
        if ($payment && $membership->status === ZumraProgramMembership::STATUS_PENDING_PAYMENT) {
            try {
                $payment = $payments->reconcile($payment);
            } catch (Throwable $exception) {
                // En production, ne jamais divulguer un incident financier au navigateur.
                // En test, l'exception doit rester visible afin qu'aucune régression
                // d'activation ou de reçu ne puisse être masquée par l'interface sûre.
                if (app()->runningUnitTests()) {
                    throw $exception;
                }
                report($exception);
                $verificationUnavailable = true;
            }
            $membership->refresh();
        }
        $receipt = $payment ? ZumraPaymentReceipt::query()->where('payment_id', $payment->id)->first() : null;

        return view('zumra.payment-status', compact('membership', 'payment', 'receipt', 'verificationUnavailable'));
    }

    public function receipt(Request $request, ZumraPaymentReceipt $receipt): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        abort_unless(hash_equals($identity->reference, $receipt->core_identity_reference), 404);

        return view('zumra.receipt', compact('receipt'));
    }
}
