<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * UIUX-010 — voir la migration : vitrine de démonstration, jamais un moteur de proximité réel.
 */
final class ZumraProximityShowcase extends Model
{
    use HasUuids;

    protected $table = 'dg_zumra_proximity_showcases';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['title', 'activity_label', 'distance_label', 'sort_order'];
}
