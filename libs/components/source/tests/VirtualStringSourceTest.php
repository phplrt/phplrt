<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Source\StringSource;
use Phplrt\Source\VirtualStringSource;

final class VirtualStringSourceTest extends TestCase
{
    public function testConstructor(): void
    {
        $pathname = 'virtual/file.php';
        $content = 'test content';
        $virtualFile = new VirtualStringSource($pathname, $content);

        self::assertSame($pathname, $virtualFile->pathname);
        self::assertSame($content, $virtualFile->content);
    }

    public function testPathnameProperty(): void
    {
        $pathname = 'virtual/file.php';
        $content = 'test content';
        $virtualFile = new VirtualStringSource($pathname, $content);

        self::assertSame($pathname, $virtualFile->pathname);
    }

    public function testContentProperty(): void
    {
        $pathname = 'virtual/file.php';
        $content = 'test content';
        $virtualFile = new VirtualStringSource($pathname, $content);

        self::assertSame($content, $virtualFile->content);
    }

    public function testSizeProperty(): void
    {
        $virtualFile = new VirtualStringSource('virtual/file.php', 'test content');

        self::assertSame(12, $virtualFile->size);
    }

    public function testCreateStream(): void
    {
        $pathname = 'virtual/file.php';
        $content = 'test content';
        $virtualFile = new VirtualStringSource($pathname, $content);

        $stream = $virtualFile->createStream();

        self::assertSame(0, $stream->offset);
        self::assertSame($content, $stream->read(1024));
    }

    public function testInheritsFromSource(): void
    {
        $pathname = 'virtual/file.php';
        $content = 'test content';
        $virtualFile = new VirtualStringSource($pathname, $content);

        self::assertInstanceOf(StringSource::class, $virtualFile);
    }

    public function testEmptyContent(): void
    {
        $pathname = 'virtual/file.php';
        $virtualFile = new VirtualStringSource($pathname, '');

        self::assertSame('', $virtualFile->content);
        self::assertTrue($virtualFile->createStream()->isEof);
    }
}
