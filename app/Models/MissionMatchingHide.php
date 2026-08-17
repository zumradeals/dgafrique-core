<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Masquage d'une recommandation, scoppé au viewer et à la Mission. Ne modifie jamais
 * le profil ni la disponibilité de la personne masquée.
 */
final class MissionMatchingHide extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'dg_mission_matching_hides';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['mission_id', 'viewer_core_reference', 'subject_core_reference', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }
}
