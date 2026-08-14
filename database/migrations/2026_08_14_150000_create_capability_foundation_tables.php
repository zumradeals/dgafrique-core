<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_capability_catalog', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->nullable()->unique();
            $table->string('label', 200);
            $table->string('normalized_label', 200)->unique();
            $table->string('status', 24)->default('ACTIVE')->index();
            $table->json('metadata')->default('{}');
            $table->string('created_by_reference', 64)->nullable();
            $table->string('updated_by_reference', 64)->nullable();
            $table->timestampsTz();
        });

        Schema::create('dg_capability_statements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('core_identity_reference', 64)->index();
            $table->foreignId('catalog_item_id')->nullable()->constrained('dg_capability_catalog')->nullOnDelete();
            $table->string('kind', 32)->index();
            $table->string('label', 200);
            $table->string('normalized_label', 200);
            $table->string('proficiency', 40)->nullable();
            $table->string('status', 24)->default('DECLARED')->index();
            $table->string('visibility', 24)->default('PRIVATE');
            $table->boolean('matching_consent')->default(false);
            $table->string('source', 24)->default('PROFILE');
            $table->timestampTz('attested_at')->nullable();
            $table->timestampTz('archived_at')->nullable()->index();
            $table->timestampsTz();

            $table->unique(
                ['core_identity_reference', 'kind', 'normalized_label'],
                'dg_capability_statement_identity_kind_label_unique'
            );
        });

        $this->backfillExistingProfiles();
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_capability_statements');
        Schema::dropIfExists('dg_capability_catalog');
    }

    private function backfillExistingProfiles(): void
    {
        $now = now();

        DB::table('dg_person_profiles')->orderBy('core_identity_reference')->each(function (object $profile) use ($now): void {
            $dimensions = [
                'POSSESSED' => $profile->existing_skills ?? '[]',
                'LEARNING' => $profile->learning_goals ?? '[]',
                'TRANSMISSION' => $profile->transmission_offers ?? '[]',
            ];

            foreach ($dimensions as $kind => $encodedItems) {
                $items = is_string($encodedItems) ? json_decode($encodedItems, true) : $encodedItems;
                foreach (is_array($items) ? $items : [] as $label) {
                    if (! is_string($label) || trim($label) === '') {
                        continue;
                    }

                    $cleanLabel = mb_substr(trim($label), 0, 200);
                    $normalized = mb_substr(Str::of($cleanLabel)->ascii()->lower()->squish()->toString(), 0, 200);
                    if ($normalized === '') {
                        continue;
                    }

                    DB::table('dg_capability_statements')->insertOrIgnore([
                        'id' => (string) Str::uuid(),
                        'core_identity_reference' => $profile->core_identity_reference,
                        'kind' => $kind,
                        'label' => $cleanLabel,
                        'normalized_label' => $normalized,
                        'status' => 'DECLARED',
                        'visibility' => 'PRIVATE',
                        'matching_consent' => filter_var($profile->orientation_consent ?? false, FILTER_VALIDATE_BOOL),
                        'source' => 'PROFILE',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        });
    }
};
