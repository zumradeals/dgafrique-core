<?php

declare(strict_types=1);

namespace App\Infrastructure\GamadCore\Exceptions;

final class CoreAccountException extends CoreException
{
    public function __construct(
        public readonly string $reason,
        public readonly int $status,
        string $message,
        public readonly ?int $retryAfter = null,
    ) {
        parent::__construct($message);
    }
}
