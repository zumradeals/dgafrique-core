<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ProjectBrainMessage extends Model
{
    use HasUuids;

    public const ROLE_USER = 'USER';
    public const ROLE_ASSISTANT = 'ASSISTANT';
    public const ROLE_SYSTEM = 'SYSTEM';

    protected $table = 'dg_project_brain_messages';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['conversation_id', 'role', 'content', 'meta'];
    protected function casts(): array { return ['meta' => 'array']; }
}
