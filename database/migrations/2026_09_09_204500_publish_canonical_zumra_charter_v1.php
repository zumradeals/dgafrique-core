<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const VERSION = '1.0';
    private const TITLE = 'Charte du Programme ZUMRA';
    private const SYSTEM_PUBLISHER = 'GAMAD-SYSTEM';

    public function up(): void
    {
        $body = <<<'CHARTER'
Charte du Programme ZUMRA — Version 1.0

Le Programme ZUMRA rassemble des personnes qui choisissent de grandir, de collaborer et d’agir ensemble autour d’objectifs utiles.

En adhérant au Programme ZUMRA, je reconnais les principes suivants :

1. Respect et dignité. Je respecte chaque personne, ses convictions, sa dignité et sa contribution. Les insultes, discriminations, intimidations et appels à la violence n’ont pas leur place dans une ZUMRA.

2. Action constructive. Une ZUMRA est créée pour faire progresser un objectif concret. Son projet principal constitue son cœur opérationnel et peut donner naissance à des besoins, actions et projets dérivés.

3. Responsabilité. Je m’engage à ne pas présenter comme réalisées des actions qui ne le sont pas et à distinguer clairement intentions, travaux en cours et réalisations vérifiables.

4. Collaboration. Les membres cherchent à unir leurs capacités plutôt qu’à rechercher la popularité individuelle. Les responsabilités doivent servir le projet commun.

5. Contributions libres et transparentes. L’adhésion au Programme ZUMRA est gratuite. Toute contribution financière, matérielle ou humaine à un projet ou à un besoin reste distincte de cette adhésion et doit être clairement présentée.

6. Protection des personnes et des données. Je respecte la vie privée des membres et ne publie pas d’informations personnelles, de documents ou de contenus sensibles sans autorisation appropriée.

7. Respect des règles. Les activités conduites dans une ZUMRA doivent respecter les règles de GAMAD, les règles internes de la ZUMRA concernée et les lois applicables.

8. Preuve et progression. Une ZUMRA cherche à transformer les intentions en actions, les actions en preuves et les preuves en progression collective.

L’acceptation de cette charte permet d’adhérer gratuitement au Programme ZUMRA. Elle ne crée pas automatiquement une ZUMRA et ne rend pas automatiquement membre d’une ZUMRA existante.

En acceptant cette charte, je m’engage à respecter ces principes lorsque je crée une ZUMRA, y participe ou y assume une responsabilité.
CHARTER;

        $hash = hash('sha256', self::TITLE."\n".$body);
        $existing = DB::table('dg_zumra_charters')->where('version', self::VERSION)->first();

        if ($existing !== null) {
            if (! hash_equals((string) $existing->content_hash, $hash)) {
                throw new RuntimeException('La version 1.0 de la Charte ZUMRA existe déjà avec un contenu différent. Publication automatique refusée.');
            }

            if (! DB::table('dg_zumra_charters')->where('status', 'PUBLISHED')->exists()) {
                DB::table('dg_zumra_charters')->where('id', $existing->id)->update([
                    'status' => 'PUBLISHED',
                    'published_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return;
        }

        $hasPublishedCharter = DB::table('dg_zumra_charters')->where('status', 'PUBLISHED')->exists();
        $now = now();

        DB::table('dg_zumra_charters')->insert([
            'version' => self::VERSION,
            'title' => self::TITLE,
            'body' => $body,
            'content_hash' => $hash,
            'status' => $hasPublishedCharter ? 'RETIRED' : 'PUBLISHED',
            'published_at' => $now,
            'published_by_core_reference' => self::SYSTEM_PUBLISHER,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        $charter = DB::table('dg_zumra_charters')->where('version', self::VERSION)->first();

        if ($charter === null || (string) $charter->published_by_core_reference !== self::SYSTEM_PUBLISHER) {
            return;
        }

        $isReferenced = DB::table('dg_zumra_program_memberships')
            ->where('accepted_charter_id', $charter->id)
            ->exists();

        if (! $isReferenced) {
            DB::table('dg_zumra_charters')->where('id', $charter->id)->delete();
        }
    }
};
