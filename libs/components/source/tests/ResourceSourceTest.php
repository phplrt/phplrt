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

    public function testContentPropertyReadsFromWhereTheStreamHasBeenLeft(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');
        \fseek($stream, 5);

        $source = new ResourceSource($stream);

        // A stream that has already been read in part is the source of what
        // is left of it
        self::assertSame(5, $source->offset);
        self::assertSame('content', $source->content);
    }

    public function testSizeProperty(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');

        $source = new ResourceSource($stream);

        self::assertSame(12, $source->size);
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

    public function testReadsFromWhereTheStreamHasBeenLeft(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');
        \fseek($stream, 5);

        $source = new ResourceSource($stream);

        self::assertSame(5, $source->offset);
        self::assertSame('content', $source->read(1024));
        self::assertSame(12, $source->offset);
        self::assertTrue($source->isEof);
    }

    public function testContentIsWhatIsLeftAfterReading(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');
        \rewind($stream);

        $source = new ResourceSource($stream);

        self::assertSame('test', $source->read(4));

        // Taking the content out leaves the cursor where it has been
        self::assertSame(' content', $source->content);
        self::assertSame(' content', $source->content);
        self::assertSame(4, $source->offset);
        self::assertFalse($source->isEof);
    }

    public function testReadingIsNotAffectedByCheckingForTheEnd(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');
        \rewind($stream);

        $source = new ResourceSource($stream);

        self::assertFalse($source->isEof);
        self::assertSame('test', $source->read(4));
        self::assertSame(4, $source->offset);

        self::assertFalse($source->isEof);
        self::assertSame(' content', $source->content);
    }

    public function testMovesToAnArbitraryPosition(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');
        \rewind($stream);

        $source = new ResourceSource($stream);

        self::assertTrue($source->isSeekable);
        self::assertSame('test', $source->read(4));

        $source->offset = 0;

        self::assertSame(0, $source->offset);
        self::assertSame('test', $source->read(4));
    }

    public function testMovingForgetsWhatHasBeenPeekedAt(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');
        \rewind($stream);

        $source = new ResourceSource($stream);

        self::assertFalse($source->isEof);

        $source->offset = 5;

        self::assertSame('content', $source->read(1024));
    }

    public function testNonSeekableStreamCannotBeMoved(): void
    {
        $stream = $this->createNonSeekableResource('test content');

        try {
            $source = new ResourceSource($stream);

            self::assertFalse($source->isSeekable);

            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('does not support offset');

            $source->offset = 5;
        } finally {
            \fclose($stream);
        }
    }

    public function testFailsInCaseOfNonPositiveReadSize(): void
    {
        $source = new ResourceSource(\fopen('php://memory', 'rb+'));

        $this->expectException(InvalidArgumentException::class);

        $source->read(0);
    }

    public function testReadingKeepsTheResourceOpen(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');

        $source = new ResourceSource($stream);
        $source->read(1024);
        unset($source);

        // The resource belongs to whoever has passed it in
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

    public function testNonSeekableStreamHasNoSizeUntilItEnds(): void
    {
        $stream = $this->createNonSeekableResource('test content');

        try {
            $source = new ResourceSource($stream);

            self::assertFalse($source->isSeekable);
            self::assertNull($source->size);

            self::assertSame('test content', $source->read(1024));

            // The number of bytes that did arrive is known once the end has
            // been reached
            self::assertSame(12, $source->size);
        } finally {
            \fclose($stream);
        }
    }

    public function testNonSeekableStreamIsReadableOnce(): void
    {
        $stream = $this->createNonSeekableResource('test content');

        try {
            $source = new ResourceSource($stream);

            self::assertSame('test content', $source->content);

            $this->expectException(NotReadableException::class);
            $this->expectExceptionMessage('can be read only once');

            $source->content;
        } finally {
            \fclose($stream);
        }
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

        foreach (['size', 'isEof', 'content'] as $property) {
            try {
                $source->$property;
                self::fail(\sprintf('Reading $%s did not report the closed resource', $property));
            } catch (NotCreatableException $e) {
                self::assertStringContainsString('from closed resource type', $e->getMessage());
            }
        }

        $this->expectException(NotCreatableException::class);

        $source->read(4);
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

            // The position the source is at survives along with it
            self::assertSame(3, $unserialized->offset);
            self::assertSame('t content', $unserialized->content);
        } finally {
            \fclose($stream);
        }
    }

    public function testSerializationKeepsWhatHasAlreadyBeenRead(): void
    {
        \file_put_contents($this->temp, 'test content');

        $stream = \fopen($this->temp, 'rb');

        try {
            $source = new ResourceSource($stream);

            self::assertSame('test', $source->read(4));

            $unserialized = \unserialize(\serialize($source));

            self::assertInstanceOf(ResourceSource::class, $unserialized);
            self::assertSame(4, $unserialized->offset);
            self::assertSame(' content', $unserialized->read(1024));
            self::assertTrue($unserialized->isEof);
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
