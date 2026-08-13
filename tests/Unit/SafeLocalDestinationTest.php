<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Application\Identity\SafeLocalDestination;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SafeLocalDestinationTest extends TestCase
{
    #[DataProvider('unsafeDestinations')]
    public function test_it_rejects_external_or_malformed_destinations(mixed $destination): void
    {
        self::assertSame('/espace', SafeLocalDestination::sanitize($destination));
    }

    /** @return array<string, array{mixed}> */
    public static function unsafeDestinations(): array
    {
        return [
            'absent' => [null],
            'external' => ['https://example.com/vol'],
            'protocol relative' => ['//example.com/vol'],
            'backslash ambiguity' => ['/\\evil.example/vol'],
            'javascript' => ['javascript:alert(1)'],
            'relative' => ['espace'],
            'control character' => ["/espace\r\nX-Test: oui"],
            'tableau hostile' => [['/espace']],
        ];
    }

    public function test_it_keeps_a_local_path_and_query_string(): void
    {
        self::assertSame('/formations?page=2', SafeLocalDestination::sanitize('/formations?page=2'));
    }
}
