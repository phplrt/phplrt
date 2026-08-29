<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Parser\Tests;

use Phplrt\Contracts\Parser\ParserInterface;
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
    public function testParserCompatibility(): void
    {
        new class implements ParserInterface {
            public function parse(mixed $source): iterable
            {
                return [];
            }
        };
    }
}
