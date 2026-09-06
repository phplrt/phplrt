<?php

declare(strict_types=1);

namespace Phplrt\Position\Tests;

use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Position\Exception\InvalidArgumentException;
use Phplrt\Position\PositionFactory;
use Phplrt\Source\FileSource;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Data\DataSet;
use Testo\Expect;
use Testo\Test;

#[Test]
final class PositionFactoryTest extends TestCase
{
    public function testZeroOffsetReadsNothing(): void
    {
        $factory = new PositionFactory();

        $position = $factory->createFromOffset(new FileSource($this->temp), 0);

        Assert::same($position->line, PositionInterface::MIN_LINE);
        Assert::same($position->column, PositionInterface::MIN_COLUMN);
    }

    #[DataProvider('sourceAndChunkSizeProvider')]
    public function testUnreachableOffsetPointsAfterTheLastByte(string $code, int $chunkSize): void
    {
        $factory = new PositionFactory($chunkSize);

        $position = $factory->createFromOffset(new StringSource($code), \PHP_INT_MAX);

        Assert::same($position->line, self::calculateLine($code, \strlen($code)));
        Assert::same($position->column, self::calculateColumn($code, \strlen($code)));
    }

    #[DataSet([0], 'start')]
    #[DataSet([1], 'one')]
    #[DataSet([\PHP_INT_MAX], 'max')]
    public function testEveryOffsetOfAnEmptySourcePointsAtItsBeginning(int $offset): void
    {
        $position = (new PositionFactory())->createFromOffset(new StringSource(), $offset);

        Assert::same($position->line, PositionInterface::MIN_LINE);
        Assert::same($position->column, PositionInterface::MIN_COLUMN);
    }

    public function testFailsInCaseOfNonPositiveChunkSize(): void
    {
        Expect::exception(InvalidArgumentException::class)->withCode(InvalidArgumentException::CODE_NON_POSITIVE_CHUNK_SIZE);

        new PositionFactory(0);
    }
}
