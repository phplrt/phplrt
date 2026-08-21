<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Source\Exception\NotFoundException;
use Phplrt\Source\Exception\NotReadableException;
use Phplrt\Source\FileSource;
use Phplrt\Source\Stream\SeekableResourceStream;

final class FileSourceTest extends TestCase
{
    public function testConstructor(): void
    {
        $file = new FileSource($this->temp);

        self::assertSame($this->temp, $file->pathname);
    }

    public function testIsExistsPropertyWhenFileExists(): void
    {
        \file_put_contents($this->temp, 'test content');

        $file = new FileSource($this->temp);

        self::assertTrue($file->isExists);
    }

    public function testIsExistsPropertyWhenFileNotExists(): void
    {
        $file = new FileSource($this->temp);

        self::assertFalse($file->isExists);
    }

    public function testIsReadablePropertyWhenFileIsReadable(): void
    {
        \file_put_contents($this->temp, 'test content');

        $file = new FileSource($this->temp);

        self::assertTrue($file->isReadable);
    }

    public function testIsReadablePropertyWhenFileNotExists(): void
    {
        $file = new FileSource($this->temp);

        self::assertFalse($file->isReadable);
    }

    public function testModifiedAtProperty(): void
    {
        \file_put_contents($this->temp, 'test content');
        $expectedTime = \filemtime($this->temp);

        $file = new FileSource($this->temp);

        self::assertSame($expectedTime, $file->modifiedAt);
    }

    public function testContentProperty(): void
    {
        $content = 'test content';
        \file_put_contents($this->temp, $content);

        $file = new FileSource($this->temp);

        self::assertSame($content, $file->content);
    }

    public function testContentPropertyIsReadOnce(): void
    {
        \file_put_contents($this->temp, 'first content');
        $modifiedAt = \filemtime($this->temp);

        $file = new FileSource($this->temp);

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

        $file = new FileSource($this->temp);

        self::assertSame('first content', $file->content);

        \file_put_contents($this->temp, 'second content');
        \touch($this->temp, \filemtime($this->temp) + 1);

        self::assertSame('second content', $file->content);
    }

    public function testContentPropertyIsReadAgainAfterResize(): void
    {
        \file_put_contents($this->temp, 'first content');
        $modifiedAt = \filemtime($this->temp);

        $file = new FileSource($this->temp);

        self::assertSame('first content', $file->content);

        // The file is rewritten within the very same second, so only its size
        // tells that it has been changed
        \file_put_contents($this->temp, 'first content and a bit more');
        \touch($this->temp, $modifiedAt);

        self::assertSame('first content and a bit more', $file->content);
    }

    public function testContentPropertyThrowsWhenFileNotFound(): void
    {
        $file = new FileSource($this->temp);

        $this->expectException(NotFoundException::class);

        $file->content;
    }

    public function testSizeProperty(): void
    {
        \file_put_contents($this->temp, 'test content');

        $file = new FileSource($this->temp);

        self::assertSame(12, $file->size);
    }

    public function testCreateStreamReadsTheFileFromTheBeginning(): void
    {
        $content = 'test content';
        \file_put_contents($this->temp, $content);

        $file = new FileSource($this->temp);

        $stream = $file->createStream();

        // A regular file can be rewound, so the cursor it hands out can too
        self::assertInstanceOf(SeekableResourceStream::class, $stream);
        self::assertSame(0, $stream->offset);
        self::assertSame($content, $stream->read(1024));
        self::assertTrue($stream->isEof);
    }

    public function testCreateStreamReturnsIndependentCursors(): void
    {
        $content = 'test content';
        \file_put_contents($this->temp, $content);

        $file = new FileSource($this->temp);

        $first = $file->createStream();
        $first->read(5);

        $second = $file->createStream();

        self::assertSame(5, $first->offset);
        self::assertSame(0, $second->offset);
        self::assertSame($content, $second->read(1024));
    }

    public function testCreateStreamClosesTheFileAlongWithTheCursor(): void
    {
        \file_put_contents($this->temp, 'test content');

        $file = new FileSource($this->temp);

        $before = \count(\get_resources('stream'));

        $stream = $file->createStream();
        unset($stream);

        self::assertCount($before, \get_resources('stream'));
    }

    public function testCreateStreamThrowsWhenFileNotReadable(): void
    {
        $file = new FileSource($this->temp);

        $this->expectException(NotReadableException::class);

        $file->createStream();
    }
}
