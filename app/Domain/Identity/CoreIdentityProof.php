<?php

declare(strict_types=1);

namespace App\Domain\Identity;

final readonly class CoreIdentityProof
{
    public function __construct(
        public CoreIdentity $identity,
        public CoreSession $session,
    ) {}
}
