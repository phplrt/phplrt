<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Source\Tests;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\Factory\SourceFactoryInterface;
use Phplrt\Contracts\Source\Factory\SourceFactoryInterface;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
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
            public string $hash;
        };
    }

    #[DoesNotPerformAssertions]
    public function testReadableCompatibility(): void
    {
        new class () implements ReadableInterface {
            public mixed $stream;
            public string $content;
            public string $hash;
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
            public function create(mixed $source): ReadableInterface {}
            public function tryCreate(mixed $source): ?ReadableInterface {}

            public function createFromFile(string $pathname): FileInterface {}
            public function tryCreateFromFile(string $pathname): ?FileInterface {}
            public function createFromStream(mixed $stream, ?string $virtualPathname = null): ReadableInterface {}
            public function tryCreateFromStream(mixed $stream, ?string $virtualPathname = null): ?ReadableInterface {}
            public function createFromString(string $content = '', ?string $virtualPathname = null): ReadableInterface {}
            public function tryCreateFromString(string $content = '', ?string $virtualPathname = null): ?ReadableInterface {}
        };
    }

    #[DoesNotPerformAssertions]
    public function testSourceCreatorCompatibility(): void
    {
        new class () implements SourceFactoryInterface {
            public function create(mixed $source): ReadableInterface {}
            public function tryCreate(mixed $source): ?ReadableInterface {}
        };
    }
}
