<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Application\Profile\ProfileList;
use PHPUnit\Framework\TestCase;

final class ProfileListTest extends TestCase
{
    public function test_it_normalizes_deduplicates_and_bounds_free_lists(): void
    {
        $text = " Couture\nCommerce;Couture, Numérique\n\nAgriculture";
        self::assertSame(['Couture', 'Commerce', 'Numérique', 'Agriculture'], ProfileList::fromText($text, 4));
    }

    public function test_it_rejects_non_text_input(): void
    {
        self::assertSame([], ProfileList::fromText(['/attaque']));
    }
}
