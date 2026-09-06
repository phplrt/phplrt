<?php

declare(strict_types=1);

namespace Phplrt\Position;

use Phplrt\Contracts\Position\PositionFactoryInterface;
use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Position\Exception\InvalidArgumentException;

/**
 * Calculates positions by counting the line delimiters the source holds.
 *
 * @readonly
 */
final class PositionFactory implements PositionFactoryInterface
{
    /**
     * The number of bytes read at once by default.
     *
     * @var int<1, max>
     */
    public const DEFAULT_CHUNK_SIZE = 65536;

    /**
     * @var non-empty-string
     */
    private const LINE_DELIMITER = "\n";

    /**
     * The number of bytes read at once.
     *
     * @var int<1, max>
     */
    private readonly int $chunkSize;

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
     * @throws SourceExceptionInterface may occur when it is not possible to
     *         read source's data
     */
    private function read(ReadableInterface $source, int $limit): iterable
    {
        // Nothing beyond the limit is taken out of the source, so the last
        // chunk is the one the limit falls into.
        for ($offset = 0, $rest = $limit; $rest >= 1; $rest -= \strlen($chunk)) {
            $chunk = $source->read($offset, \min($this->chunkSize, $rest));

            if ($chunk === '') {
                break;
            }

            $offset += \strlen($chunk);

            yield $chunk;
        }
    }
}
