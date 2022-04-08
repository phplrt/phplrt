<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Exception\Tests;

use Phplrt\Exception\LineReader;
use Phplrt\Source\File;

class LineReaderTestCase extends TestCase
{
    /**
     * @var string
     */
    private const MAX_LINES_PER_TEST = 100;

    /**
     * @return array
     */
    public function dataProvider(): array
    {
        $lines = [];

        foreach (\range(1, self::MAX_LINES_PER_TEST) as $line) {
            $lines[] = 'line-' . $line;
        }

        return [
            '\n'   => [$this->create($lines, "\n"), self::MAX_LINES_PER_TEST],
            '\r\n' => [$this->create($lines, "\r\n"), self::MAX_LINES_PER_TEST],
        ];
    }

    /**
     * @param array $lines
     * @param string $delimiter
     * @return LineReader
     */
    private function create(array $lines, string $delimiter = "\n"): LineReader
    {
        return new LineReader(File::fromSources(\implode($delimiter, $lines)));
    }

    /**
     * @dataProvider dataProvider
     *
     * @param LineReader $reader
     * @param int $max
     * @return void
     */
    public function testReadLine(LineReader $reader, int $max): void
    {
        $this->assertSame('line-1', $reader->readLine(1));
        $this->assertSame('line-' . $max, $reader->readLine($max));
    }

    /**
     * @dataProvider dataProvider
     *
     * @param LineReader $reader
     * @return void
     */
    public function testReadLineUnderflow(LineReader $reader): void
    {
        $this->assertSame('', $reader->readLine(0));
        $this->assertSame('', $reader->readLine(-1));
        $this->assertSame('', $reader->readLine(\PHP_INT_MIN));
    }

    /**
     * @dataProvider dataProvider
     *
     * @param LineReader $reader
     * @param int $max
     * @return void
     */
    public function testReadLineOverflow(LineReader $reader, int $max): void
    {
        $this->assertSame('', $reader->readLine($max + 1));
        $this->assertSame('', $reader->readLine(\PHP_INT_MAX));
    }

    /**
     * @dataProvider dataProvider
     *
     * @param LineReader $reader
     * @return void
     */
    public function testReadLines(LineReader $reader): void
    {
        $this->assertEquals(['line-1', 'line-2'], [...$reader->readLines(1, 2)]);
    }

    /**
     * @dataProvider dataProvider
     *
     * @param LineReader $reader
     * @return void
     */
    public function testReadLinesInReverseOrder(LineReader $reader): void
    {
        $this->assertEquals(['line-1', 'line-2'], [...$reader->readLines(2, 1)]);
    }

    /**
     * @dataProvider dataProvider
     *
     * @param LineReader $reader
     * @return void
     */
    public function testReadLinesUnderflow(LineReader $reader): void
    {
        $this->assertEquals(['line-1', 'line-2', 'line-3'], [...$reader->readLines(-1, 3)]);
    }

    /**
     * @dataProvider dataProvider
     *
     * @param LineReader $reader
     * @param int $max
     * @return void
     */
    public function testReadLinesOverflow(LineReader $reader, int $max): void
    {
        $haystack = ['line-' . ($max - 2), 'line-' . ($max - 1), 'line-' . $max];

        $this->assertEquals($haystack, [...$reader->readLines($max - 2, $max + 2)]);
    }
}
