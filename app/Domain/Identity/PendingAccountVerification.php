<?php

declare(strict_types=1);

namespace App\Domain\Identity;

final readonly class PendingAccountVerification
{
    public function __construct(
        public string $identity,
        public string $identifierReference,
        public string $verificationReference,
        public string $destination,
        public string $channel,
        public \DateTimeImmutable $expiresAt,
        public bool $delivered,
    ) {}
}
