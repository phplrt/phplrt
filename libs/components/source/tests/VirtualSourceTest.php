<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Source\ResourceSource;
use Phplrt\Source\StringSource;
use Phplrt\Source\VirtualSource;

final class VirtualSourceTest extends TestCase
{
    public function testPathnameProperty(): void
    {
        $source = new VirtualSource('virtual/file.php', new StringSource('test content'));

        self::assertSame('virtual/file.php', $source->pathname);
        self::assertInstanceOf(FileInterface::class, $source);
    }

    public function testReadsTheContentOfTheSourceItWraps(): void
    {
        $source = new VirtualSource('virtual/file.php', new StringSource('test content'));

        self::assertSame('test content', $source->content);
        self::assertSame(12, $source->size);
    }

    public function testReadsThroughTheSourceItWraps(): void
    {
        $source = new VirtualSource('virtual/file.php', new StringSource('test content'));

        self::assertSame(0, $source->offset);
        self::assertSame('test content', $source->read(1024));
        self::assertSame(12, $source->offset);
        self::assertTrue($source->isEof);
    }

    public function testWrapsAFileOfItsOwn(): void
    {
        \file_put_contents($this->temp, 'test content');

        $source = new VirtualSource('virtual/file.php', new ResourceSource(
            \fopen($this->temp, 'rb'),
            autoclose: true,
        ));

        // The pathname is virtual, while everything read comes from the file
        self::assertSame('virtual/file.php', $source->pathname);
        self::assertSame('test content', $source->content);
        self::assertSame(12, $source->size);
    }

    public function testSizeIsUnknownWhenTheSourceCannotTellIt(): void
    {
        $stream = $this->createNonSeekableResource('test content');

        try {
            $source = new VirtualSource('virtual/file.php', new ResourceSource($stream));

            self::assertNull($source->size);
        } finally {
            \fclose($stream);
        }
    }

    public function testWrapsAnotherVirtualFile(): void
    {
        $source = new VirtualSource('outer.php', new VirtualSource(
            'inner.php',
            new StringSource('test content'),
        ));

        // The outermost name is the one an error points at
        self::assertSame('outer.php', $source->pathname);
        self::assertSame('test content', $source->content);
    }

    public function testEmptyContent(): void
    {
        $source = new VirtualSource('virtual/file.php', new StringSource());

        self::assertSame('', $source->content);
        self::assertSame(0, $source->size);
        self::assertTrue($source->isEof);
    }
}
