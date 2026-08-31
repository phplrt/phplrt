<?php

declare(strict_types=1);

namespace Phplrt\Exception\Tests;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Exception\Analysis\FailureInterval;
use Phplrt\Exception\Analysis\FailureResult;
use Phplrt\Exception\Snippet\CapturedSourceLine;
use Phplrt\Exception\SnippetReader;
use Phplrt\Position\Position;
use Phplrt\Source\FileSource;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/exception')]
#[Test]
final class SnippetFragmentTest extends TestCase
{
    private const SOURCE = "line 1\nline 2\nline 3\nline 4\nline 5\nline 6\nline 7";

    public function testReadsLinesAroundTheCapturedOne(): void
    {
        Assert::same(self::describe(self::readString(code: self::SOURCE, offset: 23, lines: 2)), [
            ' #2@7: line 2',
            ' #3@14: line 3',
            '>#4@21:2-2: line 4',
            ' #5@28: line 5',
            ' #6@35: line 6',
        ]);
    }

    public function testReadsDoubledLinesCountAndTheCapturedOne(): void
    {
        for ($lines = 0; $lines <= 3; ++$lines) {
            Assert::count(self::readString(self::SOURCE, 23, 0, $lines), \min($lines * 2 + 1, 7));
        }
    }

    public function testReadsOnlyTheCapturedLine(): void
    {
        Assert::same(self::describe(self::readString(self::SOURCE, 23, 0, 0)), [
            '>#4@21:2-2: line 4',
        ]);
    }

    public function testResultIsIndexedByLineNumbers(): void
    {
        Assert::same(\array_keys(self::readString(self::SOURCE, 23, 0, 2)), [2, 3, 4, 5, 6]);
    }

    public function testResultContainsExactlyOneCapturedLine(): void
    {
        Assert::same(self::getCapturedLineNumbers(self::readString(self::SOURCE, 23, 3, 2)), [4]);
    }

    public function testLinesMatchTheSource(): void
    {
        for ($offset = 0, $length = \strlen(self::SOURCE); $offset <= $length; ++$offset) {
            self::assertLinesMatchSource(self::SOURCE, self::readString(self::SOURCE, $offset, 9, 2));
        }
    }

    public function testIsEquivalentToTheSourceSplitting(): void
    {
        \mt_srand(0xDEAD_BEEF);

        for ($i = 0; $i < 200; ++$i) {
            $source = self::createRandomSource();

            for ($offset = -1, $size = \strlen($source); $offset <= $size + 1; ++$offset) {
                foreach ([0, 1, 4, 9, 32] as $length) {
                    Assert::same(self::describe(self::readString($source, $offset, $length, 2)), self::split($source, $offset, $length, 2), \sprintf(
                        'Invalid snippet of the %s source at offset %d of length %d',
                        \var_export($source, true),
                        $offset,
                        $length,
                    ));
                }
            }
        }
    }

    public function testOmitsLinesBeforeTheBeginningOfSource(): void
    {
        Assert::same(self::describe(self::readString(self::SOURCE, 3, 0, 2)), [
            '>#1@0:3-3: line 1',
            ' #2@7: line 2',
            ' #3@14: line 3',
        ]);
    }

    public function testOmitsLinesAfterTheEndOfSource(): void
    {
        Assert::same(self::describe(self::readString(self::SOURCE, 45, 0, 2)), [
            ' #5@28: line 5',
            ' #6@35: line 6',
            '>#7@42:3-3: line 7',
        ]);
    }

    public function testReadsSingleLineSource(): void
    {
        Assert::same(self::describe(self::readString('line 1', 3, 0, 2)), [
            '>#1@0:3-3: line 1',
        ]);
    }

    public function testReadsEmptySource(): void
    {
        $actual = self::readString('', 0, 0, 2);

        Assert::count($actual, 1);
        Assert::instanceOf($actual[1], CapturedSourceLine::class);
        Assert::same($actual[1]->number, 1);
        Assert::same($actual[1]->offset, 0);
        Assert::same($actual[1]->value, '');
        Assert::same($actual[1]->captured->offset, 0);
        Assert::same($actual[1]->captured->length, 0);
    }

    public function testTrailingDelimiterProducesAnEmptyLastLine(): void
    {
        Assert::same(self::describe(self::readString("line 1\n", 7, 0, 2)), [
            ' #1@0: line 1',
            '>#2@7:0-0: ',
        ]);
    }

