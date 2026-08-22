<?php

declare(strict_types=1);

namespace Phplrt\Position\Tests;

use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Position\PositionFactory;
use Phplrt\Source\StringSource;
use PHPUnit\Framework\Attributes\DataProvider;

final class CreateFromOffsetTest extends TestCase
{
    #[DataProvider('sourceAndChunkSizeProvider')]
    public function testPositionOfEveryOffset(string $code, int $chunkSize): void
    {
        $factory = new PositionFactory($chunkSize);
        $source = new StringSource($code);

        for ($offset = 0, $length = \strlen($code); $offset <= $length; ++$offset) {
            $position = $factory->createFromOffset($source, $offset);
            $message = \sprintf('Offset %d of %d', $offset, $length);

            self::assertSame(self::calculateLine($code, $offset), $position->line, $message);
            self::assertSame(self::calculateColumn($code, $offset), $position->column, $message);
        }
    }

    #[DataProvider('chunkSizeProvider')]
    public function testPositionInsideALargeSource(int $chunkSize): void
    {
        $code = \str_repeat("0123456789\n", 1000);

        $factory = new PositionFactory($chunkSize);
        $source = new StringSource($code);

        foreach ([0, 1, 10, 11, 12, 5499, 5500, 10999, 11000] as $offset) {
            $position = $factory->createFromOffset($source, $offset);
            $message = \sprintf('Offset %d', $offset);

            self::assertSame(self::calculateLine($code, $offset), $position->line, $message);
            self::assertSame(self::calculateColumn($code, $offset), $position->column, $message);
        }
    }

    #[DataProvider('sourceProvider')]
    public function testOffsetBeyondTheEndPointsAtTheEnd(string $code): void
    {
        $factory = new PositionFactory();
        $source = new StringSource($code);

        $position = $factory->createFromOffset($source, \PHP_INT_MAX);

        self::assertSame(self::calculateLine($code, \strlen($code)), $position->line);
        self::assertSame(self::calculateColumn($code, \strlen($code)), $position->column);
    }

    #[DataProvider('sourceProvider')]
    public function testOffsetBeforeTheBeginningPointsAtTheBeginning(string $code): void
    {
        $factory = new PositionFactory();

        $position = $factory->createFromOffset(new StringSource($code), \PHP_INT_MIN);

        self::assertSame(PositionInterface::MIN_LINE, $position->line);
        self::assertSame(PositionInterface::MIN_COLUMN, $position->column);
    }

    public function testWindowsLineDelimiterBelongsToItsOwnLine(): void
    {
        $factory = new PositionFactory();
        $source = new StringSource("first\r\nsecond");

        // The "\r" byte is the last character of the first line.
        self::assertSame(1, $factory->createFromOffset($source, 5)->line);
        self::assertSame(6, $factory->createFromOffset($source, 5)->column);

        self::assertSame(1, $factory->createFromOffset($source, 6)->line);
        self::assertSame(7, $factory->createFromOffset($source, 6)->column);

        self::assertSame(2, $factory->createFromOffset($source, 7)->line);
        self::assertSame(1, $factory->createFromOffset($source, 7)->column);
    }
}
