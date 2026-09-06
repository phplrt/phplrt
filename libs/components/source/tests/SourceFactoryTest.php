<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\Driver\ResourceSourceDriver;
use Phplrt\Source\Driver\SourceDriverInterface;
use Phplrt\Source\Driver\SplFileInfoSourceDriver;
use Phplrt\Source\Driver\StringSourceDriver;
use Phplrt\Source\Exception\NotCreatableException;
use Phplrt\Source\FileSource;
use Phplrt\Source\ResourceSource;
use Phplrt\Source\SourceFactory;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class SourceFactoryTest extends TestCase
{
    public function testCreatesSourceFromString(): void
    {
        $source = SourceFactory::createDefault()
            ->create('2 + 2');

        Assert::instanceOf($source, StringSource::class);
        Assert::same($source->content, '2 + 2');
    }

    public function testCreatesFileFromSplFileInfo(): void
    {
        \file_put_contents($this->temp, '2 + 2');

        $source = SourceFactory::createDefault()
            ->create(new \SplFileInfo($this->temp));

        Assert::instanceOf($source, FileSource::class);
        Assert::same($source->pathname, $this->temp);
        Assert::same($source->content, '2 + 2');
    }

    public function testCreatesStreamFromResource(): void
    {
        $resource = \fopen('php://memory', 'rb+');
        \fwrite($resource, 'test content');
        \rewind($resource);

        $source = SourceFactory::createDefault()
            ->create($resource);

        Assert::instanceOf($source, ResourceSource::class);
        Assert::same($source->content, 'test content');
    }

    public function testPassesReadableThrough(): void
    {
        $expected = new StringSource('2 + 2');

        $actual = SourceFactory::createDefault()
            ->create($expected);

        Assert::same($actual, $expected);
    }

    public function testFailsInCaseOfUnsupportedSource(): void
    {
        Expect::exception(NotCreatableException::class)->withMessageContaining('from int type');

        SourceFactory::createDefault()
            ->create(42);
    }

    public function testFailsInCaseOfNoDrivers(): void
    {
        Expect::exception(NotCreatableException::class);

        (new SourceFactory())
            ->create('2 + 2');
    }

    public function testPassesReadableThroughWithoutAnyDrivers(): void
    {
        $expected = new StringSource('2 + 2');

        Assert::same((new SourceFactory())->create($expected), $expected);
    }

    public function testFailsInCaseOfNonStreamResource(): void
    {
        Expect::exception(NotCreatableException::class)->withMessageContaining('from non-stream resource type');

        SourceFactory::createDefault()
            ->create(\stream_context_create());
    }

    public function testFailsInCaseOfEmptyPathname(): void
    {
        Expect::exception(NotCreatableException::class)->withMessageContaining('from empty pathname type');

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

        Assert::same($factory->create('2 + 2'), $expected);
    }

    public function testSkipsDriversThatDoNotRecognizeTheSource(): void
    {
        $factory = new SourceFactory([
            new SplFileInfoSourceDriver(),
            new ResourceSourceDriver(),
            new StringSourceDriver(),
        ]);

        Assert::instanceOf($factory->create('2 + 2'), StringSource::class);
    }

    public function testAcceptsDriversFromTraversable(): void
    {
        $factory = new SourceFactory(new \ArrayIterator([
            new ResourceSourceDriver(),
            new StringSourceDriver(),
        ]));

        Assert::instanceOf($factory->create('2 + 2'), StringSource::class);
    }

    public function testIgnoresDriverListKeys(): void
    {
        $factory = new SourceFactory([
            'resource' => new ResourceSourceDriver(),
            'string' => new StringSourceDriver(),
        ]);

        Assert::instanceOf($factory->create('2 + 2'), StringSource::class);
    }
}
