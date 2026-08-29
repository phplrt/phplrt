<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Source\Tests\Factory;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Source\SourceFactoryInterface;
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
    public function testSourceFactoryCompatibility(): void
    {
        new class implements SourceFactoryInterface {
            public function create(mixed $source): ReadableInterface
            {
                throw new \LogicException('Declared to be compiled rather than called');
            }
        };
    }
}
