<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Contributions\ContributionConfiguration;
use App\Application\Contributions\ContributionService;
use App\Application\Zahab\ZahabWalletService;
use App\Domain\Identity\CoreIdentity;
use App\Models\Contribution;
use App\Models\ContributionPayment;
use App\Models\ContributionPurpose;
use App\Models\ContributionReceipt;
use App\Models\PortalAdministrator;
use App\Models\ZahabWallet;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
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

    /**
     * CONTRIBUTION-ZAHAB-001, art. 13 du mandat — surface minimale, jamais un chantier esthétique :
     * CAP-061 était un backend réel mais totalement injoignable (audit CORE-COMPLETION-001). Cette
     * page rend le flux réellement utilisable (montant, finalité, Wallet ZAHAB utilisé, solde
     * disponible, bouton, confirmation, reçu) sans dupliquer `index()` (JSON, inchangé).
     */
    public function dashboard(Request $request, ZahabWalletService $wallets, ContributionConfiguration $configuration): View
    {
        $actor = $this->actor($request);
        $isAdministrator = PortalAdministrator::query()->whereKey($actor)->exists();
        $currentPeriod = now()->format('Y-m');
        $settings = $configuration->get();
        $purposes = ContributionPurpose::query()->where('status', ContributionPurpose::STATUS_ACTIVE)->orderBy('label')->get();

        $individual = Contribution::query()->where('type', Contribution::TYPE_INDIVIDUAL)->where('subject_reference', $actor)->first();
        $individualWallet = $wallets->walletFor(ZahabWallet::SUBJECT_PERSON, $actor, $actor);
        $individualBalance = $wallets->balance($individualWallet);
        $individualPaidThisPeriod = $individual !== null && ContributionPayment::query()
            ->where('contribution_id', $individual->id)->where('period', $currentPeriod)
            ->whereIn('status', [ContributionPayment::STATUS_PENDING, ContributionPayment::STATUS_PROCESSING, ContributionPayment::STATUS_COMPLETED])
            ->exists();

        $leaderRoles = ZumraGroupRole::query()
            ->where('core_identity_reference', $actor)->where('status', ZumraGroupRole::STATUS_ACCEPTED)
            ->get(['zumra_group_id', 'role'])->groupBy('zumra_group_id');
        $groups = ZumraGroup::query()->whereIn('id', $leaderRoles->keys())->get()->keyBy('id');
        $collectiveContributions = Contribution::query()
            ->where('type', Contribution::TYPE_COLLECTIVE)->whereIn('subject_reference', $leaderRoles->keys())->get()->keyBy('subject_reference');

        $collectives = $leaderRoles->map(function ($roles, string $groupId) use ($groups, $collectiveContributions, $wallets, $currentPeriod): ?array {
            $group = $groups->get($groupId);
            if ($group === null) {
                return null;
            }
            $roleNames = $roles->pluck('role')->all();
            $contribution = $collectiveContributions->get($groupId);
            $wallet = $wallets->walletFor(ZahabWallet::SUBJECT_ZUMRA_GROUP, $groupId, $roleNames[0] ?? '');

            return [
                'group' => $group,
                'contribution' => $contribution,
                'can_propose_or_approve' => count(array_intersect($roleNames, ['PRIMARY_LEAD', 'FINANCE_LEAD'])) > 0,
                'balance' => $wallets->balance($wallet),
                'paid_this_period' => $contribution !== null && ContributionPayment::query()
                    ->where('contribution_id', $contribution->id)->where('period', $currentPeriod)
                    ->whereIn('status', [ContributionPayment::STATUS_PENDING, ContributionPayment::STATUS_PROCESSING, ContributionPayment::STATUS_COMPLETED])
                    ->exists(),
            ];
        })->filter()->values();

        return view('contributions.dashboard', [
            'identity' => $request->attributes->get('dg_identity'),
            'isAdministrator' => $isAdministrator,
            'currentPeriod' => $currentPeriod,
            'settings' => $settings,
            'purposes' => $purposes,
            'individual' => $individual,
            'individualBalance' => $individualBalance,
            'individualPaidThisPeriod' => $individualPaidThisPeriod,
            'collectives' => $collectives,
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

        $returnToken = Str::random(64);
        $returnUrlExpiresAt = now()->addMinutes((int) config('payments.geniuspay.return_url_ttl_minutes', 1440));
        $payment = $service->payPeriod(
            $contribution,
            $this->actor($request),
            $data['period'],
            $data['purpose_code'],
            URL::temporarySignedRoute('contributions.payment.return', $returnUrlExpiresAt, ['contribution' => $contribution, 'attempt' => $returnToken, 'outcome' => 'success']),
            URL::temporarySignedRoute('contributions.payment.return', $returnUrlExpiresAt, ['contribution' => $contribution, 'attempt' => $returnToken, 'outcome' => 'error']),
            hash('sha256', $returnToken),
        );

        return redirect()->away((string) $payment->checkout_url);
    }

    /**
     * CONTRIBUTION-ZAHAB-001 — même déclencheur métier que `pay()`, réglé immédiatement par le
     * Wallet ZAHAB du sujet plutôt qu'un checkout externe : jamais de redirection navigateur,
     * aucune route générique de mutation Wallet (`payPeriodWithZahabWallet()` porte lui-même la
     * légitimité du débit).
     */
    public function payWithZahab(Request $request, Contribution $contribution, ContributionService $service): RedirectResponse
    {
        $data = $request->validate([
            'period' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'purpose_code' => ['required', 'string', Rule::exists('dg_contribution_purposes', 'code')],
        ]);

        $payment = $service->payPeriodWithZahabWallet($contribution, $this->actor($request), $data['period'], $data['purpose_code']);

        return back()->with('status', 'Contribution réglée avec votre Wallet ZAHAB.')->with('contribution_payment', $payment->reference);
    }

    public function returned(Request $request, Contribution $contribution, ContributionService $service): JsonResponse
    {
        $actor = $this->actor($request);
        $this->assertCanViewContribution($contribution, $actor, $service);

        $returnToken = (string) $request->query('attempt', '');
        abort_unless((bool) preg_match('/^[A-Za-z0-9]{64}$/', $returnToken), 404);
        $payment = ContributionPayment::query()
            ->where('contribution_id', $contribution->id)
            ->where('return_token_hash', hash('sha256', $returnToken))
            ->firstOrFail();
        $verificationUnavailable = false;
        if (in_array($payment->status, [ContributionPayment::STATUS_PENDING, ContributionPayment::STATUS_PROCESSING], true)) {
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
        $receipt = ContributionReceipt::query()->where('payment_id', $payment->id)->first();

        return response()->json([
            'payment' => [
                'reference' => $payment->reference,
                'status' => $payment->status,
                'period' => $payment->period,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
            ],
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
