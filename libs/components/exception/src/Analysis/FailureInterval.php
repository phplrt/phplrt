<?php

declare(strict_types=1);

namespace Phplrt\Exception\Analysis;

/**
 * A fragment of a source, counted in bytes from the beginning of it.
 *
 * A fragment of no length is the position it starts at.
 */
final class FailureInterval
{
    /**
     * Gets the end offset in bytes of the failure interval.
     *
     * @var int<0, max>
     */
    public int $endsAt {
        get => $this->offset + $this->length;
    }

    public function __construct(
        /**
         * The offset in bytes from the beginning of the source the fragment
         * starts at.
         *
         * @var int<0, max>
         */
        public readonly int $offset,
        /**
         * The size of the fragment, in bytes.
         *
         * @var int<0, max>
         */
        public readonly int $length = 0,
    ) {}
}
