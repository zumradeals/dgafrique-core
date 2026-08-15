<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up():void{Schema::create('dg_project_match_decisions',function(Blueprint $t):void{$t->uuid('id')->primary();$t->foreignUuid('project_id')->constrained('dg_projects')->cascadeOnDelete();$t->string('actor_core_reference',64);$t->uuid('subject_profile_reference');$t->string('decision',20);$t->timestampsTz();$t->unique(['project_id','actor_core_reference','subject_profile_reference']);});}public function down():void{Schema::dropIfExists('dg_project_match_decisions');}};
