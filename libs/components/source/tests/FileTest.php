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
        $file = new File($this->temp);

        self::assertSame($this->temp, $file->pathname);
    }

    public function testIsExistsPropertyWhenFileExists(): void
    {
        \file_put_contents($this->temp, 'test content');

        $file = new File($this->temp);

        self::assertTrue($file->isExists);
    }

    public function testIsExistsPropertyWhenFileNotExists(): void
    {
        $file = new File($this->temp);

        self::assertFalse($file->isExists);
    }

    public function testIsReadablePropertyWhenFileIsReadable(): void
    {
        \file_put_contents($this->temp, 'test content');

        $file = new File($this->temp);

        self::assertTrue($file->isReadable);
    }

    public function testIsReadablePropertyWhenFileNotExists(): void
    {
        $file = new File($this->temp);

        self::assertFalse($file->isReadable);
    }

    public function testModifiedAtProperty(): void
    {
        \file_put_contents($this->temp, 'test content');
        $expectedTime = \filemtime($this->temp);

        $file = new File($this->temp);

        self::assertSame($expectedTime, $file->modifiedAt);
    }

    public function testContentProperty(): void
    {
        $content = 'test content';
        \file_put_contents($this->temp, $content);

        $file = new File($this->temp);

        self::assertSame($content, $file->content);
    }

    public function testContentPropertyIsReadOnce(): void
    {
        \file_put_contents($this->temp, 'first content');
        $modifiedAt = \filemtime($this->temp);

        $file = new File($this->temp);

        self::assertSame('first content', $file->content);

        // The file is changed the way neither its modification time nor its
        // size is
        \file_put_contents($this->temp, 'other content');
        \touch($this->temp, $modifiedAt);

        self::assertSame('first content', $file->content);
    }

    public function testContentPropertyIsReadAgainAfterModification(): void
    {
        \file_put_contents($this->temp, 'first content');

        $file = new File($this->temp);

        self::assertSame('first content', $file->content);

        \file_put_contents($this->temp, 'second content');
        \touch($this->temp, \filemtime($this->temp) + 1);

        self::assertSame('second content', $file->content);
    }

    public function testContentPropertyIsReadAgainAfterResize(): void
    {
        \file_put_contents($this->temp, 'first content');
        $modifiedAt = \filemtime($this->temp);

        $file = new File($this->temp);

        self::assertSame('first content', $file->content);

        // The file is rewritten within the very same second, so only its size
        // tells that it has been changed
        \file_put_contents($this->temp, 'first content and a bit more');
        \touch($this->temp, $modifiedAt);

        self::assertSame('first content and a bit more', $file->content);
    }

    public function testContentPropertyThrowsWhenFileNotFound(): void
    {
        $file = new File($this->temp);

        $this->expectException(NotFoundException::class);

        $file->content;
    }

    public function testStreamProperty(): void
    {
        $content = 'test content';
        \file_put_contents($this->temp, $content);

        $file = new File($this->temp);

        $stream = $file->stream;

        self::assertIsResource($stream);
        self::assertSame($content, \stream_get_contents($stream));
    }

    public function testStreamPropertyThrowsWhenFileNotReadable(): void
    {
        $file = new File($this->temp);

        $this->expectException(NotReadableException::class);

        $file->stream;
    }

}
