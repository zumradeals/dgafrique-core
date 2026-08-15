<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PersonProfile;
use App\Models\ZumraCard;
use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use App\Models\ZumraProgramMembershipEvent;
use App\Models\PortalAdministrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

final class ZumraCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_card_is_never_issued_before_active_membership(): void
    {
        $this->membership(ZumraProgramMembership::STATUS_PENDING_PAYMENT);
        $this->signIn();

        $this->get('/zumra/carte')->assertOk()->assertSee('Carte non délivrée');
        self::assertSame(0, ZumraCard::query()->count());
    }

    public function test_an_active_member_receives_one_verifiable_card(): void
    {
        $this->membership(ZumraProgramMembership::STATUS_ACTIVE);
        PersonProfile::query()->create(['core_identity_reference' => 'IDN-PER-000000008', 'country_code' => 'CI']);
        $this->signIn();

        $this->get('/zumra/carte')->assertOk()->assertSee('Membre ZUMRA')->assertSee('CI');
        $this->get('/zumra/carte')->assertOk();

        $card = ZumraCard::query()->sole();
        self::assertSame('IDN-PER-000000008', $card->core_identity_reference);
        self::assertSame(1, ZumraProgramMembershipEvent::query()->where('event', 'CARD_ISSUED')->count());

        $url = URL::signedRoute('zumra.card.verify', ['card' => $card]);
        $this->get($url)->assertOk()->assertSee('Carte active')->assertSee($card->public_reference);
    }

    public function test_an_unsigned_or_modified_verification_url_is_rejected(): void
    {
        $this->membership(ZumraProgramMembership::STATUS_ACTIVE);
        $this->signIn();
        $this->get('/zumra/carte');
        $card = ZumraCard::query()->sole();

        $this->get(route('zumra.card.verify', $card))->assertForbidden();
        $this->get(URL::signedRoute('zumra.card.verify', ['card' => $card]).'&altered=1')->assertForbidden();
    }

    public function test_suspension_is_immediately_visible_without_deleting_the_card(): void
    {
        $membership = $this->membership(ZumraProgramMembership::STATUS_ACTIVE);
        $this->signIn();
        $this->get('/zumra/carte');
        $card = ZumraCard::query()->sole();
        $membership->update(['status' => ZumraProgramMembership::STATUS_SUSPENDED, 'suspended_at' => now()]);

        $this->get(URL::signedRoute('zumra.card.verify', ['card' => $card]))
            ->assertOk()->assertSee('Carte suspendue')->assertSee('ne doit pas être présentée comme une adhésion active');
        self::assertSame(1, ZumraCard::query()->count());
    }

    public function test_a_revoked_card_cannot_be_presented_as_active(): void
    {
        $this->membership(ZumraProgramMembership::STATUS_ACTIVE);
        $this->signIn();
        $this->get('/zumra/carte');
        $card = ZumraCard::query()->sole();
        $card->update(['status' => ZumraCard::STATUS_REVOKED, 'revoked_at' => now(), 'revocation_reason' => 'Réémission contrôlée']);

        $this->get(URL::signedRoute('zumra.card.verify', ['card' => $card]))
            ->assertOk()->assertSee('Carte révoquée')->assertDontSee('Carte active');
    }

    public function test_an_administrator_revokes_with_reason_and_the_member_receives_a_new_card(): void
    {
        $this->membership(ZumraProgramMembership::STATUS_ACTIVE);
        PortalAdministrator::query()->create(['core_identity_reference' => 'IDN-PER-000000008']);
        $this->signIn();
        $this->get('/zumra/carte');
        $oldCard = ZumraCard::query()->sole();

        $this->post(route('administration.zumra.card.revoke', $oldCard), ['reason' => 'Réémission nécessaire après perte.'])
            ->assertRedirect()->assertSessionHas('status');
        self::assertSame(ZumraCard::STATUS_REVOKED, $oldCard->refresh()->status);
        self::assertSame(1, ZumraProgramMembershipEvent::query()->where('event', 'CARD_REVOKED')->count());

        $this->get('/zumra/carte')->assertOk();
        self::assertSame(2, ZumraCard::query()->count());
        self::assertSame(1, ZumraCard::query()->where('status', ZumraCard::STATUS_ACTIVE)->count());
        $this->get(URL::signedRoute('zumra.card.verify', ['card' => $oldCard]))->assertSee('Carte révoquée');
    }

    private function membership(string $status): ZumraProgramMembership
    {
        $body = str_repeat('Respect, transmission et construction collective. ', 4);
        $charter = ZumraCharter::query()->create([
            'version' => '2026.1', 'title' => 'Charte du Programme ZUMRA', 'body' => $body,
            'content_hash' => hash('sha256', 'Charte du Programme ZUMRA'."\n".$body),
            'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now(),
        ]);

        return ZumraProgramMembership::query()->create([
            'core_identity_reference' => 'IDN-PER-000000008', 'status' => $status,
            'accepted_charter_id' => $charter->id, 'accepted_charter_version' => $charter->version,
            'accepted_charter_hash' => $charter->content_hash, 'charter_accepted_at' => now(),
            'submitted_at' => now(), 'activated_at' => $status === ZumraProgramMembership::STATUS_ACTIVE ? now() : null,
        ]);
    }

    private function signIn(): void
    {
        $reference = 'IDN-PER-000000008';
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer', 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-16T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre ZUMRA', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-16T23:59:00+00:00']),
        ]);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
