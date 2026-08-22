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

    public function createAtStarting(): Position
    {
        return new Position();
    }

    /**
     * @throws NotRewindableException When the source has already given a part
     *         of its data away and cannot be rewound
     * @throws SourceExceptionInterface may occur when it is not possible to
     *         read source's data
     */
    public function createAtEnding(ReadableInterface $source): Position
    {
        return $this->calculatePosition($source, null);
    }

    /**
     * @throws NotRewindableException When the source has already given a part
     *         of its data away and cannot be rewound
     * @throws SourceExceptionInterface may occur when it is not possible to
     *         read source's data
     */
    public function createFromOffset(ReadableInterface $source, int $offset = 0): Position
    {
        // The beginning of any source is known in advance, so there is
        // nothing to read in order to find it out.
        if ($offset <= 0) {
            return $this->createAtStarting();
        }

        return $this->calculatePosition($source, $offset);
    }

    /**
     * Returns the offset in bytes from the beginning of the source to the
     * given position.
     *
     * A position pointing beyond the end of its line is corrected to the end
     * of it, and the one pointing beyond the end of the source is corrected
     * to the end of the source.
     *
     * @api
     *
     * @return int<0, max>
     * @throws NotRewindableException When the source has already given a part
     *         of its data away and cannot be rewound
     * @throws SourceExceptionInterface may occur when it is not possible to
     *         read source's data
     */
    public function calculateOffset(ReadableInterface $source, PositionInterface $position): int
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

        foreach ($this->read($source) as $chunk) {
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
     * Reads the source from its beginning up to the given number of bytes or
     * up to the end of it in case of no limit.
     *
     * @param int<1, max>|null $limit
     * @throws NotRewindableException When the source has already given a part
     *         of its data away and cannot be rewound
     * @throws SourceExceptionInterface may occur when it is not possible to
     *         read source's data
     */
    private function calculatePosition(ReadableInterface $source, ?int $limit): Position
    {
        $line = PositionInterface::MIN_LINE;
        $column = PositionInterface::MIN_COLUMN;

        // The number of bytes that have been read.
        $read = 0;

        foreach ($this->read($source) as $chunk) {
            if ($limit !== null && $read + \strlen($chunk) > $limit) {
                $chunk = \substr($chunk, 0, $limit - $read);
            }

            $length = \strlen($chunk);
            $delimiter = \strrpos($chunk, self::LINE_DELIMITER);

            if ($delimiter === false) {
                $column += $length;
            } else {
                $line += \substr_count($chunk, self::LINE_DELIMITER);
                $column = $length - $delimiter;
            }

            $read += $length;

            if ($limit !== null && $read >= $limit) {
                break;
            }
        }

        return new Position($line, \max(PositionInterface::MIN_COLUMN, $column));
    }

    /**
     * Returns the data of the source in chunks, starting at the beginning
     * of it.
     *
     * @return iterable<mixed, string>
     * @throws NotRewindableException When the source has already given a part
     *         of its data away and cannot be rewound
     * @throws SourceExceptionInterface may occur when it is not possible to
     *         read source's data
     */
    private function read(ReadableInterface $source): iterable
    {
        if ($source->isSeekable) {
            return $this->readRewound($source);
        }

        if ($source->offset !== 0) {
            throw NotRewindableException::becauseSourceIsConsumed($source->offset);
        }

        return $this->readForward($source);
    }

    /**
     * Reads the source from its beginning and gives it back at the position
     * it has been taken at.
     *
     * @return iterable<mixed, string>
     * @throws SourceExceptionInterface may occur when it is not possible to
     *         read source's data
     */
    private function readRewound(ReadableInterface $source): iterable
    {
        $restore = $source->offset;
        $source->offset = 0;

        try {
            yield from $this->readForward($source);
        } finally {
            $source->offset = $restore;
        }
    }

    /**
     * @return iterable<mixed, string>
     * @throws SourceExceptionInterface may occur when it is not possible to
     *         read source's data
     */
    private function readForward(ReadableStreamInterface $source): iterable
    {
        while (($chunk = $source->read($this->chunkSize)) !== '') {
            yield $chunk;
        }
    }
}
