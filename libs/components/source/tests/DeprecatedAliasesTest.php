<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Source\File;
use Phplrt\Source\FileSource;
use Phplrt\Source\ResourceSource;
use Phplrt\Source\Source;
use Phplrt\Source\SourceFactory;
use Phplrt\Source\Stream;
use Phplrt\Source\StringSource;
use Phplrt\Source\VirtualFile;
use Phplrt\Source\VirtualSource;
use Phplrt\Source\VirtualStreamingFile;
use PHPUnit\Framework\Attributes\Group;

/**
 * The names the sources were known under before they have been renamed.
 *
 * Note: Removing any of these is allowed ONLY when updating a MAJOR version
 *       of the package.
 */
#[Group('deprecated')]
final class DeprecatedAliasesTest extends TestCase
{
    public function testFileIsAFileSource(): void
    {
        $source = new File($this->temp);

        self::assertSame(FileSource::class, $source::class);
        self::assertSame($this->temp, $source->pathname);
    }

    public function testSourceIsAStringSource(): void
    {
        $source = new Source('2 + 2');

        self::assertSame(StringSource::class, $source::class);
        self::assertSame('2 + 2', $source->content);
    }

    public function testStreamIsAResourceSource(): void
    {
        $source = new Stream(\fopen('php://memory', 'rb+'), autoclose: true);

        self::assertSame(ResourceSource::class, $source::class);
        self::assertSame('', $source->content);
    }

    public function testVirtualFileIsAVirtualSource(): void
    {
        $source = new VirtualFile('virtual.txt', StringSource::createFromString('2 + 2'));

        self::assertSame(VirtualSource::class, $source::class);
        self::assertSame('virtual.txt', $source->pathname);
        self::assertSame('2 + 2', $source->content);
    }

    public function testVirtualStreamingFileIsAVirtualSource(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, '2 + 2');
        \rewind($stream);

        $source = new VirtualStreamingFile('virtual.txt', new ResourceSource($stream, autoclose: true));

        self::assertSame(VirtualSource::class, $source::class);
        self::assertSame('virtual.txt', $source->pathname);
        self::assertSame('2 + 2', $source->content);
    }

    public function testASourceBuiltUnderTheNewNameMatchesTheOldOne(): void
    {
        // An alias is the very same class rather than a child of it, so code
        // that has not been updated yet keeps recognizing what it is given.
        $source = SourceFactory::createDefault()
            ->create('2 + 2');

        self::assertInstanceOf(Source::class, $source);
    }
}
