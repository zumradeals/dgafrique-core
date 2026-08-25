<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CommunityEvent;
use App\Models\Project;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupActivity;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraProgramMembership;
use App\Models\ZumraProximityShowcase;
use Database\Seeders\ZumraWorldDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ZumraWorldSeederSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_runs_without_error_and_produces_a_believable_network(): void
    {
        (new ZumraWorldDemoSeeder)->run();

        self::assertGreaterThan(20, ZumraGroup::query()->count());
        self::assertGreaterThan(50, ZumraGroupMembership::query()->where('status', ZumraGroupMembership::STATUS_ACTIVE)->count());
        self::assertSame(4, ZumraProximityShowcase::query()->count());
        self::assertGreaterThan(0, ZumraGroupActivity::query()->count());

        $rahman = ZumraGroup::query()->where('name', 'RAHMAN Technology')->sole();
        self::assertSame(15, $rahman->active_member_count);
        self::assertSame('Numériques', $rahman->domain);
        self::assertSame(3, $rahman->activities()->count());
        self::assertTrue(Project::query()->where('zumra_group_id', $rahman->id)->where('name', 'Plateforme de services numériques solidaires')->exists());
        self::assertTrue(CommunityEvent::query()->where('organizer_reference', $rahman->id)->where('title', 'Réunion de constitution')->exists());

        $viewer = 'DEMO-IDN-VIEWER';
        self::assertSame(
            ZumraGroupMembership::STATUS_INVITED,
            ZumraGroupMembership::query()->where('zumra_group_id', $rahman->id)->where('core_identity_reference', $viewer)->sole()->status,
        );
    }

    /**
     * BETA-READY-003 — régression du bug PostgreSQL : demoPerson() gardait auparavant son
     * existence via `whereKey($reference)` (comparaison de $reference — une référence
     * d'identité lisible, ex. "DEMO-IDN-F001" — contre la colonne UUID `id`), qui plante avec
     * SQLSTATE[22P02] sur PostgreSQL (typage strict) et se contentait de ne jamais matcher sur
     * SQLite (typage permissif) — masquant le bug en local/CI. La garde utilise désormais
     * `core_identity_reference`, la colonne métier réelle. Ce test protège le COMPORTEMENT :
     * une identité déjà membre du Programme n'est jamais recréée par une seconde rencontre de
     * la même référence — indépendamment du moteur de base de données. La non-régression
     * PostgreSQL elle-même (l'absence de SQLSTATE[22P02]) a été vérifiée manuellement contre une
     * instance PostgreSQL réelle (voir rapport BETA-READY-003).
     */
    public function test_a_pre_existing_program_membership_is_never_recreated_by_its_identity_reference(): void
    {
        $charter = ZumraCharter::query()->firstOrCreate(
            ['version' => '2026.1'],
            ['title' => 'Charte ZUMRA', 'body' => str_repeat('Respect, transmission et responsabilité partagée. ', 8), 'content_hash' => hash('sha256', 'zumra-world-demo-charter'), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()],
        );

        // Pré-existe déjà, comme le laisserait une exécution précédente du seed : la clé
        // primaire UUID de cette ligne est délibérément SANS AUCUN rapport avec la référence
        // d'identité lisible — exactement le scénario que whereKey($reference) manipulait mal.
        ZumraProgramMembership::query()->create([
            'core_identity_reference' => 'DEMO-IDN-F001',
            'status' => ZumraProgramMembership::STATUS_ACTIVE,
            'accepted_charter_id' => $charter->id,
            'accepted_charter_version' => $charter->version,
            'accepted_charter_hash' => $charter->content_hash,
            'charter_accepted_at' => now(),
            'submitted_at' => now(),
            'activated_at' => now(),
        ]);

        (new ZumraWorldDemoSeeder)->run();

        self::assertSame(1, ZumraProgramMembership::query()->where('core_identity_reference', 'DEMO-IDN-F001')->count());
    }
}
