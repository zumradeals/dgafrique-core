<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use App\Models\ZumraProgramMembershipEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ZumraMembershipSurfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_gamad_member_accepts_the_charter_for_free_then_opens_zumra_creation(): void
    {
        $identity = 'IDN-MEMBERSHIP-JOURNEY';
        $charter = $this->publishedCharter('2026.1');
        $this->signIn($identity);

        $this->get(route('zumra.membership.show'))
            ->assertOk()
            ->assertSee('Adhérez gratuitement au Programme ZUMRA.')
            ->assertSee('Charte du Programme ZUMRA')
            ->assertSee('Accepter la charte et adhérer gratuitement');

        $this->post(route('zumra.membership.store'), [
            'charter_id' => $charter->id,
            'accept_charter' => '1',
        ])->assertRedirect(route('zumra.groups.create'));

        $membership = ZumraProgramMembership::query()->where('core_identity_reference', $identity)->sole();
        self::assertSame(ZumraProgramMembership::STATUS_ACTIVE, $membership->status);
        self::assertNotNull($membership->activated_at);
        self::assertSame($charter->id, $membership->accepted_charter_id);

        $event = ZumraProgramMembershipEvent::query()->where('membership_id', $membership->id)->sole();
        self::assertSame('MEMBERSHIP_ACTIVATED', $event->event);
        self::assertNull($event->from_status);
        self::assertSame(ZumraProgramMembership::STATUS_ACTIVE, $event->to_status);
        self::assertSame('FREE_CHARTER_ACCEPTANCE', $event->context['activation_mode']);

        $this->get(route('zumra.groups.create'))
            ->assertOk()
            ->assertSee('Faire naître une ZUMRA');
    }

    public function test_a_legacy_pending_membership_can_be_activated_for_free_by_reaccepting_the_charter(): void
    {
        $identity = 'IDN-LEGACY-PENDING';
        $charter = $this->publishedCharter('2026.2');
        $membership = ZumraProgramMembership::query()->create([
            'core_identity_reference' => $identity,
            'status' => ZumraProgramMembership::STATUS_PENDING_PAYMENT,
            'accepted_charter_id' => $charter->id,
            'accepted_charter_version' => $charter->version,
            'accepted_charter_hash' => $charter->content_hash,
            'charter_accepted_at' => now()->subDay(),
            'submitted_at' => now()->subDay(),
        ]);
        $this->signIn($identity);

        $this->get(route('zumra.membership.show'))
            ->assertOk()
            ->assertSee('Votre adhésion peut maintenant être activée gratuitement.')
            ->assertSee('Activer gratuitement mon adhésion');

        $this->post(route('zumra.membership.store'), [
            'charter_id' => $charter->id,
            'accept_charter' => '1',
        ])->assertRedirect(route('zumra.groups.create'));

        self::assertSame(ZumraProgramMembership::STATUS_ACTIVE, $membership->refresh()->status);
    }

    private function publishedCharter(string $version): ZumraCharter
    {
        $body = str_repeat('Respect, transmission et construction collective. ', 4);

        return ZumraCharter::query()->create([
            'version' => $version,
            'title' => 'Charte du Programme ZUMRA',
            'body' => $body,
            'content_hash' => hash('sha256', 'Charte du Programme ZUMRA'."\n".$body),
            'status' => ZumraCharter::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
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

        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'test-only'])
            ->assertRedirect('/espace');
    }
}
