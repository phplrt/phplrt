<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Exception\Tests;

use Phplrt\Contracts\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Note: Changing the behavior of these tests is allowed ONLY when updating
 *       a MAJOR version of the package.
 */
class CompatibilityTest extends TestCase
{
    public function testRuntimeExceptionCompatibility(): void
    {
        self::expectNotToPerformAssertions();

        new class () extends \Exception implements RuntimeExceptionInterface {
            public function getToken(): TokenInterface {}
            public function getSource(): ReadableInterface {}
        };
    }
}
