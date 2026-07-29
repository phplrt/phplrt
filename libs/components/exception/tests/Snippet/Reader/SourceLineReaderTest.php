<?php

declare(strict_types=1);

namespace Phplrt\Exception\Tests\Snippet\Reader;

use Phplrt\Exception\ErrorInfoResult;
use Phplrt\Exception\Snippet\CapturedSourceLine;
use Phplrt\Exception\Snippet\Exception\SourceNotReadableException;
use Phplrt\Exception\Snippet\Reader\Content\FileContent;
use Phplrt\Exception\Snippet\Reader\Content\StringContent;
use Phplrt\Exception\Snippet\Reader\SourceLineReader;
use Phplrt\Exception\Snippet\SourceLine;
use Phplrt\Exception\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/exception')]
final class SourceLineReaderTest extends TestCase
{
    /**
     * Each line of the source is 6 bytes long, so with a single-byte
     * delimiter the line number {@see $number} starts at offset
     * {@see $number} - 1 multiplied by 7.
     *
     * @var non-empty-string
     */
    private const string SOURCE = "line 1\nline 2\nline 3\nline 4\nline 5\nline 6\nline 7";

    #[TestDox('Reads the captured line along with N lines before and after it')]
    public function testReadsLinesAroundTheCapturedOne(): void
    {
        self::assertSame([
            ' #2@7: line 2',
            ' #3@14: line 3',
            '>#4@21:3-3: line 4',
            ' #5@28: line 5',
            ' #6@35: line 6',
        ], self::describe(self::readString(code: self::SOURCE, offset: 23, lines: 2)));
    }

    #[TestDox('Reads exactly N * 2 + 1 lines when the source is large enough')]
    public function testReadsDoubledLinesCountAndTheCapturedOne(): void
    {
        for ($lines = 0; $lines <= 3; ++$lines) {
            self::assertCount(\min($lines * 2 + 1, 7), self::readString(self::SOURCE, 23, 0, $lines));
        }
    }

    #[TestDox('Reads only the captured line in case no additional lines are requested')]
    public function testReadsOnlyTheCapturedLine(): void
    {
        self::assertSame([
            '>#4@21:3-3: line 4',
        ], self::describe(self::readString(self::SOURCE, 23, 0, 0)));
    }

    #[TestDox('The result is indexed by the line numbers')]
    public function testResultIsIndexedByLineNumbers(): void
    {
        self::assertSame([2, 3, 4, 5, 6], \array_keys(self::readString(self::SOURCE, 23, 0, 2)));
    }

    #[TestDox('The result contains exactly one captured line in case the fragment is located on a single line')]
    public function testResultContainsExactlyOneCapturedLine(): void
    {
        self::assertSame([4], self::getCapturedLineNumbers(self::readString(self::SOURCE, 23, 3, 2)));
    }

    #[TestDox('The lines are located at the specified offsets of the source')]
    public function testLinesMatchTheSource(): void
    {
        for ($offset = 0, $length = \strlen(self::SOURCE); $offset <= $length; ++$offset) {
            self::assertLinesMatchSource(self::SOURCE, self::readString(self::SOURCE, $offset, 9, 2));
        }
    }

    #[TestDox('Reading any fragment of any source is equivalent to splitting it by the delimiters')]
    public function testIsEquivalentToTheSourceSplitting(): void
    {
        \mt_srand(0xDEAD_BEEF);

        for ($i = 0; $i < 200; ++$i) {
            $source = self::createRandomSource();

            for ($offset = -1, $size = \strlen($source); $offset <= $size + 1; ++$offset) {
                foreach ([0, 1, 4, 9, 32] as $length) {
                    self::assertSame(
                        self::split($source, $offset, $length, 2),
                        self::describe(self::readString($source, $offset, $length, 2)),
                        \sprintf(
                            'Invalid snippet of the %s source at offset %d of length %d',
                            \var_export($source, true),
                            $offset,
                            $length,
                        ),
                    );
                }
            }
        }
    }

