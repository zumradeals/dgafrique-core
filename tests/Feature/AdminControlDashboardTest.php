<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Moderation\ModerationConfiguration;
use App\Application\Moderation\ModerationReportService;
use App\Application\Notifications\NotificationConfiguration;
use App\Application\Zahab\ZahabWalletService;
use App\Application\Zumra\ZumraGroupService;
use App\Http\Controllers\Administration\LedgerController;
use App\Http\Controllers\Administration\WalletController;
use App\Models\ContextComment;
use App\Models\ModerationReport;
use App\Models\PortalAdministrator;
use App\Models\PortalSetting;
use App\Models\ZahabAcquisition;
use App\Models\ZahabWallet;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupRole;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use ReflectionClass;
use Tests\TestCase;

/**
 * ADMIN-CONTROL-002 — suite de la tour de contrôle admin. Ne re-teste jamais la logique métier déjà
 * couverte ailleurs (ZumraGroupService, ModerationDecisionService, ZahabWalletService, ...) : vérifie
 * uniquement que les nouvelles pages/routes admin la branchent correctement (accès, filtres, données
 * réelles affichées, garantie de lecture seule Finance/Ledger).
 */
final class AdminControlDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_administrator_is_forbidden_from_every_new_admin_section(): void
    {
        $this->signIn('IDN-PER-100000001');

        foreach ([
            '/administration',
            '/administration/communaute/zumra',
            '/administration/projets/liste',
            '/administration/finance',
            '/administration/ledger',
            '/administration/moteurs',
            '/administration/moderation',
            '/administration/configuration',
            '/administration/journal',
        ] as $path) {
            $this->get($path)->assertForbidden();
        }
    }

    public function test_dashboard_shows_real_non_fabricated_metrics(): void
    {
        $this->group('IDN-LEADER-1', ZumraGroup::STATE_ACTIVE);
        $this->group('IDN-LEADER-2', ZumraGroup::STATE_CONSTITUTING);

        $wallet = app(ZahabWalletService::class)->walletFor(ZahabWallet::SUBJECT_PERSON, 'IDN-SAVER', 'IDN-ADMIN');
        app(ZahabWalletService::class)->credit($wallet, 7500, ZahabWalletService::REASON_AID, (string) Str::uuid(), 'IDN-ADMIN');

        ZahabAcquisition::query()->create($this->acquisitionPayload('IDN-SAVER', ZahabAcquisition::STATUS_COMPLETED, 2000));
        ZahabAcquisition::query()->create($this->acquisitionPayload('IDN-SAVER', ZahabAcquisition::STATUS_FAILED, 1000));

        $this->admin();

        $response = $this->get('/administration')->assertOk();
        $response->assertSee('7 500');
        $response->assertSee('2 000');

        $this->assertDatabaseCount('dg_zumra_groups', 2);
    }

    public function test_zumra_list_can_be_filtered_by_state_and_search(): void
    {
        $active = $this->group('IDN-LEADER-3', ZumraGroup::STATE_ACTIVE);
        $constituting = $this->group('IDN-LEADER-4', ZumraGroup::STATE_CONSTITUTING);
        $this->admin();

        $this->get('/administration/communaute/zumra?state='.ZumraGroup::STATE_ACTIVE)
            ->assertOk()->assertSee($active->name)->assertDontSee($constituting->name);

        $this->get('/administration/communaute/zumra?q='.urlencode($active->name))
            ->assertOk()->assertSee($active->name);
    }

    public function test_administrator_can_advance_a_zumra_group_through_its_lifecycle_from_the_admin_list(): void
    {
        $group = $this->group('IDN-LEADER-5', ZumraGroup::STATE_CONSTITUTING);
        $this->makeStructurallyReady($group);
        $this->admin();

        $this->post(route('administration.zumra.groups.ready', $group))->assertRedirect();
        self::assertSame(ZumraGroup::STATE_READY, $group->fresh()->state);

        $this->post(route('administration.zumra.groups.validate', $group))->assertRedirect();
        self::assertSame(ZumraGroup::STATE_VALIDATED, $group->fresh()->state);

        $this->post(route('administration.zumra.groups.activate', $group))->assertRedirect();
        self::assertSame(ZumraGroup::STATE_ACTIVE, $group->fresh()->state);
    }

    public function test_a_non_administrator_cannot_trigger_a_zumra_lifecycle_action(): void
    {
        $group = $this->group('IDN-LEADER-6', ZumraGroup::STATE_CONSTITUTING);
        $this->signIn('IDN-PER-100000002');

        $this->post(route('administration.zumra.groups.ready', $group))->assertForbidden();
        self::assertSame(ZumraGroup::STATE_CONSTITUTING, $group->fresh()->state);
    }

    public function test_ledger_page_filters_by_purpose_and_direction(): void
    {
        $wallet = app(ZahabWalletService::class)->walletFor(ZahabWallet::SUBJECT_PERSON, 'IDN-LEDGER', 'IDN-ADMIN');
        app(ZahabWalletService::class)->credit($wallet, 900, ZahabWalletService::REASON_AID, (string) Str::uuid(), 'IDN-ADMIN');
        app(ZahabWalletService::class)->credit($wallet, 1500, ZahabWalletService::REASON_SPONSORSHIP, (string) Str::uuid(), 'IDN-ADMIN');
        $this->admin();

        $this->get('/administration/ledger?purpose_code='.ZahabWalletService::REASON_AID)
            ->assertOk()->assertSee('900')->assertDontSee('1 500');

        $this->get('/administration/ledger?direction=DEBIT')->assertOk()->assertDontSee('900');
    }

    public function test_wallet_page_shows_derived_balance_not_a_stored_balance(): void
    {
        $wallet = app(ZahabWalletService::class)->walletFor(ZahabWallet::SUBJECT_PERSON, 'IDN-BALANCE', 'IDN-ADMIN');
        app(ZahabWalletService::class)->credit($wallet, 4200, ZahabWalletService::REASON_AID, (string) Str::uuid(), 'IDN-ADMIN');
        self::assertFalse(Str::contains(implode(',', array_keys($wallet->getAttributes())), 'balance'));
        $this->admin();

        $this->get('/administration/zahab-wallets')->assertOk()->assertSee('4 200');
    }

    public function test_administration_wallet_and_ledger_controllers_expose_no_write_action(): void
    {
        $forbidden = ['credit', 'debit', 'reverse', 'store', 'update', 'destroy', 'walletCredit', 'walletDebit', 'walletTransfer'];

        foreach ([WalletController::class, LedgerController::class] as $class) {
            $methods = array_map(
                static fn ($m) => $m->getName(),
                (new ReflectionClass($class))->getMethods(\ReflectionMethod::IS_PUBLIC),
            );
            foreach ($forbidden as $name) {
                self::assertNotContains($name, $methods, "{$class} ne doit jamais exposer {$name}().");
            }
            self::assertSame(['index'], $methods, "{$class} doit rester strictement en lecture (index() uniquement).");
        }
    }

    public function test_finance_pages_render_real_data(): void
    {
        ZahabAcquisition::query()->create($this->acquisitionPayload('IDN-FIN', ZahabAcquisition::STATUS_COMPLETED, 3300));
        $this->admin();

        $this->get('/administration/finance')->assertOk()->assertSee('3 300');
        $this->get('/administration/finance/acquisitions?status=COMPLETED')->assertOk()->assertSee('IDN-FIN');
        $this->get('/administration/finance/acquisitions?status=FAILED')->assertOk()->assertDontSee('IDN-FIN');
        $this->get('/administration/finance/contributions')->assertOk();
    }

    public function test_engines_page_shows_configuration_and_hidden_counts_only_never_a_score(): void
    {
        $this->admin();

        // La doctrine (« jamais un score de personne, jamais un classement ») est un texte
        // explicatif affiché volontairement — ce qui compte réellement est structurel : le
        // contrôleur ne sélectionne jamais de score/rang par personne (voir AdminEnginesController,
        // uniquement des compteurs agrégés HIDDEN), et aucune référence d'identité individuelle
        // n'apparaît sur cette page.
        $response = $this->get('/administration/moteurs')->assertOk();
        $response->assertSee('Jamais un score de personne');
        $response->assertDontSee('IDN-');
    }

    public function test_configuration_hub_links_all_resolve(): void
    {
        $this->admin();
        $response = $this->get('/administration/configuration')->assertOk();

        foreach ([
            'administration.profile.edit', 'administration.discovery.edit', 'administration.collective-capabilities.edit',
            'administration.needs.edit', 'administration.zumra.edit', 'administration.zumra.groups.edit',
            'administration.projects.edit', 'administration.project-accompaniment.edit', 'administration.contributions.edit',
            'administration.project-matching.edit', 'administration.recommendations.edit',
            'administration.moderation.configuration.edit', 'administration.notifications.edit', 'administration.satellites.index',
        ] as $name) {
            $response->assertSee(route($name), false);
        }
    }

    public function test_moderation_configuration_can_be_saved_via_portal_setting(): void
    {
        $admin = $this->admin();

        $this->get('/administration/configuration/moderation')->assertOk();
        $this->put('/administration/configuration/moderation', [
            'warning_default_duration_days' => 14,
            'suspension_default_duration_days' => 30,
        ])->assertRedirect();

        $setting = PortalSetting::query()->findOrFail(ModerationConfiguration::KEY);
        self::assertSame(14, $setting->value['warning_default_duration_days']);
        self::assertSame($admin, $setting->updated_by_core_reference);
    }

    public function test_notification_configuration_can_be_saved_via_portal_setting(): void
    {
        $this->admin();

        $this->get('/administration/configuration/notifications')->assertOk();
        $this->put('/administration/configuration/notifications', [
            'lookback_days' => 21,
            'max_actionable' => 10,
            'max_recent' => 10,
            'scan_limit' => 200,
        ])->assertRedirect();

        $setting = PortalSetting::query()->findOrFail(NotificationConfiguration::KEY);
        self::assertSame(21, $setting->value['lookback_days']);
        self::assertFalse($setting->value['mission_fyi_enabled']);
    }

    public function test_moderation_page_lists_pending_reports_and_appeals(): void
    {
        $group = $this->group('IDN-LEADER-7', ZumraGroup::STATE_ACTIVE);
        $comment = ContextComment::query()->create([
            'public_reference' => (string) Str::uuid(),
            'context_type' => ContextComment::CONTEXT_ZUMRA_ACTIVITY,
            'context_reference' => $group->public_reference,
            'author_core_reference' => 'IDN-AUTHOR',
            'purpose' => 'COORDINATION',
            'body' => 'Un contenu réel posté dans l’activité de cette ZUMRA, à signaler.',
            'posted_at' => now(),
        ]);
        app(ModerationReportService::class)->reportContextComment($comment, 'IDN-REPORTER', ModerationReport::REASON_HARASSMENT, null);
        $this->admin();

        $this->get('/administration/moderation')->assertOk()->assertSee('1');
    }

    public function test_journal_aggregates_and_filters_by_type(): void
    {
        $this->group('IDN-LEADER-8', ZumraGroup::STATE_CONSTITUTING);
        $this->admin();

        $this->get('/administration/journal')->assertOk();
        $this->get('/administration/journal?type=ZUMRA')->assertOk();
    }

    private function acquisitionPayload(string $person, string $status, int $amount): array
    {
        return [
            'person_core_reference' => $person,
            'provider' => 'GENIUSPAY',
            'reference' => (string) Str::uuid(),
            'amount' => $amount,
            'currency' => 'XOF',
            'environment' => 'live',
            'status' => $status,
        ];
    }

    private function group(string $leader, string $state): ZumraGroup
    {
        $this->programMember($leader);
        $group = app(ZumraGroupService::class)->create($leader, [
            'name' => 'ZUMRA '.Str::random(6), 'domain' => 'Numérique',
            'founding_objective' => 'Former une équipe qui transmet des outils utiles et agit concrètement.',
            'participation_mode' => 'HYBRID', 'internal_charter' => 'Respect, responsabilité et transmission.',
            'assume_primary_lead' => true,
        ], 3);

        if ($state === ZumraGroup::STATE_ACTIVE) {
            $this->makeStructurallyReady($group);
            PortalAdministrator::query()->firstOrCreate(['core_identity_reference' => 'IDN-ADMIN-SEED']);
            $service = app(ZumraGroupService::class);
            $service->markReady($group, 'IDN-ADMIN-SEED');
            $service->validate($group, 'IDN-ADMIN-SEED');
            $service->activate($group, 'IDN-ADMIN-SEED');
        }

        return $group->fresh();
    }

    private function makeStructurallyReady(ZumraGroup $group): void
    {
        foreach (['PRIMARY_LEAD', 'TREASURER', 'SECRETARY', 'COORDINATOR', 'MENTOR'] as $role) {
            ZumraGroupRole::query()->updateOrCreate(
                ['zumra_group_id' => $group->id, 'role' => $role],
                [
                    'core_identity_reference' => $group->proposer_core_reference,
                    'status' => ZumraGroupRole::STATUS_ACCEPTED,
                    'proposed_by_core_reference' => $group->proposer_core_reference,
                    'proposed_at' => now(), 'accepted_at' => now(),
                ],
            );
        }
    }

    private function programMember(string $identity): void
    {
        $charter = ZumraCharter::query()->firstOrCreate(['version' => '2026.1'], [
            'title' => 'Charte ZUMRA', 'body' => str_repeat('Respect et transmission. ', 8),
            'content_hash' => hash('sha256', 'charter'), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now(),
        ]);
        ZumraProgramMembership::query()->firstOrCreate(['core_identity_reference' => $identity], [
            'status' => ZumraProgramMembership::STATUS_ACTIVE, 'accepted_charter_id' => $charter->id,
            'accepted_charter_version' => $charter->version, 'accepted_charter_hash' => $charter->content_hash,
            'charter_accepted_at' => now(), 'submitted_at' => now(), 'activated_at' => now(),
        ]);
    }

    private function admin(): string
    {
        $reference = 'IDN-PER-900000001';
        PortalAdministrator::query()->firstOrCreate(['core_identity_reference' => $reference]);
        $this->signIn($reference);

        return $reference;
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-09-16T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre DG Afrique', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-09-16T23:59:00+00:00']),
        ]);

        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
