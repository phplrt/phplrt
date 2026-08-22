<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Source;

/**
 * A cursor over a source of a known size.
 *
 * Such a source is guaranteed to end, and how many bytes it holds is known
 * before anything has been read out of it.
 */
interface FiniteStreamInterface extends ReadableStreamInterface
{
    /**
     * Gets the size of the source in bytes.
     *
     * @var int<0, max>
     */
    public int $size {
        get;
    }
}
