<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Contributions\ContributionService;
use App\Domain\Identity\CoreIdentity;
use App\Models\Contribution;
use App\Models\ContributionPayment;
use App\Models\ContributionReceipt;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * CAP-061 — exposition minimale : aucun portail financier. JSON pour la consultation, redirections
 * pour les actions, retour navigateur jamais traité comme une preuve de paiement.
 */
final class ContributionController
{
    public function index(Request $request): JsonResponse
    {
        $actor = $this->actor($request);
        $individual = Contribution::query()->where('type', Contribution::TYPE_INDIVIDUAL)->where('subject_reference', $actor)->first();
        $collectiveIds = ZumraGroupRole::query()->where('core_identity_reference', $actor)->where('status', ZumraGroupRole::STATUS_ACCEPTED)->pluck('zumra_group_id');
        $collectives = Contribution::query()->where('type', Contribution::TYPE_COLLECTIVE)->whereIn('subject_reference', $collectiveIds)->get();

        return response()->json([
            'individual' => $individual ? $this->present($individual) : null,
            'collectives' => $collectives->map(fn (Contribution $c): array => $this->present($c))->values(),
        ]);
    }

    public function startIndividual(Request $request, ContributionService $service): RedirectResponse
    {
        $contribution = $service->startIndividual($this->actor($request));

        return redirect()->route('contributions.index')->with('status', 'Votre contribution individuelle est active. Elle reste entièrement facultative.')->with('contribution', $contribution->public_reference);
    }

    public function proposeCollective(Request $request, ZumraGroup $group, ContributionService $service): RedirectResponse
    {
        $service->proposeCollective($group, $this->actor($request));

        return back()->with('status', 'Engagement de contribution collective proposé. Il attend l’approbation de l’autre autorité.');
    }

    public function approveCollective(Request $request, ZumraGroup $group, ContributionService $service): RedirectResponse
    {
        $contribution = Contribution::query()->where('type', Contribution::TYPE_COLLECTIVE)->where('subject_reference', $group->id)->firstOrFail();
        $service->approveCollective($contribution, $this->actor($request));

        return back()->with('status', 'Engagement de contribution collective approuvé et actif.');
    }

    public function pause(Request $request, Contribution $contribution, ContributionService $service): RedirectResponse
    {
        $service->pause($contribution, $this->actor($request));

        return back()->with('status', 'Contribution mise en pause.');
    }

    public function resume(Request $request, Contribution $contribution, ContributionService $service): RedirectResponse
    {
        $service->resume($contribution, $this->actor($request));

        return back()->with('status', 'Contribution reprise.');
    }

    public function stop(Request $request, Contribution $contribution, ContributionService $service): RedirectResponse
    {
        $service->stop($contribution, $this->actor($request));

        return back()->with('status', 'Contribution arrêtée. Son historique reste conservé.');
    }

    public function pay(Request $request, Contribution $contribution, ContributionService $service): RedirectResponse
    {
        $data = $request->validate([
            'period' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'purpose_code' => ['required', 'string', Rule::exists('dg_contribution_purposes', 'code')],
        ]);

        // La référence du paiement n'existe qu'après sa création chez le prestataire : comme
        // CAP-007B, le retour navigateur identifie l'ENGAGEMENT (connu à l'avance), jamais le
        // paiement — returned() relit ensuite la tentative la plus récente de cet engagement.
        $payment = $service->payPeriod(
            $contribution,
            $this->actor($request),
            $data['period'],
            $data['purpose_code'],
            route('contributions.payment.return', ['contribution' => $contribution, 'outcome' => 'success']),
            route('contributions.payment.return', ['contribution' => $contribution, 'outcome' => 'error']),
        );

        return redirect()->away((string) $payment->checkout_url);
    }

    public function returned(Request $request, Contribution $contribution, ContributionService $service): JsonResponse
    {
        $actor = $this->actor($request);
        $this->assertCanViewContribution($contribution, $actor, $service);

        $payment = ContributionPayment::query()->where('contribution_id', $contribution->id)->latest('created_at')->first();
        $verificationUnavailable = false;
        if ($payment && in_array($payment->status, [ContributionPayment::STATUS_PENDING, ContributionPayment::STATUS_PROCESSING], true)) {
            try {
                $payment = $service->reconcile($payment);
            } catch (Throwable $exception) {
                if (app()->runningUnitTests()) {
                    throw $exception;
                }
                report($exception);
                $verificationUnavailable = true;
            }
        }
        $receipt = $payment ? ContributionReceipt::query()->where('payment_id', $payment->id)->first() : null;

        return response()->json([
            'payment' => $payment ? [
                'reference' => $payment->reference,
                'status' => $payment->status,
                'period' => $payment->period,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
            ] : null,
            'receipt_reference' => $receipt?->id,
            'verification_unavailable' => $verificationUnavailable,
        ]);
    }

    public function receipt(Request $request, ContributionReceipt $receipt, ContributionService $service): JsonResponse
    {
        $actor = $this->actor($request);
        $contribution = Contribution::query()->findOrFail($receipt->contribution_id);
        $this->assertCanViewContribution($contribution, $actor, $service);

        return response()->json([
            'number' => $receipt->number,
            'reference' => $receipt->provider_reference,
            'amount' => $receipt->amount,
            'currency' => $receipt->currency,
            'period' => $receipt->period,
            'purpose_code' => $receipt->purpose_code,
            'issued_at' => $receipt->issued_at,
        ]);
    }

    private function assertCanViewContribution(Contribution $contribution, string $actor, ContributionService $service): void
    {
        if ($contribution->type === Contribution::TYPE_INDIVIDUAL) {
            abort_unless(hash_equals($contribution->subject_reference, $actor), 404);

            return;
        }
        abort_unless($service->isLeader(ZumraGroup::query()->findOrFail($contribution->subject_reference), $actor), 404);
    }

    private function actor(Request $request): string
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        return $identity->reference;
    }

    private function present(Contribution $contribution): array
    {
        return [
            'reference' => $contribution->public_reference,
            'type' => $contribution->type,
            'status' => $contribution->status,
            'proposed_role' => $contribution->proposed_role,
            'approved_role' => $contribution->approved_role,
        ];
    }
}
