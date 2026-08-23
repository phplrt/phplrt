<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Source;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;

/**
 * A cursor over the bytes of a source.
 */
interface ReadableStreamInterface
{
    /**
     * Gets or moves the position the next read starts at, counted in bytes
     * from the beginning of the source.
     *
     * A position beyond the end of the source is allowed: reading at such a
     * position returns an empty string.
     *
     * @var int<0, max>
     */
    public int $offset {
        get;

        /**
         * @throws SourceExceptionInterface may occur during the inability to
         *         move the position
         */
        set;
    }

    /**
     * Returns {@see true} in case the position can be moved to an arbitrary
     * one, and {@see false} in case the source can only be read forwards.
     */
    public bool $isSeekable {
        get;
    }

    /**
     * Returns {@see true} in case there is nothing left to read.
     *
     * @throws SourceExceptionInterface may occur during the inability to read
     *         the source
     */
    public bool $isEof {
        get;
    }

    /**
     * Reads at most the given number of bytes starting at the current offset
     * and moves the offset by the number of bytes that have been read.
     *
     * A source with less than requested left returns everything it has, and
     * the one with nothing left returns an empty string.
     *
     * @param int<1, max> $bytes
     * @throws SourceExceptionInterface may occur during the inability to read
     *         the source
     */
    public function read(int $bytes): string;
}
