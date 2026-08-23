<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Source\Tests;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Source\ReadableStreamInterface;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;

/**
 * Note: Changing the behavior of these tests is allowed ONLY when updating
 *       a MAJOR version of the package.
 */
class CompatibilityTest extends TestCase
{
    #[DoesNotPerformAssertions]
    public function testFileCompatibility(): void
    {
        new class () implements FileInterface {
            public string $pathname;

            public string $content;
            public int $offset;
            public bool $isSeekable;
            public bool $isEof;

            public function read(int $bytes): string
            {
                throw new \LogicException('Declared to be compiled rather than called');
            }

            public function __toString(): string
            {
                throw new \LogicException('Declared to be compiled rather than called');
            }
        };
    }

    #[DoesNotPerformAssertions]
    public function testReadableCompatibility(): void
    {
        new class () implements ReadableInterface {
            public string $content;
            public int $offset;
            public bool $isSeekable;
            public bool $isEof;

            public function read(int $bytes): string
            {
                throw new \LogicException('Declared to be compiled rather than called');
            }

            public function __toString(): string
            {
                throw new \LogicException('Declared to be compiled rather than called');
            }
        };
    }

    #[DoesNotPerformAssertions]
    public function testReadableStreamCompatibility(): void
    {
        new class () implements ReadableStreamInterface {
            public int $offset;
            public bool $isSeekable;
            public bool $isEof;

            public function read(int $bytes): string
            {
                throw new \LogicException('Declared to be compiled rather than called');
            }
        };
    }

    #[DoesNotPerformAssertions]
    public function testSourceExceptionCompatibility(): void
    {
        new class () extends \Exception implements SourceExceptionInterface {};
    }
}
