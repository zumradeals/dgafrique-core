<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PortalAdministrator;
use App\Models\PortalSetting;
use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use App\Models\ZumraProgramMembershipEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ZumraProgramMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_membership_can_be_created_without_a_published_charter(): void
    {
        $this->signIn();
        $this->get('/zumra/adhesion')->assertOk()->assertSee('Aucune charte n’est actuellement publiée');
        self::assertSame(0, ZumraProgramMembership::query()->count());
    }

    public function test_a_member_accepts_the_current_charter_without_becoming_active_or_being_charged(): void
    {
        $charter = $this->publishedCharter();
        $this->signIn();

        $this->post('/zumra/adhesion', ['charter_id' => $charter->id, 'accept_charter' => '1'])
            ->assertRedirect('/zumra/adhesion')
            ->assertSessionHas('status');

        $membership = ZumraProgramMembership::query()->sole();
        self::assertSame('IDN-PER-000000008', $membership->core_identity_reference);
        self::assertSame(ZumraProgramMembership::STATUS_PENDING_PAYMENT, $membership->status);
        self::assertSame('2026.1', $membership->accepted_charter_version);
        self::assertSame($charter->content_hash, $membership->accepted_charter_hash);
        self::assertNull($membership->activated_at);
        self::assertSame('DOSSIER_SUBMITTED', ZumraProgramMembershipEvent::query()->sole()->event);

        $this->get('/zumra/adhesion')->assertOk()->assertSee('Aucun débit en attente')->assertSee('Paiement bientôt disponible');
    }

    public function test_acceptance_is_bound_to_the_submitted_published_charter(): void
    {
        $retired = $this->publishedCharter(status: ZumraCharter::STATUS_RETIRED);
        $this->signIn();

        $this->post('/zumra/adhesion', ['charter_id' => $retired->id, 'accept_charter' => '1'])->assertNotFound();
        self::assertSame(0, ZumraProgramMembership::query()->count());
    }

    public function test_an_administrator_publishes_an_immutable_charter_version_and_configures_copy(): void
    {
        PortalAdministrator::query()->create(['core_identity_reference' => 'IDN-PER-000000008']);
        $this->signIn();
        $body = str_repeat('Respect, transmission et construction collective. ', 4);

        $this->post('/administration/programme-zumra/charte', [
            'version' => '2026.2', 'charter_title' => 'Charte communautaire', 'charter_body' => $body,
        ])->assertRedirect();
        $this->put('/administration/programme-zumra', [
            'title' => 'Entrer dans le Programme ZUMRA', 'introduction' => 'Présentation administrable.',
            'commitment_notice' => 'Engagement unique et contribution libre.', 'pending_payment_title' => 'Dossier prêt',
            'pending_payment_help' => 'En attente du paiement officiel.', 'accept_label' => 'J’accepte la charte.',
            'submit_label' => 'Continuer', 'payment_unavailable_notice' => 'Aucun paiement disponible.',
        ])->assertRedirect();

        self::assertSame('IDN-PER-000000008', ZumraCharter::query()->sole()->published_by_core_reference);
        self::assertSame('IDN-PER-000000008', PortalSetting::query()->findOrFail('zumra.program.presentation')->updated_by_core_reference);
        $this->get('/zumra/adhesion')->assertOk()->assertSee('Entrer dans le Programme ZUMRA')->assertSee('Continuer');
    }

    private function publishedCharter(string $status = ZumraCharter::STATUS_PUBLISHED): ZumraCharter
    {
        $body = str_repeat('Respect, transmission et construction collective. ', 4);

        return ZumraCharter::query()->create([
            'version' => '2026.1', 'title' => 'Charte du Programme ZUMRA', 'body' => $body,
            'content_hash' => hash('sha256', 'Charte du Programme ZUMRA'."\n".$body),
            'status' => $status, 'published_at' => now(),
        ]);
    }

    private function signIn(): void
    {
        $reference = 'IDN-PER-000000008';
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer', 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-15T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre ZUMRA', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-15T23:59:00+00:00']),
        ]);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
