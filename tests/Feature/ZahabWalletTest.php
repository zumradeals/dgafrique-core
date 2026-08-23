<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Ledger\LedgerService;
use App\Application\Organizations\OrganizationService;
use App\Application\Zahab\ZahabWalletService;
use App\Application\Zumra\ZumraGroupService;
use App\Http\Controllers\WalletController;
use App\Models\LedgerEntry;
use App\Models\Organization;
use App\Models\PortalAdministrator;
use App\Models\ZahabWallet;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;
use TypeError;

/**
 * ZAHAB-001 — Wallet ZAHAB (arbitrage produit : 1 ZAHAB = 1 FCFA, addendum daté dans
 * docs/architecture/ARCHITECTURE-PRODUIT-V2.md §8). Le solde n'est jamais stocké : chaque test qui
 * l'affirme le fait en le recalculant depuis dg_ledger_entries via `ZahabWalletService::balance()`,
 * jamais en lisant une colonne. Aucune route HTTP n'expose `credit()`/`debit()` — testé explicitement.
 */
final class ZahabWalletTest extends TestCase
{
    use RefreshDatabase;

    private string $coreReference = 'IDN-ACTOR';

    // ===== Création paresseuse et unicité (art. 8/9 du mandat) =====

    public function test_a_personal_wallet_is_created_lazily_on_first_use(): void
    {
        $service = app(ZahabWalletService::class);

        self::assertSame(0, ZahabWallet::query()->count());
        $wallet = $service->walletFor(ZahabWallet::SUBJECT_PERSON, 'IDN-MEMBER', 'IDN-MEMBER');

        self::assertSame(1, ZahabWallet::query()->count());
        self::assertSame(ZahabWallet::SUBJECT_PERSON, $wallet->subject_type);
        self::assertSame('IDN-MEMBER', $wallet->subject_reference);
    }

    public function test_a_wallet_is_never_created_twice_for_the_same_subject(): void
    {
        $service = app(ZahabWalletService::class);

        $first = $service->walletFor(ZahabWallet::SUBJECT_PERSON, 'IDN-MEMBER', 'IDN-MEMBER');
        $second = $service->walletFor(ZahabWallet::SUBJECT_PERSON, 'IDN-MEMBER', 'IDN-MEMBER');

        self::assertSame($first->id, $second->id);
        self::assertSame(1, ZahabWallet::query()->count());
    }

    public function test_the_database_rejects_a_duplicate_wallet_bypassing_the_service(): void
    {
        // Filet de sécurité DB, indépendant de la logique applicative (même patron que
        // dg_contributions) : une tentative directe d'INSERT en doublon échoue toujours.
        app(ZahabWalletService::class)->walletFor(ZahabWallet::SUBJECT_PERSON, 'IDN-MEMBER', 'IDN-MEMBER');

        $this->expectException(QueryException::class);
        ZahabWallet::query()->create([
            'subject_type' => ZahabWallet::SUBJECT_PERSON,
            'subject_reference' => 'IDN-MEMBER',
            'created_by_core_reference' => 'IDN-MEMBER',
        ]);
    }

    public function test_a_zumra_group_wallet_is_supported(): void
    {
        $group = app(ZumraGroupService::class)->create('IDN-LEADER', $this->groupPayload(), 3);
        $wallet = app(ZahabWalletService::class)->walletFor(ZahabWallet::SUBJECT_ZUMRA_GROUP, $group->id, 'IDN-LEADER');

        self::assertSame(ZahabWallet::SUBJECT_ZUMRA_GROUP, $wallet->subject_type);
        self::assertSame($group->id, $wallet->subject_reference);
    }

    public function test_an_organization_wallet_is_supported(): void
    {
        $organization = $this->organization('IDN-FOUNDER');
        $wallet = app(ZahabWalletService::class)->walletFor(ZahabWallet::SUBJECT_ORGANIZATION, $organization->id, 'IDN-FOUNDER');

        self::assertSame(ZahabWallet::SUBJECT_ORGANIZATION, $wallet->subject_type);
        self::assertSame($organization->id, $wallet->subject_reference);
    }

