<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Contracts\Position;

interface PositionInterface
{
    /**
     * @var positive-int
     */
    public const MIN_LINE = 1;

    /**
     * @var positive-int
     */
    public const MIN_COLUMN = 1;

    /**
     * @var positive-int|0
     */
    public const MIN_OFFSET = 0;

    /**
     * Returns offset in bytes.
     *
     * Equivalent to the amount from a line and a column.
     *
     * @return positive-int|0
     */
    public function getOffset(): int;

    /**
     * Returns the position line.
     *
     * @return positive-int
     */
    public function getLine(): int;

    /**
     * Returns the position column.
     *
     * @return positive-int
     */
    public function getColumn(): int;
}
