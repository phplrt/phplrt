<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Position;

interface PositionInterface
{
    /**
     * @var int<1, max>
     */
    public const int MIN_LINE = 1;

    /**
     * @var int<1, max>
     */
    public const int MIN_COLUMN = 1;

    /**
     * @var int<0, max>
     */
    public const int MIN_OFFSET = 0;

    /**
     * Gets offset in bytes.
     *
     * Equivalent to the amount from a line and a column.
     *
     * @var int<0, max>
     */
    public int $offset {
        get;
    }

    /**
     * Gets the position line.
     *
     * @var int<1, max>
     */
    public int $line {
        get;
    }

    /**
     * Gets the position column.
     *
     * @var int<1, max>
     */
    public int $column {
        get;
    }
}
