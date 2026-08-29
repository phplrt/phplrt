<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Source\Exception\ClosedStreamException;
use Phplrt\Source\Exception\NegativeOffsetException;
use Phplrt\Source\Exception\NonPositiveBytesCountException;
use Phplrt\Source\Exception\StreamNotReadableException;
use Phplrt\Source\Exception\StreamNotRewindableException;
use Phplrt\Source\Exception\StreamNotSerializableException;
use Phplrt\Source\ResourceSource;
use Phplrt\Source\StringSource;
use Phplrt\Source\VirtualSource;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class ResourceSourceTest extends TestCase
{
    public function testWorksOverTheVeryResourceItHasBeenGiven(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        $source = new ResourceSource($stream);

        \fwrite($stream, 'test content');
        \rewind($stream);

        Assert::same($source->content, 'test content');
    }

    public function testContentProperty(): void
    {
        $content = 'test content';
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, $content);
        \rewind($stream);

        $source = new ResourceSource($stream);

        Assert::same($source->content, $content);
    }

    public function testSourceBeginsWhereTheStreamHasBeenLeft(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');
        \fseek($stream, 5);

        $source = new ResourceSource($stream);

        Assert::same($source->content, 'content');
        Assert::same($source->read(0, 1024), 'content');
        Assert::same($source->read(3, 1024), 'tent');
    }

    public function testUriPropertyWithFileStream(): void
    {
        \file_put_contents($this->temp, 'test content');

        $stream = \fopen($this->temp, 'rb');

        try {
            $source = new ResourceSource($stream);

            Assert::same($source->uri, $this->temp);
        } finally {
            \fclose($stream);
        }
    }

    public function testUriPropertyWithMemoryStream(): void
    {
        $stream = \fopen('php://memory', 'rb+');

        $source = new ResourceSource($stream);

        Assert::same($source->uri, 'php://memory');
    }

    public function testModeProperty(): void
    {
        $stream = \fopen('php://memory', 'w+b');

        $source = new ResourceSource($stream);

        Assert::same($source->mode, 'w+b');
    }

    public function testIsSeekablePropertyWithMemoryStream(): void
    {
        $source = new ResourceSource(\fopen('php://memory', 'rb+'));

        Assert::true($source->isSeekable);
    }

    public function testIsLocalPropertyWithFileStream(): void
    {
        \file_put_contents($this->temp, 'test content');

        $stream = \fopen($this->temp, 'rb');

        try {
            $source = new ResourceSource($stream);

            Assert::true($source->isLocal);
        } finally {
            \fclose($stream);
        }
    }

    public function testIsLocalPropertyWithMemoryStream(): void
    {
        $source = new ResourceSource(\fopen('php://memory', 'rb'));

        Assert::true($source->isLocal);
    }

    public function testFailsInCaseOfWriteOnlyStream(): void
    {
        Expect::exception(StreamNotReadableException::class)->withMessageContaining('is not open for reading');

        new ResourceSource(\fopen('php://output', 'wb'));
    }

    public function testContentIsTheWholeSource(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');
        \rewind($stream);

        $source = new ResourceSource($stream);

        Assert::same($source->read(0, 4), 'test');

        Assert::same($source->content, 'test content');
        Assert::same($source->content, 'test content');
    }

    public function testReadsInAnArbitraryOrder(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');
        \rewind($stream);

        $source = new ResourceSource($stream);

        Assert::same($source->read(4, 1024), ' content');
        Assert::same($source->read(0, 4), 'test');
        Assert::same($source->read(4, 1024), ' content');
    }

    public function testReadsNothingBeyondTheEnd(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');
        \rewind($stream);

        $source = new ResourceSource($stream);

        Assert::same($source->read(12, 1024), '');
        Assert::same($source->read(1024, 1024), '');
    }

    public function testNonSeekableStreamIsReadForwards(): void
    {
        $stream = $this->createNonSeekableResource('test content');

        try {
            $source = new ResourceSource($stream);

            Assert::false($source->isSeekable);

            Assert::same($source->read(0, 4), 'test');
            Assert::same($source->read(5, 1024), 'content');
            Assert::same($source->read(1024, 1024), '');
        } finally {
            \fclose($stream);
        }
    }

    public function testNonSeekableStreamCannotBeReadBackwards(): void
    {
        $stream = $this->createNonSeekableResource('test content');

        try {
            $source = new ResourceSource($stream);

            Assert::same($source->read(4, 1024), ' content');

            Expect::exception(StreamNotRewindableException::class);

            $source->read(0, 4);
        } finally {
            \fclose($stream);
        }
    }

    public function testNonSeekableStreamContentIsReadableMoreThanOnce(): void
    {
        $stream = $this->createNonSeekableResource('test content');

        try {
            $source = new ResourceSource($stream);

            Assert::same($source->content, 'test content');
            Assert::same($source->content, 'test content');
        } finally {
            \fclose($stream);
        }
    }

    public function testNonSeekableStreamContentIsUnavailableAfterAPartOfItHasBeenTaken(): void
    {
        $stream = $this->createNonSeekableResource('test content');

        try {
            $source = new ResourceSource($stream);

            Assert::same($source->read(0, 4), 'test');

            Expect::exception(StreamNotRewindableException::class);

            $source->content;
        } finally {
            \fclose($stream);
        }
    }

    public function testNonSeekableStreamIsReadInAnArbitraryOrderOnceTakenOver(): void
    {
        $stream = $this->createNonSeekableResource('test content');

        try {
            $taken = new ResourceSource($stream)->toSeekableSource();

            Assert::same($taken->content, 'test content');
            Assert::same($taken->read(0, 4), 'test');
            Assert::same($taken->read(5, 1024), 'content');
            Assert::same($taken->read(0, 4), 'test');
        } finally {
            \fclose($stream);
        }
    }

    public function testTakenOverNonSeekableStreamIsNoLongerReadByOffset(): void
    {
        $stream = $this->createNonSeekableResource('test content');

        try {
            $source = new ResourceSource($stream);
            $source->toSeekableSource();

            Assert::same($source->content, 'test content');

            Expect::exception(StreamNotRewindableException::class);

            $source->read(0, 4);
        } finally {
            \fclose($stream);
        }
    }

    public function testTakenOverSeekableStreamIsStillReadByOffset(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');
        \rewind($stream);

        $source = new ResourceSource($stream);
        $source->toSeekableSource();

        Assert::same($source->read(0, 4), 'test');
        Assert::same($source->read(4, 1024), ' content');
    }

    public function testTakenOverStreamWithoutUriBecomesAStringSource(): void
    {
        $stream = $this->createNonSeekableResource('test content');

        try {
            Assert::instanceOf(new ResourceSource($stream)->toSeekableSource(), StringSource::class);
        } finally {
            \fclose($stream);
        }
    }

    public function testTakenOverStreamWithUriBecomesAVirtualSource(): void
    {
        \file_put_contents($this->temp, 'test content');

        $stream = \fopen($this->temp, 'rb');

        try {
            $taken = new ResourceSource($stream)->toSeekableSource();

            Assert::instanceOf($taken, VirtualSource::class);
            Assert::same($taken->pathname, $this->temp);
            Assert::same($taken->content, 'test content');
        } finally {
            \fclose($stream);
        }
    }

    public function testFailsInCaseOfNonPositiveReadSize(): void
    {
        $source = new ResourceSource(\fopen('php://memory', 'rb+'));

        Expect::exception(NonPositiveBytesCountException::class);

        $source->read(0, 0);
    }

    public function testFailsInCaseOfNegativeOffset(): void
    {
        $source = new ResourceSource(\fopen('php://memory', 'rb+'));

        Expect::exception(NegativeOffsetException::class);

        $source->read(-1, 1024);
    }

    public function testReadingKeepsTheResourceOpen(): void
    {
        $stream = \fopen('php://memory', 'rb+');
        \fwrite($stream, 'test content');

        $source = new ResourceSource($stream);
        $source->read(0, 1024);
        unset($source);

        Assert::true(\is_resource($stream));
    }

    public function testAutocloseIsDisabledByDefault(): void
    {
        $stream = \fopen('php://memory', 'rb+');

        $source = new ResourceSource($stream);
        unset($source);

        Assert::true(\is_resource($stream));
    }

    public function testAutocloseClosesTheResource(): void
    {
        $stream = \fopen('php://memory', 'rb+');

        $source = new ResourceSource($stream, autoclose: true);
        unset($source);

        Assert::same(\get_debug_type($stream), 'resource (closed)');
    }

    public function testFailsInCaseOfClosedResource(): void
    {
        $stream = \fopen('php://memory', 'rb+');

        $source = new ResourceSource($stream);

        \fclose($stream);

        Expect::exception(ClosedStreamException::class)->withMessageContaining('closed from the outside');

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
            Assert::fail('Reading $content did not report the closed resource');
        } catch (ClosedStreamException $e) {
            Assert::string($e->getMessage())->contains('closed from the outside');
        }

        Expect::exception(ClosedStreamException::class);

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

            Assert::instanceOf($unserialized, ResourceSource::class);
            Assert::same($unserialized->uri, $this->temp);

            Assert::same($unserialized->content, 't content');
            Assert::same($unserialized->read(0, 4), 't co');
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

            Assert::same($source->read(0, 4), 'test');

            $unserialized = \unserialize(\serialize($source));

            Assert::instanceOf($unserialized, ResourceSource::class);
            Assert::same($unserialized->content, 'test content');
            Assert::same($unserialized->read(4, 1024), ' content');
        } finally {
            \fclose($stream);
        }
    }

    public function testSerializationFailsWithoutUri(): void
    {
        $stream = $this->createNonSeekableResource('test content');

        try {
            $source = new ResourceSource($stream);

            Expect::exception(StreamNotSerializableException::class);

            \serialize($source);
        } finally {
            \fclose($stream);
        }
    }
}
