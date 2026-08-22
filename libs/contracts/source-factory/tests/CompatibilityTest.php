<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Source\Tests\Factory;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Source\SourceFactoryInterface;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;

/**
 * Note: Changing the behavior of these tests is allowed ONLY when updating
 *       a MAJOR version of the package.
 */
class CompatibilityTest extends TestCase
{
    #[DoesNotPerformAssertions]
    public function testSourceFactoryCompatibility(): void
    {
        new class () implements SourceFactoryInterface {
            public function create(mixed $source): ReadableInterface
            {
                throw new \LogicException('Declared to be compiled rather than called');
            }
        };
    }
}
