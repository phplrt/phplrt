<?php

declare(strict_types=1);

namespace Phplrt\Exception\Snippet;

use Phplrt\Exception\Analysis\FailureInterval;

/**
 * A line of the source code containing a part of the captured (error) fragment.
 *
 * @readonly
 */
final class CapturedSourceLine extends SourceLine
{
    /**
     * @param int<1, max> $number
     * @param int<0, max> $offset
     */
    public function __construct(
        int $number,
        int $offset,
        string $value,
        /**
         * The part of the captured fragment this line contains, counted in
         * bytes from the beginning of the line.
         *
         * A line the fragment only passes through contains no bytes of it,
         * which is how a fragment spanning several lines is told from one
         * pointing at a position.
         */
        public readonly FailureInterval $captured,
    ) {
        parent::__construct($number, $offset, $value);
    }
}
