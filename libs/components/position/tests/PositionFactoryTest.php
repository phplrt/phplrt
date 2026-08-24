<?php

declare(strict_types=1);

namespace Phplrt\Position\Tests;

use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Position\Exception\InvalidArgumentException;
use Phplrt\Position\PositionFactory;
use Phplrt\Source\FileSource;
use Phplrt\Source\StringSource;
use PHPUnit\Framework\Attributes\DataProvider;

final class PositionFactoryTest extends TestCase
{
    public function testZeroOffsetReadsNothing(): void
    {
        $factory = new PositionFactory();

        $position = $factory->createFromOffset(new FileSource($this->temp), 0);

        self::assertSame(PositionInterface::MIN_LINE, $position->line);
        self::assertSame(PositionInterface::MIN_COLUMN, $position->column);
    }

    #[DataProvider('sourceAndChunkSizeProvider')]
    public function testUnreachableOffsetPointsAfterTheLastByte(string $code, int $chunkSize): void
    {
        $factory = new PositionFactory($chunkSize);

        $position = $factory->createFromOffset(new StringSource($code), \PHP_INT_MAX);

        self::assertSame(self::calculateLine($code, \strlen($code)), $position->line);
        self::assertSame(self::calculateColumn($code, \strlen($code)), $position->column);
    }

    public function testEveryOffsetOfAnEmptySourcePointsAtItsBeginning(): void
    {
        $factory = new PositionFactory();
        $source = new StringSource();

        foreach ([0, 1, \PHP_INT_MAX] as $offset) {
            $position = $factory->createFromOffset($source, $offset);

            self::assertSame(PositionInterface::MIN_LINE, $position->line);
            self::assertSame(PositionInterface::MIN_COLUMN, $position->column);
        }
    }

    public function testFailsInCaseOfNonPositiveChunkSize(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(InvalidArgumentException::CODE_NON_POSITIVE_CHUNK_SIZE);

        new PositionFactory(0);
    }
}
