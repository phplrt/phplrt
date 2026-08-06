<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\Driver\SourceDriverInterface;
use Phplrt\Source\Driver\SplFileInfoSourceDriver;
use Phplrt\Source\Driver\StreamSourceDriver;
use Phplrt\Source\Driver\StringSourceDriver;
use Phplrt\Source\Exception\NotCreatableException;
use Phplrt\Source\File;
use Phplrt\Source\Source;
use Phplrt\Source\SourceFactory;
use Phplrt\Source\Stream;

final class SourceFactoryTest extends TestCase
{
    public function testCreatesSourceFromString(): void
    {
        $source = SourceFactory::createDefault()
            ->create('2 + 2');

        self::assertInstanceOf(Source::class, $source);
        self::assertSame('2 + 2', $source->content);
    }

    public function testCreatesFileFromSplFileInfo(): void
    {
        \file_put_contents($this->temp, '2 + 2');

        $source = SourceFactory::createDefault()
            ->create(new \SplFileInfo($this->temp));

        self::assertInstanceOf(File::class, $source);
        self::assertSame($this->temp, $source->pathname);
        self::assertSame('2 + 2', $source->content);
    }

    public function testCreatesStreamFromResource(): void
    {
        $resource = \fopen('php://memory', 'rb+');

        $source = SourceFactory::createDefault()
            ->create($resource);

        self::assertInstanceOf(Stream::class, $source);
        self::assertSame($resource, $source->stream);
    }

    public function testPassesReadableThrough(): void
    {
        $expected = new Source('2 + 2');

        $actual = SourceFactory::createDefault()
            ->create($expected);

        self::assertSame($expected, $actual);
    }

    public function testFailsInCaseOfUnsupportedSource(): void
    {
        $this->expectException(NotCreatableException::class);
        $this->expectExceptionMessage('from int type');

        SourceFactory::createDefault()
            ->create(42);
    }

    public function testFailsInCaseOfNoDrivers(): void
    {
        $this->expectException(NotCreatableException::class);

        new SourceFactory()
            ->create('2 + 2');
    }

    public function testPassesReadableThroughWithoutAnyDrivers(): void
    {
        $expected = new Source('2 + 2');

        self::assertSame($expected, new SourceFactory()->create($expected));
    }

    public function testFailsInCaseOfNonStreamResource(): void
    {
        $this->expectException(NotCreatableException::class);
        $this->expectExceptionMessage('from non-stream resource type');

        SourceFactory::createDefault()
            ->create(\stream_context_create());
    }

    public function testFailsInCaseOfEmptyPathname(): void
    {
        $this->expectException(NotCreatableException::class);
        $this->expectExceptionMessage('from empty pathname type');

        SourceFactory::createDefault()
            ->create(new \SplFileInfo(''));
    }

    public function testTheFirstMatchingDriverWins(): void
    {
        $expected = new Source('overridden');

        $factory = new SourceFactory([
            new class ($expected) implements SourceDriverInterface {
                public function __construct(
                    private readonly ReadableInterface $result,
                ) {}

                public function tryCreate(mixed $source): ?ReadableInterface
                {
                    return \is_string($source) ? $this->result : null;
                }
            },
            new StringSourceDriver(),
        ]);

        self::assertSame($expected, $factory->create('2 + 2'));
    }

    public function testSkipsDriversThatDoNotRecognizeTheSource(): void
    {
        $factory = new SourceFactory([
            new SplFileInfoSourceDriver(),
            new StreamSourceDriver(),
            new StringSourceDriver(),
        ]);

        self::assertInstanceOf(Source::class, $factory->create('2 + 2'));
    }

    public function testAcceptsDriversFromTraversable(): void
    {
        $factory = new SourceFactory(new \ArrayIterator([
            new StreamSourceDriver(),
            new StringSourceDriver(),
        ]));

        self::assertInstanceOf(Source::class, $factory->create('2 + 2'));
    }

    public function testIgnoresDriverListKeys(): void
    {
        $factory = new SourceFactory([
            'stream' => new StreamSourceDriver(),
            'string' => new StringSourceDriver(),
        ]);

        self::assertInstanceOf(Source::class, $factory->create('2 + 2'));
    }
}
