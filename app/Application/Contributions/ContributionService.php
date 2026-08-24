<?php

declare(strict_types=1);

namespace App\Application\Contributions;

use App\Application\Ledger\LedgerService;
use App\Application\Zahab\ZahabWalletService;
use App\Application\Zumra\ZumraGroupService;
use App\Infrastructure\Payments\GeniusPayClient;
use App\Models\Contribution;
use App\Models\ContributionEvent;
use App\Models\ContributionPayment;
use App\Models\ContributionPurpose;
use App\Models\ContributionReceipt;
use App\Models\ZahabWallet;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupRole;
use App\Models\ZumraProgramMembership;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * CAP-061 — art. 6 (ZUMRA-DOCTRINE-INVARIANTE.md) : contributions volontaires, jamais une dette,
 * jamais un score, jamais un investissement. Contribution porte l'ENGAGEMENT ; ContributionPayment
 * porte chaque TENTATIVE mensuelle — deux vérités jamais confondues. Ce service ne modifie JAMAIS
 * ZumraGroup.state ni ZumraGroup.maturity : les machines d'état finance et gouvernance restent
 * strictement indépendantes (art. 6.3 : « aucune suspension automatique de la ZUMRA »).
 */
final class ContributionService
{
    private const COLLECTIVE_AUTHORITY_ROLES = ['PRIMARY_LEAD', 'FINANCE_LEAD'];

    private const ZUMRA_ELIGIBLE_STATES = [
        ZumraGroup::STATE_VALIDATED,
        ZumraGroup::STATE_ACTIVE,
        ZumraGroup::STATE_WARNED,
        ZumraGroup::STATE_REHABILITATING,
    ];

    public function __construct(
        private readonly ZumraGroupService $zumraGroups,
        private readonly GeniusPayClient $provider,
        private readonly ContributionConfiguration $configuration,
        private readonly LedgerService $ledger,
        private readonly ZahabWalletService $wallets,
    ) {}

    /** Contribution individuelle (art. 6.2) : sujet = payeur = la personne elle-même. */
    public function startIndividual(string $actor): Contribution
    {
        $this->assertActiveProgramMember($actor);
        abort_if(
            Contribution::query()->where('type', Contribution::TYPE_INDIVIDUAL)->where('subject_reference', $actor)->exists(),
            409,
            'Vous avez déjà un engagement de contribution individuelle.'
        );

        return DB::transaction(function () use ($actor): Contribution {
            $contribution = Contribution::query()->create([
                'public_reference' => (string) Str::uuid(),
                'type' => Contribution::TYPE_INDIVIDUAL,
                'subject_type' => Contribution::SUBJECT_PERSON,
                'subject_reference' => $actor,
                'status' => Contribution::STATUS_ACTIVE,
                'initiated_by_core_reference' => $actor,
            ]);
            $this->event($contribution, 'CONTRIBUTION_STARTED', $actor);

            return $contribution;
        });
    }

    /**
     * Contribution collective (art. 6.3) : le premier responsable ou le responsable financier
     * propose l'engagement. L'autre doit l'approuver (approveCollective()) — jamais la même
     * personne, jamais le même rôle deux fois.
     */
    public function proposeCollective(ZumraGroup $group, string $actor): Contribution
    {
        $this->assertZumraEligible($group);
        $role = $this->assertCollectiveAuthorityRole($group, $actor);
        abort_if(
            Contribution::query()->where('type', Contribution::TYPE_COLLECTIVE)->where('subject_reference', $group->id)->exists(),
            409,
            'Cette ZUMRA a déjà un engagement de contribution collective.'
        );

        return DB::transaction(function () use ($group, $actor, $role): Contribution {
            $contribution = Contribution::query()->create([
                'public_reference' => (string) Str::uuid(),
                'type' => Contribution::TYPE_COLLECTIVE,
                'subject_type' => Contribution::SUBJECT_ZUMRA_GROUP,
                'subject_reference' => $group->id,
                'status' => Contribution::STATUS_PROPOSED,
                'initiated_by_core_reference' => $actor,
                'proposed_by_core_reference' => $actor,
                'proposed_role' => $role,
                'proposed_at' => now(),
            ]);
            $this->event($contribution, 'CONTRIBUTION_PROPOSED', $actor, ['role' => $role]);

            return $contribution;
        });
    }

