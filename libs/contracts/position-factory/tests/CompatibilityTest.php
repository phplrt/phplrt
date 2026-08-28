<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Position\Tests\Factory;

use Phplrt\Contracts\Position\PositionFactoryInterface;
use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Testo\Assert\ExpectNoAssertions;
use Testo\Test;

/**
 * Note: Changing the behavior of these tests is allowed ONLY when updating
 *       a MAJOR version of the package.
 */
#[Test]
class CompatibilityTest extends TestCase
{
    #[ExpectNoAssertions]
    public function testPositionFactoryCompatibility(): void
    {
        new class implements PositionFactoryInterface {
            public function createFromOffset(ReadableInterface $source, int $offset = 0): PositionInterface
            {
                throw new \LogicException('Declared to be compiled rather than called');
            }

            public function createOffsetFromPosition(ReadableInterface $source, PositionInterface $position): int
            {
                throw new \LogicException('Declared to be compiled rather than called');
            }
        };
    }
}
