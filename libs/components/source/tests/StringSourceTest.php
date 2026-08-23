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

    public function testContentIsTheWholeSource(): void
    {
        $content = 'test content';
        $source = new StringSource($content);

        self::assertSame('test', $source->read(0, 4));

        // Reading a part of the source leaves the source itself as it is
        self::assertSame($content, $source->content);
        self::assertSame($content, $source->content);
    }

    public function testReadsTheWholeContent(): void
    {
        $content = 'test content';
        $source = new StringSource($content);

        self::assertSame($content, $source->read(0, 1024));
    }

    public function testReadsByChunks(): void
    {
        $source = new StringSource('test content');

        self::assertSame('test', $source->read(0, 4));
        self::assertSame(' content', $source->read(4, 1024));
    }

    public function testReadsInAnArbitraryOrder(): void
    {
        $source = new StringSource('test content');

        self::assertSame('content', $source->read(5, 1024));
        self::assertSame('test', $source->read(0, 4));
        self::assertSame('content', $source->read(5, 1024));
    }

    public function testReadsNothingBeyondTheEnd(): void
    {
        $source = new StringSource('test content');

        self::assertSame('', $source->read(12, 1024));
        self::assertSame('', $source->read(1024, 1024));
    }

    public function testFailsInCaseOfNegativeOffset(): void
    {
        $source = new StringSource('test content');

        $this->expectException(InvalidArgumentException::class);

        $source->read(-1, 1024);
    }

    public function testFailsInCaseOfNonPositiveReadSize(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new StringSource('test content')->read(0, 0);
    }

    public function testReadingDoesNotOpenAnyResource(): void
    {
        $source = new StringSource('test content');

        $before = \count(\get_resources('stream'));

        $source->read(0, 1024);

        self::assertCount($before, \get_resources('stream'));
    }

    public function testEmptyContent(): void
    {
        $source = new StringSource('');

        self::assertSame('', $source->content);
        self::assertSame('', $source->read(0, 1024));
    }
}
