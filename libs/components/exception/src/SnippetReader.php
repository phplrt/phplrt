<?php

declare(strict_types=1);

namespace Phplrt\Exception;

use Phplrt\Contracts\Position\PositionFactoryInterface;
use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Exception\Analysis\FailureInterval;
use Phplrt\Exception\Analysis\FailureResult;
use Phplrt\Exception\Snippet\CapturedSourceLine;
use Phplrt\Exception\Snippet\SourceLine;
use Phplrt\Position\Position;
use Phplrt\Position\PositionFactory;

/**
 * Reads the fragment of the source code an error occurred in, along with the
 * lines around it.
 */
final readonly class SnippetReader
{
    /**
     * The number of lines read before and after the fragment by default.
     *
     * @var int<0, max>
     */
    public const int DEFAULT_LINES_AROUND = 2;

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
         * The factory telling which line of the source a fragment starts on
         * and where a line of it begins.
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
     * Returns the lines of the source the given error occurred in, indexed by
     * their own numbers.
     *
     * The lines holding the fragment of the error are the captured ones, and
     * an error that covers no fragment captures the position it has been
     * thrown at.
     *
     * @param int<0, max> $lines the number of lines read before and after
     *        the fragment
     * @return array<int<1, max>, SourceLine>
     * @throws SourceExceptionInterface in case the data of the source cannot
     *         be read
     */
    public function read(FailureResult $info, int $lines = self::DEFAULT_LINES_AROUND): array
    {
        return $this->fragment(
            $info->source,
            $info->interval ?? $this->createLineInterval($info->source, $info->position),
            $lines,
        );
    }

    /**
     * Returns the whole line the given position points at.
     *
     * An error that covers no fragment of the source tells nothing but the
     * line it occurred on, so the line itself is the fragment of it.
     *
     * @throws SourceExceptionInterface in case the data of the given source
     *         cannot be read
     */
    private function createLineInterval(ReadableInterface $source, PositionInterface $position): FailureInterval
    {
        $offset = $this->positions->createOffsetFromPosition($source, new Position($position->line));

        // A column beyond the end of its own line is corrected to that end,
        // so the widest one there is measures the line.
        $end = $this->positions->createOffsetFromPosition(
            $source,
            new Position($position->line, \PHP_INT_MAX),
        );

        return new FailureInterval($offset, \max(0, $end - $offset));
    }

    /**
     * Returns the lines of the given source holding the given fragment of it,
     * indexed by their own numbers.
     *
     * @param int<0, max> $lines the number of lines read before and after
     *        the fragment
     * @return array<int<1, max>, SourceLine>
     * @throws SourceExceptionInterface in case the data of the source cannot
     *         be read
     */
    public function fragment(
        ReadableInterface $source,
        FailureInterval $fragment,
        int $lines = self::DEFAULT_LINES_AROUND,
    ): array {
        // Invariants against the callers not covered by static analysis.
        $offset = \max(0, $fragment->offset);
        $length = \max(0, $fragment->length);
        $lines = \max(0, $lines);

        $number = $this->positions->createFromOffset($source, $offset)->line;
        $first = \max(PositionInterface::MIN_LINE, $number - $lines);
        // The end of the fragment is saturated instead of overflowing.
        $end = $offset + \max(0, \min($length, \PHP_INT_MAX - $offset));

        $from = $this->positions->createOffsetFromPosition($source, new Position($first));

        $result = [];
        $current = $first;
        $trailing = $lines;

        foreach ($this->walk($source, $from) as [$start, $value]) {
            // The line the fragment starts on is captured no matter how long
            // the fragment is, while a fragment ending right at the beginning
            // of a line leaves that line out.
            $isCaptured = $current === $number
                || ($current > $number && $start < $end);

            if (!$isCaptured && $current > $number && $trailing-- === 0) {
                break;
            }

            $result[$current] = $isCaptured
                ? $this->createCapturedLine($current, $start, $value, $offset, $end)
                : new SourceLine($current, $start, $value);

            ++$current;
        }

        return $result;
    }

    /**
     * @param int<1, max> $number
     * @param int<0, max> $start
     * @param int<0, max> $offset
     * @param int<0, max> $end
     */
    private function createCapturedLine(
        int $number,
        int $start,
        string $value,
        int $offset,
        int $end,
    ): CapturedSourceLine {
        $from = $this->calculateOffset($offset, $start, $value);

        return new CapturedSourceLine($number, $start, $value, new FailureInterval(
            offset: $from,
            // The fragment may well begin on an earlier line, in which case
            // it captures this one from its very first byte
            length: \max(0, $this->calculateOffset($end, $start, $value) - $from),
        ));
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

        while (($chunk = $source->read($at, $this->chunkSize)) !== '') {
            $at += \strlen($chunk);
            $buffer .= $chunk;

            // The line the buffer ends with is not closed by a delimiter yet,
            // so it waits for the data that follows it.
            $closed = \explode(self::DELIMITER_ANCHOR, $buffer);
            $buffer = \array_pop($closed);

            foreach ($closed as $value) {
                yield [$start, \str_ends_with($value, self::DELIMITER_EXTRA)
                    ? \substr($value, 0, -1)
                    : $value];

                $start = \max(0, $start + \strlen($value) + 1);
            }
        }

        // The source ends without a delimiter, so whatever is left of it is
        // the last line, the "\r" of which belongs to the line rather than
        // closes it.
        yield [$start, $buffer];
    }

    /**
     * Returns the offset of the given position of the source inside the line
     * starting at the given offset, counted in bytes from the beginning of
     * that line.
     *
     * A position outside the line is corrected to the nearest end of it.
     *
     * @param int<0, max> $offset
     * @param int<0, max> $start
     * @return int<0, max>
     */
    private function calculateOffset(int $offset, int $start, string $value): int
    {
        return \max(0, \min($offset - $start, \strlen($value)));
    }
}
