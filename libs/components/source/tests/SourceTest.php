<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Source\Source;

final class SourceTest extends TestCase
{
    public function testConstructor(): void
    {
        $content = 'test content';
        $source = new Source($content, $this->hasher);

        self::assertSame($content, $source->content);
    }

    public function testContentProperty(): void
    {
        $content = 'test content';
        $source = new Source($content, $this->hasher);

        self::assertSame($content, $source->content);
    }

    public function testStreamProperty(): void
    {
        $content = 'test content';
        $source = new Source($content, $this->hasher);

        $stream = $source->stream;

        self::assertIsResource($stream);
        self::assertSame($content, \stream_get_contents($stream));
    }

    public function testStreamPropertyRewinds(): void
    {
        $content = 'test content';
        $source = new Source($content, $this->hasher);

        $stream = $source->stream;
        \fseek($stream, 5);
        $stream2 = $source->stream;

        // Each access should return a new stream positioned at the start
        self::assertSame($content, \stream_get_contents($stream2));
    }

    public function testHashProperty(): void
    {
        $content = 'test content';
        $source = new Source($content, $this->hasher);

        $hash = $source->hash;

        self::assertNotEmpty($hash);
        self::assertIsString($hash);
    }

    public function testHashPropertyCaching(): void
    {
        $content = 'test content';
        $source = new Source($content, $this->hasher);

        // Access hash multiple times
        $hash1 = $source->hash;
        $hash2 = $source->hash;

        self::assertSame($hash1, $hash2);
        self::assertNotEmpty($hash1);
    }

    public function testEmptyContent(): void
    {
        $content = '';
        $source = new Source($content, $this->hasher);

        self::assertSame('', $source->content);
        self::assertIsResource($source->stream);
    }
}

