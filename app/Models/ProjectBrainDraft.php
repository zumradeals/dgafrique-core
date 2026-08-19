<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ProjectBrainDraft extends Model
{
    use HasUuids;

    public const KIND_NEED_CREATE = 'NEED_CREATE';
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $table = 'dg_project_brain_drafts';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['conversation_id', 'project_id', 'actor_core_reference', 'kind', 'status', 'payload', 'result_reference', 'confirmed_at'];
    protected function casts(): array { return ['payload' => 'array', 'confirmed_at' => 'immutable_datetime']; }
}
