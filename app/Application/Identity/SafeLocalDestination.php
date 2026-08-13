<?php

declare(strict_types=1);

namespace App\Application\Identity;

final class SafeLocalDestination
{
    public static function sanitize(mixed $destination, string $fallback = '/espace'): string
    {
        if (!is_string($destination) || $destination === '') {
            return $fallback;
        }

        if (
            !str_starts_with($destination, '/')
            || str_starts_with($destination, '//')
            || str_contains($destination, '\\')
            || preg_match('/[\x00-\x1F\x7F]/', $destination) === 1
        ) {
            return $fallback;
        }

        $parts = parse_url($destination);
        if (
            $parts === false
            || isset($parts['host'], $parts['scheme'], $parts['user'], $parts['pass'])
            || !isset($parts['path'])
            || !str_starts_with($parts['path'], '/')
        ) {
            return $fallback;
        }

        return $destination;
    }
}