    #[TestDox('Non-existent lines before the beginning of the source are omitted')]
    public function testOmitsLinesBeforeTheBeginningOfSource(): void
    {
        self::assertSame([
            '>#1@0:4-4: line 1',
            ' #2@7: line 2',
            ' #3@14: line 3',
        ], self::describe(self::readString(self::SOURCE, 3, 0, 2)));
    }

    #[TestDox('Non-existent lines after the end of the source are omitted')]
    public function testOmitsLinesAfterTheEndOfSource(): void
    {
        self::assertSame([
            ' #5@28: line 5',
            ' #6@35: line 6',
            '>#7@42:4-4: line 7',
        ], self::describe(self::readString(self::SOURCE, 45, 0, 2)));
    }

    #[TestDox('The source containing a single line is read as the captured one')]
    public function testReadsSingleLineSource(): void
    {
        self::assertSame([
            '>#1@0:4-4: line 1',
        ], self::describe(self::readString('line 1', 3, 0, 2)));
    }

    #[TestDox('The empty source is read as a single empty captured line')]
    public function testReadsEmptySource(): void
    {
        $actual = self::readString('', 0, 0, 2);

        self::assertCount(1, $actual);
        self::assertInstanceOf(CapturedSourceLine::class, $actual[1]);
        self::assertSame(1, $actual[1]->number);
        self::assertSame(0, $actual[1]->offset);
        self::assertSame('', $actual[1]->value);
        self::assertSame(1, $actual[1]->startColumn);
        self::assertSame(0, $actual[1]->width);
    }

    #[TestDox('The trailing delimiter of the source produces an empty last line')]
    public function testTrailingDelimiterProducesAnEmptyLastLine(): void
    {
        self::assertSame([
            ' #1@0: line 1',
            '>#2@7:1-1: ',
        ], self::describe(self::readString("line 1\n", 7, 0, 2)));
    }

