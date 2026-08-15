<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\HasMany; use Illuminate\Database\Eloquent\Relations\HasOne;
final class Project extends Model
{
    use HasUuids;
    public const OWNER_PERSON='PERSON', OWNER_GROUP='GROUP';
    public const STATUS_PROPOSED='PROPOSED', STATUS_ADOPTED='ADOPTED', STATUS_IN_PROGRESS='IN_PROGRESS', STATUS_COMPLETED='COMPLETED', STATUS_ARCHIVED='ARCHIVED';
    public const VISIBILITY_PRIVATE='PRIVATE', VISIBILITY_GROUP='GROUP', VISIBILITY_PROGRAM='PROGRAM', VISIBILITY_PUBLIC='PUBLIC';
    protected $table='dg_projects'; public $incrementing=false; protected $keyType='string';
    protected $fillable=['public_reference','owner_type','owner_reference','initiator_core_reference','source_need_id','name','summary','problem','proposed_solution','beneficiaries','domain','participation_mode','location','objectives','required_capabilities','required_resources','risks','property_regime','visibility','status','maturity','decided_by_core_reference','adopted_at','started_at','completed_at','archived_at'];
    protected function casts(): array { return ['objectives'=>'array','required_capabilities'=>'array','required_resources'=>'array','risks'=>'array','adopted_at'=>'immutable_datetime','started_at'=>'immutable_datetime','completed_at'=>'immutable_datetime','archived_at'=>'immutable_datetime']; }
    public function getRouteKeyName(): string { return 'public_reference'; }
    public function milestones(): HasMany { return $this->hasMany(ProjectMilestone::class)->orderBy('position'); }
    public function events(): HasMany { return $this->hasMany(ProjectEvent::class); }
    public function accompaniment(): HasOne { return $this->hasOne(ProjectAccompaniment::class); }
}
