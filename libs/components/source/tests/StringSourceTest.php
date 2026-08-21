<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Source\Stream\StringStream;
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

    public function testCreateStream(): void
    {
        $content = 'test content';
        $source = new StringSource($content);

        $stream = $source->createStream();

        self::assertInstanceOf(StringStream::class, $stream);
        self::assertSame(0, $stream->offset);
        self::assertSame($content, $stream->read(1024));
    }

    public function testCreateStreamReturnsIndependentCursors(): void
    {
        $content = 'test content';
        $source = new StringSource($content);

        $first = $source->createStream();
        $first->offset = 5;

        $second = $source->createStream();

        // Each call returns a cursor placed at the beginning of the source
        self::assertSame(0, $second->offset);
        self::assertSame($content, $second->read(1024));
        self::assertSame(5, $first->offset);
    }

    public function testCreateStreamDoesNotOpenAnyResource(): void
    {
        $source = new StringSource('test content');

        $before = \count(\get_resources('stream'));

        $source->createStream();

        self::assertCount($before, \get_resources('stream'));
    }

    public function testEmptyContent(): void
    {
        $source = new StringSource('');

        self::assertSame('', $source->content);
        self::assertSame(0, $source->size);
        self::assertTrue($source->createStream()->isEof);
    }
}
