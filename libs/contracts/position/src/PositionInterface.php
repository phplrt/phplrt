<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Position;

/**
 * A human-readable location inside a source.
 *
 * The line of a position MUST be counted from the beginning of the source and
 * the column MUST be counted from the beginning of its own line, both starting
 * at one.
 *
 * An implementation MUST be immutable.
 *
 * @readonly
 */
interface PositionInterface
{
    /**
     * The minimal line number a position is allowed to have.
     *
     * @var int<1, max>
     */
    public const int MIN_LINE = 1;

    /**
     * The minimal column number a position is allowed to have.
     *
     * @var int<1, max>
     */
    public const int MIN_COLUMN = 1;

    /**
     * The number of the source line the position points at.
     *
     * @var int<1, max>
     */
    public int $line {
        get;
    }

    /**
     * The number of the column within its own line the position points at.
     *
     * @var int<1, max>
     */
    public int $column {
        get;
    }
}
