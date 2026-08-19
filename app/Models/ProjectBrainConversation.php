<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProjectBrainConversation extends Model
{
    use HasUuids;

    protected $table = 'dg_project_brain_conversations';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['project_id', 'actor_core_reference', 'title'];

    public function messages(): HasMany
    {
        return $this->hasMany(ProjectBrainMessage::class, 'conversation_id');
    }

    public function drafts(): HasMany
    {
        return $this->hasMany(ProjectBrainDraft::class, 'conversation_id');
    }
}
