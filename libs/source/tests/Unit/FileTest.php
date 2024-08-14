<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests\Unit;

use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use PHPUnit\Framework\Attributes\DataProvider;

class FileTest extends TestCase
{
    #[DataProvider('provider')]
    public function testSources(\Closure $factory): void
    {
        $readable = $factory();

        $this->assertSame($this->getSources(), $readable->getContents());
    }

    #[DataProvider('provider')]
    public function testCloneable(\Closure $factory): void
    {
        $readable = $factory();

        $this->assertSame($this->getSources(), (clone $readable)->getContents());
    }

    #[DataProvider('provider')]
    public function testSerializable(\Closure $factory): void
    {
        $readable = $factory();

        $unserialized = \unserialize(\serialize($readable));

        $this->assertSame($this->getSources(), $unserialized->getContents());
    }

    public static function filesDataProvider(): array
    {
        $filter = fn(array $cb) => $cb[0]() instanceof FileInterface;

        return \array_filter(static::provider(), $filter);
    }

    #[DataProvider('filesDataProvider')]
    public function testPathname(\Closure $factory): void
    {
        /** @var ReadableInterface $readable */
        $readable = $factory();

        $path = $readable->getPathname();

        $this->assertSame($path, $readable->getPathname());
        $this->assertSame($path, (clone $readable)->getPathname());
        $this->assertSame($path, \unserialize(\serialize($readable))->getPathname());
    }

    public static function getPathname(): string
    {
        return __FILE__;
    }
}
