<?php

declare(strict_types=1);

namespace Phplrt\Position\Tests;

use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Position\Position;
use Phplrt\Position\PositionFactory;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
final class CreateOffsetFromPositionTest extends TestCase
{
    #[DataProvider('sourceAndChunkSizeProvider')]
    public function testEveryOffsetIsRestoredFromItsPosition(string $code, int $chunkSize): void
    {
        $factory = new PositionFactory($chunkSize);
        $source = new StringSource($code);

        for ($offset = 0, $length = \strlen($code); $offset <= $length; ++$offset) {
            $position = $factory->createFromOffset($source, $offset);

            Assert::same($factory->createOffsetFromPosition($source, $position), $offset, \sprintf('Offset %d of %d', $offset, $length));
        }
    }

    public function testColumnBeyondTheEndOfItsLinePointsAtTheEndOfIt(): void
    {
        $factory = new PositionFactory();
        $source = new StringSource("first\nsecond\nthird");

        Assert::same($factory->createOffsetFromPosition($source, new Position(1, 100)), 5);
        Assert::same($factory->createOffsetFromPosition($source, new Position(2, 100)), 12);
        Assert::same($factory->createOffsetFromPosition($source, new Position(3, 100)), 18);
    }

    public function testLineBeyondTheEndOfTheSourcePointsAtTheEndOfIt(): void
    {
        $factory = new PositionFactory();
        $source = new StringSource("first\nsecond");

        Assert::same($factory->createOffsetFromPosition($source, new Position(100, 1)), 12);
    }

    public function testGreatestPositionPointsAtTheEndOfTheSource(): void
    {
        $factory = new PositionFactory();
        $source = new StringSource("first\nsecond");

        $position = new Position(\PHP_INT_MAX, \PHP_INT_MAX);

        Assert::same($factory->createOffsetFromPosition($source, $position), 12);
    }

    public function testEveryPositionOfAnEmptySourcePointsAtItsBeginning(): void
    {
        $factory = new PositionFactory();
        $source = new StringSource();

        Assert::same($factory->createOffsetFromPosition($source, new Position()), 0);
        Assert::same($factory->createOffsetFromPosition($source, new Position(50, 50)), 0);
    }

    public function testPositionBelowTheMinimumPointsAtTheBeginning(): void
    {
        $factory = new PositionFactory();
        $source = new StringSource("first\nsecond");

        $position = new class implements PositionInterface {
            public int $line = 0;
            public int $column = -100;
        };

        Assert::same($factory->createOffsetFromPosition($source, $position), 0);
    }

    #[DataProvider('chunkSizeProvider')]
    public function testOffsetOfTheLineBeginning(int $chunkSize): void
    {
        $factory = new PositionFactory($chunkSize);
        $source = new StringSource("first\n\nthird\nfourth");

        Assert::same($factory->createOffsetFromPosition($source, new Position(1, 1)), 0);
        Assert::same($factory->createOffsetFromPosition($source, new Position(2, 1)), 6);
        Assert::same($factory->createOffsetFromPosition($source, new Position(3, 1)), 7);
        Assert::same($factory->createOffsetFromPosition($source, new Position(4, 1)), 13);
    }
}
