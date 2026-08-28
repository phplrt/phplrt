<?php

declare(strict_types=1);

namespace Phplrt\Position\Tests;

use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Position\PositionFactory;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
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

            Assert::same($position->line, self::calculateLine($code, $offset), $message);
            Assert::same($position->column, self::calculateColumn($code, $offset), $message);
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

            Assert::same($position->line, self::calculateLine($code, $offset), $message);
            Assert::same($position->column, self::calculateColumn($code, $offset), $message);
        }
    }

    #[DataProvider('sourceProvider')]
    public function testOffsetBeyondTheEndPointsAtTheEnd(string $code): void
    {
        $factory = new PositionFactory();
        $source = new StringSource($code);

        $position = $factory->createFromOffset($source, \PHP_INT_MAX);

        Assert::same($position->line, self::calculateLine($code, \strlen($code)));
        Assert::same($position->column, self::calculateColumn($code, \strlen($code)));
    }

    #[DataProvider('sourceProvider')]
    public function testOffsetBeforeTheBeginningPointsAtTheBeginning(string $code): void
    {
        $factory = new PositionFactory();

        $position = $factory->createFromOffset(new StringSource($code), \PHP_INT_MIN);

        Assert::same($position->line, PositionInterface::MIN_LINE);
        Assert::same($position->column, PositionInterface::MIN_COLUMN);
    }

    public function testWindowsLineDelimiterBelongsToItsOwnLine(): void
    {
        $factory = new PositionFactory();
        $source = new StringSource("first\r\nsecond");

        Assert::same($factory->createFromOffset($source, 5)->line, 1);
        Assert::same($factory->createFromOffset($source, 5)->column, 6);

        Assert::same($factory->createFromOffset($source, 6)->line, 1);
        Assert::same($factory->createFromOffset($source, 6)->column, 7);

        Assert::same($factory->createFromOffset($source, 7)->line, 2);
        Assert::same($factory->createFromOffset($source, 7)->column, 1);
    }
}
