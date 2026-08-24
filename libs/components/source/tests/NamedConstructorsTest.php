<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Source\FileSource;
use Phplrt\Source\ResourceSource;
use Phplrt\Source\StringSource;
use Phplrt\Source\VirtualSource;

final class NamedConstructorsTest extends TestCase
{
    public function testFileSourceFromPathname(): void
    {
        \file_put_contents($this->temp, 'test content');

        $source = FileSource::createFromPathname($this->temp);

        self::assertSame($this->temp, $source->pathname);
        self::assertSame('test content', $source->content);
    }

    public function testFileSourceFromSplFileInfo(): void
    {
        \file_put_contents($this->temp, 'test content');

        $source = FileSource::createFromSplFileInfo(new \SplFileInfo($this->temp));

        self::assertSame($this->temp, $source->pathname);
        self::assertSame('test content', $source->content);
    }

    public function testStringSourceFromString(): void
    {
        $source = StringSource::createFromString('test content');

        self::assertSame('test content', $source->content);
    }

    public function testEmptyStringSource(): void
    {
        $source = StringSource::createEmpty();

        self::assertSame('', $source->content);
    }

    public function testResourceSourceFromResource(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');
        \rewind($stream);

        try {
            $source = ResourceSource::createFromResource($stream);

            self::assertSame('test content', $source->content);
        } finally {
            \fclose($stream);
        }
    }

    public function testResourceSourceFromResourceDoesNotTakeOwnership(): void
    {
        $stream = \fopen('php://memory', 'rb+');

        $source = ResourceSource::createFromResource($stream);
        unset($source);

        self::assertIsResource($stream);

        \fclose($stream);
    }

    public function testVirtualSourceFromString(): void
    {
        $source = VirtualSource::createFromString('virtual.txt', 'test content');

        self::assertSame('virtual.txt', $source->pathname);
        self::assertSame('test content', $source->content);
    }

    public function testEmptyVirtualSource(): void
    {
        $source = VirtualSource::createEmpty('virtual.txt');

        self::assertSame('virtual.txt', $source->pathname);
        self::assertSame('', $source->content);
    }

    public function testVirtualSourceFromResourceStream(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');
        \rewind($stream);

        try {
            $source = VirtualSource::createFromResourceStream('virtual.txt', $stream);

            self::assertSame('virtual.txt', $source->pathname);
            self::assertSame('test content', $source->content);
        } finally {
            \fclose($stream);
        }
    }

    public function testVirtualSourceFromPathname(): void
    {
        \file_put_contents($this->temp, 'test content');

        $source = VirtualSource::createFromPathname($this->temp);

        self::assertSame($this->temp, $source->pathname);
        self::assertSame('test content', $source->content);
    }
}
