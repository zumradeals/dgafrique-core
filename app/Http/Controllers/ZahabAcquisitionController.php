<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Zahab\ZahabAcquisitionService;
use App\Domain\Identity\CoreIdentity;
use App\Models\ZahabAcquisition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * ZAHAB-002 — exposition minimale, jamais un portail financier : redirections vers/depuis
 * GeniusPay uniquement, jamais de route générique `wallet/credit`. Le retour navigateur
 * (`returned()`) ne fait jamais confiance à `?outcome=success` — seule la réconciliation
 * server-to-server (`reconcile()`) peut créditer un Wallet (art. 3/8 du mandat).
 */
final class ZahabAcquisitionController
{
    public function store(Request $request, ZahabAcquisitionService $acquisitions): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
        ]);
        $actor = $this->actor($request);

        try {
            $acquisition = $acquisitions->start(
                $actor,
                (int) $data['amount'],
                route('zahab.acquisitions.return', ['outcome' => 'success']),
                route('zahab.acquisitions.return', ['outcome' => 'error']),
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['amount' => 'Le service de paiement est momentanément indisponible. Aucun débit n’a été déclenché.']);
        }

        return redirect()->away((string) $acquisition->checkout_url);
    }

    /**
     * Comme `ZumraMembershipPaymentController::returned()` : aucun identifiant d'acquisition dans
     * l'URL (elle n'existe pas encore au moment où `store()` construit l'URL de retour) — on relit
     * la tentative la plus récente de cet acteur, jamais une preuve fournie par le navigateur.
     */
    public function returned(Request $request, ZahabAcquisitionService $acquisitions): RedirectResponse
    {
        $actor = $this->actor($request);
        $acquisition = ZahabAcquisition::query()->where('person_core_reference', $actor)->latest('created_at')->first();
        abort_if($acquisition === null, 404);

        if (in_array($acquisition->status, [ZahabAcquisition::STATUS_PENDING, ZahabAcquisition::STATUS_PROCESSING], true)) {
            try {
                $acquisitions->reconcile($acquisition);
            } catch (Throwable $exception) {
                if (app()->runningUnitTests()) {
                    throw $exception;
                }
                report($exception);

                return redirect()->route('zahab.wallet.dashboard')->with('status', 'Vérification du paiement momentanément indisponible. Votre historique se mettra à jour dès que possible.');
            }
        }

        return redirect()->route('zahab.wallet.dashboard')->with('status', 'Retour de GeniusPay reçu. Votre historique reflète l’état réellement confirmé du paiement.');
    }

    private function actor(Request $request): string
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        return $identity->reference;
    }
}