    public function test_an_unknown_subject_type_is_refused(): void
    {
        $this->assertAborts(422, fn () => app(ZahabWalletService::class)->walletFor('SATELLITE', 'X', 'IDN-ADMIN'));
    }

    // ===== Solde (art. 5/6 du mandat) =====

    public function test_a_new_wallet_has_a_zero_balance(): void
    {
        $service = app(ZahabWalletService::class);
        $wallet = $service->walletFor(ZahabWallet::SUBJECT_PERSON, 'IDN-MEMBER', 'IDN-MEMBER');

        self::assertSame(0, $service->balance($wallet));
    }

    public function test_the_wallet_table_stores_no_balance_column(): void
    {
        $columns = Schema::getColumnListing('dg_zahab_wallets');
        foreach (['balance', 'available_balance', 'credit', 'score'] as $forbidden) {
            self::assertNotContains($forbidden, $columns);
        }
    }

    // ===== Crédit (art. 14 du mandat) =====

    public function test_an_authorized_credit_increases_the_balance(): void
    {
        $service = app(ZahabWalletService::class);
        $wallet = $service->walletFor(ZahabWallet::SUBJECT_PERSON, 'IDN-MEMBER', 'IDN-MEMBER');

        $service->credit($wallet, 500, ZahabWalletService::REASON_AID, (string) Str::uuid(), 'IDN-ADMIN');

        self::assertSame(500, $service->balance($wallet));
    }

    public function test_a_credit_with_an_unknown_business_reason_is_refused(): void
    {
        $service = app(ZahabWalletService::class);
        $wallet = $service->walletFor(ZahabWallet::SUBJECT_PERSON, 'IDN-MEMBER', 'IDN-MEMBER');

        $this->assertAborts(422, fn () => $service->credit($wallet, 500, 'FREE_MONEY', (string) Str::uuid(), 'IDN-ADMIN'));
        self::assertSame(0, $service->balance($wallet));
    }

    // ===== Débit (art. 13 du mandat) =====

    public function test_a_debit_with_sufficient_funds_succeeds(): void
    {
        $service = app(ZahabWalletService::class);
        $wallet = $service->walletFor(ZahabWallet::SUBJECT_PERSON, 'IDN-MEMBER', 'IDN-MEMBER');
        $service->credit($wallet, 1000, ZahabWalletService::REASON_AID, (string) Str::uuid(), 'IDN-ADMIN');

        $service->debit($wallet, 400, ZahabWalletService::REASON_SERVICE_PURCHASE, (string) Str::uuid(), 'IDN-MEMBER');

        self::assertSame(600, $service->balance($wallet));
    }

    public function test_a_debit_exceeding_the_balance_is_refused(): void
    {
        $service = app(ZahabWalletService::class);
        $wallet = $service->walletFor(ZahabWallet::SUBJECT_PERSON, 'IDN-MEMBER', 'IDN-MEMBER');
        $service->credit($wallet, 400, ZahabWalletService::REASON_AID, (string) Str::uuid(), 'IDN-ADMIN');

        $this->assertAborts(409, fn () => $service->debit($wallet, 500, ZahabWalletService::REASON_SERVICE_PURCHASE, (string) Str::uuid(), 'IDN-MEMBER'));
        self::assertSame(400, $service->balance($wallet), 'Le débit refusé ne doit jamais avoir été appliqué.');
    }

    public function test_the_balance_never_goes_negative_across_several_sequential_debits(): void
    {
        // Preuve de l'application sérialisée de l'invariant "jamais négatif" : le même chemin de
        // verrouillage (lockForUpdate + recalcul dans la transaction) qui protège ce test protège
        // deux débits réellement concurrents pour le même Wallet.
        $service = app(ZahabWalletService::class);
        $wallet = $service->walletFor(ZahabWallet::SUBJECT_PERSON, 'IDN-MEMBER', 'IDN-MEMBER');
        $service->credit($wallet, 1000, ZahabWalletService::REASON_AID, (string) Str::uuid(), 'IDN-ADMIN');

        $service->debit($wallet, 700, ZahabWalletService::REASON_SERVICE_PURCHASE, (string) Str::uuid(), 'IDN-MEMBER');
        $this->assertAborts(409, fn () => $service->debit($wallet, 400, ZahabWalletService::REASON_SERVICE_PURCHASE, (string) Str::uuid(), 'IDN-MEMBER'));

        self::assertSame(300, $service->balance($wallet));
    }

