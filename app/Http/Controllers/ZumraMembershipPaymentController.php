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
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

final class ZumraMembershipPaymentController
{
    public function store(Request $request, MembershipPaymentService $payments): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $membership = ZumraProgramMembership::query()->where('core_identity_reference', $identity->reference)->firstOrFail();
        $returnToken = Str::random(64);
        $returnUrlExpiresAt = now()->addMinutes((int) config('payments.geniuspay.return_url_ttl_minutes', 1440));

        try {
            $payment = $payments->start(
                $membership,
                URL::temporarySignedRoute('zumra.payment.return', $returnUrlExpiresAt, ['attempt' => $returnToken, 'outcome' => 'success']),
                URL::temporarySignedRoute('zumra.payment.return', $returnUrlExpiresAt, ['attempt' => $returnToken, 'outcome' => 'error']),
                hash('sha256', $returnToken),
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['payment' => 'Le service de paiement est momentanément indisponible. Aucun débit n’a été déclenché.']);
        }

        return redirect()->away((string) $payment->checkout_url);
    }

    /**
     * ADHESION-ZAHAB-001 — même déclencheur métier que `store()`, réglé immédiatement par le
     * Wallet ZAHAB du membre plutôt qu'un checkout externe : jamais de redirection navigateur,
     * aucune route générique de mutation Wallet (`payWithZahabWallet()` porte elle-même la
     * légitimité du débit).
     */
    public function payWithZahab(Request $request, MembershipPaymentService $payments): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $membership = ZumraProgramMembership::query()->where('core_identity_reference', $identity->reference)->firstOrFail();

        $payments->payWithZahabWallet($membership, $identity->reference);

        return redirect()->route('zumra.membership.show')->with('status', 'Adhésion réglée avec votre Wallet ZAHAB.');
    }

    public function returned(Request $request, MembershipPaymentService $payments): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $membership = ZumraProgramMembership::query()->where('core_identity_reference', $identity->reference)->firstOrFail();
        $returnToken = (string) $request->query('attempt', '');
        abort_unless((bool) preg_match('/^[A-Za-z0-9]{64}$/', $returnToken), 404);
        $payment = ZumraPayment::query()
            ->where('membership_id', $membership->id)
            ->where('return_token_hash', hash('sha256', $returnToken))
            ->firstOrFail();
        $verificationUnavailable = false;
        if ($membership->status === ZumraProgramMembership::STATUS_PENDING_PAYMENT) {
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
        $receipt = ZumraPaymentReceipt::query()->where('payment_id', $payment->id)->first();

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
