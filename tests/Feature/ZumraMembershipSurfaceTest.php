<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Zahab\ZahabWalletService;
use App\Models\ZahabWallet;
use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ZumraMembershipSurfaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('payments.membership.enabled', true);
        config()->set('payments.membership.amount', 500);
        config()->set('payments.membership.currency', 'XOF');
    }

    public function test_a_gamad_member_without_zumra_membership_can_activate_it_then_open_creation(): void
    {
        $identity = 'IDN-MEMBERSHIP-JOURNEY';
        $body = str_repeat('Respect, transmission et construction collective. ', 4);
        $charter = ZumraCharter::query()->create([
            'version' => '2026.1',
            'title' => 'Charte du Programme ZUMRA',
            'body' => $body,
            'content_hash' => hash('sha256', 'Charte du Programme ZUMRA'."\n".$body),
            'status' => ZumraCharter::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        $this->signIn($identity);

        $this->get(route('zumra.membership.show'))
            ->assertOk()
            ->assertSee('Adhérez au Programme ZUMRA.')
            ->assertSee('Charte du Programme ZUMRA')
            ->assertSee('Préparer mon adhésion');

        $this->post(route('zumra.membership.store'), [
            'charter_id' => $charter->id,
            'accept_charter' => '1',
        ])->assertRedirect(route('zumra.membership.show'));

        $membership = ZumraProgramMembership::query()->where('core_identity_reference', $identity)->sole();
        self::assertSame(ZumraProgramMembership::STATUS_PENDING_PAYMENT, $membership->status);

        $wallet = app(ZahabWalletService::class)->walletFor(ZahabWallet::SUBJECT_PERSON, $identity, $identity);
        app(ZahabWalletService::class)->credit(
            $wallet,
            1000,
            ZahabWalletService::REASON_AID,
            (string) Str::uuid(),
            'IDN-ADMIN',
        );

        $this->get(route('zumra.membership.show'))
            ->assertOk()
            ->assertSee('Wallet ZAHAB')
            ->assertSee('Payer 500 F CFA avec mon Wallet');

        $this->post(route('zumra.payment.zahab.store'))
            ->assertRedirect(route('zumra.membership.show'));

        self::assertSame(ZumraProgramMembership::STATUS_ACTIVE, $membership->refresh()->status);

        $this->get(route('zumra.membership.show'))
            ->assertOk()
            ->assertSee('Vous pouvez maintenant faire naître une ZUMRA.')
            ->assertSee('Créer une ZUMRA');

        $this->get(route('zumra.groups.create'))
            ->assertOk()
            ->assertSee('Faire naître une ZUMRA');
    }

    private function signIn(string $reference): void
    {
        Http::fake(function (ClientRequest $request) use ($reference) {
            $url = $request->url();
            $method = $request->method();

            if ($method === 'POST' && str_ends_with(rtrim($url, '/'), '/sessions')) {
                return Http::response([
                    'jeton' => 'bearer-'.$reference,
                    'entite' => $reference,
                    'assurance' => 'AS1',
                    'expire_le' => '2026-09-16T23:59:00+00:00',
                ], 201);
            }
            if (str_ends_with($url, '/sessions/current')) {
                return Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-09-16T23:59:00+00:00']);
            }
            if (str_contains($url, '/identites/')) {
                return Http::response([
                    'reference' => $reference,
                    'type' => 'personne',
                    'libelle' => 'Membre GAMAD',
                    'etat' => 'ACTIF',
                    'source' => 'CORE',
                    'regime' => 'INSCRIT_AU_REGISTRE',
                ]);
            }

            return Http::response(['error' => 'UNEXPECTED_TEST_REQUEST'], 500);
        });

        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])
            ->assertRedirect('/espace');
    }
}
