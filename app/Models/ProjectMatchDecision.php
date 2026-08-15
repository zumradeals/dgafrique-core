<?php
declare(strict_types=1); namespace App\Models; use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model;
final class ProjectMatchDecision extends Model{use HasUuids;public const HIDDEN='HIDDEN';protected $table='dg_project_match_decisions';public $incrementing=false;protected $keyType='string';protected $fillable=['project_id','actor_core_reference','subject_profile_reference','decision'];}
