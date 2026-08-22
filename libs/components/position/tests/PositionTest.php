<?php

declare(strict_types=1);

namespace Phplrt\Position\Tests;

use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Position\Exception\InvalidArgumentException;
use Phplrt\Position\Position;

final class PositionTest extends TestCase
{
    public function testPointsAtTheBeginningByDefault(): void
    {
        $position = new Position();

        self::assertSame(PositionInterface::MIN_LINE, $position->line);
        self::assertSame(PositionInterface::MIN_COLUMN, $position->column);
    }

    public function testKeepsTheGivenLineAndColumn(): void
    {
        $position = new Position(42, 13);

        self::assertSame(42, $position->line);
        self::assertSame(13, $position->column);
    }

    public function testFailsInCaseOfNonPositiveLine(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(InvalidArgumentException::CODE_LINE_OUT_OF_RANGE);

        new Position(0, 1);
    }

    public function testFailsInCaseOfNonPositiveColumn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(InvalidArgumentException::CODE_COLUMN_OUT_OF_RANGE);

        new Position(1, 0);
    }
}
