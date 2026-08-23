<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Missions\MissionAssignmentService;
use App\Application\Missions\MissionWorkflow;
use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\MissionAssignment;
use App\Models\NotificationRead;
use App\Models\PersonProfile;
use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

final class NotificationHttpSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_page_renders_with_actionable_and_recent_sections(): void
    {
        $this->signIn('IDN-VISITOR');

        $this->get('/notifications')
            ->assertOk()
            ->assertSee('Notifications')
            ->assertSee('À traiter')
            ->assertSee('Récentes');
    }

    public function test_notifications_link_is_reachable_from_the_account_menu(): void
    {
        $this->signIn('IDN-NAV');

        $this->get('/espace')->assertOk()->assertSee(route('notifications.index'), false);
    }

    public function test_pending_mission_invitation_is_visible_on_the_notifications_page(): void
    {
        $this->activateProgram('IDN-OWNER-HTTP');
        $project = app(ProjectService::class)->create('IDN-OWNER-HTTP', $this->projectPayload(), (new ProjectConfiguration)->defaults());

        $workflow = app(MissionWorkflow::class);
        $assignments = app(MissionAssignmentService::class);
        $inviteeRef = $this->discoverableProfile('IDN-INVITEE-HTTP', 'Invité HTTP');

        $mission = $workflow->create('IDN-OWNER-HTTP', 'PROJECT', $project->public_reference, [
            'title' => 'Coordonner la logistique',
            'description' => 'Organiser le transport et la logistique de la session.',
        ]);
        $mission = $workflow->propose($mission, 'IDN-OWNER-HTTP');
        $mission = $workflow->officialize($mission, 'IDN-OWNER-HTTP', ['expected_result' => 'Logistique prête.']);
        $assignments->invite($mission, 'IDN-OWNER-HTTP', $inviteeRef, MissionAssignment::ROLE_EXECUTOR);

        $this->signIn('IDN-INVITEE-HTTP');
        $this->get('/notifications')->assertOk()->assertSee('Coordonner la logistique');
    }

    /** Fiche §1/§9 : jamais de badge numérique ni de mécanique de pression à réagir vite. */
    public function test_notifications_view_never_renders_a_numeric_badge_or_counter(): void
    {
        $view = file_get_contents(resource_path('views/notifications/index.blade.php'));
        self::assertIsString($view);
        self::assertStringNotContainsString('count()', $view, 'La page Notifications ne doit jamais afficher de compteur numérique.');
        self::assertStringNotContainsString('->count(', $view);

        $topbar = file_get_contents(resource_path('views/components/dg/topbar.blade.php'));
        self::assertStringContainsString("route('notifications.index')", $topbar);
        self::assertStringNotContainsString('unread', strtolower($topbar));
    }

    public function test_landing_and_notification_business_tables_never_seed_demo_data(): void
    {
        $this->get('/')->assertOk();
        self::assertSame(0, NotificationRead::query()->count());
    }

    private function zumraFor(string $actor): string
    {
        return app(ZumraGroupService::class)->create($actor, [
            'name' => 'ZUMRA '.$actor.' '.uniqid(), 'domain' => 'Général',
            'founding_objective' => str_repeat('Ancrer les projets de test dans une ZUMRA réelle. ', 2),
            'participation_mode' => 'HYBRID', 'internal_charter' => str_repeat('Respect, transmission et responsabilité partagée. ', 4),
            'assume_primary_lead' => true,
        ])->public_reference;
    }

    private function projectPayload(array $overrides = []): array
    {
        return array_replace([
            'owner_type' => 'PERSON', 'group_reference' => null, 'zumra_group_reference' => $this->zumraFor('IDN-OWNER-HTTP'), 'source_need_reference' => null,
            'name' => 'Atelier '.uniqid(),
            'summary' => 'Créer un espace pratique où des jeunes peuvent apprendre ensemble et produire des services utiles.',
            'problem' => 'Des jeunes motivés disposent de peu de cadres pratiques pour apprendre et transformer leurs acquis.',
            'proposed_solution' => 'Mettre en place un atelier progressif avec transmission entre pairs et accompagnement.',
            'beneficiaries' => 'Jeunes débutants et personnes en reconversion dans la commune.',
            'domain' => 'DIGITAL', 'participation_mode' => 'HYBRID', 'location' => 'Abidjan',
            'objectives' => "Former une première équipe\nProduire trois services pilotes",
            'required_capabilities' => "Formation numérique\nGestion de projet",
            'required_resources' => "Ordinateurs\nConnexion internet",
            'risks' => "Disponibilité irrégulière\nAccès au matériel",
            'milestones' => "Constituer l'équipe\nPréparer le lieu\nLancer le pilote",
            'property_regime' => 'PERSONAL_SUPPORTED', 'visibility' => 'PUBLIC',
        ], $overrides);
    }

    private function discoverableProfile(string $reference, string $name): string
    {
        $profile = PersonProfile::query()->create([
            'core_identity_reference' => $reference,
            'orientation_consent' => true,
            'orientation_consented_at' => now(),
            'discovery_reference' => (string) Str::uuid(),
            'discovery_display_name' => $name,
            'discovery_consent' => true,
            'discovery_consented_at' => now(),
        ]);

        return $profile->discovery_reference;
    }

    private function activateProgram(string $reference): void
    {
        $body = str_repeat('Respect et transmission. ', 5);
        $charter = ZumraCharter::query()->firstOrCreate(
            ['version' => '2026.1'],
            ['title' => 'Charte ZUMRA', 'body' => $body, 'content_hash' => hash('sha256', $body), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()]
        );
        ZumraProgramMembership::query()->create([
            'core_identity_reference' => $reference,
            'status' => ZumraProgramMembership::STATUS_ACTIVE,
            'accepted_charter_id' => $charter->id,
            'accepted_charter_version' => $charter->version,
            'accepted_charter_hash' => $charter->content_hash,
            'charter_accepted_at' => now(),
            'submitted_at' => now(),
            'activated_at' => now(),
        ]);
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-20T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre DG Afrique', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-20T23:59:00+00:00']),
        ]);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
