<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Source\Exception\InvalidArgumentException;
use Phplrt\Source\Exception\LogicException;
use Phplrt\Source\Exception\NotCreatableException;
use Phplrt\Source\Exception\NotReadableException;
use Phplrt\Source\ResourceSource;

final class ResourceSourceTest extends TestCase
{
    public function testWorksOverTheVeryResourceItHasBeenGiven(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        $source = new ResourceSource($stream);

        \fwrite($stream, 'test content');
        \rewind($stream);

        self::assertSame('test content', $source->content);
    }

    public function testContentProperty(): void
    {
        $content = 'test content';
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, $content);
        \rewind($stream);

        $source = new ResourceSource($stream);

        self::assertSame($content, $source->content);
    }

    public function testSourceBeginsWhereTheStreamHasBeenLeft(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');
        \fseek($stream, 5);

        $source = new ResourceSource($stream);

        self::assertSame('content', $source->content);
        self::assertSame('content', $source->read(0, 1024));
        self::assertSame('tent', $source->read(3, 1024));
    }

    public function testUriPropertyWithFileStream(): void
    {
        \file_put_contents($this->temp, 'test content');

        $stream = \fopen($this->temp, 'rb');

        try {
            $source = new ResourceSource($stream);

            self::assertSame($this->temp, $source->uri);
        } finally {
            \fclose($stream);
        }
    }

    public function testUriPropertyWithMemoryStream(): void
    {
        $stream = \fopen('php://memory', 'rb+');

        $source = new ResourceSource($stream);

        self::assertSame('php://memory', $source->uri);
    }

    public function testModeProperty(): void
    {
        $stream = \fopen('php://memory', 'w+b');

        $source = new ResourceSource($stream);

        self::assertSame('w+b', $source->mode);
    }

    public function testIsSeekablePropertyWithMemoryStream(): void
    {
        $source = new ResourceSource(\fopen('php://memory', 'rb+'));

        self::assertTrue($source->isSeekable);
    }

    public function testIsLocalPropertyWithFileStream(): void
    {
        \file_put_contents($this->temp, 'test content');

        $stream = \fopen($this->temp, 'rb');

        try {
            $source = new ResourceSource($stream);

            self::assertTrue($source->isLocal);
        } finally {
            \fclose($stream);
        }
    }

    public function testIsLocalPropertyWithMemoryStream(): void
    {
        $source = new ResourceSource(\fopen('php://memory', 'rb'));

        self::assertTrue($source->isLocal);
    }

    public function testFailsInCaseOfWriteOnlyStream(): void
    {
        $this->expectException(NotReadableException::class);
        $this->expectExceptionMessage('is not open for reading');

        new ResourceSource(\fopen('php://output', 'wb'));
    }

    public function testContentIsTheWholeSource(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');
        \rewind($stream);

        $source = new ResourceSource($stream);

        self::assertSame('test', $source->read(0, 4));

        self::assertSame('test content', $source->content);
        self::assertSame('test content', $source->content);
    }

    public function testReadsInAnArbitraryOrder(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');
        \rewind($stream);

        $source = new ResourceSource($stream);

        self::assertSame(' content', $source->read(4, 1024));
        self::assertSame('test', $source->read(0, 4));
        self::assertSame(' content', $source->read(4, 1024));
    }

    public function testReadsNothingBeyondTheEnd(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');
        \rewind($stream);

        $source = new ResourceSource($stream);

        self::assertSame('', $source->read(12, 1024));
        self::assertSame('', $source->read(1024, 1024));
    }

    public function testNonSeekableStreamIsReadInAnArbitraryOrder(): void
    {
        $stream = $this->createNonSeekableResource('test content');

        try {
            $source = new ResourceSource($stream);

            self::assertFalse($source->isSeekable);

            self::assertSame(' content', $source->read(4, 1024));
            self::assertSame('test', $source->read(0, 4));
            self::assertSame('', $source->read(1024, 1024));
        } finally {
            \fclose($stream);
        }
    }

    public function testNonSeekableStreamIsReadableMoreThanOnce(): void
    {
        $stream = $this->createNonSeekableResource('test content');

        try {
            $source = new ResourceSource($stream);

            self::assertSame('test content', $source->content);
            self::assertSame('test content', $source->content);
            self::assertSame('test', $source->read(0, 4));
        } finally {
            \fclose($stream);
        }
    }

    public function testFailsInCaseOfNonPositiveReadSize(): void
    {
        $source = new ResourceSource(\fopen('php://memory', 'rb+'));

        $this->expectException(InvalidArgumentException::class);

        $source->read(0, 0);
    }

    public function testFailsInCaseOfNegativeOffset(): void
    {
        $source = new ResourceSource(\fopen('php://memory', 'rb+'));

        $this->expectException(InvalidArgumentException::class);

        $source->read(-1, 1024);
    }

    public function testReadingKeepsTheResourceOpen(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');

        $source = new ResourceSource($stream);
        $source->read(0, 1024);
        unset($source);

        self::assertIsResource($stream);
    }

    public function testAutocloseIsDisabledByDefault(): void
    {
        $stream = \fopen('php://memory', 'rb+');

        $source = new ResourceSource($stream);
        unset($source);

        self::assertIsResource($stream);
    }

    public function testAutocloseClosesTheResource(): void
    {
        $stream = \fopen('php://memory', 'rb+');

        $source = new ResourceSource($stream, autoclose: true);
        unset($source);

        self::assertIsClosedResource($stream);
    }

    public function testFailsInCaseOfClosedResource(): void
    {
        $stream = \fopen('php://memory', 'rb+');

        $source = new ResourceSource($stream);

        \fclose($stream);

        $this->expectException(NotCreatableException::class);
        $this->expectExceptionMessage('from closed resource type');

        $source->content;
    }

    public function testEveryReadingOfAClosedResourceIsReported(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');
        \rewind($stream);

        $source = new ResourceSource($stream);

        \fclose($stream);

        try {
            $source->content;
            self::fail('Reading $content did not report the closed resource');
        } catch (NotCreatableException $e) {
            self::assertStringContainsString('from closed resource type', $e->getMessage());
        }

        $this->expectException(NotCreatableException::class);

        $source->read(0, 4);
    }

    public function testSerializationWithFileStream(): void
    {
        \file_put_contents($this->temp, 'test content');

        $stream = \fopen($this->temp, 'rb');
        \fseek($stream, 3);

        try {
            $source = new ResourceSource($stream);
            $serialized = \serialize($source);
            $unserialized = \unserialize($serialized);

            self::assertInstanceOf(ResourceSource::class, $unserialized);
            self::assertSame($this->temp, $unserialized->uri);

            self::assertSame('t content', $unserialized->content);
            self::assertSame('t co', $unserialized->read(0, 4));
        } finally {
            \fclose($stream);
        }
    }

    public function testSerializationIsNotAffectedByReading(): void
    {
        \file_put_contents($this->temp, 'test content');

        $stream = \fopen($this->temp, 'rb');

        try {
            $source = new ResourceSource($stream);

            self::assertSame('test', $source->read(0, 4));

            $unserialized = \unserialize(\serialize($source));

            self::assertInstanceOf(ResourceSource::class, $unserialized);
            self::assertSame('test content', $unserialized->content);
            self::assertSame(' content', $unserialized->read(4, 1024));
        } finally {
            \fclose($stream);
        }
    }

    public function testSerializationFailsWithoutUri(): void
    {
        $stream = $this->createNonSeekableResource('test content');

        try {
            $source = new ResourceSource($stream);

            $this->expectException(LogicException::class);

            \serialize($source);
        } finally {
            \fclose($stream);
        }
    }
}
