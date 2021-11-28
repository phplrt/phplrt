<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Interval;

use Phplrt\Contracts\Interval\IntervalInterface;
use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Contracts\Source\ReadableInterface;

interface FactoryInterface
{
    /**
     * @param ReadableInterface $source
     * @param positive-int|0 $offset
     * @param positive-int|0 $length
     * @return IntervalInterface
     */
    public function fromOffset(
        ReadableInterface $source,
        int $offset = PositionInterface::MIN_OFFSET,
        int $length = 0
    ): IntervalInterface;

    /**
     * @param ReadableInterface $source
     * @param positive-int $line
     * @param positive-int $column
     * @param positive-int|0 $length
     * @return IntervalInterface
     */
    public function fromLineAndColumn(
        ReadableInterface $source,
        int $line = PositionInterface::MIN_LINE,
        int $column = PositionInterface::MIN_COLUMN,
        int $length = 0
    ): IntervalInterface;
}
