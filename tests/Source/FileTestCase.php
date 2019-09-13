<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Tests\Source;

use Phplrt\Source\FileInterface;
use Phplrt\Source\ReadableInterface;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\SkippedTestError;

/**
 * Class FactoryTestCase
 */
class FileTestCase extends TestCase
{
    /**
     * @dataProvider provider
     * @param \Closure $factory
     * @throws ExpectationFailedException
     */
    public function testSources(\Closure $factory): void
    {
        $readable = $factory();

        $this->assertSame($this->getSources(), $readable->getContents());
        $this->assertSame($this->getSources(), (clone $readable)->getContents());
        $this->assertSame($this->getSources(), \unserialize(\serialize($readable))->getContents());
    }

    /**
     * @dataProvider provider
     * @param \Closure $factory
     * @throws ExpectationFailedException
     * @throws SkippedTestError
     */
    public function testPathname(\Closure $factory): void
    {
        /** @var ReadableInterface $readable */
        $readable = $factory();

        if (! $readable instanceof FileInterface) {
            $this->markTestSkipped('Test cannot be performed for a source that is not a file');
            return;
        }

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