    /** L'autre autorité (rôle différent, personne différente) approuve l'engagement proposé. */
    public function approveCollective(Contribution $contribution, string $actor): Contribution
    {
        abort_unless($contribution->type === Contribution::TYPE_COLLECTIVE, 422, 'Cet engagement n’est pas collectif.');
        $group = ZumraGroup::query()->findOrFail($contribution->subject_reference);
        $this->assertZumraEligible($group);
        $role = $this->assertCollectiveAuthorityRole($group, $actor);

        return DB::transaction(function () use ($contribution, $actor, $role): Contribution {
            $fresh = Contribution::query()->whereKey($contribution->id)->lockForUpdate()->firstOrFail();
            abort_unless($fresh->status === Contribution::STATUS_PROPOSED, 409, 'Cet engagement n’est plus en attente d’approbation.');
            abort_if(hash_equals((string) $fresh->proposed_by_core_reference, $actor), 403, 'La même personne ne peut pas proposer et approuver l’engagement.');
            abort_if($fresh->proposed_role === $role, 422, 'L’approbation doit provenir de l’autre autorité que celle qui a proposé.');

            $fresh->update(['status' => Contribution::STATUS_ACTIVE, 'approved_by_core_reference' => $actor, 'approved_role' => $role, 'approved_at' => now()]);
            $this->event($fresh, 'CONTRIBUTION_APPROVED', $actor, ['role' => $role]);

            return $fresh;
        });
    }

    public function pause(Contribution $contribution, string $actor): Contribution
    {
        $this->assertSubjectAuthority($contribution, $actor);

        return DB::transaction(function () use ($contribution, $actor): Contribution {
            $fresh = Contribution::query()->whereKey($contribution->id)->lockForUpdate()->firstOrFail();
            abort_unless($fresh->status === Contribution::STATUS_ACTIVE, 409, 'Seul un engagement actif peut être mis en pause.');

            $fresh->update(['status' => Contribution::STATUS_PAUSED, 'paused_at' => now()]);
            $this->event($fresh, 'CONTRIBUTION_PAUSED', $actor);

            return $fresh;
        });
    }

    /** Reprend un engagement en pause OU réactive un engagement arrêté — jamais un doublon, jamais de perte d'historique. */
    public function resume(Contribution $contribution, string $actor): Contribution
    {
        $this->assertSubjectAuthority($contribution, $actor);

        return DB::transaction(function () use ($contribution, $actor): Contribution {
            $fresh = Contribution::query()->whereKey($contribution->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($fresh->status, [Contribution::STATUS_PAUSED, Contribution::STATUS_STOPPED], true), 409, 'Seul un engagement en pause ou arrêté peut reprendre.');

            $fresh->update(['status' => Contribution::STATUS_ACTIVE, 'paused_at' => null, 'stopped_at' => null, 'resumed_at' => now()]);
            $this->event($fresh, 'CONTRIBUTION_RESUMED', $actor);

            return $fresh;
        });
    }

    public function stop(Contribution $contribution, string $actor): Contribution
    {
        $this->assertSubjectAuthority($contribution, $actor);

        return DB::transaction(function () use ($contribution, $actor): Contribution {
            $fresh = Contribution::query()->whereKey($contribution->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($fresh->status, [Contribution::STATUS_ACTIVE, Contribution::STATUS_PAUSED], true), 409, 'Seul un engagement actif ou en pause peut être arrêté.');

            $fresh->update(['status' => Contribution::STATUS_STOPPED, 'stopped_at' => now()]);
            $this->event($fresh, 'CONTRIBUTION_STOPPED', $actor);

            return $fresh;
        });
    }

    /**
     * Déclenche une tentative de paiement volontaire pour une période AAAA-MM. Aucun prélèvement
     * automatique : une personne réelle initie explicitement chaque mois (art. 6.2/6.3).
     */
    public function payPeriod(Contribution $contribution, string $actor, string $period, string $purposeCode, string $successUrl, string $errorUrl): ContributionPayment
    {
        abort_unless((bool) preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period), 422, 'Période invalide (format AAAA-MM attendu).');
        abort_unless($contribution->status === Contribution::STATUS_ACTIVE, 409, 'Seul un engagement actif peut recevoir un paiement.');

