<?php

declare(strict_types=1);

namespace Phplrt\Position\Tests;

use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Position\Position;
use Phplrt\Position\PositionFactory;
use Phplrt\Source\StringSource;
use PHPUnit\Framework\Attributes\DataProvider;

final class CreateOffsetFromPositionTest extends TestCase
{
    #[DataProvider('sourceAndChunkSizeProvider')]
    public function testEveryOffsetIsRestoredFromItsPosition(string $code, int $chunkSize): void
    {
        $factory = new PositionFactory($chunkSize);
        $source = new StringSource($code);

        for ($offset = 0, $length = \strlen($code); $offset <= $length; ++$offset) {
            $position = $factory->createFromOffset($source, $offset);

            self::assertSame(
                $offset,
                $factory->createOffsetFromPosition($source, $position),
                \sprintf('Offset %d of %d', $offset, $length),
            );
        }
    }

    public function testColumnBeyondTheEndOfItsLinePointsAtTheEndOfIt(): void
    {
        $factory = new PositionFactory();
        $source = new StringSource("first\nsecond\nthird");

        self::assertSame(5, $factory->createOffsetFromPosition($source, new Position(1, 100)));
        self::assertSame(12, $factory->createOffsetFromPosition($source, new Position(2, 100)));
        self::assertSame(18, $factory->createOffsetFromPosition($source, new Position(3, 100)));
    }

    public function testLineBeyondTheEndOfTheSourcePointsAtTheEndOfIt(): void
    {
        $factory = new PositionFactory();
        $source = new StringSource("first\nsecond");

        self::assertSame(12, $factory->createOffsetFromPosition($source, new Position(100, 1)));
    }

    public function testGreatestPositionPointsAtTheEndOfTheSource(): void
    {
        $factory = new PositionFactory();
        $source = new StringSource("first\nsecond");

        $position = new Position(\PHP_INT_MAX, \PHP_INT_MAX);

        self::assertSame(12, $factory->createOffsetFromPosition($source, $position));
    }

    public function testEveryPositionOfAnEmptySourcePointsAtItsBeginning(): void
    {
        $factory = new PositionFactory();
        $source = new StringSource();

        self::assertSame(0, $factory->createOffsetFromPosition($source, new Position()));
        self::assertSame(0, $factory->createOffsetFromPosition($source, new Position(50, 50)));
    }

    public function testPositionBelowTheMinimumPointsAtTheBeginning(): void
    {
        $factory = new PositionFactory();
        $source = new StringSource("first\nsecond");

        // A position of an implementation that does not hold the one-based
        // invariant of the contract.
        $position = new class () implements PositionInterface {
            public int $line = 0;
            public int $column = -100;
        };

        self::assertSame(0, $factory->createOffsetFromPosition($source, $position));
    }

    #[DataProvider('chunkSizeProvider')]
    public function testOffsetOfTheLineBeginning(int $chunkSize): void
    {
        $factory = new PositionFactory($chunkSize);
        $source = new StringSource("first\n\nthird\nfourth");

        self::assertSame(0, $factory->createOffsetFromPosition($source, new Position(1, 1)));
        self::assertSame(6, $factory->createOffsetFromPosition($source, new Position(2, 1)));
        self::assertSame(7, $factory->createOffsetFromPosition($source, new Position(3, 1)));
        self::assertSame(13, $factory->createOffsetFromPosition($source, new Position(4, 1)));
    }
}
