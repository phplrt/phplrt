<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Position;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Converts the offsets inside a source into the positions and back.
 */
interface PositionFactoryInterface
{
    /**
     * Creates the position of the given offset inside the given source.
     *
     * An offset beyond the end of the source MUST be corrected to the end
     * of it.
     *
     * @param int<0, max> $offset the offset in bytes from the beginning of
     *        the source
     * @return PositionInterface the position of the given offset
     * @throws SourceExceptionInterface if the data of the given source cannot
     *         be read
     */
    public function createFromOffset(ReadableInterface $source, int $offset): PositionInterface;

    /**
     * Creates the offset in bytes from the beginning of the given source the
     * given position points at.
     *
     * A position beyond the end of its own line MUST be corrected to the end
     * of that line, and a position beyond the end of the source MUST be
     * corrected to the end of the source.
     *
     * @return int<0, max> the offset the given position points at
     * @throws SourceExceptionInterface if the data of the given source cannot
     *         be read
     */
    public function createOffsetFromPosition(ReadableInterface $source, PositionInterface $position): int;
}