    /**
     * @param non-empty-string $delimiter
     */
    #[TestDox('The lines are separated by any of the supported delimiters')]
    #[DataProvider('delimitersDataProvider')]
    public function testSupportedDelimiters(string $delimiter): void
    {
        $source = \implode($delimiter, ['line 1', 'line 2', 'line 3']);
        $size = 6 + \strlen($delimiter);

        self::assertSame([
            \sprintf(' #1@%d: line 1', 0),
            \sprintf(' #2@%d: line 2', $size),
            \sprintf('>#3@%d:1-1: line 3', $size * 2),
        ], self::describe(self::readString($source, $size * 2, 0, 2)));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function delimitersDataProvider(): iterable
    {
        yield 'LF' => ["\n"];
        yield 'CRLF' => ["\r\n"];
    }

    #[TestDox('Every line of the captured fragment is returned as a captured one')]
    public function testCapturesEveryLineOfTheFragment(): void
    {
        self::assertSame([
            ' #2@7: line 2',
            ' #3@14: line 3',
            '>#4@21:3-7: line 4',
            '>#5@28:1-7: line 5',
            '>#6@35:1-4: line 6',
            ' #7@42: line 7',
        ], self::describe(self::readString(self::SOURCE, 23, 15, 2)));
    }

    #[TestDox('The lines after the captured ones are read from the end of the fragment')]
    public function testReadsLinesAfterTheEndOfFragment(): void
    {
        self::assertSame([
            '>#1@0:3-7: line 1',
            '>#2@7:1-7: line 2',
            '>#3@14:1-4: line 3',
            ' #4@21: line 4',
        ], self::describe(self::readString(self::SOURCE, 2, 15, 1)));
    }

    #[TestDox('A fragment ending at the beginning of a line does not capture this line')]
    public function testFragmentEndingAtTheBeginningOfLineDoesNotCaptureIt(): void
    {
        self::assertSame([
            '>#4@21:1-7: line 4',
        ], self::describe(self::readString(self::SOURCE, 21, 7, 0)));

        self::assertSame([
            '>#4@21:1-7: line 4',
            '>#5@28:1-2: line 5',
        ], self::describe(self::readString(self::SOURCE, 21, 8, 0)));
    }

    #[TestDox('A fragment of zero length is captured by a single line')]
    public function testZeroLengthFragmentIsCapturedBySingleLine(): void
    {
        for ($offset = 0, $size = \strlen(self::SOURCE); $offset <= $size; ++$offset) {
            $actual = self::readString(self::SOURCE, $offset, 0, 2);

            self::assertCount(1, self::getCapturedLineNumbers($actual));

            foreach ($actual as $line) {
                if ($line instanceof CapturedSourceLine) {
                    self::assertSame(0, $line->width);
                }
            }
        }
    }

    #[TestDox('The captured columns point to the boundaries of the fragment')]
    public function testCapturedColumnsPointToTheFragmentBoundaries(): void
    {
        $source = "line 1\nline 2";

        foreach (self::readString($source, 2, 8, 0) as $line) {
            self::assertInstanceOf(CapturedSourceLine::class, $line);

            $captured = \substr($line->value, $line->startColumn - 1, $line->width);

            self::assertSame($line->number === 1 ? 'ne 1' : 'lin', $captured);
        }
    }

    #[TestDox('The captured column points to the character located at the captured offset')]
    public function testCapturedColumnPointsToTheCapturedOffset(): void
    {
        for ($offset = 0, $length = \strlen(self::SOURCE); $offset < $length; ++$offset) {
            $captured = self::findFirstCapturedLine(self::readString(self::SOURCE, $offset, 0, 2));

            self::assertSame(
                self::SOURCE[$offset],
                self::SOURCE[$captured->offset + $captured->startColumn - 1],
                \sprintf('The column of the offset %d is expected to point to the same character', $offset),
            );
        }
    }

    #[TestDox('An offset pointing to the delimiter belongs to the preceding line')]
    public function testOffsetInsideDelimiterBelongsToThePrecedingLine(): void
    {
        $captured = self::findFirstCapturedLine(self::readString(self::SOURCE, 6, 0, 0));

        self::assertSame(1, $captured->number);
        self::assertSame(7, $captured->startColumn);
    }

    #[TestDox('The column never exceeds the length of the line')]
    public function testColumnNeverExceedsTheLengthOfTheLine(): void
    {
        // The offset points to the "\n" of the "\r\n" delimiter.
        $captured = self::findFirstCapturedLine(self::readString("line 1\r\nline 2", 7, 0, 0));

        self::assertSame(1, $captured->number);
        self::assertSame(7, $captured->startColumn);
        self::assertSame(0, $captured->width);
    }

    #[TestDox('A negative offset is reduced to the beginning of the source')]
    public function testNegativeOffsetIsReducedToTheBeginningOfSource(): void
    {
        $captured = self::findFirstCapturedLine(self::readString(self::SOURCE, -42, 0, 0));

        self::assertSame(1, $captured->number);
        self::assertSame(1, $captured->startColumn);
    }

    #[TestDox('An offset outside the source is reduced to the end of the source')]
    public function testOverflowedOffsetIsReducedToTheEndOfSource(): void
    {
        $captured = self::findFirstCapturedLine(self::readString(self::SOURCE, 42_000, 0, 0));

        self::assertSame(7, $captured->number);
        self::assertSame(7, $captured->startColumn);
    }

    #[TestDox('A negative length is reduced to an empty fragment')]
    public function testNegativeLengthIsReducedToAnEmptyFragment(): void
    {
        self::assertSame(
            self::describe(self::readString(self::SOURCE, 23, 0, 0)),
            self::describe(self::readString(self::SOURCE, 23, -42, 0)),
        );
    }

    #[TestDox('A length outside the source is reduced to the end of the source')]
    public function testOverflowedLengthIsReducedToTheEndOfSource(): void
    {
        self::assertSame(
            self::describe(self::readString(self::SOURCE, 42, 6, 0)),
            self::describe(self::readString(self::SOURCE, 42, 42_000, 0)),
        );
    }

    #[TestDox('The empty line closing the source is captured by a fragment ending outside of it')]
    public function testOverflowedLengthCapturesTheTrailingEmptyLine(): void
    {
        self::assertSame([
            '>#1@0:1-7: line 1',
            '>#2@7:1-1: ',
        ], self::describe(self::readString("line 1\n", 0, 42_000, 0)));
    }

    #[TestDox('Reads the snippet from a file in the same way as from a string')]
    public function testReadsFile(): void
    {
        $pathname = self::createSourceFile(self::SOURCE);

        try {
            self::assertSame(
                self::describe(self::readString(self::SOURCE, 23, 15, 2)),
                self::describe(self::readFile($pathname, 23, 15, 2)),
            );
        } finally {
            @\unlink($pathname);
        }
    }

    #[TestDox('Reading a non-existent file is not allowed')]
    public function testReadsNonExistentFile(): void
    {
        $this->expectException(SourceNotReadableException::class);

        self::readFile(__DIR__ . '/non-existent-file.txt', 0);
    }

    #[TestDox('Reading a directory instead of a file is not allowed')]
    public function testReadsDirectory(): void
    {
        $this->expectException(SourceNotReadableException::class);

        self::readFile(__DIR__, 0);
    }

    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     * @param int<0, max> $lines
     * @return array<int<1, max>, SourceLine>
     */
    private static function readString(
        string $code,
        int $offset,
        int $length = 0,
        int $lines = ErrorInfoResult::DEFAULT_LINES_AROUND,
    ): array {
        return new SourceLineReader()
            ->read(new StringContent($code), $offset, $length, $lines);
    }

    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     * @param int<0, max> $lines
     * @return array<int<1, max>, SourceLine>
     * @throws SourceNotReadableException
     */
    private static function readFile(
        string $pathname,
        int $offset,
        int $length = 0,
        int $lines = ErrorInfoResult::DEFAULT_LINES_AROUND,
    ): array {
        return new SourceLineReader()
            ->read(new FileContent($pathname), $offset, $length, $lines);
    }

    /**
     * @param iterable<mixed, SourceLine> $lines
     * @return list<int<1, max>>
     */
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

    /**
     * @param iterable<mixed, SourceLine> $lines
     */
    private static function findFirstCapturedLine(iterable $lines): CapturedSourceLine
    {
        foreach ($lines as $line) {
            if ($line instanceof CapturedSourceLine) {
                return $line;
            }
        }

        self::fail('The result is expected to contain a captured line');
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

    /**
     * The reference implementation of the reader based on splitting the whole
     * source code by the supported delimiters.
     *
     * @param int<0, max> $lines
     * @return list<string>
     */
    private static function split(string $code, int $offset, int $length, int $lines): array
    {
        $size = \strlen($code);
        $offset = \max(0, \min($offset, $size));
        $end = $offset + \max(0, $length);

        /** @var list<array{string, int<0, max>}>|false $parts */
        $parts = \preg_split('/\r\n|\n/', $code, -1, \PREG_SPLIT_OFFSET_CAPTURE);

        if ($parts === false) {
            self::fail('Unable to split the source code');
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
                    self::calculateColumn($offset, $start, $value),
                    self::calculateColumn($end, $start, $value),
                    $value,
                )
                : \sprintf(' #%d@%d: %s', $i + 1, $start, $value);
        }

        return $result;
    }

    /**
     * @return int<1, max>
     */
    private static function calculateColumn(int $offset, int $start, string $value): int
    {
        return \max(1, \min($offset - $start + 1, \strlen($value) + 1));
    }

    /**
     * @return non-empty-string
     */
    private static function createSourceFile(string $content): string
    {
        $pathname = \tempnam(\sys_get_temp_dir(), 'phplrt-snippet-');

        if ($pathname === false || \file_put_contents($pathname, $content) === false) {
            self::fail('Unable to create a temporary source file');
        }

        return $pathname;
    }
}
