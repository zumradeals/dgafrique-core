<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use App\Infrastructure\GamadCore\Exceptions\CoreProtocolException;
use DateTimeImmutable;
use Throwable;

final readonly class CoreSession
{
    public function __construct(
        public string $entity,
        public string $assurance,
        public DateTimeImmutable $expiresAt,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromCurrentPayload(array $payload): self
    {
        foreach (['entite', 'assurance', 'expire_le'] as $field) {
            if (!isset($payload[$field]) || !is_string($payload[$field]) || trim($payload[$field]) === '') {
                throw new CoreProtocolException("Champ de session Core manquant ou invalide : {$field}.");
            }
        }

        try {
            $expiresAt = new DateTimeImmutable($payload['expire_le']);
        } catch (Throwable) {
            throw new CoreProtocolException('Échéance de session Core invalide.');
        }

        return new self($payload['entite'], $payload['assurance'], $expiresAt);
    }
}