        if ($contribution->type === Contribution::TYPE_COLLECTIVE) {
            $group = ZumraGroup::query()->findOrFail($contribution->subject_reference);
            abort_if($group->state === ZumraGroup::STATE_SUSPENDED, 409, 'Une ZUMRA suspendue ne peut pas initier un nouveau paiement collectif.');
            abort_unless($this->zumraGroups->isLeader($group, $actor), 403, 'Seul un responsable habilité de la ZUMRA peut initier le paiement du mois.');
        } else {
            abort_unless(hash_equals($contribution->subject_reference, $actor), 403, 'Seule la personne titulaire peut initier son paiement.');
        }

        $purpose = ContributionPurpose::query()->where('code', $purposeCode)->where('status', ContributionPurpose::STATUS_ACTIVE)->first();
        abort_if($purpose === null, 422, 'Finalité inconnue ou désactivée.');

        abort_if(
            ContributionPayment::query()->where('contribution_id', $contribution->id)->where('period', $period)
                ->whereIn('status', [ContributionPayment::STATUS_PENDING, ContributionPayment::STATUS_PROCESSING, ContributionPayment::STATUS_COMPLETED])
                ->exists(),
            409,
            'Un paiement actif ou confirmé existe déjà pour cette période.'
        );

        $settings = $this->configuration->get();
        $isIndividual = $contribution->type === Contribution::TYPE_INDIVIDUAL;
        abort_unless((bool) $settings[$isIndividual ? 'individual_enabled' : 'collective_enabled'], 409, 'Les paiements de contribution ne sont pas encore ouverts.');
        $amount = $isIndividual ? (int) $settings['individual_amount'] : (int) $settings['collective_amount'];
        $currency = (string) $settings['currency'];
        $environment = (string) config('payments.geniuspay.environment');

        $remote = $this->provider->createContributionPayment(
            $amount,
            $currency,
            'Contribution '.($contribution->type === Contribution::TYPE_INDIVIDUAL ? 'individuelle' : 'collective').' — '.$period,
            ['contribution_reference' => $contribution->public_reference, 'period' => $period, 'purpose' => $purposeCode],
            $successUrl,
            $errorUrl,
        );
        if ($remote['amount'] !== $amount || $remote['environment'] !== $environment || ! $remote['checkout_url']
            || parse_url((string) $remote['checkout_url'], PHP_URL_SCHEME) !== 'https') {
            throw new RuntimeException('CONTRIBUTION_PAYMENT_CREATION_MISMATCH');
        }

