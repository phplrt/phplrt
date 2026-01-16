<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Position;

interface PositionInterface
{
    /**
     * @var int<1, max>
     */
    public const MIN_LINE = 1;

    /**
     * @var int<1, max>
     */
    public const MIN_COLUMN = 1;

    /**
     * @var int<0, max>
     */
    public const MIN_OFFSET = 0;

    /**
     * Returns offset in bytes.
     *
     * Equivalent to the amount from a line and a column.
     *
     * @return int<0, max>
     */
    public function getOffset(): int;

    /**
     * Returns the position line.
     *
     * @return int<1, max>
     */
    public function getLine(): int;

    /**
     * Returns the position column.
     *
     * @return int<1, max>
     */
    public function getColumn(): int;
}