    #[DataProvider('delimitersDataProvider')]
    public function testSupportedDelimiters(string $delimiter): void
    {
        $source = \implode($delimiter, ['line 1', 'line 2', 'line 3']);
        $size = 6 + \strlen($delimiter);

        Assert::same(self::describe(self::readString($source, $size * 2, 0, 2)), [
            \sprintf(' #1@%d: line 1', 0),
            \sprintf(' #2@%d: line 2', $size),
            \sprintf('>#3@%d:0-0: line 3', $size * 2),
        ]);
    }

    public static function delimitersDataProvider(): iterable
    {
        yield 'LF' => ["\n"];
        yield 'CRLF' => ["\r\n"];
    }

    public function testCapturesEveryLineOfTheFragment(): void
    {
        Assert::same(self::describe(self::readString(self::SOURCE, 23, 15, 2)), [
            ' #2@7: line 2',
            ' #3@14: line 3',
            '>#4@21:2-6: line 4',
            '>#5@28:0-6: line 5',
            '>#6@35:0-3: line 6',
            ' #7@42: line 7',
        ]);
    }

    public function testReadsLinesAfterTheEndOfFragment(): void
    {
        Assert::same(self::describe(self::readString(self::SOURCE, 2, 15, 1)), [
            '>#1@0:2-6: line 1',
            '>#2@7:0-6: line 2',
            '>#3@14:0-3: line 3',
            ' #4@21: line 4',
        ]);
    }

    public function testFragmentEndingAtTheBeginningOfLineDoesNotCaptureIt(): void
    {
        Assert::same(self::describe(self::readString(self::SOURCE, 21, 7, 0)), [
            '>#4@21:0-6: line 4',
        ]);

        Assert::same(self::describe(self::readString(self::SOURCE, 21, 8, 0)), [
            '>#4@21:0-6: line 4',
            '>#5@28:0-1: line 5',
        ]);
    }

    public function testZeroLengthFragmentIsCapturedBySingleLine(): void
    {
        for ($offset = 0, $size = \strlen(self::SOURCE); $offset <= $size; ++$offset) {
            $actual = self::readString(self::SOURCE, $offset, 0, 2);

            Assert::count(self::getCapturedLineNumbers($actual), 1);

            foreach ($actual as $line) {
                if ($line instanceof CapturedSourceLine) {
                    Assert::same($line->captured->length, 0);
                }
            }
        }
    }

    public function testCapturedIntervalPointsToTheFragmentBoundaries(): void
    {
        $source = "line 1\nline 2";

        foreach (self::readString($source, 2, 8, 0) as $line) {
            Assert::instanceOf($line, CapturedSourceLine::class);

            $captured = \substr($line->value, $line->captured->offset, $line->captured->length);

            Assert::same($captured, $line->number === 1 ? 'ne 1' : 'lin');
        }
    }

    public function testCapturedIntervalStartsAtTheCapturedOffset(): void
    {
        for ($offset = 0, $length = \strlen(self::SOURCE); $offset < $length; ++$offset) {
            $line = self::findFirstCapturedLine(self::readString(self::SOURCE, $offset, 0, 2));

            Assert::same(self::SOURCE[$line->offset + $line->captured->offset], self::SOURCE[$offset], \sprintf('The interval of the offset %d is expected to start at the same character', $offset));
        }
    }

    public function testOffsetInsideDelimiterBelongsToThePrecedingLine(): void
    {
        $line = self::findFirstCapturedLine(self::readString(self::SOURCE, 6, 0, 0));

        Assert::same($line->number, 1);
        Assert::same($line->captured->offset, 6);
    }

    public function testCapturedOffsetNeverExceedsTheLengthOfTheLine(): void
    {
        $line = self::findFirstCapturedLine(self::readString("line 1\r\nline 2", 7, 0, 0));

        Assert::same($line->number, 1);
        Assert::same($line->captured->offset, 6);
        Assert::same($line->captured->length, 0);
    }

    public function testNegativeOffsetIsReducedToTheBeginningOfSource(): void
    {
        $line = self::findFirstCapturedLine(self::readString(self::SOURCE, -42, 0, 0));

        Assert::same($line->number, 1);
        Assert::same($line->captured->offset, 0);
    }

    public function testOverflowedOffsetIsReducedToTheEndOfSource(): void
    {
        $line = self::findFirstCapturedLine(self::readString(self::SOURCE, 42_000, 0, 0));

        Assert::same($line->number, 7);
        Assert::same($line->captured->offset, 6);
    }

    public function testNegativeLengthIsReducedToAnEmptyFragment(): void
    {
        Assert::same(self::describe(self::readString(self::SOURCE, 23, -42, 0)), self::describe(self::readString(self::SOURCE, 23, 0, 0)));
    }

