<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Contracts\Position;

interface IntervalInterface
{
    /**
     * Returns the position of the start of the interval.
     *
     * @return PositionInterface
     */
    public function fromPosition(): PositionInterface;

    /**
     * Returns the position of the end of the interval.
     *
     * @return PositionInterface
     */
    public function toPosition(): PositionInterface;

    /**
     * Returns the length from the beginning to the end of the interval.
     *
     * @return positive-int|0
     */
    public function getLength(): int;
}
