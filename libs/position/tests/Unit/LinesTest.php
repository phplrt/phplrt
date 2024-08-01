<?php

declare(strict_types=1);

namespace Phplrt\Position\Tests\Unit;

use Phplrt\Position\Position;
use PHPUnit\Framework\Attributes\DataProvider;

class LinesTest extends TestCase
{
    #[DataProvider('provider')]
    public function testOffsetOverflow(string $text, int $lines): void
    {
        $position = Position::fromOffset($text, \PHP_INT_MAX);

        $this->assertSame($lines, $position->getLine());
    }

    #[DataProvider('provider')]
    public function testOffsetUnderflow(string $text): void
    {
        $position = Position::fromOffset($text, \PHP_INT_MIN);

        $this->assertSame(1, $position->getLine());
    }

    #[DataProvider('provider')]
    public function testPosition(string $text): void
    {
        $line = 1;

        for ($offset = 0, $length = \strlen($text); $offset < $length; ++$offset) {
            $this->assertSame($line, Position::fromOffset($text, $offset)->getLine());

            if ($text[$offset] === "\n") {
                ++$line;
            }
        }
    }
}
