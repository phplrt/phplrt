<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Source\Tests;

use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Source\SourceExceptionInterface;
use Phplrt\Contracts\Source\SourceFactoryInterface;

/**
 * Note: Changing the behavior of these tests is allowed ONLY when updating
 *       a MAJOR version of the package.
 */
class CompatibilityTest extends TestCase
{
    public function testFileCompatibility(): void
    {
        self::expectNotToPerformAssertions();

        new class () implements FileInterface {
            public function getPathname(): string {}

            public function getStream() {}
            public function getContents(): string {}
            public function getHash(): string {}
        };
    }

    /**
     * @requires PHP 8.0
     */
    public function testFileWithMixedCompatibility(): void
    {
        self::expectNotToPerformAssertions();

        new class () implements FileInterface {
            public function getPathname(): string {}

            public function getStream(): mixed {}
            public function getContents(): string {}
            public function getHash(): string {}
        };
    }

    public function testReadableCompatibility(): void
    {
        self::expectNotToPerformAssertions();

        new class () implements ReadableInterface {
            public function getStream() {}
            public function getContents(): string {}
            public function getHash(): string {}
        };
    }

    /**
     * @requires PHP 8.0
     */
    public function testReadableWithMixedCompatibility(): void
    {
        self::expectNotToPerformAssertions();

        new class () implements ReadableInterface {
            public function getStream(): mixed {}
            public function getContents(): string {}
            public function getHash(): string {}
        };
    }

    public function testSourceExceptionCompatibility(): void
    {
        self::expectNotToPerformAssertions();

        new class () extends \Exception implements SourceExceptionInterface {};
    }

    public function testSourceFactoryCompatibility(): void
    {
        self::expectNotToPerformAssertions();

        new class () implements SourceFactoryInterface {
            public function create($source): ReadableInterface {}
            public function createFromString(string $content = '', string $name = null): ReadableInterface {}
            public function createFromFile(string $filename): FileInterface {}
            public function createFromStream($stream, string $name = null): ReadableInterface {}
        };
    }

    /**
     * @requires PHP 8.0
     */
    public function testSourceFactoryWithMixedCompatibility(): void
    {
        self::expectNotToPerformAssertions();

        new class () implements SourceFactoryInterface {
            public function create(mixed $source): ReadableInterface {}
            public function createFromString(string $content = '', string $name = null): ReadableInterface {}
            public function createFromFile(string $filename): FileInterface {}
            public function createFromStream(mixed $stream, string $name = null): ReadableInterface {}
        };
    }
}
