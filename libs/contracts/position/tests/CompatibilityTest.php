<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Position\Tests;

use Phplrt\Contracts\Position\PositionInterface;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;

/**
 * Note: Changing the behavior of these tests is allowed ONLY when updating
 *       a MAJOR version of the package.
 */
class CompatibilityTest extends TestCase
{
    #[DoesNotPerformAssertions]
    public function testPositionCompatibility(): void
    {
        new class () implements PositionInterface {
            public int $line;
            public int $column;
        };
    }
}
