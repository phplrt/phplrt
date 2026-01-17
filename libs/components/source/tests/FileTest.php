<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Source\Exception\NotFoundException;
use Phplrt\Source\Exception\NotReadableException;
use Phplrt\Source\File;

final class FileTest extends TestCase
{
    public function testConstructor(): void
    {
        $file = new File($this->temp, $this->hasher);

        self::assertSame($this->temp, $file->pathname);
    }

    public function testIsExistsPropertyWhenFileExists(): void
    {
        \file_put_contents($this->temp, 'test content');

        $file = new File($this->temp, $this->hasher);

        self::assertTrue($file->isExists);
    }

    public function testIsExistsPropertyWhenFileNotExists(): void
    {
        $file = new File($this->temp, $this->hasher);

        self::assertFalse($file->isExists);
    }

    public function testIsReadablePropertyWhenFileIsReadable(): void
    {
        \file_put_contents($this->temp, 'test content');

        $file = new File($this->temp, $this->hasher);

        self::assertTrue($file->isReadable);
    }

    public function testIsReadablePropertyWhenFileNotExists(): void
    {
        $file = new File($this->temp, $this->hasher);

        self::assertFalse($file->isReadable);
    }

    public function testModifiedAtProperty(): void
    {
        \file_put_contents($this->temp, 'test content');
        $expectedTime = \filemtime($this->temp);

        $file = new File($this->temp, $this->hasher);

        self::assertSame($expectedTime, $file->modifiedAt);
    }

    public function testContentProperty(): void
    {
        $content = 'test content';
        \file_put_contents($this->temp, $content);

        $file = new File($this->temp, $this->hasher);

        self::assertSame($content, $file->content);
    }

    public function testContentPropertyThrowsWhenFileNotFound(): void
    {
        $file = new File($this->temp, $this->hasher);

        $this->expectException(NotFoundException::class);

        $file->content;
    }

    public function testStreamProperty(): void
    {
        $content = 'test content';
        \file_put_contents($this->temp, $content);

        $file = new File($this->temp, $this->hasher);

        $stream = $file->stream;

        self::assertIsResource($stream);
        self::assertSame($content, \stream_get_contents($stream));
    }

    public function testStreamPropertyThrowsWhenFileNotReadable(): void
    {
        $file = new File($this->temp, $this->hasher);

        $this->expectException(NotReadableException::class);

        $file->stream;
    }

    public function testHashProperty(): void
    {
        $content = 'test content';
        \file_put_contents($this->temp, $content);

        $file = new File($this->temp, $this->hasher);

        $hash = $file->hash;

        self::assertNotEmpty($hash);
        self::assertIsString($hash);
    }

}

