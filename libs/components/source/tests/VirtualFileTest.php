<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Source\VirtualFile;

final class VirtualFileTest extends TestCase
{
    public function testConstructor(): void
    {
        $pathname = 'virtual/file.php';
        $content = 'test content';
        $virtualFile = new VirtualFile($pathname, $content);

        self::assertSame($pathname, $virtualFile->pathname);
        self::assertSame($content, $virtualFile->content);
    }

    public function testPathnameProperty(): void
    {
        $pathname = 'virtual/file.php';
        $content = 'test content';
        $virtualFile = new VirtualFile($pathname, $content);

        self::assertSame($pathname, $virtualFile->pathname);
    }

    public function testContentProperty(): void
    {
        $pathname = 'virtual/file.php';
        $content = 'test content';
        $virtualFile = new VirtualFile($pathname, $content);

        self::assertSame($content, $virtualFile->content);
    }

    public function testStreamProperty(): void
    {
        $pathname = 'virtual/file.php';
        $content = 'test content';
        $virtualFile = new VirtualFile($pathname, $content);

        $stream = $virtualFile->stream;

        self::assertIsResource($stream);
        self::assertSame($content, \stream_get_contents($stream));
    }

    public function testInheritsFromSource(): void
    {
        $pathname = 'virtual/file.php';
        $content = 'test content';
        $virtualFile = new VirtualFile($pathname, $content);

        self::assertInstanceOf(\Phplrt\Source\Source::class, $virtualFile);
    }

    public function testEmptyContent(): void
    {
        $pathname = 'virtual/file.php';
        $content = '';
        $virtualFile = new VirtualFile($pathname, $content);

        self::assertSame('', $virtualFile->content);
        self::assertIsResource($virtualFile->stream);
    }
}
