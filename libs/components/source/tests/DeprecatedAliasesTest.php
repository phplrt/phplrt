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
use Testo\Assert;
use Testo\Filter\Group;
use Testo\Test;

#[Group('deprecated')]
#[Test]
final class DeprecatedAliasesTest extends TestCase
{
    public function testFileIsAFileSource(): void
    {
        $source = new File($this->temp);

        Assert::same($source::class, FileSource::class);
        Assert::same($source->pathname, $this->temp);
    }

    public function testSourceIsAStringSource(): void
    {
        $source = new Source('2 + 2');

        Assert::same($source::class, StringSource::class);
        Assert::same($source->content, '2 + 2');
    }

    public function testStreamIsAResourceSource(): void
    {
        $source = new Stream(\fopen('php://memory', 'rb+'), autoclose: true);

        Assert::same($source::class, ResourceSource::class);
        Assert::same($source->content, '');
    }

    public function testVirtualFileIsAVirtualSource(): void
    {
        $source = new VirtualFile('virtual.txt', StringSource::createFromString('2 + 2'));

        Assert::same($source::class, VirtualSource::class);
        Assert::same($source->pathname, 'virtual.txt');
        Assert::same($source->content, '2 + 2');
    }

    public function testVirtualStreamingFileIsAVirtualSource(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, '2 + 2');
        \rewind($stream);

        $source = new VirtualStreamingFile('virtual.txt', new ResourceSource($stream, autoclose: true));

        Assert::same($source::class, VirtualSource::class);
        Assert::same($source->pathname, 'virtual.txt');
        Assert::same($source->content, '2 + 2');
    }

    public function testASourceBuiltUnderTheNewNameMatchesTheOldOne(): void
    {
        $source = SourceFactory::createDefault()
            ->create('2 + 2');

        Assert::instanceOf($source, Source::class);
    }
}
