<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Source\FileSource;
use Phplrt\Source\ResourceSource;
use Phplrt\Source\StringSource;
use Phplrt\Source\VirtualSource;
use Testo\Assert;
use Testo\Test;

#[Test]
final class NamedConstructorsTest extends TestCase
{
    public function testFileSourceFromPathname(): void
    {
        \file_put_contents($this->temp, 'test content');

        $source = FileSource::createFromPathname($this->temp);

        Assert::same($source->pathname, $this->temp);
        Assert::same($source->content, 'test content');
    }

    public function testFileSourceFromSplFileInfo(): void
    {
        \file_put_contents($this->temp, 'test content');

        $source = FileSource::createFromSplFileInfo(new \SplFileInfo($this->temp));

        Assert::same($source->pathname, $this->temp);
        Assert::same($source->content, 'test content');
    }

    public function testStringSourceFromString(): void
    {
        $source = StringSource::createFromString('test content');

        Assert::same($source->content, 'test content');
    }

    public function testEmptyStringSource(): void
    {
        $source = StringSource::createEmpty();

        Assert::same($source->content, '');
    }

    public function testResourceSourceFromResource(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');
        \rewind($stream);

        try {
            $source = ResourceSource::createFromResource($stream);

            Assert::same($source->content, 'test content');
        } finally {
            \fclose($stream);
        }
    }

    public function testResourceSourceFromResourceDoesNotTakeOwnership(): void
    {
        $stream = \fopen('php://memory', 'rb+');

        $source = ResourceSource::createFromResource($stream);
        unset($source);

        Assert::true(\is_resource($stream));

        \fclose($stream);
    }

    public function testVirtualSourceFromString(): void
    {
        $source = VirtualSource::createFromString('virtual.txt', 'test content');

        Assert::same($source->pathname, 'virtual.txt');
        Assert::same($source->content, 'test content');
    }

    public function testEmptyVirtualSource(): void
    {
        $source = VirtualSource::createEmpty('virtual.txt');

        Assert::same($source->pathname, 'virtual.txt');
        Assert::same($source->content, '');
    }

    public function testVirtualSourceFromResourceStream(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');
        \rewind($stream);

        try {
            $source = VirtualSource::createFromResourceStream('virtual.txt', $stream);

            Assert::same($source->pathname, 'virtual.txt');
            Assert::same($source->content, 'test content');
        } finally {
            \fclose($stream);
        }
    }

    public function testVirtualSourceFromPathname(): void
    {
        \file_put_contents($this->temp, 'test content');

        $source = VirtualSource::createFromPathname($this->temp);

        Assert::same($source->pathname, $this->temp);
        Assert::same($source->content, 'test content');
    }
}
