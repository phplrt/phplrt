<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Source\Tests;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\FileInterface;
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
    public function testFileCompatibility(): void
    {
        new class implements FileInterface {
            public string $pathname;

            public string $content;

            public function read(int $offset, int $bytes): string
            {
                throw new \LogicException('Declared to be compiled rather than called');
            }

            public function __toString(): string
            {
                throw new \LogicException('Declared to be compiled rather than called');
            }
        };
    }

    #[ExpectNoAssertions]
    public function testReadableCompatibility(): void
    {
        new class implements ReadableInterface {
            public string $content;

            public function read(int $offset, int $bytes): string
            {
                throw new \LogicException('Declared to be compiled rather than called');
            }

            public function __toString(): string
            {
                throw new \LogicException('Declared to be compiled rather than called');
            }
        };
    }

    #[ExpectNoAssertions]
    public function testSourceExceptionCompatibility(): void
    {
        new class extends \Exception implements SourceExceptionInterface {};
    }
}
