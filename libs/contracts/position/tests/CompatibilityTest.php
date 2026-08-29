<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Position\Tests;

use Phplrt\Contracts\Position\PositionInterface;
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
    public function testPositionCompatibility(): void
    {
        new class implements PositionInterface {
            public int $line;
            public int $column;
        };
    }
}
