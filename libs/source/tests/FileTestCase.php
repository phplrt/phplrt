<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use PHPUnit\Framework\SkippedTestError;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use PHPUnit\Framework\ExpectationFailedException;

class FileTestCase extends TestCase
{
    /**
     * @dataProvider provider
     *
     * @param \Closure $factory
     * @throws ExpectationFailedException
     */
    public function testSources(\Closure $factory): void
    {
        $readable = $factory();

        $this->assertSame($this->getSources(), $readable->getContents());
    }

    /**
     * @dataProvider provider
     *
     * @param \Closure $factory
     * @throws ExpectationFailedException
     */
    public function testCloneable(\Closure $factory): void
    {
        $readable = $factory();

        $this->assertSame($this->getSources(), (clone $readable)->getContents());
    }

    /**
     * @dataProvider provider
     *
     * @param \Closure $factory
     * @return void
     */
    public function testSerializable(\Closure $factory): void
    {
        $readable = $factory();

        $this->assertSame($this->getSources(), \unserialize(\serialize($readable))->getContents());
    }

    /**
     * @return array
     */
    public function filesDataProvider(): array
    {
        $filter = fn(array $cb) => $cb[0]() instanceof FileInterface;

        return \array_filter($this->provider(), $filter);
    }

    /**
     * @dataProvider filesDataProvider
     *
     * @param \Closure $factory
     * @throws ExpectationFailedException
     * @throws SkippedTestError
     */
    public function testPathname(\Closure $factory): void
    {
        /** @var ReadableInterface $readable */
        $readable = $factory();

        $path = $readable->getPathname();

        $this->assertSame($path, $readable->getPathname());
        $this->assertSame($path, (clone $readable)->getPathname());
        $this->assertSame($path, \unserialize(\serialize($readable))->getPathname());
    }

    /**
     * @return string
     */
    public function getPathname(): string
    {
        return __FILE__;
    }
}
