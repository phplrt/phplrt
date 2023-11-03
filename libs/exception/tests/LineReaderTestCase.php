<?php

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

    private function create(array $lines, string $delimiter = "\n"): LineReader
    {
        return new LineReader(File::fromSources(\implode($delimiter, $lines)));
    }

    /**
     * @dataProvider dataProvider
     */
    public function testReadLine(LineReader $reader, int $max): void
    {
        $this->assertSame('line-1', $reader->readLine(1));
        $this->assertSame('line-' . $max, $reader->readLine($max));
    }

    /**
     * @dataProvider dataProvider
     */
    public function testReadLineUnderflow(LineReader $reader): void
    {
        $this->assertSame('', $reader->readLine(0));
        $this->assertSame('', $reader->readLine(-1));
        $this->assertSame('', $reader->readLine(\PHP_INT_MIN));
    }

    /**
     * @dataProvider dataProvider
     */
    public function testReadLineOverflow(LineReader $reader, int $max): void
    {
        $this->assertSame('', $reader->readLine($max + 1));
        $this->assertSame('', $reader->readLine(\PHP_INT_MAX));
    }

    /**
     * @dataProvider dataProvider
     */
    public function testReadLines(LineReader $reader): void
    {
        $this->assertEquals(['line-1', 'line-2'], [...$reader->readLines(1, 2)]);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testReadLinesInReverseOrder(LineReader $reader): void
    {
        $this->assertEquals(['line-1', 'line-2'], [...$reader->readLines(2, 1)]);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testReadLinesUnderflow(LineReader $reader): void
    {
        $this->assertEquals(['line-1', 'line-2', 'line-3'], [...$reader->readLines(-1, 3)]);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testReadLinesOverflow(LineReader $reader, int $max): void
    {
        $haystack = ['line-' . ($max - 2), 'line-' . ($max - 1), 'line-' . $max];

        $this->assertEquals($haystack, [...$reader->readLines($max - 2, $max + 2)]);
    }
}
