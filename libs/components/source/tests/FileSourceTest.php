<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Source\Exception\NotFoundException;
use Phplrt\Source\Exception\NotReadableException;
use Phplrt\Source\FileSource;

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

    public function testContentIsWhatIsLeftFromTheCursor(): void
    {
        \file_put_contents($this->temp, 'test content');

        $file = new FileSource($this->temp);

        self::assertSame('test', $file->read(4));

        // Taking the content out leaves the cursor where it has been
        self::assertSame(' content', $file->content);
        self::assertSame(' content', $file->content);
        self::assertSame(4, $file->offset);

        self::assertSame(' content', $file->read(1024));
    }

    public function testTheFileIsGivenUpAlongWithTheSource(): void
    {
        \file_put_contents($this->temp, 'first content');

        $file = new FileSource($this->temp);

        self::assertSame('first content', $file->content);

        unset($file);

        self::assertNotFalse(\file_put_contents($this->temp, 'second content'));
        self::assertSame('second content', new FileSource($this->temp)->content);
    }

    public function testContentPropertyThrowsWhenFileNotFound(): void
    {
        $file = new FileSource($this->temp);

        $this->expectException(NotFoundException::class);

        $file->content;
    }

    public function testSizeThrowsWhenFileNotFound(): void
    {
        $file = new FileSource($this->temp);

        $this->expectException(NotFoundException::class);

        $file->size;
    }

    public function testModifiedAtThrowsWhenFileNotFound(): void
    {
        $file = new FileSource($this->temp);

        $this->expectException(NotFoundException::class);

        $file->modifiedAt;
    }

    public function testSizeProperty(): void
    {
        \file_put_contents($this->temp, 'test content');

        $file = new FileSource($this->temp);

        self::assertSame(12, $file->size);
    }

    public function testReadsTheFileFromTheBeginning(): void
    {
        $content = 'test content';
        \file_put_contents($this->temp, $content);

        $file = new FileSource($this->temp);

        self::assertSame(0, $file->offset);
        self::assertSame($content, $file->read(1024));
        self::assertSame(12, $file->offset);
        self::assertTrue($file->isEof);
    }

    public function testReadsByChunks(): void
    {
        \file_put_contents($this->temp, 'test content');

        $file = new FileSource($this->temp);

        self::assertSame('test', $file->read(4));
        self::assertSame(4, $file->offset);
        self::assertSame(' content', $file->read(1024));
    }

    public function testMovesToAnArbitraryPosition(): void
    {
        \file_put_contents($this->temp, 'test content');

        $file = new FileSource($this->temp);

        self::assertTrue($file->isSeekable);
        self::assertSame('test', $file->read(4));

        $file->offset = 0;

        self::assertSame('test', $file->read(4));
    }

    public function testDoesNotOpenTheFileUntilItIsRead(): void
    {
        \file_put_contents($this->temp, 'test content');

        $before = \count(\get_resources('stream'));

        $file = new FileSource($this->temp);

        self::assertCount($before, \get_resources('stream'));

        $file->read(1);

        self::assertCount($before + 1, \get_resources('stream'));
    }

    public function testClosesTheFileAlongWithTheSource(): void
    {
        \file_put_contents($this->temp, 'test content');

        $before = \count(\get_resources('stream'));

        $file = new FileSource($this->temp);
        $file->read(1);
        unset($file);

        self::assertCount($before, \get_resources('stream'));
    }

    public function testReadingThrowsWhenFileNotReadable(): void
    {
        $file = new FileSource($this->temp);

        $this->expectException(NotReadableException::class);

        $file->read(1024);
    }
}
