<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Brouillon de naissance d'un Projet — UIUX-009B. Ne devient jamais une ligne `dg_projects` avant
 * confirmation explicite (voir DESIGN-INVARIANTS/EXPERIENCE-PRODUIT-CANONIQUE §31) : `dg_projects`
 * impose déjà des colonnes NOT NULL et le quota de projets actifs de `ProjectService` compte
 * toute ligne non COMPLETED/ARCHIVED — un brouillon vivant comme ligne `Project` consommerait
 * silencieusement ce quota avant même que la personne ait terminé sa proposition. Distinct et
 * indépendant de `ProjectBrainIntent` (mémoire de conversation IA) : le Cerveau reste une seconde
 * porte d'entrée, jamais une dépendance de ce brouillon déterministe.
 */
final class ProjectDraft extends Model
{
    use HasUuids;

    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_CONFIRMED = 'CONFIRMED';

    public const STATUS_ABANDONED = 'ABANDONED';

    protected $table = 'dg_project_drafts';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['actor_core_reference', 'status', 'current_step', 'payload', 'project_id', 'confirmed_at', 'abandoned_at'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'confirmed_at' => 'immutable_datetime',
            'abandoned_at' => 'immutable_datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
