<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Source;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;

/**
 * A cursor that is able to read the source in an arbitrary order.
 */
interface SeekableStreamInterface extends ReadableStreamInterface
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
}