    // ===== Ledger et idempotence (art. 4/10/12 du mandat) =====

    public function test_every_credit_produces_a_ledger_entry(): void
    {
        $service = app(ZahabWalletService::class);
        $wallet = $service->walletFor(ZahabWallet::SUBJECT_PERSON, 'IDN-MEMBER', 'IDN-MEMBER');

        $entry = $service->credit($wallet, 500, ZahabWalletService::REASON_AID, (string) Str::uuid(), 'IDN-ADMIN');

        self::assertSame(1, LedgerEntry::query()->where('wallet_id', $wallet->id)->count());
        self::assertSame(LedgerEntry::DIRECTION_CREDIT, $entry->direction);
        self::assertSame(LedgerEntry::SOURCE_ZAHAB_WALLET_MOVEMENT, $entry->source_type);
        self::assertSame('XOF', $entry->currency);
    }

    public function test_replaying_the_same_idempotency_key_never_credits_twice(): void
    {
        $service = app(ZahabWalletService::class);
        $wallet = $service->walletFor(ZahabWallet::SUBJECT_PERSON, 'IDN-MEMBER', 'IDN-MEMBER');
        $key = (string) Str::uuid();

        $first = $service->credit($wallet, 500, ZahabWalletService::REASON_AID, $key, 'IDN-ADMIN');
        $second = $service->credit($wallet, 500, ZahabWalletService::REASON_AID, $key, 'IDN-ADMIN');

        self::assertSame($first->id, $second->id);
        self::assertSame(1, LedgerEntry::query()->where('wallet_id', $wallet->id)->count());
        self::assertSame(500, $service->balance($wallet));
    }

    public function test_replaying_the_same_debit_idempotency_key_never_debits_twice(): void
    {
        $service = app(ZahabWalletService::class);
        $wallet = $service->walletFor(ZahabWallet::SUBJECT_PERSON, 'IDN-MEMBER', 'IDN-MEMBER');
        $service->credit($wallet, 1000, ZahabWalletService::REASON_AID, (string) Str::uuid(), 'IDN-ADMIN');
        $key = (string) Str::uuid();

        $first = $service->debit($wallet, 400, ZahabWalletService::REASON_SERVICE_PURCHASE, $key, 'IDN-MEMBER');
        $second = $service->debit($wallet, 400, ZahabWalletService::REASON_SERVICE_PURCHASE, $key, 'IDN-MEMBER');

        self::assertSame($first->id, $second->id);
        self::assertSame(600, $service->balance($wallet), 'Rejouer la même clé ne doit jamais débiter deux fois, même après que le solde a déjà baissé.');
    }

    // ===== Historique (art. 20 du mandat) =====

    public function test_wallet_history_reflects_exactly_the_movements_made(): void
    {
        $service = app(ZahabWalletService::class);
        $wallet = $service->walletFor(ZahabWallet::SUBJECT_PERSON, 'IDN-MEMBER', 'IDN-MEMBER');
        $service->credit($wallet, 1000, ZahabWalletService::REASON_AID, (string) Str::uuid(), 'IDN-ADMIN');
        $service->debit($wallet, 300, ZahabWalletService::REASON_SERVICE_PURCHASE, (string) Str::uuid(), 'IDN-MEMBER');

        $movements = LedgerEntry::query()->where('wallet_id', $wallet->id)->orderBy('occurred_at')->get();

        self::assertSame(2, $movements->count());
        self::assertSame([LedgerEntry::DIRECTION_CREDIT, LedgerEntry::DIRECTION_DEBIT], $movements->pluck('direction')->all());
        self::assertSame([1000, 300], $movements->pluck('amount')->all());
    }

    // ===== Compensation / reversal (art. 22 du mandat) =====

