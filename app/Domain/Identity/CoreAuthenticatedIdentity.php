<?php

declare(strict_types=1);

namespace App\Domain\Identity;

final readonly class CoreAuthenticatedIdentity
{
    public function __construct(
        public CoreIdentity $identity,
        public CoreSession $session,
        #[\SensitiveParameter]
        private string $bearerToken,
    ) {}

    public function bearerToken(): string
    {
        return $this->bearerToken;
    }
}
