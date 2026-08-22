<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Source\Exception\InvalidArgumentException;
use Phplrt\Source\StringSource;

final class StringSourceTest extends TestCase
{
    public function testConstructor(): void
    {
        $content = 'test content';
        $source = new StringSource($content);

        self::assertSame($content, $source->content);
    }

    public function testContentProperty(): void
    {
        $content = 'test content';
        $source = new StringSource($content);

        self::assertSame($content, $source->content);
    }

    public function testSizeProperty(): void
    {
        $source = new StringSource('test content');

        self::assertSame(12, $source->size);
    }

    public function testReadsTheWholeContent(): void
    {
        $content = 'test content';
        $source = new StringSource($content);

        self::assertSame(0, $source->offset);
        self::assertSame($content, $source->read(1024));
        self::assertSame(12, $source->offset);
        self::assertTrue($source->isEof);
    }

    public function testReadsByChunks(): void
    {
        $source = new StringSource('test content');

        self::assertSame('test', $source->read(4));
        self::assertSame(4, $source->offset);
        self::assertSame(' content', $source->read(1024));
    }

    public function testMovesBackwards(): void
    {
        $content = 'test content';
        $source = new StringSource($content);

        $source->read(1024);
        $source->offset = 0;

        self::assertSame($content, $source->read(1024));
    }

    public function testReadsFromTheGivenOffset(): void
    {
        $source = new StringSource('test content');
        $source->offset = 5;

        self::assertSame('content', $source->read(1024));
    }

    public function testFailsInCaseOfNegativeOffset(): void
    {
        $source = new StringSource('test content');

        $this->expectException(InvalidArgumentException::class);

        $source->offset = -1;
    }

    public function testFailsInCaseOfNonPositiveReadSize(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new StringSource('test content')->read(0);
    }

    public function testReadingDoesNotOpenAnyResource(): void
    {
        $source = new StringSource('test content');

        $before = \count(\get_resources('stream'));

        $source->read(1024);

        self::assertCount($before, \get_resources('stream'));
    }

    public function testEmptyContent(): void
    {
        $source = new StringSource('');

        self::assertSame('', $source->content);
        self::assertSame(0, $source->size);
        self::assertTrue($source->isEof);
        self::assertSame('', $source->read(1024));
    }
}
