<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class CapabilityCatalogItem extends Model
{
    protected $table = 'dg_capability_catalog';

    protected $fillable = [
        'code', 'label', 'normalized_label', 'status', 'metadata',
        'created_by_reference', 'updated_by_reference',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }
}
