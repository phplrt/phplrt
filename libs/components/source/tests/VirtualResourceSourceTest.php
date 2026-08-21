<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Source\ResourceSource;
use Phplrt\Source\VirtualResourceSource;

final class VirtualResourceSourceTest extends TestCase
{
    public function testConstructor(): void
    {
        $pathname = 'virtual/file.php';
        $content = 'test content';
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, $content);
        \rewind($stream);

        $virtualFileStream = new VirtualResourceSource($pathname, $stream);

        self::assertSame($pathname, $virtualFileStream->pathname);
        self::assertSame($stream, $virtualFileStream->stream);
    }

    public function testPathnameProperty(): void
    {
        $pathname = 'virtual/file.php';
        $stream = \fopen('php://memory', 'rb+');

        $virtualFileStream = new VirtualResourceSource($pathname, $stream);

        self::assertSame($pathname, $virtualFileStream->pathname);
    }

    public function testContentProperty(): void
    {
        $pathname = 'virtual/file.php';
        $content = 'test content';
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, $content);
        \rewind($stream);

        $virtualFileStream = new VirtualResourceSource($pathname, $stream);

        self::assertSame($content, $virtualFileStream->content);
    }

    public function testInheritsFromStream(): void
    {
        $pathname = 'virtual/file.php';
        $stream = \fopen('php://memory', 'rb+');

        $virtualFileStream = new VirtualResourceSource($pathname, $stream);

        self::assertInstanceOf(ResourceSource::class, $virtualFileStream);
    }

    public function testUriProperty(): void
    {
        $pathname = 'virtual/file.php';
        $stream = \fopen('php://memory', 'rb+');

        $virtualFileStream = new VirtualResourceSource($pathname, $stream);

        self::assertSame('php://memory', $virtualFileStream->uri);
    }

    public function testModeProperty(): void
    {
        $pathname = 'virtual/file.php';
        $stream = \fopen('php://memory', 'w+b');

        $virtualFileStream = new VirtualResourceSource($pathname, $stream);

        self::assertSame('w+b', $virtualFileStream->mode);
    }

    public function testCreateStream(): void
    {
        $pathname = 'virtual/file.php';
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');
        \fseek($stream, 5);

        $virtualFileStream = new VirtualResourceSource($pathname, $stream);

        $cursor = $virtualFileStream->createStream();

        self::assertSame(0, $cursor->offset);
        self::assertSame('test content', $cursor->read(1024));
    }

    public function testAutocloseIsPassedThrough(): void
    {
        $stream = \fopen('php://memory', 'rb+');

        $virtualFileStream = new VirtualResourceSource('virtual/file.php', $stream, autoclose: true);
        unset($virtualFileStream);

        self::assertIsClosedResource($stream);
    }

    public function testIsLocalProperty(): void
    {
        $pathname = 'virtual/file.php';
        $stream = \fopen('php://memory', 'rb+');

        $virtualFileStream = new VirtualResourceSource($pathname, $stream);

        self::assertTrue($virtualFileStream->isLocal);
    }
}
