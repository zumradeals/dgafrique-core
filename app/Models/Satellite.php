<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class Satellite extends Model
{
    use HasUuids;

    protected $table = 'dg_satellites';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'product_reference', 'display_name', 'description', 'callback_url',
        'is_active', 'created_by_core_reference',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
