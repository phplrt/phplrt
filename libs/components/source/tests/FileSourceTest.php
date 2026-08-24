<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Source\Exception\FileNotFoundException;
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

    public function testContentIsTheWholeFile(): void
    {
        \file_put_contents($this->temp, 'test content');

        $file = new FileSource($this->temp);

        self::assertSame('test', $file->read(0, 4));

        self::assertSame('test content', $file->content);
        self::assertSame('test content', $file->content);

        self::assertSame(' content', $file->read(4, 1024));
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

        $this->expectException(FileNotFoundException::class);

        $file->content;
    }

    public function testSizeThrowsWhenFileNotFound(): void
    {
        $file = new FileSource($this->temp);

        $this->expectException(FileNotFoundException::class);

        $file->size;
    }

    public function testModifiedAtThrowsWhenFileNotFound(): void
    {
        $file = new FileSource($this->temp);

        $this->expectException(FileNotFoundException::class);

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

        self::assertSame($content, $file->read(0, 1024));
    }

    public function testReadsByChunks(): void
    {
        \file_put_contents($this->temp, 'test content');

        $file = new FileSource($this->temp);

        self::assertSame('test', $file->read(0, 4));
        self::assertSame(' content', $file->read(4, 1024));
    }

    public function testReadsInAnArbitraryOrder(): void
    {
        \file_put_contents($this->temp, 'test content');

        $file = new FileSource($this->temp);

        self::assertSame('content', $file->read(5, 1024));
        self::assertSame('test', $file->read(0, 4));
        self::assertSame('content', $file->read(5, 1024));
    }

    public function testDoesNotOpenTheFileUntilItIsRead(): void
    {
        \file_put_contents($this->temp, 'test content');

        $before = \count(\get_resources('stream'));

        $file = new FileSource($this->temp);

        self::assertCount($before, \get_resources('stream'));

        $file->read(0, 1);

        self::assertCount($before + 1, \get_resources('stream'));
    }

    public function testClosesTheFileAlongWithTheSource(): void
    {
        \file_put_contents($this->temp, 'test content');

        $before = \count(\get_resources('stream'));

        $file = new FileSource($this->temp);
        $file->read(0, 1);
        unset($file);

        self::assertCount($before, \get_resources('stream'));
    }

    public function testReadingThrowsWhenFileNotFound(): void
    {
        $file = new FileSource($this->temp);

        $this->expectException(FileNotFoundException::class);

        $file->read(0, 1024);
    }
}
