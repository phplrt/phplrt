<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests\Stream;

use Phplrt\Source\Exception\NotAccessibleException;
use Phplrt\Source\Tests\TestCase;
use Phplrt\Source\Stream\SeekableResourceStream;

final class SeekableResourceStreamTest extends TestCase
{
    public function testFailsInCaseOfNonSeekableResource(): void
    {
        $resource = $this->createNonSeekableResource('test content');

        try {
            $this->expectException(NotAccessibleException::class);
            $this->expectExceptionMessage('does not support offset');

            new SeekableResourceStream($resource);
        } finally {
            \fclose($resource);
        }
    }

    public function testReadsFromTheGivenOffset(): void
    {
        $stream = $this->createStream('test content');
        $stream->offset = 5;

        self::assertSame('content', $stream->read(1024));
        self::assertSame(12, $stream->offset);
    }

    public function testMovesBackwards(): void
    {
        $stream = $this->createStream('test content');

        self::assertSame('test content', $stream->read(1024));

        $stream->offset = 0;

        self::assertSame('test', $stream->read(4));
    }

    public function testReadsEmptyStringBeyondTheEnd(): void
    {
        $stream = $this->createStream('test');
        $stream->offset = 1024;

        self::assertSame('', $stream->read(1024));
        self::assertTrue($stream->isEof);
    }

    public function testIsEofDoesNotMoveTheCursor(): void
    {
        $stream = $this->createStream('test content');

        self::assertFalse($stream->isEof);
        self::assertSame(0, $stream->offset);
        self::assertSame('test content', $stream->read(1024));
    }

    public function testIsEofIsTrueForAnEmptyStream(): void
    {
        self::assertTrue($this->createStream('')->isEof);
    }

    public function testCursorsSharingAResourceDoNotInterfere(): void
    {
        $resource = \fopen('php://memory', 'rb+');
        \fwrite($resource, 'test content');

        $first = new SeekableResourceStream($resource);
        $second = new SeekableResourceStream($resource);

        self::assertSame('test', $first->read(4));
        self::assertSame('test content', $second->read(1024));

        // The reading of the other cursor has left the resource at its end,
        // which the first one is expected to survive
        self::assertSame(' content', $first->read(1024));

        \fclose($resource);
    }

    public function testFailsInCaseOfNegativeOffset(): void
    {
        $stream = $this->createStream('test');

        $this->expectException(\InvalidArgumentException::class);

        $stream->offset = -1;
    }

    public function testFailsInCaseOfNonPositiveReadSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createStream('test')->read(0);
    }

    private function createStream(string $content): SeekableResourceStream
    {
        $resource = \fopen('php://memory', 'rb+');
        \fwrite($resource, $content);

        return new SeekableResourceStream($resource, autoclose: true);
    }
}
