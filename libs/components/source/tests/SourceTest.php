<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Source\Source;

final class SourceTest extends TestCase
{
    public function testConstructor(): void
    {
        $content = 'test content';
        $source = new Source($content);

        self::assertSame($content, $source->content);
    }

    public function testContentProperty(): void
    {
        $content = 'test content';
        $source = new Source($content);

        self::assertSame($content, $source->content);
    }

    public function testStreamProperty(): void
    {
        $content = 'test content';
        $source = new Source($content);

        $stream = $source->stream;

        self::assertIsResource($stream);
        self::assertSame($content, \stream_get_contents($stream));
    }

    public function testStreamPropertyRewinds(): void
    {
        $content = 'test content';
        $source = new Source($content);

        $stream = $source->stream;
        \fseek($stream, 5);
        $stream2 = $source->stream;

        // Each access should return a new stream positioned at the start
        self::assertSame($content, \stream_get_contents($stream2));
    }

    public function testEmptyContent(): void
    {
        $content = '';
        $source = new Source($content);

        self::assertSame('', $source->content);
        self::assertIsResource($source->stream);
    }
}