        try {
            return DB::transaction(function () use ($contribution, $actor, $period, $purpose, $amount, $currency, $environment, $remote): ContributionPayment {
                $payment = ContributionPayment::query()->create([
                    'contribution_id' => $contribution->id,
                    'period' => $period,
                    'purpose_id' => $purpose->id,
                    'initiated_by_core_reference' => $actor,
                    'provider' => 'GENIUSPAY',
                    'reference' => $remote['reference'],
                    'provider_id' => $remote['provider_id'],
                    'amount' => $amount,
                    'currency' => $currency,
                    'environment' => $environment,
                    'status' => $remote['status'],
                    'checkout_url' => $remote['checkout_url'],
                    'provider_snapshot' => $remote['snapshot'],
                    'provider_snapshot_hash' => $this->snapshotHash($remote['snapshot']),
                ]);
                $this->event($contribution, 'PAYMENT_STARTED', $actor, ['period' => $period, 'reference' => $payment->reference]);

                return $payment;
            });
        } catch (QueryException $exception) {
            // Filet de sécurité de l'index unique partiel (concurrence réelle échappant à la
            // vérification applicative ci-dessus) — jamais un 500 sur une simple course bénigne.
            if ((string) $exception->getCode() === '23505') {
                abort(409, 'Un paiement actif ou confirmé existe déjà pour cette période.');
            }
            throw $exception;
        }
    }

    /**
     * CONTRIBUTION-ZAHAB-001 — même déclenchement que `payPeriod()` (mêmes vérifications
     * d'autorité, de période, d'engagement ACTIF, de finalité active, du même interrupteur
     * `individual_enabled`/`collective_enabled`), mais réglé immédiatement par le Wallet ZAHAB du
     * sujet au lieu d'un checkout GeniusPay externe : aucune étape PENDING/PROCESSING, aucune
     * réconciliation à part — le débit ZAHAB EST la confirmation, synchrone et atomique.
     *
     * CAP-061 ne modélise actuellement AUCUN bénéficiaire Wallet-éligible pour une contribution
     * (`purpose_code` reste un code doctrinal de destination — TRAINING, SOLIDARITY... — jamais un
     * Projet ou une Organisation financés réellement, confirmé par l'audit CORE-COMPLETION-001).
     * V1 n'enregistre donc qu'UN mouvement : le DEBIT du Wallet du contributeur (Personne pour une
     * contribution individuelle, ZUMRA pour une collective). Aucun Wallet DG Afrique/Projet n'est
     * fabriqué pour équilibrer visuellement l'opération.
     *
     * `zahab_operation_reference`/la clé de mouvement sont dérivées déterministiquement de
     * `(contribution, période)` — jamais un UUID aléatoire — afin qu'un double clic, un retry HTTP
     * ou un rejeu métier retombe sur la même identité et ne débite jamais deux fois : la contrainte
     * UNIQUE(reference) et l'index partiel actif-par-période de `dg_contribution_payments`
     * (inchangés, réutilisés tels quels) protègent le côté Contribution ; l'idempotence de
     * `ZahabWalletService::debit()` protège le côté Wallet/Ledger, indépendamment.
     */
    public function payPeriodWithZahabWallet(Contribution $contribution, string $actor, string $period, string $purposeCode): ContributionPayment
    {
        abort_unless((bool) preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period), 422, 'Période invalide (format AAAA-MM attendu).');
        abort_unless($contribution->status === Contribution::STATUS_ACTIVE, 409, 'Seul un engagement actif peut recevoir un paiement.');

        $isIndividual = $contribution->type === Contribution::TYPE_INDIVIDUAL;
        if ($isIndividual) {
            abort_unless(hash_equals($contribution->subject_reference, $actor), 403, 'Seule la personne titulaire peut initier son paiement.');
            $walletSubjectType = ZahabWallet::SUBJECT_PERSON;
        } else {
            $group = ZumraGroup::query()->findOrFail($contribution->subject_reference);
            abort_if($group->state === ZumraGroup::STATE_SUSPENDED, 409, 'Une ZUMRA suspendue ne peut pas initier un nouveau paiement collectif.');
            abort_unless($this->zumraGroups->isLeader($group, $actor), 403, 'Seul un responsable habilité de la ZUMRA peut initier le paiement du mois.');
            $walletSubjectType = ZahabWallet::SUBJECT_ZUMRA_GROUP;
        }

        $purpose = ContributionPurpose::query()->where('code', $purposeCode)->where('status', ContributionPurpose::STATUS_ACTIVE)->first();
        abort_if($purpose === null, 422, 'Finalité inconnue ou désactivée.');

        abort_if(
            ContributionPayment::query()->where('contribution_id', $contribution->id)->where('period', $period)
                ->whereIn('status', [ContributionPayment::STATUS_PENDING, ContributionPayment::STATUS_PROCESSING, ContributionPayment::STATUS_COMPLETED])
                ->exists(),
            409,
            'Un paiement actif ou confirmé existe déjà pour cette période.'
        );

        $settings = $this->configuration->get();
        abort_unless((bool) $settings[$isIndividual ? 'individual_enabled' : 'collective_enabled'], 409, 'Les paiements de contribution ne sont pas encore ouverts.');
        $amount = $isIndividual ? (int) $settings['individual_amount'] : (int) $settings['collective_amount'];
        $currency = (string) $settings['currency'];
        abort_unless($currency === ZahabWalletService::CURRENCY, 422, 'Les contributions réglées en ZAHAB ne sont possibles qu’en XOF.');

        // Déterministe : jamais un UUID aléatoire (art. 8 du mandat) — un rejeu retombe toujours
        // sur la même référence, protégé par UNIQUE(reference) ci-dessous et par l'idempotence
        // propre de ZahabWalletService::debit() côté Wallet/Ledger.
        $reference = 'ZAHAB-'.$contribution->id.'-'.$period;
        $operationReference = 'contribution:'.$contribution->public_reference.':'.$period;
        $movementKey = $operationReference.':contributor-debit';

        try {
            return DB::transaction(function () use ($contribution, $actor, $period, $purpose, $amount, $currency, $reference, $walletSubjectType, $operationReference, $movementKey, $isIndividual): ContributionPayment {
                $wallet = $this->wallets->walletFor($walletSubjectType, $contribution->subject_reference, $actor);

                $payment = ContributionPayment::query()->create([
                    'contribution_id' => $contribution->id,
                    'period' => $period,
                    'purpose_id' => $purpose->id,
                    'initiated_by_core_reference' => $actor,
                    'provider' => 'ZAHAB',
                    'reference' => $reference,
                    'provider_id' => null,
                    'amount' => $amount,
                    'currency' => $currency,
                    'environment' => 'zahab',
                    'status' => ContributionPayment::STATUS_COMPLETED,
                    'checkout_url' => null,
                    'completed_at' => now(),
                ]);
                $this->event($contribution, 'PAYMENT_STARTED', $actor, ['period' => $period, 'reference' => $payment->reference, 'provider' => 'ZAHAB']);

                $this->wallets->debit(
                    $wallet,
                    $amount,
                    $isIndividual ? ZahabWalletService::REASON_INDIVIDUAL_CONTRIBUTION : ZahabWalletService::REASON_COLLECTIVE_CONTRIBUTION,
                    $movementKey,
                    $actor,
                    $operationReference,
                );

                $this->issueReceipt($payment, $contribution);
                $this->event($contribution, 'PAYMENT_CONFIRMED', $actor, ['period' => $period, 'reference' => $payment->reference, 'provider' => 'ZAHAB']);

                return $payment;
            });
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23505') {
                abort(409, 'Un paiement actif ou confirmé existe déjà pour cette période.');
            }
            throw $exception;
        }
    }

    /**
     * Réconciliation serveur-à-serveur — jamais le retour navigateur. Ne vérifie que les champs
     * réellement renvoyés par GeniusPay (payment() ne renvoie pas de devise ni de finalité : la
     * devise et la finalité restent des vérifications LOCALES, jamais une comparaison distante
     * inventée).
     */
    public function reconcile(ContributionPayment $payment): ContributionPayment
    {
        $remote = $this->provider->payment($payment->reference);
        if ($remote['amount'] !== $payment->amount || $payment->currency !== 'XOF' || $remote['environment'] !== $payment->environment) {
            throw new RuntimeException('CONTRIBUTION_PAYMENT_RECONCILIATION_MISMATCH');
        }

        return DB::transaction(function () use ($payment, $remote): ContributionPayment {
            $locked = ContributionPayment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $contribution = Contribution::query()->whereKey($locked->contribution_id)->lockForUpdate()->firstOrFail();
            $wasAlreadyFinal = in_array($locked->status, [ContributionPayment::STATUS_COMPLETED, ContributionPayment::STATUS_FAILED, ContributionPayment::STATUS_CANCELLED], true);

            $locked->fill([
                'status' => $remote['status'],
                'fees' => $remote['fees'],
                'net_amount' => $remote['net_amount'],
                'provider_snapshot' => $remote['snapshot'],
                'provider_snapshot_hash' => $this->snapshotHash($remote['snapshot']),
                'last_verified_at' => now(),
                'completed_at' => $remote['status'] === ContributionPayment::STATUS_COMPLETED ? ($locked->completed_at ?? now()) : $locked->completed_at,
            ])->save();

            // CAP-007B : seul `live` finalise toujours. `sandbox` ne finalise que si l'interrupteur
            // dédié (payments.geniuspay.sandbox_activation_allowed) est explicitement ouvert —
            // même garde-fou que l'adhésion, jamais dupliqué sous un nouveau nom.
            $canFinalize = $locked->environment === 'live'
                || ($locked->environment === 'sandbox' && (bool) config('payments.geniuspay.sandbox_activation_allowed'));

            if (! $wasAlreadyFinal && $canFinalize) {
                if ($remote['status'] === ContributionPayment::STATUS_COMPLETED) {
                    $this->issueReceipt($locked, $contribution);
                    // CAP-062 : projection ledger uniquement après confirmation réelle — jamais
                    // avant, jamais si la finalisation reste bloquée (sandbox non autorisé, etc.).
                    $this->ledger->postContributionPayment($locked);
                    $this->event($contribution, 'PAYMENT_CONFIRMED', 'SYSTEM:PAYMENT_PROVIDER', ['reference' => $locked->reference, 'period' => $locked->period]);
                } elseif (in_array($remote['status'], [ContributionPayment::STATUS_FAILED, ContributionPayment::STATUS_CANCELLED], true)) {
                    $this->event($contribution, 'PAYMENT_FAILED', 'SYSTEM:PAYMENT_PROVIDER', ['reference' => $locked->reference, 'period' => $locked->period, 'status' => $remote['status']]);
                }
            }

            return $locked->refresh();
        }, 3);
    }

    public function isLeader(ZumraGroup $group, string $actor): bool
    {
        return $this->zumraGroups->isLeader($group, $actor);
    }

    private function assertSubjectAuthority(Contribution $contribution, string $actor): void
    {
        if ($contribution->type === Contribution::TYPE_INDIVIDUAL) {
            abort_unless(hash_equals($contribution->subject_reference, $actor), 403);

            return;
        }
        $group = ZumraGroup::query()->findOrFail($contribution->subject_reference);
        abort_unless($this->zumraGroups->isLeader($group, $actor), 403);
    }

    private function assertCollectiveAuthorityRole(ZumraGroup $group, string $actor): string
    {
        $role = ZumraGroupRole::query()
            ->where('zumra_group_id', $group->id)
            ->where('core_identity_reference', $actor)
            ->where('status', ZumraGroupRole::STATUS_ACCEPTED)
            ->whereIn('role', self::COLLECTIVE_AUTHORITY_ROLES)
            ->value('role');
        abort_if($role === null, 403, 'Seuls le premier responsable et le responsable financier peuvent agir sur l’engagement collectif.');

        return (string) $role;
    }

    private function assertZumraEligible(ZumraGroup $group): void
    {
        abort_unless(in_array($group->state, self::ZUMRA_ELIGIBLE_STATES, true), 409, 'Cette ZUMRA doit être au moins validée pour s’engager financièrement.');
    }

    private function assertActiveProgramMember(string $actor): void
    {
        abort_unless(
            ZumraProgramMembership::query()->where('core_identity_reference', $actor)->where('status', ZumraProgramMembership::STATUS_ACTIVE)->exists(),
            403,
            'Une adhésion active au Programme ZUMRA est requise pour contribuer.'
        );
    }

    private function issueReceipt(ContributionPayment $payment, Contribution $contribution): void
    {
        if (ContributionReceipt::query()->where('payment_id', $payment->id)->exists()) {
            return;
        }
        $purpose = ContributionPurpose::query()->find($payment->purpose_id);
        $issuedAt = now();
        $number = 'DGC-'.$issuedAt->format('Y').'-'.strtoupper(Str::random(12));
        $canonical = implode('|', [$number, $payment->reference, $contribution->subject_reference, (string) $payment->amount, $payment->currency, $payment->period, $issuedAt->toIso8601String()]);
        ContributionReceipt::query()->create([
            'payment_id' => $payment->id,
            'contribution_id' => $contribution->id,
            'number' => $number,
            'core_identity_reference' => $payment->initiated_by_core_reference,
            'subject_type' => $contribution->subject_type,
            'subject_reference' => $contribution->subject_reference,
            'provider' => $payment->provider,
            'provider_reference' => $payment->reference,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'period' => $payment->period,
            'purpose_code' => $purpose?->code ?? '',
            'issued_at' => $issuedAt,
            'integrity_hash' => hash('sha256', $canonical),
        ]);
    }

    private function snapshotHash(array $snapshot): string
    {
        ksort($snapshot);

        return hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }

    private function event(Contribution $contribution, string $event, string $actor, array $context = []): void
    {
        ContributionEvent::query()->create(['contribution_id' => $contribution->id, 'event' => $event, 'actor_core_reference' => $actor, 'context' => $context, 'occurred_at' => now()]);
    }
}
