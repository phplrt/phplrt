<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests\Stream;

use Phplrt\Source\Stream\StringStream;
use Phplrt\Source\Tests\TestCase;

final class StringStreamTest extends TestCase
{
    public function testReadsTheWholeString(): void
    {
        $stream = new StringStream('test content');

        self::assertSame('test content', $stream->read(1024));
        self::assertSame(12, $stream->offset);
    }

    public function testReadsByChunks(): void
    {
        $stream = new StringStream('test content');

        self::assertSame('test', $stream->read(4));
        self::assertSame(4, $stream->offset);

        self::assertSame(' content', $stream->read(1024));
        self::assertSame(12, $stream->offset);
    }

    public function testReadsFromTheGivenOffset(): void
    {
        $stream = new StringStream('test content');
        $stream->offset = 5;

        self::assertSame('content', $stream->read(1024));
    }

    public function testMovesBackwards(): void
    {
        $stream = new StringStream('test content');
        $stream->read(1024);

        $stream->offset = 0;

        self::assertSame('test', $stream->read(4));
    }

    public function testReadsEmptyStringBeyondTheEnd(): void
    {
        $stream = new StringStream('test');
        $stream->offset = 1024;

        self::assertSame('', $stream->read(1024));
        self::assertSame(1024, $stream->offset);
        self::assertTrue($stream->isEof);
    }

    public function testIsEofIsTrueForAnEmptyString(): void
    {
        self::assertTrue(new StringStream()->isEof);
    }

    public function testIsEofIsTrueAtTheEnd(): void
    {
        $stream = new StringStream('test');

        self::assertFalse($stream->isEof);

        $stream->read(1024);

        self::assertTrue($stream->isEof);
    }

    public function testFailsInCaseOfNegativeOffset(): void
    {
        $stream = new StringStream('test');

        $this->expectException(\InvalidArgumentException::class);

        $stream->offset = -1;
    }

    public function testFailsInCaseOfNonPositiveReadSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new StringStream('test')->read(0);
    }
}
