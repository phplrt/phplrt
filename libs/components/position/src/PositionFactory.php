<?php

declare(strict_types=1);

namespace Phplrt\Position;

use Phplrt\Contracts\Position\PositionFactoryInterface;
use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Source\ReadableStreamInterface;
use Phplrt\Position\Exception\InvalidArgumentException;
use Phplrt\Position\Exception\NotRewindableException;

/**
 * Calculates positions by counting the line delimiters the source holds.
 *
 * The source is read from its beginning, which a source that can be rewound
 * survives untouched: it is left at the position it has been given at. The
 * one that cannot be rewound is left at the end of the data that has been
 * read out of it, and the one that has already given a part of its data away
 * is not accepted at all.
 */
final readonly class PositionFactory implements PositionFactoryInterface
{
    /**
     * The number of bytes read at once by default.
     *
     * @var int<1, max>
     */
    public const int DEFAULT_CHUNK_SIZE = 65536;

    /**
     * @var non-empty-string
     */
    private const string LINE_DELIMITER = "\n";

    /**
     * The number of bytes read at once.
     *
     * @var int<1, max>
     */
    private int $chunkSize;

    /**
     * @throws InvalidArgumentException When the number of bytes is not positive
     */
    public function __construct(int $chunkSize = self::DEFAULT_CHUNK_SIZE)
    {
        if ($chunkSize < 1) {
            throw InvalidArgumentException::becauseChunkSizeIsNotPositive($chunkSize);
        }

        $this->chunkSize = $chunkSize;
    }

    /**
     * @throws NotRewindableException When the source has already given a part
     *         of its data away and cannot be rewound
     * @throws SourceExceptionInterface may occur when it is not possible to
     *         read source's data
     */
    public function createFromOffset(ReadableInterface $source, int $offset): Position
    {
        // The beginning of any source is known in advance, so there is
        // nothing to read in order to find it out.
        if ($offset <= 0) {
            return new Position();
        }

        return $this->calculatePosition($source, $offset);
    }

    /**
     * @return int<0, max>
     * @throws NotRewindableException When the source has already given a part
     *         of its data away and cannot be rewound
     * @throws SourceExceptionInterface may occur when it is not possible to
     *         read source's data
     */
    public function createOffsetFromPosition(ReadableInterface $source, PositionInterface $position): int
    {
        $line = \max(PositionInterface::MIN_LINE, $position->line);
        $column = \max(PositionInterface::MIN_COLUMN, $position->column);

        if ($line === PositionInterface::MIN_LINE && $column === PositionInterface::MIN_COLUMN) {
            return 0;
        }

        // The offset of the beginning of the chunk being read.
        $base = 0;

        // The line the reading is in.
        $current = PositionInterface::MIN_LINE;

        // The number of bytes left to be walked along the line the position
        // points at.
        $remaining = $column - PositionInterface::MIN_COLUMN;

        foreach ($this->read($source, \PHP_INT_MAX) as $chunk) {
            $length = \strlen($chunk);
            $index = 0;

            while ($current < $line) {
                $delimiter = \strpos($chunk, self::LINE_DELIMITER, $index);

                if ($delimiter === false) {
                    $index = $length;

                    break;
                }

                ++$current;
                $index = $delimiter + 1;
            }

            if ($current === $line && $remaining > 0 && $index < $length) {
                // A column pointing beyond the end of its line is not walked
                // any further than the line itself goes.
                $rest = \substr($chunk, $index, $remaining);
                $delimiter = \strpos($rest, self::LINE_DELIMITER);

                if ($delimiter !== false) {
                    return $base + $index + $delimiter;
                }

                $index += \strlen($rest);
                $remaining -= \strlen($rest);
            }

            if ($current === $line && $remaining === 0) {
                return $base + $index;
            }

            $base += $length;
        }

        return $base;
    }

    /**
     * Reads the source from its beginning up to the given number of bytes,
     * or up to the end of it in case there is less data than that.
     *
     * @param int<1, max> $limit
     * @throws NotRewindableException When the source has already given a part
     *         of its data away and cannot be rewound
     * @throws SourceExceptionInterface may occur when it is not possible to
     *         read source's data
     */
    private function calculatePosition(ReadableInterface $source, int $limit): Position
    {
        $line = PositionInterface::MIN_LINE;
        $column = PositionInterface::MIN_COLUMN;

        foreach ($this->read($source, $limit) as $chunk) {
            $delimiter = \strrpos($chunk, self::LINE_DELIMITER);

            if ($delimiter === false) {
                $column += \strlen($chunk);
            } else {
                $line += \substr_count($chunk, self::LINE_DELIMITER);
                $column = \strlen($chunk) - $delimiter;
            }
        }

        return new Position($line, \max(PositionInterface::MIN_COLUMN, $column));
    }

    /**
     * Returns the data of the source in chunks, starting at the beginning of
     * it and stopping at the given number of bytes or at the end of the
     * source, whichever comes first.
     *
     * @param int<1, max> $limit
     * @return iterable<mixed, string>
     * @throws NotRewindableException When the source has already given a part
     *         of its data away and cannot be rewound
     * @throws SourceExceptionInterface may occur when it is not possible to
     *         read source's data
     */
    private function read(ReadableInterface $source, int $limit): iterable
    {
        if ($source->isSeekable) {
            return $this->readRewound($source, $limit);
        }

        if ($source->offset !== 0) {
            throw NotRewindableException::becauseSourceIsConsumed($source->offset);
        }

        return $this->readForward($source, $limit);
    }

    /**
     * Reads the source from its beginning and gives it back at the position
     * it has been taken at.
     *
     * @param int<1, max> $limit
     * @return iterable<mixed, string>
     * @throws SourceExceptionInterface may occur when it is not possible to
     *         read source's data
     */
    private function readRewound(ReadableInterface $source, int $limit): iterable
    {
        $restore = $source->offset;
        $source->offset = 0;

        try {
            yield from $this->readForward($source, $limit);
        } finally {
            $source->offset = $restore;
        }
    }

    /**
     * @param int<1, max> $limit
     * @return iterable<mixed, string>
     * @throws SourceExceptionInterface may occur when it is not possible to
     *         read source's data
     */
    private function readForward(ReadableStreamInterface $source, int $limit): iterable
    {
        // Nothing beyond the limit is taken out of the source, so the last
        // chunk is the one the limit falls into.
        for ($rest = $limit; $rest >= 1; $rest -= \strlen($chunk)) {
            $chunk = $source->read(\min($this->chunkSize, $rest));

            if ($chunk === '') {
                break;
            }

            yield $chunk;
        }
    }
}
