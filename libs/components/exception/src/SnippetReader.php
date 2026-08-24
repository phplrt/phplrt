<?php

declare(strict_types=1);

namespace Phplrt\Exception;

use Phplrt\Contracts\Position\PositionFactoryInterface;
use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Exception\Analysis\FailureInterval;
use Phplrt\Exception\Analysis\FailureResult;
use Phplrt\Exception\Snippet\Internal\CapturedFragment;
use Phplrt\Exception\Snippet\Internal\LineReader;
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
     * The reader of the lines the source consists of.
     */
    private LineReader $lines;

    /**
     * @param int<1, max> $chunkSize the number of bytes read at once
     */
    public function __construct(
        /**
         * The factory telling which line of the source a fragment starts on
         * and where a line of it begins.
         */
        private PositionFactoryInterface $positions = new PositionFactory(),
        int $chunkSize = self::DEFAULT_CHUNK_SIZE,
    ) {
        $this->lines = new LineReader($chunkSize);
    }

    /**
     * Returns the lines of the source the given error occurred in, indexed by
     * their own numbers.
     *
     * The lines holding the fragment of the error are the captured ones, and
     * an error that covers no fragment captures the place it has been thrown
     * at.
     *
     * @param int<0, max> $lines the number of lines read before and after
     *        the fragment
     * @return array<int<1, max>, SourceLine>
     * @throws SourceExceptionInterface in case the data of the source cannot
     *         be read
     */
    public function read(FailureResult $info, int $lines = self::DEFAULT_LINES_AROUND): array
    {
        $lines = \max(0, $lines);

        $fragment = $this->normalize(
            fragment: $info->interval
                ?? $this->createIntervalAt($info->source, $info->position)
        );

        $position = $this->positions->createFromOffset(
            source: $info->source,
            offset: $fragment->offset,
        );

        $captured = new CapturedFragment(
            fragment: $fragment,
            number: $position->line,
        );

        $first = \max(SourceLine::MIN_NUMBER, $captured->number - $lines);
        $from = $this->findLineOffset($info->source, $first);

        return $this->select(
            lines: $this->lines->read($info->source, $from, $first),
            captured: $captured,
            trailing: $lines,
        );
    }

    /**
     * Returns the given fragment with the values static analysis does not
     * cover corrected.
     */
    private function normalize(FailureInterval $fragment): FailureInterval
    {
        $offset = \max(0, $fragment->offset);

        // The end of the fragment is saturated instead of overflowing.
        return new FailureInterval(
            offset: $offset,
            length: \max(0, \min($fragment->length, \PHP_INT_MAX - $offset)),
        );
    }

    /**
     * Returns the whole line the given position starts at, or that very place
     * in case the position points at a column of its own.
     *
     * @throws SourceExceptionInterface in case the data of the given source
     *         cannot be read
     */
    private function createIntervalAt(ReadableInterface $source, PositionInterface $position): FailureInterval
    {
        $offset = $this->positions->createOffsetFromPosition($source, $position);

        if ($position->column !== PositionInterface::MIN_COLUMN) {
            return new FailureInterval($offset);
        }

        // A column beyond the end of its own line is corrected to that end,
        // so the widest one there is measures the line.
        $end = $this->positions->createOffsetFromPosition(
            source: $source,
            position: new Position($position->line, \PHP_INT_MAX),
        );

        return new FailureInterval(
            offset: $offset,
            length: \max(0, $end - $offset),
        );
    }

    /**
     * Returns the offset the given line of the given source starts at.
     *
     * @param int<1, max> $number
     * @return int<0, max>
     * @throws SourceExceptionInterface in case the data of the given source
     *         cannot be read
     */
    private function findLineOffset(ReadableInterface $source, int $number): int
    {
        return $this->positions->createOffsetFromPosition($source, new Position($number));
    }

    /**
     * Returns the lines holding the given fragment along with the given
     * number of the lines that follow them.
     *
     * @param iterable<mixed, SourceLine> $lines
     * @param int<0, max> $trailing
     * @return array<int<1, max>, SourceLine>
     */
    private function select(iterable $lines, CapturedFragment $captured, int $trailing): array
    {
        $result = [];

        foreach ($lines as $line) {
            if ($captured->contains($line)) {
                $result[$line->number] = $captured->capture($line);

                continue;
            }

            // Nothing but the lines asked for is read after the fragment.
            if ($line->number > $captured->number && $trailing-- === 0) {
                break;
            }

            $result[$line->number] = $line;
        }

        return $result;
    }
}
