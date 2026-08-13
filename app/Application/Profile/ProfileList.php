<?php

declare(strict_types=1);

namespace App\Application\Profile;

final class ProfileList
{
    /** @return list<string> */
    public static function fromText(mixed $value, int $maximum = 12): array
    {
        if (!is_string($value)) {
            return [];
        }

        $items = preg_split('/[\r\n,;]+/u', $value) ?: [];
        $items = array_map(static fn (string $item): string => trim($item), $items);
        $items = array_filter($items, static fn (string $item): bool => $item !== '');
        $items = array_values(array_unique($items));

        return array_slice($items, 0, $maximum);
    }

    /** @param mixed $value */
    public static function toText($value): string
    {
        return is_array($value) ? implode("\n", array_filter($value, 'is_string')) : '';
    }
}
