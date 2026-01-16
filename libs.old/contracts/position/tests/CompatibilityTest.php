<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Position\Tests;

use Phplrt\Contracts\Position\PositionFactoryInterface;
use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Note: Changing the behavior of these tests is allowed ONLY when updating
 *       a MAJOR version of the package.
 */
class CompatibilityTest extends TestCase
{
    public function testPositionFactoryCompatibility(): void
    {
        self::expectNotToPerformAssertions();

        new class () implements PositionFactoryInterface {
            public function createAtStarting(): PositionInterface {}
            public function createAtEnding(ReadableInterface $source): PositionInterface {}
            public function createFromOffset(
                ReadableInterface $source,
                int $offset = PositionInterface::MIN_OFFSET
            ): PositionInterface {}
            public function createFromPosition(
                ReadableInterface $source,
                int $line = PositionInterface::MIN_LINE,
                int $column = PositionInterface::MIN_COLUMN
            ): PositionInterface {}
        };
    }

    public function testPositionCompatibility(): void
    {
        self::expectNotToPerformAssertions();

        new class () implements PositionInterface {
            public function getOffset(): int {}
            public function getLine(): int {}
            public function getColumn(): int {}
        };
    }
}
