<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Contributions\ContributionConfiguration;
use App\Application\Contributions\ContributionService;
use App\Application\Zahab\ZahabWalletService;
use App\Application\Zumra\ZumraGroupService;
use App\Http\Controllers\ContributionController;
use App\Models\Contribution;
use App\Models\ContributionPayment;
use App\Models\ContributionReceipt;
use App\Models\LedgerEntry;
use App\Models\PortalAdministrator;
use App\Models\PortalSetting;
use App\Models\ZahabWallet;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * CONTRIBUTION-ZAHAB-001 — raccordement de CAP-061 (contributions) au Wallet ZAHAB. Réutilise
 * intégralement le moteur existant (ContributionService, ZahabWalletService, LedgerService) : aucun
 * deuxième moteur financier. CAP-061 n'a pas de bénéficiaire Wallet-éligible (`purpose_code` reste
 * un code doctrinal de destination) — un seul mouvement est donc produit par paiement ZAHAB : le
 * DEBIT du Wallet du contributeur (Personne ou ZUMRA), jamais un Wallet DG Afrique/Projet fabriqué.
 */
final class ContributionZahabTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableContributions();
    }

    // ===== Individuel =====

    public function test_a_sufficiently_funded_wallet_pays_the_contribution_successfully(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $wallet = $this->creditPersonalWallet('IDN-MEMBER', 5000);

        $payment = app(ContributionService::class)->payPeriodWithZahabWallet($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING');

        self::assertSame(ContributionPayment::STATUS_COMPLETED, $payment->status);
        self::assertSame('ZAHAB', $payment->provider);
        self::assertSame(500, $payment->amount);
        self::assertSame(4500, app(ZahabWalletService::class)->balance($wallet->fresh()));
    }

    public function test_insufficient_wallet_balance_is_refused_and_leaves_no_trace(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->creditPersonalWallet('IDN-MEMBER', 100); // < 500 requis — produit déjà 1 écriture CREDIT légitime

        $this->assertAborts(409, fn () => app(ContributionService::class)->payPeriodWithZahabWallet($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING'));

        self::assertSame(0, ContributionPayment::query()->count(), 'Un échec pour fonds insuffisants ne doit laisser aucune ligne de paiement (rollback complet).');
        self::assertSame(0, LedgerEntry::query()->where('direction', LedgerEntry::DIRECTION_DEBIT)->count(), 'Aucun débit ne doit avoir été produit par la tentative refusée.');
        self::assertSame(100, app(ZahabWalletService::class)->balance($this->personalWallet('IDN-MEMBER')));
    }

    public function test_the_debit_amount_matches_the_configured_individual_amount_exactly(): void
    {
        PortalSetting::query()->updateOrCreate(
            ['key' => ContributionConfiguration::KEY],
            ['value' => array_merge(app(ContributionConfiguration::class)->defaults(), ['individual_enabled' => true, 'collective_enabled' => true, 'individual_amount' => 777]), 'updated_by_core_reference' => 'IDN-ADMIN']
        );
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->creditPersonalWallet('IDN-MEMBER', 1000);

        $payment = app(ContributionService::class)->payPeriodWithZahabWallet($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING');

        self::assertSame(777, $payment->amount);
        self::assertSame(223, app(ZahabWalletService::class)->balance($this->personalWallet('IDN-MEMBER')));
    }

    public function test_a_single_ledger_entry_is_produced_debiting_the_contributors_wallet(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $wallet = $this->creditPersonalWallet('IDN-MEMBER', 5000);

        app(ContributionService::class)->payPeriodWithZahabWallet($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING');

        // Un seul DEBIT est produit par le paiement (en plus du CREDIT légitime de la mise en place
        // ci-dessus) : aucun bénéficiaire Wallet-éligible en V1, jamais un Wallet DG Afrique/Projet
        // fabriqué pour équilibrer visuellement l'opération.
        $debits = LedgerEntry::query()->where('wallet_id', $wallet->id)->where('direction', LedgerEntry::DIRECTION_DEBIT)->get();
        self::assertSame(1, $debits->count());
        $movement = $debits->sole();
        self::assertSame(500, $movement->amount);
        self::assertSame(ZahabWalletService::REASON_INDIVIDUAL_CONTRIBUTION, $movement->purpose_code);
        self::assertNotNull($movement->zahab_operation_reference);
        self::assertStringContainsString((string) $contribution->public_reference, (string) $movement->zahab_operation_reference);

        // Étanchéité : aucune deuxième écriture SOURCE_CONTRIBUTION_PAYMENT n'est produite pour le
        // même paiement (un seul mouvement représente cet événement économique, jamais deux).
        self::assertSame(0, LedgerEntry::query()->where('source_type', LedgerEntry::SOURCE_CONTRIBUTION_PAYMENT)->count());
    }

    public function test_a_receipt_is_issued_for_a_zahab_funded_contribution(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->creditPersonalWallet('IDN-MEMBER', 5000);

        $payment = app(ContributionService::class)->payPeriodWithZahabWallet($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING');

        $receipt = ContributionReceipt::query()->where('payment_id', $payment->id)->sole();
        self::assertSame('ZAHAB', $receipt->provider);
        self::assertSame(500, $receipt->amount);
        self::assertSame('TRAINING', $receipt->purpose_code);
        self::assertSame('2026-09', $receipt->period);
    }

    public function test_retrying_the_same_period_after_success_never_double_debits(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->creditPersonalWallet('IDN-MEMBER', 5000);
        app(ContributionService::class)->payPeriodWithZahabWallet($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING');

        $this->assertAborts(409, fn () => app(ContributionService::class)->payPeriodWithZahabWallet($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING'));

        self::assertSame(1, ContributionPayment::query()->count());
        self::assertSame(4500, app(ZahabWalletService::class)->balance($this->personalWallet('IDN-MEMBER')));
    }

    public function test_a_stranger_cannot_pay_someone_elses_individual_contribution_with_zahab(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->creditPersonalWallet('IDN-MEMBER', 5000);

        $this->assertAborts(403, fn () => app(ContributionService::class)->payPeriodWithZahabWallet($contribution, 'IDN-STRANGER', '2026-09', 'TRAINING'));
    }

    public function test_zahab_payment_is_refused_while_individual_contributions_remain_disabled(): void
    {
        PortalSetting::query()->updateOrCreate(
            ['key' => ContributionConfiguration::KEY],
            ['value' => array_merge(app(ContributionConfiguration::class)->defaults(), ['individual_enabled' => false, 'collective_enabled' => true]), 'updated_by_core_reference' => 'IDN-ADMIN']
        );
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->creditPersonalWallet('IDN-MEMBER', 5000);

        $this->assertAborts(409, fn () => app(ContributionService::class)->payPeriodWithZahabWallet($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING'));
        self::assertSame(5000, app(ZahabWalletService::class)->balance($this->personalWallet('IDN-MEMBER')));
    }

    // ===== Collectif =====

    public function test_proposing_a_collective_engagement_never_debits_prematurely(): void
    {
        $group = $this->activeGroup('IDN-LEADER', 'IDN-FINANCE');
        $this->creditZumraWallet($group, 5000);

        app(ContributionService::class)->proposeCollective($group, 'IDN-LEADER');

        self::assertSame(0, ContributionPayment::query()->count());
        self::assertSame(5000, app(ZahabWalletService::class)->balance($this->zumraWallet($group)), 'Le solde ne doit bouger qu’au moment métier du paiement, jamais à la proposition.');
    }

    public function test_an_authorized_leader_can_pay_the_approved_collective_engagement_with_zahab(): void
    {
        $group = $this->activeGroup('IDN-LEADER', 'IDN-FINANCE');
        $contribution = $this->approvedCollective($group, 'IDN-LEADER', 'IDN-FINANCE');
        $this->creditZumraWallet($group, 5000);

        $payment = app(ContributionService::class)->payPeriodWithZahabWallet($contribution, 'IDN-LEADER', '2026-09', 'SOLIDARITY');

        self::assertSame(ContributionPayment::STATUS_COMPLETED, $payment->status);
        self::assertSame(2500, $payment->amount);
        self::assertSame(2500, app(ZahabWalletService::class)->balance($this->zumraWallet($group)));
        $movement = LedgerEntry::query()->where('wallet_id', $this->zumraWallet($group)->id)->where('direction', LedgerEntry::DIRECTION_DEBIT)->sole();
        self::assertSame(ZahabWallet::SUBJECT_ZUMRA_GROUP, $movement->subject_type);
        self::assertSame(ZahabWalletService::REASON_COLLECTIVE_CONTRIBUTION, $movement->purpose_code);
    }

    public function test_an_unauthorized_member_cannot_trigger_the_collective_zahab_payment(): void
    {
        $group = $this->activeGroup('IDN-LEADER', 'IDN-FINANCE');
        $contribution = $this->approvedCollective($group, 'IDN-LEADER', 'IDN-FINANCE');
        $this->creditZumraWallet($group, 5000);

        $this->assertAborts(403, fn () => app(ContributionService::class)->payPeriodWithZahabWallet($contribution, 'IDN-OUTSIDER', '2026-09', 'SOLIDARITY'));
        self::assertSame(5000, app(ZahabWalletService::class)->balance($this->zumraWallet($group)));
    }

    public function test_insufficient_zumra_wallet_balance_is_refused_cleanly(): void
    {
        $group = $this->activeGroup('IDN-LEADER', 'IDN-FINANCE');
        $contribution = $this->approvedCollective($group, 'IDN-LEADER', 'IDN-FINANCE');
        $this->creditZumraWallet($group, 1000); // < 2500 requis

        $this->assertAborts(409, fn () => app(ContributionService::class)->payPeriodWithZahabWallet($contribution, 'IDN-LEADER', '2026-09', 'SOLIDARITY'));

        self::assertSame(0, ContributionPayment::query()->count());
        self::assertSame(1000, app(ZahabWalletService::class)->balance($this->zumraWallet($group)));
    }

    public function test_retrying_the_collective_period_after_success_never_double_debits(): void
    {
        $group = $this->activeGroup('IDN-LEADER', 'IDN-FINANCE');
        $contribution = $this->approvedCollective($group, 'IDN-LEADER', 'IDN-FINANCE');
        $this->creditZumraWallet($group, 5000);
        app(ContributionService::class)->payPeriodWithZahabWallet($contribution, 'IDN-LEADER', '2026-09', 'SOLIDARITY');

        // Un autre responsable habilité rejoue la même période — toujours refusé, jamais un double débit.
        $this->assertAborts(409, fn () => app(ContributionService::class)->payPeriodWithZahabWallet($contribution, 'IDN-FINANCE', '2026-09', 'SOLIDARITY'));

        self::assertSame(1, ContributionPayment::query()->count());
        self::assertSame(2500, app(ZahabWalletService::class)->balance($this->zumraWallet($group)));
    }

    public function test_a_suspended_zumra_still_blocks_the_zahab_payment(): void
    {
        $group = $this->activeGroup('IDN-LEADER', 'IDN-FINANCE');
        $contribution = $this->approvedCollective($group, 'IDN-LEADER', 'IDN-FINANCE');
        $this->creditZumraWallet($group, 5000);
        $admin = $this->administrator();
        $groupService = app(ZumraGroupService::class);
        $group = $groupService->warn($group, $admin);
        $group = $groupService->suspend($group, $admin);

        $this->assertAborts(409, fn () => app(ContributionService::class)->payPeriodWithZahabWallet($contribution, 'IDN-LEADER', '2026-09', 'SOLIDARITY'));
    }

    // ===== Intégrité =====

    public function test_no_generic_wallet_mutation_route_exists_on_the_contribution_controller(): void
    {
        $reflection = new \ReflectionClass(ContributionController::class);
        $methodNames = array_map(static fn (\ReflectionMethod $m): string => $m->getName(), $reflection->getMethods(\ReflectionMethod::IS_PUBLIC));
        foreach (['credit', 'debit', 'walletCredit', 'walletDebit', 'walletTransfer'] as $forbidden) {
            self::assertNotContains($forbidden, $methodNames);
        }
    }

    public function test_zahab_payment_route_requires_a_valid_purpose_and_never_accepts_a_client_supplied_amount(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->creditPersonalWallet('IDN-MEMBER', 5000);
        $this->signIn('IDN-MEMBER');

        // Le montant n'est jamais un champ du formulaire : seule la configuration serveur le
        // détermine. L'envoyer n'a aucun effet — vérifié en confirmant le montant réellement débité.
        $this->post(route('contributions.pay.zahab', $contribution), ['period' => '2026-09', 'purpose_code' => 'TRAINING', 'amount' => 999999])
            ->assertRedirect();

        $payment = ContributionPayment::query()->sole();
        self::assertSame(500, $payment->amount, 'Le montant client "amount" doit être totalement ignoré.');
    }

    public function test_zahab_payment_route_rejects_an_unknown_purpose_code(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->creditPersonalWallet('IDN-MEMBER', 5000);
        $this->signIn('IDN-MEMBER');

        $this->post(route('contributions.pay.zahab', $contribution), ['period' => '2026-09', 'purpose_code' => 'NOT_A_REAL_PURPOSE'])
            ->assertSessionHasErrors('purpose_code');
        self::assertSame(0, ContributionPayment::query()->count());
    }

    public function test_the_pre_existing_geniuspay_contribution_flow_still_works_untouched(): void
    {
        // CAP-061 historique (art. 17 du mandat) : le flux GeniusPay existant reste 100% intact et
        // coexiste avec un paiement ZAHAB pour la MÊME contribution sur une autre période.
        config()->set('payments.geniuspay.environment', 'live');
        config()->set('payments.geniuspay.api_key', 'pk_live_test');
        config()->set('payments.geniuspay.api_secret', 'sk_live_test');
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->creditPersonalWallet('IDN-MEMBER', 5000);
        app(ContributionService::class)->payPeriodWithZahabWallet($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING');

        $this->fakeGeniusPay('pending');
        $geniusPayment = app(ContributionService::class)->payPeriod($contribution, 'IDN-MEMBER', '2026-10', 'TRAINING', 'https://example.test/ok', 'https://example.test/ko');
        $this->fakeGeniusPayReconcile('completed');
        $confirmed = app(ContributionService::class)->reconcile($geniusPayment);

        self::assertSame(ContributionPayment::STATUS_COMPLETED, $confirmed->status);
        self::assertSame('GENIUSPAY', $confirmed->provider);
        self::assertSame(2, ContributionPayment::query()->count());
        self::assertSame(1, LedgerEntry::query()->where('source_type', LedgerEntry::SOURCE_CONTRIBUTION_PAYMENT)->count());
        // 2 écritures ZAHAB_WALLET_MOVEMENT : le CREDIT de mise en place + le DEBIT du paiement de
        // septembre — jamais une deuxième écriture pour le paiement GeniusPay d'octobre (source_type
        // CONTRIBUTION_PAYMENT distinct, aucun chevauchement entre les deux mécanismes).
        self::assertSame(2, LedgerEntry::query()->where('source_type', LedgerEntry::SOURCE_ZAHAB_WALLET_MOVEMENT)->count());
    }

    public function test_no_project_wallet_subject_is_ever_introduced(): void
    {
        self::assertNotContains('PROJECT', ZahabWallet::SUBJECTS);
        self::assertSame(0, ZahabWallet::query()->where('subject_type', 'PROJECT')->count());
    }

    public function test_the_minimal_dashboard_renders_the_individual_and_collective_flows(): void
    {
        $group = $this->activeGroup('IDN-LEADER', 'IDN-FINANCE');
        $this->creditZumraWallet($group, 5000);
        $this->approvedCollective($group, 'IDN-LEADER', 'IDN-FINANCE');
        $this->programMember('IDN-MEMBER');
        // IDN-LEADER est déjà membre du programme via activeGroup(); on lui ouvre aussi un
        // engagement individuel pour vérifier que les deux blocs cohabitent sur la même page.
        app(ContributionService::class)->startIndividual('IDN-LEADER');
        $this->creditPersonalWallet('IDN-LEADER', 5000);
        $this->signIn('IDN-LEADER');

        $this->get(route('contributions.dashboard'))
            ->assertOk()
            ->assertSee('Mes contributions')
            ->assertSee('Wallet ZAHAB', false)
            ->assertSee('Atelier contribution ZAHAB', false);
    }

    public function test_the_dashboard_renders_correctly_with_no_engagement_at_all(): void
    {
        $this->programMember('IDN-FRESH');
        $this->signIn('IDN-FRESH');

        $this->get(route('contributions.dashboard'))
            ->assertOk()
            ->assertSee('Vous n\'avez pas encore d\'engagement de contribution individuelle', false);
    }

    public function test_no_balance_ever_goes_negative_across_the_full_contribution_flow(): void
    {
        $this->programMember('IDN-MEMBER');
        $contribution = app(ContributionService::class)->startIndividual('IDN-MEMBER');
        $this->creditPersonalWallet('IDN-MEMBER', 500);
        app(ContributionService::class)->payPeriodWithZahabWallet($contribution, 'IDN-MEMBER', '2026-09', 'TRAINING');

        $this->assertAborts(409, fn () => app(ContributionService::class)->payPeriodWithZahabWallet($contribution, 'IDN-MEMBER', '2026-10', 'TRAINING'));

        self::assertSame(0, app(ZahabWalletService::class)->balance($this->personalWallet('IDN-MEMBER')));
    }

    // ===== Helpers =====

    private function assertAborts(int $status, callable $fn): void
    {
        try {
            $fn();
            self::fail("Expected an HttpException with status {$status} but none was thrown.");
        } catch (HttpException $e) {
            self::assertSame($status, $e->getStatusCode());
        }
    }

    private function creditPersonalWallet(string $identity, int $amount): ZahabWallet
    {
        $wallet = app(ZahabWalletService::class)->walletFor(ZahabWallet::SUBJECT_PERSON, $identity, $identity);
        app(ZahabWalletService::class)->credit($wallet, $amount, ZahabWalletService::REASON_AID, (string) Str::uuid(), 'IDN-ADMIN');

        return $wallet->fresh();
    }

    private function personalWallet(string $identity): ZahabWallet
    {
        return app(ZahabWalletService::class)->walletFor(ZahabWallet::SUBJECT_PERSON, $identity, $identity);
    }

    private function creditZumraWallet(ZumraGroup $group, int $amount): ZahabWallet
    {
        $wallet = app(ZahabWalletService::class)->walletFor(ZahabWallet::SUBJECT_ZUMRA_GROUP, $group->id, 'IDN-ADMIN');
        app(ZahabWalletService::class)->credit($wallet, $amount, ZahabWalletService::REASON_AID, (string) Str::uuid(), 'IDN-ADMIN');

        return $wallet->fresh();
    }

    private function zumraWallet(ZumraGroup $group): ZahabWallet
    {
        return app(ZahabWalletService::class)->walletFor(ZahabWallet::SUBJECT_ZUMRA_GROUP, $group->id, 'IDN-ADMIN');
    }

    private function enableContributions(): void
    {
        $config = app(ContributionConfiguration::class);
        PortalSetting::query()->updateOrCreate(
            ['key' => ContributionConfiguration::KEY],
            ['value' => array_merge($config->defaults(), ['individual_enabled' => true, 'collective_enabled' => true]), 'updated_by_core_reference' => 'IDN-ADMIN']
        );
    }

    private function administrator(): string
    {
        $reference = 'IDN-ADMIN-'.Str::random(6);
        PortalAdministrator::query()->create(['core_identity_reference' => $reference]);

        return $reference;
    }

    private function groupPayload(): array
    {
        return [
            'name' => 'Atelier contribution ZAHAB '.Str::random(8),
            'domain' => 'Numérique',
            'founding_objective' => 'Former une équipe qui transmet les outils numériques et réalise des solutions utiles aux communautés locales.',
            'participation_mode' => 'HYBRID',
            'internal_charter' => 'Chaque membre respecte la dignité, la hiérarchie, la transmission et les décisions responsables.',
            'assume_primary_lead' => true,
        ];
    }

    /** @return array{0: ZumraGroup} */
    private function readyGroup(string $leader, string $financeLead): array
    {
        $this->programMember($leader);
        $service = app(ZumraGroupService::class);
        $group = $service->create($leader, $this->groupPayload(), 3);

        $roles = ['FIRST_DEPUTY' => 'IDN-DEPUTY1-'.Str::random(4), 'SECOND_DEPUTY' => 'IDN-DEPUTY2-'.Str::random(4), 'FINANCE_LEAD' => $financeLead, 'SOCIAL_RELATIONS_LEAD' => 'IDN-SOCIAL-'.Str::random(4)];
        $last = array_key_last($roles);
        foreach ($roles as $role => $identity) {
            $this->programMember($identity);
            $service->proposeRole($group, $leader, $role, $identity);
            $service->acceptRole($group, $identity, $role, 3, $role === $last);
        }

        return [$group->refresh()];
    }

    private function validatedGroup(string $leader, string $financeLead): ZumraGroup
    {
        [$group] = $this->readyGroup($leader, $financeLead);
        $admin = $this->administrator();

        return app(ZumraGroupService::class)->validate($group, $admin);
    }

    private function activeGroup(string $leader, string $financeLead): ZumraGroup
    {
        $group = $this->validatedGroup($leader, $financeLead);
        $admin = $this->administrator();

        return app(ZumraGroupService::class)->activate($group, $admin);
    }

    private function approvedCollective(ZumraGroup $group, string $leader, string $financeLead): Contribution
    {
        $service = app(ContributionService::class);
        $contribution = $service->proposeCollective($group, $leader);

        return $service->approveCollective($contribution, $financeLead);
    }

    private function programMember(string $identity): void
    {
        $charter = ZumraCharter::query()->firstOrCreate(['version' => '2026.1'], ['title' => 'Charte ZUMRA', 'body' => str_repeat('Respect et transmission. ', 8), 'content_hash' => hash('sha256', 'charter'), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()]);
        ZumraProgramMembership::query()->firstOrCreate(['core_identity_reference' => $identity], ['status' => ZumraProgramMembership::STATUS_ACTIVE, 'accepted_charter_id' => $charter->id, 'accepted_charter_version' => $charter->version, 'accepted_charter_hash' => $charter->content_hash, 'charter_accepted_at' => now(), 'submitted_at' => now(), 'activated_at' => now()]);
    }

    private function signIn(string $reference): void
    {
        Http::fake(function (ClientRequest $request) use ($reference) {
            $url = $request->url();
            $method = $request->method();

            if ($method === 'POST' && str_ends_with(rtrim($url, '/'), '/sessions')) {
                return Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-09-16T23:59:00+00:00'], 201);
            }
            if (str_ends_with($url, '/sessions/current')) {
                return Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-09-16T23:59:00+00:00']);
            }
            if (str_contains($url, '/identites/')) {
                return Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']);
            }

            return Http::response(['error' => 'UNEXPECTED_TEST_REQUEST'], 500);
        });

        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }

    private $providerStatus = 'pending';

    private $providerEnvironment = 'live';

    private $providerAmount = 500;

    private $paymentCounter = 0;

    private function fakeGeniusPay(string $providerStatus, ?int $amount = null, ?string $environment = null): void
    {
        $this->providerStatus = $providerStatus;
        if ($amount !== null) {
            $this->providerAmount = $amount;
        }
        if ($environment !== null) {
            $this->providerEnvironment = $environment;
        }
        $this->installFake();
    }

    private function fakeGeniusPayReconcile(string $providerStatus, ?int $amount = null, ?string $environment = null): void
    {
        $this->fakeGeniusPay($providerStatus, $amount, $environment);
    }

    private function installFake(): void
    {
        Http::fake(function (ClientRequest $request) {
            $url = $request->url();
            $method = $request->method();

            if (str_ends_with($url, '/sessions/current')) {
                return Http::response(['entite' => 'IDN-MEMBER', 'assurance' => 'AS1', 'expire_le' => '2026-09-16T23:59:00+00:00']);
            }
            if (str_ends_with($url, '/sessions')) {
                return Http::response(['jeton' => 'bearer', 'entite' => 'IDN-MEMBER', 'assurance' => 'AS1', 'expire_le' => '2026-09-16T23:59:00+00:00'], 201);
            }
            if (str_contains($url, '/identites/')) {
                return Http::response(['reference' => 'IDN-MEMBER', 'type' => 'personne', 'libelle' => 'Membre', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']);
            }
            if ($method === 'POST' && str_ends_with(rtrim($url, '/'), '/payments')) {
                $this->paymentCounter++;
                $reference = 'CONTRIB-ZAHAB-TEST-REF-'.$this->paymentCounter;

                return Http::response(['success' => true, 'data' => $this->providerPayload($reference)]);
            }
            if ($method === 'GET' && str_contains($url, '/payments/')) {
                $reference = rawurldecode((string) basename((string) parse_url($url, PHP_URL_PATH)));

                return Http::response(['success' => true, 'data' => $this->providerPayload($reference)]);
            }

            return Http::response(['error' => 'UNEXPECTED_TEST_REQUEST'], 500);
        });
    }

    private function providerPayload(string $reference): array
    {
        return [
            'id' => 'pay-'.$reference,
            'reference' => $reference,
            'amount' => $this->providerAmount,
            'status' => $this->providerStatus,
            'environment' => $this->providerEnvironment,
            'checkout_url' => 'https://checkout.example/pay/'.$reference,
            'completed_at' => $this->providerStatus === 'completed' ? now()->toIso8601String() : null,
        ];
    }
}
