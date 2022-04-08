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
     * @return PositionInterface
     */
    public function getFrom(): PositionInterface;

    /**
     * @return PositionInterface
     */
    public function getTo(): PositionInterface;

    /**
     * @return positive-int|0
     */
    public function getLength(): int;
}
