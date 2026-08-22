<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Position;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Calculates {@see PositionInterface} objects over a source.
 */
interface PositionFactoryInterface
{
    /**
     * Creates a position pointing at the beginning of any source.
     */
    public function createAtStarting(): PositionInterface;

    /**
     * Creates a position pointing right after the last byte of the source.
     *
     * @throws SourceExceptionInterface may occur when it is not possible to
     *         read source's data
     */
    public function createAtEnding(ReadableInterface $source): PositionInterface;

    /**
     * Creates a position of the given offset within the source.
     *
     * An offset pointing beyond the end of the source is corrected to the
     * end of it.
     *
     * @param int<0, max> $offset the offset in bytes from the beginning of
     *        the source
     * @throws SourceExceptionInterface may occur when it is not possible to
     *         read source's data
     */
    public function createFromOffset(ReadableInterface $source, int $offset = 0): PositionInterface;
}
