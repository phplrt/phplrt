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
        $virtualFile = new VirtualFile($pathname, $content, $this->hasher);

        self::assertSame($pathname, $virtualFile->pathname);
        self::assertSame($content, $virtualFile->content);
    }

    public function testPathnameProperty(): void
    {
        $pathname = 'virtual/file.php';
        $content = 'test content';
        $virtualFile = new VirtualFile($pathname, $content, $this->hasher);

        self::assertSame($pathname, $virtualFile->pathname);
    }

    public function testContentProperty(): void
    {
        $pathname = 'virtual/file.php';
        $content = 'test content';
        $virtualFile = new VirtualFile($pathname, $content, $this->hasher);

        self::assertSame($content, $virtualFile->content);
    }

    public function testStreamProperty(): void
    {
        $pathname = 'virtual/file.php';
        $content = 'test content';
        $virtualFile = new VirtualFile($pathname, $content, $this->hasher);

        $stream = $virtualFile->stream;

        self::assertIsResource($stream);
        self::assertSame($content, \stream_get_contents($stream));
    }

    public function testHashProperty(): void
    {
        $pathname = 'virtual/file.php';
        $content = 'test content';
        $virtualFile = new VirtualFile($pathname, $content, $this->hasher);

        $hash = $virtualFile->hash;

        self::assertNotEmpty($hash);
        self::assertIsString($hash);
    }

    public function testHashPropertyCaching(): void
    {
        $pathname = 'virtual/file.php';
        $content = 'test content';
        $virtualFile = new VirtualFile($pathname, $content, $this->hasher);

        $hash1 = $virtualFile->hash;
        $hash2 = $virtualFile->hash;

        self::assertSame($hash1, $hash2);
        self::assertNotEmpty($hash1);
    }

    public function testInheritsFromSource(): void
    {
        $pathname = 'virtual/file.php';
        $content = 'test content';
        $virtualFile = new VirtualFile($pathname, $content, $this->hasher);

        self::assertInstanceOf(\Phplrt\Source\Source::class, $virtualFile);
    }

    public function testEmptyContent(): void
    {
        $pathname = 'virtual/file.php';
        $content = '';
        $virtualFile = new VirtualFile($pathname, $content, $this->hasher);

        self::assertSame('', $virtualFile->content);
        self::assertIsResource($virtualFile->stream);
    }
}

