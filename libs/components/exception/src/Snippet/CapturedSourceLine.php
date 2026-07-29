<?php

declare(strict_types=1);

namespace Phplrt\Exception\Snippet;

/**
 * A line of the source code containing a part of the captured (error) fragment.
 */
final readonly class CapturedSourceLine extends SourceLine
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
         * The 1-based column of the first captured character of the line.
         *
         * @var int<1, max>
         */
        public int $startColumn,
        /**
         * The number of columns captured on the line, counted from
         * {@see $startColumn}.
         *
         * A line the fragment only passes through captures no column of its
         * own, which is how a fragment spanning several lines is told from one
         * pointing at a position.
         *
         * @var int<0, max>
         */
        public int $width,
    ) {
        parent::__construct($number, $offset, $value);
    }
}