    public function test_reversing_a_credit_returns_the_balance_to_its_prior_state(): void
    {
        $service = app(ZahabWalletService::class);
        $wallet = $service->walletFor(ZahabWallet::SUBJECT_PERSON, 'IDN-MEMBER', 'IDN-MEMBER');
        $movement = $service->credit($wallet, 1000, ZahabWalletService::REASON_AID, (string) Str::uuid(), 'IDN-ADMIN');

        $reversal = $service->reverse($movement, (string) Str::uuid(), 'IDN-ADMIN');

        self::assertSame(0, $service->balance($wallet));
        self::assertSame(LedgerEntry::TYPE_REVERSAL, $reversal->entry_type);
        self::assertSame($movement->id, $reversal->reverses_entry_id);
        self::assertSame(LedgerEntry::DIRECTION_DEBIT, $reversal->direction);
        // Immutabilité : l'écriture d'origine n'est jamais modifiée par la compensation.
        self::assertSame(LedgerEntry::DIRECTION_CREDIT, $movement->refresh()->direction);
    }

    public function test_reversing_the_same_movement_twice_with_the_same_key_is_idempotent(): void
    {
        $service = app(ZahabWalletService::class);
        $wallet = $service->walletFor(ZahabWallet::SUBJECT_PERSON, 'IDN-MEMBER', 'IDN-MEMBER');
        $movement = $service->credit($wallet, 1000, ZahabWalletService::REASON_AID, (string) Str::uuid(), 'IDN-ADMIN');
        $key = (string) Str::uuid();

        $first = $service->reverse($movement, $key, 'IDN-ADMIN');
        $second = $service->reverse($movement, $key, 'IDN-ADMIN');

        self::assertSame($first->id, $second->id);
        self::assertSame(0, $service->balance($wallet), 'Une double compensation rejouée ne doit jamais créditer deux fois.');
    }

    public function test_a_ledger_entry_that_is_not_a_wallet_movement_cannot_be_reversed_as_one(): void
    {
        // Preuve d'étanchéité : une écriture CAP-061/007B (aucun wallet_id) ne peut jamais être
        // traitée comme un mouvement de Wallet ZAHAB.
        $entry = LedgerEntry::query()->create([
            'source_type' => LedgerEntry::SOURCE_MEMBERSHIP_PAYMENT,
            'source_id' => (string) Str::uuid(),
            'entry_type' => LedgerEntry::TYPE_PAYMENT,
            'amount' => 500,
            'currency' => 'XOF',
            'direction' => LedgerEntry::DIRECTION_CREDIT,
            'payer_core_reference' => 'IDN-X',
            'subject_type' => 'PERSON',
            'subject_reference' => 'IDN-X',
            'occurred_at' => now(),
            'posted_at' => now(),
        ]);

        $this->assertAborts(422, fn () => app(ZahabWalletService::class)->reverse($entry, (string) Str::uuid(), 'IDN-ADMIN'));
    }

    // ===== Montants (art. 6/18/19/20 du mandat) =====

    public function test_a_zero_amount_is_refused(): void
    {
        $service = app(ZahabWalletService::class);
        $wallet = $service->walletFor(ZahabWallet::SUBJECT_PERSON, 'IDN-MEMBER', 'IDN-MEMBER');

        $this->assertAborts(422, fn () => $service->credit($wallet, 0, ZahabWalletService::REASON_AID, (string) Str::uuid(), 'IDN-ADMIN'));
    }

    public function test_a_negative_amount_is_refused(): void
    {
        $service = app(ZahabWalletService::class);
        $wallet = $service->walletFor(ZahabWallet::SUBJECT_PERSON, 'IDN-MEMBER', 'IDN-MEMBER');

        $this->assertAborts(422, fn () => $service->credit($wallet, -500, ZahabWalletService::REASON_AID, (string) Str::uuid(), 'IDN-ADMIN'));
    }

    public function test_a_non_integer_amount_is_impossible_by_type(): void
    {
        $service = app(ZahabWalletService::class);
        $wallet = $service->walletFor(ZahabWallet::SUBJECT_PERSON, 'IDN-MEMBER', 'IDN-MEMBER');
        $amount = 500.5;

        $this->expectException(TypeError::class);
        $service->credit($wallet, $amount, ZahabWalletService::REASON_AID, (string) Str::uuid(), 'IDN-ADMIN');
    }

