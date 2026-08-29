<?php

declare(strict_types=1);

namespace Phplrt\Position\Tests;

use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Position\Exception\InvalidArgumentException;
use Phplrt\Position\Position;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class PositionTest extends TestCase
{
    public function testPointsAtTheBeginningByDefault(): void
    {
        $position = new Position();

        Assert::same($position->line, PositionInterface::MIN_LINE);
        Assert::same($position->column, PositionInterface::MIN_COLUMN);
    }

    public function testKeepsTheGivenLineAndColumn(): void
    {
        $position = new Position(42, 13);

        Assert::same($position->line, 42);
        Assert::same($position->column, 13);
    }

    public function testFailsInCaseOfNonPositiveLine(): void
    {
        Expect::exception(InvalidArgumentException::class)->withCode(InvalidArgumentException::CODE_LINE_OUT_OF_RANGE);

        new Position(0, 1);
    }

    public function testFailsInCaseOfNonPositiveColumn(): void
    {
        Expect::exception(InvalidArgumentException::class)->withCode(InvalidArgumentException::CODE_COLUMN_OUT_OF_RANGE);

        new Position(1, 0);
    }
}
