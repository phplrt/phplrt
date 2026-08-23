<?php

declare(strict_types=1);

namespace Phplrt\Exception\Snippet\Reader;

use Phplrt\Contracts\Position\PositionFactoryInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Exception\Snippet\CapturedSourceLine;
use Phplrt\Exception\Snippet\SourceLine;
use Phplrt\Position\PositionFactory;

/**
 * Reads the lines of the source code around the captured (error) fragment.
 */
final readonly class SourceLineReader
{
    /**
     * The default number of bytes read at once.
     *
     * @var int<1, max>
     */
    public const int DEFAULT_CHUNK_SIZE = 8192;

    /**
     * @var non-empty-string
     */
    private const string DELIMITER_ANCHOR = "\n";

    /**
     * @var non-empty-string
     */
    private const string DELIMITER_EXTRA = "\r";

    public function __construct(
        /**
         * The factory telling which line and column of the source the
         * captured fragment is located at.
         */
        private PositionFactoryInterface $positions = new PositionFactory(),
        /**
         * The number of bytes read at once.
         *
         * @var int<1, max>
         */
        private int $chunkSize = self::DEFAULT_CHUNK_SIZE,
    ) {}

    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     * @param int<0, max> $lines
     * @return array<int<1, max>, SourceLine>
     * @throws SourceExceptionInterface in case the source cannot be read
     */
    public function read(ReadableInterface $source, int $offset, int $length, int $lines): array
    {
        return $this->readLines($source, \max(0, $offset), \max(0, $length), \max(0, $lines));
    }

    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     * @param int<0, max> $lines
     * @return array<int<1, max>, SourceLine>
     * @throws SourceExceptionInterface
     */
    private function readLines(ReadableInterface $source, int $offset, int $length, int $lines): array
    {
        $number = $this->positions->createFromOffset($source, $offset)->line;

        $above = \min($lines, $number - 1);
        $first = \max(1, $number - $above);

        $end = $offset + \max(0, \min($length, \PHP_INT_MAX - $offset));

        $result = [];
        $current = $first;
        $trailing = $lines;

        foreach ($this->walk($source, $this->findLineOffset($source, $offset, $above)) as [$start, $value]) {
            if ($current < $number) {
                $result[$current] = new SourceLine($current, $start, $value);
                ++$current;

                continue;
            }

            // A fragment ending right at the beginning of a line does not
            // capture this line.
            $captured = $current === $number || $start < $end;

            if (!$captured && $trailing-- === 0) {
                break;
            }

            $startColumn = $this->calculateColumn($offset, $start, $value);

            $result[$current] = $captured
                ? new CapturedSourceLine(
                    $current,
                    $start,
                    $value,
                    $startColumn,
                    // The fragment may well begin on an earlier line, in which
                    // case it captures this one from its very first column
                    \max(0, $this->calculateColumn($end, $start, $value) - $startColumn),
                )
                : new SourceLine($current, $start, $value);

            ++$current;
        }

        return $result;
    }

    /**
     * Returns the offset of the beginning of the line located the given
     * number of lines above the one the offset points at.
     *
     * @param int<0, max> $offset
     * @param int<0, max> $lines
     * @return int<0, max>
     * @throws SourceExceptionInterface
     */
    private function findLineOffset(ReadableInterface $source, int $offset, int $lines): int
    {
        // The line the offset points at begins right after the delimiter
        // closing the line above it, so one more delimiter is passed than
        // the number of lines being stepped over.
        $expected = $lines + 1;

        for ($width = $this->chunkSize;; $width *= 2) {
            $from = \max(0, $offset - $width);
            $window = $this->readAt($source, $from, \min($offset, $width));

            $index = \strlen($window);
            $found = 0;

            while ($found < $expected && $index > 0) {
                // A negative offset limits the search to the occurrences
                // starting before the position found the last time.
                $delimiter = \strrpos($window, self::DELIMITER_ANCHOR, $index - \strlen($window) - 1);

                if ($delimiter === false) {
                    $index = 0;

                    break;
                }

                $index = $delimiter;
                ++$found;
            }

            if ($found === $expected) {
                return \max(0, $from + $index + 1);
            }

            // The source begins before the window does, so there is nothing
            // left to step over.
            if ($from === 0) {
                return 0;
            }
        }
    }

    /**
     * Reads the given number of bytes located at the given offset.
     *
     * @param int<0, max> $from
     * @param int<0, max> $length
     * @throws SourceExceptionInterface
     */
    private function readAt(ReadableInterface $source, int $from, int $length): string
    {
        $result = '';

        while (($rest = $length - \strlen($result)) > 0) {
            $chunk = $source->read($from + \strlen($result), \min($this->chunkSize, $rest));

            if ($chunk === '') {
                break;
            }

            $result .= $chunk;
        }

        return $result;
    }

    /**
     * Reads the source line by line, starting at the given offset.
     *
     * A line ends wherever its delimiter is, and the data left after the last
     * delimiter is the line the source ends with.
     *
     * @param int<0, max> $from
     * @return iterable<mixed, array{int<0, max>, string}>
     * @throws SourceExceptionInterface
     */
    private function walk(ReadableInterface $source, int $from): iterable
    {
        $buffer = '';
        $start = $from;
        $at = $from;
        $isEof = false;

        while (true) {
            $anchor = \strpos($buffer, self::DELIMITER_ANCHOR);

            if ($anchor === false) {
                if (!$isEof) {
                    $chunk = $source->read($at, $this->chunkSize);
                    $isEof = $chunk === '';
                    $at += \strlen($chunk);
                    $buffer .= $chunk;

                    continue;
                }

                // The source ends without a delimiter, so whatever is left of
                // it is the last line, the "\r" of which belongs to the line
                // rather than closes it.
                yield [$start, $buffer];

                return;
            }

            $value = \substr($buffer, 0, $anchor);

            yield [$start, \str_ends_with($value, self::DELIMITER_EXTRA)
                ? \substr($value, 0, -1)
                : $value];

            $start += $anchor + 1;
            $buffer = \substr($buffer, $anchor + 1);
        }
    }

    /**
     * @param int<0, max> $offset
     * @param int<0, max> $start
     * @return int<1, max>
     */
    private function calculateColumn(int $offset, int $start, string $value): int
    {
        return \max(1, \min($offset - $start + 1, \strlen($value) + 1));
    }
}