    // ===== Aucune route générique (art. 14/28 du mandat) =====

    public function test_no_controller_exposes_a_public_credit_or_debit_action(): void
    {
        foreach ([WalletController::class, \App\Http\Controllers\Administration\WalletController::class] as $controller) {
            $reflection = new \ReflectionClass($controller);
            $methodNames = array_map(static fn (\ReflectionMethod $m): string => $m->getName(), $reflection->getMethods(\ReflectionMethod::IS_PUBLIC));
            foreach (['credit', 'debit', 'reverse', 'store', 'update'] as $forbidden) {
                self::assertNotContains($forbidden, $methodNames, "{$controller} ne doit exposer aucune action d'écriture ({$forbidden}).");
            }
        }
    }

    public function test_ledger_service_exposes_no_wallet_credit_or_debit_shortcut(): void
    {
        // Seul ZahabWalletService porte les invariants (verrou, solde, idempotence) : LedgerService
        // ne doit jamais offrir un raccourci qui les contournerait.
        $reflection = new \ReflectionClass(LedgerService::class);
        $methodNames = array_map(static fn (\ReflectionMethod $m): string => $m->getName(), $reflection->getMethods(\ReflectionMethod::IS_PUBLIC));
        self::assertNotContains('credit', $methodNames);
        self::assertNotContains('debit', $methodNames);
    }

    // ===== Autorisations de lecture (art. 25 du mandat) =====

    public function test_a_person_sees_their_own_wallet(): void
    {
        app(ZahabWalletService::class)->walletFor(ZahabWallet::SUBJECT_PERSON, 'IDN-MEMBER', 'IDN-MEMBER');
        $this->signIn('IDN-MEMBER');

        $this->getJson('/finances/zahab')->assertOk()->assertJsonPath('wallet.subject_type', ZahabWallet::SUBJECT_PERSON);
    }

    public function test_a_zumra_leader_sees_the_collective_wallet(): void
    {
        $group = app(ZumraGroupService::class)->create('IDN-LEADER', $this->groupPayload(), 3);
        $this->signIn('IDN-LEADER');

        $this->getJson("/zumra/groupes/{$group->public_reference}/zahab")->assertOk();
    }

    public function test_a_non_leader_cannot_see_the_collective_wallet(): void
    {
        $group = app(ZumraGroupService::class)->create('IDN-LEADER', $this->groupPayload(), 3);
        $this->signIn('IDN-OUTSIDER');

        $this->getJson("/zumra/groupes/{$group->public_reference}/zahab")->assertNotFound();
    }

    public function test_an_organization_manager_sees_the_organization_wallet(): void
    {
        $organization = $this->organization('IDN-FOUNDER');
        $this->signIn('IDN-FOUNDER');

        $this->getJson("/organisations/{$organization->public_reference}/zahab")->assertOk();
    }

    public function test_a_non_manager_cannot_see_the_organization_wallet(): void
    {
        $organization = $this->organization('IDN-FOUNDER');
        $this->signIn('IDN-OUTSIDER');

        $this->getJson("/organisations/{$organization->public_reference}/zahab")->assertNotFound();
    }

    public function test_an_administrator_sees_all_wallets(): void
    {
        app(ZahabWalletService::class)->walletFor(ZahabWallet::SUBJECT_PERSON, 'IDN-MEMBER', 'IDN-MEMBER');
        $admin = $this->administrator();
        $this->signIn($admin);

        $this->getJson('/administration/zahab-wallets')->assertOk();
    }

    public function test_a_non_administrator_cannot_reach_the_administration_wallets_endpoint(): void
    {
        $this->signIn('IDN-MEMBER');

        $this->getJson('/administration/zahab-wallets')->assertForbidden();
    }

    // ===== Compatibilité CAP-061/CAP-062 (art. 3/30/35 du mandat) =====

