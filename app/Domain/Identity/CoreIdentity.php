<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use App\Infrastructure\GamadCore\Exceptions\CoreProtocolException;

final readonly class CoreIdentity
{
    public function __construct(
        public string $reference,
        public string $type,
        public string $label,
        public ?string $state,
        public string $source,
        public string $regime,
        public ?string $effectiveAt = null,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromCorePayload(array $payload, string $expectedReference): self
    {
        foreach (['reference', 'type', 'libelle', 'source', 'regime'] as $field) {
            if (!isset($payload[$field]) || !is_string($payload[$field]) || trim($payload[$field]) === '') {
                throw new CoreProtocolException("Champ Core manquant ou invalide : {$field}.");
            }
        }

        if (!hash_equals($expectedReference, $payload['reference'])) {
            throw new CoreProtocolException('La référence retournée par Core ne correspond pas à la référence demandée.');
        }

        return new self(
            reference: $payload['reference'],
            type: $payload['type'],
            label: $payload['libelle'],
            state: self::nullableString($payload['etat'] ?? null),
            source: $payload['source'],
            regime: $payload['regime'],
            effectiveAt: self::nullableString($payload['date_effet'] ?? null),
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? $value : null;
    }
}
