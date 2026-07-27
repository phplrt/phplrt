<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Source\Stream;

final class StreamTest extends TestCase
{
    public function testConstructor(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        $streamObj = new Stream($stream);

        self::assertSame($stream, $streamObj->stream);
    }

    public function testContentProperty(): void
    {
        $content = 'test content';
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, $content);
        \rewind($stream);

        $streamObj = new Stream($stream);

        self::assertSame($content, $streamObj->content);
    }

    public function testContentPropertyReadsFromCurrentPosition(): void
    {
        $content = 'test content';
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, $content);
        \rewind($stream);
        \fseek($stream, 5); // Move to position 5

        $streamObj = new Stream($stream);

        // stream_get_contents reads from current position
        self::assertSame('content', $streamObj->content);
    }

    public function testUriPropertyWithFileStream(): void
    {
        \file_put_contents($this->temp, 'test content');

        $stream = \fopen($this->temp, 'rb');

        try {
            $streamObj = new Stream($stream);

            self::assertSame($this->temp, $streamObj->uri);
        } finally {
            \fclose($stream);
        }
    }

    public function testUriPropertyWithMemoryStream(): void
    {
        $stream = \fopen('php://memory', 'rb+');

        $streamObj = new Stream($stream);

        self::assertSame('php://memory', $streamObj->uri);
    }

    public function testModeProperty(): void
    {
        $stream = \fopen('php://memory', 'w+b');

        $streamObj = new Stream($stream);

        self::assertSame('w+b', $streamObj->mode);
    }

    public function testOffsetProperty(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');
        \fseek($stream, 5);

        $streamObj = new Stream($stream);

        self::assertSame(5, $streamObj->offset);
    }

    public function testIsLocalPropertyWithFileStream(): void
    {
        \file_put_contents($this->temp, 'test content');

        $stream = \fopen($this->temp, 'rb');

        try {
            $streamObj = new Stream($stream);

            self::assertTrue($streamObj->isLocal);
        } finally {
            \fclose($stream);
        }
    }

    public function testIsLocalPropertyWithMemoryStream(): void
    {
        $streamObj = new Stream(\fopen('php://memory', 'rb'));

        self::assertTrue($streamObj->isLocal);
    }

    public function testSerializationWithFileStream(): void
    {
        \file_put_contents($this->temp, 'test content');

        $stream = \fopen($this->temp, 'rb');
        \fseek($stream, 3);

        try {
            $streamObj = new Stream($stream);
            $serialized = \serialize($streamObj);
            $unserialized = \unserialize($serialized);

            self::assertInstanceOf(Stream::class, $unserialized);
            self::assertSame($this->temp, $unserialized->uri);
            self::assertSame(3, $unserialized->offset);
        } finally {
            \fclose($stream);
        }
    }
}
