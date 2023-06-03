<?php

declare(strict_types=1);

namespace Phplrt\Position\Tests;

use Phplrt\Position\Position;
use PHPUnit\Framework\ExpectationFailedException;
use Phplrt\Source\Exception\NotAccessibleException;

class ColumnsTestCase extends TestCase
{
    /**
     * @dataProvider provider
     * @param string $text
     * @throws ExpectationFailedException
     * @throws NotAccessibleException
     * @throws \RuntimeException
     */
    public function testOffsetOverflow(string $text): void
    {
        $position = Position::fromOffset($text, \PHP_INT_MAX);

        $this->assertSame(1, $position->getColumn());
    }

    /**
     * @dataProvider provider
     * @param string $text
     * @throws ExpectationFailedException
     * @throws NotAccessibleException
     * @throws \RuntimeException
     */
    public function testOffsetUnderflow(string $text): void
    {
        $position = Position::fromOffset($text, \PHP_INT_MIN);

        $this->assertSame(1, $position->getColumn());
    }

    /**
     * @dataProvider provider
     * @param string $text
     * @throws ExpectationFailedException
     * @throws NotAccessibleException
     * @throws \RuntimeException
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
