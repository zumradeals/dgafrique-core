<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

final class PersonProfile extends Model
{
    // CAP-025 — trois états en langage humain, jamais un calendrier de créneaux (fiche §5).
    public const AVAILABILITY_OPEN = 'OPEN';
    public const AVAILABILITY_LIMITED = 'LIMITED';
    public const AVAILABILITY_PAUSED = 'PAUSED';

    public const AVAILABILITY_LABELS = [
        self::AVAILABILITY_OPEN => 'Disponible pour de nouvelles sollicitations',
        self::AVAILABILITY_LIMITED => 'Disponibilité réduite',
        self::AVAILABILITY_PAUSED => 'En pause pour le moment',
    ];

    protected $table = 'dg_person_profiles';
    protected $primaryKey = 'core_identity_reference';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'core_identity_reference', 'country_code', 'city', 'phone',
        'current_activity', 'education_level', 'existing_skills',
        'starts_without_skill', 'transmission_offers', 'learning_goals',
        'experience_highlights', 'experience_proofs', 'declared_needs',
        'interest_domains', 'intentions', 'participation_mode',
        'collaboration_preferences', 'orientation_consent',
        'orientation_consented_at', 'discovery_reference',
        'discovery_display_name', 'discovery_bio', 'discovery_consent',
        'discovery_consented_at', 'availability_status',
        'availability_note', 'availability_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'existing_skills' => 'array',
            'starts_without_skill' => 'boolean',
            'transmission_offers' => 'array',
            'learning_goals' => 'array',
            'experience_highlights' => 'array',
            'experience_proofs' => 'array',
            'declared_needs' => 'array',
            'interest_domains' => 'array',
            'intentions' => 'array',
            'collaboration_preferences' => 'array',
            'orientation_consent' => 'boolean',
            'orientation_consented_at' => 'immutable_datetime',
            'discovery_consent' => 'boolean',
            'discovery_consented_at' => 'immutable_datetime',
            'availability_updated_at' => 'immutable_datetime',
        ];
    }

    public function capabilityStatements(): HasMany
    {
        return $this->hasMany(CapabilityStatement::class, 'core_identity_reference', 'core_identity_reference');
    }
}
