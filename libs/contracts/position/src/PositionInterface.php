<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Position;

/**
 * A human-readable location inside a source.
 *
 * Note: Implementations MUST guarantee:
 *   - {@see $line} is one-based, so that the first line of a source
 *     is {@see MIN_LINE}
 *   - {@see $column} is one-based and counted within its own {@see $line},
 *     so that the beginning of every line is {@see MIN_COLUMN}
 *
 * @readonly An implementation MUST be immutable.
 */
interface PositionInterface
{
    /**
     * Minimal allowed line number.
     *
     * @var int<1, max>
     */
    public const int MIN_LINE = 1;

    /**
     * Minimal allowed column number.
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
     * The number of the column within its own {@see $line} the position
     * points at.
     *
     * @var int<1, max>
     */
    public int $column {
        get;
    }
}
