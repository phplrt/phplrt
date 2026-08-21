<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Source\Exception\NotCreatableException;
use Phplrt\Source\Exception\NotReadableException;
use Phplrt\Source\Stream\ForwardResourceStream;
use Phplrt\Source\Stream\SeekableResourceStream;
use Phplrt\Source\ResourceSource;

final class ResourceSourceTest extends TestCase
{
    public function testConstructor(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        $streamObj = new ResourceSource($stream);

        self::assertSame($stream, $streamObj->stream);
    }

    public function testContentProperty(): void
    {
        $content = 'test content';
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, $content);
        \rewind($stream);

        $streamObj = new ResourceSource($stream);

        self::assertSame($content, $streamObj->content);
    }

    public function testContentPropertyReadsTheWholeSeekableStream(): void
    {
        $content = 'test content';
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, $content);
        \fseek($stream, 5);

        $streamObj = new ResourceSource($stream);

        // A stream that can be rewound is the source of everything it holds,
        // no matter where it has been left at
        self::assertSame($content, $streamObj->content);
    }

    public function testSizeProperty(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');

        $streamObj = new ResourceSource($stream);

        self::assertSame(12, $streamObj->size);
    }

    public function testUriPropertyWithFileStream(): void
    {
        \file_put_contents($this->temp, 'test content');

        $stream = \fopen($this->temp, 'rb');

        try {
            $streamObj = new ResourceSource($stream);

            self::assertSame($this->temp, $streamObj->uri);
        } finally {
            \fclose($stream);
        }
    }

    public function testUriPropertyWithMemoryStream(): void
    {
        $stream = \fopen('php://memory', 'rb+');

        $streamObj = new ResourceSource($stream);

        self::assertSame('php://memory', $streamObj->uri);
    }

    public function testModeProperty(): void
    {
        $stream = \fopen('php://memory', 'w+b');

        $streamObj = new ResourceSource($stream);

        self::assertSame('w+b', $streamObj->mode);
    }

    public function testIsSeekablePropertyWithMemoryStream(): void
    {
        $streamObj = new ResourceSource(\fopen('php://memory', 'rb+'));

        self::assertTrue($streamObj->isSeekable);
    }

    public function testIsLocalPropertyWithFileStream(): void
    {
        \file_put_contents($this->temp, 'test content');

        $stream = \fopen($this->temp, 'rb');

        try {
            $streamObj = new ResourceSource($stream);

            self::assertTrue($streamObj->isLocal);
        } finally {
            \fclose($stream);
        }
    }

    public function testIsLocalPropertyWithMemoryStream(): void
    {
        $streamObj = new ResourceSource(\fopen('php://memory', 'rb'));

        self::assertTrue($streamObj->isLocal);
    }

    public function testFailsInCaseOfWriteOnlyStream(): void
    {
        $this->expectException(NotReadableException::class);
        $this->expectExceptionMessage('is not open for reading');

        new ResourceSource(\fopen('php://output', 'wb'));
    }

    public function testCreateStreamStartsAtTheBeginningOfASeekableStream(): void
    {
        $content = 'test content';
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, $content);
        \fseek($stream, 5);

        $streamObj = new ResourceSource($stream);

        $cursor = $streamObj->createStream();

        self::assertInstanceOf(SeekableResourceStream::class, $cursor);
        self::assertSame(0, $cursor->offset);
        self::assertSame($content, $cursor->read(1024));
    }

    public function testCreateStreamReturnsIndependentCursors(): void
    {
        $content = 'test content';
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, $content);

        $streamObj = new ResourceSource($stream);

        $first = $streamObj->createStream();
        $second = $streamObj->createStream();

        self::assertSame('test', $first->read(4));
        self::assertSame('test content', $second->read(1024));
        self::assertSame(' content', $first->read(1024));
    }

    public function testCreateStreamKeepsTheResourceOpen(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');

        $streamObj = new ResourceSource($stream);

        $cursor = $streamObj->createStream();
        unset($cursor);

        // The resource belongs to whoever has passed it in
        self::assertIsResource($stream);
    }

    public function testAutocloseIsDisabledByDefault(): void
    {
        $stream = \fopen('php://memory', 'rb+');

        $streamObj = new ResourceSource($stream);
        unset($streamObj);

        self::assertIsResource($stream);
    }

    public function testAutocloseClosesTheResource(): void
    {
        $stream = \fopen('php://memory', 'rb+');

        $streamObj = new ResourceSource($stream, autoclose: true);
        unset($streamObj);

        self::assertIsClosedResource($stream);
    }

    public function testNonSeekableStreamIsForwardOnly(): void
    {
        $stream = $this->createNonSeekableResource('test content');

        try {
            $streamObj = new ResourceSource($stream);

            self::assertFalse($streamObj->isSeekable);
            self::assertNull($streamObj->size);
            self::assertInstanceOf(ForwardResourceStream::class, $streamObj->createStream());
        } finally {
            \fclose($stream);
        }
    }

    public function testNonSeekableStreamIsReadableOnce(): void
    {
        $stream = $this->createNonSeekableResource('test content');

        try {
            $streamObj = new ResourceSource($stream);
            $streamObj->createStream();

            $this->expectException(NotReadableException::class);
            $this->expectExceptionMessage('can be read only once');

            $streamObj->createStream();
        } finally {
            \fclose($stream);
        }
    }

    public function testFailsInCaseOfClosedResource(): void
    {
        $stream = \fopen('php://memory', 'rb+');

        $streamObj = new ResourceSource($stream);

        \fclose($stream);

        $this->expectException(NotCreatableException::class);
        $this->expectExceptionMessage('from closed resource type');

        $streamObj->createStream();
    }

    public function testSerializationWithFileStream(): void
    {
        \file_put_contents($this->temp, 'test content');

        $stream = \fopen($this->temp, 'rb');
        \fseek($stream, 3);

        try {
            $streamObj = new ResourceSource($stream);
            $serialized = \serialize($streamObj);
            $unserialized = \unserialize($serialized);

            self::assertInstanceOf(ResourceSource::class, $unserialized);
            self::assertSame($this->temp, $unserialized->uri);
            self::assertSame('test content', $unserialized->content);
        } finally {
            \fclose($stream);
        }
    }

    public function testSerializationFailsWithoutUri(): void
    {
        $stream = $this->createNonSeekableResource('test content');

        try {
            $streamObj = new ResourceSource($stream);

            $this->expectException(\LogicException::class);

            \serialize($streamObj);
        } finally {
            \fclose($stream);
        }
    }
}
