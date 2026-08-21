<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests\Stream;

use Phplrt\Source\Exception\NotCreatableException;
use Phplrt\Source\Stream\ForwardResourceStream;
use Phplrt\Source\Tests\TestCase;

final class ResourceStreamTest extends TestCase
{
    public function testFailsInCaseOfNonResource(): void
    {
        $this->expectException(NotCreatableException::class);
        $this->expectExceptionMessage('from string type');

        new ForwardResourceStream('php://memory');
    }

    public function testFailsInCaseOfNonStreamResource(): void
    {
        $this->expectException(NotCreatableException::class);
        $this->expectExceptionMessage('from non-stream resource type');

        new ForwardResourceStream(\stream_context_create());
    }

    public function testReadsTheWholeStream(): void
    {
        $stream = $this->createForwardStream('test content');

        self::assertSame('test content', $stream->read(1024));
        self::assertSame(12, $stream->offset);
    }

    public function testReadsByChunks(): void
    {
        $stream = $this->createForwardStream('test content');

        self::assertSame('test', $stream->read(4));
        self::assertSame(4, $stream->offset);

        self::assertSame(' content', $stream->read(1024));
        self::assertSame(12, $stream->offset);
    }

    public function testReadsEmptyStringAtTheEnd(): void
    {
        $stream = $this->createForwardStream('test');
        $stream->read(1024);

        self::assertSame('', $stream->read(1024));
        self::assertSame(4, $stream->offset);
    }

    public function testIsEofIsFalseWhileSomethingIsLeft(): void
    {
        $stream = $this->createForwardStream('test');

        self::assertFalse($stream->isEof);
        // Finding out that the end has not been reached does not move the cursor
        self::assertSame(0, $stream->offset);
        self::assertSame('test', $stream->read(1024));
    }

    public function testIsEofIsTrueForAnEmptyStream(): void
    {
        self::assertTrue($this->createForwardStream('')->isEof);
    }

    public function testIsEofIsTrueAtTheEnd(): void
    {
        $stream = $this->createForwardStream('test');
        $stream->read(1024);

        self::assertTrue($stream->isEof);
    }

    public function testPeekedByteIsReturnedByTheNextRead(): void
    {
        $stream = $this->createForwardStream('test');

        self::assertFalse($stream->isEof);
        self::assertSame('t', $stream->read(1));
        self::assertSame(1, $stream->offset);
        self::assertSame('est', $stream->read(1024));
    }

    public function testFailsInCaseOfNonPositiveReadSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createForwardStream('test')->read(0);
    }

    public function testAutocloseIsDisabledByDefault(): void
    {
        $resource = \fopen('php://memory', 'rb+');

        $stream = new ForwardResourceStream($resource);
        unset($stream);

        self::assertIsResource($resource);
    }

    public function testAutocloseClosesTheResource(): void
    {
        $resource = \fopen('php://memory', 'rb+');

        $stream = new ForwardResourceStream($resource, autoclose: true);
        unset($stream);

        self::assertIsClosedResource($resource);
    }

    private function createForwardStream(string $content): ForwardResourceStream
    {
        $resource = \fopen('php://memory', 'rb+');
        \fwrite($resource, $content);
        \rewind($resource);

        return new ForwardResourceStream($resource, autoclose: true);
    }
}
