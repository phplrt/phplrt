<?php

declare(strict_types=1);

namespace Phplrt\Position\Tests\Unit;

use Phplrt\Position\Position;

class ColumnsTest extends TestCase
{
    /**
     * @dataProvider provider
     */
    public function testOffsetOverflow(string $text): void
    {
        $position = Position::fromOffset($text, \PHP_INT_MAX);

        $this->assertSame(1, $position->getColumn());
    }

    /**
     * @dataProvider provider
     */
    public function testOffsetUnderflow(string $text): void
    {
        $position = Position::fromOffset($text, \PHP_INT_MIN);

        $this->assertSame(1, $position->getColumn());
    }

    /**
     * @dataProvider provider
     */
    public function testPosition(string $text): void
    {
        $column = 1;

        for ($offset = 0, $length = \strlen($text); $offset < $length; ++$offset) {
            $this->assertSame($column, Position::fromOffset($text, $offset)->getColumn());

            if ($text[$offset] === "\n") {
                $column = 1;
            } else {
                ++$column;
            }
        }
    }
}