    public function test_existing_contribution_and_membership_ledger_entries_default_to_credit_with_no_wallet(): void
    {
        // Aucune migration de données rétroactive : les écritures CAP-061/007B déjà projetées ne
        // sont jamais raccordées à un Wallet par ce chantier (ce sera CONTRIBUTION-ZAHAB-001).
        $entry = LedgerEntry::query()->create([
            'source_type' => LedgerEntry::SOURCE_CONTRIBUTION_PAYMENT,
            'source_id' => (string) Str::uuid(),
            'amount' => 500,
            'currency' => 'XOF',
            'payer_core_reference' => 'IDN-X',
            'subject_type' => 'PERSON',
            'subject_reference' => 'IDN-X',
            'occurred_at' => now(),
            'posted_at' => now(),
        ]);

        self::assertSame(LedgerEntry::DIRECTION_CREDIT, $entry->refresh()->direction);
        self::assertNull($entry->wallet_id);
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

    private function groupPayload(): array
    {
        return [
            'name' => 'Atelier ZAHAB '.Str::random(8),
            'domain' => 'Numérique',
            'founding_objective' => 'Former une équipe qui transmet les outils numériques et réalise des solutions utiles aux communautés locales.',
            'participation_mode' => 'HYBRID',
            'internal_charter' => 'Chaque membre respecte la dignité, la hiérarchie, la transmission et les décisions responsables.',
            'assume_primary_lead' => true,
        ];
    }

    private function organization(string $founder): Organization
    {
        $this->fakeCoreOrganizationProvisioning();

        return app(OrganizationService::class)->create($founder, [
            'name' => 'Organisation ZAHAB '.Str::random(6),
            'description' => 'Une structure durable qui porte des responsabilités et des ressources dans la durée.',
            'type' => 'COOPERATIVE',
            'visibility' => Organization::VISIBILITY_PRIVATE,
        ]);
    }

    private function fakeCoreOrganizationProvisioning(): void
    {
        Http::fake(function ($request) {
            $url = (string) $request->url();
            if (str_ends_with($url, '/sessions') && ($request['entite'] ?? null) === 'PRD-GAMAD-005') {
                return Http::response([
                    'jeton' => 'product-bearer-'.Str::random(8), 'entite' => 'PRD-GAMAD-005',
                    'assurance' => 'A1', 'expire_le' => '2026-08-16T23:59:00+00:00',
                ], 201);
            }
            if (str_ends_with($url, '/identites')) {
                return Http::response([
                    'identite' => ['reference' => 'IDN-CORE-ORG-'.Str::random(12), 'etat' => 'ACTIVE', 'assurance' => 'A1'],
                ], 201);
            }
            if (str_ends_with($url, '/organisations')) {
                return Http::response([
                    'resultat' => [
                        'reference' => 'ORG-GAMAD-'.Str::random(8), 'identite_reference' => 'IDN-CORE-ORG-'.Str::random(12),
                        'etat' => 'PREPARATION', 'type_organisation_reference' => 'COOPERATIVE',
                    ],
                ], 201);
            }

            return null;
        });
    }

    private function administrator(): string
    {
        $reference = 'IDN-ADMIN-'.Str::random(6);
        PortalAdministrator::query()->create(['core_identity_reference' => $reference]);

        return $reference;
    }

    private function signIn(string $reference): void
    {
        Http::fake(function (ClientRequest $request) {
            $url = $request->url();
            $method = $request->method();

            if ($method === 'POST' && str_ends_with(rtrim($url, '/'), '/sessions')) {
                $identifier = (string) ($request->data()['identifiant'] ?? $this->coreReference);

                return Http::response(['jeton' => 'bearer-'.$identifier, 'entite' => $identifier, 'assurance' => 'AS1', 'expire_le' => '2026-09-16T23:59:00+00:00'], 201);
            }
            if (str_ends_with($url, '/sessions/current')) {
                $authorization = $request->header('Authorization')[0] ?? '';
                $reference = str_starts_with($authorization, 'Bearer bearer-') ? substr($authorization, strlen('Bearer bearer-')) : $this->coreReference;

                return Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-09-16T23:59:00+00:00']);
            }
            if (str_contains($url, '/identites/')) {
                $reference = rawurldecode((string) basename((string) parse_url($url, PHP_URL_PATH)));

                return Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']);
            }

            return Http::response(['error' => 'UNEXPECTED_TEST_REQUEST'], 500);
        });

        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
