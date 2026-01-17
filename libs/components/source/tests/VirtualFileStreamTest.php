<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Source\VirtualFileStream;

final class VirtualFileStreamTest extends TestCase
{
    public function testConstructor(): void
    {
        $pathname = 'virtual/file.php';
        $content = 'test content';
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, $content);
        \rewind($stream);

        $virtualFileStream = new VirtualFileStream($pathname, $stream, $this->hasher);

        self::assertSame($pathname, $virtualFileStream->pathname);
        self::assertSame($stream, $virtualFileStream->stream);
    }

    public function testPathnameProperty(): void
    {
        $pathname = 'virtual/file.php';
        $stream = \fopen('php://memory', 'rb+');

        $virtualFileStream = new VirtualFileStream($pathname, $stream, $this->hasher);

        self::assertSame($pathname, $virtualFileStream->pathname);
    }

    public function testContentProperty(): void
    {
        $pathname = 'virtual/file.php';
        $content = 'test content';
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, $content);
        \rewind($stream);

        $virtualFileStream = new VirtualFileStream($pathname, $stream, $this->hasher);

        self::assertSame($content, $virtualFileStream->content);
    }

    public function testHashProperty(): void
    {
        $pathname = 'virtual/file.php';
        $content = 'test content';
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, $content);
        \rewind($stream);

        $virtualFileStream = new VirtualFileStream($pathname, $stream, $this->hasher);

        $hash = $virtualFileStream->hash;

        self::assertNotEmpty($hash);
        self::assertIsString($hash);
    }

    public function testHashPropertyCaching(): void
    {
        $pathname = 'virtual/file.php';
        $content = 'test content';
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, $content);
        \rewind($stream);

        $virtualFileStream = new VirtualFileStream($pathname, $stream, $this->hasher);

        $hash1 = $virtualFileStream->hash;
        $hash2 = $virtualFileStream->hash;

        self::assertSame($hash1, $hash2);
        self::assertNotEmpty($hash1);
    }

    public function testInheritsFromStream(): void
    {
        $pathname = 'virtual/file.php';
        $stream = \fopen('php://memory', 'rb+');

        $virtualFileStream = new VirtualFileStream($pathname, $stream, $this->hasher);

        self::assertInstanceOf(\Phplrt\Source\Stream::class, $virtualFileStream);
    }

    public function testUriProperty(): void
    {
        $pathname = 'virtual/file.php';
        $stream = \fopen('php://memory', 'rb+');

        $virtualFileStream = new VirtualFileStream($pathname, $stream, $this->hasher);

        self::assertSame('php://memory', $virtualFileStream->uri);
    }

    public function testModeProperty(): void
    {
        $pathname = 'virtual/file.php';
        $stream = \fopen('php://memory', 'w+b');

        $virtualFileStream = new VirtualFileStream($pathname, $stream, $this->hasher);

        self::assertSame('w+b', $virtualFileStream->mode);
    }

    public function testOffsetProperty(): void
    {
        $pathname = 'virtual/file.php';
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');
        \fseek($stream, 5);

        $virtualFileStream = new VirtualFileStream($pathname, $stream, $this->hasher);

        self::assertSame(5, $virtualFileStream->offset);
    }

    public function testIsLocalProperty(): void
    {
        $pathname = 'virtual/file.php';
        $stream = \fopen('php://memory', 'rb+');

        $virtualFileStream = new VirtualFileStream($pathname, $stream, $this->hasher);

        self::assertTrue($virtualFileStream->isLocal);
    }
}

