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
    public function testStartingPointsAtTheFirstLineAndColumn(): void
    {
        $position = new PositionFactory()->createAtStarting();

        self::assertSame(PositionInterface::MIN_LINE, $position->line);
        self::assertSame(PositionInterface::MIN_COLUMN, $position->column);
    }

    public function testStartingReadsNothing(): void
    {
        $factory = new PositionFactory();

        // An unreadable file is enough of a source for the position that is
        // known in advance.
        $position = $factory->createFromOffset(new FileSource($this->temp), 0);

        self::assertSame(PositionInterface::MIN_LINE, $position->line);
        self::assertSame(PositionInterface::MIN_COLUMN, $position->column);
    }

    #[DataProvider('sourceAndChunkSizeProvider')]
    public function testEndingPointsAfterTheLastByte(string $code, int $chunkSize): void
    {
        $factory = new PositionFactory($chunkSize);

        $position = $factory->createAtEnding(new StringSource($code));

        self::assertSame(self::calculateLine($code, \strlen($code)), $position->line);
        self::assertSame(self::calculateColumn($code, \strlen($code)), $position->column);
    }

    public function testFailsInCaseOfNonPositiveChunkSize(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(InvalidArgumentException::CODE_NON_POSITIVE_CHUNK_SIZE);

        new PositionFactory(0);
    }
}
