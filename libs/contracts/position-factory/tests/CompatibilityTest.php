<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Position\Tests\Factory;

use Phplrt\Contracts\Position\PositionFactoryInterface;
use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;

/**
 * Note: Changing the behavior of these tests is allowed ONLY when updating
 *       a MAJOR version of the package.
 */
class CompatibilityTest extends TestCase
{
    #[DoesNotPerformAssertions]
    public function testPositionFactoryCompatibility(): void
    {
        new class () implements PositionFactoryInterface {
            public function createAtStarting(): PositionInterface
            {
                throw new \LogicException('Declared to be compiled rather than called');
            }

            public function createAtEnding(ReadableInterface $source): PositionInterface
            {
                throw new \LogicException('Declared to be compiled rather than called');
            }

            public function createFromOffset(ReadableInterface $source, int $offset = 0): PositionInterface
            {
                throw new \LogicException('Declared to be compiled rather than called');
            }

            public function createFromPosition(
                ReadableInterface $source,
                int $line = PositionInterface::MIN_LINE,
                int $column = PositionInterface::MIN_COLUMN,
            ): PositionInterface {
                throw new \LogicException('Declared to be compiled rather than called');
            }
        };
    }
}
