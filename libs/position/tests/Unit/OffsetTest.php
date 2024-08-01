<?php

declare(strict_types=1);

namespace Phplrt\Position\Tests\Unit;

use Phplrt\Position\Position;
use PHPUnit\Framework\Attributes\DataProvider;

class OffsetTest extends TestCase
{
    #[DataProvider('provider')]
    public function testOffsetOverflow(string $text): void
    {
        $position = Position::fromOffset($text, \PHP_INT_MAX);

        $this->assertSame(\strlen($text), $position->getOffset());
    }

    #[DataProvider('provider')]
    public function testOffsetUnderflow(string $text): void
    {
        $position = Position::fromOffset($text, \PHP_INT_MIN);

        $this->assertSame(0, $position->getOffset());
    }
}
