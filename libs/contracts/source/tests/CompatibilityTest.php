<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Source\Tests;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\FileInterface;
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
    public function testFileCompatibility(): void
    {
        new class () implements FileInterface {
            public string $pathname;

            public mixed $stream;
            public string $content;
        };
    }

    #[DoesNotPerformAssertions]
    public function testReadableCompatibility(): void
    {
        new class () implements ReadableInterface {
            public mixed $stream;
            public string $content;
        };
    }

    #[DoesNotPerformAssertions]
    public function testSourceExceptionCompatibility(): void
    {
        new class () extends \Exception implements SourceExceptionInterface {};
    }

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
