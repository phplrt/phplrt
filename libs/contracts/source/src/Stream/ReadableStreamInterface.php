<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Source\Stream;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;

/**
 * A cursor over the bytes of a source.
 *
 * A cursor carries a position of its own, so several of them are able to read
 * the very same source independently of each other.
 */
interface ReadableStreamInterface
{
    /**
     * Gets the position the next read starts at, counted in bytes from the
     * beginning of the source.
     *
     * @var int<0, max>
     */
    public int $offset {
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
