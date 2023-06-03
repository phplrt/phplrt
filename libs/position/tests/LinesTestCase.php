<?php

declare(strict_types=1);

namespace Phplrt\Position\Tests;

use Phplrt\Position\Position;
use PHPUnit\Framework\ExpectationFailedException;
use Phplrt\Source\Exception\NotAccessibleException;

class LinesTestCase extends TestCase
{
    /**
     * @dataProvider provider
     *
     * @param string $text
     * @param int $lines
     * @throws ExpectationFailedException
     * @throws NotAccessibleException
     * @throws \RuntimeException
     */
    public function testOffsetOverflow(string $text, int $lines): void
    {
        $position = Position::fromOffset($text, \PHP_INT_MAX);

        $this->assertSame($lines, $position->getLine());
    }

    /**
     * @dataProvider provider
     *
     * @param string $text
     * @param int $lines
     * @throws ExpectationFailedException
     * @throws NotAccessibleException
     * @throws \RuntimeException
     */
    public function testOffsetUnderflow(string $text, int $lines): void
    {
        $position = Position::fromOffset($text, \PHP_INT_MIN);

        $this->assertSame(1, $position->getLine());
    }

    /**
     * @dataProvider provider
     *
     * @param string $text
     * @throws ExpectationFailedException
     * @throws NotAccessibleException
     * @throws \RuntimeException
     */
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
