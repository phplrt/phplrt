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
 * All properties described below SHOULD BE considered actual interface
 * requirements. Their absence in the code is due to support requirements
 * for PHP versions prior to 8.4.
 *
 * @property-read int<1, max> $line The number of the source line the position
 *         points at.
 * @property-read int<1, max> $column The number of the column within its own
 *         line the position points at.
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
    public const MIN_LINE = 1;

    /**
     * The minimal column number a position is allowed to have.
     *
     * @var int<1, max>
     */
    public const MIN_COLUMN = 1;
}
