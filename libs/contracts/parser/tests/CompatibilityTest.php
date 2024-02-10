<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Parser\Tests;

use Phplrt\Contracts\Parser\ParserInterface;

/**
 * Note: Changing the behavior of these tests is allowed ONLY when updating
 *       a MAJOR version of the package.
 */
class CompatibilityTest extends TestCase
{
    public function testParserCompatibility(): void
    {
        self::expectNotToPerformAssertions();

        new class () implements ParserInterface {
            public function parse($source): iterable {}
        };
    }

    /**
     * @requires PHP 8.0
     */
    public function testParserWithMixedCompatibility(): void
    {
        self::expectNotToPerformAssertions();

        new class () implements ParserInterface {
            public function parse(mixed $source): iterable {}
        };
    }
}