    public function testOverflowedLengthIsReducedToTheEndOfSource(): void
    {
        Assert::same(self::describe(self::readString(self::SOURCE, 42, 42_000, 0)), self::describe(self::readString(self::SOURCE, 42, 6, 0)));
    }

    public function testOverflowedLengthCapturesTheTrailingEmptyLine(): void
    {
        Assert::same(self::describe(self::readString("line 1\n", 0, 42_000, 0)), [
            '>#1@0:0-6: line 1',
            '>#2@7:0-0: ',
        ]);
    }

    public function testReadsFile(): void
    {
        $pathname = self::createSourceFile(self::SOURCE);

        try {
            Assert::same(self::describe(self::readFile($pathname, 23, 15, 2)), self::describe(self::readString(self::SOURCE, 23, 15, 2)));
        } finally {
            @\unlink($pathname);
        }
    }

    public function testReadsNonExistentFile(): void
    {
        Expect::exception(SourceExceptionInterface::class);

        self::readFile(__DIR__ . '/non-existent-file.txt', 0);
    }

    public function testReadsDirectory(): void
    {
        Expect::exception(SourceExceptionInterface::class);

        self::readFile(__DIR__, 0);
    }

    private static function readString(
        string $code,
        int $offset,
        int $length = 0,
        int $lines = SnippetReader::DEFAULT_LINES_AROUND,
    ): array {
        return self::read(new StringSource($code), $offset, $length, $lines);
    }

    private static function readFile(
        string $pathname,
        int $offset,
        int $length = 0,
        int $lines = SnippetReader::DEFAULT_LINES_AROUND,
    ): array {
        return self::read(new FileSource($pathname), $offset, $length, $lines);
    }

    private static function read(ReadableInterface $source, int $offset, int $length, int $lines): array
    {
        return (new SnippetReader())->read(new FailureResult(
            class: '',
            message: '',
            source: $source,
            position: new Position(),
            interval: new FailureInterval($offset, $length),
        ), $lines);
    }

    private static function getCapturedLineNumbers(iterable $lines): array
    {
        $result = [];

        foreach ($lines as $line) {
            if ($line instanceof CapturedSourceLine) {
                $result[] = $line->number;
            }
        }

        return $result;
    }

    private static function findFirstCapturedLine(iterable $lines): CapturedSourceLine
    {
        foreach ($lines as $line) {
            if ($line instanceof CapturedSourceLine) {
                return $line;
            }
        }

        Assert::fail('The result is expected to contain a captured line');
    }

    private static function createRandomSource(): string
    {
        $alphabet = ['a', 'bb', 'ccc', "\n", "\r\n", "\n\r", "\r", ' '];

        $result = '';

        for ($i = 0, $size = \mt_rand(0, 16); $i < $size; ++$i) {
            $result .= $alphabet[\mt_rand(0, \count($alphabet) - 1)];
        }

        return $result;
    }

    private static function split(string $code, int $offset, int $length, int $lines): array
    {
        $size = \strlen($code);
        $offset = \max(0, \min($offset, $size));
        $end = $offset + \max(0, $length);

        $parts = \preg_split('/\r\n|\n/', $code, -1, \PREG_SPLIT_OFFSET_CAPTURE);

        if ($parts === false) {
            Assert::fail('Unable to split the source code');
        }

        $first = 0;

        foreach ($parts as $index => [, $start]) {
            if ($start <= $offset) {
                $first = $index;
            }
        }

        $last = $first;

        foreach ($parts as $index => [, $start]) {
            if ($index > $first && $start < $end) {
                $last = $index;
            }
        }

        $result = [];

        for ($i = \max(0, $first - $lines), $to = \min(\count($parts) - 1, $last + $lines); $i <= $to; ++$i) {
            [$value, $start] = $parts[$i];

            $result[] = $i >= $first && $i <= $last
                ? \sprintf(
                    '>#%d@%d:%d-%d: %s',
                    $i + 1,
                    $start,
                    self::calculateOffset($offset, $start, $value),
                    self::calculateOffset($end, $start, $value),
                    $value,
                )
                : \sprintf(' #%d@%d: %s', $i + 1, $start, $value);
        }

        return $result;
    }

    private static function calculateOffset(int $offset, int $start, string $value): int
    {
        return \max(0, \min($offset - $start, \strlen($value)));
    }

    private static function createSourceFile(string $content): string
    {
        $pathname = \tempnam(\sys_get_temp_dir(), 'phplrt-snippet-');

        if ($pathname === false || \file_put_contents($pathname, $content) === false) {
            Assert::fail('Unable to create a temporary source file');
        }

        return $pathname;
    }
}
