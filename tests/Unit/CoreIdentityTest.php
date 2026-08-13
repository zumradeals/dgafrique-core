<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Identity\CoreIdentity;
use App\Infrastructure\GamadCore\Exceptions\CoreProtocolException;
use PHPUnit\Framework\TestCase;

final class CoreIdentityTest extends TestCase
{
    public function test_it_builds_an_identity_from_the_ctr_01_contract(): void
    {
        $identity = CoreIdentity::fromCorePayload([
            'reference' => 'PER-GAMAD-000000001',
            'type' => 'PERSONNE',
            'libelle' => 'Membre DG Afrique',
            'etat' => 'ACTIF',
            'date_effet' => '2026-08-13',
            'source' => 'SRC-GAMAD-001',
            'regime' => 'INSCRIT',
        ], 'PER-GAMAD-000000001');

        self::assertSame('PER-GAMAD-000000001', $identity->reference);
        self::assertSame('Membre DG Afrique', $identity->label);
        self::assertSame('ACTIF', $identity->state);
    }

    public function test_it_rejects_a_reference_substitution(): void
    {
        $this->expectException(CoreProtocolException::class);

        CoreIdentity::fromCorePayload([
            'reference' => 'PER-GAMAD-000000002',
            'type' => 'PERSONNE',
            'libelle' => 'Autre personne',
            'source' => 'SRC-GAMAD-001',
            'regime' => 'INSCRIT',
        ], 'PER-GAMAD-000000001');
    }
}
