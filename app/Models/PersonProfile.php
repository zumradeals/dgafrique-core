<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PersonProfile extends Model
{
    protected $table = 'dg_person_profiles';
    protected $primaryKey = 'core_identity_reference';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'core_identity_reference', 'country_code', 'city', 'phone',
        'current_activity', 'education_level', 'existing_skills',
        'starts_without_skill', 'learning_goals', 'interest_domains',
        'intentions', 'participation_mode', 'orientation_consent',
        'orientation_consented_at',
    ];

    protected function casts(): array
    {
        return [
            'existing_skills' => 'array',
            'starts_without_skill' => 'boolean',
            'learning_goals' => 'array',
            'interest_domains' => 'array',
            'intentions' => 'array',
            'orientation_consent' => 'boolean',
            'orientation_consented_at' => 'immutable_datetime',
        ];
    }
}
