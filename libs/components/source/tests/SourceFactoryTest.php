<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\Driver\SourceDriverInterface;
use Phplrt\Source\Driver\SplFileInfoSourceDriver;
use Phplrt\Source\Driver\ResourceSourceDriver;
use Phplrt\Source\Driver\StringSourceDriver;
use Phplrt\Source\Exception\NotCreatableException;
use Phplrt\Source\FileSource;
use Phplrt\Source\StringSource;
use Phplrt\Source\SourceFactory;
use Phplrt\Source\ResourceSource;

final class SourceFactoryTest extends TestCase
{
    public function testCreatesSourceFromString(): void
    {
        $source = SourceFactory::createDefault()
            ->create('2 + 2');

        self::assertInstanceOf(StringSource::class, $source);
        self::assertSame('2 + 2', $source->content);
    }

    public function testCreatesFileFromSplFileInfo(): void
    {
        \file_put_contents($this->temp, '2 + 2');

        $source = SourceFactory::createDefault()
            ->create(new \SplFileInfo($this->temp));

        self::assertInstanceOf(FileSource::class, $source);
        self::assertSame($this->temp, $source->pathname);
        self::assertSame('2 + 2', $source->content);
    }

    public function testCreatesStreamFromResource(): void
    {
        $resource = \fopen('php://memory', 'rb+');
        \fwrite($resource, 'test content');
        \rewind($resource);

        $source = SourceFactory::createDefault()
            ->create($resource);

        self::assertInstanceOf(ResourceSource::class, $source);
        self::assertSame('test content', $source->content);
    }

    public function testPassesReadableThrough(): void
    {
        $expected = new StringSource('2 + 2');

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
        $expected = new StringSource('2 + 2');

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
        $expected = new StringSource('overridden');

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
            new ResourceSourceDriver(),
            new StringSourceDriver(),
        ]);

        self::assertInstanceOf(StringSource::class, $factory->create('2 + 2'));
    }

    public function testAcceptsDriversFromTraversable(): void
    {
        $factory = new SourceFactory(new \ArrayIterator([
            new ResourceSourceDriver(),
            new StringSourceDriver(),
        ]));

        self::assertInstanceOf(StringSource::class, $factory->create('2 + 2'));
    }

    public function testIgnoresDriverListKeys(): void
    {
        $factory = new SourceFactory([
            'resource' => new ResourceSourceDriver(),
            'string' => new StringSourceDriver(),
        ]);

        self::assertInstanceOf(StringSource::class, $factory->create('2 + 2'));
    }
}
