<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model;
final class ProjectEvent extends Model { use HasUuids; protected $table='dg_project_events'; public $incrementing=false; protected $keyType='string'; protected $fillable=['project_id','event','actor_core_reference','context','occurred_at']; protected function casts(): array{return ['context'=>'array','occurred_at'=>'immutable_datetime'];} }
